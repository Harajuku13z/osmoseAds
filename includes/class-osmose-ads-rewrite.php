<?php
/**
 * Gestion des rewrite rules pour les URLs d'annonces
 */
class Osmose_Ads_Rewrite {

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_tags'));
        add_filter('template_include', array($this, 'template_loader'), 999); // Priorité très élevée
        add_filter('single_template', array($this, 'single_template_loader'), 999, 3); // Priorité très élevée
        add_action('template_redirect', array($this, 'force_template_redirect'), 1); // Forcer le template très tôt
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
     * Détecter si la requête provient d'un bot
     * 
     * @param string $user_agent User agent de la requête
     * @return bool True si c'est un bot, false sinon
     */
    private function is_bot($user_agent = null) {
        if ($user_agent === null) {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }
        
        $user_agent_lower = strtolower($user_agent);
        
        // Ne pas bloquer si pas de user agent (peut être un vrai utilisateur)
        if (empty($user_agent)) {
            return false;
        }
        
        // Liste des bots connus (patterns spécifiques uniquement)
        // On évite les patterns trop génériques comme "bot" seul
        $bot_patterns = array(
            // Bots de recherche (patterns spécifiques)
            'googlebot/', 'bingbot/', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
            'facebookexternalhit', 'twitterbot', 'rogerbot', 'linkedinbot',
            'applebot/', 'qwantify',
            // Bots sociaux (patterns spécifiques)
            'embedly', 'quora link preview', 'pinterestbot', 'slackbot', 'redditbot',
            'whatsapp', 'flipboard', 'tumblr', 'bitlybot', 'skypeuripreview',
            'nuzzel', 'discordbot',
            // Outils de scraping (patterns spécifiques)
            'python-requests/', 'go-http-client', 'okhttp/', 'scrapy/',
            'mechanize', 'phantomjs', 'headlesschrome', 'selenium', 'webdriver',
            // Outils de monitoring (patterns spécifiques)
            'pingdom', 'gtmetrix', 'pagespeed insights', 'lighthouse',
            'speedcurve', 'newrelicpinger', 'datadog', 'uptimerobot',
            'pingbot', 'site24x7', 'statuscake', 'monitis', 'alertra',
            'siteuptime', 'hosttracker', 'websitepulse', 'dotcom-monitor',
            'siteimprove',
            // Outils SEO (patterns spécifiques)
            'screaming frog', 'ahrefsbot', 'moz.com', 'semrushbot',
            'majestic', 'sistrix', 'deepcrawl', 'sitebulb', 'oncrawl',
            'botify', 'lumar', 'brightedge', 'conductor', 'searchmetrics',
            'seomator', 'sitechecker', 'siteauditor', 'siteanalyzer',
            // Autres bots spécifiques
            'bitrix link preview', 'smtbot',
        );
        
        // Vérifier les patterns spécifiques
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return true;
            }
        }
        
        // Vérifier les patterns génériques mais seulement s'ils sont au début ou suivis d'un slash
        $generic_bot_patterns = array(
            '/bot', 'bot/', 'crawler/', 'spider/', 'scraper/',
        );
        
        foreach ($generic_bot_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return true;
            }
        }
        
        // Vérifier les outils de ligne de commande (curl, wget) mais seulement s'ils sont seuls
        if (preg_match('/^(curl|wget|libwww|lwp-trivial|perl|ruby|php)\//i', $user_agent)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Gérer le tracking des appels via URL
     */
    public function handle_call_tracking() {
        global $wp_query;
        
        // Vérifier si c'est un bot - ne pas tracker les appels des bots
        if ($this->is_bot()) {
            error_log('Osmose ADS: Bot detected, skipping call tracking');
            // Rediriger quand même vers le numéro de téléphone si fourni
            $phone = isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '';
            if (!empty($phone)) {
                $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
                wp_redirect('tel:' . $phone_clean);
                exit;
            }
            // Sinon, rediriger vers la page d'origine ou l'accueil
            $page_url = isset($_GET['page_url']) ? urldecode($_GET['page_url']) : '';
            if (!empty($page_url)) {
                wp_redirect($page_url);
            } else {
                wp_redirect(home_url());
            }
            exit;
        }
        
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
        
        // Vérifier si c'est un bot - ne pas tracker les appels des bots
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        if ($this->is_bot($user_agent)) {
            error_log('Osmose ADS: Bot detected, skipping call tracking. User-Agent: ' . $user_agent);
            // Rediriger quand même vers le numéro de téléphone si fourni
            if (!empty($phone)) {
                $phone_clean = preg_replace('/[^0-9+]/', '', $phone);
                wp_redirect('tel:' . $phone_clean);
                exit;
            }
            // Sinon, rediriger vers la page d'origine ou l'accueil
            if (!empty($page_url)) {
                wp_redirect($page_url);
            } else {
                wp_redirect(home_url());
            }
            exit;
        }
        
        // Récupérer les informations de l'utilisateur
        $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
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
        
        // Vérifier si on doit forcer le template (défini dans force_template_redirect)
        if (isset($GLOBALS['osmose_ads_force_template']) && $GLOBALS['osmose_ads_force_template']) {
            if (isset($GLOBALS['osmose_ads_template_path']) && file_exists($GLOBALS['osmose_ads_template_path'])) {
                return $GLOBALS['osmose_ads_template_path'];
            }
        }
        
        // S'assurer que le post est chargé
        if (!$post && isset($wp_query->post)) {
            $post = $wp_query->post;
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
        } elseif (isset($post) && $post && isset($post->ID)) {
            $post_id = $post->ID;
            
            if ($post->post_type === 'ad') {
                $is_ad = true;
            } elseif ($post->post_type === 'post' && $post_id > 0) {
                $is_generated_article = (get_post_meta($post_id, 'article_auto_generated', true) === '1');
            }
        }
        
        if ($is_ad || $is_generated_article) {
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
     * Hook spécifique pour les single posts (plus fiable que template_include)
     */
    public function single_template_loader($template, $type, $templates) {
        global $post, $wp_query;
        
        // Ne rien faire en admin
        if (is_admin()) {
            return $template;
        }
        
        // S'assurer que le post est chargé
        if (!$post && isset($wp_query->post)) {
            $post = $wp_query->post;
        }
        
        if (!$post || !isset($post->ID)) {
            return $template;
        }
        
        // Vérifier si c'est un post de type 'ad'
        if ($post->post_type === 'ad') {
            // Vérifier si un template single-ad.php existe dans le thème
            $theme_template = locate_template(array('single-ad.php'));
            if ($theme_template) {
                return $theme_template;
            }
            
            // Utiliser le template du plugin
            $plugin_template = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/single-ad.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Vérifier si c'est un article généré (post avec meta article_auto_generated)
        if ($post->post_type === 'post' && $post->ID > 0) {
            $is_generated_article = get_post_meta($post->ID, 'article_auto_generated', true);
            
            // Debug: logger pour vérifier
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Osmose ADS: Checking article - ID: ' . $post->ID . ', meta: ' . $is_generated_article);
            }
            
            if ($is_generated_article === '1' || $is_generated_article === 1) {
                // Vérifier si un template single-ad.php existe dans le thème
                $theme_template = locate_template(array('single-ad.php'));
                if ($theme_template) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Osmose ADS: Using theme template: ' . $theme_template);
                    }
                    return $theme_template;
                }
                
                // Utiliser le template du plugin
                $plugin_template = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/single-ad.php';
                if (file_exists($plugin_template)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Osmose ADS: Using plugin template: ' . $plugin_template);
                    }
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }

    /**
     * Forcer le chargement du template pour les articles générés (approche agressive)
     * Cette fonction vérifie très tôt et force WordPress à utiliser le bon template
     */
    public function force_template_redirect() {
        global $post, $wp_query;
        
        // Ne rien faire en admin
        if (is_admin()) {
            return;
        }
        
        // Vérifier si on est sur un single post
        if (!is_single()) {
            return;
        }
        
        // S'assurer que le post est chargé
        if (!$post && isset($wp_query->post)) {
            $post = $wp_query->post;
        }
        
        if (!$post || !isset($post->ID)) {
            return;
        }
        
        $should_use_ad_template = false;
        
        // Vérifier si c'est un post de type 'ad'
        if ($post->post_type === 'ad') {
            $should_use_ad_template = true;
        }
        
        // Vérifier si c'est un article généré
        if ($post->post_type === 'post' && $post->ID > 0) {
            $is_generated_article = get_post_meta($post->ID, 'article_auto_generated', true);
            if ($is_generated_article === '1' || $is_generated_article === 1) {
                $should_use_ad_template = true;
            }
        }
        
        // Si on doit utiliser le template des annonces, stocker l'info pour template_include
        if ($should_use_ad_template) {
            // Stocker dans une variable globale pour que template_include le récupère
            $GLOBALS['osmose_ads_force_template'] = true;
            
            // Vérifier si un template single-ad.php existe dans le thème
            $theme_template = locate_template(array('single-ad.php'));
            if ($theme_template && file_exists($theme_template)) {
                $GLOBALS['osmose_ads_template_path'] = $theme_template;
            } else {
                // Utiliser le template du plugin
                $plugin_template = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/single-ad.php';
                if (file_exists($plugin_template)) {
                    $GLOBALS['osmose_ads_template_path'] = $plugin_template;
                }
            }
        }
    }
    
    /**
     * Intercepter les requêtes d'annonces avant le chargement du template
     */
    public function intercept_ad_requests() {
        // Cette fonction n'est plus nécessaire avec la nouvelle structure
        // mais on la garde pour compatibilité
    }
}
