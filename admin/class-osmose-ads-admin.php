<?php
/**
 * Classe Admin
 */
class Osmose_Ads_Admin {

    public function enqueue_styles($hook) {
        // Charger uniquement sur les pages Osmose ADS
        if (strpos($hook, 'osmose-ads') === false) {
            return;
        }
        
        wp_enqueue_style(
            'osmose-ads-admin',
            OSMOSE_ADS_PLUGIN_URL . 'admin/css/osmose-ads-admin.css',
            array(),
            OSMOSE_ADS_VERSION
        );
        
        // Ajouter une classe au body pour cibler les pages Osmose ADS
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
        
        // Masquer les notifications WordPress
        add_action('admin_notices', array($this, 'hide_wp_notices'), 1);
    }
    
    public function add_admin_body_class($classes) {
        return $classes . ' osmose-ads-page';
    }
    
    public function hide_wp_notices() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'osmose-ads') !== false) {
            echo '<style>.notice, .update-nag, .error, .updated { display: none !important; }</style>';
        }
    }

    public function enqueue_scripts($hook) {
        // Charger uniquement sur les pages Osmose ADS
        if (strpos($hook, 'osmose-ads') === false) {
            return;
        }
        
        wp_enqueue_script(
            'osmose-ads-admin',
            OSMOSE_ADS_PLUGIN_URL . 'admin/js/osmose-ads-admin.js',
            array('jquery'),
            OSMOSE_ADS_VERSION,
            true
        );
        
        // Charger WordPress Media Library sur les pages de templates, création et articles
        if (strpos($hook, 'osmose-ads-templates') !== false || strpos($hook, 'osmose-ads-template-create') !== false || strpos($hook, 'osmose-ads-articles') !== false || strpos($hook, 'osmose-ads-articles-config') !== false || strpos($hook, 'osmose-ads-simulator-config') !== false) {
            wp_enqueue_media();
        }
        
        // Charger le script d'import direct depuis l'API sur la page des villes
        if (strpos($hook, 'osmose-ads-cities') !== false) {
            wp_enqueue_script(
                'osmose-ads-cities-direct',
                OSMOSE_ADS_PLUGIN_URL . 'admin/js/cities-direct-api.js',
                array('jquery'),
                OSMOSE_ADS_VERSION,
                true
            );
            
            // Localiser le script pour que osmoseAds soit disponible
            wp_localize_script('osmose-ads-cities-direct', 'osmoseAds', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('osmose_ads_nonce'),
            ));
            
            // Note: osmoseAds est maintenant défini directement dans cities.php template
            // pour garantir qu'il est disponible avant le chargement du script externe
        }
        
        // Créer le nonce et localiser le script
        wp_localize_script('osmose-ads-admin', 'osmoseAds', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osmose_ads_nonce'),
            'plugin_url' => OSMOSE_ADS_PLUGIN_URL,
        ));
        
        // S'assurer que la variable est disponible même pour les scripts inline
        add_action('admin_footer', function() use ($hook) {
            if (strpos($hook, 'osmose-ads') !== false) {
                ?>
                <script>
                if (typeof osmoseAds === 'undefined') {
                    var osmoseAds = {
                        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        nonce: '<?php echo wp_create_nonce('osmose_ads_nonce'); ?>',
                        plugin_url: '<?php echo OSMOSE_ADS_PLUGIN_URL; ?>'
                    };
                }
                </script>
                <?php
            }
        }, 5);
    }

    public function add_admin_menu() {
        // Pas d'icône dans le menu - on utilisera juste le texte
        // On pourra ajouter un logo dans le header plus tard si nécessaire
        // Vérifier si un logo existe
        $logo_path = OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.png';
        $logo_url = '';
        if (file_exists($logo_path)) {
            $logo_url = OSMOSE_ADS_PLUGIN_URL . 'admin/img/logo.png';
        }
        
        add_menu_page(
            __('Osmose ADS', 'osmose-ads'),
            __('🚀 Osmose ADS', 'osmose-ads'),
            'manage_options',
            'osmose-ads',
            array($this, 'display_dashboard'),
            $logo_url ? 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><image href="' . esc_url($logo_url) . '" width="20" height="20"/></svg>') : 'dashicons-admin-generic', // Icône fusée ou logo
            30
        );
        
        // Ajouter le style pour l'icône personnalisée si logo existe
        if ($logo_url) {
            add_action('admin_head', array($this, 'add_menu_icon_style'));
        }
        
        add_submenu_page(
            'osmose-ads',
            __('Tableau de bord', 'osmose-ads'),
            __('Tableau de bord', 'osmose-ads'),
            'manage_options',
            'osmose-ads',
            array($this, 'display_dashboard')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Templates', 'osmose-ads'),
            __('Templates', 'osmose-ads'),
            'manage_options',
            'osmose-ads-templates',
            array($this, 'display_templates')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Créer un Template', 'osmose-ads'),
            __('Créer un Template', 'osmose-ads'),
            'manage_options',
            'osmose-ads-template-create',
            array($this, 'display_template_create')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Annonces', 'osmose-ads'),
            __('Annonces', 'osmose-ads'),
            'manage_options',
            'osmose-ads-ads',
            array($this, 'display_ads')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Villes', 'osmose-ads'),
            __('Villes', 'osmose-ads'),
            'manage_options',
            'osmose-ads-cities',
            array($this, 'display_cities')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Articles Générés', 'osmose-ads'),
            __('📝 Articles Générés', 'osmose-ads'),
            'manage_options',
            'osmose-ads-articles',
            array($this, 'display_articles')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Configuration Articles', 'osmose-ads'),
            __('⚙️ Config Articles', 'osmose-ads'),
            'manage_options',
            'osmose-ads-articles-config',
            array($this, 'display_articles_config')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Configuration', 'osmose-ads'),
            __('Configuration', 'osmose-ads'),
            'manage_options',
            'osmose-ads-setup',
            array($this, 'display_setup')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Statistiques d\'Appels', 'osmose-ads'),
            __('Statistiques d\'Appels', 'osmose-ads'),
            'manage_options',
            'osmose-ads-calls',
            array($this, 'display_call_stats')
        );
        
        // Page de détails d'un appel (cachée du menu)
        add_submenu_page(
            null, // null pour cacher du menu
            __('Détails de l\'Appel', 'osmose-ads'),
            __('Détails de l\'Appel', 'osmose-ads'),
            'manage_options',
            'osmose-ads-call-details',
            array($this, 'display_call_details')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Statistiques de Visites', 'osmose-ads'),
            __('Statistiques de Visites', 'osmose-ads'),
            'manage_options',
            'osmose-ads-visits',
            array($this, 'display_visit_stats')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Demandes de Devis', 'osmose-ads'),
            __('📋 Demandes de Devis', 'osmose-ads'),
            'manage_options',
            'osmose-ads-quotes',
            array($this, 'display_quote_requests')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Configuration Simulateur', 'osmose-ads'),
            __('⚙️ Config Simulateur', 'osmose-ads'),
            'manage_options',
            'osmose-ads-simulator-config',
            array($this, 'display_simulator_config')
        );
        
        add_submenu_page(
            'osmose-ads',
            __('Réglages', 'osmose-ads'),
            __('Réglages', 'osmose-ads'),
            'manage_options',
            'osmose-ads-settings',
            array($this, 'display_settings')
        );
    }
    
    /**
     * Redirection après activation
     */
    public function activation_redirect() {
        if (get_transient('osmose_ads_activation_redirect')) {
            delete_transient('osmose_ads_activation_redirect');
            
            // Vérifier si la configuration est déjà faite
            $is_configured = get_option('osmose_ads_setup_completed', false);
            
            if (!$is_configured && !isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=osmose-ads-setup'));
                exit;
            }
        }
    }
    
    public function display_setup() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/setup.php';
    }

    public function register_settings() {
        register_setting('osmose_ads_settings', 'osmose_ads_ai_personalization');
        register_setting('osmose_ads_settings', 'osmose_ads_company_phone');
        register_setting('osmose_ads_settings', 'osmose_ads_company_phone_raw');
        register_setting('osmose_ads_settings', 'osmose_ads_company_email');
        register_setting('osmose_ads_settings', 'osmose_ads_company_address');
        register_setting('osmose_ads_settings', 'osmose_ads_devis_url');
        register_setting('osmose_ads_settings', 'osmose_ads_openai_api_key');
        register_setting('osmose_ads_settings', 'osmose_ads_ai_provider');
        register_setting('osmose_ads_settings', 'osmose_ads_services');
        
        // Settings pour le simulateur
        register_setting('osmose_ads_simulator_settings', 'osmose_ads_simulator_page_id');
        register_setting('osmose_ads_simulator_settings', 'osmose_ads_simulator_page_slug');
        register_setting('osmose_ads_simulator_settings', 'osmose_ads_simulator_title');
        register_setting('osmose_ads_simulator_settings', 'osmose_ads_simulator_email_notification');
        register_setting('osmose_ads_simulator_settings', 'osmose_ads_simulator_email_recipient');
    }

    public function display_dashboard() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    public function display_templates() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/templates.php';
    }
    
    public function display_template_create() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/template-create.php';
    }

    public function display_ads() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/ads.php';
    }


    public function display_cities() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/cities.php';
    }

    public function display_settings() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/settings.php';
    }
    
    public function display_call_stats() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/call-stats.php';
    }
    
    public function display_call_details() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/call-details.php';
    }
    
    public function display_visit_stats() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/visit-stats.php';
    }
    
    public function display_quote_requests() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/quote-requests.php';
    }
    
    public function display_simulator_config() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/simulator-config.php';
    }
    
    public function display_articles() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/articles.php';
    }
    
    public function display_articles_config() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/articles-config.php';
    }
    
    /**
     * Ajouter le style pour l'icône du menu
     */
    public function add_menu_icon_style() {
        $logo_url = OSMOSE_ADS_PLUGIN_URL . 'admin/img/logo.png';
        if (file_exists(OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.png')) {
            echo '<style>
                #toplevel_page_osmose-ads .wp-menu-image img {
                    width: 20px;
                    height: 20px;
                    padding: 6px 0;
                }
            </style>';
        } else {
            // Style pour l'icône fusée
            echo '<style>
                #toplevel_page_osmose-ads .wp-menu-image::before {
                    content: "🚀";
                    font-size: 20px;
                    line-height: 1;
                }
            </style>';
        }
    }

    /**
     * Enregistrer les handlers AJAX
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_osmose_ads_create_template', array($this, 'ajax_create_template'));
        add_action('wp_ajax_osmose_ads_bulk_generate', array($this, 'ajax_bulk_generate'));
        add_action('wp_ajax_osmose_ads_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_osmose_ads_delete_ad', array($this, 'ajax_delete_ad'));
        add_action('wp_ajax_osmose_ads_delete_all_ads', array($this, 'ajax_delete_all_ads'));
        add_action('wp_ajax_osmose_ads_test_email', array($this, 'ajax_test_email'));
        add_action('wp_ajax_osmose_ads_delete_all_calls', array($this, 'ajax_delete_all_calls'));
        add_action('wp_ajax_osmose_ads_recalculate_bot_status', array($this, 'ajax_recalculate_bot_status'));
        add_action('wp_ajax_osmose_generate_article_ajax', array($this, 'ajax_generate_article'));
    }

    /**
     * Handler AJAX pour créer un template
     */
    public function ajax_create_template() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        // Charger les dépendances si nécessaire
        if (!class_exists('Ad_Template')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
        }
        if (!class_exists('AI_Service')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_create_template();
    }

    /**
     * Handler AJAX pour supprimer un template
     */
    public function ajax_delete_template() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_delete_template();
    }

    /**
     * Handler AJAX pour supprimer une annonce
     */
    public function ajax_delete_ad() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_delete_ad();
    }

    /**
     * Handler AJAX pour supprimer toutes les annonces
     */
    public function ajax_delete_all_ads() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_delete_all_ads();
    }
    
    /**
     * Handler AJAX pour supprimer tous les appels
     */
    public function ajax_delete_all_calls() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
        
        // Vérifier que la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error(array('message' => __('La table de tracking n\'existe pas', 'osmose-ads')));
            return;
        }
        
        // Supprimer tous les appels
        $deleted = $wpdb->query("DELETE FROM $table_name");
        
        if ($deleted === false) {
            wp_send_json_error(array('message' => __('Erreur lors de la suppression:', 'osmose-ads') . ' ' . $wpdb->last_error));
            return;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d appel(s) supprimé(s) avec succès', 'osmose-ads'), $deleted),
            'deleted' => $deleted
        ));
    }

    /**
     * Handler AJAX pour recalculer les statuts bot/humain
     */
    public function ajax_recalculate_bot_status() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_recalculate_bot_status();
    }

    /**
     * Handler AJAX pour génération en masse
     */
    public function ajax_bulk_generate() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        // Charger les dépendances si nécessaire
        if (!class_exists('Ad_Template')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
        }
        
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/ajax-handlers.php';
        osmose_ads_handle_bulk_generate();
    }
    
    /**
     * Enregistrer les handlers AJAX pour l'import de villes
     */
    public function register_cities_ajax_handlers() {
        add_action('wp_ajax_osmose_ads_import_cities', array($this, 'ajax_import_cities'));
        add_action('wp_ajax_osmose_ads_import_communes_direct', array($this, 'ajax_import_communes_direct'));
        add_action('wp_ajax_osmose_ads_get_departments', array($this, 'ajax_get_departments'));
        add_action('wp_ajax_osmose_ads_get_regions', array($this, 'ajax_get_regions'));
        add_action('wp_ajax_osmose_ads_search_city', array($this, 'ajax_search_city'));
        
        error_log('Osmose ADS: AJAX handlers registered for cities');
    }
    
    /**
     * Handler AJAX pour importer des communes reçues directement depuis JavaScript
     */
    public function ajax_import_communes_direct() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        $communes_json = wp_unslash($_POST['communes'] ?? '');
        $communes = json_decode($communes_json, true);
        
        if (!is_array($communes) || empty($communes)) {
            wp_send_json_error(array('message' => __('Aucune commune reçue', 'osmose-ads')));
        }
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $geo_api = new France_Geo_API();
        $imported = 0;
        $skipped = 0;
        
        error_log('Osmose ADS: Importing ' . count($communes) . ' communes from JavaScript');
        
        foreach ($communes as $commune) {
            $normalized = $geo_api->normalize_commune_data($commune);
            
            // Vérifier si la ville existe déjà
            $existing = get_posts(array(
                'post_type' => 'city',
                'meta_query' => array(
                    array(
                        'key' => 'insee_code',
                        'value' => $normalized['code'],
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
            ));
            
            if (!empty($existing)) {
                $skipped++;
                continue;
            }
            
            if (empty($normalized['name']) || empty($normalized['code'])) {
                $skipped++;
                continue;
            }
            
            $city_id = wp_insert_post(array(
                'post_title' => $normalized['name'],
                'post_type' => 'city',
                'post_status' => 'publish',
            ));
            
            if ($city_id && !is_wp_error($city_id)) {
                update_post_meta($city_id, 'name', $normalized['name']);
                update_post_meta($city_id, 'insee_code', $normalized['code']);
                update_post_meta($city_id, 'postal_code', $normalized['postal_code']);
                update_post_meta($city_id, 'all_postal_codes', $normalized['all_postal_codes'] ?? $normalized['postal_code']);
                update_post_meta($city_id, 'department', $normalized['department']);
                update_post_meta($city_id, 'department_name', $normalized['department_name'] ?? '');
                update_post_meta($city_id, 'region', $normalized['region']);
                update_post_meta($city_id, 'region_name', $normalized['region_name'] ?? '');
                update_post_meta($city_id, 'population', $normalized['population']);
                update_post_meta($city_id, 'surface', $normalized['surface'] ?? 0);
                if (isset($normalized['latitude'])) {
                    update_post_meta($city_id, 'latitude', $normalized['latitude']);
                }
                if (isset($normalized['longitude'])) {
                    update_post_meta($city_id, 'longitude', $normalized['longitude']);
                }
                $imported++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('%d ville(s) importée(s), %d ignorée(s) (déjà existantes)', 'osmose-ads'),
                $imported,
                $skipped
            ),
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => count($communes),
        ));
    }
    
    /**
     * Handler AJAX pour importer des villes
     */
    public function ajax_import_cities() {
        // Log pour débogage
        error_log('Osmose ADS: ajax_import_cities called');
        error_log('Osmose ADS: POST data = ' . print_r($_POST, true));
        
        // Vérifier le nonce
        if (!check_ajax_referer('osmose_ads_nonce', 'nonce', false)) {
            error_log('Osmose ADS: Nonce check failed');
            wp_send_json_error(array('message' => __('Erreur de sécurité - nonce invalide', 'osmose-ads')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            error_log('Osmose ADS: User does not have manage_options capability');
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
            return;
        }
        
        // Charger les dépendances
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $import_type = sanitize_text_field($_POST['import_type'] ?? '');
        
        if (empty($import_type)) {
            error_log('Osmose ADS: import_type is empty');
            wp_send_json_error(array('message' => __('Type d\'import non spécifié', 'osmose-ads')));
            return;
        }
        
        error_log('Osmose ADS: import_type = ' . $import_type);
        
        $geo_api = new France_Geo_API();
        
        $communes = array();
        $errors = array();
        
        switch ($import_type) {
            case 'department':
                $department_code = sanitize_text_field($_POST['department_code'] ?? '');
                error_log('Osmose ADS: department_code = ' . $department_code);
                if (empty($department_code)) {
                    wp_send_json_error(array('message' => __('Code département requis', 'osmose-ads')));
                    return;
                }
                error_log('Osmose ADS: Calling get_communes_by_department...');
                $communes = $geo_api->get_communes_by_department($department_code);
                break;
                
            case 'region':
                $region_code = sanitize_text_field($_POST['region_code'] ?? '');
                error_log('Osmose ADS: region_code = ' . $region_code);
                if (empty($region_code)) {
                    wp_send_json_error(array('message' => __('Code région requis', 'osmose-ads')));
                    return;
                }
                error_log('Osmose ADS: Calling get_communes_by_region...');
                $communes = $geo_api->get_communes_by_region($region_code);
                break;
                
            case 'distance':
                $city_code = sanitize_text_field($_POST['city_code'] ?? '');
                $distance = floatval($_POST['distance'] ?? 10);
                error_log('Osmose ADS: city_code = ' . $city_code . ', distance = ' . $distance);
                if (empty($city_code)) {
                    wp_send_json_error(array('message' => __('Ville de référence requise', 'osmose-ads')));
                    return;
                }
                error_log('Osmose ADS: Calling get_communes_by_distance...');
                $communes = $geo_api->get_communes_by_distance($city_code, $distance);
                break;
                
            default:
                error_log('Osmose ADS: Invalid import_type = ' . $import_type);
                wp_send_json_error(array('message' => __('Type d\'import invalide: ' . $import_type, 'osmose-ads')));
                return;
        }
        
        if (is_wp_error($communes)) {
            error_log('Osmose ADS: API returned WP_Error: ' . $communes->get_error_message());
            wp_send_json_error(array('message' => __('Erreur API: ' . $communes->get_error_message(), 'osmose-ads')));
            return;
        }
        
        if (empty($communes) || !is_array($communes)) {
            error_log('Osmose ADS: No communes found or invalid format. Type: ' . gettype($communes) . ', Count: ' . (is_array($communes) ? count($communes) : 'N/A'));
            wp_send_json_error(array('message' => __('Aucune commune trouvée ou format de réponse invalide', 'osmose-ads')));
            return;
        }
        
        error_log('Osmose ADS: Found ' . count($communes) . ' communes');
        
        // Importer les communes
        $imported = 0;
        $skipped = 0;
        $errors_count = 0;
        
        error_log('Osmose ADS: Starting import of ' . count($communes) . ' communes');
        
        foreach ($communes as $index => $commune) {
            if ($index % 100 === 0) {
                error_log('Osmose ADS: Processing commune ' . ($index + 1) . ' / ' . count($communes));
            }
            $normalized = $geo_api->normalize_commune_data($commune);
            
            // Vérifier si la ville existe déjà (par code INSEE)
            $existing = get_posts(array(
                'post_type' => 'city',
                'meta_query' => array(
                    array(
                        'key' => 'insee_code',
                        'value' => $normalized['code'],
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
            ));
            
            if (!empty($existing)) {
                $skipped++;
                continue;
            }
            
            // Vérifier les données minimales
            if (empty($normalized['name']) || empty($normalized['code'])) {
                error_log('Osmose ADS: Skipping commune with missing data: ' . print_r($commune, true));
                $skipped++;
                continue;
            }
            
            // Créer la ville
            $city_id = wp_insert_post(array(
                'post_title' => $normalized['name'],
                'post_type' => 'city',
                'post_status' => 'publish',
            ));
            
            if (is_wp_error($city_id)) {
                error_log('Osmose ADS: Error creating post for ' . $normalized['name'] . ': ' . $city_id->get_error_message());
                $errors_count++;
                continue;
            }
            
            if ($city_id) {
                update_post_meta($city_id, 'name', $normalized['name']);
                update_post_meta($city_id, 'insee_code', $normalized['code']);
                update_post_meta($city_id, 'postal_code', $normalized['postal_code']);
                update_post_meta($city_id, 'all_postal_codes', $normalized['all_postal_codes'] ?? $normalized['postal_code']);
                update_post_meta($city_id, 'department', $normalized['department']);
                update_post_meta($city_id, 'department_name', $normalized['department_name'] ?? '');
                update_post_meta($city_id, 'region', $normalized['region']);
                update_post_meta($city_id, 'region_name', $normalized['region_name'] ?? '');
                update_post_meta($city_id, 'population', $normalized['population']);
                update_post_meta($city_id, 'surface', $normalized['surface'] ?? 0);
                if (isset($normalized['latitude'])) {
                    update_post_meta($city_id, 'latitude', $normalized['latitude']);
                }
                if (isset($normalized['longitude'])) {
                    update_post_meta($city_id, 'longitude', $normalized['longitude']);
                }
                $imported++;
            } else {
                error_log('Osmose ADS: wp_insert_post returned false for ' . $normalized['name']);
                $errors_count++;
            }
        }
        
        error_log('Osmose ADS: Import completed. Imported: ' . $imported . ', Skipped: ' . $skipped . ', Errors: ' . $errors_count);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('%d ville(s) importée(s), %d ignorée(s) (déjà existantes), %d erreur(s)', 'osmose-ads'),
                $imported,
                $skipped,
                $errors_count
            ),
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors_count,
            'total' => count($communes),
        ));
    }
    
    /**
     * Handler AJAX pour récupérer les départements
     */
    public function ajax_get_departments() {
        // Vérifier le nonce
        if (!check_ajax_referer('osmose_ads_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
            return;
        }
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $geo_api = new France_Geo_API();
        $departments = $geo_api->get_departments();
        
        if (is_wp_error($departments)) {
            wp_send_json_error(array('message' => $departments->get_error_message()));
            return;
        }
        
        if (!is_array($departments) || empty($departments)) {
            wp_send_json_error(array('message' => __('Aucun département trouvé', 'osmose-ads')));
            return;
        }
        
        wp_send_json_success($departments);
    }
    
    /**
     * Handler AJAX pour récupérer les régions
     */
    public function ajax_get_regions() {
        // Vérifier le nonce
        if (!check_ajax_referer('osmose_ads_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
            return;
        }
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $geo_api = new France_Geo_API();
        $regions = $geo_api->get_regions();
        
        if (is_wp_error($regions)) {
            wp_send_json_error(array('message' => $regions->get_error_message()));
            return;
        }
        
        if (!is_array($regions) || empty($regions)) {
            wp_send_json_error(array('message' => __('Aucune région trouvée', 'osmose-ads')));
            return;
        }
        
        wp_send_json_success($regions);
    }
    
    /**
     * Handler AJAX pour rechercher une ville
     */
    public function ajax_search_city() {
        // Vérifier le nonce
        if (!check_ajax_referer('osmose_ads_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
            return;
        }
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        if (empty($search)) {
            wp_send_json_error(array('message' => __('Terme de recherche requis', 'osmose-ads')));
            return;
        }
        
        $geo_api = new France_Geo_API();
        $communes = $geo_api->search_commune($search);
        
        if (is_wp_error($communes)) {
            wp_send_json_error(array('message' => $communes->get_error_message()));
            return;
        }
        
        wp_send_json_success(is_array($communes) ? $communes : array());
    }
    
    /**
     * Handler AJAX pour générer un article
     */
    public function ajax_generate_article() {
        // Vérifier le nonce
        if (!check_ajax_referer('osmose_generate_article_ajax', 'nonce', false)) {
            wp_send_json_error(__('Erreur de sécurité', 'osmose-ads'));
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permissions insuffisantes', 'osmose-ads'));
            return;
        }
        
        // Récupérer les données
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $featured_image_id = isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0;
        $publish_immediately = isset($_POST['publish_immediately']) && intval($_POST['publish_immediately']) === 1;
        
        if (empty($keyword)) {
            wp_send_json_error(__('Le mot-clé est requis', 'osmose-ads'));
            return;
        }
        
        // Charger le générateur d'articles
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-article-generator.php';
        $generator = new Osmose_Article_Generator();
        
        // Générer l'article avec le mot-clé fourni
        $result = $generator->generate_article($keyword);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        if (!$result || !is_numeric($result)) {
            wp_send_json_error(__('Erreur lors de la génération de l\'article', 'osmose-ads'));
            return;
        }
        
        $article_id = intval($result);
        
        // Définir l'image mise en avant si fournie
        if ($featured_image_id > 0) {
            set_post_thumbnail($article_id, $featured_image_id);
        }
        
        // Publier immédiatement si demandé
        if ($publish_immediately) {
            wp_update_post(array(
                'ID' => $article_id,
                'post_status' => 'publish',
            ));
        }
        
        // Retourner le succès avec les informations de l'article
        wp_send_json_success(array(
            'message' => __('Article généré avec succès!', 'osmose-ads'),
            'article_id' => $article_id,
            'edit_link' => get_edit_post_link($article_id, 'raw'),
        ));
    }
    
    /**
     * Handler AJAX pour tester l'envoi d'email
     */
    public function ajax_test_email() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
            return;
        }
        
        $test_email = isset($_POST['email']) ? sanitize_email($_POST['email']) : get_option('admin_email');
        
        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => __('Adresse email invalide', 'osmose-ads')));
            return;
        }
        
        // Créer des données de test
        $test_data = array(
            'first_name' => 'Test',
            'last_name' => 'Email',
            'email' => $test_email,
            'phone' => '01 23 45 67 89',
            'property_type' => 'maison',
            'postal_code' => '75001',
            'address' => '123 Rue de Test',
            'city' => 'Paris',
            'surface' => '100',
            'project_type' => 'Toiture',
            'project_details' => 'Hydrofuge, Démoussage',
        );
        
        try {
            // Générer l'email de test
            $html_message = Osmose_Ads_Email::generate_admin_notification_email($test_data);
            $subject = __('[TEST] Email de test - Osmose ADS', 'osmose-ads');
            
            // Headers pour email HTML
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );
            
            $mail_sent = wp_mail($test_email, $subject, $html_message, $headers);
            
            if ($mail_sent) {
                wp_send_json_success(array(
                    'message' => __('Email de test envoyé avec succès à ', 'osmose-ads') . $test_email
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Erreur lors de l\'envoi de l\'email. Vérifiez la configuration SMTP de WordPress.', 'osmose-ads')
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Erreur: ', 'osmose-ads') . $e->getMessage()
            ));
        }
    }
}



