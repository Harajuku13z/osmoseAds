# 🖥️ Instructions SSH pour Activer le Mode Debug WordPress

Ce guide vous explique comment activer le mode debug WordPress via SSH pour identifier l'erreur critique.

---

## 📋 Pré-requis

- Accès SSH à votre serveur
- Nom d'utilisateur et mot de passe SSH (fournis par votre hébergeur)
- Terminal : 
  - **Mac/Linux** : Terminal (préinstallé)
  - **Windows** : PowerShell, CMD, ou [PuTTY](https://www.putty.org/)

---

## 🚀 Méthode 1 : Script Automatique (RECOMMANDÉ)

### Étape 1 : Se connecter en SSH

```bash
ssh votre-utilisateur@bretagne.normesrenovation.fr
# ou
ssh votre-utilisateur@votre-ip
```

Entrez votre mot de passe quand demandé.

### Étape 2 : Aller dans le dossier WordPress

```bash
# Trouver le dossier WordPress (généralement)
cd public_html
# ou
cd www
# ou
cd htdocs

# Vérifier qu'on est au bon endroit
ls -la wp-config.php
```

Si vous voyez `wp-config.php`, vous êtes au bon endroit ✅

### Étape 3 : Télécharger et exécuter le script

```bash
# Télécharger le script depuis GitHub
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh

# Rendre le script exécutable
chmod +x enable-wp-debug.sh

# Exécuter le script
bash enable-wp-debug.sh
```

**C'est tout !** Le script va :
- ✅ Trouver automatiquement wp-config.php
- ✅ Créer une sauvegarde
- ✅ Activer le mode debug
- ✅ Créer le fichier debug.log
- ✅ Vous dire où consulter les erreurs

### Étape 4 : Reproduire l'erreur

Visitez la page qui pose problème :
```
https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
```

### Étape 5 : Consulter les erreurs

```bash
# Voir les dernières erreurs en temps réel
tail -f wp-content/debug.log

# Appuyez sur Ctrl+C pour arrêter

# Voir les 50 dernières lignes
tail -50 wp-content/debug.log

# Chercher les erreurs Osmose ADS
grep -i "osmose" wp-content/debug.log

# Chercher les erreurs fatales
grep -i "fatal" wp-content/debug.log
```

### Étape 6 : M'envoyer les erreurs

```bash
# Copier les dernières erreurs dans un fichier
tail -100 wp-content/debug.log > erreurs-osmose.txt

# Télécharger le fichier via SCP (depuis votre ordinateur)
scp votre-utilisateur@bretagne.normesrenovation.fr:~/public_html/erreurs-osmose.txt ./
```

Envoyez-moi le contenu de `erreurs-osmose.txt`.

### Étape 7 : Désactiver le debug (après réparation)

```bash
# Télécharger le script de désactivation
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh

chmod +x disable-wp-debug.sh
bash disable-wp-debug.sh
```

---

## 🔧 Méthode 2 : Édition Manuelle de wp-config.php

Si vous préférez éditer manuellement :

### Étape 1 : Se connecter et naviguer

```bash
ssh votre-utilisateur@bretagne.normesrenovation.fr
cd public_html  # ou www ou htdocs
```

### Étape 2 : Créer une sauvegarde

```bash
cp wp-config.php wp-config.php.backup
```

### Étape 3 : Éditer wp-config.php

Avec `nano` (éditeur simple) :
```bash
nano wp-config.php
```

Ou avec `vi` :
```bash
vi wp-config.php
```

### Étape 4 : Ajouter les lignes de debug

Cherchez cette ligne (généralement vers la ligne 80) :
```php
define('WP_DEBUG', false);
```

**Remplacez-la par :**
```php
// ========================================
// MODE DEBUG ACTIVÉ
// ========================================
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
// ========================================
```

### Étape 5 : Sauvegarder

**Avec nano :**
- Appuyez sur `Ctrl + O` (pour sauvegarder)
- Appuyez sur `Entrée` (confirmer)
- Appuyez sur `Ctrl + X` (pour quitter)

**Avec vi :**
- Appuyez sur `Esc`
- Tapez `:wq` et appuyez sur `Entrée`

### Étape 6 : Vérifier que c'est correct

```bash
grep -A5 "WP_DEBUG" wp-config.php
```

Vous devriez voir :
```
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Étape 7 : Consulter les erreurs

Suivez les mêmes instructions que la Méthode 1 (Étapes 4-6).

---

## 📱 Méthode 3 : Via Application Mobile SSH

Si vous utilisez une app mobile comme **Termius** ou **JuiceSSH** :

1. Connectez-vous à votre serveur
2. Exécutez ces commandes une par une :

```bash
cd public_html
cp wp-config.php wp-config.php.backup
sed -i "s/define('WP_DEBUG', false);/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);/" wp-config.php
```

3. Reproduisez l'erreur sur votre site
4. Consultez le log :

```bash
tail -50 wp-content/debug.log
```

---

## 🎯 Commandes Utiles

### Vérifier la structure du site

```bash
# Voir la structure des dossiers
ls -la

# Vérifier que WordPress est bien là
ls -la wp-config.php wp-content wp-admin
```

### Vérifier les permissions

```bash
# Permissions de wp-config.php (devrait être 644 ou 600)
ls -l wp-config.php

# Changer les permissions si nécessaire
chmod 644 wp-config.php
```

### Créer le fichier debug.log manuellement

```bash
touch wp-content/debug.log
chmod 666 wp-content/debug.log
```

### Vider le fichier debug.log

```bash
# Vider le contenu (pour repartir de zéro)
> wp-content/debug.log

# Ou le supprimer
rm wp-content/debug.log
```

### Chercher des erreurs spécifiques

```bash
# Erreurs fatales
grep -i "fatal" wp-content/debug.log

# Erreurs Osmose
grep -i "osmose" wp-content/debug.log

# Erreurs de base de données
grep -i "database\|mysql" wp-content/debug.log

# Erreurs de classe non trouvée
grep -i "class.*not found" wp-content/debug.log
```

### Télécharger le fichier debug.log sur votre ordinateur

**Depuis votre ordinateur (pas en SSH) :**

```bash
# Via SCP
scp votre-utilisateur@bretagne.normesrenovation.fr:public_html/wp-content/debug.log ./debug-osmose.log

# Via rsync
rsync -avz votre-utilisateur@bretagne.normesrenovation.fr:public_html/wp-content/debug.log ./
```

---

## 🔍 Interpréter les Erreurs

### Erreur : "Fatal error: Class 'Osmose_Ads' not found"
**Solution :** Fichier de classe manquant → Ré-uploadez le plugin

### Erreur : "Column 'source' doesn't exist"
**Solution :** Exécutez cette commande SQL :
```bash
wp db query "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN source varchar(50);"
```

### Erreur : "Call to undefined function"
**Solution :** Extension PHP manquante
```bash
# Vérifier les extensions PHP
php -m | grep -E "curl|json|mbstring"
```

---

## 🛡️ Sécurité

### Désactiver le debug après diagnostic

**Ne laissez JAMAIS le debug activé en production !**

```bash
# Méthode 1 : Script automatique
bash disable-wp-debug.sh

# Méthode 2 : Manuelle
nano wp-config.php
# Changez true en false pour WP_DEBUG

# Méthode 3 : Restaurer la sauvegarde
cp wp-config.php.backup wp-config.php
```

### Protéger le fichier debug.log

```bash
# Empêcher l'accès web au fichier debug.log
echo "deny from all" > wp-content/.htaccess
```

---

## 🆘 Aide Supplémentaire

### Vous ne trouvez pas wp-config.php ?

```bash
# Chercher wp-config.php dans tous les dossiers
find ~ -name "wp-config.php" 2>/dev/null
```

### Vous n'avez pas les permissions ?

```bash
# Voir qui est le propriétaire
ls -l wp-config.php

# Si nécessaire, contactez votre hébergeur pour :
# - Obtenir les permissions nécessaires
# - Ou demandez-leur d'activer le debug
```

### Connexion SSH refusée ?

Vérifiez avec votre hébergeur :
- L'accès SSH est-il activé ?
- Le port SSH (généralement 22)
- Votre nom d'utilisateur SSH
- Votre mot de passe SSH

---

## 📞 Contact

Si vous rencontrez des difficultés :

1. Copiez les erreurs du debug.log
2. Prenez une capture d'écran
3. Notez :
   - Version PHP (`php -v`)
   - Version WordPress
   - Hébergeur
4. Envoyez-moi ces informations

---

## 🎬 Résumé Ultra-Rapide

```bash
# 1. Se connecter
ssh user@bretagne.normesrenovation.fr

# 2. Aller dans WordPress
cd public_html

# 3. Activer le debug
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh
bash enable-wp-debug.sh

# 4. Reproduire l'erreur (visitez le site)

# 5. Voir les erreurs
tail -50 wp-content/debug.log

# 6. M'envoyer le résultat

# 7. Désactiver après
bash disable-wp-debug.sh
```

**C'est tout !** 🎉

