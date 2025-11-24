<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function add_rewrite_rules() {
        // Créer des rewrite rules pour que les annonces utilisent la même structure d'URL que les posts
        $permalink_structure = get_option('permalink_structure');
        
        if (!empty($permalink_structure)) {
            // Si une structure de permalink personnalisée est définie
            // Ajouter une règle qui capture les slugs d'annonces
            // et les route vers le post_type 'ad'
            
            // Exemple: si la structure est /%postname%/, on capture directement le slug
            // et on vérifie si c'est une annonce
            add_rewrite_tag('%ad_slug%', '([^/]+)', 'post_type=ad&name=');
            
            // Règle pour capturer les slugs d'annonces
            // Cette règle sera évaluée en premier et vérifiera si le slug correspond à une annonce
            // Si oui, elle route vers post_type=ad, sinon WordPress continue avec les posts normaux
        } else {
            // Structure de permalink par défaut (?p=123)
            // Pas besoin de rewrite rules supplémentaires
        }
        
        // Flush rules si nécessaire
        if (get_option('osmose_ads_flush_rewrite_rules')) {
            flush_rewrite_rules(false);
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

