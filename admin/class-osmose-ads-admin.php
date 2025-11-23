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
        
        // Charger WordPress Media Library sur les pages de templates
        if (strpos($hook, 'osmose-ads-templates') !== false) {
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
        add_menu_page(
            __('Osmose ADS', 'osmose-ads'),
            __('Osmose ADS', 'osmose-ads'),
            'manage_options',
            'osmose-ads',
            array($this, 'display_dashboard'),
            '', // Pas d'icône
            30
        );
        
        // Masquer l'icône par défaut avec CSS
        add_action('admin_head', array($this, 'hide_menu_icon'));
        
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
            __('Génération en Masse', 'osmose-ads'),
            __('Génération en Masse', 'osmose-ads'),
            'manage_options',
            'osmose-ads-bulk',
            array($this, 'display_bulk_generation')
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
            __('Configuration', 'osmose-ads'),
            __('Configuration', 'osmose-ads'),
            'manage_options',
            'osmose-ads-setup',
            array($this, 'display_setup')
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
        register_setting('osmose_ads_settings', 'osmose_ads_openai_api_key');
        register_setting('osmose_ads_settings', 'osmose_ads_ai_provider');
        register_setting('osmose_ads_settings', 'osmose_ads_services');
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

    public function display_bulk_generation() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/bulk-generation.php';
    }

    public function display_cities() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/cities.php';
    }

    public function display_settings() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/settings.php';
    }
    
    /**
     * Masquer l'icône du menu dans la sidebar
     */
    public function hide_menu_icon() {
        echo '<style>
            #toplevel_page_osmose-ads .wp-menu-image {
                display: none !important;
            }
            #toplevel_page_osmose-ads .wp-menu-name {
                padding-left: 12px !important;
            }
        </style>';
    }

    /**
     * Enregistrer les handlers AJAX
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_osmose_ads_create_template', array($this, 'ajax_create_template'));
        add_action('wp_ajax_osmose_ads_bulk_generate', array($this, 'ajax_bulk_generate'));
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
}



