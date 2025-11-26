<?php
/**
 * Script de diagnostic pour Osmose ADS
 * 
 * Uploadez ce fichier à la racine de votre site WordPress
 * Puis accédez à : https://votre-site.com/diagnostic.php
 */

// Sécurité basique
$secret_key = 'osmose2024'; // Changez cette clé !
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('Accès refusé. Utilisez : diagnostic.php?key=' . $secret_key);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Osmose ADS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; }
        .success { color: green; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; }
        pre { background: #272822; color: #f8f8f2; padding: 15px; overflow-x: auto; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table td, table th { padding: 8px; border: 1px solid #ddd; text-align: left; }
        table th { background: #0073aa; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnostic Osmose ADS</h1>
    
    <?php
    
    // 1. Vérifier que WordPress est chargé
    echo '<div class="section">';
    echo '<h2>1. Chargement WordPress</h2>';
    
    if (file_exists('./wp-load.php')) {
        require_once('./wp-load.php');
        echo '<p class="success">✅ WordPress chargé avec succès</p>';
    } else {
        echo '<p class="error">❌ Impossible de charger WordPress. Assurez-vous que ce fichier est à la racine du site.</p>';
        echo '</div></div></body></html>';
        exit;
    }
    echo '</div>';
    
    // 2. Informations serveur
    echo '<div class="section">';
    echo '<h2>2. Informations Serveur</h2>';
    echo '<table>';
    echo '<tr><th>Paramètre</th><th>Valeur</th></tr>';
    echo '<tr><td>Version PHP</td><td>' . phpversion() . (version_compare(phpversion(), '7.4.0', '>=') ? ' <span class="success">✅</span>' : ' <span class="error">❌ (Minimum 7.4 requis)</span>') . '</td></tr>';
    echo '<tr><td>Version WordPress</td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td>URL du site</td><td>' . get_site_url() . '</td></tr>';
    echo '<tr><td>Répertoire WordPress</td><td>' . ABSPATH . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // 3. Vérifier les extensions PHP
    echo '<div class="section">';
    echo '<h2>3. Extensions PHP</h2>';
    echo '<table>';
    echo '<tr><th>Extension</th><th>Status</th></tr>';
    $required_extensions = ['curl', 'json', 'mbstring', 'mysqli', 'openssl'];
    foreach ($required_extensions as $ext) {
        $loaded = extension_loaded($ext);
        echo '<tr><td>' . $ext . '</td><td>' . ($loaded ? '<span class="success">✅ Activée</span>' : '<span class="error">❌ Manquante</span>') . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // 4. Vérifier que le plugin existe
    echo '<div class="section">';
    echo '<h2>4. Plugin Osmose ADS</h2>';
    
    $plugin_dir = WP_PLUGIN_DIR . '/osmose-ads/';
    
    if (!is_dir($plugin_dir)) {
        echo '<p class="error">❌ Le dossier du plugin n\'existe pas : ' . $plugin_dir . '</p>';
    } else {
        echo '<p class="success">✅ Dossier du plugin trouvé</p>';
        
        // Vérifier les fichiers principaux
        $required_files = [
            'osmose-ads.php',
            'includes/class-osmose-ads.php',
            'includes/class-osmose-ads-loader.php',
            'includes/class-osmose-ads-activator.php',
            'includes/class-osmose-ads-deactivator.php',
            'includes/class-osmose-ads-i18n.php',
            'includes/class-osmose-ads-post-types.php',
            'includes/class-osmose-ads-rewrite.php',
            'includes/models/class-ad.php',
            'includes/models/class-ad-template.php',
            'includes/services/class-ai-service.php',
            'includes/services/class-city-content-personalizer.php',
            'includes/services/class-france-geo-api.php',
            'admin/class-osmose-ads-admin.php',
            'public/class-osmose-ads-public.php',
        ];
        
        echo '<table>';
        echo '<tr><th>Fichier</th><th>Status</th><th>Taille</th></tr>';
        
        $missing_files = [];
        foreach ($required_files as $file) {
            $full_path = $plugin_dir . $file;
            $exists = file_exists($full_path);
            $size = $exists ? filesize($full_path) : 0;
            
            echo '<tr>';
            echo '<td>' . $file . '</td>';
            echo '<td>' . ($exists ? '<span class="success">✅</span>' : '<span class="error">❌ Manquant</span>') . '</td>';
            echo '<td>' . ($exists ? number_format($size) . ' octets' : '-') . '</td>';
            echo '</tr>';
            
            if (!$exists) {
                $missing_files[] = $file;
            }
        }
        echo '</table>';
        
        if (!empty($missing_files)) {
            echo '<p class="error">⚠️ Fichiers manquants : ' . count($missing_files) . '</p>';
            echo '<p>Ré-uploadez le plugin complet depuis GitHub.</p>';
        }
    }
    echo '</div>';
    
    // 5. Vérifier que le plugin est actif
    echo '<div class="section">';
    echo '<h2>5. Status du Plugin</h2>';
    
    if (!function_exists('is_plugin_active')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    $plugin_active = is_plugin_active('osmose-ads/osmose-ads.php');
    
    if ($plugin_active) {
        echo '<p class="success">✅ Le plugin est activé</p>';
    } else {
        echo '<p class="error">❌ Le plugin n\'est PAS activé</p>';
        echo '<p>Activez-le dans WordPress : wp-admin/plugins.php</p>';
    }
    echo '</div>';
    
    // 6. Vérifier les tables de la base de données
    echo '<div class="section">';
    echo '<h2>6. Base de Données</h2>';
    
    global $wpdb;
    
    $tables_to_check = [
        $wpdb->prefix . 'osmose_ad_templates',
        $wpdb->prefix . 'osmose_ads_call_tracking',
    ];
    
    echo '<table>';
    echo '<tr><th>Table</th><th>Status</th><th>Lignes</th></tr>';
    
    foreach ($tables_to_check as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0;
        
        echo '<tr>';
        echo '<td>' . $table . '</td>';
        echo '<td>' . ($exists ? '<span class="success">✅ Existe</span>' : '<span class="error">❌ Manquante</span>') . '</td>';
        echo '<td>' . ($exists ? number_format($count) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Vérifier la structure de la table de tracking
    $tracking_table = $wpdb->prefix . 'osmose_ads_call_tracking';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$tracking_table'") == $tracking_table;
    
    if ($table_exists) {
        echo '<h3>Structure de la table de tracking :</h3>';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $tracking_table");
        
        echo '<table>';
        echo '<tr><th>Colonne</th><th>Type</th><th>Null</th></tr>';
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td>' . $column->Field . '</td>';
            echo '<td>' . $column->Type . '</td>';
            echo '<td>' . $column->Null . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Vérifier que la colonne 'source' existe
        $has_source_column = false;
        foreach ($columns as $column) {
            if ($column->Field === 'source') {
                $has_source_column = true;
                break;
            }
        }
        
        if (!$has_source_column) {
            echo '<p class="error">❌ La colonne "source" est manquante dans la table de tracking !</p>';
            echo '<p><strong>Solution :</strong> Exécutez cette requête dans phpMyAdmin :</p>';
            echo '<pre>ALTER TABLE ' . $tracking_table . ' ADD COLUMN source varchar(50) AFTER referrer;</pre>';
        } else {
            echo '<p class="success">✅ La colonne "source" existe</p>';
        }
    } else {
        echo '<p class="error">❌ La table de tracking n\'existe pas. Désactivez puis réactivez le plugin.</p>';
    }
    
    echo '</div>';
    
    // 7. Vérifier les Custom Post Types
    echo '<div class="section">';
    echo '<h2>7. Custom Post Types</h2>';
    
    $post_types = ['ad', 'ad_template', 'city'];
    
    echo '<table>';
    echo '<tr><th>Post Type</th><th>Status</th><th>Nombre</th></tr>';
    
    foreach ($post_types as $pt) {
        $exists = post_type_exists($pt);
        $count = $exists ? wp_count_posts($pt)->publish : 0;
        
        echo '<tr>';
        echo '<td>' . $pt . '</td>';
        echo '<td>' . ($exists ? '<span class="success">✅ Enregistré</span>' : '<span class="error">❌ Non enregistré</span>') . '</td>';
        echo '<td>' . number_format($count) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // 8. Tester le chargement des classes
    echo '<div class="section">';
    echo '<h2>8. Test de Chargement des Classes</h2>';
    
    $classes_to_test = [
        'Osmose_Ads',
        'Osmose_Ads_Loader',
        'Osmose_Ads_Admin',
        'Osmose_Ads_Public',
        'Osmose_Ads_Post_Types',
        'Osmose_Ads_Rewrite',
        'Ad',
        'Ad_Template',
        'AI_Service',
        'City_Content_Personalizer',
        'France_Geo_API',
    ];
    
    echo '<table>';
    echo '<tr><th>Classe</th><th>Status</th></tr>';
    
    foreach ($classes_to_test as $class) {
        $exists = class_exists($class);
        echo '<tr>';
        echo '<td>' . $class . '</td>';
        echo '<td>' . ($exists ? '<span class="success">✅ Chargée</span>' : '<span class="error">❌ Non trouvée</span>') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // 9. Lire les dernières erreurs du log
    echo '<div class="section">';
    echo '<h2>9. Dernières Erreurs WordPress</h2>';
    
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    
    if (file_exists($debug_log)) {
        echo '<p class="success">✅ Fichier debug.log trouvé</p>';
        echo '<p><strong>Taille :</strong> ' . number_format(filesize($debug_log)) . ' octets</p>';
        
        // Lire les 50 dernières lignes
        $lines = file($debug_log);
        $last_lines = array_slice($lines, -50);
        
        echo '<h3>50 dernières lignes du log :</h3>';
        echo '<pre>' . htmlspecialchars(implode('', $last_lines)) . '</pre>';
        
        // Chercher les erreurs Osmose ADS
        $osmose_errors = array_filter($lines, function($line) {
            return stripos($line, 'osmose') !== false && (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false);
        });
        
        if (!empty($osmose_errors)) {
            echo '<h3 class="error">⚠️ Erreurs Osmose ADS trouvées :</h3>';
            echo '<pre>' . htmlspecialchars(implode('', array_slice($osmose_errors, -10))) . '</pre>';
        }
    } else {
        echo '<p class="warning">⚠️ Fichier debug.log non trouvé</p>';
        echo '<p>Activez le mode debug dans wp-config.php :</p>';
        echo '<pre>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</pre>';
    }
    echo '</div>';
    
    // 10. Résumé et recommandations
    echo '<div class="section">';
    echo '<h2>10. Résumé et Recommandations</h2>';
    
    $issues = [];
    
    if (version_compare(phpversion(), '7.4.0', '<')) {
        $issues[] = 'Version PHP trop ancienne (minimum 7.4 requis)';
    }
    
    if (!empty($missing_files)) {
        $issues[] = count($missing_files) . ' fichiers du plugin sont manquants';
    }
    
    if (!$plugin_active) {
        $issues[] = 'Le plugin n\'est pas activé';
    }
    
    if ($table_exists && !$has_source_column) {
        $issues[] = 'La colonne "source" est manquante dans la table de tracking';
    }
    
    if (!post_type_exists('ad')) {
        $issues[] = 'Le Custom Post Type "ad" n\'est pas enregistré';
    }
    
    if (empty($issues)) {
        echo '<p class="success">🎉 Aucun problème détecté ! Si vous avez toujours une erreur, consultez le log debug ci-dessus.</p>';
    } else {
        echo '<p class="error">⚠️ Problèmes détectés :</p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li class="error">' . $issue . '</li>';
        }
        echo '</ul>';
        
        echo '<h3>Actions recommandées :</h3>';
        echo '<ol>';
        
        if (!$plugin_active) {
            echo '<li>Activez le plugin dans WordPress (wp-admin/plugins.php)</li>';
        }
        
        if (!empty($missing_files)) {
            echo '<li>Ré-uploadez tous les fichiers du plugin depuis GitHub</li>';
        }
        
        if ($table_exists && !$has_source_column) {
            echo '<li>Exécutez la requête SQL fournie ci-dessus dans phpMyAdmin</li>';
        }
        
        if (version_compare(phpversion(), '7.4.0', '<')) {
            echo '<li>Mettez à jour PHP vers la version 7.4 ou supérieure</li>';
        }
        
        echo '<li>Désactivez puis réactivez le plugin pour recréer les tables</li>';
        echo '<li>Videz le cache de WordPress et du serveur</li>';
        echo '</ol>';
    }
    echo '</div>';
    
    ?>
    
    <div class="section">
        <h2>📋 Prochaines Étapes</h2>
        <ol>
            <li>Prenez une capture d'écran de cette page</li>
            <li>Si des problèmes sont listés, suivez les actions recommandées</li>
            <li>Consultez le fichier debug.log pour plus de détails</li>
            <li>Si le problème persiste, partagez cette capture avec le support</li>
        </ol>
    </div>
    
    <div class="section">
        <p style="text-align: center; color: #666; font-size: 0.9em;">
            Script de diagnostic Osmose ADS v1.0<br>
            🔒 N'oubliez pas de supprimer ce fichier après utilisation !
        </p>
    </div>
</div>
</body>
</html>

