<?php
/**
 * Script de réparation pour corriger les erreurs du simulateur
 * À exécuter une fois depuis l'admin WordPress ou via WP-CLI
 */

// Charger WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

global $wpdb;

echo "<h1>Réparation du simulateur Osmose ADS</h1>";

// 1. Vérifier que la table existe
$table_name = $wpdb->prefix . 'osmose_ads_quote_requests';
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

if (!$table_exists) {
    echo "<p>Création de la table quote_requests...</p>";
    
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
    
    dbDelta($sql);
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        echo "<p style='color: green;'>✓ Table créée avec succès</p>";
    } else {
        echo "<p style='color: red;'>✗ Erreur lors de la création de la table: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ La table existe déjà</p>";
}

// 2. Vérifier les fichiers du simulateur
$files_to_check = array(
    'public/templates/simulator.php',
    'public/css/osmose-simulator.css',
    'public/js/osmose-simulator.js',
    'admin/partials/quote-requests.php'
);

$plugin_dir = plugin_dir_path(__FILE__);

echo "<h2>Vérification des fichiers</h2>";
foreach ($files_to_check as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        echo "<p style='color: green;'>✓ $file existe</p>";
    } else {
        echo "<p style='color: red;'>✗ $file manquant</p>";
    }
}

// 3. Vérifier la syntaxe PHP des fichiers principaux
echo "<h2>Vérification de la syntaxe PHP</h2>";

$php_files = array(
    'public/class-osmose-ads-public.php',
    'includes/class-osmose-ads.php',
    'admin/class-osmose-ads-admin.php'
);

foreach ($php_files as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        $output = array();
        $return_var = 0;
        exec("php -l " . escapeshellarg($full_path) . " 2>&1", $output, $return_var);
        if ($return_var === 0) {
            echo "<p style='color: green;'>✓ $file : syntaxe OK</p>";
        } else {
            echo "<p style='color: red;'>✗ $file : erreur de syntaxe</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}

echo "<h2>Réparation terminée</h2>";
echo "<p><a href='" . admin_url('admin.php?page=osmose-ads-quotes') . "'>Voir les demandes de devis</a></p>";
echo "<p><a href='" . admin_url() . "'>Retour à l'admin</a></p>";





