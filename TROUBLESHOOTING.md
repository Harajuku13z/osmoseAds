# Dépannage - Osmose ADS

## Problème : Liste des villes n'apparaît pas

### Solution 1 : Vérifier les onglets

1. Sur la page Villes, vérifiez que l'onglet **"Liste des Villes"** est actif (bleu)
2. Si non, cliquez dessus pour l'activer
3. La liste devrait apparaître

### Solution 2 : Vérifier les villes existantes

1. Allez dans **WordPress Admin > Articles > Tous les articles**
2. Filtrez par type "Ville"
3. Vérifiez qu'il y a bien des villes publiées

### Solution 3 : Vérifier JavaScript

1. Ouvrez la console du navigateur (F12)
2. Vérifiez s'il y a des erreurs JavaScript
3. Si oui, notez-les et contactez le support

### Solution 4 : Recharger la page

1. Faites un Ctrl+F5 (ou Cmd+Shift+R sur Mac) pour forcer le rechargement
2. Videz le cache du navigateur si nécessaire

## Problème : Logo ne s'affiche pas

### Solution 1 : Placer le logo au bon endroit

Le logo doit être placé dans un de ces emplacements :

1. **`/wp-content/plugins/osmose-ads/admin/img/logo.jpg`** (recommandé)
2. **`/wp-content/plugins/osmose-ads/admin/img/logo.png`**
3. **`/wp-content/plugins/osmose-ads/../logo.jpg`** (racine du projet)
4. **Racine WordPress : `/logo.jpg`**

### Solution 2 : Créer le dossier img

Si le dossier n'existe pas :

```bash
mkdir -p /wp-content/plugins/osmose-ads/admin/img
```

Puis placez votre logo `logo.jpg` ou `logo.png` dans ce dossier.

### Solution 3 : Vérifier les permissions

Le logo doit être lisible :
- Permissions : 644
- Propriétaire : www-data (ou l'utilisateur du serveur web)

### Solution 4 : Vérifier le format

Le logo doit être :
- Format : JPG ou PNG
- Taille : 200-400px de largeur recommandé
- Nom : `logo.jpg` ou `logo.png` (minuscules)

### Solution 5 : Tester l'URL du logo

Dans votre navigateur, essayez d'accéder directement à :
```
https://votre-site.com/wp-content/plugins/osmose-ads/admin/img/logo.jpg
```

Si vous obtenez une erreur 404, le logo n'est pas au bon endroit.

## Problème : Interface ne se charge pas

### Solution 1 : Vérifier les permissions

Les fichiers doivent avoir les bonnes permissions :
- Fichiers : 644
- Dossiers : 755

### Solution 2 : Vider le cache

1. Videz le cache WordPress (si plugin de cache installé)
2. Videz le cache du navigateur
3. Videz le cache opcode PHP si activé

### Solution 3 : Vérifier les erreurs

1. Activez le mode debug dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Vérifiez le fichier `/wp-content/debug.log` pour les erreurs

## Problème : Import de villes ne fonctionne pas

### Solution 1 : Vérifier la connexion internet

L'API GeoAPI nécessite une connexion internet. Vérifiez que :
- Le serveur a accès à internet
- Aucun firewall ne bloque `https://geo.api.gouv.fr`

### Solution 2 : Tester l'API manuellement

Testez dans votre navigateur :
```
https://geo.api.gouv.fr/departements
```

Vous devriez voir une liste JSON de départements.

### Solution 3 : Vérifier les logs

Consultez les logs WordPress pour voir les erreurs d'API.

## Problème : Styles CSS ne s'appliquent pas

### Solution 1 : Vider le cache

1. Videz le cache du navigateur (Ctrl+F5)
2. Videz le cache WordPress

### Solution 2 : Vérifier que les CSS sont chargés

1. Faites clic droit > Inspecter l'élément
2. Vérifiez dans l'onglet "Network" que `osmose-ads-admin.css` est chargé
3. Si non, vérifiez les permissions du fichier

## Problème : JavaScript ne fonctionne pas

### Solution 1 : Vérifier jQuery

1. Ouvrez la console (F12)
2. Tapez : `typeof jQuery`
3. Si ça retourne "undefined", jQuery n'est pas chargé

### Solution 2 : Vérifier les erreurs

1. Console > Onglet "Console"
2. Vérifiez s'il y a des erreurs rouges
3. Notez-les pour le support

## Contact Support

Si le problème persiste :
1. Notez les erreurs de la console
2. Vérifiez les logs WordPress
3. Notez votre version WordPress et PHP
4. Contactez le support avec ces informations

