<?php
if (!defined('ABSPATH')) {
    exit;
}

class OA_Install {

    const DB_VERSION = '2.0.0';

    public static function activate() {
        self::create_tables();
        update_option('oa_db_version', self::DB_VERSION);
        if (!wp_next_scheduled('oa_process_queue')) {
            wp_schedule_event(time() + 60, 'oa_minute', 'oa_process_queue');
        }
        add_option('oa_activation_redirect', 1);
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('oa_process_queue');
    }

    public static function maybe_upgrade() {
        if (get_option('oa_db_version') !== self::DB_VERSION) {
            self::create_tables();
            update_option('oa_db_version', self::DB_VERSION);
        }
        if (!wp_next_scheduled('oa_process_queue')) {
            wp_schedule_event(time() + 60, 'oa_minute', 'oa_process_queue');
        }
    }

    public static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'oa_' . $name;
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $jobs  = self::table('jobs');
        $calls = self::table('calls');

        dbDelta("CREATE TABLE $jobs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            batch_id varchar(32) NOT NULL DEFAULT '',
            trade varchar(64) NOT NULL DEFAULT '',
            keyword_id varchar(96) NOT NULL DEFAULT '',
            city_code varchar(10) NOT NULL DEFAULT '',
            city_name varchar(190) NOT NULL DEFAULT '',
            payload longtext NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 0,
            attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
            claim varchar(32) NOT NULL DEFAULT '',
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            seo_score tinyint(3) unsigned NOT NULL DEFAULT 0,
            tokens int(11) unsigned NOT NULL DEFAULT 0,
            message text NULL,
            publish_at datetime NULL,
            created_at datetime NOT NULL,
            started_at datetime NULL,
            finished_at datetime NULL,
            PRIMARY KEY  (id),
            KEY status_priority (status, priority, id),
            KEY batch (batch_id),
            KEY city_kw (city_code, keyword_id)
        ) $charset;");

        dbDelta("CREATE TABLE $calls (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            kind varchar(16) NOT NULL DEFAULT 'call',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_kind (post_id, kind),
            KEY created (created_at)
        ) $charset;");
    }
}
