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
        
        $permalink_structure = get_option('permalink_structure');
        
        if (!empty($permalink_structure)) {
            // Si la structure est simple comme /%postname%/
            if (strpos($permalink_structure, '%postname%') !== false && strpos($permalink_structure, '%year%') === false) {
                // Les annonces utilisent déjà la structure standard grâce à rewrite dans le CPT
                // Pas besoin de règle supplémentaire, WordPress gère automatiquement
            }
            
            // Ajouter une règle pour gérer les URLs avec /ad/ (anciennes URLs)
            // et rediriger vers la nouvelle structure sans préfixe
            add_rewrite_rule(
                '^ad/([^/]+)/?$',
                'index.php?post_type=ad&name=$matches[1]',
                'top'
            );
        } else {
            // Si les permaliens ne sont pas activés, ajouter une règle pour les URLs propres
            add_rewrite_rule(
                '^([^/]+)/?$',
                'index.php?post_type=ad&name=$matches[1]',
                'bottom' // bottom pour ne pas interférer avec les autres règles
            );
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
        
        // Ajouter une règle de réécriture pour le sitemap XML index
        add_rewrite_rule('^sitemap-ads\.xml$', 'index.php?osmose_ads_sitemap=1', 'top');
        add_rewrite_tag('%osmose_ads_sitemap%', '([0-9]+)');
        
        // Ajouter une règle de réécriture pour les sitemaps numérotés
        add_rewrite_rule('^sitemap-ads-(\d+)\.xml$', 'index.php?osmose_ads_sitemap_num=$matches[1]', 'top');
        add_rewrite_tag('%osmose_ads_sitemap_num%', '([0-9]+)');
    }

    /**
     * Gérer le tracking des appels via URL
     */
    public function handle_call_tracking() {
        global $wp_query;
        
        // Log pour debug
        error_log('Osmose ADS: handle_call_tracking called');
        error_log('Osmose ADS: Query vars: ' . print_r($wp_query->query_vars, true));
        error_log('Osmose ADS: GET params: ' . print_r($_GET, true));
        error_log('Osmose ADS: REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        
        if (!isset($wp_query->query_vars['osmose_call_track'])) {
            error_log('Osmose ADS: osmose_call_track not set in query_vars');
            return;
        }
        
        error_log('Osmose ADS: Call tracking triggered!');
        
        // Récupérer les paramètres
        $ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
        $ad_slug = isset($_GET['ad_slug']) ? sanitize_text_field($_GET['ad_slug']) : '';
        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'unknown';
        $phone = isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '';
        $page_url = isset($_GET['page_url']) ? urldecode($_GET['page_url']) : '';
        
        error_log('Osmose ADS: Tracking params - ad_id: ' . $ad_id . ', source: ' . $source . ', phone: ' . $phone);
        
        // Enregistrer l'appel dans la base de données
        global $wpdb;
        $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
        
        // Vérifier que la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('Osmose ADS: ERROR - Table does not exist!');
        }
        
        // Récupérer les informations de l'utilisateur
        $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referrer = esc_url_raw($_SERVER['HTTP_REFERER'] ?? $page_url);
        
        // Vérifier que la colonne 'source' existe avant d'insérer
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $has_source_column = false;
        foreach ($columns as $column) {
            if ($column->Field === 'source') {
                $has_source_column = true;
                break;
            }
        }
        
        // Préparer les données à insérer
        $insert_data = array(
            'ad_id' => $ad_id ?: null,
            'ad_slug' => $ad_slug ?: '',
            'page_url' => $page_url ?: $referrer,
            'phone_number' => $phone ?: '',
            'user_ip' => $user_ip ?: '',
            'user_agent' => $user_agent ?: '',
            'referrer' => $referrer ?: '',
            'call_time' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $insert_format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
        
        // Ajouter 'source' seulement si la colonne existe
        if ($has_source_column) {
            $insert_data['source'] = $source;
            $insert_format[] = '%s';
        } else {
            // Si la colonne n'existe pas, essayer de l'ajouter
            error_log('Osmose ADS: WARNING - Column "source" missing, attempting to add it');
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) DEFAULT NULL");
            // Re-vérifier
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            foreach ($columns as $column) {
                if ($column->Field === 'source') {
                    $insert_data['source'] = $source;
                    $insert_format[] = '%s';
                    break;
                }
            }
        }
        
        // Enregistrer l'appel
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            $insert_format
        );
        
        if ($result === false) {
            error_log('Osmose ADS: ERROR inserting call - ' . $wpdb->last_error);
        } else {
            error_log('Osmose ADS: Call tracked successfully! ID: ' . $wpdb->insert_id);
        }
        
        // Rediriger vers le numéro de téléphone
        if (!empty($phone)) {
            // Nettoyer le numéro pour le format tel:
            $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
            error_log('Osmose ADS: Redirecting to tel:' . $phone_clean);
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
        $vars[] = 'osmose_ads_sitemap';
        $vars[] = 'osmose_ads_sitemap_num';
        return $vars;
    }

    public function template_loader($template) {
        global $wp_query, $post;
        
        // Ne rien faire en admin
        if (is_admin()) {
            return $template;
        }
        
        // Récupérer l'objet de la requête
        $queried_object = get_queried_object();
        
        // Vérifier si c'est un post de type 'ad'
        $is_ad = false;
        $is_generated_article = false;
        $post_id = 0;
        
        if ($queried_object && isset($queried_object->post_type)) {
            $post_id = isset($queried_object->ID) ? $queried_object->ID : 0;
            
            if ($queried_object->post_type === 'ad') {
                $is_ad = true;
            } elseif ($queried_object->post_type === 'post' && $post_id > 0) {
                // Vérifier si c'est un article généré
                $is_generated_article = (get_post_meta($post_id, 'article_auto_generated', true) === '1');
            }
        } elseif (isset($post) && $post) {
            $post_id = $post->ID;
            
            if ($post->post_type === 'ad') {
                $is_ad = true;
            } elseif ($post->post_type === 'post' && $post_id > 0) {
                $is_generated_article = (get_post_meta($post_id, 'article_auto_generated', true) === '1');
            }
        }
        
        if ($is_ad || $is_generated_article) {
            // Si on est dans le blog (home, archive, category, search, tag, author), utiliser le template standard
            if (is_home() || is_archive() || is_category() || is_search() || is_tag() || is_author()) {
                return $template; // Laisser WordPress utiliser le template standard du thème (single.php)
            }
            
            // Si on accède directement à l'annonce/article via son URL, utiliser le template des annonces
            if (is_single() && !is_admin()) {
                // D'abord vérifier si un template single-ad.php existe dans le thème
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
