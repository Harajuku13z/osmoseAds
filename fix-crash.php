<?php
/**
 * SCRIPT DE RÉPARATION URGENTE - Erreur Critique WordPress
 * 
 * Ce script peut être exécuté MÊME si WordPress est cassé
 * 
 * INSTRUCTIONS :
 * 1. Téléchargez ce fichier
 * 2. Uploadez-le à la RACINE de WordPress (même dossier que wp-config.php)
 * 3. Visitez : https://votre-site.com/fix-crash.php
 * 4. Suivez les instructions affichées
 * 5. SUPPRIMEZ ce fichier après utilisation !
 */

// Sécurité basique
$SECRET_KEY = 'osmose2024'; // Changez cette clé !
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    die('❌ Accès refusé. Utilisez : fix-crash.php?key=' . $SECRET_KEY);
}

// Charger WordPress (même si le plugin est cassé)
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    __DIR__ . '/../wp-load.php',
    __DIR__ . '/../../wp-load.php',
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
    die('❌ Impossible de charger WordPress. Placez ce fichier à la racine de WordPress.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔧 Réparation Urgente - Osmose ADS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .step h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .code {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background: #5568d3;
        }
        .button-danger {
            background: #dc3545;
        }
        .button-danger:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        table th {
            background: #667eea;
            color: white;
        }
        .actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔧 Réparation Urgente</h1>
        <p>Script de réparation automatique pour l'erreur critique WordPress</p>
    </div>
    
    <div class="content">
        <?php
        
        $errors_fixed = [];
        $errors_found = [];
        $warnings = [];
        
        echo '<div class="step">';
        echo '<h2>1️⃣ Vérification du Système</h2>';
        
        // Vérifier PHP
        $php_version = phpversion();
        if (version_compare($php_version, '7.4.0', '>=')) {
            echo '<p class="success">✅ PHP ' . $php_version . ' (compatible)</p>';
        } else {
            echo '<p class="error">❌ PHP ' . $php_version . ' (minimum 7.4 requis)</p>';
            $errors_found[] = 'Version PHP trop ancienne';
        }
        
        // Vérifier WordPress
        if (defined('ABSPATH')) {
            echo '<p class="success">✅ WordPress chargé</p>';
        } else {
            echo '<p class="error">❌ WordPress non chargé</p>';
            $errors_found[] = 'WordPress non chargé';
        }
        
        echo '</div>';
        
        // RÉPARATION 1 : Désactiver le plugin si nécessaire
        echo '<div class="step">';
        echo '<h2>2️⃣ Désactivation Temporaire du Plugin</h2>';
        
        if (function_exists('get_option')) {
            $active_plugins = get_option('active_plugins', []);
            $plugin_file = 'osmose-ads/osmose-ads.php';
            
            if (in_array($plugin_file, $active_plugins)) {
                echo '<p class="warning">⚠️ Le plugin est actuellement activé</p>';
                echo '<p>Pour réparer, nous devons le désactiver temporairement.</p>';
                
                if (isset($_GET['disable_plugin']) && $_GET['disable_plugin'] === 'yes') {
                    $new_plugins = array_diff($active_plugins, [$plugin_file]);
                    update_option('active_plugins', array_values($new_plugins));
                    echo '<p class="success">✅ Plugin désactivé avec succès !</p>';
                    echo '<p><strong>Rafraîchissez cette page pour continuer la réparation.</strong></p>';
                    $errors_fixed[] = 'Plugin désactivé temporairement';
                } else {
                    echo '<a href="?key=' . $SECRET_KEY . '&disable_plugin=yes" class="button button-danger">Désactiver le Plugin Temporairement</a>';
                    echo '<p class="warning"><small>⚠️ Cela désactivera le plugin pour permettre la réparation. Vous pourrez le réactiver après.</small></p>';
                }
            } else {
                echo '<p class="success">✅ Le plugin est déjà désactivé</p>';
            }
        }
        
        echo '</div>';
        
        // RÉPARATION 2 : Réparer la base de données
        echo '<div class="step">';
        echo '<h2>3️⃣ Réparation de la Base de Données</h2>';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            echo '<p class="warning">⚠️ Table manquante : ' . $table_name . '</p>';
            echo '<p>Création de la table...</p>';
            
            // Créer la table
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ad_id bigint(20) UNSIGNED,
                ad_slug varchar(255),
                page_url varchar(500),
                phone_number varchar(50),
                user_ip varchar(45),
                user_agent text,
                referrer varchar(500),
                source varchar(50),
                call_time datetime DEFAULT CURRENT_TIMESTAMP,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ad_id (ad_id),
                KEY idx_created_at (created_at),
                KEY idx_call_time (call_time)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                echo '<p class="success">✅ Table créée avec succès !</p>';
                $errors_fixed[] = 'Table créée';
            } else {
                echo '<p class="error">❌ Impossible de créer la table : ' . $wpdb->last_error . '</p>';
                $errors_found[] = 'Impossible de créer la table';
            }
        } else {
            echo '<p class="success">✅ Table trouvée : ' . $table_name . '</p>';
            
            // Vérifier la colonne 'source'
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            $has_source = false;
            
            foreach ($columns as $column) {
                if ($column->Field === 'source') {
                    $has_source = true;
                    break;
                }
            }
            
            if (!$has_source) {
                echo '<p class="warning">⚠️ Colonne "source" manquante. Ajout en cours...</p>';
                
                // Trouver la position de 'referrer'
                $has_referrer = false;
                foreach ($columns as $column) {
                    if ($column->Field === 'referrer') {
                        $has_referrer = true;
                        break;
                    }
                }
                
                if ($has_referrer) {
                    $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) DEFAULT NULL AFTER referrer");
                } else {
                    $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) DEFAULT NULL");
                }
                
                if ($result !== false) {
                    echo '<p class="success">✅ Colonne "source" ajoutée avec succès !</p>';
                    $errors_fixed[] = 'Colonne source ajoutée';
                } else {
                    echo '<p class="error">❌ Erreur : ' . $wpdb->last_error . '</p>';
                    echo '<p><strong>Exécutez cette requête dans phpMyAdmin :</strong></p>';
                    echo '<div class="code">ALTER TABLE ' . $table_name . ' ADD COLUMN source varchar(50) AFTER referrer;</div>';
                    $errors_found[] = 'Impossible d\'ajouter la colonne source';
                }
            } else {
                echo '<p class="success">✅ Colonne "source" présente</p>';
            }
        }
        
        echo '</div>';
        
        // RÉPARATION 3 : Vérifier les fichiers
        echo '<div class="step">';
        echo '<h2>4️⃣ Vérification des Fichiers du Plugin</h2>';
        
        $plugin_dir = WP_PLUGIN_DIR . '/osmose-ads/';
        
        if (!is_dir($plugin_dir)) {
            echo '<p class="error">❌ Dossier du plugin introuvable : ' . $plugin_dir . '</p>';
            $errors_found[] = 'Dossier plugin manquant';
        } else {
            echo '<p class="success">✅ Dossier trouvé</p>';
            
            $critical_files = [
                'osmose-ads.php',
                'includes/class-osmose-ads.php',
                'includes/class-osmose-ads-loader.php',
                'includes/class-osmose-ads-activator.php',
            ];
            
            $missing = [];
            foreach ($critical_files as $file) {
                if (!file_exists($plugin_dir . $file)) {
                    $missing[] = $file;
                }
            }
            
            if (empty($missing)) {
                echo '<p class="success">✅ Tous les fichiers critiques sont présents</p>';
            } else {
                echo '<p class="error">❌ Fichiers manquants :</p>';
                echo '<ul>';
                foreach ($missing as $file) {
                    echo '<li>' . $file . '</li>';
                }
                echo '</ul>';
                echo '<p><strong>Solution :</strong> Ré-uploadez tous les fichiers du plugin depuis GitHub.</p>';
                $errors_found[] = count($missing) . ' fichiers manquants';
            }
        }
        
        echo '</div>';
        
        // RÉPARATION 4 : Activer le debug
        echo '<div class="step">';
        echo '<h2>5️⃣ Activation du Mode Debug</h2>';
        
        $wp_config_path = ABSPATH . 'wp-config.php';
        
        if (!file_exists($wp_config_path)) {
            echo '<p class="error">❌ wp-config.php introuvable</p>';
        } else {
            $wp_config_content = file_get_contents($wp_config_path);
            
            if (strpos($wp_config_content, "define('WP_DEBUG', true)") !== false) {
                echo '<p class="success">✅ Mode debug déjà activé</p>';
            } else {
                echo '<p class="warning">⚠️ Mode debug non activé</p>';
                
                if (isset($_GET['enable_debug']) && $_GET['enable_debug'] === 'yes') {
                    // Créer une sauvegarde
                    copy($wp_config_path, $wp_config_path . '.backup-' . date('Ymd-His'));
                    
                    // Ajouter le debug
                    $debug_code = "\n// Mode debug activé par fix-crash.php\n";
                    $debug_code .= "define('WP_DEBUG', true);\n";
                    $debug_code .= "define('WP_DEBUG_LOG', true);\n";
                    $debug_code .= "define('WP_DEBUG_DISPLAY', false);\n";
                    $debug_code .= "@ini_set('display_errors', 0);\n";
                    
                    // Insérer avant "C'est tout"
                    if (strpos($wp_config_content, "/* C'est tout") !== false) {
                        $wp_config_content = str_replace("/* C'est tout", $debug_code . "\n/* C'est tout", $wp_config_content);
                    } else {
                        $wp_config_content .= $debug_code;
                    }
                    
                    file_put_contents($wp_config_path, $wp_config_content);
                    echo '<p class="success">✅ Mode debug activé !</p>';
                    echo '<p>Les erreurs seront enregistrées dans : <code>wp-content/debug.log</code></p>';
                    $errors_fixed[] = 'Mode debug activé';
                } else {
                    echo '<a href="?key=' . $SECRET_KEY . '&enable_debug=yes" class="button">Activer le Mode Debug</a>';
                }
            }
        }
        
        echo '</div>';
        
        // RÉPARATION 5 : Réactiver le plugin
        echo '<div class="step">';
        echo '<h2>6️⃣ Réactivation du Plugin</h2>';
        
        if (function_exists('get_option')) {
            $active_plugins = get_option('active_plugins', []);
            $plugin_file = 'osmose-ads/osmose-ads.php';
            
            if (!in_array($plugin_file, $active_plugins)) {
                echo '<p class="warning">⚠️ Le plugin est désactivé</p>';
                
                if (isset($_GET['enable_plugin']) && $_GET['enable_plugin'] === 'yes') {
                    $active_plugins[] = $plugin_file;
                    update_option('active_plugins', array_values($active_plugins));
                    echo '<p class="success">✅ Plugin réactivé !</p>';
                    echo '<p><strong>Testez votre site maintenant !</strong></p>';
                    $errors_fixed[] = 'Plugin réactivé';
                } else {
                    echo '<a href="?key=' . $SECRET_KEY . '&enable_plugin=yes" class="button">Réactiver le Plugin</a>';
                    echo '<p class="warning"><small>⚠️ Réactivez seulement après avoir réparé la base de données !</small></p>';
                }
            } else {
                echo '<p class="success">✅ Plugin activé</p>';
            }
        }
        
        echo '</div>';
        
        // RÉSUMÉ
        echo '<div class="step">';
        echo '<h2>📊 Résumé</h2>';
        
        if (!empty($errors_fixed)) {
            echo '<div class="success">';
            echo '<h3>✅ Réparations Effectuées :</h3>';
            echo '<ul>';
            foreach ($errors_fixed as $fix) {
                echo '<li>' . $fix . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (!empty($errors_found)) {
            echo '<div class="error">';
            echo '<h3>❌ Problèmes Restants :</h3>';
            echo '<ul>';
            foreach ($errors_found as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (empty($errors_found) && !empty($errors_fixed)) {
            echo '<div class="success" style="padding: 30px; text-align: center;">';
            echo '<h2 style="font-size: 28px; margin-bottom: 15px;">🎉 Réparation Terminée !</h2>';
            echo '<p style="font-size: 18px; margin-bottom: 20px;">Votre site devrait maintenant fonctionner correctement.</p>';
            echo '<a href="' . home_url() . '" class="button" style="font-size: 18px; padding: 15px 30px;">Tester le Site</a>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // ACTIONS
        echo '<div class="actions">';
        echo '<h2>🔧 Actions Disponibles</h2>';
        echo '<p><a href="?key=' . $SECRET_KEY . '" class="button">🔄 Rafraîchir cette Page</a></p>';
        echo '<p><a href="' . admin_url() . '" class="button">📊 Tableau de Bord WordPress</a></p>';
        echo '<p><a href="' . home_url() . '" class="button">🏠 Page d\'Accueil</a></p>';
        echo '</div>';
        
        ?>
        
        <div class="warning" style="margin-top: 40px; padding: 20px;">
            <h3>⚠️ SÉCURITÉ IMPORTANTE</h3>
            <p><strong>SUPPRIMEZ ce fichier (fix-crash.php) du serveur après utilisation !</strong></p>
            <p>Ce script contient des fonctions sensibles et ne doit pas rester accessible.</p>
        </div>
    </div>
</div>
</body>
</html>

