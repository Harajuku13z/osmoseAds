<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_tags'));
        add_filter('template_include', array($this, 'template_loader'));
        add_action('template_redirect', array($this, 'intercept_ad_requests'));
        add_action('template_redirect', array($this, 'handle_call_tracking'));
    }

    public function add_rewrite_rules() {
        // Les annonces utilisent maintenant la même structure d'URL que les posts
        // L'interception se fait dans parse_request et template_loader
        
        // Ajouter une règle pour gérer les URLs avec /ad/ (anciennes URLs)
        // et rediriger vers la nouvelle structure sans préfixe
        $permalink_structure = get_option('permalink_structure');
        if (!empty($permalink_structure)) {
            // Si la structure est simple comme /%postname%/
            if (strpos($permalink_structure, '%postname%') !== false && strpos($permalink_structure, '%year%') === false) {
                // Ajouter une règle pour /ad/slug et rediriger vers /slug
                add_rewrite_rule(
                    '^ad/([^/]+)/?$',
                    'index.php?post_type=ad&name=$matches[1]',
                    'top'
                );
            }
        }
        
        // Flush rules si nécessaire
        if (get_option('osmose_ads_flush_rewrite_rules')) {
            flush_rewrite_rules(false);
            delete_option('osmose_ads_flush_rewrite_rules');
        }
    }

    public function add_rewrite_tags() {
        add_rewrite_tag('%ad_slug%', '([^&]+)');
        
        // Ajouter une règle de réécriture pour le tracking des appels
        add_rewrite_rule('^osmose-call-track/?$', 'index.php?osmose_call_track=1', 'top');
        add_rewrite_tag('%osmose_call_track%', '([0-9]+)');
    }

    /**
     * Gérer le tracking des appels via URL
     */
    public function handle_call_tracking() {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['osmose_call_track'])) {
            return;
        }
        
        // Récupérer les paramètres
        $ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
        $ad_slug = isset($_GET['ad_slug']) ? sanitize_text_field($_GET['ad_slug']) : '';
        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'unknown';
        $phone = isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '';
        $page_url = isset($_GET['page_url']) ? esc_url_raw($_GET['page_url']) : '';
        
        // Enregistrer l'appel dans la base de données
        global $wpdb;
        $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
        
        // Récupérer les informations de l'utilisateur
        $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referrer = esc_url_raw($_SERVER['HTTP_REFERER'] ?? $page_url);
        
        // Enregistrer l'appel
        $wpdb->insert(
            $table_name,
            array(
                'ad_id' => $ad_id ?: null,
                'ad_slug' => $ad_slug ?: '',
                'page_url' => $page_url ?: $referrer,
                'phone_number' => $phone ?: '',
                'user_ip' => $user_ip ?: '',
                'user_agent' => $user_agent ?: '',
                'referrer' => $referrer ?: '',
                'call_time' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'source' => $source
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Rediriger vers le numéro de téléphone
        if (!empty($phone)) {
            // Nettoyer le numéro pour le format tel:
            $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
            wp_redirect('tel:' . $phone_clean);
            exit;
        }
        
        // Si pas de numéro, rediriger vers la page d'origine
        if (!empty($page_url)) {
            wp_redirect($page_url);
        } else {
            wp_redirect(home_url());
        }
        exit;
    }

    public function add_query_vars($vars) {
        return $vars;
    }

    public function template_loader($template) {
        global $wp_query, $post;
        
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

    /**
     * Intercepter les requêtes d'annonces avant le chargement du template
     */
    public function intercept_ad_requests() {
        // Cette fonction n'est plus nécessaire avec la nouvelle structure
        // mais on la garde pour compatibilité
    }
}
