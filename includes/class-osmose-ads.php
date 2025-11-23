<?php
/**
 * Classe principale du plugin
 */
class Osmose_Ads {

    /**
     * Loader responsable de maintenir et enregistrer tous les hooks
     */
    protected $loader;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_core_hooks();
    }

    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-loader.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-i18n.php';
        
        // Core classes
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-post-types.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-rewrite.php';
        
        // Models
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad.php';
        
        // Services
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-city-content-personalizer.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
        
        // Admin
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/class-osmose-ads-admin.php';
        
        // Public
        require_once OSMOSE_ADS_PLUGIN_DIR . 'public/class-osmose-ads-public.php';
        
        $this->loader = new Osmose_Ads_Loader();
    }

    /**
     * Définir les hooks admin
     */
    private function define_admin_hooks() {
        $plugin_admin = new Osmose_Ads_Admin();
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Redirection après activation
        $this->loader->add_action('admin_init', $plugin_admin, 'activation_redirect');
        
        // Enregistrer les handlers AJAX (doit être fait tôt, pas dans admin_init)
        // Les handlers AJAX WordPress doivent être enregistrés avant que les requêtes ne soient faites
        $this->loader->add_action('init', $plugin_admin, 'register_ajax_handlers', 1);
        $this->loader->add_action('init', $plugin_admin, 'register_cities_ajax_handlers', 1);
    }

    /**
     * Définir les hooks publics
     */
    private function define_public_hooks() {
        $plugin_public = new Osmose_Ads_Public();
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Définir les hooks core
     */
    private function define_core_hooks() {
        $plugin_i18n = new Osmose_Ads_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        
        // Post Types
        $post_types = new Osmose_Ads_Post_Types();
        $this->loader->add_action('init', $post_types, 'register_post_types');
        $this->loader->add_action('init', $post_types, 'register_taxonomies');
        
        // Rewrite Rules
        $rewrite = new Osmose_Ads_Rewrite();
        $this->loader->add_action('init', $rewrite, 'add_rewrite_rules');
        $this->loader->add_filter('query_vars', $rewrite, 'add_query_vars');
        $this->loader->add_filter('template_include', $rewrite, 'template_loader');
    }

    /**
     * Exécuter le loader
     */
    public function run() {
        $this->loader->run();
    }
}



