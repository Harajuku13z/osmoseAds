<?php
/**
 * Classe Public
 */
class Osmose_Ads_Public {

    public function enqueue_styles() {
        // Charger uniquement sur les pages d'annonces
        global $wp_query, $post;
        $is_ad_page = is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
                      (isset($post) && $post->post_type === 'ad');
        
        if ($is_ad_page) {
            wp_enqueue_style(
                'osmose-ads-public',
                OSMOSE_ADS_PLUGIN_URL . 'public/css/osmose-ads-public.css',
                array(),
                OSMOSE_ADS_VERSION
            );
            
            // Ajouter Bootstrap Icons
            wp_enqueue_style(
                'bootstrap-icons',
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
                array(),
                '1.11.0'
            );
        }
    }

    public function enqueue_scripts() {
        // Charger sur toutes les pages où des annonces peuvent être affichées
        global $wp_query, $post;
        $is_ad_page = is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
                      (isset($post) && $post->post_type === 'ad');
        
        if ($is_ad_page) {
            wp_enqueue_script(
                'osmose-ads-public',
                OSMOSE_ADS_PLUGIN_URL . 'public/js/osmose-ads-public.js',
                array('jquery'),
                OSMOSE_ADS_VERSION,
                true
            );
            
            // Localiser le script avec les variables de tracking
            wp_localize_script('osmose-ads-public', 'osmoseAdsTracking', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('osmose_ads_track_call'),
            ));
        }
    }
    
    /**
     * Ajouter les variables de tracking dans le footer pour toutes les pages avec des annonces
     */
    public function add_tracking_variables() {
        global $post;
        
        // Vérifier si on est sur une page avec une annonce
        if (isset($post) && $post->post_type === 'ad') {
            $ad_id = $post->ID;
            $ad_slug = $post->post_name;
            $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $phone_raw = get_option('osmose_ads_company_phone_raw', '');
            
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('Osmose ADS: Adding tracking attributes to tel: links');
                console.log('Osmose ADS: Ad ID:', <?php echo intval($ad_id); ?>, 'Slug:', '<?php echo esc_js($ad_slug); ?>');
                
                // Ajouter les attributs data aux liens tel: existants si nécessaire
                $('a[href^="tel:"]').each(function() {
                    var $link = $(this);
                    console.log('Osmose ADS: Processing tel link:', $link.attr('href'));
                    
                    if (!$link.hasClass('osmose-track-call')) {
                        $link.addClass('osmose-track-call');
                    }
                    
                    // Toujours mettre à jour les data attributes
                    $link.attr('data-ad-id', '<?php echo esc_js($ad_id); ?>');
                    $link.attr('data-ad-slug', '<?php echo esc_js($ad_slug); ?>');
                    $link.attr('data-page-url', '<?php echo esc_js($page_url); ?>');
                    $link.attr('data-phone', '<?php echo esc_js($phone_raw); ?>');
                    
                    console.log('Osmose ADS: Tel link processed with tracking class');
                });
                
                console.log('Osmose ADS: Total tel: links found:', $('a[href^="tel:"]').length);
                console.log('Osmose ADS: Total tracking links:', $('a.osmose-track-call').length);
            });
            </script>
            <?php
        }
    }

    /**
     * Générer et afficher le sitemap XML
     */
    public function generate_sitemap() {
        // Vérifier l'URL directement (plus fiable que les query vars)
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $is_sitemap = false;
        
        // Vérifier si l'URL contient sitemap-ads.xml
        if (strpos($request_uri, 'sitemap-ads.xml') !== false) {
            $is_sitemap = true;
        }
        
        // Vérifier aussi via query vars (si les rewrite rules sont flushées)
        global $wp_query;
        if (isset($wp_query->query_vars['osmose_ads_sitemap'])) {
            $is_sitemap = true;
        }
        
        if (!$is_sitemap) {
            return;
        }

        // Récupérer toutes les annonces publiées
        $ads = get_posts(array(
            'post_type' => 'ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
        ));

        // En-têtes XML
        header('Content-Type: application/xml; charset=utf-8');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
        echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

        // URL de la page d'accueil
        $home_url = home_url('/');
        $lastmod = get_lastpostmodified('GMT');
        echo "  <url>\n";
        echo "    <loc>" . esc_url($home_url) . "</loc>\n";
        echo "    <lastmod>" . esc_html($lastmod ? date('c', strtotime($lastmod)) : date('c')) . "</lastmod>\n";
        echo "    <changefreq>daily</changefreq>\n";
        echo "    <priority>1.0</priority>\n";
        echo "  </url>\n";

        // URLs des annonces
        foreach ($ads as $ad) {
            $url = get_permalink($ad->ID);
            $modified = $ad->post_modified_gmt;
            $modified_date = $modified ? date('c', strtotime($modified)) : date('c');
            
            // Déterminer la priorité (peut être ajustée selon vos besoins)
            $priority = '0.8';
            
            // Déterminer la fréquence de changement
            $changefreq = 'weekly';
            
            echo "  <url>\n";
            echo "    <loc>" . esc_url($url) . "</loc>\n";
            echo "    <lastmod>" . esc_html($modified_date) . "</lastmod>\n";
            echo "    <changefreq>" . esc_html($changefreq) . "</changefreq>\n";
            echo "    <priority>" . esc_html($priority) . "</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }
}

/**
 * Fonction helper pour tracker les visites des annonces
 * 
 * @param int $ad_id ID de l'annonce
 * @param string $ad_slug Slug de l'annonce
 * @param string $page_url URL de la page
 * @param int|null $template_id ID du template
 * @param object|null $city Objet City
 */
if (!function_exists('osmose_ads_track_visit')) {
    function osmose_ads_track_visit($ad_id, $ad_slug, $page_url, $template_id = null, $city = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'osmose_ads_visits';
        
        // Vérifier que la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Créer la table si elle n'existe pas
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ad_id bigint(20) UNSIGNED NOT NULL,
                ad_slug varchar(255),
                page_url varchar(500),
                user_ip varchar(45),
                user_agent text,
                referrer varchar(500),
                referrer_domain varchar(255),
                utm_source varchar(100),
                utm_medium varchar(100),
                utm_campaign varchar(100),
                device_type varchar(50),
                browser varchar(100),
                country varchar(100),
                city_name varchar(255),
                template_id bigint(20) UNSIGNED,
                visit_date date,
                visit_time datetime DEFAULT CURRENT_TIMESTAMP,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ad_id (ad_id),
                KEY idx_visit_date (visit_date),
                KEY idx_visit_time (visit_time),
                KEY idx_referrer_domain (referrer_domain),
                KEY idx_template_id (template_id),
                KEY idx_ad_visit_date (ad_id, visit_date)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Récupérer les informations du visiteur
        $user_ip = osmose_ads_get_user_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Extraire le domaine du referrer
        $referrer_domain = '';
        if (!empty($referrer)) {
            $parsed_url = parse_url($referrer);
            $referrer_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        }
        
        // Récupérer les paramètres UTM
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';
        $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '';
        $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '';
        
        // Détecter le type d'appareil et le navigateur
        $device_type = osmose_ads_detect_device_type($user_agent);
        $browser = osmose_ads_detect_browser($user_agent);
        
        // Récupérer le nom de la ville
        $city_name = '';
        if ($city && is_object($city)) {
            $city_name = method_exists($city, 'get_name') ? $city->get_name() : (isset($city->name) ? $city->name : '');
        }
        
        // Date de la visite
        $visit_date = current_time('Y-m-d');
        $visit_time = current_time('mysql');
        
        // Insérer la visite dans la base de données
        $wpdb->insert(
            $table_name,
            array(
                'ad_id' => $ad_id,
                'ad_slug' => $ad_slug,
                'page_url' => $page_url,
                'user_ip' => $user_ip,
                'user_agent' => $user_agent,
                'referrer' => $referrer,
                'referrer_domain' => $referrer_domain,
                'utm_source' => $utm_source,
                'utm_medium' => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'device_type' => $device_type,
                'browser' => $browser,
                'city_name' => $city_name,
                'template_id' => $template_id,
                'visit_date' => $visit_date,
                'visit_time' => $visit_time,
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            )
        );
    }
    
    /**
     * Récupérer l'IP du visiteur (anonymisée pour le RGPD)
     */
    function osmose_ads_get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        // Anonymiser l'IP (dernier octet)
                        $ip_parts = explode('.', $ip);
                        if (count($ip_parts) === 4) {
                            $ip_parts[3] = '0';
                            return implode('.', $ip_parts);
                        }
                        return $ip;
                    }
                }
            }
        }
        // Fallback
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) === 4) {
            $ip_parts[3] = '0';
            return implode('.', $ip_parts);
        }
        return $ip;
    }
    
    /**
     * Détecter le type d'appareil
     */
    function osmose_ads_detect_device_type($user_agent) {
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        $user_agent_lower = strtolower($user_agent);
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|opera mobi|skyfire|maemo|windows phone|palm|iemobile|symbian|symbianos|fennec/i', $user_agent_lower)) {
            return 'Mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $user_agent_lower)) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }
    
    /**
     * Détecter le navigateur
     */
    function osmose_ads_detect_browser($user_agent) {
        if (empty($user_agent)) {
            return 'Unknown';
        }
        
        $user_agent_lower = strtolower($user_agent);
        
        if (strpos($user_agent_lower, 'chrome') !== false && strpos($user_agent_lower, 'edg') === false) {
            return 'Chrome';
        } elseif (strpos($user_agent_lower, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent_lower, 'safari') !== false && strpos($user_agent_lower, 'chrome') === false) {
            return 'Safari';
        } elseif (strpos($user_agent_lower, 'edg') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent_lower, 'opera') !== false || strpos($user_agent_lower, 'opr') !== false) {
            return 'Opera';
        } else {
            return 'Other';
        }
    }
}



