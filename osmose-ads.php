<?php
/**
 * Plugin Name:       Osmose Ads — Pages locales SEO
 * Plugin URI:        https://osmose.com/osmose-ads
 * Description:       Génère en masse des pages locales uniques (1 ville × jusqu'à 8 mots-clés métier), rédigées par IA, optimisées AIOSEO, avec maillage interne, schema LocalBusiness/FAQ et photos.
 * Version:           2.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Osmose
 * License:           GPL v2 or later
 * Text Domain:       osmose-ads
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OA_VERSION', '2.1.0');
define('OA_FILE', __FILE__);
define('OA_DIR', plugin_dir_path(__FILE__));
define('OA_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function ($class) {
    if (strpos($class, 'OA_') !== 0) {
        return;
    }
    $file = OA_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

// Intervalle « chaque minute » déclaré dès le chargement du fichier : il doit exister
// AVANT toute programmation (activation, mise à jour), sinon WP-Cron refuse l'événement.
add_filter('cron_schedules', function ($schedules) {
    $schedules['oa_minute'] = array('interval' => 60, 'display' => 'Osmose Ads — chaque minute');
    return $schedules;
});

register_activation_hook(__FILE__, array('OA_Install', 'activate'));
register_deactivation_hook(__FILE__, array('OA_Install', 'deactivate'));

add_action('plugins_loaded', array('OA_Plugin', 'instance'));
