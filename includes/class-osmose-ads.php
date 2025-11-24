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
        
        // Ajouter les variables de tracking dans le footer
        $this->loader->add_action('wp_footer', $plugin_public, 'add_tracking_variables');
        
        // S'assurer que le contenu des annonces s'affiche dans single.php
        $this->loader->add_filter('the_content', $this, 'display_ad_content_in_single', 10, 1);
    }
    
    /**
     * Afficher le contenu des annonces dans single.php du thème
     */
    public function display_ad_content_in_single($content) {
        global $post;
        
        // Uniquement pour les annonces affichées dans single.php (pas dans le template personnalisé)
        if (!isset($post) || $post->post_type !== 'ad') {
            return $content;
        }
        
        // Si on utilise déjà le template personnalisé, ne pas modifier
        if (is_singular('ad') && !is_home() && !is_archive() && !is_category() && !is_search()) {
            return $content;
        }
        
        // Si le contenu est vide ou si c'est une annonce affichée dans le blog
        if (empty($content) || (is_single() && $post->post_type === 'ad')) {
            // Charger le modèle Ad
            if (!class_exists('Ad')) {
                require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad.php';
            }
            
            try {
                $ad = new Ad($post->ID);
                $ad_content = $ad->get_content();
                
                if (!empty($ad_content)) {
                    // Retourner le contenu de l'annonce
                    return $ad_content;
                }
            } catch (Exception $e) {
                error_log('Osmose ADS: Error loading ad content: ' . $e->getMessage());
            }
        }
        
        return $content;
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
        
        // Intégration avec AIOSEO - S'assurer que les annonces sont reconnues comme des posts
        // AIOSEO utilise différents hooks selon la version
        $this->loader->add_filter('aioseo_post_types', $this, 'add_ads_to_aioseo_post_types', 10, 1);
        $this->loader->add_filter('aioseo_enabled_post_types', $this, 'add_ads_to_aioseo_enabled_types', 10, 1);
        
        // S'assurer que AIOSEO peut modifier les annonces
        $this->loader->add_filter('aioseo_is_valid_post_type', $this, 'aioseo_is_valid_ad_type', 10, 2);
        
        // Flush rewrite rules une fois après la modification du CPT
        $this->loader->add_action('init', $this, 'flush_rewrite_rules_once', 999);
        
        // Inclure les annonces dans les requêtes principales du blog
        $this->loader->add_action('pre_get_posts', $this, 'include_ads_in_main_query');
        
        // Inclure les annonces dans l'écran "Articles" de WordPress
        $this->loader->add_action('pre_get_posts', $this, 'include_ads_in_admin_posts', 10);
        
        // Ajouter une colonne pour identifier les annonces dans la liste des articles
        $this->loader->add_filter('manage_post_posts_columns', $this, 'add_ad_type_column');
        $this->loader->add_action('manage_post_posts_custom_column', $this, 'show_ad_type_column', 10, 2);
        $this->loader->add_filter('manage_edit-post_sortable_columns', $this, 'make_ad_type_sortable');
        
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
        if ($query->is_home() || $query->is_archive() || $query->is_search() || $query->is_category() || $query->is_tag() || $query->is_author()) {
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
     * Inclure les annonces dans l'écran "Articles" de WordPress
     */
    public function include_ads_in_admin_posts($query) {
        // Uniquement dans l'admin
        if (!is_admin()) {
            return;
        }
        
        // Vérifier qu'on est sur la page edit.php (Articles)
        global $pagenow;
        if ($pagenow !== 'edit.php') {
            return;
        }
        
        // Vérifier qu'on n'est pas déjà en train de filtrer par un type de post spécifique
        // et qu'on n'est pas sur une page de CPT spécifique
        $screen = get_current_screen();
        if ($screen && $screen->post_type && $screen->post_type !== 'post') {
            return;
        }
        
        // Si la requête est pour 'post' ou vide (par défaut), inclure aussi 'ad'
        $post_types = $query->get('post_type');
        if (empty($post_types) || $post_types === 'post') {
            $query->set('post_type', array('post', 'ad'));
        } elseif (is_array($post_types) && in_array('post', $post_types) && !in_array('ad', $post_types)) {
            $post_types[] = 'ad';
            $query->set('post_type', array_unique($post_types));
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
     * Ajouter une colonne "Type" dans la liste des articles pour identifier les annonces
     */
    public function add_ad_type_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            // Ajouter la colonne "Type" après "Titre"
            if ($key === 'title') {
                $new_columns['osmose_ad_type'] = __('Type', 'osmose-ads');
            }
        }
        return $new_columns;
    }
    
    /**
     * Afficher le type (Article ou Annonce) dans la colonne
     */
    public function show_ad_type_column($column_name, $post_id) {
        if ($column_name === 'osmose_ad_type') {
            $post_type = get_post_type($post_id);
            if ($post_type === 'ad') {
                echo '<span style="color: #2271b1; font-weight: 600;">📢 ' . __('Annonce', 'osmose-ads') . '</span>';
            } else {
                echo '<span style="color: #646970;">📝 ' . __('Article', 'osmose-ads') . '</span>';
            }
        }
    }
    
    /**
     * Rendre la colonne "Type" triable
     */
    public function make_ad_type_sortable($columns) {
        $columns['osmose_ad_type'] = 'post_type';
        return $columns;
    }

    /**
     * Ajouter les annonces aux post types supportés par AIOSEO
     */
    public function add_ads_to_aioseo_post_types($post_types) {
        if (!is_array($post_types)) {
            $post_types = array();
        }
        
        // Ajouter 'ad' aux post types supportés par AIOSEO
        if (!in_array('ad', $post_types)) {
            $post_types[] = 'ad';
        }
        
        return $post_types;
    }
    
    /**
     * Ajouter les annonces aux types de posts activés dans AIOSEO
     */
    public function add_ads_to_aioseo_enabled_types($post_types) {
        if (!is_array($post_types)) {
            $post_types = array();
        }
        
        // Ajouter 'ad' aux post types activés dans AIOSEO
        if (!in_array('ad', $post_types)) {
            $post_types[] = 'ad';
        }
        
        return $post_types;
    }
    
    /**
     * Vérifier que AIOSEO reconnaît les annonces comme un type de post valide
     */
    public function aioseo_is_valid_ad_type($is_valid, $post_type) {
        if ($post_type === 'ad') {
            return true;
        }
        return $is_valid;
    }
    
    /**
     * Flush rewrite rules une seule fois après la modification du CPT
     */
    public function flush_rewrite_rules_once() {
        if (get_option('osmose_ads_flush_rewrite_rules')) {
            flush_rewrite_rules(false); // false = hard flush
            delete_option('osmose_ads_flush_rewrite_rules');
        }
    }
    
    /**
     * Exécuter le loader
     */
    public function run() {
        $this->loader->run();
    }
}



