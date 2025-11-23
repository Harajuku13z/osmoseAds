# Guide de résolution de problèmes - Import de villes

## Problème : Les départements et régions ne se chargent pas

### Étape 1 : Vérifier la console JavaScript

1. Ouvrez la page des villes dans WordPress : `wp-admin/admin.php?page=osmose-ads-cities`
2. Appuyez sur **F12** pour ouvrir les outils de développement
3. Allez dans l'onglet **Console**
4. Rechargez la page

Vous devriez voir des messages qui commencent par `Osmose ADS:`. Notez tous les messages d'erreur.

### Étape 2 : Vérifier que l'objet osmoseAds existe

Dans la console JavaScript, tapez :
```javascript
console.log(window.osmoseAds);
```

Si vous voyez `undefined`, c'est que l'objet n'a pas été créé. Vous devriez voir :
```javascript
{
  ajax_url: "...",
  nonce: "...",
  plugin_url: "..."
}
```

### Étape 3 : Tester un appel AJAX manuellement

Dans la console JavaScript, testez :
```javascript
jQuery.ajax({
    url: window.osmoseAds.ajax_url,
    type: 'POST',
    dataType: 'json',
    data: {
        action: 'osmose_ads_get_departments',
        nonce: window.osmoseAds.nonce
    },
    success: function(response) {
        console.log('Réponse:', response);
    },
    error: function(xhr, status, error) {
        console.error('Erreur:', status, error, xhr.responseText);
    }
});
```

### Étape 4 : Vérifier les logs WordPress

1. Activez le mode debug dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Vérifiez le fichier `/wp-content/debug.log`
3. Cherchez les lignes qui commencent par `Osmose ADS:`

### Étape 5 : Vérifier que les handlers AJAX sont enregistrés

Dans `debug.log`, vous devriez voir :
```
Osmose ADS: AJAX handlers registered for cities
```

Si vous ne voyez pas ce message, les handlers ne sont pas enregistrés.

### Solutions courantes

#### Solution 1 : Les handlers AJAX ne sont pas enregistrés

**Cause** : Les handlers AJAX sont enregistrés trop tard.

**Solution** : Videz le cache WordPress et rechargez la page.

#### Solution 2 : Le nonce est invalide

**Cause** : Le nonce a expiré ou n'est pas correct.

**Solution** : Rechargez simplement la page pour obtenir un nouveau nonce.

#### Solution 3 : Problème de permissions

**Cause** : L'utilisateur n'a pas les droits nécessaires.

**Solution** : Connectez-vous avec un compte administrateur.

#### Solution 4 : Problème de connexion à l'API

**Cause** : Le serveur ne peut pas se connecter à l'API externe.

**Solution** : 
- Vérifiez que votre serveur peut faire des requêtes HTTPS externes
- Vérifiez les logs WordPress pour voir les erreurs d'API
- Contactez votre hébergeur si nécessaire

#### Solution 5 : Le script JavaScript ne se charge pas

**Cause** : Le fichier JavaScript n'est pas chargé ou il y a une erreur JavaScript.

**Solution** :
1. Vérifiez la console pour les erreurs JavaScript
2. Vérifiez que jQuery est chargé
3. Vérifiez que le fichier `admin/js/osmose-ads-admin.js` existe

### Test rapide

Pour tester rapidement si tout fonctionne, ouvrez la console et exécutez :

```javascript
// Vérifier que jQuery est chargé
console.log('jQuery:', typeof jQuery);

// Vérifier que osmoseAds existe
console.log('osmoseAds:', window.osmoseAds);

// Tester l'API directement
if (window.osmoseAds) {
    jQuery.ajax({
        url: window.osmoseAds.ajax_url,
        type: 'POST',
        data: {
            action: 'osmose_ads_get_departments',
            nonce: window.osmoseAds.nonce
        },
        success: function(r) {
            console.log('✅ SUCCÈS:', r);
        },
        error: function(xhr) {
            console.error('❌ ERREUR:', xhr.status, xhr.responseText);
        }
    });
}
```

### Contacter le support

Si le problème persiste après avoir suivi ces étapes :
1. Copiez tous les messages de la console JavaScript
2. Copiez les erreurs du fichier `debug.log`
3. Fournissez ces informations pour diagnostic
