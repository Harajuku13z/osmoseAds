# Guide de Publication sur GitHub

Ce guide vous explique comment publier le plugin Osmose ADS sur GitHub.

## 📋 Prérequis

1. Un compte GitHub
2. Git installé sur votre machine
3. Accès en ligne de commande

## 🚀 Étapes de Publication

### 1. Créer le dépôt sur GitHub

1. Allez sur [GitHub](https://github.com)
2. Cliquez sur le bouton **"+"** en haut à droite
3. Sélectionnez **"New repository"**
4. Remplissez les informations :
   - **Repository name** : `osmose-ads`
   - **Description** : "Système de génération automatique de pages géolocalisées pour WordPress"
   - **Visibility** : Public (ou Private selon vos préférences)
   - ⚠️ **NE COCHEZ PAS** "Initialize with README" (on a déjà un README)
5. Cliquez sur **"Create repository"**

### 2. Initialiser Git localement (déjà fait)

Le dépôt Git a déjà été initialisé dans le dossier `osmose-ads`.

### 3. Ajouter tous les fichiers

```bash
cd osmose-ads
git add .
```

### 4. Faire le premier commit

```bash
git commit -m "Initial commit - Version 1.0.0"
```

### 5. Lier au dépôt distant GitHub

Remplacez `VOTRE_USERNAME` par votre nom d'utilisateur GitHub :

```bash
git remote add origin https://github.com/VOTRE_USERNAME/osmose-ads.git
```

### 6. Pousser vers GitHub

```bash
git branch -M main
git push -u origin main
```

## 📝 Commandes Git Utiles

### Vérifier le statut
```bash
git status
```

### Ajouter des fichiers modifiés
```bash
git add .
```

### Faire un commit
```bash
git commit -m "Description des changements"
```

### Pousser les changements
```bash
git push
```

### Récupérer les changements
```bash
git pull
```

### Créer une nouvelle branche
```bash
git checkout -b feature/nom-de-la-fonctionnalite
```

## 🏷️ Créer une Release

1. Allez sur votre dépôt GitHub
2. Cliquez sur **"Releases"** (à droite)
3. Cliquez sur **"Create a new release"**
4. Remplissez :
   - **Tag version** : `v1.0.0`
   - **Release title** : `Version 1.0.0`
   - **Description** : Copiez le contenu du CHANGELOG.md
5. Cliquez sur **"Publish release"**

## 📦 Créer un ZIP pour Distribution

Pour créer un fichier ZIP du plugin :

```bash
cd ..
zip -r osmose-ads.zip osmose-ads -x "*.git*" "*.DS_Store"
```

Ou utilisez GitHub :
1. Allez sur la page des Releases
2. GitHub génère automatiquement un ZIP pour chaque release

## 🔧 Configuration GitHub

### Ajouter une Description

Allez dans **Settings** → **General** → **Description** du dépôt

### Ajouter des Topics/Tags

Dans la page du dépôt, cliquez sur l'icône ⚙️ à côté de **About** et ajoutez :
- `wordpress`
- `wordpress-plugin`
- `seo`
- `geolocation`
- `ai`
- `france`

### Activer GitHub Pages (optionnel)

Pour créer une documentation :
1. Allez dans **Settings** → **Pages**
2. Sélectionnez la branche `main` ou `gh-pages`
3. Activez GitHub Pages

## 📊 Badges (optionnel)

Vous pouvez ajouter des badges dans le README.md en utilisant [Shields.io](https://shields.io)

Exemple :
```markdown
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
```

## 🔐 Sécurité

### Ajouter un Secret pour CI/CD

1. Allez dans **Settings** → **Secrets and variables** → **Actions**
2. Ajoutez les secrets nécessaires (clés API, tokens, etc.)

### Ignorer les fichiers sensibles

Le fichier `.gitignore` est déjà configuré pour ignorer :
- Fichiers de configuration sensibles
- Logs
- Fichiers temporaires
- node_modules

## 📚 Documentation

### Wiki GitHub

Vous pouvez activer le Wiki GitHub pour créer plus de documentation :
1. Allez dans **Settings** → **Features**
2. Activez **Wikis**

### Discussions

Pour activer les discussions :
1. Allez dans **Settings** → **Features**
2. Activez **Discussions**

## 🎯 Prochaines Étapes

1. ✅ Créer le dépôt GitHub
2. ✅ Pousser le code
3. ⬜ Créer la première release
4. ⬜ Ajouter des badges
5. ⬜ Configurer les GitHub Actions
6. ⬜ Créer des issues pour les futures fonctionnalités

## 💡 Astuces

- Utilisez des messages de commit clairs et descriptifs
- Créez des branches pour chaque fonctionnalité
- Utilisez les Pull Requests pour les contributions
- Gardez le CHANGELOG.md à jour
- Taggez vos releases avec des versions sémantiques (v1.0.0, v1.1.0, etc.)

## 📞 Support

Si vous avez des questions sur GitHub, consultez la [documentation officielle](https://docs.github.com).

