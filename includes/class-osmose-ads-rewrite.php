<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function add_rewrite_rules() {
        // Flush rules si nécessaire
        if (get_option('osmose_ads_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('osmose_ads_flush_rewrite_rules');
        }
    }

    public function add_query_vars($vars) {
        return $vars;
    }

    public function template_loader($template) {
        global $post;
        
        // Si c'est un post de type 'ad', charger le template personnalisé
        if (isset($post) && $post->post_type === 'ad') {
            // Vérifier si un template existe dans le thème
            $theme_template = locate_template(array('single-ad.php'));
            if ($theme_template) {
                return $theme_template;
            }
            
            // Sinon, utiliser le template du plugin
            $plugin_template = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/single-ad.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
}

