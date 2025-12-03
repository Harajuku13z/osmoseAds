<?php
/**
 * Script de diagnostic pour l'activation du plugin
 * À exécuter depuis le terminal ou via wp-cli
 */

// Charger WordPress
if (!defined('ABSPATH')) {
    // Si on est dans le dossier du plugin
    $wp_load = dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        die("Impossible de charger WordPress. Assurez-vous d'exécuter ce script depuis le dossier du plugin.\n");
    }
}

echo "=== Diagnostic d'activation Osmose ADS ===\n\n";

// 1. Vérifier les constantes
echo "1. Vérification des constantes...\n";
if (defined('OSMOSE_ADS_PLUGIN_DIR')) {
    echo "   ✓ OSMOSE_ADS_PLUGIN_DIR: " . OSMOSE_ADS_PLUGIN_DIR . "\n";
} else {
    echo "   ✗ OSMOSE_ADS_PLUGIN_DIR non définie\n";
}

if (defined('OSMOSE_ADS_PLUGIN_URL')) {
    echo "   ✓ OSMOSE_ADS_PLUGIN_URL: " . OSMOSE_ADS_PLUGIN_URL . "\n";
} else {
    echo "   ✗ OSMOSE_ADS_PLUGIN_URL non définie\n";
}

// 2. Vérifier les fichiers principaux
echo "\n2. Vérification des fichiers...\n";
$files_to_check = array(
    'includes/class-osmose-ads.php',
    'includes/class-osmose-ads-loader.php',
    'includes/class-osmose-ads-activator.php',
    'admin/class-osmose-ads-admin.php',
    'public/class-osmose-ads-public.php',
    'includes/class-osmose-ads-email.php',
);

foreach ($files_to_check as $file) {
    $path = OSMOSE_ADS_PLUGIN_DIR . $file;
    if (file_exists($path)) {
        echo "   ✓ $file existe\n";
    } else {
        echo "   ✗ $file MANQUANT\n";
    }
}

// 3. Vérifier la syntaxe PHP
echo "\n3. Vérification de la syntaxe PHP...\n";
foreach ($files_to_check as $file) {
    $path = OSMOSE_ADS_PLUGIN_DIR . $file;
    if (file_exists($path)) {
        $output = array();
        $return = 0;
        exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return);
        if ($return === 0) {
            echo "   ✓ $file: syntaxe OK\n";
        } else {
            echo "   ✗ $file: ERREUR DE SYNTAXE\n";
            echo "      " . implode("\n      ", $output) . "\n";
        }
    }
}

// 4. Tester le chargement des classes
echo "\n4. Test de chargement des classes...\n";
try {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-loader.php';
    echo "   ✓ Osmose_Ads_Loader chargée\n";
} catch (Exception $e) {
    echo "   ✗ Erreur chargement Osmose_Ads_Loader: " . $e->getMessage() . "\n";
}

try {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-activator.php';
    echo "   ✓ Osmose_Ads_Activator chargée\n";
} catch (Exception $e) {
    echo "   ✗ Erreur chargement Osmose_Ads_Activator: " . $e->getMessage() . "\n";
}

try {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads.php';
    echo "   ✓ Osmose_Ads chargée\n";
} catch (Exception $e) {
    echo "   ✗ Erreur chargement Osmose_Ads: " . $e->getMessage() . "\n";
}

try {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-email.php';
    echo "   ✓ Osmose_Ads_Email chargée\n";
} catch (Exception $e) {
    echo "   ✗ Erreur chargement Osmose_Ads_Email: " . $e->getMessage() . "\n";
}

// 5. Tester l'activation
echo "\n5. Test d'activation...\n";
try {
    if (class_exists('Osmose_Ads_Activator')) {
        Osmose_Ads_Activator::activate();
        echo "   ✓ Activation réussie\n";
    } else {
        echo "   ✗ Classe Osmose_Ads_Activator non trouvée\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erreur lors de l'activation: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du diagnostic ===\n";


