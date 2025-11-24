# 📋 Plan d'Amélioration - Osmose ADS

Basé sur la documentation fonctionnelle complète du système d'annonces.

---

## ✅ Fonctionnalités Existantes

- [x] Templates d'annonces (CPT `ad_template`)
- [x] Annonces (CPT `ad`)
- [x] Villes (CPT `city`)
- [x] Génération de contenu par IA (ChatGPT/Groq)
- [x] Remplacement de variables ([VILLE], [DÉPARTEMENT], [CODE_POSTAL], [RÉGION])
- [x] Métadonnées SEO complètes (title, description, keywords, Open Graph, Twitter)
- [x] Import en masse de villes via API geo.api.gouv.fr
- [x] Génération en masse d'annonces
- [x] Call tracking avec source (hero, sidebar, footer, floating)
- [x] View counter pour chaque annonce
- [x] Tracking numbers uniques
- [x] Integration avec AIOSEO
- [x] Template public moderne (hero, sidebar, CTA)

---

## 🚀 Fonctionnalités à Implémenter

### Phase 1 - Fondations (Priorité HAUTE) 🔴

#### 1. Système de Status pour les Annonces
**Objectif** : Permettre de gérer le cycle de vie des annonces (brouillon, publié, archivé)

**Implémentation** :
- Ajouter un meta `ad_status` avec valeurs : `draft`, `published`, `archived`
- Ajouter un filtre dans l'admin pour filtrer par status
- Modifier les requêtes publiques pour n'afficher que les annonces `published`
- Ajouter une interface pour changer le status en masse

**Fichiers concernés** :
- `includes/models/class-ad.php` → Ajouter méthodes `get_status()`, `set_status()`
- `admin/partials/ads.php` → Ajouter filtres et actions en masse
- `includes/class-osmose-ads.php` → Filtrer les requêtes publiques

---

#### 2. Compteur d'Utilisation des Templates
**Objectif** : Suivre combien d'annonces utilisent chaque template

**Implémentation** :
- Ajouter un meta `usage_count` pour chaque template
- Incrémenter lors de la création d'une annonce
- Décrémenter lors de la suppression d'une annonce
- Afficher le compteur dans la liste des templates

**Fichiers concernés** :
- `includes/models/class-ad-template.php` → Méthodes `increment_usage()`, `decrement_usage()`, `get_usage_count()`
- `admin/ajax-handlers.php` → Modifier `osmose_ads_handle_create_template` et `osmose_ads_bulk_generate`
- `admin/partials/templates.php` → Afficher le compteur

---

#### 3. Système de Cache pour Contenu Personnalisé
**Objectif** : Éviter les appels répétés à l'IA pour le même contenu (économie + performance)

**Implémentation** :
- Utiliser WordPress Transients API
- Clé de cache : `osmose_content_{template_id}_{city_id}_{hash}`
- Durée : 30 jours (2592000 secondes)
- Invalidation manuelle ou lors de la mise à jour du template

**Fichiers concernés** :
- `includes/models/class-ad-template.php` → Modifier `get_content_for_city()` et `get_meta_for_city()`
- Créer `includes/services/class-cache-manager.php`

---

#### 4. Section Annonces Similaires
**Objectif** : Afficher d'autres services dans la même ville sur chaque page d'annonce

**Implémentation** :
- Ajouter méthode `get_related_ads($limit = 5)` dans `Ad` model
- Requête : même ville, template différent, status = published
- Afficher dans le template public avec cards modernes

**Fichiers concernés** :
- `includes/models/class-ad.php` → Ajouter `get_related_ads()`
- `public/templates/single-ad.php` → Section déjà présente, améliorer l'affichage

---

### Phase 2 - Gestion Avancée (Priorité MOYENNE) 🟡

#### 5. Interface de Gestion des Services
**Objectif** : Gérer une liste centralisée de services au lieu de les saisir manuellement

**Implémentation** :
- Créer une page admin "Services"
- Stocker dans `wp_options` comme JSON ou créer un CPT `service`
- Champs : nom, slug, description, icône, catégorie
- Interface CRUD avec drag & drop pour l'ordre

**Fichiers concernés** :
- Créer `admin/partials/services.php`
- Créer `admin/class-osmose-ads-services.php`
- Modifier `admin/partials/template-create.php` → Dropdown de services

---

#### 6. Personnalisation IA Avancée avec Contexte Local
**Objectif** : Générer du contenu vraiment unique par ville (pas juste remplacement de variables)

