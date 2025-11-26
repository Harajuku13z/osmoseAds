# ⚡ Commandes Rapides SSH - WordPress Debug

## 🎯 Commande One-Liner (ULTRA-RAPIDE)

Copiez-collez cette ligne directement en SSH (active le debug automatiquement) :

```bash
cd public_html && cp wp-config.php wp-config.php.backup-$(date +%Y%m%d) && sed -i.old "/define.*'WP_DEBUG'/d; /define.*'WP_DEBUG_LOG'/d; /define.*'WP_DEBUG_DISPLAY'/d" wp-config.php && sed -i "s/\(\/\* C'est tout\|\/\* That's all\)/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);\n@ini_set('display_errors', 0);\n\n\1/" wp-config.php && touch wp-content/debug.log && chmod 666 wp-content/debug.log && echo "✅ Debug activé ! Consultez : tail -f wp-content/debug.log"
```

---

## 📝 Commandes Pas à Pas

### 1️⃣ Se connecter et naviguer

```bash
ssh votre-utilisateur@bretagne.normesrenovation.fr
cd public_html  # ou cd www, ou cd htdocs
```

### 2️⃣ Sauvegarder wp-config.php

```bash
cp wp-config.php wp-config.php.backup
```

### 3️⃣ Activer WP_DEBUG (choix multiple)

**Option A - Avec sed (automatique) :**
```bash
sed -i "s/define('WP_DEBUG', false);/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);/" wp-config.php
```

**Option B - Avec echo (simple) :**
```bash
# Supprimer l'ancien WP_DEBUG
sed -i "/define.*'WP_DEBUG'/d" wp-config.php

# Ajouter le nouveau
sed -i "s/\(\/\* C'est tout\)/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);\n\n\1/" wp-config.php
```

**Option C - Avec nano (manuel) :**
```bash
nano wp-config.php
# Cherchez WP_DEBUG et modifiez manuellement
# Ctrl+O pour sauvegarder, Ctrl+X pour quitter
```

### 4️⃣ Créer le fichier debug.log

```bash
touch wp-content/debug.log
chmod 666 wp-content/debug.log
```

### 5️⃣ Vérifier que c'est activé

```bash
grep "WP_DEBUG" wp-config.php
```

Vous devriez voir :
```
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### 6️⃣ Reproduire l'erreur

Visitez votre site depuis un navigateur :
```
https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
```

### 7️⃣ Voir les erreurs en temps réel

```bash
tail -f wp-content/debug.log
```
*(Appuyez sur Ctrl+C pour arrêter)*

### 8️⃣ Voir les dernières erreurs

```bash
tail -50 wp-content/debug.log
```

### 9️⃣ Chercher des erreurs spécifiques

```bash
# Erreurs Osmose
grep -i "osmose" wp-content/debug.log

# Erreurs fatales
grep -i "fatal" wp-content/debug.log

# Erreurs de base de données
grep -i "column\|table\|database" wp-content/debug.log
```

### 🔟 Copier les erreurs dans un fichier

```bash
tail -100 wp-content/debug.log > erreurs.txt
cat erreurs.txt
```

---

## 🔧 Réparer les Problèmes Courants

### Problème : Colonne 'source' manquante

```bash
# Avec WP-CLI (si installé)
wp db query "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN source varchar(50);"

# Avec MySQL direct
mysql -u votre_user -p votre_database -e "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN source varchar(50);"
```

### Problème : Fichiers du plugin manquants

```bash
cd wp-content/plugins/

# Sauvegarder l'ancien
mv osmose-ads osmose-ads-old

# Télécharger la nouvelle version
wget https://github.com/Harajuku13z/osmoseAds/archive/refs/heads/main.zip
unzip main.zip
mv osmoseAds-main osmose-ads

# Définir les permissions
chmod -R 755 osmose-ads
```

### Problème : Permissions incorrectes

```bash
# Réparer les permissions WordPress standard
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

---

## 🛑 Désactiver le Debug

### Une fois le problème résolu :

**Option A - Restaurer la sauvegarde :**
```bash
cp wp-config.php.backup wp-config.php
```

