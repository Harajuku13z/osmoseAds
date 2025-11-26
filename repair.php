<?php
/**
 * Script de Réparation Automatique Osmose ADS
 * 
 * INSTRUCTIONS :
 * 1. Uploadez ce fichier dans /wp-content/plugins/osmose-ads/
 * 2. Visitez : https://votre-site.com/wp-content/plugins/osmose-ads/repair.php?key=osmose2024
 * 
 * ⚠️ SUPPRIMEZ CE FICHIER après utilisation !
 */

// Clé de sécurité - CHANGEZ-LA !
$security_key = 'osmose2024';

// Vérifier la clé
if (!isset($_GET['key']) || $_GET['key'] !== $security_key) {
    die('❌ Accès refusé. Utilisez : repair.php?key=' . $security_key);
}

// Charger WordPress
$wp_load_paths = [
    __DIR__ . '/../../../../wp-load.php',  // Depuis plugin
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../wp-load.php',
    __DIR__ . '/../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('❌ Impossible de charger WordPress. Placez ce fichier dans /wp-content/plugins/osmose-ads/');
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('❌ Vous devez être connecté en tant qu\'administrateur.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Réparation Osmose ADS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0073aa;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        .section {
            margin: 25px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
        }
        .success {
            color: #46b450;
            font-weight: bold;
        }
        .error {
            color: #dc3232;
            font-weight: bold;
        }
        .warning {
            color: #ffb900;
            font-weight: bold;
        }
        .code {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .button:hover {
            background: #005a87;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #0073aa;
            color: white;
        }
        ul.checklist {
            list-style: none;
            padding: 0;
        }
        ul.checklist li {
            padding: 8px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Réparation Automatique Osmose ADS</h1>
    
    <?php
    
    $errors = [];
    $warnings = [];
    $success = [];
    $actions_performed = [];
    
    // 1. Vérifier la version PHP
    echo '<div class="section">';
    echo '<h2>1. Vérification PHP</h2>';
    $php_version = phpversion();
    if (version_compare($php_version, '7.4.0', '>=')) {
        echo '<p class="success">✅ PHP ' . $php_version . ' (compatible)</p>';
        $success[] = 'Version PHP compatible';
    } else {
        echo '<p class="error">❌ PHP ' . $php_version . ' (minimum 7.4 requis)</p>';
        $errors[] = 'Version PHP trop ancienne';
    }
    echo '</div>';
    
    // 2. Vérifier que le plugin existe
    echo '<div class="section">';
    echo '<h2>2. Vérification des Fichiers</h2>';
    
    if (!defined('OSMOSE_ADS_PLUGIN_DIR')) {
        define('OSMOSE_ADS_PLUGIN_DIR', WP_PLUGIN_DIR . '/osmose-ads/');
    }
    
    $required_files = [
        'osmose-ads.php',
        'includes/class-osmose-ads.php',
        'includes/class-osmose-ads-loader.php',
        'includes/class-osmose-ads-activator.php',
        'includes/models/class-ad.php',
        'includes/models/class-ad-template.php',
        'admin/class-osmose-ads-admin.php',
        'public/class-osmose-ads-public.php',
    ];
    
    $missing_files = [];
    foreach ($required_files as $file) {
        if (!file_exists(OSMOSE_ADS_PLUGIN_DIR . $file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        echo '<p class="success">✅ Tous les fichiers principaux sont présents</p>';
        $success[] = 'Fichiers du plugin présents';
    } else {
        echo '<p class="error">❌ Fichiers manquants : ' . count($missing_files) . '</p>';
        echo '<ul>';
        foreach ($missing_files as $file) {
            echo '<li>' . $file . '</li>';
        }
        echo '</ul>';
        $errors[] = count($missing_files) . ' fichiers manquants';
    }
    echo '</div>';
    
    // 3. Réparer la base de données
    echo '<div class="section">';
    echo '<h2>3. Réparation de la Base de Données</h2>';
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
    
    // Vérifier si la table existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        echo '<p class="warning">⚠️ Table manquante : ' . $table_name . '</p>';
        echo '<p>Tentative de création...</p>';
        
        // Charger l'activateur
        require_once(OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-activator.php');
        
        // Créer les tables
        Osmose_Ads_Activator::activate();
        
        // Re-vérifier
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if ($table_exists) {
            echo '<p class="success">✅ Table créée avec succès !</p>';
            $actions_performed[] = 'Table de tracking créée';
            $success[] = 'Table créée';
        } else {
            echo '<p class="error">❌ Impossible de créer la table</p>';
            $errors[] = 'Impossible de créer la table';
        }
    } else {
        echo '<p class="success">✅ Table trouvée : ' . $table_name . '</p>';
        
        // Vérifier la colonne 'source'
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $has_source = false;
        $has_referrer = false;
        
        echo '<h3>Colonnes actuelles :</h3>';
        echo '<table>';
        echo '<tr><th>Colonne</th><th>Type</th></tr>';
        foreach ($columns as $column) {
            echo '<tr><td>' . $column->Field . '</td><td>' . $column->Type . '</td></tr>';
            if ($column->Field === 'source') {
                $has_source = true;
            }
            if ($column->Field === 'referrer') {
                $has_referrer = true;
            }
        }
        echo '</table>';
        
        if (!$has_source) {
            echo '<p class="warning">⚠️ Colonne "source" manquante. Ajout en cours...</p>';
            
            // Ajouter la colonne
            if ($has_referrer) {
                $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) DEFAULT NULL AFTER referrer");
            } else {
                $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) DEFAULT NULL");
            }
            
            if ($result !== false) {
                echo '<p class="success">✅ Colonne "source" ajoutée avec succès !</p>';
                $actions_performed[] = 'Colonne "source" ajoutée à la table de tracking';
                $success[] = 'Colonne source ajoutée';
            } else {
                echo '<p class="error">❌ Erreur lors de l\'ajout de la colonne : ' . $wpdb->last_error . '</p>';
                $errors[] = 'Impossible d\'ajouter la colonne source';
            }
        } else {
            echo '<p class="success">✅ Colonne "source" présente</p>';
            $success[] = 'Colonne source présente';
        }
    }
    echo '</div>';
    
    // 4. Vérifier les Custom Post Types
    echo '<div class="section">';
    echo '<h2>4. Vérification des Post Types</h2>';
    
    $post_types_to_check = ['ad', 'ad_template', 'city'];
    $missing_post_types = [];
    
    echo '<table>';
    echo '<tr><th>Post Type</th><th>Status</th><th>Nombre</th></tr>';
    
    foreach ($post_types_to_check as $pt) {
        $exists = post_type_exists($pt);
        $count = 0;
        
        if ($exists) {
            $count = wp_count_posts($pt);
            $count = isset($count->publish) ? $count->publish : 0;
        } else {
            $missing_post_types[] = $pt;
        }
        
        echo '<tr>';
        echo '<td>' . $pt . '</td>';
        echo '<td>' . ($exists ? '<span class="success">✅ Enregistré</span>' : '<span class="error">❌ Non enregistré</span>') . '</td>';
        echo '<td>' . number_format($count) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    if (!empty($missing_post_types)) {
        echo '<p class="error">⚠️ Post types manquants : ' . implode(', ', $missing_post_types) . '</p>';
        echo '<p>Le plugin doit être activé pour enregistrer ces post types.</p>';
        $warnings[] = 'Post types non enregistrés';
    } else {
        echo '<p class="success">✅ Tous les post types sont enregistrés</p>';
        $success[] = 'Post types enregistrés';
    }
    echo '</div>';
    
    // 5. Flush rewrite rules
    echo '<div class="section">';
    echo '<h2>5. Rafraîchissement des URLs</h2>';
    flush_rewrite_rules(false);
    echo '<p class="success">✅ Règles d\'URL rafraîchies</p>';
    $actions_performed[] = 'Règles d\'URL rafraîchies';
    echo '</div>';
    
    // 6. Vérifier le statut du plugin
    echo '<div class="section">';
    echo '<h2>6. Statut du Plugin</h2>';
    
    if (!function_exists('is_plugin_active')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    $plugin_active = is_plugin_active('osmose-ads/osmose-ads.php');
    
    if ($plugin_active) {
        echo '<p class="success">✅ Le plugin est activé</p>';
        $success[] = 'Plugin activé';
    } else {
        echo '<p class="error">❌ Le plugin n\'est PAS activé</p>';
        echo '<p>Activez-le dans <a href="' . admin_url('plugins.php') . '">wp-admin/plugins.php</a></p>';
        $errors[] = 'Plugin non activé';
    }
    echo '</div>';
    
    // 7. Résumé
    echo '<div class="section">';
    echo '<h2>7. Résumé de la Réparation</h2>';
    
    if (!empty($actions_performed)) {
        echo '<h3 class="success">✅ Actions effectuées :</h3>';
        echo '<ul class="checklist">';
        foreach ($actions_performed as $action) {
            echo '<li>✅ ' . $action . '</li>';
        }
        echo '</ul>';
    }
    
    if (empty($errors)) {
        echo '<div style="padding: 20px; background: #d4edda; border: 2px solid #46b450; border-radius: 8px; margin: 20px 0;">';
        echo '<h3 style="color: #155724; margin-top: 0;">🎉 Réparation Terminée avec Succès !</h3>';
        echo '<p style="font-size: 16px; margin: 10px 0;">Votre plugin Osmose ADS devrait maintenant fonctionner correctement.</p>';
        echo '<p><strong>Prochaines étapes :</strong></p>';
        echo '<ol>';
        echo '<li>Testez votre site : <a href="' . home_url() . '" target="_blank">' . home_url() . '</a></li>';
        echo '<li>Vérifiez qu\'il n\'y a plus d\'erreur</li>';
        echo '<li><strong>IMPORTANT : Supprimez ce fichier repair.php du serveur !</strong></li>';
        echo '</ol>';
        echo '<a href="' . admin_url() . '" class="button">Retour au Tableau de Bord</a>';
        echo '</div>';
    } else {
        echo '<div style="padding: 20px; background: #f8d7da; border: 2px solid #dc3232; border-radius: 8px; margin: 20px 0;">';
        echo '<h3 style="color: #721c24; margin-top: 0;">⚠️ Problèmes Détectés</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li class="error">❌ ' . $error . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Actions recommandées :</strong></p>';
        echo '<ol>';
        
        if (in_array('Plugin non activé', $errors)) {
            echo '<li>Activez le plugin dans <a href="' . admin_url('plugins.php') . '">wp-admin/plugins.php</a></li>';
        }
        
        if (!empty($missing_files)) {
            echo '<li>Ré-uploadez tous les fichiers du plugin depuis GitHub</li>';
        }
        
        if (in_array('Version PHP trop ancienne', $errors)) {
            echo '<li>Mettez à jour PHP vers la version 7.4 ou supérieure</li>';
        }
        
        echo '<li>Contactez le support en fournissant cette capture d\'écran</li>';
        echo '</ol>';
        echo '</div>';
    }
    
    if (!empty($warnings)) {
        echo '<h3 class="warning">⚠️ Avertissements :</h3>';
        echo '<ul>';
        foreach ($warnings as $warning) {
            echo '<li class="warning">' . $warning . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    
    // 8. Informations système
    echo '<div class="section">';
    echo '<h2>8. Informations Système</h2>';
    echo '<table>';
    echo '<tr><th>Paramètre</th><th>Valeur</th></tr>';
    echo '<tr><td>Version PHP</td><td>' . phpversion() . '</td></tr>';
    echo '<tr><td>Version WordPress</td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td>URL du site</td><td>' . home_url() . '</td></tr>';
    echo '<tr><td>Thème actif</td><td>' . wp_get_theme()->get('Name') . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    ?>
    
    <div class="section" style="background: #fff3cd; border-color: #ffb900;">
        <h3 style="color: #856404;">⚠️ SÉCURITÉ IMPORTANTE</h3>
        <p style="font-size: 16px;"><strong>SUPPRIMEZ CE FICHIER (repair.php) du serveur maintenant !</strong></p>
        <p>Ce script contient des fonctions sensibles et ne doit pas rester accessible.</p>
    </div>
</div>
</body>
</html>

