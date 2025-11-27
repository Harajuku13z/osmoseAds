<?php
/**
 * Classe d'activation du plugin
 */
class Osmose_Ads_Activator {

    public static function activate() {
        // Créer les tables si nécessaire (optionnel, on peut utiliser CPT)
        self::create_tables();
        
        // Enregistrer les post types
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-post-types.php';
        $post_types = new Osmose_Ads_Post_Types();
        $post_types->register_post_types();
        
        // Enregistrer les rewrite rules pour le sitemap
        add_rewrite_rule('^sitemap-ads\.xml$', 'index.php?osmose_ads_sitemap=1', 'top');
        add_rewrite_tag('%osmose_ads_sitemap%', '([0-9]+)');
        
        // Flush rewrite rules pour appliquer les nouvelles règles IMMÉDIATEMENT
        flush_rewrite_rules(false);
        
        // Marquer qu'il faut flush les règles au prochain chargement aussi
        update_option('osmose_ads_flush_rewrite_rules', true);
        
        // Créer les options par défaut
        self::set_default_options();
        
        // Créer la catégorie "Annonces"
        self::create_annonces_category();
        
        // Marquer que c'est la première activation
        set_transient('osmose_ads_activation_redirect', true, 30);
    }
    
    /**
     * Créer la catégorie "Annonces" lors de l'activation
     */
    private static function create_annonces_category() {
        // Vérifier si la catégorie existe déjà
        $category_exists = term_exists('Annonces', 'category');
        
        if (!$category_exists) {
            // Créer la catégorie "Annonces"
            $category_id = wp_create_category('Annonces');
            
            if (!is_wp_error($category_id)) {
                // Sauvegarder l'ID de la catégorie dans les options
                update_option('osmose_ads_category_id', $category_id);
            }
        } else {
            // Si elle existe déjà, sauvegarder son ID
            $category_id = is_array($category_exists) ? $category_exists['term_id'] : $category_exists;
            update_option('osmose_ads_category_id', $category_id);
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des templates (optionnel, on utilise CPT)
        $table_templates = $wpdb->prefix . 'osmose_ad_templates';
        
        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            service_name varchar(255) NOT NULL,
            service_slug varchar(255) NOT NULL,
            content_html longtext NOT NULL,
            short_description text,
            long_description text,
            icon varchar(50) DEFAULT 'fas fa-tools',
            featured_image varchar(255),
            meta_title varchar(160),
            meta_description text,
            meta_keywords text,
            og_title varchar(160),
            og_description text,
            twitter_title varchar(160),
            twitter_description text,
            ai_prompt_used longtext,
            ai_response_data longtext,
            is_active tinyint(1) DEFAULT 1,
            usage_count int(11) DEFAULT 0,
            created_at datetime,
            updated_at datetime,
            PRIMARY KEY (id),
            KEY idx_service_slug_active (service_slug, is_active),
            KEY idx_service_name (service_name)
        ) $charset_collate;";
        
        // Table pour tracker les appels téléphoniques
        $table_calls = $wpdb->prefix . 'osmose_ads_call_tracking';
        
        $sql_calls = "CREATE TABLE IF NOT EXISTS $table_calls (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED,
            ad_slug varchar(255),
            page_url varchar(500),
            phone_number varchar(50),
            user_ip varchar(45),
            user_agent text,
            referrer varchar(500),
            source varchar(50),
            call_time datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ad_id (ad_id),
            KEY idx_created_at (created_at),
            KEY idx_call_time (call_time),
            KEY idx_page_url (page_url(255))
        ) $charset_collate;";
        
        // Table pour tracker les visites des annonces
        $table_visits = $wpdb->prefix . 'osmose_ads_visits';
        
        $sql_visits = "CREATE TABLE IF NOT EXISTS $table_visits (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_calls);
        dbDelta($sql_visits);
        
        // Vérifier que les tables ont bien été créées
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_calls'") == $table_calls) {
            error_log('Osmose ADS: Call tracking table created successfully during activation');
        } else {
            error_log('Osmose ADS: WARNING - Failed to create call tracking table during activation');
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_visits'") == $table_visits) {
            error_log('Osmose ADS: Visits tracking table created successfully during activation');
        } else {
            error_log('Osmose ADS: WARNING - Failed to create visits tracking table during activation');
        }
    }

    private static function set_default_options() {
        // Options par défaut
        if (!get_option('osmose_ads_ai_personalization')) {
            update_option('osmose_ads_ai_personalization', false);
        }
        if (!get_option('osmose_ads_company_phone')) {
            update_option('osmose_ads_company_phone', '');
        }
        if (!get_option('osmose_ads_company_phone_raw')) {
            update_option('osmose_ads_company_phone_raw', '');
        }
        if (!get_option('osmose_ads_company_email')) {
            update_option('osmose_ads_company_email', '');
        }
        if (!get_option('osmose_ads_devis_url')) {
            update_option('osmose_ads_devis_url', '');
        }
        if (!get_option('osmose_ads_openai_api_key')) {
            update_option('osmose_ads_openai_api_key', '');
        }
        if (!get_option('osmose_ads_ai_provider')) {
            update_option('osmose_ads_ai_provider', 'openai'); // openai ou groq
        }
    }
}



