# Guide de diagnostic pour l'erreur d'activation

## Problème corrigé ✅

J'ai corrigé un problème dans `includes/class-osmose-ads-email.php` où `get_bloginfo('name')` était utilisé trop tôt lors de l'activation.

## Comment voir l'erreur exacte

### Méthode 1 : Via l'interface WordPress

1. Va dans **Réglages → Réglages généraux**
2. Active le mode debug en ajoutant dans `wp-config.php` :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
3. Essaie d'activer le plugin à nouveau
4. Regarde le fichier `/wp-content/debug.log` pour voir l'erreur exacte

### Méthode 2 : Via les logs du serveur

- Regarde les logs d'erreur PHP de ton serveur (généralement dans `/var/log/` ou via cPanel)
- Cherche les erreurs récentes avec "osmose" ou "Osmose_Ads"

### Méthode 3 : Via le terminal (si tu as accès SSH)

```bash
cd /chemin/vers/ton/site/wp-content/plugins/osmose-ads
php -l osmose-ads.php
php -l includes/class-osmose-ads.php
php -l includes/class-osmose-ads-email.php
```

## Erreurs courantes et solutions

### Erreur : "Call to undefined function get_bloginfo()"
✅ **Corrigé** - J'ai ajouté des vérifications pour s'assurer que WordPress est chargé avant d'utiliser `get_bloginfo()`

### Erreur : "Class 'Osmose_Ads_Loader' not found"
- Vérifie que le fichier `includes/class-osmose-ads-loader.php` existe
- Vérifie les permissions des fichiers

### Erreur : "Cannot redeclare class"
- Un autre plugin ou le thème utilise peut-être le même nom de classe
- Désactive temporairement les autres plugins pour tester

### Erreur de syntaxe PHP
- Utilise `php -l nom-du-fichier.php` pour vérifier chaque fichier
- Tous les fichiers ont été vérifiés et sont OK

## Prochaines étapes

1. **Essaie d'activer le plugin à nouveau** - Le problème principal devrait être corrigé
2. **Si ça ne marche toujours pas**, envoie-moi :
   - Le message d'erreur exact (copie-colle)
   - Le contenu de `/wp-content/debug.log` (les dernières lignes)
   - La version de PHP (`php -v`)

## Fichiers modifiés récemment

- `includes/class-osmose-ads-email.php` - Correction de l'utilisation de `get_bloginfo()`
- `public/templates/single-ad.php` - Ajout du code postal dans les titres
- `admin/partials/settings.php` - Ajout des réglages SMTP
- `public/class-osmose-ads-public.php` - Ajout du bot meta-externalagent

Tous ces fichiers ont été vérifiés pour les erreurs de syntaxe et sont OK.


