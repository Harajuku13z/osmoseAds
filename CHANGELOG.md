# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [1.0.0] - 2024-01-XX

### Ajouté
- Génération automatique de templates avec IA (OpenAI/Groq)
- Personnalisation du contenu par ville
- Génération en masse d'annonces
- Import de villes via API officielle française (GeoAPI)
  - Import par département
  - Import par région
  - Import par rayon (distance)
- Interface d'administration moderne avec palette bleue
- Page de configuration initiale guidée
- Dashboard avec statistiques
- Gestion des templates réutilisables
- Système de cache pour le contenu personnalisé
- Support des métadonnées SEO (Open Graph, Twitter Cards)
- Variables de remplacement dans les templates
- Masquage des notifications WordPress sur les pages du plugin

### Sécurité
- Protection ABSPATH sur tous les fichiers
- Vérification des nonces pour les actions AJAX
- Validation et sanitization des entrées utilisateur

### Documentation
- README.md complet
- Guide d'installation
- Guide de contribution
- Documentation de l'API

## [Unreleased]

### À venir
- Export/Import des configurations
- Support multi-langues
- Statistiques avancées
- Intégration avec d'autres APIs géographiques

---

[1.0.0]: https://github.com/votre-username/osmose-ads/releases/tag/v1.0.0

