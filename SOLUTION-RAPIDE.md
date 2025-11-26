# ⚡ SOLUTION RAPIDE - Erreur Critique WordPress

## 🎯 Solution #1 : Réparer la Base de Données (TRÈS PROBABLE)

### Via phpMyAdmin (RECOMMANDÉ)
1. Connectez-vous à **phpMyAdmin**
2. Sélectionnez votre base de données WordPress
3. Cliquez sur l'onglet **SQL**
4. Copiez-collez cette requête :

```sql
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

5. Cliquez sur **Exécuter**

### Via wp-cli (si disponible)
```bash
wp db query "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;"
```

---

## 🎯 Solution #2 : Désactiver/Réactiver le Plugin

1. Connectez-vous à votre serveur via FTP/cPanel
2. Allez dans `/wp-content/plugins/`
3. **Renommez** le dossier `osmose-ads` en `osmose-ads-temp`
4. Votre site devrait refonctionner (sans les annonces)
5. **Renommez** `osmose-ads-temp` en `osmose-ads`
6. Allez dans **wp-admin/plugins.php**
7. **Réactivez** le plugin Osmose ADS

Cela recréera les tables correctement.

---

## 🎯 Solution #3 : Activer le Mode Debug pour Voir l'Erreur Exacte

### Étape 1 : Activer le debug
Connectez-vous via FTP et éditez le fichier `wp-config.php` à la racine.

Trouvez cette ligne (vers la ligne 80) :
```php
define('WP_DEBUG', false);
```

Remplacez par :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

Sauvegardez le fichier.

### Étape 2 : Reproduire l'erreur
Visitez : https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire

### Étape 3 : Lire le log
Via FTP, ouvrez le fichier :
```
/wp-content/debug.log
```

Cherchez les dernières lignes avec "Fatal error", "PHP Error" ou "Osmose ADS"

Copiez ces lignes et envoyez-les moi.

---

## 🎯 Solution #4 : Vérifier que Tous les Fichiers Sont Présents

Via FTP, vérifiez que ces fichiers existent :

```
/wp-content/plugins/osmose-ads/
├── osmose-ads.php  ✓
├── includes/
│   ├── class-osmose-ads.php  ✓
│   ├── class-osmose-ads-loader.php  ✓
│   ├── class-osmose-ads-activator.php  ✓
│   ├── class-osmose-ads-deactivator.php  ✓
│   ├── class-osmose-ads-i18n.php  ✓
│   ├── class-osmose-ads-post-types.php  ✓
│   ├── class-osmose-ads-rewrite.php  ✓
│   ├── models/
│   │   ├── class-ad.php  ✓
│   │   └── class-ad-template.php  ✓
│   └── services/
│       ├── class-ai-service.php  ✓
│       ├── class-city-content-personalizer.php  ✓
│       └── class-france-geo-api.php  ✓
├── admin/
│   ├── class-osmose-ads-admin.php  ✓
│   └── ajax-handlers.php  ✓
└── public/
    └── class-osmose-ads-public.php  ✓
```

Si des fichiers manquent :
1. Téléchargez le plugin complet depuis GitHub
2. Supprimez le dossier `osmose-ads` sur le serveur
3. Re-uploadez tout le dossier

---

## 🎯 Solution #5 : Script de Réparation Automatique

Créez un fichier `repair-osmose.php` dans `/wp-content/plugins/osmose-ads/` :

```php
<?php
/**
 * Script de réparation Osmose ADS
 * Accès : wp-admin/admin.php?page=osmose-ads-repair
 */

// Charger WordPress
require_once('../../../wp-load.php');

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

echo '<h1>Réparation Osmose ADS</h1>';

// Vérifier et réparer la table
global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_call_tracking';

// Vérifier si la table existe
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    echo '<p>✅ Table trouvée : ' . $table_name . '</p>';
    
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
        echo '<p>❌ Colonne "source" manquante. Réparation en cours...</p>';
        
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN source varchar(50) AFTER referrer");
        
        echo '<p>✅ Colonne "source" ajoutée !</p>';
    } else {
        echo '<p>✅ Colonne "source" présente</p>';
    }
} else {
    echo '<p>❌ Table manquante. Recréation...</p>';
    
    // Activer le plugin pour recréer les tables
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    require_once(OSMOSE_ADS_PLUGIN_DIR . 'includes/class-osmose-ads-activator.php');
    
    Osmose_Ads_Activator::activate();
    
    echo '<p>✅ Tables recréées !</p>';
}

echo '<p><strong>Réparation terminée. <a href="' . admin_url() . '">Retour au tableau de bord</a></strong></p>';
```

Puis visitez :
```
https://bretagne.normesrenovation.fr/wp-content/plugins/osmose-ads/repair-osmose.php
```

---

## 📊 Ordre des Solutions à Essayer

1. **Solution #1** (Base de données) - 80% de chances que ce soit ça
2. **Solution #3** (Activer le debug) - Pour voir l'erreur exacte
3. **Solution #2** (Désactiver/Réactiver) - Si #1 ne marche pas
4. **Solution #4** (Vérifier les fichiers) - Si erreur "Class not found"
5. **Solution #5** (Script de réparation) - En dernier recours

---

## ❓ Erreurs Courantes et Solutions

### "Fatal error: Uncaught Error: Call to undefined method"
→ Un fichier de classe est manquant → Solution #4

### "Database error" ou "Column 'source' doesn't exist"
→ La colonne est manquante → Solution #1

### "Class 'Ad' not found"
→ Les fichiers modèles sont manquants → Solution #4

### "Headers already sent"
→ Un fichier PHP a un espace avant `<?php` → Ré-uploadez les fichiers

---

## 🆘 Besoin d'Aide ?

Si aucune solution ne fonctionne, envoyez-moi :
1. Le contenu du fichier `/wp-content/debug.log` (dernières lignes)
2. Version PHP (visible dans cPanel ou wp-admin)
3. Version WordPress
4. Capture d'écran de l'erreur

---

## ⚠️ IMPORTANT

Avant toute manipulation, **faites une sauvegarde** de :
- La base de données (via phpMyAdmin → Exporter)
- Le dossier `/wp-content/plugins/osmose-ads/`

