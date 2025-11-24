<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function add_rewrite_rules() {
        // Pour que les annonces utilisent la même structure d'URL que les posts,
        // on doit intercepter les requêtes et vérifier si le slug correspond à une annonce
        // Avant que WordPress ne cherche un post normal
        
        $permalink_structure = get_option('permalink_structure');
        
        if (!empty($permalink_structure)) {
            // Extraire le pattern de la structure de permalink
            // Exemple: /%year%/%monthnum%/%day%/%postname%/ ou /%postname%/
            
            // Ajouter une règle qui intercepte toutes les requêtes de posts
            // et vérifie si c'est une annonce avant de chercher un post normal
            // Cette règle doit être en priorité haute pour être évaluée en premier
            
            // Si la structure est simple comme /%postname%/
            if (strpos($permalink_structure, '%postname%') !== false && strpos($permalink_structure, '%year%') === false) {
                // Pattern simple : juste le slug
                // On ajoute une règle qui capture le slug et vérifie si c'est une annonce
                add_rewrite_rule(
                    '^([^/]+)/?$',
                    'index.php?post_type=ad&name=$matches[1]',
                    'top' // Priorité haute pour intercepter avant les posts normaux
                );
            }
            
            // Ajouter le query var pour 'name' avec post_type
            add_rewrite_tag('%ad_name%', '([^/]+)', 'post_type=ad&name=');
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

