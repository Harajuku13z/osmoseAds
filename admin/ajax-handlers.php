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
    
    // Récupérer les informations de l'entreprise depuis les options WordPress
    $company_name = get_bloginfo('name');
    $company_phone = get_option('osmose_ads_company_phone', '');
    $company_phone_raw = get_option('osmose_ads_company_phone_raw', $company_phone);
    $company_email = get_option('admin_email', '');
    $site_url = get_site_url();
    
    // Appeler l'IA pour générer le contenu
    $ai_service = new AI_Service();
    
    if (empty($prompt)) {
        // Construire la liste des mots-clés pour le prompt
        $keywords_list = '';
        if (!empty($service_keywords)) {
            $keywords_array = array_map('trim', explode(',', $service_keywords));
            $keywords_list = implode(', ', $keywords_array);
        }
        
        // Construire un prompt simplifié, 100% orienté résultat
        $company_address = get_option('osmose_ads_company_address', '');
        
        $prompt  = "Tu es un rédacteur web SEO senior spécialisé en couverture.\n";
        $prompt .= "Génère UNIQUEMENT du HTML (pas de markdown), sans wrapper <html> ni <body>.\n";
        $prompt .= "Utilise OBLIGATOIREMENT ces variables : [VILLE], [DÉPARTEMENT], [CODE_POSTAL], [RÉGION].\n";
        $prompt .= "NE JAMAIS mettre de ville réelle en dur (toujours [VILLE], [DÉPARTEMENT], etc.).\n\n";
        
        $prompt .= "Contexte :\n";
        $prompt .= "- Service principal (focus keyword) : " . $service_name . "\n";
        if (!empty($keywords_list)) {
            $prompt .= "- Mots-clés secondaires : " . $keywords_list . "\n";
        }
        $prompt .= "- Entreprise : " . ($company_name ?: '[ENTREPRISE]') . "\n";
        $prompt .= "- Adresse : " . ($company_address ?: '[ADRESSE]') . "\n";
        $prompt .= "- Site : " . $site_url . "\n\n";

        $prompt .= "OBJECTIF :\n";
        $prompt .= "- Créer un contenu premium pour un " . strtolower($service_name) . " à [VILLE] ([DÉPARTEMENT], [CODE_POSTAL]).\n";
        $prompt .= "- Respecter les bonnes pratiques SEO : focus keyword dans l'intro, H2, FAQ, etc.\n";
        $prompt .= "- Longueur cible : 1800 à 2600 mots.\n\n";

        $prompt .= "CONTRAINTES HTML IMPORTANTES :\n";
        $prompt .= "- Balises autorisées : <h2>, <h3>, <p>, <strong>, <em>, <br> uniquement.\n";
        $prompt .= "- INTERDIT : titres 'Introduction', 'Description courte', 'Présentation', 'FAQ " . strtolower($service_name) . "'.\n";
        $prompt .= "- Ne mets AUCUN emoji, AUCUN titre du type 'Article ... Premium'.\n\n";

        $prompt .= "STRUCTURE EXACTE À PRODUIRE :\n\n";

        // 1) INTRODUCTION
        $prompt .= "1/ INTRODUCTION (200–250 mots)\n";
        $prompt .= "- Pas de titre, commence directement par un paragraphe <p>.\n";
        $prompt .= "- Première phrase : doit contenir le focus keyword \"" . strtolower($service_name) . "\" + [VILLE] + [DÉPARTEMENT].\n";
        $prompt .= "- 2 à 3 paragraphes <p>, ton commercial mais concret, orienté bénéfices client.\n\n";

        // 2) GARANTIES
        $prompt .= "2/ GARANTIES (120–180 mots)\n";
        $prompt .= "- Un seul <h2> clair, par exemple : \"Une entreprise de couverture de confiance à [VILLE]\".\n";
        $prompt .= "- 1 ou 2 <p> qui parlent : garantie décennale, assurance, sérieux, sécurité, propreté de chantier.\n\n";

        // 3) PRESTATIONS
        $prompt .= "3/ PRESTATIONS (OBLIGATOIRE : au moins 10 services)\n";
        $prompt .= "- Un seul <h2> : \"Nos prestations de " . strtolower($service_name) . " à [VILLE]\".\n";
        $prompt .= "- Liste de 10 à 14 prestations.\n";
        $prompt .= "- Format STRICT pour CHAQUE prestation :\n";
        $prompt .= "  <p><strong>[Nom de la prestation]</strong> – [Description de 25 à 40 mots expliquant ce que l'on fait, les bénéfices pour le client, et en liant si possible au climat / contexte de [VILLE], [DÉPARTEMENT]].</p>\n";
        $prompt .= "- Les prestations doivent couvrir : pose, rénovation, réparation urgente, isolation, démoussage, hydrofuge, zinguerie, urgence intempéries, etc.\n\n";

        // 4) FAQ
        $prompt .= "4/ FAQ (3 à 4 questions)\n";
        $prompt .= "- Un seul <h2> : \"Questions fréquentes sur " . strtolower($service_name) . " à [VILLE]\".\n";
        $prompt .= "- Pour chaque question :\n";
        $prompt .= "  <h3>[Question complète avec le mot \""
                 . strtolower($service_name) . "\" et [VILLE]] ?</h3>\n";
        $prompt .= "  <p>[Réponse de 40 à 60 mots, claire, pédagogique, avec focus sur la pratique réelle d'un artisan à [VILLE]].</p>\n\n";

        // 5) RÈGLES SEO
        $prompt .= "5/ RÈGLES SEO DANS LE TEXTE :\n";
        $prompt .= "- \""
                 . strtolower($service_name) . "\" doit apparaître naturellement dans l'introduction, dans au moins un H2, plusieurs prestations et plusieurs FAQ.\n";
        $prompt .= "- Utiliser beaucoup de mots de liaison : \"D'abord\", \"Ensuite\", \"De plus\", \"Par ailleurs\", \"Cependant\", \"En revanche\", \"Ainsi\", \"Enfin\", \"Par conséquent\".\n";
        $prompt .= "- Paragraphes courts (2–4 phrases), lisibles.\n";
        $prompt .= "- Intégrer au moins 2 liens internes sous forme d'ancres (par ex. <a href=\"/contact\">contact</a>, <a href=\"/devis\">devis</a>).\n";
        $prompt .= "- Intégrer au moins 1 lien externe utile (ex. <a href=\"https://www.service-public.fr/\">service-public.fr</a>) dans un contexte informatif.\n";
        $prompt .= "- Ne PAS générer de meta title/description ici (c'est géré à part).\n\n";

        $prompt .= "Produis UNIQUEMENT le contenu HTML final, sans explication autour, sans commentaires, sans texte avant/après.\n";
    }
    
    $system_message = 'Tu es un rédacteur web senior spécialisé en BTP/couverture avec 10+ ans d\'expérience. Tu maîtrises parfaitement le vocabulaire technique du métier, les enjeux clients et les standards WordPress/SEO 2025.';
    
    // Générer le contenu principal avec plus de tokens pour un contenu de qualité
    $ai_response = $ai_service->call_ai($prompt, $system_message, array(
        'temperature' => 0.8,
        'max_tokens' => 4000,
    ));
    
    if (is_wp_error($ai_response)) {
        wp_send_json_error(array('message' => $ai_response->get_error_message()));
    }
    
    // Nettoyer la réponse de l'IA
    $content = $ai_response ?? '';
    
    // Supprimer les commentaires de validation à la fin
    $content = preg_replace('/\s*[-─═]{3,}.*$/s', '', $content);
    $content = preg_replace('/\s*✅.*$/s', '', $content);
    $content = preg_replace('/\s*\*\*Note.*$/s', '', $content);
    
    // Convertir le Markdown en HTML si l'IA a généré du Markdown
    $content = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $content);
    $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</3>', $content);
    $content = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^#\s+(.+)$/m', '<h2>$1</h2>', $content);
    
    // Convertir le gras Markdown en HTML
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $content);
    
    // Convertir l'italique Markdown en HTML
    $content = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $content);
    $content = preg_replace('/_(.+?)_/s', '<em>$1</em>', $content);
    
    // Nettoyages spécifiques pour éviter les doublons de titres ou libellés techniques
    // Supprimer les paragraphes ou lignes qui ne contiennent que ces libellés
    $content = preg_replace('/(<p>)?\s*(Description\s+courte|Présentation|Garantie\s+satisfaction\s+et\s+performances|FAQ\s+' . preg_quote(strtolower($service_name), '/') . ')\s*(<\/p>)?/iu', '', $content);
    
    // Éviter les doublons immédiats de H2/H3 identiques
    $content = preg_replace('/(<h[23][^>]*>[^<]+<\/h[23]>)\s*(\1)+/i', '$1', $content);
    
    // Supprimer un éventuel H2 d'ouverture de type \"Article ...\" ou avec emoji qui ne sert à rien pour l'utilisateur
    // Exemple : <h2>🎯 Article Couvreur Premium à [VILLE]</h2>
    $content = preg_replace('/^<h2[^>]*>[^<]*(Article|Premium|🎯)[^<]*<\/h2>\s*/iu', '', $content);
    
    // Mettre à jour la réponse
    $ai_response = trim($content);
    
    // Demander à l'IA de générer les meta SEO selon les normes All in One SEO
    $meta_prompt = "Pour le service '$service_name' dans une ville [VILLE] du département [DÉPARTEMENT], génère des métadonnées SEO optimisées selon les normes All in One SEO. Réponds UNIQUEMENT au format JSON suivant (sans texte avant ou après) :\n\n";
    $meta_prompt .= "{\n";
    $meta_prompt .= "  \"meta_title\": \"titre SEO optimisé avec mot-clé principal en début (50-60 caractères max), format: [Service] [VILLE] [DÉPARTEMENT] | [Entreprise]\",\n";
    $meta_prompt .= "  \"meta_description\": \"description SEO engageante (150-160 caractères) incluant [VILLE] et [DÉPARTEMENT], avec bénéfice principal et CTA implicite\",\n";
    $meta_prompt .= "  \"meta_keywords\": \"mot-clé1, mot-clé2, mot-clé3 (optionnel, peu recommandé)\",\n";
    $meta_prompt .= "  \"og_title\": \"titre Open Graph (60-90 caractères)\",\n";
    $meta_prompt .= "  \"og_description\": \"description Open Graph (200-300 caractères) incluant [VILLE] et [DÉPARTEMENT]\",\n";
    $meta_prompt .= "  \"twitter_title\": \"titre Twitter (70 caractères max)\",\n";
    $meta_prompt .= "  \"twitter_description\": \"description Twitter (200 caractères max) incluant [VILLE] et [DÉPARTEMENT]\"\n";
    $meta_prompt .= "}\n\n";
    $meta_prompt .= "IMPORTANT : Les descriptions DOIVENT inclure [VILLE] et [DÉPARTEMENT] de manière naturelle. Le meta_title doit placer le mot-clé principal en début (poids SEO maximal).";
    
    $meta_response = $ai_service->call_ai($meta_prompt, 'Tu es un expert SEO spécialisé dans les normes All in One SEO. Tu génères des métadonnées optimisées au format JSON strict, en respectant les longueurs recommandées et en incluant systématiquement la localisation ([VILLE] et [DÉPARTEMENT]) dans les descriptions.', array(
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
    
    // Valeurs par défaut si l'IA n'a pas généré de meta (avec [VILLE] et [DÉPARTEMENT])
    $meta_title = $meta_data['meta_title'] ?? $service_name . ' [VILLE] [DÉPARTEMENT] | Service professionnel';
    $meta_description = $meta_data['meta_description'] ?? 'Service professionnel ' . strtolower($service_name) . ' à [VILLE] ([DÉPARTEMENT]). Intervention rapide et de qualité. Devis gratuit.';
    $meta_keywords = $meta_data['meta_keywords'] ?? strtolower($service_name) . ', [VILLE], [DÉPARTEMENT], service professionnel';
    $og_title = $meta_data['og_title'] ?? $meta_title;
    $og_description = $meta_data['og_description'] ?? ($meta_description ?: 'Service professionnel ' . strtolower($service_name) . ' à [VILLE] ([DÉPARTEMENT]). Intervention rapide et de qualité.');
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
    // Sauvegarder aussi les mots-clés du service pour les utiliser plus tard (tags, SEO, etc.)
    if (!empty($service_keywords)) {
        update_post_meta($template_id, 'service_keywords', $service_keywords);
    }
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
    
    $view_url = admin_url('admin.php?page=osmose-ads-templates&template_id=' . $template_id);
    
    wp_send_json_success(array(
        'message'     => __('Template créé avec succès avec images et métadonnées SEO', 'osmose-ads'),
        'template_id' => $template_id,
        'view_url'    => $view_url,
    ));
}

/**
 * Handler AJAX pour tracker les appels téléphoniques (accessible publiquement)
 */
function osmose_ads_track_call() {
    // Logger pour debug
    error_log('Osmose ADS: Track call handler called');
    error_log('Osmose ADS: POST data: ' . print_r($_POST, true));
    
    // Vérifier le nonce (moins strict pour le debug)
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'osmose_ads_track_call')) {
        error_log('Osmose ADS: Nonce verification failed. Nonce received: ' . $nonce);
        // Ne pas bloquer pour le moment - continuer quand même
        // wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
        // return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
    
    // Vérifier que la table existe (elle devrait avoir été créée à l'activation)
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log('Osmose ADS: Call tracking table does not exist! Creating it now...');
        
        // Créer la table si elle n'existe pas (fallback)
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED,
            ad_slug varchar(255),
            page_url varchar(500),
            phone_number varchar(50),
            user_ip varchar(45),
            user_agent text,
            referrer varchar(500),
            call_time datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ad_id (ad_id),
            KEY idx_created_at (created_at),
            KEY idx_call_time (call_time),
            KEY idx_page_url (page_url(255))
        ) $charset_collate;";
        dbDelta($sql);
        
        // Vérifier à nouveau
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            error_log('Osmose ADS: Call tracking table created successfully');
        } else {
            error_log('Osmose ADS: ERROR - Failed to create call tracking table!');
            wp_send_json_error(array('message' => __('Impossible de créer la table de tracking', 'osmose-ads')));
            return;
        }
    } else {
        error_log('Osmose ADS: Call tracking table exists');
    }
    
    // Récupérer les données
    $ad_id = intval($_POST['ad_id'] ?? 0);
    $ad_slug = sanitize_text_field($_POST['ad_slug'] ?? '');
    $page_url = esc_url_raw($_POST['page_url'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    
    // Si page_url n'est pas défini, utiliser l'URL actuelle
    if (empty($page_url)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $page_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    // Récupérer les informations de l'utilisateur
    $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    $referrer = esc_url_raw($_SERVER['HTTP_REFERER'] ?? '');
    
    error_log('Osmose ADS: Inserting call tracking. Ad ID: ' . $ad_id . ', Slug: ' . $ad_slug . ', Phone: ' . $phone);
    
    // Enregistrer l'appel
    $result = $wpdb->insert(
        $table_name,
        array(
            'ad_id' => $ad_id ?: null,
            'ad_slug' => $ad_slug ?: '',
            'page_url' => $page_url ?: '',
            'phone_number' => $phone ?: '',
            'user_ip' => $user_ip ?: '',
            'user_agent' => $user_agent ?: '',
            'referrer' => $referrer ?: '',
            'call_time' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        $error = $wpdb->last_error;
        error_log('Osmose ADS: Database error: ' . $error);
        error_log('Osmose ADS: Last query: ' . $wpdb->last_query);
        wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement: ' . $error, 'osmose-ads')));
    } else {
        error_log('Osmose ADS: Call tracked successfully. Insert ID: ' . $wpdb->insert_id);
        wp_send_json_success(array('message' => __('Appel enregistré', 'osmose-ads'), 'insert_id' => $wpdb->insert_id));
    }
}

// Enregistrer les handlers AJAX pour le tracking
add_action('wp_ajax_osmose_ads_track_call', 'osmose_ads_track_call');
add_action('wp_ajax_nopriv_osmose_ads_track_call', 'osmose_ads_track_call'); // Accessible publiquement

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
        
        // Récupérer l'ID de la catégorie "Annonces"
        $category_id = get_option('osmose_ads_category_id');
        if (!$category_id) {
            // Créer la catégorie si elle n'existe pas
            $category_id = wp_create_category('Annonces');
            if (!is_wp_error($category_id)) {
                update_option('osmose_ads_category_id', $category_id);
            }
        }
        
        // Créer l'annonce
        $ad_id = wp_insert_post(array(
            'post_title' => $service_name . ' à ' . $city_name,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => 'ad',
            'post_status' => 'publish',
            'post_category' => $category_id ? array($category_id) : array(), // Assigner la catégorie "Annonces"
        ));
        
        if (is_wp_error($ad_id)) {
            $errors++;
            continue;
        }
        
        // Générer automatiquement des étiquettes (tags) SEO pour l'annonce
        $tags = array();
        
        // Mot-clé principal = nom du service
        if (!empty($service_name)) {
            $tags[] = $service_name;
        }
        
        // Service + ville
        if (!empty($service_name) && !empty($city_name)) {
            $tags[] = $service_name . ' ' . $city_name;
        }
        
        // Ville seule
        if (!empty($city_name)) {
            $tags[] = $city_name;
        }
        
        // Récupérer les mots-clés du template (si définis)
        $template_keywords = get_post_meta($template_id, 'service_keywords', true);
        if (!empty($template_keywords)) {
            $keywords_array = array_map('trim', explode(',', $template_keywords));
            foreach ($keywords_array as $kw) {
                if (!empty($kw)) {
                    $tags[] = $kw;
                    // Variante avec ville
                    if (!empty($city_name)) {
                        $tags[] = $kw . ' ' . $city_name;
                    }
                }
            }
        }
        
        // Ajouter quelques tags génériques basés sur le service et la ville
        if (!empty($service_name) && !empty($city_name)) {
            $tags[] = 'artisan ' . $city_name;
            $tags[] = 'entreprise ' . strtolower($service_name);
            $tags[] = strtolower($service_name) . ' ' . $city_name . ' devis';
            $tags[] = strtolower($service_name) . ' ' . $city_name . ' prix';
        }
        
        // Nettoyer et dédupliquer
        $tags = array_filter(array_unique(array_map('sanitize_text_field', $tags)));
        
        // S'assurer qu'on a au moins 10 tags (compléter avec des combinaisons si nécessaire)
        if (count($tags) < 10 && !empty($service_name) && !empty($city_name)) {
            $base = strtolower($service_name);
            $ville = $city_name;
            $extras = array(
                $base . ' professionnel ' . $ville,
                $base . ' pas cher ' . $ville,
                'entreprise ' . $base . ' ' . $ville,
                'spécialiste ' . $base . ' ' . $ville,
                'travaux ' . $base . ' ' . $ville,
                'devis ' . $base . ' ' . $ville,
                'réparation ' . $base . ' ' . $ville,
                'installation ' . $base . ' ' . $ville,
            );
            foreach ($extras as $extra) {
                if (count($tags) >= 10) {
                    break;
                }
                $tags[] = sanitize_text_field($extra);
            }
        }
        
        if (!empty($tags)) {
            // Assigner les tags à l'annonce (créera les termes si nécessaire)
            wp_set_post_terms($ad_id, $tags, 'post_tag', false);
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

/**
 * Handler pour supprimer un template
 */
function osmose_ads_handle_delete_template() {
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
    }
    
    $template_id = intval($_POST['template_id'] ?? 0);
    
    if (!$template_id) {
        wp_send_json_error(array('message' => __('ID de template manquant', 'osmose-ads')));
    }
    
    // Vérifier que c'est bien un template
    $template = get_post($template_id);
    if (!$template || $template->post_type !== 'ad_template') {
        wp_send_json_error(array('message' => __('Template non trouvé', 'osmose-ads')));
    }
    
    // Vérifier les annonces associées
    $ads_count = get_posts(array(
        'post_type' => 'ad',
        'meta_key' => 'template_id',
        'meta_value' => $template_id,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ));
    
    $ads_count = count($ads_count);
    
    // Option : supprimer aussi les annonces associées si demandé
    $delete_ads = isset($_POST['delete_ads']) && $_POST['delete_ads'] === 'true';
    
    if ($ads_count > 0 && !$delete_ads) {
        // Il y a des annonces associées, demander confirmation
        wp_send_json_error(array(
            'message' => sprintf(
                __('Ce template est utilisé par %d annonce(s). Voulez-vous aussi supprimer ces annonces ?', 'osmose-ads'),
                $ads_count
            ),
            'has_ads' => true,
            'ads_count' => $ads_count,
        ));
    }
    
    // Supprimer les annonces associées si demandé
    if ($delete_ads && $ads_count > 0) {
        $ads = get_posts(array(
            'post_type' => 'ad',
            'meta_key' => 'template_id',
            'meta_value' => $template_id,
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        foreach ($ads as $ad) {
            wp_delete_post($ad->ID, true); // true = force delete (bypass trash)
        }
    }
    
    // Supprimer le template
    $deleted = wp_delete_post($template_id, true);
    
    if ($deleted) {
        wp_send_json_success(array(
            'message' => __('Template supprimé avec succès', 'osmose-ads'),
            'deleted_ads' => $delete_ads ? $ads_count : 0,
        ));
    } else {
        wp_send_json_error(array('message' => __('Erreur lors de la suppression', 'osmose-ads')));
    }
}
