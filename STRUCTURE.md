# Structure du Plugin Osmose ADS

## Vue d'ensemble

Le plugin Osmose ADS est un système complet de génération de pages géolocalisées pour WordPress. Il suit les meilleures pratiques WordPress et est structuré de manière modulaire.

## Architecture

### Fichier Principal
- `osmose-ads.php` - Point d'entrée du plugin, enregistre les hooks d'activation/désactivation

### Dossier `includes/`
Contient toutes les classes principales du plugin :

#### Classes Core
- `class-osmose-ads.php` - Classe principale qui orchestre tout le plugin
- `class-osmose-ads-loader.php` - Gestionnaire de hooks WordPress
- `class-osmose-ads-i18n.php` - Gestion de l'internationalisation
- `class-osmose-ads-activator.php` - Logique d'activation
- `class-osmose-ads-deactivator.php` - Logique de désactivation
- `class-osmose-ads-post-types.php` - Enregistrement des Custom Post Types
- `class-osmose-ads-rewrite.php` - Gestion des URLs et rewrite rules

#### Modèles (`includes/models/`)
- `class-ad-template.php` - Modèle pour les templates d'annonces
- `class-ad.php` - Modèle pour les annonces

#### Services (`includes/services/`)
- `class-ai-service.php` - Service d'intégration avec les APIs IA (OpenAI, Groq)
- `class-city-content-personalizer.php` - Service de personnalisation de contenu par ville

### Dossier `admin/`
Interface d'administration WordPress :

- `class-osmose-ads-admin.php` - Classe principale de l'admin
- `ajax-handlers.php` - Gestionnaires AJAX pour les actions admin
- `partials/` - Templates des pages admin :
  - `dashboard.php` - Tableau de bord
  - `templates.php` - Gestion des templates
  - `ads.php` - Liste des annonces
  - `bulk-generation.php` - Génération en masse
  - `cities.php` - Gestion des villes
  - `settings.php` - Réglages
- `css/osmose-ads-admin.css` - Styles admin
- `js/osmose-ads-admin.js` - Scripts admin

### Dossier `public/`
Interface publique :

- `class-osmose-ads-public.php` - Classe principale publique
- `templates/single-ad.php` - Template d'affichage d'une annonce
- `css/osmose-ads-public.css` - Styles publics
- `js/osmose-ads-public.js` - Scripts publics

## Custom Post Types

### `ad_template`
- **Type** : Custom Post Type (non public)
- **Usage** : Stocke les modèles de contenu réutilisables
- **Meta fields** : service_name, service_slug, meta_title, meta_description, etc.

### `ad`
- **Type** : Custom Post Type (public)
- **Usage** : Stocke les annonces individuelles par ville
- **Permalink** : `/ads/{slug}/`
- **Meta fields** : template_id, city_id, keyword, status, meta_*

### `city`
- **Type** : Custom Post Type (non public)
- **Usage** : Référentiel des villes
- **Meta fields** : name, postal_code, department, region, population

## Flux de Données

### Création d'un Template
1. Admin entre le nom du service
2. Le système appelle l'IA pour générer le contenu
3. Le template est sauvegardé dans `ad_template`
4. Les métadonnées sont stockées en meta fields

### Génération d'Annonces
1. Admin sélectionne un service et des villes
2. Le système récupère ou crée le template correspondant
3. Pour chaque ville :
   - Génère le contenu personnalisé (variables ou IA)
   - Génère les métadonnées personnalisées
   - Crée un post `ad` avec le slug unique
4. Le compteur d'utilisation du template est incrémenté

### Affichage Public
1. Utilisateur accède à `/ads/{slug}/`
2. WordPress charge le post `ad` correspondant
3. Le template loader vérifie si un template personnalisé existe
4. Si oui, utilise `single-ad.php` du plugin ou du thème
5. Le contenu personnalisé est généré à la volée
6. Les métadonnées SEO sont injectées dans le head

## Système de Cache

Le plugin utilise l'API Transients de WordPress pour mettre en cache :
- Contenu personnalisé par ville (30 jours)
- Métadonnées personnalisées (30 jours)

Clés de cache :
- `osmose_ads_content_{hash}` - Contenu personnalisé
- `osmose_ads_meta_{hash}` - Métadonnées personnalisées

## Intégration IA

### Support des APIs
- **OpenAI** : API ChatGPT (modèles gpt-3.5-turbo, gpt-4, etc.)
- **Groq** : API Groq (modèle mixtral-8x7b-32768)

### Processus de Génération
1. Construction du contexte de la ville
2. Création du prompt personnalisé
3. Appel à l'API IA
4. Post-traitement du contenu
5. Mise en cache du résultat

## Variables Disponibles

Dans les templates, ces variables sont remplacées automatiquement :

- `[VILLE]` → Nom de la ville
- `[DÉPARTEMENT]` → Département
- `[RÉGION]` → Région
- `[CODE_POSTAL]` → Code postal
- `[FORM_URL]` → URL du formulaire de devis
- `[PHONE]` → Téléphone formaté
- `[PHONE_RAW]` → Téléphone brut
- `[TITRE]` → Titre de l'annonce

## Hooks WordPress Utilisés

### Actions
- `init` - Enregistrement des CPT et rewrite rules
- `admin_menu` - Ajout du menu admin
- `admin_init` - Enregistrement des settings
- `wp_enqueue_scripts` - Chargement des assets
- `wp_head` - Injection des métadonnées SEO

### Filters
- `query_vars` - Ajout de variables de requête
- `template_include` - Chargement du template personnalisé
- `wp_title` - Personnalisation du titre
- `pre_get_document_title` - Personnalisation du titre (WP 4.4+)

## Options WordPress

Le plugin stocke ces options :
- `osmose_ads_ai_personalization` - Booléen, active la personnalisation IA
- `osmose_ads_company_phone` - Téléphone formaté
- `osmose_ads_company_phone_raw` - Téléphone brut
- `osmose_ads_openai_api_key` - Clé API
- `osmose_ads_ai_provider` - Fournisseur IA (openai/groq)
- `osmose_ads_services` - Array des services disponibles

## Sécurité

- Tous les fichiers contiennent `index.php` pour empêcher l'accès direct
- Utilisation de `sanitize_text_field()`, `esc_html()`, `esc_attr()` partout
- Vérification des nonces pour toutes les actions AJAX
- Vérification des capabilities (`manage_options`) pour l'admin
- Utilisation de `wp_remote_post()` pour les appels API sécurisés

## Extensibilité

Le plugin peut être étendu via :

1. **Filtres personnalisés** - Ajouter vos propres filtres dans les classes
2. **Template du thème** - Créer `single-ad.php` dans votre thème
3. **Hooks WordPress** - Utiliser les hooks existants pour personnaliser
4. **Classes** - Étendre les classes existantes si nécessaire

## Compatibilité

- **WordPress** : 5.0+
- **PHP** : 7.4+
- **MySQL** : 5.6+
- **Thèmes** : Compatible avec tous les thèmes WordPress standards

## Performances

- Mise en cache des contenus générés (30 jours)
- Lazy loading des modèles
- Requêtes optimisées avec meta_query
- Compression des assets (recommandé)



