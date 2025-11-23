# 📦 Publication sur GitHub - Guide Rapide

## Option 1 : Script Automatique (Recommandé)

1. Exécutez le script :
```bash
./deploy-to-github.sh votre-username-github
```

2. Suivez les instructions affichées

## Option 2 : Manuel

### 1. Créer le dépôt sur GitHub

- Allez sur https://github.com/new
- Nom : `osmose-ads`
- Description : "Système de génération automatique de pages géolocalisées pour WordPress"
- **NE COCHEZ PAS** "Initialize with README"
- Cliquez sur "Create repository"

### 2. Lier et pousser

```bash
cd osmose-ads
git add .
git commit -m "Initial commit - Version 1.0.0"
git remote add origin https://github.com/VOTRE_USERNAME/osmose-ads.git
git branch -M main
git push -u origin main
```

## ✅ Vérification

Après le push, visitez :
```
https://github.com/VOTRE_USERNAME/osmose-ads
```

## 📝 Prochaines Étapes

1. ✅ Créer une release (v1.0.0)
2. ✅ Ajouter des topics/tags
3. ✅ Configurer GitHub Actions
4. ✅ Activer GitHub Pages (optionnel)

Voir `GITHUB_SETUP.md` pour plus de détails.

