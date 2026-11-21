<?php
/**
 * Désinstallation : supprime réglages et tables. Les pages générées sont conservées
 * (ce sont des pages WordPress normales, elles continuent de fonctionner).
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
global $wpdb;
foreach (array('oa_settings', 'oa_custom_trades', 'oa_usage', 'oa_db_version', 'oa_mesh_ver', 'oa_last_fatal') as $opt) {
    delete_option($opt);
}
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}oa_jobs");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}oa_calls");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%oa\_geo\_%' OR option_name LIKE '\_transient\_%oa\_mesh\_%'");
wp_clear_scheduled_hook('oa_process_queue');
