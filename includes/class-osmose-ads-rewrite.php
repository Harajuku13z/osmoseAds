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
        
        // Si c'est un post de type 'ad'
        if (isset($post) && $post->post_type === 'ad') {
            // Si on est dans le blog (home, archive, category, search, tag, author), utiliser le template standard
            if (is_home() || is_archive() || is_category() || is_search() || is_tag() || is_author()) {
                return $template; // Laisser WordPress utiliser le template standard du thème (single.php)
            }
            
            // Si on accède directement à l'annonce via son URL, utiliser le template personnalisé
            // Vérifier si un template existe dans le thème
            $theme_template = locate_template(array('single-ad.php', 'single.php'));
            if ($theme_template && strpos($theme_template, 'single-ad.php') !== false) {
                return $theme_template;
            }
            
            // Si on a un template single.php dans le thème et qu'on vient du blog, l'utiliser
            if (is_single() && !is_admin()) {
                $single_template = locate_template(array('single.php'));
                if ($single_template) {
                    return $single_template;
                }
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