**Implémentation** :
- Créer un service `CityContentPersonalizer`
- Construire un contexte riche pour chaque ville :
  - Type de zone (grande ville, ville moyenne, petite ville, rurale)
  - Climat régional
  - Architecture typique (selon région)
  - Défis spécifiques (humidité en Bretagne, neige en montagne, etc.)
  - Population et démographie
- Prompt IA enrichi avec ce contexte
- Générer du contenu 100% unique
- Mise en cache automatique

**Fichiers concernés** :
- Créer `includes/services/class-city-content-personalizer.php`
- Modifier `includes/models/class-ad-template.php` → Utiliser le personalizer
- Ajouter un setting pour activer/désactiver

---

### Phase 3 - Contenu Additionnel (Priorité BASSE) 🟢

#### 7. Système de Réalisations/Portfolio
**Objectif** : Afficher des réalisations de l'entreprise sur les pages d'annonces

**Implémentation** :
- Créer un CPT `portfolio_item`
- Champs : titre, description, images (galerie), localisation (ville), service associé
- Afficher dans le template public les réalisations de la même ville/service
- Interface admin pour gérer le portfolio

**Fichiers concernés** :
- Créer `includes/class-osmose-ads-portfolio.php`
- Créer `admin/partials/portfolio.php`
- Modifier `public/templates/single-ad.php` → Section portfolio

---

#### 8. Système d'Avis Clients
**Objectif** : Afficher des avis clients sur les pages d'annonces

**Implémentation** :
- Créer un CPT `review`
- Champs : nom client, note (1-5), commentaire, date, ville, service
- Afficher les 3 derniers avis sur chaque page d'annonce
- Interface admin pour modérer les avis
- Optionnel : Intégration avec Google Reviews API

**Fichiers concernés** :
- Créer `includes/class-osmose-ads-reviews.php`
- Créer `admin/partials/reviews.php`
- Modifier `public/templates/single-ad.php` → Section avis

---

## 📊 Priorités d'Implémentation

### Sprint 1 (Maintenant)
1. ✅ Système de status
2. ✅ Compteur d'utilisation
3. ✅ Système de cache
4. ✅ Annonces similaires (amélioration affichage)

### Sprint 2
5. Interface de gestion des services
6. Personnalisation IA avancée

### Sprint 3
7. Système de réalisations/portfolio
8. Système d'avis clients

---

## 🎯 Bénéfices Attendus

### Performance
- ⚡ Réduction des appels IA grâce au cache (-70% coûts)
- ⚡ Pages plus rapides grâce au cache de contenu

### SEO
- 📈 Contenu plus unique avec personnalisation IA avancée
- 📈 Meilleure structure avec annonces similaires
- 📈 Rich snippets avec avis clients (schema.org)

### Gestion
- 🎨 Meilleure organisation avec système de status
- 🎨 Suivi précis de l'utilisation des templates
- 🎨 Interface services centralisée

### Conversion
- 💰 Réalisations augmentent la confiance
- 💰 Avis clients rassurent les prospects
- 💰 Annonces similaires gardent le visiteur sur le site

---

## 🔄 Mise à Jour des Données Existantes

Après implémentation, exécuter ces scripts de migration :

```php
// Mettre à jour le status de toutes les annonces existantes
$ads = get_posts(['post_type' => 'ad', 'posts_per_page' => -1]);
foreach ($ads as $ad) {
    update_post_meta($ad->ID, 'ad_status', 'published');
}

// Calculer le usage_count pour tous les templates existants
$templates = get_posts(['post_type' => 'ad_template', 'posts_per_page' => -1]);
foreach ($templates as $template) {
    $count = count(get_posts([
        'post_type' => 'ad',
        'meta_key' => 'template_id',
        'meta_value' => $template->ID,
        'posts_per_page' => -1
    ]));
    update_post_meta($template->ID, 'usage_count', $count);
}
```

---

## 📝 Notes Techniques

### Compatibilité
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+

### Dépendances
- Aucune dépendance externe (tout en natif WordPress)
- API IA : ChatGPT ou Groq (déjà implémenté)

### Performance
- Cache automatique de 30 jours
- Requêtes optimisées avec index
- Lazy loading des annonces similaires

---

## ✅ Checklist de Déploiement

Avant de déployer chaque fonctionnalité :

- [ ] Code testé en local
- [ ] Script de migration créé (si nécessaire)
- [ ] Documentation utilisateur mise à jour
- [ ] Tests de performance effectués
- [ ] Compatibilité vérifiée avec AIOSEO
- [ ] Call tracking vérifié
- [ ] Commit Git avec message descriptif
- [ ] Version incrémentée dans `osmose-ads.php`

---

**Dernière mise à jour** : 24 novembre 2025

