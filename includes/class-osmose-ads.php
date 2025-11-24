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
        
        // Enregistrer les handlers AJAX immédiatement (pas dans admin_init)
        // Les handlers AJAX WordPress doivent être enregistrés avant que les requêtes ne soient faites
        // On peut les enregistrer sur 'plugins_loaded' ou 'init' avec priorité haute
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'register_ajax_handlers', 20);
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'register_cities_ajax_handlers', 20);
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
        
        // Inclure les annonces dans les requêtes principales du blog
        $this->loader->add_action('pre_get_posts', $this, 'include_ads_in_main_query');
        
        // Créer la catégorie "Annonces" lors de l'activation
        $this->loader->add_action('init', $this, 'create_annonces_category', 20);
        
        // Assigner automatiquement la catégorie "Annonces" lors de la création d'annonces
        $this->loader->add_action('save_post_ad', $this, 'assign_annonces_category', 10, 1);
    }
    
    /**
     * Inclure les annonces dans les requêtes principales du blog
     */
    public function include_ads_in_main_query($query) {
        // Ne pas modifier les requêtes admin ou AJAX
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Inclure les annonces dans la page d'accueil, archive, recherche et catégories
        if ($query->is_home() || $query->is_archive() || $query->is_search() || $query->is_category()) {
            $post_types = $query->get('post_type');
            
            // Si aucun post_type n'est défini ou si c'est 'post', ajouter 'ad'
            if (empty($post_types) || $post_types === 'post') {
                $query->set('post_type', array('post', 'ad'));
            } elseif (is_array($post_types) && in_array('post', $post_types)) {
                if (!in_array('ad', $post_types)) {
                    $post_types[] = 'ad';
                    $query->set('post_type', array_unique($post_types));
                }
            }
        }
    }
    
    /**
     * Créer la catégorie "Annonces" si elle n'existe pas
     */
    public function create_annonces_category() {
        // Vérifier si la catégorie existe déjà
        $category_exists = term_exists('Annonces', 'category');
        
        if (!$category_exists) {
            // Créer la catégorie "Annonces"
            $category_id = wp_create_category('Annonces');
            
            if (!is_wp_error($category_id)) {
                // Sauvegarder l'ID de la catégorie dans les options
                update_option('osmose_ads_category_id', $category_id);
            }
        } else {
            // Si elle existe déjà, sauvegarder son ID
            $category_id = is_array($category_exists) ? $category_exists['term_id'] : $category_exists;
            update_option('osmose_ads_category_id', $category_id);
        }
    }
    
    /**
     * Assigner automatiquement la catégorie "Annonces" aux nouvelles annonces
     */
    public function assign_annonces_category($post_id) {
        // Vérifier que c'est bien une annonce
        if (get_post_type($post_id) !== 'ad') {
            return;
        }
        
        // Vérifier si c'est une révision ou un autosave
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Récupérer l'ID de la catégorie "Annonces"
        $category_id = get_option('osmose_ads_category_id');
        
        if (!$category_id) {
            // Si la catégorie n'existe pas, la créer
            $this->create_annonces_category();
            $category_id = get_option('osmose_ads_category_id');
        }
        
        if ($category_id) {
            // Récupérer les catégories actuelles
            $current_categories = wp_get_post_categories($post_id);
            
            // Ajouter la catégorie "Annonces" si elle n'est pas déjà présente
            if (!in_array($category_id, $current_categories)) {
                $current_categories[] = $category_id;
                wp_set_post_categories($post_id, $current_categories);
            }
        }
    }

    /**
     * Exécuter le loader
     */
    public function run() {
        $this->loader->run();
    }
}



