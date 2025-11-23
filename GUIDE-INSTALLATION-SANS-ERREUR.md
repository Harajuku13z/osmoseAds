# Guide d'Installation sans Erreur - Osmose ADS

## ⚠️ IMPORTANT : Étapes pour éviter les erreurs critiques

### Prérequis

Avant d'installer, vérifiez que votre environnement répond aux exigences :

- ✅ **WordPress** : Version 5.0 ou supérieure
- ✅ **PHP** : Version 7.4 ou supérieure
- ✅ **Mémoire PHP** : Au moins 128MB (recommandé 256MB)
- ✅ **Permissions** : Droits d'écriture pour les fichiers

### Vérification de l'environnement

1. **Vérifier la version PHP**
   - Connectez-vous à votre hébergement
   - Vérifiez la version PHP (minimum 7.4)
   - Si version inférieure, contactez votre hébergeur

2. **Vérifier la mémoire PHP**
   - Ajoutez dans `wp-config.php` :
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

3. **Activer le mode debug temporairement**
   - Dans `wp-config.php`, changez :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
   - Cela permettra de voir les erreurs dans `wp-content/debug.log`

## Installation étape par étape

### Étape 1 : Préparation

1. **Sauvegarder votre site**
   - Faites une sauvegarde complète (base de données + fichiers)
   - Utilisez un plugin comme UpdraftPlus ou la sauvegarde de votre hébergeur

2. **Désactiver les autres plugins temporairement** (recommandé)
   - Allez dans **Extensions**
   - Désactivez tous les plugins sauf Osmose ADS
   - Cela permet d'identifier les conflits éventuels

### Étape 2 : Installation du plugin

#### Méthode A : Via FTP/SFTP (Recommandée)

1. **Télécharger le plugin**
   - Assurez-vous d'avoir le dossier complet `osmose-ads`

2. **Se connecter via FTP**
   - Utilisez FileZilla ou votre client FTP
   - Connectez-vous à votre serveur

3. **Naviguer vers le dossier plugins**
   - Allez dans `/wp-content/plugins/`
   - **IMPORTANT** : Le dossier doit s'appeler exactement `osmose-ads`

4. **Uploader le plugin**
   - Uploadez TOUT le dossier `osmose-ads` dans `/wp-content/plugins/`
   - Structure finale : `/wp-content/plugins/osmose-ads/osmose-ads.php`

5. **Vérifier les permissions**
   - Les fichiers doivent être en `644`
   - Les dossiers doivent être en `755`

#### Méthode B : Via l'interface WordPress

1. **Créer un fichier ZIP**
   - Compressez le dossier `osmose-ads` en ZIP
   - Nom du fichier : `osmose-ads.zip`

2. **Uploader dans WordPress**
   - Allez dans **Extensions > Ajouter**
   - Cliquez sur **Téléverser une extension**
   - Sélectionnez `osmose-ads.zip`
   - Cliquez sur **Installer maintenant**

### Étape 3 : Activation

1. **Activer le plugin**
   - Allez dans **Extensions**
   - Trouvez "Osmose ADS"
   - Cliquez sur **Activer**

2. **Vérifier qu'il n'y a pas d'erreur**
   - Si une erreur apparaît, notez le message exact
   - Vérifiez `wp-content/debug.log` pour plus de détails

### Étape 4 : Configuration initiale

1. **Flush des permaliinks** (CRUCIAL)
   - Allez dans **Réglages > Permaliinks**
   - Ne changez rien, cliquez simplement sur **Enregistrer**
   - Cela régénère les règles de réécriture

2. **Configurer les réglages**
   - Allez dans **Osmose ADS > Réglages**
   - Configurez au minimum :
     - Téléphone de l'entreprise
     - Liste des services

3. **Ajouter des villes**
   - Allez dans **Osmose ADS > Villes**
   - Ajoutez au moins une ville pour tester

## Problèmes courants et solutions

### ❌ Erreur : "Fatal error: Cannot redeclare class"

**Cause** : Le plugin est chargé deux fois ou un conflit avec un autre plugin

**Solution** :
1. Vérifiez qu'il n'y a pas deux copies du plugin
2. Désactivez tous les autres plugins
3. Réactivez-les un par un pour identifier le conflit

### ❌ Erreur : "Call to undefined function"

**Cause** : Fonction WordPress non disponible ou plugin activé trop tôt

**Solution** :
1. Vérifiez votre version de WordPress (minimum 5.0)
2. Mettez à jour WordPress si nécessaire
3. Vérifiez que le plugin est bien dans `/wp-content/plugins/`

### ❌ Erreur : "Permission denied"

**Cause** : Permissions de fichiers incorrectes

**Solution** :
1. Via FTP, modifiez les permissions :
   - Fichiers : `644`
   - Dossiers : `755`
2. Le dossier `osmose-ads` doit être accessible en lecture

### ❌ Erreur : "Headers already sent"

**Cause** : Espaces ou caractères avant/après les balises PHP

**Solution** :
1. Vérifiez qu'il n'y a pas d'espaces avant `<?php`
2. Vérifiez qu'il n'y a pas de saut de ligne après `?>`
3. Les fichiers doivent commencer par `<?php` et ne pas avoir de `?>` à la fin

### ❌ Les pages ne s'affichent pas

**Cause** : Permaliinks non configurés

**Solution** :
1. Allez dans **Réglages > Permaliinks**
2. Cliquez sur **Enregistrer** (même sans changement)
3. Videz le cache si vous utilisez un plugin de cache

### ❌ Menu admin non visible

**Cause** : Permissions utilisateur insuffisantes

**Solution** :
1. Assurez-vous d'être connecté en tant qu'administrateur
2. Vérifiez vos capabilities dans **Utilisateurs > Votre profil**

## Vérification après installation

### Checklist de vérification

- [ ] Le plugin s'active sans erreur
- [ ] Le menu "Osmose ADS" apparaît dans l'admin
- [ ] Les pages admin s'affichent correctement
- [ ] Les permaliinks sont régénérés
- [ ] Au moins une ville est ajoutée
- [ ] Les services sont configurés

### Test rapide

1. **Créer un template de test**
   - Allez dans **Osmose ADS > Templates**
   - Créez un template simple (sans IA pour le test)

2. **Créer une annonce de test**
   - Allez dans **Osmose ADS > Génération en Masse**
   - Sélectionnez un service
   - Sélectionnez une ville
   - Générez une annonce

3. **Vérifier l'affichage public**
   - Visitez l'URL de l'annonce
   - Vérifiez que le contenu s'affiche correctement

## Activation du mode debug (optionnel)

Pour voir toutes les erreurs potentielles :

1. Ajoutez dans `wp-config.php` (avant "C'est tout") :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

2. Les erreurs seront dans : `/wp-content/debug.log`

3. **IMPORTANT** : Désactivez le debug en production :
```php
define('WP_DEBUG', false);
```

## Support

Si vous rencontrez toujours des problèmes :

1. Vérifiez le fichier `wp-content/debug.log`
2. Notez le message d'erreur exact
3. Vérifiez votre version de WordPress et PHP
4. Contactez le support avec ces informations

## Configuration minimale pour démarrer

1. **Activer le plugin** ✅
2. **Flush permaliinks** ✅
3. **Ajouter 1 service** dans Réglages
4. **Ajouter 1 ville** dans Villes
5. **Créer un template** (manuel pour tester)
6. **Générer 1 annonce** pour vérifier

Une fois ces étapes réussies, vous pouvez commencer à utiliser toutes les fonctionnalités !

