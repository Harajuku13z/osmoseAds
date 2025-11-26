# 🔗 Liens Rapides - Téléchargement Direct GitHub

Tous les fichiers pour résoudre votre erreur critique WordPress sont disponibles ici :

---

## 📥 Téléchargements Directs

### 🔧 Scripts de Réparation

| Fichier | Télécharger | Usage |
|---------|------------|-------|
| **repair.php** | [📥 Télécharger](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php) | Upload via FTP → Accès web |
| **diagnostic.php** | [📥 Télécharger](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/diagnostic.php) | Diagnostic complet |
| **enable-wp-debug.sh** | [📥 Télécharger](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh) | Script SSH activation debug |
| **disable-wp-debug.sh** | [📥 Télécharger](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh) | Script SSH désactivation debug |

### 📚 Documentation

| Fichier | Voir en ligne | Description |
|---------|--------------|-------------|
| **README-DEBUG.md** | [📖 Lire](https://github.com/Harajuku13z/osmoseAds/blob/main/README-DEBUG.md) | **Guide principal** - Tout ce qu'il faut savoir |
| **INSTRUCTIONS-SSH.md** | [📖 Lire](https://github.com/Harajuku13z/osmoseAds/blob/main/INSTRUCTIONS-SSH.md) | Guide SSH complet (3 méthodes) |
| **COMMANDES-RAPIDES.md** | [📖 Lire](https://github.com/Harajuku13z/osmoseAds/blob/main/COMMANDES-RAPIDES.md) | One-liners prêts à copier-coller |
| **SOLUTION-RAPIDE.md** | [📖 Lire](https://github.com/Harajuku13z/osmoseAds/blob/main/SOLUTION-RAPIDE.md) | 5 solutions détaillées |
| **DEBUG-INSTRUCTIONS.md** | [📖 Lire](https://github.com/Harajuku13z/osmoseAds/blob/main/DEBUG-INSTRUCTIONS.md) | Activation debug manuelle |

---

## ⚡ Actions Rapides

### 🎯 Méthode 1 : Réparation Web (Recommandée)

1. **Téléchargez** : [repair.php](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php)
   - Clic droit → "Enregistrer sous"
   
2. **Uploadez** via FTP dans :
   ```
   /wp-content/plugins/osmose-ads/repair.php
   ```

3. **Visitez** :
   ```
   https://bretagne.normesrenovation.fr/wp-content/plugins/osmose-ads/repair.php?key=osmose2024
   ```

4. **Supprimez** le fichier après utilisation !

---

### 🖥️ Méthode 2 : SSH One-Liner

```bash
# Connexion
ssh votre-user@bretagne.normesrenovation.fr

# Télécharger et exécuter en une ligne
cd public_html && wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh && bash enable-wp-debug.sh

# Voir les erreurs
tail -50 wp-content/debug.log
```

---

### 💾 Méthode 3 : SQL Direct (phpMyAdmin)

```sql
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

---

## 📦 Télécharger Tout le Plugin

### Option A : Archive ZIP complète
```
https://github.com/Harajuku13z/osmoseAds/archive/refs/heads/main.zip
```

### Option B : Clone Git
```bash
git clone https://github.com/Harajuku13z/osmoseAds.git
```

### Option C : Via SSH direct sur le serveur
```bash
cd /wp-content/plugins/
wget https://github.com/Harajuku13z/osmoseAds/archive/refs/heads/main.zip
unzip main.zip
mv osmoseAds-main osmose-ads
```

---

## 🔍 Commandes SSH Directes

### Télécharger les scripts via wget

```bash
# Script de réparation web
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php

# Script d'activation debug
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh

# Script de désactivation debug
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh

# Rendre exécutables
chmod +x enable-wp-debug.sh disable-wp-debug.sh
```

### Ou via curl

```bash
# Script d'activation debug
curl -O https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh

# Exécuter
bash enable-wp-debug.sh
```

---

## 🎬 Workflow Complet en SSH

```bash
# 1. Connexion
ssh votre-user@bretagne.normesrenovation.fr

# 2. Navigation
cd public_html  # ou www, ou htdocs

# 3. Téléchargement du script
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh

# 4. Exécution
bash enable-wp-debug.sh

# 5. Reproduction de l'erreur
# Visitez : https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire

# 6. Consultation des erreurs
tail -50 wp-content/debug.log

# 7. Recherche spécifique
grep -i "osmose\|fatal" wp-content/debug.log

# 8. Si besoin de réparer la BDD
mysql -u USER -p DATABASE -e "ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN source varchar(50);"

# 9. Désactivation du debug
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh
bash disable-wp-debug.sh
```

---

## 🌐 URLs Utiles

### Votre Site
- **Page avec erreur** : https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
- **Admin WordPress** : https://bretagne.normesrenovation.fr/wp-admin/
- **Plugins** : https://bretagne.normesrenovation.fr/wp-admin/plugins.php

### GitHub - Plugin
- **Repository** : https://github.com/Harajuku13z/osmoseAds
- **Derniers commits** : https://github.com/Harajuku13z/osmoseAds/commits/main
- **Tous les fichiers** : https://github.com/Harajuku13z/osmoseAds/tree/main

### Raw Files (téléchargement direct)
- **repair.php** : https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php
- **enable-wp-debug.sh** : https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh
- **disable-wp-debug.sh** : https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/disable-wp-debug.sh

---

## 📱 Si Vous Utilisez un Mobile

### Télécharger les fichiers
1. Ouvrez [repair.php](https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php) sur mobile
2. Appuyez longuement → "Télécharger le lien"
3. Utilisez une app FTP mobile (FileZilla Mobile, FTP Manager)
4. Uploadez le fichier

### SSH sur Mobile
Applications recommandées :
- **Termius** (iOS/Android)
- **JuiceSSH** (Android)
- **Prompt** (iOS)

---

## 🆘 Accès d'Urgence

### Désactiver le Plugin Sans SSH ni FTP

Via phpMyAdmin :
```sql
UPDATE wp_options 
SET option_value = '' 
WHERE option_name = 'active_plugins';
```

⚠️ Cela désactive **TOUS** les plugins !

### Renommer le Plugin via File Manager (cPanel)

1. Connectez-vous à cPanel
2. File Manager
3. Allez dans `/wp-content/plugins/`
4. Renommez `osmose-ads` en `osmose-ads-disabled`
5. Le site devrait refonctionner (sans les annonces)

---

## 📋 Checklist Pré-Téléchargement

Avant de commencer, vérifiez que vous avez :

- [ ] **Accès FTP** (identifiants FTP)
  - OU **Accès SSH** (identifiants SSH)
  - OU **Accès cPanel/File Manager**

- [ ] **Accès phpMyAdmin** (pour la réparation SQL)

- [ ] **Accès WordPress Admin** (wp-admin)

- [ ] **Sauvegarde récente** de votre site (recommandé)

---

## 🎯 Quelle Méthode Choisir ?

```
Vous avez FTP ?
└─ Utilisez repair.php ⭐⭐⭐⭐⭐

Vous avez SSH ?
└─ Utilisez enable-wp-debug.sh ⭐⭐⭐⭐☆

Vous avez phpMyAdmin ?
└─ Exécutez la requête SQL ⭐⭐⭐☆☆

Vous n'avez rien ?
└─ Contactez votre hébergeur ⭐⭐☆☆☆
```

---

## 💡 Astuce Pro

### Bookmarklets pour Téléchargement Rapide

Créez des favoris dans votre navigateur avec ces URLs pour un accès ultra-rapide :

- **repair.php** : `https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/repair.php`
- **Guide principal** : `https://github.com/Harajuku13z/osmoseAds/blob/main/README-DEBUG.md`

---

## 📞 Support

**Repository GitHub** : https://github.com/Harajuku13z/osmoseAds

**Issues** : https://github.com/Harajuku13z/osmoseAds/issues

---

**Tous les fichiers sont à jour et prêts à l'emploi ! 🚀**

*Dernière mise à jour : Novembre 2025*

