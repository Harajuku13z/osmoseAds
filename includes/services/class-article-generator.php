<?php
/**
 * Générateur d'articles automatiques avec IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Osmose_Article_Generator {
    
    private $ai_service;
    
    public function __construct() {
        if (!class_exists('AI_Service')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
        }
        $this->ai_service = new AI_Service();
    }
    
    /**
     * Générer un article
     */
    public function generate_article($keyword = null, $department = null, $city = null) {
        // Récupérer la configuration
        $keywords = $this->get_keywords();
        $favorite_cities = get_option('osmose_articles_favorite_cities', array());
        $favorite_departments = get_option('osmose_articles_favorite_departments', array());
        
        // Sélectionner un mot-clé aléatoire si non fourni
        if (!$keyword) {
            $keyword = $this->select_random_keyword($keywords);
        }
        
        if (!$keyword) {
            return new WP_Error('no_keyword', __('Aucun mot-clé configuré. Veuillez configurer les mots-clés dans la page de configuration.', 'osmose-ads'));
        }
        
        // Sélectionner un département et une ville si non fournis
        if (!$department && !empty($favorite_departments)) {
            $department = $favorite_departments[array_rand($favorite_departments)];
        }
        
        if (!$city && !empty($favorite_cities)) {
            $city_id = $favorite_cities[array_rand($favorite_cities)];
            $city = $this->get_city_data($city_id);
        } elseif ($city && is_numeric($city)) {
            $city = $this->get_city_data($city);
        }
        
        // Si on a un département mais pas de ville, récupérer des villes du département
        if ($department && !$city) {
            $city = $this->get_city_from_department($department);
        }
        
        // Déterminer le type d'article (aléatoire)
        $article_types = array('how_to', 'top_companies', 'guide');
        $article_type = $article_types[array_rand($article_types)];
        
        // Générer le titre et le contenu
        $title = $this->generate_title($keyword, $department, $city, $article_type);
        $content = $this->generate_content($keyword, $department, $city, $article_type);
        
        if (is_wp_error($title) || is_wp_error($content)) {
            return is_wp_error($title) ? $title : $content;
        }
        
        // Créer l'article
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft', // Brouillon par défaut, sera publié selon le planning
            'post_type' => 'osmose_article',
            'post_author' => 1,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Sauvegarder les métadonnées
        if ($keyword) {
            update_post_meta($post_id, 'article_keyword', $keyword);
        }
        if ($department) {
            update_post_meta($post_id, 'article_department', $department);
            $dept_name = $this->get_department_name($department);
            if ($dept_name) {
                update_post_meta($post_id, 'article_department_name', $dept_name);
            }
        }
        if ($city && is_array($city)) {
            update_post_meta($post_id, 'article_city', $city['name']);
            if (isset($city['id'])) {
                update_post_meta($post_id, 'article_city_id', $city['id']);
            }
        }
        update_post_meta($post_id, 'article_type', $article_type);
        update_post_meta($post_id, 'article_generated_at', current_time('mysql'));
        update_post_meta($post_id, 'article_auto_generated', 1);
        
        return $post_id;
    }
    
    /**
     * Récupérer les mots-clés configurés
     */
    private function get_keywords() {
        $keywords_text = get_option('osmose_articles_keywords', '');
        if (empty($keywords_text)) {
            return array();
        }
        
        $keywords = array_filter(array_map('trim', explode("\n", $keywords_text)));
        return $keywords;
    }
    
    /**
     * Sélectionner un mot-clé aléatoire
     */
    private function select_random_keyword($keywords) {
        if (empty($keywords)) {
            return null;
        }
        return $keywords[array_rand($keywords)];
    }
    
    /**
     * Récupérer les données d'une ville
     */
    private function get_city_data($city_id) {
        $city = get_post($city_id);
        if (!$city || $city->post_type !== 'city') {
            return null;
        }
        
        $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
        $department = get_post_meta($city_id, 'department', true);
        $department_name = get_post_meta($city_id, 'department_name', true);
        
        return array(
            'id' => $city_id,
            'name' => $city_name,
            'department' => $department,
            'department_name' => $department_name,
        );
    }
    
    /**
     * Récupérer une ville d'un département
     */
    private function get_city_from_department($department_code) {
        $cities = get_posts(array(
            'post_type' => 'city',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'department',
                    'value' => $department_code,
                    'compare' => '=',
                ),
            ),
            'orderby' => 'rand',
        ));
        
        if (empty($cities)) {
            return null;
        }
        
        return $this->get_city_data($cities[0]->ID);
    }
    
    /**
     * Récupérer le nom d'un département
     */
    private function get_department_name($department_code) {
        global $wpdb;
        $name = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = 'department_name' 
             AND post_id IN (
                 SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = 'department' AND meta_value = %s LIMIT 1
             ) LIMIT 1",
            $department_code
        ));
        
        return $name ?: $department_code;
    }
    
    /**
     * Générer un titre d'article
     */
    private function generate_title($keyword, $department, $city, $article_type) {
        $prompt = $this->build_title_prompt($keyword, $department, $city, $article_type);
        
        $response = $this->ai_service->generate_content($prompt, 200);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Nettoyer et extraire le titre
        $title = trim($response);
        $title = preg_replace('/^["\']|["\']$/', '', $title); // Enlever les guillemets
        $title = wp_strip_all_tags($title);
        $title = substr($title, 0, 100); // Limiter à 100 caractères
        
        return $title;
    }
    
    /**
     * Construire le prompt pour le titre
     */
    private function build_title_prompt($keyword, $department, $city, $article_type) {
        $prompt = "Génère UN SEUL titre d'article SEO optimisé (maximum 80 caractères) pour un site web français.\n\n";
        
        $context = "Mot-clé principal: {$keyword}\n";
        
        if ($department) {
            $dept_name = $this->get_department_name($department);
            $context .= "Département: {$dept_name} ({$department})\n";
        }
        
        if ($city && is_array($city)) {
            $context .= "Ville: {$city['name']}\n";
        }
        
        $prompt .= $context . "\n";
        
        $dept_name = $department ? $this->get_department_name($department) : '';
        $city_name = ($city && is_array($city)) ? $city['name'] : '';
        
        switch ($article_type) {
            case 'how_to':
                $prompt .= "Type d'article: Guide pratique \"Comment faire\"\n";
                $prompt .= "Exemples de formats:\n";
                if ($dept_name) {
                    $prompt .= "- Comment {$keyword} sa toiture en {$dept_name} ?\n";
                }
                if ($city_name) {
                    $prompt .= "- Guide: {$keyword} de toiture à {$city_name}\n";
                }
                break;
                
            case 'top_companies':
                $prompt .= "Type d'article: Liste \"Top entreprises\"\n";
                $prompt .= "Exemples de formats:\n";
                if ($dept_name) {
                    $prompt .= "- Top 10 entreprises de {$keyword} en {$dept_name}\n";
                }
                if ($city_name) {
                    $prompt .= "- Meilleurs professionnels {$keyword} à {$city_name}\n";
                }
                break;
                
            case 'guide':
                $prompt .= "Type d'article: Guide complet\n";
                $prompt .= "Exemples de formats:\n";
                if ($dept_name) {
                    $prompt .= "- Guide complet du {$keyword} en {$dept_name}\n";
                }
                if ($city_name) {
                    $prompt .= "- Tout savoir sur le {$keyword} à {$city_name}\n";
                }
                break;
        }
        
        $prompt .= "\nIMPORTANT: Génère UNIQUEMENT le titre, sans guillemets, sans numérotation, sans préfixe. Maximum 80 caractères.\n";
        
        return $prompt;
    }
    
    /**
     * Générer le contenu de l'article
     */
    private function generate_content($keyword, $department, $city, $article_type) {
        $prompt = $this->build_content_prompt($keyword, $department, $city, $article_type);
        
        $response = $this->ai_service->generate_content($prompt, 2000);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Nettoyer le contenu
        $content = $this->clean_content($response);
        
        return $content;
    }
    
    /**
     * Construire le prompt pour le contenu
     */
    private function build_content_prompt($keyword, $department, $city, $article_type) {
        $prompt = "Écris un article complet et détaillé (minimum 800 mots) en français pour un site web.\n\n";
        
        $context = "Sujet: {$keyword}\n";
        
        if ($department) {
            $dept_name = $this->get_department_name($department);
            $context .= "Département: {$dept_name} ({$department})\n";
        }
        
        if ($city && is_array($city)) {
            $context .= "Ville principale: {$city['name']}\n";
            
            // Ajouter d'autres villes du département pour enrichir
            $other_cities = $this->get_other_cities_from_department($department, $city['id'], 3);
            if (!empty($other_cities)) {
                $city_names = array_map(function($c) { return $c['name']; }, $other_cities);
                $context .= "Autres villes à mentionner: " . implode(', ', $city_names) . "\n";
            }
        }
        
        $prompt .= $context . "\n";
        
        switch ($article_type) {
            case 'how_to':
                $prompt .= "Type d'article: Guide pratique \"Comment faire\"\n\n";
                $prompt .= "Structure requise:\n";
                $prompt .= "1. Introduction (2-3 paragraphes) expliquant l'importance du sujet et mentionnant la géolocalisation (département, villes)\n";
                $prompt .= "2. Section \"Pourquoi faire cela\" avec 3-4 raisons\n";
                $prompt .= "3. Section \"Étapes à suivre\" avec 5-7 étapes détaillées\n";
                $prompt .= "4. Section \"Conseils pratiques\" avec 4-5 conseils\n";
                $prompt .= "5. Conclusion avec appel à l'action\n\n";
                if ($city_name && $dept_name) {
                    $prompt .= "Dans le texte, mentionne naturellement la géolocalisation: \"que vous soyez à {$city_name} ou dans d'autres villes du département\", \"en {$dept_name}\", etc.\n";
                } elseif ($dept_name) {
                    $prompt .= "Dans le texte, mentionne naturellement la géolocalisation: \"en {$dept_name}\", \"dans le département\", etc.\n";
                }
                break;
                
            case 'top_companies':
                $prompt .= "Type d'article: Liste \"Top entreprises\"\n\n";
                $prompt .= "Structure requise:\n";
                if ($dept_name) {
                    $prompt .= "1. Introduction (2-3 paragraphes) sur l'importance de choisir les bons professionnels en {$dept_name}\n";
                } else {
                    $prompt .= "1. Introduction (2-3 paragraphes) sur l'importance de choisir les bons professionnels\n";
                }
                $prompt .= "2. Section \"Top 10 entreprises\" avec:\n";
                $prompt .= "   - Pour chaque entreprise: nom fictif mais réaliste, localisation (ville du département), spécialités, avantages\n";
                $prompt .= "   - Commencer par une entreprise principale, puis lister les autres\n";
                $prompt .= "3. Section \"Comment choisir\" avec 4-5 critères\n";
                $prompt .= "4. Conclusion\n\n";
                if ($city_name && $dept_name) {
                    $prompt .= "Mentionne les villes du département ({$city_name} et autres villes) dans les descriptions des entreprises.\n";
                } elseif ($dept_name) {
                    $prompt .= "Mentionne les villes du département dans les descriptions des entreprises.\n";
                }
                break;
                
            case 'guide':
                $prompt .= "Type d'article: Guide complet\n\n";
                $prompt .= "Structure requise:\n";
                if ($dept_name && $city_name) {
                    $prompt .= "1. Introduction (3-4 paragraphes) avec contexte géographique ({$dept_name}, {$city_name})\n";
                } elseif ($dept_name) {
                    $prompt .= "1. Introduction (3-4 paragraphes) avec contexte géographique ({$dept_name})\n";
                } else {
                    $prompt .= "1. Introduction (3-4 paragraphes)\n";
                }
                $prompt .= "2. Section \"Qu'est-ce que\" avec explication détaillée\n";
                $prompt .= "3. Section \"Avantages\" avec 5-6 avantages\n";
                $prompt .= "4. Section \"Réglementation en France\" avec informations pertinentes\n";
                $prompt .= "5. Section \"Coûts et tarifs\" avec estimations pour la région\n";
                if ($dept_name) {
                    $prompt .= "6. Section \"Conseils pour {$dept_name}\" avec spécificités régionales\n";
                } else {
                    $prompt .= "6. Section \"Conseils pratiques\"\n";
                }
                $prompt .= "7. Conclusion\n\n";
                break;
        }
        
        $prompt .= "IMPORTANT:\n";
        $prompt .= "- Écris en français naturel et fluide\n";
        $prompt .= "- Utilise des balises HTML appropriées (<h2>, <h3>, <p>, <ul>, <li>)\n";
        $prompt .= "- Intègre naturellement la géolocalisation dans le texte\n";
        $prompt .= "- Le contenu doit être informatif et utile\n";
        $prompt .= "- Minimum 800 mots\n";
        
        return $prompt;
    }
    
    /**
     * Récupérer d'autres villes du département
     */
    private function get_other_cities_from_department($department_code, $exclude_city_id, $limit = 3) {
        $cities = get_posts(array(
            'post_type' => 'city',
            'posts_per_page' => $limit,
            'post__not_in' => array($exclude_city_id),
            'meta_query' => array(
                array(
                    'key' => 'department',
                    'value' => $department_code,
                    'compare' => '=',
                ),
            ),
            'orderby' => 'rand',
        ));
        
        $result = array();
        foreach ($cities as $city) {
            $city_data = $this->get_city_data($city->ID);
            if ($city_data) {
                $result[] = $city_data;
            }
        }
        
        return $result;
    }
    
    /**
     * Nettoyer le contenu généré
     */
    private function clean_content($content) {
        // Enlever les balises HTML indésirables mais garder les balises de structure
        $allowed_tags = '<h2><h3><h4><p><ul><ol><li><strong><em><a><br>';
        $content = wp_kses($content, array(
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'p' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'strong' => array(),
            'em' => array(),
            'a' => array('href' => array(), 'title' => array()),
            'br' => array(),
        ));
        
        // Nettoyer les espaces multiples
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\n\s*\n/', "\n\n", $content);
        
        return trim($content);
    }
}

