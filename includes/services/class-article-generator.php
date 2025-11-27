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
        try {
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
            if (!$department && !empty($favorite_departments) && is_array($favorite_departments)) {
                $random_key = array_rand($favorite_departments);
                $department = isset($favorite_departments[$random_key]) ? $favorite_departments[$random_key] : null;
            }
            
            if (!$city && !empty($favorite_cities) && is_array($favorite_cities)) {
                $random_key = array_rand($favorite_cities);
                $city_id = isset($favorite_cities[$random_key]) ? intval($favorite_cities[$random_key]) : 0;
                if ($city_id > 0) {
                    $city = $this->get_city_data($city_id);
                }
            } elseif ($city && is_numeric($city)) {
                $city = $this->get_city_data(intval($city));
            }
            
            // Si on a un département mais pas de ville, récupérer des villes du département
            if ($department && (!$city || !is_array($city))) {
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
            
            // Vérifier que le titre et le contenu ne sont pas vides
            if (empty($title) || empty($content)) {
                return new WP_Error('empty_content', __('Le titre ou le contenu généré est vide.', 'osmose-ads'));
            }
            
            // Générer l'extrait (excerpt) pour SEO
            $excerpt = $this->generate_excerpt($keyword, $department, $city);
            
            // Créer l'article
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => 'draft', // Brouillon par défaut, sera publié selon le planning
                'post_type' => 'osmose_article',
                'post_author' => 1,
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                return $post_id;
            }
            
            // Générer et sauvegarder les métadonnées SEO
            $dept_name = '';
            if ($department) {
                $dept_name = $this->get_department_name($department);
            }
            $city_name = ($city && is_array($city) && isset($city['name'])) ? $city['name'] : '';
            
            $meta_title = $this->generate_meta_title($title, $keyword, $dept_name, $city_name);
            $meta_description = $this->generate_meta_description($keyword, $dept_name, $city_name);
            
            // Sauvegarder les métadonnées SEO
            update_post_meta($post_id, '_aioseo_title', $meta_title);
            update_post_meta($post_id, '_aioseo_description', $meta_description);
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            
            // Sauvegarder les autres métadonnées
            if ($keyword) {
                update_post_meta($post_id, 'article_keyword', $keyword);
            }
            if ($department) {
                update_post_meta($post_id, 'article_department', $department);
                if ($dept_name) {
                    update_post_meta($post_id, 'article_department_name', $dept_name);
                }
            }
            if ($city && is_array($city)) {
                $city_name_meta = isset($city['name']) ? $city['name'] : '';
                if ($city_name_meta) {
                    update_post_meta($post_id, 'article_city', $city_name_meta);
                }
                if (isset($city['id']) && $city['id']) {
                    update_post_meta($post_id, 'article_city_id', intval($city['id']));
                }
            }
            update_post_meta($post_id, 'article_type', $article_type);
            update_post_meta($post_id, 'article_generated_at', current_time('mysql'));
            update_post_meta($post_id, 'article_auto_generated', 1);
            
            return $post_id;
        } catch (Exception $e) {
            return new WP_Error('generation_exception', sprintf(__('Erreur lors de la génération de l\'article: %s', 'osmose-ads'), $e->getMessage()));
        } catch (Error $e) {
            return new WP_Error('generation_error', sprintf(__('Erreur fatale lors de la génération de l\'article: %s', 'osmose-ads'), $e->getMessage()));
        }
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
        if (empty($department_code)) {
            return null;
        }
        
        $cities = get_posts(array(
            'post_type' => 'city',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'department',
                    'value' => sanitize_text_field($department_code),
                    'compare' => '=',
                ),
            ),
            'orderby' => 'rand',
        ));
        
        if (empty($cities) || !is_array($cities) || !isset($cities[0]) || !isset($cities[0]->ID)) {
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
        
        $response = $this->ai_service->call_ai($prompt, '', array(
            'max_tokens' => 200,
            'temperature' => 0.7,
        ));
        
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
        
        $response = $this->ai_service->call_ai($prompt, '', array(
            'max_tokens' => 3000, // Augmenté pour plus de contenu
            'temperature' => 0.7,
        ));
        
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
        // Récupérer le nom de l'entreprise
        $company_name = get_bloginfo('name');
        
        // Récupérer les services proposés (depuis les templates actifs)
        $services = $this->get_active_services();
        $services_text = !empty($services) ? implode(', ', array_slice($services, 0, 5)) : $keyword;
        
        $prompt = "Écris un article complet et détaillé (minimum 1000 mots) en français pour un site web WordPress.\n\n";
        $prompt .= "⚠️ IMPORTANT: Tu DOIS générer du HTML VALIDE et BIEN STRUCTURÉ avec des balises appropriées.\n\n";
        
        // Définir les variables au début pour éviter les erreurs
        $dept_name = '';
        $city_name = '';
        
        $context = "Sujet principal: {$keyword}\n";
        $context .= "Nom de l'entreprise: {$company_name}\n";
        $context .= "Services proposés par l'entreprise: {$services_text}\n";
        
        if ($department) {
            $dept_name = $this->get_department_name($department);
            $context .= "Département (TU DOIS TOUJOURS utiliser le nom complet): {$dept_name} (code: {$department})\n";
        }
        
        if ($city && is_array($city)) {
            $city_name = isset($city['name']) ? $city['name'] : '';
            if ($city_name) {
                $context .= "Ville principale: {$city_name}\n";
            }
            
            // Ajouter d'autres villes du département pour enrichir
            if ($department && isset($city['id']) && $city['id']) {
                $other_cities = $this->get_other_cities_from_department($department, $city['id'], 3);
                if (!empty($other_cities)) {
                    $city_names = array_map(function($c) { return isset($c['name']) ? $c['name'] : ''; }, $other_cities);
                    $city_names = array_filter($city_names); // Enlever les valeurs vides
                    if (!empty($city_names)) {
                        $context .= "Autres villes à mentionner: " . implode(', ', $city_names) . "\n";
                    }
                }
            }
        }
        
        $prompt .= $context . "\n";
        
        $prompt .= "STRUCTURE HTML REQUISE:\n";
        $prompt .= "- Utilise <h2> pour les titres de sections principales\n";
        $prompt .= "- Utilise <h3> pour les sous-sections\n";
        $prompt .= "- Utilise <p> pour les paragraphes (minimum 3-4 phrases par paragraphe)\n";
        $prompt .= "- Utilise <ul> et <li> pour les listes\n";
        $prompt .= "- Utilise <strong> pour mettre en évidence les points importants\n";
        $prompt .= "- Ajoute des espaces entre les sections (saut de ligne)\n";
        $prompt .= "- Le contenu doit être bien aéré et lisible\n\n";
        
        $prompt .= "OBLIGATIONS ABSOLUES:\n";
        $prompt .= "1. TU DOIS mentionner le nom de l'entreprise \"{$company_name}\" au moins 3 fois dans l'article\n";
        $prompt .= "2. TU DOIS mentionner les services proposés: {$services_text}\n";
        $prompt .= "3. TU DOIS TOUJOURS utiliser le nom COMPLET du département \"{$dept_name}\" (jamais juste le code)\n";
        $prompt .= "4. TU DOIS créer un contenu riche et détaillé avec des informations utiles\n";
        $prompt .= "5. TU DOIS utiliser un langage naturel et fluide\n\n";
        
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
        
        $prompt .= "FORMAT DE SORTIE:\n";
        $prompt .= "- Génère UNIQUEMENT le contenu HTML (sans <html>, <head>, <body>)\n";
        $prompt .= "- Commence directement par le contenu (pas de titre H1, il sera ajouté séparément)\n";
        $prompt .= "- Chaque section doit être séparée par un saut de ligne\n";
        $prompt .= "- Le HTML doit être valide et bien formaté\n";
        $prompt .= "- Minimum 1000 mots de contenu réel\n\n";
        
        $prompt .= "EXEMPLE DE STRUCTURE:\n";
        $prompt .= "<p>Introduction avec mention de {$company_name} et du département {$dept_name}...</p>\n\n";
        $prompt .= "<h2>Titre de section</h2>\n";
        $prompt .= "<p>Contenu détaillé...</p>\n";
        $prompt .= "<p>Autre paragraphe...</p>\n\n";
        $prompt .= "<h3>Sous-section</h3>\n";
        $prompt .= "<p>Contenu...</p>\n\n";
        
        return $prompt;
    }
    
    /**
     * Récupérer les services actifs (depuis les templates)
     */
    private function get_active_services() {
        $templates = get_posts(array(
            'post_type' => 'ad_template',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'is_active',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        ));
        
        $services = array();
        foreach ($templates as $template) {
            $service_name = get_post_meta($template->ID, 'service_name', true);
            if ($service_name) {
                $services[] = $service_name;
            }
        }
        
        return array_unique($services);
    }
    
    /**
     * Générer l'extrait (excerpt) pour l'article
     */
    private function generate_excerpt($keyword, $department, $city) {
        $company_name = get_bloginfo('name');
        $dept_name = '';
        $city_name = '';
        
        if ($department) {
            $dept_name = $this->get_department_name($department);
        }
        
        if ($city && is_array($city) && isset($city['name'])) {
            $city_name = $city['name'];
        }
        
        $excerpt = "Découvrez tout ce qu'il faut savoir sur {$keyword}";
        if ($city_name && $dept_name) {
            $excerpt .= " à {$city_name} dans le département {$dept_name}";
        } elseif ($dept_name) {
            $excerpt .= " dans le département {$dept_name}";
        }
        $excerpt .= ". {$company_name} vous propose des solutions professionnelles et adaptées à vos besoins.";
        
        return $excerpt;
    }
    
    /**
     * Générer le meta title pour SEO
     */
    private function generate_meta_title($title, $keyword, $dept_name, $city_name) {
        $company_name = get_bloginfo('name');
        
        // Construire un titre SEO optimisé (max 60 caractères)
        $meta_title = $title;
        
        if ($city_name && $dept_name) {
            $meta_title = "{$keyword} à {$city_name} ({$dept_name}) - {$company_name}";
        } elseif ($dept_name) {
            $meta_title = "{$keyword} en {$dept_name} - {$company_name}";
        } else {
            $meta_title = "{$keyword} - {$company_name}";
        }
        
        // Limiter à 60 caractères pour SEO
        if (strlen($meta_title) > 60) {
            $meta_title = substr($meta_title, 0, 57) . '...';
        }
        
        return $meta_title;
    }
    
    /**
     * Générer la meta description pour SEO
     */
    private function generate_meta_description($keyword, $dept_name, $city_name) {
        $company_name = get_bloginfo('name');
        
        $description = "Expert en {$keyword}";
        if ($city_name && $dept_name) {
            $description .= " à {$city_name} dans le département {$dept_name}";
        } elseif ($dept_name) {
            $description .= " en {$dept_name}";
        }
        $description .= ". {$company_name} vous accompagne avec des solutions professionnelles. Devis gratuit et intervention rapide.";
        
        // Limiter à 160 caractères pour SEO
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }
        
        return $description;
    }
    
    /**
     * Récupérer d'autres villes du département
     */
    private function get_other_cities_from_department($department_code, $exclude_city_id, $limit = 3) {
        if (empty($department_code) || empty($exclude_city_id)) {
            return array();
        }
        
        $exclude_ids = is_array($exclude_city_id) ? $exclude_city_id : array(intval($exclude_city_id));
        $exclude_ids = array_filter($exclude_ids); // Enlever les valeurs vides
        
        if (empty($exclude_ids)) {
            return array();
        }
        
        $cities = get_posts(array(
            'post_type' => 'city',
            'posts_per_page' => intval($limit),
            'post__not_in' => $exclude_ids,
            'meta_query' => array(
                array(
                    'key' => 'department',
                    'value' => sanitize_text_field($department_code),
                    'compare' => '=',
                ),
            ),
            'orderby' => 'rand',
        ));
        
        $result = array();
        if ($cities && is_array($cities)) {
            foreach ($cities as $city) {
                if (isset($city->ID)) {
                    $city_data = $this->get_city_data($city->ID);
                    if ($city_data && is_array($city_data)) {
                        $result[] = $city_data;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Nettoyer et formater le contenu généré
     */
    private function clean_content($content) {
        // Nettoyer le contenu en gardant les balises HTML valides
        $allowed_tags = array(
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
        
        // Normaliser les sauts de ligne
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Ajouter des espaces entre les balises de section pour une meilleure lisibilité
        $content = preg_replace('/(<\/h2>)\s*(<h3>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/h3>)\s*(<h4>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/h2>)\s*(<p>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/h3>)\s*(<p>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/h4>)\s*(<p>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/p>)\s*(<h2>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/p>)\s*(<h3>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ul>)\s*(<h2>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ul>)\s*(<h3>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ul>)\s*(<p>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ol>)\s*(<h2>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ol>)\s*(<h3>)/i', "$1\n\n$2", $content);
        $content = preg_replace('/(<\/ol>)\s*(<p>)/i', "$1\n\n$2", $content);
        
        // S'assurer qu'il y a un saut de ligne après chaque balise de fermeture de paragraphe
        $content = preg_replace('/(<\/p>)(?!\n)/i', "$1\n", $content);
        
        // Nettoyer les espaces multiples dans le texte (mais pas entre les balises)
        $content = preg_replace('/[ \t]+/', ' ', $content);
        
        // Nettoyer les lignes vides multiples (garder max 2 lignes vides)
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }
}