**Option B - Désactiver manuellement :**
```bash
sed -i "s/define('WP_DEBUG', true);/define('WP_DEBUG', false);/" wp-config.php
sed -i "/define.*'WP_DEBUG_LOG'/d" wp-config.php
sed -i "/define.*'WP_DEBUG_DISPLAY'/d" wp-config.php
```

**Option C - Script automatique :**
```bash
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh
bash disable-wp-debug.sh
```

---

## 📊 Commandes de Diagnostic

### Vérifier la version PHP

```bash
php -v
```

### Vérifier les extensions PHP

```bash
php -m | grep -E "curl|json|mbstring|mysqli"
```

### Vérifier l'espace disque

```bash
df -h
```

### Vérifier la mémoire

```bash
free -h
```

### Vérifier que le plugin est présent

```bash
ls -la wp-content/plugins/osmose-ads/
```

### Vérifier les tables de la base de données

```bash
# Avec WP-CLI
wp db tables | grep osmose

# Ou lister directement
mysql -u votre_user -p -e "SHOW TABLES LIKE '%osmose%'" votre_database
```

### Vérifier la structure d'une table

```bash
wp db query "DESCRIBE wp_osmose_ads_call_tracking;"
```

---

## 🎨 Affichage en Couleur

Pour un affichage plus lisible des erreurs :

```bash
# Erreurs en rouge
tail -50 wp-content/debug.log | grep --color=always -i "error\|fatal\|warning"

# Erreurs Osmose en vert
tail -50 wp-content/debug.log | grep --color=always -i "osmose"
```

---

## 💾 Télécharger les Logs

### Sur votre ordinateur (depuis un autre terminal)

```bash
# Via SCP
scp user@bretagne.normesrenovation.fr:public_html/wp-content/debug.log ./debug-local.log

# Via rsync
rsync -avz user@bretagne.normesrenovation.fr:public_html/wp-content/debug.log ./
```

---

## 🔄 Nettoyer les Logs

### Vider le fichier debug.log

```bash
# Vider sans supprimer
> wp-content/debug.log

# Ou supprimer complètement
rm wp-content/debug.log
```

### Garder seulement les dernières erreurs

```bash
# Garder les 1000 dernières lignes
tail -1000 wp-content/debug.log > wp-content/debug-temp.log
mv wp-content/debug-temp.log wp-content/debug.log
```

---

## 🚨 En Cas d'Urgence

### Site cassé ? Désactiver le plugin rapidement

```bash
cd wp-content/plugins/
mv osmose-ads osmose-ads-disabled
```

### Restaurer

```bash
mv osmose-ads-disabled osmose-ads
```

---

## 📋 Checklist Complète

```bash
# 1. Connexion
ssh user@site.com && cd public_html

# 2. Backup
cp wp-config.php wp-config.php.backup

# 3. Activer debug
sed -i "s/define('WP_DEBUG', false);/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);/" wp-config.php

# 4. Créer log
touch wp-content/debug.log && chmod 666 wp-content/debug.log

# 5. Reproduire l'erreur (visitez le site)

# 6. Voir erreurs
tail -50 wp-content/debug.log

# 7. Chercher "osmose"
grep -i "osmose\|fatal" wp-content/debug.log

# 8. Copier les erreurs
tail -100 wp-content/debug.log > erreurs.txt

# 9. Désactiver debug
cp wp-config.php.backup wp-config.php
```

---

## 📞 Aide Rapide

**Hébergeurs courants et leurs chemins :**

- **OVH :** `cd www` ou `cd public_html`
- **O2Switch :** `cd public_html`
- **Ionos :** `cd htdocs`
- **Hostinger :** `cd public_html`
- **cPanel :** `cd public_html`

**Port SSH par défaut :** 22

**Pour trouver votre chemin WordPress :**
```bash
find ~ -name "wp-config.php" 2>/dev/null | head -1
```

---

## ✅ Résumé Ultra-Compact

```bash
# TOUT EN UNE LIGNE (copier-coller direct)
ssh user@site.com "cd public_html && cp wp-config.php wp-config.php.bak && sed -i \"s/define('WP_DEBUG', false);/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);/\" wp-config.php && tail -f wp-content/debug.log"
```

*(Remplacez `user@site.com` et `public_html` par vos valeurs)*

🎉 **Terminé !**

