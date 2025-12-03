<?php
/**
 * Plugin Name: Osmose ADS
 * Plugin URI: https://osmose.com/osmose-ads
 * Description: Système de génération automatique et manuelle de pages de services géolocalisées avec personnalisation IA. Optimisé pour le SEO local.
 * Version: 1.0.0
 * Author: Osmose
 * Author URI: https://osmose.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: osmose-ads
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Si ce fichier est appelé directement, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('OSMOSE_ADS_VERSION', '1.0.0');
define('OSMOSE_ADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSMOSE_ADS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OSMOSE_ADS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Activation du plugin
 */
register_activation_hook(__FILE__, 'osmose_ads_activate');
function osmose_ads_activate() {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-activator.php';
    Osmose_Ads_Activator::activate();
}

/**
 * Désactivation du plugin
 */
register_deactivation_hook(__FILE__, 'osmose_ads_deactivate');
function osmose_ads_deactivate() {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-deactivator.php';
    Osmose_Ads_Deactivator::deactivate();
}

/**
 * Initialisation du plugin
 * On attend que WordPress soit complètement chargé avant d'initialiser
 */
function osmose_ads_run() {
    // S'assurer que toutes les fonctions WordPress sont disponibles
    if (!function_exists('add_action')) {
        return;
    }
    
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads.php';
    
    try {
        $plugin = new Osmose_Ads();
        $plugin->run();
    } catch (Exception $e) {
        error_log('Osmose ADS: Erreur lors de l\'initialisation: ' . $e->getMessage());
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_die('Erreur Osmose ADS: ' . $e->getMessage());
        }
    }
}

// Initialiser le plugin après que WordPress soit complètement chargé
add_action('plugins_loaded', 'osmose_ads_run', 10);



