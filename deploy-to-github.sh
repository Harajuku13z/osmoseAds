#!/bin/bash

# Script pour publier le plugin sur GitHub
# Usage: ./deploy-to-github.sh [votre-username-github]

echo "🚀 Publication de Osmose ADS sur GitHub"
echo "========================================"
echo ""

# Vérifier que Git est installé
if ! command -v git &> /dev/null
then
    echo "❌ Git n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

# Demander le nom d'utilisateur GitHub
if [ -z "$1" ]; then
    read -p "Entrez votre nom d'utilisateur GitHub: " GITHUB_USERNAME
else
    GITHUB_USERNAME=$1
fi

if [ -z "$GITHUB_USERNAME" ]; then
    echo "❌ Le nom d'utilisateur GitHub est requis."
    exit 1
fi

# Vérifier que le dépôt est initialisé
if [ ! -d ".git" ]; then
    echo "📦 Initialisation du dépôt Git..."
    git init
fi

# Ajouter tous les fichiers
echo "📝 Ajout des fichiers..."
git add .

# Faire le commit
echo "💾 Création du commit..."
git commit -m "Initial commit - Version 1.0.0

- Génération automatique de templates avec IA
- Import de villes via API officielle française
- Interface admin moderne
- Génération en masse d'annonces
- Personnalisation SEO par ville"

# Ajouter le remote (si pas déjà fait)
if ! git remote | grep -q "origin"; then
    echo "🔗 Ajout du remote GitHub..."
    git remote add origin "https://github.com/$GITHUB_USERNAME/osmose-ads.git"
else
    echo "🔄 Mise à jour du remote GitHub..."
    git remote set-url origin "https://github.com/$GITHUB_USERNAME/osmose-ads.git"
fi

# Créer la branche main si elle n'existe pas
git branch -M main

# Afficher les instructions finales
echo ""
echo "✅ Préparation terminée !"
echo ""
echo "📋 Prochaines étapes :"
echo ""
echo "1. Créez le dépôt sur GitHub :"
echo "   → https://github.com/new"
echo "   → Nom : osmose-ads"
echo "   → NE COCHEZ PAS 'Initialize with README'"
echo ""
echo "2. Poussez le code :"
echo "   git push -u origin main"
echo ""
echo "3. Ou exécutez cette commande pour pousser maintenant :"
echo "   git push -u origin main"
echo ""
read -p "Voulez-vous pousser maintenant ? (o/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo "⬆️  Push vers GitHub..."
    git push -u origin main
    if [ $? -eq 0 ]; then
        echo "✅ Code publié avec succès sur GitHub !"
        echo "🔗 https://github.com/$GITHUB_USERNAME/osmose-ads"
    else
        echo "❌ Erreur lors du push. Vérifiez que le dépôt existe sur GitHub."
    fi
fi

echo ""
echo "🎉 Terminé !"

