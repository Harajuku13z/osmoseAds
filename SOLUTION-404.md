# 🔧 Solution si vous avez une Erreur 404

Si vous avez une erreur 404 en accédant à `fix-crash.php`, voici **3 solutions alternatives** :

---

## 🎯 Solution #1 : Utiliser la Version Plugin (RECOMMANDÉ)

### Étape 1 : Télécharger
👉 **Téléchargez** : https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/fix-crash-plugin.php

### Étape 2 : Upload via FTP
Uploadez le fichier dans :
```
/wp-content/plugins/osmose-ads/fix-crash-plugin.php
```

### Étape 3 : Accéder
Visitez :
```
https://bretagne.normesrenovation.fr/wp-content/plugins/osmose-ads/fix-crash-plugin.php?key=osmose2024
```

**Cette URL devrait fonctionner !** ✅

---

## 🎯 Solution #2 : Réparation SQL Directe (PLUS RAPIDE)

Si vous avez accès à **phpMyAdmin**, c'est la solution la plus rapide :

### Étape 1 : Connectez-vous à phpMyAdmin
- Via cPanel → phpMyAdmin
- Ou directement : `https://bretagne.normesrenovation.fr/phpmyadmin`

### Étape 2 : Sélectionnez votre base de données
Cliquez sur le nom de votre base de données WordPress

### Étape 3 : Onglet SQL
Cliquez sur l'onglet **SQL** en haut

### Étape 4 : Copiez-collez cette requête

```sql
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

**⚠️ IMPORTANT :** Remplacez `wp_` par le préfixe de votre base de données si différent !

Pour trouver votre préfixe :
- Regardez dans `wp-config.php` : `$table_prefix = 'wp_';`
- Ou regardez les noms des tables dans phpMyAdmin

### Étape 5 : Cliquez sur "Exécuter"

### Étape 6 : Testez votre site
```
https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
```

**C'est tout !** ✅

---

## 🎯 Solution #3 : Trouver le Bon Chemin WordPress

Si vous ne savez pas où est WordPress :

### Via FTP

1. **Connectez-vous en FTP**
2. **Cherchez** le fichier `wp-config.php`
3. **Notez** le chemin complet

### Via SSH

```bash
# Connexion SSH
ssh votre-user@bretagne.normesrenovation.fr

# Trouver wp-config.php
find ~ -name "wp-config.php" 2>/dev/null

# Aller dans ce dossier
cd /chemin/trouvé/ci-dessus
```

### Chemins Courants

- **OVH** : `www/` ou `public_html/`
- **O2Switch** : `public_html/`
- **Ionos** : `htdocs/`
- **Hostinger** : `public_html/`
- **cPanel** : `public_html/`

### Une fois le chemin trouvé

Uploadez `fix-crash.php` dans ce dossier, puis visitez :
```
https://bretagne.normesrenovation.fr/fix-crash.php?key=osmose2024
```

---

## 🎯 Solution #4 : Désactiver le Plugin via phpMyAdmin

Si le site est complètement cassé et que vous ne pouvez rien faire :

### Étape 1 : phpMyAdmin
Connectez-vous à phpMyAdmin

### Étape 2 : Table `wp_options`
1. Cliquez sur la table `wp_options` (ou `votre_prefixe_options`)
2. Cliquez sur l'onglet **Rechercher**
3. Cherchez : `active_plugins`

### Étape 3 : Modifier
1. Cliquez sur **Modifier** (icône crayon)
2. Dans le champ `option_value`, trouvez `osmose-ads/osmose-ads.php`
3. **Supprimez** cette ligne (gardez le reste)
4. Cliquez sur **Exécuter**

**⚠️ ATTENTION :** Cela désactive le plugin. Le site devrait refonctionner, mais sans les annonces.

### Étape 4 : Réparer la BDD
Suivez la **Solution #2** ci-dessus pour réparer la base de données

### Étape 5 : Réactiver le Plugin
Dans WordPress Admin → Plugins → Activer Osmose ADS

---

## 🎯 Solution #5 : Via SSH (One-Liner)

Si vous avez accès SSH :

```bash
# Connexion
ssh votre-user@bretagne.normesrenovation.fr

# Aller dans WordPress (ajustez le chemin)
cd public_html  # ou www, ou htdocs

# Réparer directement via SQL
mysql -u VOTRE_USER -p VOTRE_DATABASE -e "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;"
```

Remplacez :
- `VOTRE_USER` : votre utilisateur MySQL
- `VOTRE_DATABASE` : votre base de données WordPress
- `wp_` : votre préfixe de table si différent

---

## 📋 Checklist de Diagnostic

Avant de continuer, vérifiez :

- [ ] **Où est WordPress ?**
  - Via FTP : Cherchez `wp-config.php`
  - Via SSH : `find ~ -name "wp-config.php"`

- [ ] **Quel est le préfixe des tables ?**
  - Regardez dans `wp-config.php` : `$table_prefix`
  - Ou dans phpMyAdmin : regardez les noms des tables

- [ ] **Quel accès avez-vous ?**
  - [ ] FTP
  - [ ] SSH
  - [ ] phpMyAdmin
  - [ ] cPanel File Manager

---

## 🚀 Ma Recommandation

**Si vous avez phpMyAdmin :**
→ Utilisez **Solution #2** (SQL direct) - C'est le plus rapide ! ⚡

**Si vous avez FTP :**
→ Utilisez **Solution #1** (fix-crash-plugin.php) - Le plus simple ! ✅

**Si le site est complètement cassé :**
→ Utilisez **Solution #4** (Désactiver via phpMyAdmin) - En dernier recours ! 🆘

---

## 🆘 Besoin d'Aide ?

Si aucune solution ne fonctionne :

1. **Trouvez votre préfixe de table** :
   - Via FTP : Ouvrez `wp-config.php` et cherchez `$table_prefix`
   - Via phpMyAdmin : Regardez les noms des tables

2. **Trouvez le nom de votre base de données** :
   - Dans `wp-config.php` : `DB_NAME`

3. **Exécutez cette requête SQL** (remplacez les valeurs) :

```sql
-- Remplacez 'wp_' par votre préfixe
-- Remplacez 'votre_database' par votre base de données

USE votre_database;
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

4. **Testez votre site**

---

## ✅ Après Réparation

Une fois réparé :

1. ✅ Testez le site
2. ✅ Supprimez les fichiers de réparation (`fix-crash.php`, `fix-crash-plugin.php`)
3. ✅ Mettez à jour le plugin depuis GitHub pour avoir la dernière version avec les corrections

---

**La Solution #2 (SQL direct) est généralement la plus rapide et la plus fiable !** 🎯

