# Osmose ADS - Plugin WordPress

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

Système de génération automatique et manuelle de pages de services géolocalisées avec personnalisation IA. Optimisé pour le SEO local.

## 📋 Description

Osmose ADS permet de créer des pages uniques pour chaque combinaison service/ville, optimisées pour le référencement local. Le plugin offre :

- ✅ Génération automatique de templates de contenu avec IA (OpenAI/Groq)
- ✅ Personnalisation du contenu par ville (avec ou sans IA)
- ✅ Génération en masse d'annonces
- ✅ Import de villes via l'API officielle française (data.gouv.fr)
- ✅ Optimisation SEO automatique
- ✅ Interface d'administration moderne et intuitive

## ✨ Fonctionnalités

### Gestion des Templates
- Création automatique de templates avec IA
- Création manuelle de templates
- Personnalisation par ville avec remplacement de variables
- Personnalisation IA avancée pour contenu unique

### Génération d'Annonces
- Génération en masse pour plusieurs villes
- Personnalisation automatique du contenu
- Métadonnées SEO optimisées
- Gestion des slugs uniques

### Import de Villes
- Import par département
- Import par région
- Import par rayon (distance)
- Utilisation de l'API GeoAPI officielle (data.gouv.fr)

### Interface Admin
- Design moderne avec palette bleue
- Dashboard avec statistiques
- Configuration initiale guidée
- Masquage des notifications WordPress

## 🚀 Installation

### Méthode 1 : Installation manuelle

1. Téléchargez ou clonez ce dépôt
2. Uploadez le dossier `osmose-ads` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu "Extensions" de WordPress
4. Suivez le guide de configuration initiale

### Méthode 2 : Via GitHub

```bash
cd wp-content/plugins
git clone https://github.com/votre-username/osmose-ads.git
```

Puis activez le plugin dans WordPress.

## 📖 Configuration

### Configuration initiale

1. Après activation, vous serez redirigé vers la page de configuration
2. Remplissez les informations :
   - Téléphone de l'entreprise
   - Liste des services
   - Villes (ou importez-en via l'API)
   - Clé API IA (optionnel)

### Import de villes via l'API

1. Allez dans **Osmose ADS > Villes**
2. Cliquez sur l'onglet "Import en Masse"
3. Choisissez votre méthode :
   - **Par Département** : Importe toutes les communes d'un département
   - **Par Région** : Importe toutes les communes d'une région
   - **Par Rayon** : Importe les communes dans un rayon autour d'une ville

### Génération d'annonces

1. Allez dans **Osmose ADS > Génération en Masse**
2. Sélectionnez un service
3. Sélectionnez les villes
4. Cliquez sur "Générer les Annonces"

## 🔧 Prérequis

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Mémoire PHP recommandée : 256MB
- Clé API OpenAI ou Groq (optionnel, pour la personnalisation IA)

## 🎨 Variables Disponibles

Dans vos templates, vous pouvez utiliser ces variables :

- `[VILLE]` → Nom de la ville
- `[DÉPARTEMENT]` → Département
- `[RÉGION]` → Région
- `[CODE_POSTAL]` → Code postal
- `[FORM_URL]` → URL du formulaire de devis
- `[PHONE]` → Numéro de téléphone formaté
- `[PHONE_RAW]` → Numéro de téléphone brut
- `[TITRE]` → Titre de l'annonce

## 📁 Structure du Projet

```
osmose-ads/
├── osmose-ads.php              # Fichier principal
├── includes/                   # Classes principales
│   ├── models/                # Modèles de données
│   └── services/              # Services (IA, API Geo)
├── admin/                     # Interface admin
│   ├── partials/             # Templates admin
│   ├── css/                  # Styles admin
│   └── js/                   # Scripts admin
├── public/                    # Interface publique
│   ├── templates/            # Templates publics
│   ├── css/                  # Styles publics
│   └── js/                   # Scripts publics
├── README.md                  # Documentation
├── LICENSE                    # Licence GPL v2
└── .gitignore                # Fichiers ignorés par Git
```

## 🔗 API Utilisées

- **GeoAPI** : https://geo.api.gouv.fr (API officielle française)
- **OpenAI** : https://api.openai.com (Optionnel)
- **Groq** : https://api.groq.com (Optionnel)

## 📝 Changelog

### Version 1.0.0
- Version initiale
- Génération automatique de templates avec IA
- Import de villes via API française
- Interface admin moderne
- Génération en masse d'annonces

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre feature (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence GPL v2 ou ultérieure. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👤 Auteur

**Osmose**

- Website: [osmose.com](https://osmose.com)
- GitHub: [@votre-username](https://github.com/votre-username)

## 🙏 Remerciements

- API GeoAPI de data.gouv.fr pour les données géographiques
- WordPress pour le framework
- La communauté WordPress pour le support

## 📞 Support

Pour toute question ou problème :
- Ouvrez une issue sur GitHub
- Consultez la documentation dans le dossier `docs/`

---

⭐ Si ce projet vous a aidé, n'hésitez pas à lui donner une étoile sur GitHub !
