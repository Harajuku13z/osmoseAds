# 🚀 Publication sur GitHub - Étapes Rapides

## ✅ Fichiers GitHub créés

- ✅ `.gitignore` - Fichiers à ignorer
- ✅ `LICENSE` - Licence GPL v2
- ✅ `README.md` - Documentation principale
- ✅ `CHANGELOG.md` - Historique des versions
- ✅ `.github/` - Templates et workflows
- ✅ `GITHUB_SETUP.md` - Guide détaillé
- ✅ `deploy-to-github.sh` - Script de déploiement

## 📋 Commandes à exécuter

### 1. Créer le dépôt sur GitHub

1. Allez sur https://github.com/new
2. Nom du dépôt : `osmose-ads`
3. Description : `Système de génération automatique de pages géolocalisées pour WordPress`
4. **Important** : NE COCHEZ PAS "Initialize with README"
5. Cliquez sur "Create repository"

### 2. Publier le code

**Option A : Utiliser le script (recommandé)**

```bash
cd osmose-ads
./deploy-to-github.sh votre-username-github
```

**Option B : Commandes manuelles**

```bash
cd osmose-ads

# Ajouter tous les fichiers
git add .

# Faire le premier commit
git commit -m "Initial commit - Version 1.0.0

- Génération automatique de templates avec IA
- Import de villes via API officielle française
- Interface admin moderne
- Génération en masse d'annonces"

# Ajouter le remote GitHub (remplacez VOTRE_USERNAME)
git remote add origin https://github.com/VOTRE_USERNAME/osmose-ads.git

# Créer la branche main
git branch -M main

# Pousser vers GitHub
git push -u origin main
```

### 3. Vérifier la publication

Visitez : `https://github.com/VOTRE_USERNAME/osmose-ads`

Vous devriez voir tous les fichiers du plugin.

## 🎯 Prochaines Étapes

### Créer une Release

1. Allez sur votre dépôt GitHub
2. Cliquez sur **"Releases"** (colonne de droite)
3. Cliquez sur **"Create a new release"**
4. Remplissez :
   - **Tag version** : `v1.0.0`
   - **Release title** : `Version 1.0.0 - Initial Release`
   - **Description** : Copiez le contenu de `CHANGELOG.md`
5. Cliquez sur **"Publish release"**

### Configurer le dépôt

1. **Ajouter une description** : Dans la page du dépôt, cliquez sur ⚙️ à côté de "About"
2. **Ajouter des topics** : `wordpress`, `wordpress-plugin`, `seo`, `geolocation`, `ai`, `france`
3. **Ajouter le site web** (si vous en avez un)

## 📊 Badges (optionnel)

Ajoutez ces badges dans le README.md :

```markdown
![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)
```

## 🔧 GitHub Actions

Les workflows GitHub Actions sont déjà configurés :
- Vérification de la syntaxe PHP
- Vérification WordPress (à configurer)

## 📚 Documentation

- `README.md` - Documentation principale
- `GITHUB_SETUP.md` - Guide complet GitHub
- `CHANGELOG.md` - Historique des versions
- `CONTRIBUTING.md` - Guide de contribution

## ⚠️ Important

- Ne commitez jamais les clés API ou mots de passe
- Le `.gitignore` est configuré pour ignorer les fichiers sensibles
- Vérifiez avant de pousser : `git status`

## 🎉 C'est tout !

Votre projet est maintenant prêt pour GitHub !

