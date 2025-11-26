# 🔍 Guide Complet - Résoudre l'Erreur Critique WordPress

## 🎯 Votre Situation

Vous avez une **erreur critique** sur : 
```
https://bretagne.normesrenovation.fr/?ad=couverture-et-toiture-allaire
```

Ce guide vous donne **toutes les solutions possibles** pour identifier et corriger le problème.

---

## ⚡ SOLUTIONS RAPIDES (par ordre de priorité)

### 🥇 Solution #1 : Réparation Automatique via Web (RECOMMANDÉ)

**Temps : 2 minutes | Difficulté : ⭐☆☆☆☆**

1. Téléchargez `repair.php` depuis GitHub
2. Uploadez-le dans `/wp-content/plugins/osmose-ads/` via FTP
3. Visitez : `https://bretagne.normesrenovation.fr/wp-content/plugins/osmose-ads/repair.php?key=osmose2024`
4. Le script répare automatiquement tout
5. **Supprimez repair.php après utilisation !**

📄 **Fichier :** `repair.php`

---

### 🥈 Solution #2 : Réparation via SSH Automatique

**Temps : 3 minutes | Difficulté : ⭐⭐☆☆☆**

```bash
# 1. Connexion SSH
ssh votre-user@bretagne.normesrenovation.fr

# 2. Aller dans WordPress
cd public_html  # ou www, ou htdocs

# 3. Télécharger et exécuter le script d'activation debug
wget https://raw.githubusercontent.com/Harajuku13z/osmoseAds/main/enable-wp-debug.sh
bash enable-wp-debug.sh

# 4. Reproduire l'erreur (visitez le site)

# 5. Voir les erreurs
tail -50 wp-content/debug.log

# 6. M'envoyer les erreurs
```

📄 **Fichiers :** 
- `enable-wp-debug.sh` (active le debug)
- `disable-wp-debug.sh` (désactive après)
- `INSTRUCTIONS-SSH.md` (guide complet)

---

### 🥉 Solution #3 : Commande One-Liner SSH

**Temps : 1 minute | Difficulté : ⭐⭐☆☆☆**

Une seule ligne à copier-coller en SSH :

```bash
cd public_html && cp wp-config.php wp-config.php.backup-$(date +%Y%m%d) && sed -i.old "/define.*'WP_DEBUG'/d; /define.*'WP_DEBUG_LOG'/d; /define.*'WP_DEBUG_DISPLAY'/d" wp-config.php && sed -i "s/\(\/\* C'est tout\|\/\* That's all\)/define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);\n@ini_set('display_errors', 0);\n\n\1/" wp-config.php && touch wp-content/debug.log && chmod 666 wp-content/debug.log && tail -f wp-content/debug.log
```

📄 **Fichier :** `COMMANDES-RAPIDES.md`

---

### 🏅 Solution #4 : Réparation SQL Directe

**Temps : 30 secondes | Difficulté : ⭐⭐☆☆☆**

Si l'erreur est "Column 'source' doesn't exist" :

1. Connectez-vous à **phpMyAdmin**
2. Onglet **SQL**
3. Exécutez :

```sql
ALTER TABLE wp_osmose_ads_call_tracking 
ADD COLUMN IF NOT EXISTS source varchar(50) AFTER referrer;
```

📄 **Fichier :** `SOLUTION-RAPIDE.md`

---

### 🎖️ Solution #5 : Activation Debug Manuelle (FTP)

**Temps : 5 minutes | Difficulté : ⭐⭐⭐☆☆**

1. Téléchargez `wp-config.php` via FTP
2. Ouvrez-le avec un éditeur de texte
3. Cherchez `define('WP_DEBUG', false);`
4. Remplacez par :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

5. Sauvegardez et re-uploadez
6. Consultez `/wp-content/debug.log` via FTP

📄 **Fichier :** `DEBUG-INSTRUCTIONS.md`

---

## 📚 Documentation Disponible

| Fichier | Description | Usage |
|---------|-------------|-------|
| **repair.php** | Script web de réparation automatique | Upload via FTP, accès web |
| **enable-wp-debug.sh** | Active le debug automatiquement | SSH : `bash enable-wp-debug.sh` |
| **disable-wp-debug.sh** | Désactive le debug | SSH : `bash disable-wp-debug.sh` |
| **INSTRUCTIONS-SSH.md** | Guide complet SSH (3 méthodes) | Lecture |
| **COMMANDES-RAPIDES.md** | One-liners prêts à l'emploi | Copier-coller |
| **SOLUTION-RAPIDE.md** | 5 solutions détaillées | Lecture |
| **DEBUG-INSTRUCTIONS.md** | Activation debug manuelle | Lecture |
| **diagnostic.php** | Diagnostic web complet | Upload via FTP, accès web |

---

## 🎬 Workflow Recommandé

