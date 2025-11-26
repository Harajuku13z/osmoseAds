# Instructions pour déboguer l'erreur critique WordPress

## Étape 1 : Activer le mode debug

Connectez-vous à votre serveur via FTP ou cPanel et éditez le fichier `wp-config.php` à la racine de votre site WordPress.

Trouvez ces lignes (généralement vers la ligne 80) :

```php
define('WP_DEBUG', false);
```

Et remplacez-les par :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

**Sauvegardez le fichier.**

## Étape 2 : Reproduire l'erreur

1. Visitez l'URL qui pose problème : https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
2. L'erreur sera enregistrée dans un fichier de log

## Étape 3 : Consulter le fichier de log

Allez dans le dossier `/wp-content/` de votre site et ouvrez le fichier `debug.log`

Ce fichier contiendra le message d'erreur exact avec :
- Le nom du fichier qui cause l'erreur
- Le numéro de ligne
- Le type d'erreur (Fatal error, Warning, etc.)

## Étape 4 : M'envoyer le contenu du log

Copiez les dernières lignes du fichier `debug.log` (surtout les lignes qui mentionnent "Fatal error" ou "Osmose ADS") et envoyez-les moi.

## Solutions rapides à essayer en attendant

### Solution 1 : Désactiver temporairement le plugin
Dans `/wp-content/plugins/`, renommez le dossier `osmose-ads` en `osmose-ads-disabled`
Le site devrait refonctionner (mais sans les annonces).

### Solution 2 : Vérifier les fichiers du plugin
Assurez-vous que tous les fichiers ont été uploadés correctement, notamment :
- `/wp-content/plugins/osmose-ads/includes/class-osmose-ads.php`
- `/wp-content/plugins/osmose-ads/includes/class-osmose-ads-loader.php`
- `/wp-content/plugins/osmose-ads/includes/models/class-ad.php`
- `/wp-content/plugins/osmose-ads/includes/models/class-ad-template.php`
- `/wp-content/plugins/osmose-ads/includes/services/class-ai-service.php`
- `/wp-content/plugins/osmose-ads/includes/services/class-city-content-personalizer.php`
- `/wp-content/plugins/osmose-ads/includes/services/class-france-geo-api.php`

### Solution 3 : Réinstaller le plugin
1. Désactivez le plugin dans WordPress (si possible)
2. Supprimez le dossier `osmose-ads`
3. Re-téléchargez depuis GitHub : https://github.com/Harajuku13z/osmoseAds
4. Re-uploadez tout le dossier
5. Réactivez le plugin

### Solution 4 : Vérifier la version PHP
Le plugin nécessite PHP 7.4 ou supérieur. Vérifiez votre version PHP dans cPanel ou via phpinfo().

## Erreurs courantes et solutions

### Erreur : "Class not found"
**Solution** : Un fichier de classe est manquant. Ré-uploadez tous les fichiers du plugin.

### Erreur : "Call to undefined function"
**Solution** : Une extension PHP est manquante. Activez les extensions `curl`, `json`, `mbstring`.

### Erreur : "Cannot redeclare class"
**Solution** : Le plugin est chargé deux fois. Vérifiez qu'il n'y a qu'une seule copie dans `/wp-content/plugins/`.

### Erreur : SQL syntax error
**Solution** : Exécutez cette requête dans phpMyAdmin :
```sql
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

## Besoin d'aide ?

Envoyez-moi :
1. Le contenu du fichier `debug.log`
2. Votre version de PHP
3. Votre version de WordPress
4. La liste des autres plugins actifs

