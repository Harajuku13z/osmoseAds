<?php
/**
 * Classe Public
 */
class Osmose_Ads_Public {

    public function enqueue_styles() {
        // Charger uniquement sur les pages d'annonces et articles générés
        global $wp_query, $post;
        
        $is_ad_page = false;
        
        // Vérifier si c'est une annonce
        if (is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
            (isset($post) && $post->post_type === 'ad')) {
            $is_ad_page = true;
        }
        
        // Vérifier si c'est un article généré (post avec meta article_auto_generated)
        if (!$is_ad_page && isset($post) && $post->post_type === 'post' && isset($post->ID)) {
            $is_generated_article = get_post_meta($post->ID, 'article_auto_generated', true);
            if ($is_generated_article === '1' || $is_generated_article === 1) {
                $is_ad_page = true;
            }
        }
        
        // Vérifier aussi via queried_object
        if (!$is_ad_page) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'post' && isset($queried_object->ID)) {
                $is_generated_article = get_post_meta($queried_object->ID, 'article_auto_generated', true);
                if ($is_generated_article === '1' || $is_generated_article === 1) {
                    $is_ad_page = true;
                }
            }
        }
        
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
        
        $is_ad_page = false;
        
        // Vérifier si c'est une annonce
        if (is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
            (isset($post) && $post->post_type === 'ad')) {
            $is_ad_page = true;
        }
        
        // Vérifier si c'est un article généré (post avec meta article_auto_generated)
        if (!$is_ad_page && isset($post) && $post->post_type === 'post' && isset($post->ID)) {
            $is_generated_article = get_post_meta($post->ID, 'article_auto_generated', true);
            if ($is_generated_article === '1' || $is_generated_article === 1) {
                $is_ad_page = true;
            }
        }
        
        // Vérifier aussi via queried_object
        if (!$is_ad_page) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'post' && isset($queried_object->ID)) {
                $is_generated_article = get_post_meta($queried_object->ID, 'article_auto_generated', true);
                if ($is_generated_article === '1' || $is_generated_article === 1) {
                    $is_ad_page = true;
                }
            }
        }
        
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
        // Nettoyer l'URL : enlever le slash final et les query strings
        $request_uri = rtrim(parse_url($request_uri, PHP_URL_PATH), '/');
        $is_sitemap = false;
        $sitemap_index = false;
        $sitemap_number = null;
        
        // Vérifier si l'URL contient sitemap-ads.xml (index ou numéroté)
        // Gérer aussi le cas avec un slash final
        if (preg_match('/sitemap-ads(?:-(\d+))?\.xml$/', $request_uri, $matches)) {
            $is_sitemap = true;
            if (empty($matches[1])) {
                // C'est le sitemap index
                $sitemap_index = true;
            } else {
                // C'est un sitemap numéroté
                $sitemap_number = intval($matches[1]);
            }
        }
        
        // Vérifier aussi via query vars (si les rewrite rules sont flushées)
        global $wp_query;
        if (isset($wp_query) && isset($wp_query->query_vars['osmose_ads_sitemap'])) {
            $is_sitemap = true;
            $sitemap_index = true; // Par défaut, c'est l'index
        }
        
        if (isset($wp_query) && isset($wp_query->query_vars['osmose_ads_sitemap_num'])) {
            $is_sitemap = true;
            $sitemap_number = intval($wp_query->query_vars['osmose_ads_sitemap_num']);
        }
        
        if (!$is_sitemap) {
            return;
        }

        // Vérifier que les headers n'ont pas déjà été envoyés
        if (headers_sent()) {
            return;
        }

        // Augmenter temporairement la limite de mémoire pour la génération du sitemap
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        // Nombre maximum de liens par sitemap
        $max_links_per_sitemap = 4000;

        // En-têtes XML
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');
        
        // Si c'est le sitemap index, générer l'index (sans charger toutes les annonces)
        if ($sitemap_index) {
            $this->generate_sitemap_index_optimized($max_links_per_sitemap);
            exit;
        }
        
        // Sinon, générer le sitemap numéroté (seulement les annonces nécessaires)
        if ($sitemap_number !== null) {
            $this->generate_sitemap_file_optimized($sitemap_number, $max_links_per_sitemap);
            exit;
        }
        
        // Si on arrive ici sans avoir généré de sitemap, générer l'index par défaut
        $this->generate_sitemap_index_optimized($max_links_per_sitemap);
        exit;
    }
    
    /**
     * Générer le sitemap index qui liste tous les sitemaps
     */
    private function generate_sitemap_index($ads, $max_links_per_sitemap) {
        $total_ads = count($ads);
        $num_sitemaps = $total_ads > 0 ? ceil($total_ads / $max_links_per_sitemap) : 0;
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Toujours ajouter le sitemap de la page d'accueil
        $home_sitemap_url = home_url('/sitemap-ads-0.xml');
        $lastmod = get_lastpostmodified('GMT');
        $lastmod_date = $lastmod ? date('c', strtotime($lastmod)) : date('c');
        echo "  <sitemap>\n";
        echo "    <loc>" . esc_url($home_sitemap_url) . "</loc>\n";
        echo "    <lastmod>" . esc_html($lastmod_date) . "</lastmod>\n";
        echo "  </sitemap>\n";
        
        // Ajouter les sitemaps des annonces (seulement s'il y en a)
        if ($num_sitemaps > 0) {
            for ($i = 1; $i <= $num_sitemaps; $i++) {
                $sitemap_url = home_url('/sitemap-ads-' . $i . '.xml');
                $start_index = ($i - 1) * $max_links_per_sitemap;
                $end_index = min($start_index + $max_links_per_sitemap, $total_ads);
                $count = $end_index - $start_index;
                
                // Calculer la date de modification la plus récente pour ce sitemap
                $lastmod = date('c');
                if ($count > 0 && isset($ads[$start_index])) {
                    $lastmod = $ads[$start_index]->post_modified_gmt;
                    $lastmod = $lastmod ? date('c', strtotime($lastmod)) : date('c');
                }
                
                echo "  <sitemap>\n";
                echo "    <loc>" . esc_url($sitemap_url) . "</loc>\n";
                echo "    <lastmod>" . esc_html($lastmod) . "</lastmod>\n";
                echo "  </sitemap>\n";
            }
        }
        
        echo '</sitemapindex>';
    }
    
    /**
     * Générer un fichier sitemap spécifique
     */
    private function generate_sitemap_file($ads, $sitemap_number, $max_links_per_sitemap) {
        $total_ads = count($ads);
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
        echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        
        // Sitemap 0 = page d'accueil uniquement
        if ($sitemap_number === 0) {
            $home_url = home_url('/');
            $lastmod = get_lastpostmodified('GMT');
            $lastmod_date = $lastmod ? date('c', strtotime($lastmod)) : date('c');
            echo "  <url>\n";
            echo "    <loc>" . esc_url($home_url) . "</loc>\n";
            echo "    <lastmod>" . esc_html($lastmod_date) . "</lastmod>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>1.0</priority>\n";
            echo "  </url>\n";
        } else {
            // Sitemaps numérotés = annonces
            $start_index = ($sitemap_number - 1) * $max_links_per_sitemap;
            $end_index = min($start_index + $max_links_per_sitemap, $total_ads);
            
            for ($i = $start_index; $i < $end_index; $i++) {
                if (!isset($ads[$i])) {
                    continue;
                }
                
                $ad = $ads[$i];
                if (!is_object($ad) || !isset($ad->ID)) {
                    continue;
                }
                
                $url = get_permalink($ad->ID);
                if (!$url) {
                    continue;
                }
                
                $modified = isset($ad->post_modified_gmt) ? $ad->post_modified_gmt : '';
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
        }
        
        echo '</urlset>';
    }
    
    /**
     * Générer le sitemap index optimisé (sans charger toutes les annonces)
     */
    private function generate_sitemap_index_optimized($max_links_per_sitemap) {
        global $wpdb;
        
        // Compter le nombre total d'annonces publiées sans les charger
        $total_ads = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
            'ad'
        ));
        
        $num_sitemaps = $total_ads > 0 ? ceil($total_ads / $max_links_per_sitemap) : 0;
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Ajouter les sitemaps des annonces (la page d'accueil sera incluse dans le premier sitemap)
        // Si pas d'annonces, créer quand même un sitemap avec la page d'accueil
        if ($num_sitemaps > 0) {
            for ($i = 1; $i <= $num_sitemaps; $i++) {
                $sitemap_url = home_url('/sitemap-ads-' . $i . '.xml');
                
                // Récupérer la date de modification la plus récente pour ce batch (sans charger tous les posts)
                $start_index = ($i - 1) * $max_links_per_sitemap;
                $lastmod_query = $wpdb->prepare(
                    "SELECT post_modified_gmt FROM {$wpdb->posts} 
                     WHERE post_type = %s AND post_status = 'publish' 
                     ORDER BY post_modified_gmt DESC 
                     LIMIT 1 OFFSET %d",
                    'ad',
                    $start_index
                );
                $lastmod_gmt = $wpdb->get_var($lastmod_query);
                $lastmod = $lastmod_gmt ? date('c', strtotime($lastmod_gmt)) : date('c');
                
                echo "  <sitemap>\n";
                echo "    <loc>" . esc_url($sitemap_url) . "</loc>\n";
                echo "    <lastmod>" . esc_html($lastmod) . "</lastmod>\n";
                echo "  </sitemap>\n";
            }
        } else {
            // Si pas d'annonces, créer un sitemap avec seulement la page d'accueil
            $sitemap_url = home_url('/sitemap-ads-1.xml');
            $lastmod = get_lastpostmodified('GMT');
            $lastmod_date = $lastmod ? date('c', strtotime($lastmod)) : date('c');
            echo "  <sitemap>\n";
            echo "    <loc>" . esc_url($sitemap_url) . "</loc>\n";
            echo "    <lastmod>" . esc_html($lastmod_date) . "</lastmod>\n";
            echo "  </sitemap>\n";
        }
        
        echo '</sitemapindex>';
    }
    
    /**
     * Générer un fichier sitemap spécifique optimisé (seulement les annonces nécessaires)
     */
    private function generate_sitemap_file_optimized($sitemap_number, $max_links_per_sitemap) {
        global $wpdb;
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
        echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        
        // Sitemap 0 n'existe plus - rediriger vers sitemap 1
        if ($sitemap_number === 0) {
            // Rediriger vers le sitemap 1
            wp_redirect(home_url('/sitemap-ads-1.xml'), 301);
            exit;
        }
        
        // Toujours inclure la page d'accueil dans le premier sitemap
        if ($sitemap_number === 1) {
            $home_url = home_url('/');
            $lastmod = get_lastpostmodified('GMT');
            $lastmod_date = $lastmod ? date('c', strtotime($lastmod)) : date('c');
            echo "  <url>\n";
            echo "    <loc>" . esc_url($home_url) . "</loc>\n";
            echo "    <lastmod>" . esc_html($lastmod_date) . "</lastmod>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>1.0</priority>\n";
            echo "  </url>\n";
        }
        
        // Sitemaps numérotés = annonces
        // Récupérer uniquement les annonces nécessaires pour ce sitemap
        $start_index = ($sitemap_number - 1) * $max_links_per_sitemap;
        
        // Pour le sitemap 1, on a déjà inclus la page d'accueil, donc on réduit d'1 le nombre d'annonces
        if ($sitemap_number === 1) {
            $limit = $max_links_per_sitemap - 1;
        } else {
            $limit = $max_links_per_sitemap;
        }
        
        if ($limit > 0) {
            
            $ads = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_name, post_modified_gmt 
                 FROM {$wpdb->posts} 
                 WHERE post_type = %s AND post_status = 'publish' 
                 ORDER BY post_modified_gmt DESC 
                 LIMIT %d OFFSET %d",
                'ad',
                $limit,
                $start_index
            ));
            
            if ($ads && is_array($ads)) {
                foreach ($ads as $ad) {
                    if (!isset($ad->ID) || !isset($ad->post_name)) {
                        continue;
                    }
                    
                    // Utiliser get_permalink mais seulement pour ce post (beaucoup plus léger que de charger tous les posts)
                    // On crée un objet post minimal pour get_permalink
                    $post_obj = new stdClass();
                    $post_obj->ID = $ad->ID;
                    $post_obj->post_name = $ad->post_name;
                    $post_obj->post_type = 'ad';
                    $post_obj->post_status = 'publish';
                    
                    // Utiliser get_permalink avec l'ID directement (plus efficace)
                    $url = get_permalink($ad->ID);
                    if (!$url) {
                        // Fallback : construire l'URL manuellement si get_permalink échoue
                        $url = home_url('/' . $ad->post_name . '/');
                    }
                    
                    $modified = isset($ad->post_modified_gmt) ? $ad->post_modified_gmt : '';
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
            }
        }
        
        echo '</urlset>';
    }
    
    /**
     * Enregistrer le shortcode pour le simulateur
     */
    public function register_simulator_shortcode() {
        add_shortcode('osmose_simulator', array($this, 'render_simulator'));
    }
    
    /**
     * Rendre le simulateur
     */
    public function render_simulator($atts) {
        // Vérifier que les constantes sont définies
        if (!defined('OSMOSE_ADS_PLUGIN_URL') || !defined('OSMOSE_ADS_PLUGIN_DIR') || !defined('OSMOSE_ADS_VERSION')) {
            return '<p>' . __('Erreur : Le plugin Osmose ADS n\'est pas correctement initialisé.', 'osmose-ads') . '</p>';
        }
        
        // Charger les styles et scripts
        wp_enqueue_style(
            'osmose-simulator',
            OSMOSE_ADS_PLUGIN_URL . 'public/css/osmose-simulator.css',
            array(),
            OSMOSE_ADS_VERSION
        );
        
        // Utiliser la version 2 du simulateur si le template v2 existe
        $template_path = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/simulator-v2.php';
        $script_file = file_exists($template_path) ? 'osmose-simulator-v2.js' : 'osmose-simulator.js';
        
        wp_enqueue_script(
            'osmose-simulator',
            OSMOSE_ADS_PLUGIN_URL . 'public/js/' . $script_file,
            array('jquery'),
            OSMOSE_ADS_VERSION,
            true
        );
        
        // Localiser le script
        wp_localize_script('osmose-simulator', 'osmoseAdsSimulator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osmose_ads_quote_request'),
        ));
        
        // Charger le template (utiliser v2 si disponible)
        $template_path = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/simulator-v2.php';
        if (!file_exists($template_path)) {
            $template_path = OSMOSE_ADS_PLUGIN_DIR . 'public/templates/simulator.php';
        }
        if (!file_exists($template_path)) {
            return '<p>' . __('Erreur : Le template du simulateur est introuvable.', 'osmose-ads') . '</p>';
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Handler AJAX pour les demandes de devis
     */
    public function handle_quote_request() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'osmose_ads_quote_request')) {
            wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
            return;
        }
        
        // Récupérer les données
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        
        if (empty($data)) {
            wp_send_json_error(array('message' => __('Données manquantes', 'osmose-ads')));
            return;
        }
        
        // Valider les champs requis
        $required_fields = array('first_name', 'last_name', 'email', 'phone', 'property_type');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                wp_send_json_error(array('message' => sprintf(__('Le champ %s est requis', 'osmose-ads'), $field)));
                return;
            }
        }
        
        // Valider l'email
        if (!is_email($data['email'])) {
            wp_send_json_error(array('message' => __('Adresse email invalide', 'osmose-ads')));
            return;
        }
        
        // Préparer les données pour la base
        global $wpdb;
        $table_name = $wpdb->prefix . 'osmose_ads_quote_requests';
        
        // Vérifier que la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Créer la table si elle n'existe pas
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                property_type varchar(50),
                work_type text,
                first_name varchar(100),
                last_name varchar(100),
                email varchar(255),
                phone varchar(50),
                address varchar(500),
                city varchar(255),
                postal_code varchar(20),
                surface varchar(50),
                project_type varchar(100),
                project_details text,
                message text,
                status varchar(50) DEFAULT 'pending',
                user_ip varchar(45),
                user_agent text,
                page_url varchar(500),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_created_at (created_at),
                KEY idx_email (email)
            ) $charset_collate;";
            
            // Vérifier si les nouvelles colonnes existent, sinon les ajouter
            $columns = $wpdb->get_col("DESC $table_name");
            if (!in_array('surface', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN surface varchar(50) AFTER postal_code");
            }
            if (!in_array('project_type', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN project_type varchar(100) AFTER surface");
            }
            if (!in_array('project_details', $columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN project_details text AFTER project_type");
            }
            dbDelta($sql);
        }
        
        // Préparer les données d'insertion
        $insert_data = array(
            'property_type' => sanitize_text_field($data['property_type'] ?? ''),
            'work_type' => isset($data['work_type']) && is_array($data['work_type']) ? implode(', ', array_map('sanitize_text_field', $data['work_type'])) : '',
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'address' => isset($data['address']) ? sanitize_text_field($data['address']) : '',
            'city' => isset($data['city']) ? sanitize_text_field($data['city']) : '',
            'postal_code' => isset($data['postal_code']) ? sanitize_text_field($data['postal_code']) : '',
            'surface' => isset($data['surface']) ? sanitize_text_field($data['surface']) : '',
            'project_type' => isset($data['project_type']) ? sanitize_text_field($data['project_type']) : '',
            'project_details' => isset($data['project_details']) && is_array($data['project_details']) ? implode(', ', array_map('sanitize_text_field', $data['project_details'])) : (isset($data['project_details']) ? sanitize_text_field($data['project_details']) : ''),
            'message' => isset($data['message']) ? sanitize_text_field($data['message']) : '',
            'status' => 'pending',
            'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'page_url' => esc_url_raw($_SERVER['HTTP_REFERER'] ?? ''),
        );
        
        // Insérer dans la base de données
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement', 'osmose-ads')));
            return;
        }
        
        // Envoyer les emails HTML
        $email_notification = get_option('osmose_ads_simulator_email_notification', 1);
        if ($email_notification) {
            // Email à l'admin
            $email_recipient = get_option('osmose_ads_simulator_email_recipient', get_option('admin_email'));
            if (is_email($email_recipient)) {
                $subject = sprintf(__('Nouvelle demande de devis - %s', 'osmose-ads'), $insert_data['first_name'] . ' ' . $insert_data['last_name']);
                $html_message = Osmose_Ads_Email::generate_admin_notification_email($insert_data);
                
                // Headers pour email HTML
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                );
                
                wp_mail($email_recipient, $subject, $html_message, $headers);
            }
        }
        
        // Email de confirmation à l'utilisateur
        $html_confirmation = Osmose_Ads_Email::generate_user_confirmation_email($insert_data);
        $confirmation_subject = __('Confirmation de votre demande de devis', 'osmose-ads');
        $confirmation_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        wp_mail($insert_data['email'], $confirmation_subject, $html_confirmation, $confirmation_headers);
        
        wp_send_json_success(array('message' => __('Demande envoyée avec succès !', 'osmose-ads')));
    }
    
    /**
     * Ajouter un bouton flottant pour ouvrir le simulateur
     */
    public function add_simulator_floating_button() {
        // Récupérer l'URL de la page simulateur
        $simulator_page_id = get_option('osmose_ads_simulator_page_id');
        if ($simulator_page_id) {
            $simulator_url = get_permalink($simulator_page_id);
            $is_simulator_page = is_page($simulator_page_id);
        } else {
            $simulator_url = home_url('/simulateur-devis/');
            $is_simulator_page = is_page('simulateur-devis');
        }
        
        // Ne pas afficher sur la page du simulateur elle-même
        if ($is_simulator_page || get_query_var('osmose_simulator')) {
            return;
        }
        
        ?>
        <!-- Bouton flottant simulateur -->
        <div id="osmose-simulator-floating-btn" class="osmose-simulator-floating-btn">
            <a href="<?php echo esc_url($simulator_url); ?>" class="osmose-open-simulator-btn">
                <span class="simulator-icon">📋</span>
                <span class="simulator-text"><?php _e('Devis Gratuit', 'osmose-ads'); ?></span>
            </a>
        </div>
        
        <!-- Modal du simulateur -->
        <div id="osmose-simulator-modal" class="osmose-simulator-modal" style="display: none;">
            <div class="osmose-simulator-modal-overlay"></div>
            <div class="osmose-simulator-modal-content">
                <button class="osmose-simulator-modal-close" aria-label="<?php esc_attr_e('Fermer', 'osmose-ads'); ?>">
                    <span>&times;</span>
                </button>
                <div class="osmose-simulator-modal-body">
                    <?php echo do_shortcode('[osmose_simulator]'); ?>
                </div>
            </div>
        </div>
        
        <style>
        /* Bouton flottant */
        .osmose-simulator-floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9998;
            animation: pulse 2s infinite;
        }
        
        .osmose-simulator-floating-btn a {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
        }
        
        .osmose-simulator-floating-btn a:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .simulator-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .simulator-text {
            white-space: nowrap;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Modal */
        .osmose-simulator-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .osmose-simulator-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .osmose-simulator-modal-content {
            position: relative;
            background: white;
            border-radius: 15px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .osmose-simulator-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f0f0f0;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #333;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .osmose-simulator-modal-close:hover {
            background: #e0e0e0;
            transform: rotate(90deg);
        }
        
        .osmose-simulator-modal-body {
            padding: 40px;
        }
        
        /* Style pour le lien dans le menu */
        .osmose-simulator-menu-item a {
            color: inherit;
            text-decoration: none;
        }
        
        .osmose-simulator-menu-item a:hover {
            opacity: 0.8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .osmose-simulator-floating-btn {
                bottom: 20px;
                right: 20px;
            }
            
            .osmose-simulator-floating-btn a {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .simulator-text {
                display: none;
            }
            
            .simulator-icon {
                margin-right: 0;
                font-size: 28px;
            }
            
            .osmose-simulator-modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .osmose-simulator-modal-body {
                padding: 20px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Le bouton flottant et le lien du menu redirigent directement vers la page
            // La modal est disponible si nécessaire pour une ouverture en popup
            $('.osmose-open-simulator-btn').on('click', function(e) {
                // Laisser le lien normal fonctionner (redirection vers la page)
                // Si tu veux ouvrir en modal, décommente les lignes suivantes :
                // e.preventDefault();
                // $('#osmose-simulator-modal').fadeIn(300);
                // $('body').css('overflow', 'hidden');
            });
            
            // Fermer la modal (si utilisée)
            $('.osmose-simulator-modal-close, .osmose-simulator-modal-overlay').on('click', function() {
                $('#osmose-simulator-modal').fadeOut(300);
                $('body').css('overflow', '');
            });
            
            // Fermer avec Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#osmose-simulator-modal').is(':visible')) {
                    $('#osmose-simulator-modal').fadeOut(300);
                    $('body').css('overflow', '');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Créer automatiquement la page simulateur si elle n'existe pas
     */
    public function create_simulator_page() {
        // Récupérer le slug depuis les options ou utiliser la valeur par défaut
        $page_slug = get_option('osmose_ads_simulator_page_slug', 'simulateur-devis');
        $page_title = get_option('osmose_ads_simulator_title', __('Simulateur de Devis', 'osmose-ads'));
        $page_id = get_option('osmose_ads_simulator_page_id');
        
        // Vérifier si la page existe déjà
        $page = null;
        if ($page_id) {
            $page = get_post($page_id);
        }
        
        if (!$page || $page->post_status !== 'publish') {
            // Chercher par slug
            $page = get_page_by_path($page_slug);
            
            if (!$page) {
                // Créer la page
                $page_data = array(
                    'post_title'    => $page_title,
                    'post_content'  => '[osmose_simulator]',
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $page_slug,
                    'post_author'   => get_current_user_id() ?: 1,
                );
                
                $page_id = wp_insert_post($page_data);
                
                if ($page_id && !is_wp_error($page_id)) {
                    // Sauvegarder l'ID de la page dans les options
                    update_option('osmose_ads_simulator_page_id', $page_id);
                }
            } else {
                // Mettre à jour l'ID si la page existe déjà
                update_option('osmose_ads_simulator_page_id', $page->ID);
            }
        }
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
if (!function_exists('osmose_ads_is_bot')) {
    /**
     * Détecter si la requête provient d'un bot
     * 
     * @return bool True si c'est un bot, false sinon
     */
    function osmose_ads_is_bot() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Ne pas bloquer si pas de user agent (peut être un vrai utilisateur)
        if (empty($user_agent)) {
            return false;
        }
        
        // Liste des bots connus (patterns spécifiques uniquement)
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
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        // Vérifier les patterns génériques mais seulement s'ils sont au début ou suivis d'un slash
        $generic_bot_patterns = array(
            '/bot', 'bot/', 'crawler/', 'spider/', 'scraper/',
        );
        
        foreach ($generic_bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        // Vérifier les outils de ligne de commande (curl, wget) mais seulement s'ils sont seuls
        if (preg_match('/^(curl|wget|libwww|lwp-trivial|perl|ruby|php)\//i', $user_agent)) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('osmose_ads_track_visit')) {
    function osmose_ads_track_visit($ad_id, $ad_slug, $page_url, $template_id = null, $city = null) {
        // Ne pas tracker les visites des bots
        if (osmose_ads_is_bot()) {
            return;
        }
        
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

