# Guide de débogage - Import de villes via API

## Corrections apportées

### 1. Enregistrement des handlers AJAX
- **Problème** : Les handlers AJAX étaient enregistrés dans `admin_init`, ce qui pouvait être trop tard
- **Solution** : Enregistrement sur `plugins_loaded` avec priorité 20 pour garantir qu'ils sont prêts avant les requêtes

### 2. Logs de débogage
Des logs détaillés ont été ajoutés pour diagnostiquer les problèmes :
- Dans les handlers AJAX (`ajax_import_cities`, `ajax_get_departments`, etc.)
- Dans l'API (`get_communes_by_department`, `get_communes_by_region`)
- Dans le JavaScript (console.log)

### 3. Gestion d'erreurs améliorée
- Vérification des réponses API
- Messages d'erreur détaillés
- Gestion des timeouts et erreurs de connexion

## Comment diagnostiquer un problème

### 1. Activer le mode debug WordPress
Ajoutez dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 2. Vérifier les logs
Les logs sont écrits dans `/wp-content/debug.log`. Cherchez les lignes commençant par `Osmose ADS:`.

### 3. Vérifier la console JavaScript
Ouvrez la console du navigateur (F12) et regardez les messages de débogage.

### 4. Tester l'API directement
Testez l'endpoint de l'API directement :
```bash
curl "https://geo.api.gouv.fr/departements/75/communes?fields=nom,code"
```

### 5. Vérifier que les handlers sont enregistrés
Dans la console JavaScript, testez :
```javascript
console.log('AJAX URL:', osmoseAds.ajax_url);
console.log('Nonce:', osmoseAds.nonce);
```

## Problèmes courants et solutions

### Problème : "Erreur de sécurité - nonce invalide"
**Cause** : Le nonce n'est pas correctement passé ou a expiré
**Solution** : Rechargez la page pour obtenir un nouveau nonce

### Problème : "Permissions insuffisantes"
**Cause** : L'utilisateur n'a pas les droits `manage_options`
**Solution** : Connectez-vous avec un compte administrateur

### Problème : "Erreur API : code 403"
**Cause** : L'API bloque la requête (rate limiting ou problème de configuration)
**Solution** : Vérifiez que le serveur peut faire des requêtes HTTP externes

### Problème : "Timeout"
**Cause** : L'import prend trop de temps
**Solution** : 
- Essayez avec un département/une région plus petit(e)
- Augmentez le timeout dans `wp-config.php` : `set_time_limit(300);`

### Problème : Aucune réponse AJAX
**Cause** : Les handlers ne sont pas enregistrés
**Solution** : Vérifiez dans `debug.log` si vous voyez "Osmose ADS: AJAX handlers registered"

## Test manuel de l'API

Pour tester si l'API fonctionne depuis WordPress, vous pouvez créer un script de test temporaire :

```php
<?php
// Ajoutez ceci temporairement dans functions.php ou un fichier de test

require_once plugin_dir_path(__FILE__) . 'osmose-ads/includes/services/class-france-geo-api.php';

$api = new France_Geo_API();

// Test avec Paris (75)
$communes = $api->get_communes_by_department('75');

if (is_wp_error($communes)) {
    echo 'Erreur: ' . $communes->get_error_message();
} else {
    echo 'Succès: ' . count($communes) . ' communes trouvées';
    echo '<pre>';
    print_r(array_slice($communes, 0, 3)); // Afficher les 3 premières
    echo '</pre>';
}
```

## Support

Si le problème persiste :
1. Vérifiez les logs WordPress (`debug.log`)
2. Vérifiez la console JavaScript du navigateur
3. Testez l'API directement avec curl
4. Vérifiez que votre serveur peut faire des requêtes HTTPS externes

