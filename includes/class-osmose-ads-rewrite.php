<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function add_rewrite_rules() {
        // Les annonces utilisent maintenant la même structure d'URL que les posts
        // L'interception se fait dans parse_request et template_loader
        // Pas besoin de rewrite rules spéciales car WordPress va chercher d'abord dans les posts,
        // et nous intercepterons dans template_loader si c'est une 404
        
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
        global $wp_query, $post;
        
        // Intercepter les requêtes pour vérifier si un slug correspond à une annonce
        // avant que WordPress ne retourne 404
        if (is_404() && isset($wp_query->query['name'])) {
            $slug = $wp_query->query['name'];
            
            // Vérifier si ce slug correspond à une annonce
            $ad_posts = get_posts(array(
                'post_type' => 'ad',
                'name' => $slug,
                'posts_per_page' => 1,
                'post_status' => 'publish',
            ));
            
            if (!empty($ad_posts)) {
                // C'est une annonce ! Rediriger la requête vers le bon post
                $wp_query->is_404 = false;
                $wp_query->is_single = true;
                $wp_query->is_singular = true;
                $wp_query->queried_object = $ad_posts[0];
                $wp_query->queried_object_id = $ad_posts[0]->ID;
                $wp_query->posts = array($ad_posts[0]);
                $wp_query->post_count = 1;
                $wp_query->found_posts = 1;
                $post = $ad_posts[0];
                setup_postdata($post);
            }
        }
        
        // Si c'est un post de type 'ad'
        if (isset($post) && $post->post_type === 'ad') {
            // Si on est dans le blog (home, archive, category, search, tag, author), utiliser le template standard
            if (is_home() || is_archive() || is_category() || is_search() || is_tag() || is_author()) {
                return $template; // Laisser WordPress utiliser le template standard du thème (single.php)
            }
            
            // Si on accède directement à l'annonce via son URL, utiliser le template standard du thème
            if (is_single() && !is_admin()) {
                // D'abord essayer le template du thème
                $single_template = locate_template(array('single.php'));
                if ($single_template) {
                    return $single_template;
                }
                
                // Sinon, vérifier si un template single-ad.php existe dans le thème
                $theme_template = locate_template(array('single-ad.php'));
                if ($theme_template) {
                    return $theme_template;
                }
                
                // En dernier recours, utiliser le template du plugin
                $plugin_template = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/single-ad.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }
}

