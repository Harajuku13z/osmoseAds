<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Point d'entrée : branche tous les modules.
 */
final class OA_Plugin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        OA_Install::maybe_upgrade();

        OA_Queue::hooks();
        OA_Seo::hooks();
        OA_Public::hooks();

        if (is_admin()) {
            OA_Admin::hooks();
        }

        add_filter('cron_schedules', array($this, 'cron_schedules'));
    }

    public function cron_schedules($schedules) {
        $schedules['oa_minute'] = array(
            'interval' => 60,
            'display'  => 'Osmose Ads — chaque minute',
        );
        return $schedules;
    }
}
