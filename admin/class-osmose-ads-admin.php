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
        
        wp_localize_script('osmose-ads-admin', 'osmoseAds', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osmose_ads_nonce'),
        ));
    }

    public function add_admin_menu() {
        // Utiliser une icône dashicons (plus compatible)
        add_menu_page(
            __('Osmose ADS', 'osmose-ads'),
            __('Osmose ADS', 'osmose-ads'),
            'manage_options',
            'osmose-ads',
            array($this, 'display_dashboard'),
            'dashicons-megaphone',
            30
        );
        
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
        add_action('wp_ajax_osmose_ads_get_departments', array($this, 'ajax_get_departments'));
        add_action('wp_ajax_osmose_ads_get_regions', array($this, 'ajax_get_regions'));
        add_action('wp_ajax_osmose_ads_search_city', array($this, 'ajax_search_city'));
    }
    
    /**
     * Handler AJAX pour importer des villes
     */
    public function ajax_import_cities() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
        }
        
        // Charger les dépendances
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $import_type = sanitize_text_field($_POST['import_type'] ?? '');
        $geo_api = new France_Geo_API();
        
        $communes = array();
        $errors = array();
        
        switch ($import_type) {
            case 'department':
                $department_code = sanitize_text_field($_POST['department_code'] ?? '');
                if (empty($department_code)) {
                    wp_send_json_error(array('message' => __('Code département requis', 'osmose-ads')));
                }
                $communes = $geo_api->get_communes_by_department($department_code);
                break;
                
            case 'region':
                $region_code = sanitize_text_field($_POST['region_code'] ?? '');
                if (empty($region_code)) {
                    wp_send_json_error(array('message' => __('Code région requis', 'osmose-ads')));
                }
                $communes = $geo_api->get_communes_by_region($region_code);
                break;
                
            case 'distance':
                $city_code = sanitize_text_field($_POST['city_code'] ?? '');
                $distance = floatval($_POST['distance'] ?? 10);
                if (empty($city_code)) {
                    wp_send_json_error(array('message' => __('Ville de référence requise', 'osmose-ads')));
                }
                $communes = $geo_api->get_communes_by_distance($city_code, $distance);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Type d\'import invalide', 'osmose-ads')));
        }
        
        if (is_wp_error($communes)) {
            wp_send_json_error(array('message' => $communes->get_error_message()));
        }
        
        if (empty($communes)) {
            wp_send_json_error(array('message' => __('Aucune commune trouvée', 'osmose-ads')));
        }
        
        // Importer les communes
        $imported = 0;
        $skipped = 0;
        
        foreach ($communes as $commune) {
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
            
            // Créer la ville
            $city_id = wp_insert_post(array(
                'post_title' => $normalized['name'],
                'post_type' => 'city',
                'post_status' => 'publish',
            ));
            
            if ($city_id && !is_wp_error($city_id)) {
                update_post_meta($city_id, 'name', $normalized['name']);
                update_post_meta($city_id, 'insee_code', $normalized['code']);
                update_post_meta($city_id, 'postal_code', $normalized['postal_code']);
                update_post_meta($city_id, 'department', $normalized['department']);
                update_post_meta($city_id, 'department_name', $normalized['department_name'] ?? '');
                update_post_meta($city_id, 'region', $normalized['region']);
                update_post_meta($city_id, 'region_name', $normalized['region_name'] ?? '');
                update_post_meta($city_id, 'population', $normalized['population']);
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
     * Handler AJAX pour récupérer les départements
     */
    public function ajax_get_departments() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $geo_api = new France_Geo_API();
        $departments = $geo_api->get_departments();
        
        if (is_wp_error($departments)) {
            wp_send_json_error(array('message' => $departments->get_error_message()));
        }
        
        wp_send_json_success($departments);
    }
    
    /**
     * Handler AJAX pour récupérer les régions
     */
    public function ajax_get_regions() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $geo_api = new France_Geo_API();
        $regions = $geo_api->get_regions();
        
        if (is_wp_error($regions)) {
            wp_send_json_error(array('message' => $regions->get_error_message()));
        }
        
        wp_send_json_success($regions);
    }
    
    /**
     * Handler AJAX pour rechercher une ville
     */
    public function ajax_search_city() {
        check_ajax_referer('osmose_ads_nonce', 'nonce');
        
        if (!class_exists('France_Geo_API')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        if (empty($search)) {
            wp_send_json_error(array('message' => __('Terme de recherche requis', 'osmose-ads')));
        }
        
        $geo_api = new France_Geo_API();
        $communes = $geo_api->search_commune($search);
        
        wp_send_json_success($communes);
    }
}



