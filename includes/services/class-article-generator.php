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
            
            // Déterminer le type d'article (aléatoire, mais moins souvent top_companies)
            // 40% how_to, 20% top_companies, 40% guide
            $rand = rand(1, 100);
            if ($rand <= 40) {
                $article_type = 'how_to';
            } elseif ($rand <= 60) {
                $article_type = 'top_companies';
            } else {
                $article_type = 'guide';
            }
            
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
            
            // Générer l'extrait (excerpt) pour SEO (max 160 caractères)
            $excerpt = $this->generate_excerpt($keyword, $department, $city);
            
            // Ajouter des liens internes vers le CTA/devis dans le contenu
            $content = $this->add_internal_links($content, $keyword);
            
            // Insérer les images configurées dans le contenu
            $content = $this->insert_article_images($content, $keyword, $title);
            
            // Créer l'article comme un post WordPress standard
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => 'draft', // Brouillon par défaut, sera publié selon le planning
                'post_type' => 'post', // Utiliser le post_type standard pour apparaître dans edit.php
                'post_author' => 1,
                'post_category' => array(1), // Catégorie "Uncategorized" (ID 1 par défaut)
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                return $post_id;
            }
            
            // Générer et assigner les tags WordPress
            $tags = $this->generate_tags($keyword, $department, $city);
            if (!empty($tags)) {
                wp_set_post_tags($post_id, $tags, false);
            }
            
            // S'assurer que la catégorie "Uncategorized" est assignée
            $uncategorized_id = get_option('default_category', 1);
            wp_set_post_categories($post_id, array($uncategorized_id));
            
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
        
        // Post-traitement: Remplacer le code du département par le nom si présent
        if ($department) {
            $dept_name = $this->get_department_name($department);
            if ($dept_name && $dept_name !== $department) {
                // Remplacer le code du département par le nom (avec différents formats possibles)
                $patterns = array(
                    '/\b' . preg_quote($department, '/') . '\b/',  // Code seul
                    '/\(' . preg_quote($department, '/') . '\)/',  // (56)
                    '/\s' . preg_quote($department, '/') . '\s/',  // Espaces autour
                    '/\s' . preg_quote($department, '/') . '$/',   // Fin de phrase
                    '/^' . preg_quote($department, '/') . '\s/',   // Début de phrase
                );
                
                foreach ($patterns as $pattern) {
                    $title = preg_replace($pattern, ' ' . $dept_name . ' ', $title);
                }
                
                // Nettoyer les espaces multiples
                $title = preg_replace('/\s+/', ' ', $title);
                $title = trim($title);
            }
        }
        
        $title = substr($title, 0, 100); // Limiter à 100 caractères
        
        return $title;
    }
    
    /**
     * Construire le prompt pour le titre
     */
    private function build_title_prompt($keyword, $department, $city, $article_type) {
        // Récupérer le nom du département AVANT de construire le prompt
        $dept_name = $department ? $this->get_department_name($department) : '';
        $city_name = ($city && is_array($city)) ? $city['name'] : '';
        
        $prompt = "Génère UN SEUL titre d'article SEO optimisé (maximum 80 caractères) pour un site web français.\n\n";
        
        $context = "Mot-clé principal: {$keyword}\n";
        
        if ($department && $dept_name) {
            $context .= "Département: {$dept_name} (code: {$department})\n";
            $context .= "⚠️ RÈGLE ABSOLUE: Dans le titre généré, tu DOIS utiliser UNIQUEMENT le nom complet \"{$dept_name}\".\n";
            $context .= "⚠️ INTERDICTION FORMELLE: N'utilise JAMAIS le code \"{$department}\" dans le titre.\n";
            $context .= "⚠️ Si tu utilises le code au lieu du nom, c'est une ERREUR CRITIQUE.\n";
        }
        
        if ($city && is_array($city)) {
            $context .= "Ville: {$city['name']}\n";
        }
        
        $prompt .= $context . "\n";
        
        switch ($article_type) {
            case 'how_to':
                $prompt .= "Type d'article: Guide pratique \"Comment faire\"\n";
                $prompt .= "Exemples de formats (utilise TOUJOURS le nom du département, JAMAIS le code):\n";
                if ($dept_name) {
                    $prompt .= "- Comment {$keyword} sa toiture en {$dept_name} ?\n";
                    $prompt .= "- Guide: {$keyword} de toiture en {$dept_name}\n";
                }
                if ($city_name) {
                    $prompt .= "- Guide: {$keyword} de toiture à {$city_name}\n";
                }
                break;
                
            case 'top_companies':
                $prompt .= "Type d'article: Liste \"Top entreprises\"\n";
                $prompt .= "Exemples de formats (utilise TOUJOURS le nom du département, JAMAIS le code):\n";
                if ($dept_name) {
                    $prompt .= "- Top 10 entreprises de {$keyword} en {$dept_name}\n";
                    $prompt .= "- Meilleurs professionnels {$keyword} en {$dept_name}\n";
                }
                if ($city_name) {
                    $prompt .= "- Meilleurs professionnels {$keyword} à {$city_name}\n";
                }
                break;
                
            case 'guide':
                $prompt .= "Type d'article: Guide complet\n";
                $prompt .= "Exemples de formats (utilise TOUJOURS le nom du département, JAMAIS le code):\n";
                if ($dept_name) {
                    $prompt .= "- Guide complet du {$keyword} en {$dept_name}\n";
                    $prompt .= "- {$keyword} en {$dept_name}: Guide complet\n";
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
        
        // Post-traitement: Remplacer le code du département par le nom si présent
        if ($department) {
            $dept_name = $this->get_department_name($department);
            if ($dept_name && $dept_name !== $department) {
                // Remplacer le code du département par le nom (avec différents formats possibles)
                // Ordre important : les patterns les plus spécifiques en premier
                $patterns = array(
                    // "département 22" -> "département Côtes-d'Armor"
                    '/\bdépartement\s+' . preg_quote($department, '/') . '\b/i' => 'département ' . $dept_name,
                    // "dans le département 22" -> "dans le département Côtes-d'Armor"
                    '/\bdans\s+le\s+département\s+' . preg_quote($department, '/') . '\b/i' => 'dans le département ' . $dept_name,
                    // "du département 22" -> "du département Côtes-d'Armor"
                    '/\bdu\s+département\s+' . preg_quote($department, '/') . '\b/i' => 'du département ' . $dept_name,
                    // "en 22" -> "en Côtes-d'Armor" (dans un contexte géographique)
                    '/\ben\s+' . preg_quote($department, '/') . '\b/i' => 'en ' . $dept_name,
                    // "dans le 22" -> "dans le Côtes-d'Armor"
                    '/\bdans\s+le\s+' . preg_quote($department, '/') . '\b/i' => 'dans le ' . $dept_name,
                    // "dans 22" -> "dans Côtes-d'Armor"
                    '/\bdans\s+' . preg_quote($department, '/') . '\b/i' => 'dans ' . $dept_name,
                    // "du 22" -> "du Côtes-d'Armor"
                    '/\bdu\s+' . preg_quote($department, '/') . '\b/i' => 'du ' . $dept_name,
                    // "de 22" -> "de Côtes-d'Armor"
                    '/\bde\s+' . preg_quote($department, '/') . '\b/i' => 'de ' . $dept_name,
                );
                
                foreach ($patterns as $pattern => $replacement) {
                    $content = preg_replace($pattern, $replacement, $content);
                }
                
                // Remplacer aussi les occurrences isolées du code dans un contexte géographique
                // Utiliser preg_replace_callback pour vérifier le contexte
                $content = preg_replace_callback(
                    '/\b' . preg_quote($department, '/') . '\b/i',
                    function($matches) use ($department, $dept_name) {
                        // Récupérer le contenu complet depuis la variable de closure
                        static $full_content = null;
                        if ($full_content === null) {
                            // On ne peut pas accéder directement à $content, donc on utilise une approche différente
                            // On va simplement remplacer si le contexte avant/après le match contient des mots géographiques
                            return $matches[0]; // On laisse tel quel pour l'instant, les patterns spécifiques ci-dessus gèrent déjà
                        }
                        return $matches[0];
                    },
                    $content
                );
                
                // Approche alternative : remplacer les occurrences restantes dans un contexte géographique
                // Chercher "22" précédé ou suivi de mots géographiques
                $content = preg_replace(
                    '/(\b(département|en|dans|du|de|ville|région|à|pour)\s+)' . preg_quote($department, '/') . '\b/i',
                    '$1' . $dept_name,
                    $content
                );
            }
        }
        
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
            $context .= "Département: {$dept_name}\n";
            $context .= "⚠️ CRITIQUE: Dans TOUT le contenu généré, utilise UNIQUEMENT le nom complet \"{$dept_name}\", JAMAIS le code \"{$department}\". Le code est uniquement pour référence interne.\n";
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
                $prompt .= "⚠️ IMPORTANT: NE PAS créer de liste d'entreprises dans ce type d'article. C'est un guide pratique, pas une liste.\n\n";
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
                $prompt .= "2. Section \"Top entreprises\" avec:\n";
                $prompt .= "   ⚠️ IMPORTANT: La PREMIÈRE entreprise de la liste DOIT TOUJOURS être \"{$company_name}\"\n";
                $prompt .= "   - Pour {$company_name}: décris ses services ({$services_text}), ses avantages, son expertise en {$dept_name}\n";
                $prompt .= "   - Ensuite, liste 4-9 autres entreprises fictives mais réalistes avec: nom, localisation (ville du département), spécialités, avantages\n";
                $prompt .= "   - Format: <ul><li><strong>Nom entreprise</strong> - Description détaillée...</li></ul>\n";
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
                $prompt .= "⚠️ IMPORTANT: NE PAS créer de liste d'entreprises dans ce type d'article. C'est un guide informatif, pas une liste.\n\n";
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
     * Générer l'extrait (excerpt) pour l'article (max 160 caractères)
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
        
        // Construire un extrait optimisé (max 160 caractères)
        $excerpt = "Découvrez tout sur {$keyword}";
        if ($city_name && $dept_name) {
            $excerpt .= " à {$city_name} ({$dept_name})";
        } elseif ($dept_name) {
            $excerpt .= " en {$dept_name}";
        }
        $excerpt .= ". {$company_name} vous accompagne.";
        
        // Limiter strictement à 160 caractères
        if (mb_strlen($excerpt) > 160) {
            $excerpt = mb_substr($excerpt, 0, 157) . '...';
        }
        
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
     * Générer la meta description pour SEO (120-140 caractères)
     */
    private function generate_meta_description($keyword, $dept_name, $city_name) {
        $company_name = get_bloginfo('name');
        
        // Construire une description optimisée entre 120 et 140 caractères
        $description = "Expert en {$keyword}";
        if ($city_name && $dept_name) {
            $description .= " à {$city_name} ({$dept_name})";
        } elseif ($dept_name) {
            $description .= " en {$dept_name}";
        }
        $description .= ". {$company_name} - Devis gratuit.";
        
        // Ajuster la longueur si nécessaire (cible: 120-140 caractères)
        if (strlen($description) < 120) {
            // Ajouter des informations supplémentaires
            $description = "Expert en {$keyword}";
            if ($city_name && $dept_name) {
                $description .= " à {$city_name} ({$dept_name})";
            } elseif ($dept_name) {
                $description .= " en {$dept_name}";
            }
            $description .= ". {$company_name} vous accompagne. Devis gratuit et intervention rapide.";
        }
        
        // Limiter à 140 caractères maximum
        if (strlen($description) > 140) {
            $description = substr($description, 0, 137) . '...';
        }
        
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
    
    /**
     * Ajouter des liens internes vers le CTA/devis dans le contenu
     */
    private function add_internal_links($content, $keyword) {
        $devis_url = get_option('osmose_ads_devis_url', '');
        
        if (empty($devis_url)) {
            return $content;
        }
        
        // Mots-clés pour détecter où insérer le lien (plus de mots-clés pour plus d'opportunités)
        $link_keywords = array(
            'devis', 'estimation', 'simulateur', 'calculer', 'demander', 
            'contact', 'appeler', 'devis gratuit', 'estimation gratuite',
            'simulation', 'calcul', 'tarif', 'prix', 'coût', 'budget',
            'intervention', 'travaux', 'prestation', 'service'
        );
        
        $links_added = 0;
        $max_links = 3; // Maximum 3 liens internes dans l'article
        
        // Chercher les occurrences de ces mots-clés et ajouter un lien
        foreach ($link_keywords as $link_keyword) {
            if ($links_added >= $max_links) {
                break;
            }
            
            // Pattern pour trouver le mot-clé dans une phrase (mais pas déjà dans un lien)
            $pattern = '/\b(' . preg_quote($link_keyword, '/') . ')\b/i';
            
            // Vérifier que ce n'est pas déjà dans un lien
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                $before = substr($content, max(0, $pos - 50), 50);
                $after = substr($content, $pos, 100);
                
                // Si le mot-clé n'est pas déjà dans un lien
                if (strpos($before . $after, '<a') === false || strpos($before . $after, '</a>') === false) {
                    // Remplacer la première occurrence par un lien
                    $replacement = '<a href="' . esc_url($devis_url) . '" class="osmose-internal-link" title="' . esc_attr__('Demander un devis gratuit', 'osmose-ads') . '">$1</a>';
                    $content = preg_replace($pattern, $replacement, $content, 1);
                    $links_added++;
                }
            }
        }
        
        // Si aucun lien n'a été ajouté, en ajouter un dans la conclusion
        if ($links_added === 0 || strpos($content, $devis_url) === false) {
            $link_html = '<p style="margin-top: 20px;"><a href="' . esc_url($devis_url) . '" class="osmose-internal-link button" style="display: inline-block; padding: 12px 24px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">' . __('Demander un devis gratuit', 'osmose-ads') . '</a></p>';
            
            // Chercher la conclusion (dernière section avant la fin)
            if (preg_match('/<\/h2>\s*<p>.*?<\/p>\s*$/', $content)) {
                // Ajouter avant le dernier </h2>
                $content = preg_replace('/(<\/h2>\s*)(<p>.*?<\/p>\s*)$/', '$1' . $link_html . "\n\n$2", $content);
            } elseif (preg_match('/<\/h3>\s*<p>.*?<\/p>\s*$/', $content)) {
                // Ajouter avant le dernier </h3>
                $content = preg_replace('/(<\/h3>\s*)(<p>.*?<\/p>\s*)$/', '$1' . $link_html . "\n\n$2", $content);
            } else {
                // Ajouter à la fin
                $content .= "\n\n" . $link_html;
            }
        }
        
        return $content;
    }
    
    /**
     * Insérer les images configurées dans le contenu
     */
    private function insert_article_images($content, $keyword, $title) {
        // Récupérer les images configurées pour les articles
        $article_images = get_option('osmose_articles_images', array());
        
        if (empty($article_images) || !is_array($article_images)) {
            return $content;
        }
        
        // Extraire les mots-clés du titre pour matcher avec les images
        $title_keywords = $this->extract_keywords_from_title($title, $keyword);
        
        // Trouver les images correspondantes
        $matching_images = array();
        foreach ($article_images as $img_data) {
            if (!isset($img_data['image_id']) || !isset($img_data['keywords'])) {
                continue;
            }
            
            $img_keywords = explode(',', strtolower($img_data['keywords']));
            $img_keywords = array_map('trim', $img_keywords);
            
            // Vérifier si un mot-clé du titre correspond
            foreach ($title_keywords as $title_kw) {
                if (in_array(strtolower($title_kw), $img_keywords)) {
                    $matching_images[] = $img_data;
                    break;
                }
            }
        }
        
        // Si aucune image ne correspond, prendre la première image disponible
        if (empty($matching_images) && !empty($article_images)) {
            $matching_images[] = $article_images[0];
        }
        
        // Insérer au moins une image dans le contenu
        if (!empty($matching_images)) {
            $image_to_insert = $matching_images[0];
            $image_id = intval($image_to_insert['image_id']);
            
            if ($image_id > 0) {
                $image_url = wp_get_attachment_image_url($image_id, 'large');
                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                if (empty($image_alt)) {
                    $image_alt = $title;
                }
                
                $image_html = '<figure class="osmose-article-image" style="margin: 20px 0; text-align: center;">';
                $image_html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" style="max-width: 100%; height: auto; border-radius: 8px;">';
                $image_html .= '</figure>';
                
                // Insérer l'image après le premier paragraphe ou après la première section
                if (preg_match('/(<p[^>]*>.*?<\/p>)/s', $content, $matches)) {
                    $content = str_replace($matches[0], $matches[0] . "\n\n" . $image_html, $content, 1);
                } elseif (preg_match('/(<h2[^>]*>.*?<\/h2>)/s', $content, $matches)) {
                    $content = str_replace($matches[0], $matches[0] . "\n\n" . $image_html, $content, 1);
                } else {
                    // Insérer au début
                    $content = $image_html . "\n\n" . $content;
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Extraire les mots-clés du titre pour matcher avec les images
     */
    private function extract_keywords_from_title($title, $main_keyword) {
        $keywords = array($main_keyword);
        
        // Extraire les mots significatifs du titre (plus de 4 caractères)
        $words = preg_split('/\s+/', $title);
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-ZÀ-ÿ]/', '', $word);
            if (mb_strlen($word) > 4) {
                $keywords[] = strtolower($word);
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Générer les tags WordPress pour l'article (minimum 10 tags)
     */
    private function generate_tags($keyword, $department, $city) {
        $tags = array();
        
        // Ajouter le mot-clé principal
        if (!empty($keyword)) {
            $tags[] = $keyword;
        }
        
        // Ajouter le département (nom complet uniquement)
        if ($department) {
            $dept_name = $this->get_department_name($department);
            if ($dept_name && $dept_name !== $department) {
                $tags[] = $dept_name;
            }
        }
        
        // Ajouter la ville
        if ($city && is_array($city) && isset($city['name'])) {
            $tags[] = $city['name'];
        }
        
        // Ajouter des tags génériques pertinents
        $tags[] = __('Rénovation', 'osmose-ads');
        $tags[] = __('Travaux', 'osmose-ads');
        $tags[] = __('Professionnel', 'osmose-ads');
        $tags[] = __('Artisan', 'osmose-ads');
        $tags[] = __('Expert', 'osmose-ads');
        $tags[] = __('Qualifié', 'osmose-ads');
        
        // Ajouter des tags spécifiques selon le mot-clé
        $keyword_lower = strtolower($keyword);
        
        // Tags pour toiture/couvreur
        if (stripos($keyword_lower, 'toiture') !== false || stripos($keyword_lower, 'couvreur') !== false) {
            $tags[] = __('Toiture', 'osmose-ads');
            $tags[] = __('Couvreur', 'osmose-ads');
            $tags[] = __('Charpente', 'osmose-ads');
            $tags[] = __('Zinguerie', 'osmose-ads');
        }
        
        // Tags pour isolation
        if (stripos($keyword_lower, 'isolation') !== false) {
            $tags[] = __('Isolation', 'osmose-ads');
            $tags[] = __('Isolant', 'osmose-ads');
            $tags[] = __('Énergie', 'osmose-ads');
            $tags[] = __('Économie', 'osmose-ads');
        }
        
        // Tags pour étanchéité/hydrofugation
        if (stripos($keyword_lower, 'étanchéité') !== false || stripos($keyword_lower, 'hydrofug') !== false) {
            $tags[] = __('Étanchéité', 'osmose-ads');
            $tags[] = __('Hydrofugation', 'osmose-ads');
            $tags[] = __('Protection', 'osmose-ads');
            $tags[] = __('Imperméabilisation', 'osmose-ads');
        }
        
        // Tags pour démoussage
        if (stripos($keyword_lower, 'démoussage') !== false || stripos($keyword_lower, 'nettoyage') !== false) {
            $tags[] = __('Démoussage', 'osmose-ads');
            $tags[] = __('Nettoyage', 'osmose-ads');
            $tags[] = __('Entretien', 'osmose-ads');
            $tags[] = __('Maintenance', 'osmose-ads');
        }
        
        // Tags généraux supplémentaires
        $tags[] = __('Devis gratuit', 'osmose-ads');
        $tags[] = __('Intervention rapide', 'osmose-ads');
        
        // Si on a encore moins de 10 tags, ajouter des tags génériques supplémentaires
        if (count($tags) < 10) {
            $additional_tags = array(
                __('Bretagne', 'osmose-ads'),
                __('France', 'osmose-ads'),
                __('Qualité', 'osmose-ads'),
                __('Service', 'osmose-ads'),
                __('Entreprise', 'osmose-ads'),
                __('Prestation', 'osmose-ads'),
                __('Réparation', 'osmose-ads'),
                __('Installation', 'osmose-ads'),
            );
            
            // Ajouter jusqu'à avoir au moins 10 tags
            foreach ($additional_tags as $additional_tag) {
                if (count($tags) >= 10) {
                    break;
                }
                if (!in_array($additional_tag, $tags)) {
                    $tags[] = $additional_tag;
                }
            }
        }
        
        // Nettoyer les tags
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags);
        $tags = array_unique($tags);
        
        // S'assurer qu'on a au moins 10 tags
        if (count($tags) < 10) {
            // Ajouter des tags basés sur le département si disponible
            if ($department) {
                $dept_name = $this->get_department_name($department);
                if ($dept_name && $dept_name !== $department) {
                    $tags[] = $dept_name . ' ' . __('professionnel', 'osmose-ads');
                    $tags[] = $dept_name . ' ' . __('expert', 'osmose-ads');
                }
            }
            
            // Ajouter des tags basés sur la ville si disponible
            if ($city && is_array($city) && isset($city['name'])) {
                $tags[] = $city['name'] . ' ' . __('professionnel', 'osmose-ads');
                $tags[] = $city['name'] . ' ' . __('expert', 'osmose-ads');
            }
        }
        
        // Nettoyer à nouveau et prendre les 15 premiers (pour avoir une marge)
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags);
        $tags = array_unique($tags);
        $tags = array_slice($tags, 0, 15); // Prendre jusqu'à 15 tags
        
        // S'assurer qu'on a au moins 10 tags
        if (count($tags) < 10) {
            // En dernier recours, ajouter des tags génériques
            $fallback_tags = array(
                __('Bâtiment', 'osmose-ads'),
                __('Construction', 'osmose-ads'),
                __('Habitat', 'osmose-ads'),
                __('Maison', 'osmose-ads'),
                __('Logement', 'osmose-ads'),
            );
            
            foreach ($fallback_tags as $fallback_tag) {
                if (count($tags) >= 10) {
                    break;
                }
                if (!in_array($fallback_tag, $tags)) {
                    $tags[] = $fallback_tag;
                }
            }
        }
        
        return $tags;
    }
}

