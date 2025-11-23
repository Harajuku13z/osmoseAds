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
    
    $service_name = sanitize_text_field($_POST['service_name'] ?? '');
    $service_slug = sanitize_title($service_name);
    $prompt = sanitize_textarea_field($_POST['ai_prompt'] ?? '');
    $featured_image_id = intval($_POST['featured_image_id'] ?? 0);
    $realization_images = isset($_POST['realization_images']) && is_array($_POST['realization_images']) 
        ? array_map('intval', $_POST['realization_images']) 
        : array();
    
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
        $images_info = '';
        if ($featured_image_id) {
            $images_info .= "\n- Une image mise en avant est fournie pour illustrer le service.";
        }
        if (!empty($realization_images)) {
            $images_info .= "\n- Des photos de réalisations sont disponibles pour illustrer des exemples concrets.";
        }
        
        $prompt = "Crée un contenu HTML complet et optimisé SEO pour le service : $service_name. $images_info\n\nLe contenu doit inclure :\n- Des sections H2, H3 structurées\n- Des listes à puces\n- Une FAQ pertinente\n- Du contenu optimisé pour le référencement local\n- Utilise [VILLE], [DÉPARTEMENT], [RÉGION] comme placeholders pour la géolocalisation\n- Intègre naturellement les images disponibles si mentionnées";
    }
    
    $system_message = 'Tu es un expert en rédaction web SEO. Tu génères du contenu HTML complet, structuré et optimisé pour le référencement local. Tu crées également des métadonnées SEO (meta title, meta description, keywords) optimisées.';
    
    // Générer le contenu principal
    $ai_response = $ai_service->call_ai($prompt, $system_message, array(
        'temperature' => 0.8,
        'max_tokens' => 3000,
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
    
    // Enregistrer les images de réalisations
    if (!empty($realization_images)) {
        $valid_images = array();
        foreach ($realization_images as $img_id) {
            if (wp_attachment_is_image($img_id)) {
                $valid_images[] = $img_id;
            }
        }
        if (!empty($valid_images)) {
            update_post_meta($template_id, 'realization_images', $valid_images);
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

