<?php
/**
 * Classe de désactivation du plugin
 */
class Osmose_Ads_Deactivator {

    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Nettoyer les transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_osmose_ads_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_osmose_ads_%'");
    }
}



