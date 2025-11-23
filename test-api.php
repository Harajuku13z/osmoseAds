<?php
/**
 * Script de test pour l'API géographique de la France
 * À exécuter depuis la ligne de commande ou en l'incluant dans WordPress
 * 
 * Usage:
 * - Via WordPress: Ajoutez ce fichier temporairement dans le répertoire du plugin
 * - Via CLI: php test-api.php (nécessite d'inclure WordPress)
 */

// Charger WordPress si nécessaire
if (!defined('ABSPATH')) {
    // Ajustez le chemin selon votre installation
    require_once dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
}

if (!defined('ABSPATH')) {
    die('WordPress non trouvé. Ajustez le chemin dans ce fichier.');
}

require_once dirname(__FILE__) . '/includes/services/class-france-geo-api.php';

echo "=== Test de l'API géographique de la France ===\n\n";

$api = new France_Geo_API();

// Test 1: Récupérer les départements
echo "Test 1: Récupération des départements...\n";
$departments = $api->get_departments();
if (is_wp_error($departments)) {
    echo "❌ ERREUR: " . $departments->get_error_message() . "\n\n";
} else {
    echo "✅ Succès: " . count($departments) . " départements trouvés\n";
    echo "   Exemple: " . $departments[0]['nom'] . " (" . $departments[0]['code'] . ")\n\n";
}

// Test 2: Récupérer les régions
echo "Test 2: Récupération des régions...\n";
$regions = $api->get_regions();
if (is_wp_error($regions)) {
    echo "❌ ERREUR: " . $regions->get_error_message() . "\n\n";
} else {
    echo "✅ Succès: " . count($regions) . " régions trouvées\n";
    echo "   Exemple: " . $regions[0]['nom'] . " (" . $regions[0]['code'] . ")\n\n";
}

// Test 3: Récupérer les communes d'un département (Paris - 75)
echo "Test 3: Récupération des communes du département 75 (Paris)...\n";
$communes = $api->get_communes_by_department('75');
if (is_wp_error($communes)) {
    echo "❌ ERREUR: " . $communes->get_error_message() . "\n\n";
} else {
    echo "✅ Succès: " . count($communes) . " communes trouvées\n";
    if (!empty($communes)) {
        $first = $communes[0];
        echo "   Exemple: " . ($first['nom'] ?? 'N/A') . " (code: " . ($first['code'] ?? 'N/A') . ")\n";
        echo "   Structure: " . print_r(array_keys($first), true) . "\n";
    }
    echo "\n";
}

// Test 4: Recherche de commune par nom
echo "Test 4: Recherche de commune par nom (Paris)...\n";
$search_results = $api->search_commune('Paris', 3);
if (empty($search_results)) {
    echo "❌ ERREUR: Aucun résultat trouvé\n\n";
} else {
    echo "✅ Succès: " . count($search_results) . " résultats trouvés\n";
    foreach ($search_results as $result) {
        echo "   - " . ($result['nom'] ?? 'N/A') . " (" . ($result['code'] ?? 'N/A') . ")\n";
    }
    echo "\n";
}

// Test 5: Normalisation des données d'une commune
echo "Test 5: Normalisation des données d'une commune...\n";
if (!empty($communes) && is_array($communes)) {
    $normalized = $api->normalize_commune_data($communes[0]);
    echo "✅ Données normalisées:\n";
    echo print_r($normalized, true) . "\n";
} else {
    echo "❌ Pas de commune disponible pour le test\n\n";
}

// Test 6: Test de connexion directe à l'API
echo "Test 6: Test de connexion directe à l'API...\n";
$test_url = 'https://geo.api.gouv.fr/departements/75/communes?fields=nom,code&limit=1';
echo "   URL: " . $test_url . "\n";

$response = wp_remote_get($test_url, array(
    'timeout' => 30,
    'sslverify' => true,
));

if (is_wp_error($response)) {
    echo "❌ ERREUR de connexion: " . $response->get_error_message() . "\n";
    echo "   Code d'erreur: " . $response->get_error_code() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($code === 200) {
        $data = json_decode($body, true);
        echo "✅ Connexion réussie (HTTP $code)\n";
        echo "   Réponse: " . substr($body, 0, 200) . "...\n";
    } else {
        echo "❌ ERREUR HTTP: Code $code\n";
        echo "   Réponse: " . substr($body, 0, 200) . "\n";
    }
}

echo "\n=== Fin des tests ===\n";