```
┌─────────────────────────────────────────────────┐
│  1. Essayer Solution #4 (SQL)                   │
│     └─ Si erreur "Column 'source'"              │
│                                                  │
│  2. Si ça ne marche pas :                       │
│     Essayer Solution #1 (repair.php)            │
│                                                  │
│  3. Si pas d'accès FTP :                        │
│     Essayer Solution #2 ou #3 (SSH)             │
│                                                  │
│  4. Consulter les logs debug.log                │
│                                                  │
│  5. M'envoyer les erreurs pour diagnostic       │
└─────────────────────────────────────────────────┘
```

---

## 🔍 Erreurs Courantes et Solutions

### ❌ "Column 'source' doesn't exist in table"

**Cause :** La colonne 'source' manque dans la table de tracking

**Solution :**
```sql
ALTER TABLE wp_osmose_ads_call_tracking ADD COLUMN source varchar(50);
```

**Ou :** Utilisez `repair.php` qui le fait automatiquement

---

### ❌ "Class 'Osmose_Ads' not found"

**Cause :** Fichiers du plugin manquants ou corrompus

**Solutions :**
1. Ré-uploadez tous les fichiers du plugin depuis GitHub
2. Vérifiez les permissions (755 dossiers, 644 fichiers)
3. Désactivez/Réactivez le plugin

---

### ❌ "Call to undefined function wp_get_current_user"

**Cause :** WordPress pas complètement chargé

**Solution :** Le plugin tente de charger trop tôt. C'est un bug de code.

---

### ❌ "Headers already sent"

**Cause :** Espace ou caractère avant `<?php` dans un fichier

**Solution :** Ré-uploadez les fichiers du plugin en mode BINAIRE via FTP

---

### ❌ "Fatal error: Maximum execution time exceeded"

**Cause :** Script trop long (génération massive d'annonces)

**Solution :** Augmentez `max_execution_time` dans php.ini ou contactez l'hébergeur

---

## 🛠️ Outils par Méthode d'Accès

### Vous avez accès FTP ?
→ Utilisez `repair.php` ou `diagnostic.php`

### Vous avez accès SSH ?
→ Utilisez `enable-wp-debug.sh` ou les commandes one-liner

### Vous avez accès phpMyAdmin ?
→ Exécutez la requête SQL de réparation

### Vous avez seulement accès WordPress ?
→ Contactez votre hébergeur pour activer le debug

---

## 📊 Checklist de Diagnostic

Avant de me contacter, vérifiez :

- [ ] Version PHP ≥ 7.4 ?
- [ ] Tous les fichiers du plugin uploadés ?
- [ ] Plugin activé dans WordPress ?
- [ ] Table `wp_osmose_ads_call_tracking` existe ?
- [ ] Colonne `source` existe dans la table ?
- [ ] Mode debug activé ?
- [ ] Fichier `debug.log` lisible ?
- [ ] Erreurs consultées dans `debug.log` ?

---

## 🆘 Besoin d'Aide ?

Si aucune solution ne fonctionne, envoyez-moi :

### Informations Système
```bash
# Version PHP
php -v

# Version WordPress
wp core version  # ou via wp-admin
```

### Logs d'Erreur
```bash
# Les 100 dernières lignes
tail -100 wp-content/debug.log

# Ou chercher "osmose" et "fatal"
grep -i "osmose\|fatal" wp-content/debug.log
```

### Captures d'Écran
- L'erreur affichée sur le site
- Le résultat de `repair.php` (si utilisé)
- Les dernières lignes de `debug.log`

---

## ⚠️ IMPORTANT - Sécurité

Après réparation :

1. ✅ **Désactivez le mode debug** 
   ```bash
   bash disable-wp-debug.sh
   # ou
   define('WP_DEBUG', false);
   ```

2. ✅ **Supprimez les fichiers de diagnostic**
   - `repair.php`
   - `diagnostic.php`
   - `enable-wp-debug.sh`
   - `disable-wp-debug.sh`
   - `debug.log` (optionnel)

3. ✅ **Vérifiez que le site fonctionne**

---

## 🎯 Résumé en 3 Étapes

```
1️⃣ IDENTIFIER L'ERREUR
   → Activez le debug avec enable-wp-debug.sh
   → Ou utilisez repair.php
   
2️⃣ CORRIGER LE PROBLÈME
   → SQL : Ajoutez la colonne 'source'
   → Ou re-uploadez le plugin
   
3️⃣ VÉRIFIER ET SÉCURISER
   → Testez le site
   → Désactivez le debug
   → Supprimez les scripts de diagnostic
```

---

## 📞 Contact & Support

GitHub : https://github.com/Harajuku13z/osmoseAds

**Tous les fichiers sont disponibles sur GitHub !**

---

## ✨ Succès !

Une fois réparé :
- ✅ Le site fonctionne normalement
- ✅ Les annonces s'affichent correctement
- ✅ Aucune erreur dans les logs
- ✅ Le debug est désactivé
- ✅ Les fichiers de diagnostic sont supprimés

**Félicitations ! 🎉**

---

*Dernière mise à jour : $(date +"%Y-%m-%d")*

