<?php
/**
 * Gestionnaires AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

function osmose_ads_handle_create_template() {
    // Vérifier que les classes existent
    if (!class_exists('Ad_Template')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
    }
    if (!class_exists('AI_Service')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
    }
    
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/preset-services.php';
    
    $creation_mode = sanitize_text_field($_POST['creation_mode'] ?? 'custom');
    $service_name = sanitize_text_field($_POST['service_name'] ?? '');
    $service_slug = sanitize_title($service_name);
    $prompt = sanitize_textarea_field($_POST['ai_prompt'] ?? '');
    $featured_image_id = intval($_POST['featured_image_id'] ?? 0);
    $realization_images = isset($_POST['realization_images']) && is_array($_POST['realization_images']) 
        ? array_map('intval', $_POST['realization_images']) 
        : array();
    
    // Récupérer les mots-clés associés aux images de réalisations
    $realization_keywords = isset($_POST['realization_keywords']) && is_array($_POST['realization_keywords'])
        ? array_map('sanitize_text_field', $_POST['realization_keywords'])
        : array();
    
    // Gestion des services préconfigurés
    $service_keywords = '';
    $service_description = '';
    $service_sections = array();
    
    if ($creation_mode === 'preset' && !empty($_POST['preset_service'])) {
        $preset_key = sanitize_text_field($_POST['preset_service']);
        $preset_services = osmose_ads_get_preset_services();
        
        if (isset($preset_services[$preset_key])) {
            $preset = $preset_services[$preset_key];
            $service_name = $preset['name'];
            $service_keywords = $preset['keywords'];
            $service_description = $preset['description'];
            $service_sections = $preset['sections'] ?? array();
        }
    } else {
        $service_keywords = sanitize_text_field($_POST['service_keywords'] ?? '');
        $service_description = sanitize_textarea_field($_POST['service_description'] ?? '');
    }
    
    if (empty($service_name)) {
        wp_send_json_error(array('message' => __('Le nom du service est requis', 'osmose-ads')));
    }
    
    // Vérifier si le template existe déjà
    $existing = Ad_Template::get_by_service_slug($service_slug);
    if ($existing) {
        wp_send_json_error(array('message' => __('Un template pour ce service existe déjà', 'osmose-ads')));
    }
    
    // Appeler l'IA pour générer le contenu
    $ai_service = new AI_Service();
    
    if (empty($prompt)) {
        // Construire un prompt de haute qualité selon le mode
        $images_info = '';
        if ($featured_image_id) {
            $images_info .= "\n- Une image mise en avant professionnelle est disponible pour illustrer le service.";
        }
        if (!empty($realization_images)) {
            $images_info .= "\n- Des photos de réalisations concrètes sont disponibles pour enrichir le contenu.";
        }
        
        $sections_info = '';
        if (!empty($service_sections)) {
            $sections_info = "\n\nSECTIONS À INCLURE (dans l'ordre) :\n";
            foreach ($service_sections as $section_key => $section_title) {
                $sections_info .= "- $section_title : Contenu détaillé et informatif\n";
            }
        } else {
            $sections_info = "\n\nSECTIONS STANDARD À INCLURE :\n";
            $sections_info .= "- Introduction engageante avec le service\n";
            $sections_info .= "- Pourquoi faire appel à nos services (avantages, expertise)\n";
            $sections_info .= "- Types d'interventions et services proposés (liste détaillée)\n";
            $sections_info .= "- Zone d'intervention et disponibilité\n";
            $sections_info .= "- Processus de travail et engagement qualité\n";
            $sections_info .= "- FAQ avec 5-8 questions pertinentes et réponses détaillées\n";
            $sections_info .= "- Appel à l'action pour demande de devis\n";
        }
        
        $keywords_info = '';
        if (!empty($service_keywords)) {
            $keywords_info = "\n\nMOTS-CLÉS À UTILISER NATURELLEMENT (sans sur-optimisation) :\n" . $service_keywords;
        }
        
        $description_info = '';
        if (!empty($service_description)) {
            $description_info = "\n\nCONTEXTE ET DESCRIPTION DU SERVICE :\n" . $service_description;
        }
        
        $prompt = "Tu es un rédacteur web professionnel expert en SEO local et création de contenu de qualité supérieure pour WordPress.\n\n";
        $prompt .= "TÂCHE : Créer un article/annonce HTML de haute qualité pour le service : \"$service_name\"\n\n";
        $prompt .= "CONTEXTE : Ce contenu sera utilisé pour créer des pages géolocalisées optimisées SEO. Utilise [VILLE], [DÉPARTEMENT], [RÉGION] comme placeholders.\n";
        $prompt .= $description_info;
        $prompt .= $keywords_info;
        $prompt .= $images_info;
        $prompt .= $sections_info;
        
        $prompt .= "\n\nEXIGENCES DE QUALITÉ (CRITIQUES) :\n";
        $prompt .= "1. HTML SÉMANTIQUE ET PROPRE : Utilise uniquement les balises HTML5 valides (h2, h3, p, ul, ol, li, strong, em, a, blockquote, figure, img, etc.)\n";
        $prompt .= "2. STRUCTURE PROFESSIONNELLE : Hiérarchie claire des titres (H2 pour sections principales, H3 pour sous-sections)\n";
        $prompt .= "3. CONTENU RICHE ET INFORMATIF : Chaque section doit contenir 150-300 mots de contenu utile et bien rédigé\n";
        $prompt .= "4. OPTIMISATION SEO NATURELLE : Intégration naturelle des mots-clés, pas de bourrage\n";
        $prompt .= "5. TON PROFESSIONNEL ET ENGAGEANT : Langage clair, rassurant, orienté client\n";
        $prompt .= "6. FORMAT WORDPRESS : Code HTML prêt à être collé dans l'éditeur WordPress (pas de balises style inline, pas de doctype/html/head/body)\n";
        $prompt .= "7. LONGUEUR : Article complet de 1500-2500 mots au total\n";
        $prompt .= "8. FAQ DÉTAILLÉE : Questions pertinentes avec réponses complètes (100-150 mots par réponse)\n";
        $prompt .= "9. APPEL À L'ACTION : Section finale avec CTA clair pour demande de devis\n";
        $prompt .= "10. LISIBILITÉ : Paragraphes courts (3-4 lignes max), listes à puces pour faciliter la lecture\n\n";
        
        $prompt .= "FORMAT DE SORTIE :\n";
        $prompt .= "- Du HTML pur, valide, sans commentaires\n";
        $prompt .= "- Pas de balises <html>, <head>, <body>\n";
        $prompt .= "- Structure prête pour WordPress (utiliser wp_kses_post sans problème)\n";
        $prompt .= "- Images : Utilise <figure> et <img> avec attributs alt descriptifs si nécessaire (mais pas d'URLs réelles)\n\n";
        
        $prompt .= "Génère maintenant un contenu HTML de qualité professionnelle répondant à toutes ces exigences.";
    }
    
    $system_message = 'Tu es un expert rédacteur web professionnel spécialisé en création de contenu HTML de haute qualité pour WordPress. Tu maîtrises le SEO local, la rédaction web optimisée et la création de contenu engageant et informatif. Tu génères du HTML sémantique, propre et prêt pour WordPress, avec un contenu riche, structuré et de qualité supérieure.';
    
    // Générer le contenu principal avec plus de tokens pour un contenu de qualité
    $ai_response = $ai_service->call_ai($prompt, $system_message, array(
        'temperature' => 0.75, // Légèrement réduit pour plus de cohérence
        'max_tokens' => 4000, // Augmenté pour permettre un contenu plus long et détaillé
    ));
    
    if (is_wp_error($ai_response)) {
        wp_send_json_error(array('message' => $ai_response->get_error_message()));
    }
    
    // Demander à l'IA de générer les meta SEO
    $meta_prompt = "Pour le service '$service_name', génère des métadonnées SEO optimisées. Réponds UNIQUEMENT au format JSON suivant (sans texte avant ou après) :\n{\n  \"meta_title\": \"titre SEO (50-60 caractères)\",\n  \"meta_description\": \"description SEO (150-160 caractères)\",\n  \"meta_keywords\": \"mot-clé1, mot-clé2, mot-clé3\",\n  \"og_title\": \"titre Open Graph\",\n  \"og_description\": \"description Open Graph\",\n  \"twitter_title\": \"titre Twitter\",\n  \"twitter_description\": \"description Twitter\"\n}";
    
    $meta_response = $ai_service->call_ai($meta_prompt, 'Tu es un expert SEO. Tu génères des métadonnées optimisées au format JSON strict.', array(
        'temperature' => 0.7,
        'max_tokens' => 500,
    ));
    
    $meta_data = array();
    if (!is_wp_error($meta_response)) {
        // Essayer d'extraire le JSON de la réponse
        $json_start = strpos($meta_response, '{');
        $json_end = strrpos($meta_response, '}');
        if ($json_start !== false && $json_end !== false) {
            $json_str = substr($meta_response, $json_start, $json_end - $json_start + 1);
            $decoded = json_decode($json_str, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $meta_data = $decoded;
            }
        }
    }
    
    // Valeurs par défaut si l'IA n'a pas généré de meta
    $meta_title = $meta_data['meta_title'] ?? $service_name . ' - Service professionnel | [VILLE]';
    $meta_description = $meta_data['meta_description'] ?? 'Service professionnel ' . strtolower($service_name) . ' à [VILLE]. Intervention rapide et de qualité.';
    $meta_keywords = $meta_data['meta_keywords'] ?? strtolower($service_name) . ', [VILLE], [DÉPARTEMENT], service professionnel';
    $og_title = $meta_data['og_title'] ?? $meta_title;
    $og_description = $meta_data['og_description'] ?? $meta_description;
    $twitter_title = $meta_data['twitter_title'] ?? $og_title;
    $twitter_description = $meta_data['twitter_description'] ?? $og_description;
    
    // Créer le post template
    $template_id = wp_insert_post(array(
        'post_title' => $service_name,
        'post_content' => $ai_response,
        'post_type' => 'ad_template',
        'post_status' => 'publish',
    ));
    
    if (is_wp_error($template_id)) {
        wp_send_json_error(array('message' => __('Erreur lors de la création du template', 'osmose-ads')));
    }
    
    // Définir l'image mise en avant
    if ($featured_image_id && wp_attachment_is_image($featured_image_id)) {
        set_post_thumbnail($template_id, $featured_image_id);
        update_post_meta($template_id, 'featured_image_id', $featured_image_id);
    }
    
    // Enregistrer les images de réalisations avec leurs mots-clés
    if (!empty($realization_images)) {
        $valid_images = array();
        $images_with_keywords = array();
        
        foreach ($realization_images as $img_id) {
            if (wp_attachment_is_image($img_id)) {
                $valid_images[] = $img_id;
                
                // Associer les mots-clés à l'image
                $img_keywords = isset($realization_keywords[$img_id]) ? $realization_keywords[$img_id] : '';
                if (!empty($img_keywords)) {
                    // Mettre à jour les mots-clés de l'image WordPress
                    update_post_meta($img_id, '_osmose_image_keywords', $img_keywords);
                }
                
                $images_with_keywords[] = array(
                    'id' => $img_id,
                    'keywords' => $img_keywords
                );
            }
        }
        
        if (!empty($valid_images)) {
            update_post_meta($template_id, 'realization_images', $valid_images);
            update_post_meta($template_id, 'realization_images_keywords', $images_with_keywords);
        }
    }
    
    // Enregistrer les meta
    update_post_meta($template_id, 'service_name', $service_name);
    update_post_meta($template_id, 'service_slug', $service_slug);
    update_post_meta($template_id, 'ai_prompt_used', $prompt);
    update_post_meta($template_id, 'ai_response_data', $ai_response);
    update_post_meta($template_id, 'meta_title', $meta_title);
    update_post_meta($template_id, 'meta_description', $meta_description);
    update_post_meta($template_id, 'meta_keywords', $meta_keywords);
    update_post_meta($template_id, 'og_title', $og_title);
    update_post_meta($template_id, 'og_description', $og_description);
    update_post_meta($template_id, 'twitter_title', $twitter_title);
    update_post_meta($template_id, 'twitter_description', $twitter_description);
    update_post_meta($template_id, 'is_active', true);
    update_post_meta($template_id, 'usage_count', 0);
    
    wp_send_json_success(array(
        'message' => __('Template créé avec succès avec images et métadonnées SEO', 'osmose-ads'),
        'template_id' => $template_id,
    ));
}

function osmose_ads_handle_bulk_generate() {
    // Vérifier que les classes existent
    if (!class_exists('Ad_Template')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
    }
    
    $service_slug = sanitize_text_field($_POST['service_slug'] ?? '');
    $city_ids = array_map('intval', $_POST['city_ids'] ?? array());
    
    if (empty($service_slug) || empty($city_ids)) {
        wp_send_json_error(array('message' => __('Service et villes requis', 'osmose-ads')));
    }
    
    // Récupérer le template
    $template = Ad_Template::get_by_service_slug($service_slug);
    if (!$template) {
        wp_send_json_error(array('message' => __('Template non trouvé', 'osmose-ads')));
    }
    
    $created = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($city_ids as $city_id) {
        // Vérifier si l'annonce existe déjà
        $template_id = $template->post_id;
        $existing = get_posts(array(
            'post_type' => 'ad',
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'template_id', 'value' => $template_id, 'compare' => '='),
                array('key' => 'city_id', 'value' => $city_id, 'compare' => '='),
            ),
            'posts_per_page' => 1,
        ));
        
        if (!empty($existing)) {
            $skipped++;
            continue;
        }
        
        // Récupérer la ville
        $city = get_post($city_id);
        if (!$city) {
            $errors++;
            continue;
        }
        
        $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
        $service_name = get_post_meta($template_id, 'service_name', true);
        
        // Générer le slug
        $slug = $service_slug . '-' . sanitize_title($city_name);
        
        // Générer le contenu
        $content = $template->get_content_for_city($city_id);
        
        // Générer les métadonnées
        $meta = $template->get_meta_for_city($city_id);
        
        // Créer l'annonce
        $ad_id = wp_insert_post(array(
            'post_title' => $service_name . ' à ' . $city_name,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => 'ad',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($ad_id)) {
            $errors++;
            continue;
        }
        
        // Enregistrer les meta
        update_post_meta($ad_id, 'template_id', $template_id);
        update_post_meta($ad_id, 'city_id', $city_id);
        update_post_meta($ad_id, 'keyword', $service_name);
        update_post_meta($ad_id, 'status', 'published');
        update_post_meta($ad_id, 'published_at', current_time('mysql'));
        
        foreach ($meta as $key => $value) {
            if ($value) {
                update_post_meta($ad_id, $key, $value);
            }
        }
        
        // Incrémenter le compteur
        $template->increment_usage();
        
        $created++;
    }
    
    wp_send_json_success(array(
        'message' => sprintf(
            __('%d créées, %d ignorées, %d erreurs', 'osmose-ads'),
            $created,
            $skipped,
            $errors
        ),
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors,
    ));
}

