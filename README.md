# Osmose Ads 2 — Pages locales SEO pour WordPress

Génère en masse des pages de service locales **uniques** : pour chaque ville, jusqu'à **8 pages**, une par mot-clé métier.

Exemple, couvreur à Chalon-sur-Saône :

| Page | URL |
|---|---|
| Couvreur à Chalon-sur-Saône *(page principale / hub)* | `/couvreur-a-chalon-sur-saone/` |
| Rénovation de toiture à Chalon-sur-Saône | `/renovation-de-toiture-a-chalon-sur-saone/` |
| Réfection de toiture à Chalon-sur-Saône | `/refection-de-toiture-a-chalon-sur-saone/` |
| Zinguerie à Chalon-sur-Saône | `/zinguerie-a-chalon-sur-saone/` |
| Traitement hydrofuge de toiture à Chalon-sur-Saône | … |

## Ce qui rend chaque page unique (et pas générique)

- **Une intention par page** : chaque mot-clé a ses mots-clés secondaires, son angle éditorial, et l'IA a l'interdiction de développer les sujets des pages sœurs (anti-cannibalisation).
- **Données locales officielles** (geo.api.gouv.fr) : code postal, département, région, population, communes voisines réelles.
- **Plan éditorial tournant** : 6 structures × 5 accroches, choisies selon la ville et le mot-clé.
- **Anti-duplication mesurée** : chaque page est comparée aux pages du même mot-clé dans les autres villes (empreinte par 5-grammes, noms de ville neutralisés). Au-delà du seuil, l'IA réécrit.
- **Photos** : vos photos de chantier, ordre différent par ville, textes alternatifs optimisés.

## SEO / AIOSEO

- Analyse type **TruSEO** (21 critères : expression-clé dans titre/début de titre/méta/URL/intro/sous-titres/alt, densité, longueur, liens internes/externes, phrases, transitions, voix passive, paragraphes, répartition des sous-titres).
- Si le score est sous le seuil (80 par défaut), l'IA est relancée **avec la liste précise des critères ratés**.
- Corrections automatiques garanties : titre SEO 40–60 caractères commençant par l'expression-clé, méta-description 120–160, alt de l'image principale, FAQ titrée avec l'expression-clé, lien interne et lien d'autorité.
- Écriture directe dans **All in One SEO** (titre, description, expression-clé principale + secondaires, score, Open Graph). Compatible Yoast et Rank Math. Sans plugin SEO : méta et Open Graph gérés par Osmose Ads.
- **Schema.org** : LocalBusiness typé (RoofingContractor, Plumber…) + Service + FAQPage, fusionnés dans le graphe AIOSEO.
- **Maillage interne automatique** : page principale ↔ pages de la ville, et même service dans les communes voisines (triées par distance).
- **Publication progressive** optionnelle (X pages/jour) pour un rythme naturel.

## Intelligence artificielle

| Mode | Usage |
|---|---|
| **OpenAI API** (recommandé) | Génération de masse automatique. Sorties JSON strictes. Modèle au choix (`gpt-5-mini` par défaut). |
| **ChatGPT manuel** | Utilise votre abonnement ChatGPT : le plugin prépare chaque prompt, vous le collez dans ChatGPT puis recollez la réponse. Pour quelques pages. |
| **API compatible** | OpenRouter, Mistral, Groq, serveur local… |

> Un abonnement ChatGPT Plus/Pro ne fournit pas d'accès programmatique : l'automatiser (scraping, réutilisation de session) est interdit par les conditions d'OpenAI et peut faire bannir le compte. Pour un SaaS, chaque client renseigne sa propre clé API (modèle « BYOK »), ce que fait déjà l'écran Réglages → IA.

## Installation

1. Téléversez `osmose-ads.zip` dans *Extensions → Ajouter → Téléverser*.
2. *Osmose Ads → Réglages* : entreprise (téléphone, garanties **réelles**), IA, publication.
3. *Osmose Ads → Générer* : métier → villes → mots-clés (8 max) → photos → **Lancer**.
4. Suivez la progression dans *File d'attente* ; scores et détails dans *Pages & SEO*.

Pour des volumes importants, remplacez WP-Cron par une vraie tâche cron serveur :
`* * * * * wget -q -O - https://votre-site.fr/wp-cron.php?doing_wp_cron >/dev/null 2>&1`
et ajoutez `define('DISABLE_WP_CRON', true);` dans `wp-config.php`.

## Architecture

```
osmose-ads.php                 amorçage + autoload
includes/
  class-oa-plugin.php          branchement des modules
  class-oa-install.php         tables oa_jobs (file) et oa_calls (clics)
  class-oa-settings.php        réglages
  class-oa-trades.php          14 métiers, grappes de mots-clés, prépositions (« au Creusot »)
  class-oa-geo.php             geo.api.gouv.fr + calcul de proximité
  class-oa-ai.php              OpenAI / compatible / manuel, relances, suivi des tokens
  class-oa-prompts.php         prompts + schéma JSON de page
  class-oa-generator.php       pipeline complet d'une page
  class-oa-renderer.php        JSON → HTML sémantique (tout texte IA échappé)
  class-oa-seo.php             analyse TruSEO, similarité, AIOSEO/Yoast/RankMath, schema
  class-oa-queue.php           file atomique, travailleurs parallèles, cron, drip
  class-oa-public.php          shortcodes, maillage, barre mobile, suivi des appels
  class-oa-admin.php           menus, formulaires, AJAX
admin/  views/*.php, admin.css, admin.js
public/ front.css, front.js
```

Les pages générées sont des **pages WordPress natives** : elles restent en place et éditables si le plugin est désactivé (seuls les shortcodes de boutons/maillage ne s'affichent plus).
