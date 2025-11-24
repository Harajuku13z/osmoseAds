<?php
/**
 * Enregistrement des Custom Post Types
 */
class Osmose_Ads_Post_Types {

    public function register_post_types() {
        $this->register_ad_template_post_type();
        $this->register_ad_post_type();
        $this->register_city_post_type();
    }

    /**
     * Enregistrer le CPT pour les templates d'annonces
     */
    private function register_ad_template_post_type() {
        $labels = array(
            'name'               => __('Templates d\'Annonces', 'osmose-ads'),
            'singular_name'      => __('Template d\'Annonce', 'osmose-ads'),
            'menu_name'          => __('Templates', 'osmose-ads'),
            'add_new'            => __('Ajouter un Template', 'osmose-ads'),
            'add_new_item'       => __('Ajouter un nouveau Template', 'osmose-ads'),
            'edit_item'          => __('Modifier le Template', 'osmose-ads'),
            'new_item'           => __('Nouveau Template', 'osmose-ads'),
            'view_item'          => __('Voir le Template', 'osmose-ads'),
            'search_items'       => __('Rechercher des Templates', 'osmose-ads'),
            'not_found'          => __('Aucun template trouvé', 'osmose-ads'),
            'not_found_in_trash' => __('Aucun template dans la corbeille', 'osmose-ads'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // On le gère manuellement
            'show_in_admin_bar'   => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'custom-fields'),
            'rewrite'             => false,
        );

        register_post_type('ad_template', $args);
    }

    /**
     * Enregistrer le CPT pour les annonces
     */
    private function register_ad_post_type() {
        $labels = array(
            'name'               => __('Annonces', 'osmose-ads'),
            'singular_name'      => __('Annonce', 'osmose-ads'),
            'menu_name'          => __('Annonces', 'osmose-ads'),
            'add_new'            => __('Ajouter une Annonce', 'osmose-ads'),
            'add_new_item'       => __('Ajouter une nouvelle Annonce', 'osmose-ads'),
            'edit_item'          => __('Modifier l\'Annonce', 'osmose-ads'),
            'new_item'           => __('Nouvelle Annonce', 'osmose-ads'),
            'view_item'          => __('Voir l\'Annonce', 'osmose-ads'),
            'search_items'       => __('Rechercher des Annonces', 'osmose-ads'),
            'not_found'          => __('Aucune annonce trouvée', 'osmose-ads'),
            'not_found_in_trash' => __('Aucune annonce dans la corbeille', 'osmose-ads'),
        );

        // Utiliser la même structure d'URL que les posts
        // Configuration pour que les annonces utilisent exactement la même structure que les posts
        // sans préfixe /ad/
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // On le gère manuellement
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'comments', 'author', 'trackbacks', 'revisions'),
            'rewrite'             => false,  // Désactivé pour gérer manuellement les URLs
            'query_var'           => 'ad',
            'show_in_rest'        => true, // Support Gutenberg
            'taxonomies'          => array('category', 'post_tag'), // Support des catégories et tags WordPress
        );

        register_post_type('ad', $args);
        
        // Associer les catégories et tags WordPress au CPT 'ad'
        register_taxonomy_for_object_type('category', 'ad');
        register_taxonomy_for_object_type('post_tag', 'ad');
    }

    /**
     * Enregistrer le CPT pour les villes
     */
    private function register_city_post_type() {
        $labels = array(
            'name'               => __('Villes', 'osmose-ads'),
            'singular_name'      => __('Ville', 'osmose-ads'),
            'menu_name'          => __('Villes', 'osmose-ads'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title', 'custom-fields'),
            'rewrite'             => false,
        );

        register_post_type('city', $args);
    }

    /**
     * Enregistrer les taxonomies
     */
    public function register_taxonomies() {
        // Taxonomie pour les services (optionnel)
        register_taxonomy('ad_service', array('ad', 'ad_template'), array(
            'labels' => array(
                'name' => __('Services', 'osmose-ads'),
                'singular_name' => __('Service', 'osmose-ads'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'hierarchical' => true,
        ));
    }
}



