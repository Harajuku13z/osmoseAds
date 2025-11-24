<?php
/**
 * Gestionnaires AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

function osmose_ads_handle_create_template() {
    // Vérifier que les classes existent
    if (!class_exists('Ad_Template')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
    }
    if (!class_exists('AI_Service')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
    }
    
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/preset-services.php';
    
    $creation_mode = sanitize_text_field($_POST['creation_mode'] ?? 'custom');
    $service_name = sanitize_text_field($_POST['service_name'] ?? '');
    $service_slug = sanitize_title($service_name);
    $prompt = sanitize_textarea_field($_POST['ai_prompt'] ?? '');
    $featured_image_id = intval($_POST['featured_image_id'] ?? 0);
    $realization_images = isset($_POST['realization_images']) && is_array($_POST['realization_images']) 
        ? array_map('intval', $_POST['realization_images']) 
        : array();
    
    // Récupérer les mots-clés associés aux images de réalisations
    $realization_keywords = isset($_POST['realization_keywords']) && is_array($_POST['realization_keywords'])
        ? array_map('sanitize_text_field', $_POST['realization_keywords'])
        : array();
    
    // Gestion des services préconfigurés
    $service_keywords = '';
    $service_description = '';
    $service_sections = array();
    
    if ($creation_mode === 'preset' && !empty($_POST['preset_service'])) {
        $preset_key = sanitize_text_field($_POST['preset_service']);
        $preset_services = osmose_ads_get_preset_services();
        
        if (isset($preset_services[$preset_key])) {
            $preset = $preset_services[$preset_key];
            $service_name = $preset['name'];
            $service_keywords = $preset['keywords'];
            $service_description = $preset['description'];
            $service_sections = $preset['sections'] ?? array();
        }
    } else {
        $service_keywords = sanitize_text_field($_POST['service_keywords'] ?? '');
        $service_description = sanitize_textarea_field($_POST['service_description'] ?? '');
    }
    
    if (empty($service_name)) {
        wp_send_json_error(array('message' => __('Le nom du service est requis', 'osmose-ads')));
    }
    
    // Vérifier si le template existe déjà
    $existing = Ad_Template::get_by_service_slug($service_slug);
    if ($existing) {
        wp_send_json_error(array('message' => __('Un template pour ce service existe déjà', 'osmose-ads')));
    }
    
    // Récupérer les informations de l'entreprise depuis les options WordPress
    $company_name = get_bloginfo('name');
    $company_phone = get_option('osmose_ads_company_phone', '');
    $company_phone_raw = get_option('osmose_ads_company_phone_raw', $company_phone);
    $company_email = get_option('admin_email', '');
    $site_url = get_site_url();
    
    // Appeler l'IA pour générer le contenu
    $ai_service = new AI_Service();
    
    if (empty($prompt)) {
        // Construire la liste des mots-clés pour le prompt
        $keywords_list = '';
        if (!empty($service_keywords)) {
            $keywords_array = array_map('trim', explode(',', $service_keywords));
            $keywords_list = implode(', ', $keywords_array);
        }
        
        // Construire le prompt complet selon le nouveau modèle
        $prompt = "═══════════════════════════════════════════════════════════════════\n";
        $prompt .= "🚨 RÈGLE CRITIQUE N°1 - VARIABLES OBLIGATOIRES 🚨\n";
        $prompt .= "═══════════════════════════════════════════════════════════════════\n\n";
        $prompt .= "VOUS DEVEZ IMPÉRATIVEMENT UTILISER CES VARIABLES :\n";
        $prompt .= "• [VILLE] pour le nom de la ville (JAMAIS \"Rennes\", \"Paris\", etc. en dur)\n";
        $prompt .= "• [DÉPARTEMENT] pour le département (JAMAIS \"Ille-et-Vilaine\", etc. en dur)\n";
        $prompt .= "• [CODE_POSTAL] pour le code postal\n";
        $prompt .= "• [RÉGION] pour la région\n\n";
        $prompt .= "❌ INTERDIT : \"Couvreur à Rennes\", \"Expert toiture à Paris\"\n";
        $prompt .= "✅ CORRECT : \"Couvreur à [VILLE]\", \"Expert toiture à [VILLE]\"\n\n";
        $prompt .= "Ces variables seront automatiquement remplacées pour chaque ville.\n";
        $prompt .= "═══════════════════════════════════════════════════════════════════\n\n\n";
        
        $prompt .= "# 🎯 Prompt Expert : Génération d'Articles Couvreur Premium SEO\n\n\n";
        $prompt .= "## IDENTITÉ\n\n";
        $prompt .= "Tu es un rédacteur web senior spécialisé en BTP/couverture avec 10+ ans d'expérience. Tu maîtrises parfaitement le vocabulaire technique du métier, les enjeux clients et les standards WordPress/SEO 2025.\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 📋 MISSION\n\n";
        $prompt .= "Créer un article HTML complet, dense en informations, optimisé SEO et géolocalisé pour promouvoir les services d'un couvreur.\n";
        $prompt .= "⚠️ ATTENTION : N'utilisez JAMAIS de nom de ville en dur. Utilisez TOUJOURS [VILLE] et [DÉPARTEMENT].\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 📥 DONNÉES REQUISES\n\n\n";
        $company_address = get_option('osmose_ads_company_address', '');
        $services = get_option('osmose_ads_services', array());
        
        $prompt .= "```\n\n";
        $prompt .= "Entreprise : " . ($company_name ?: '[NOM_ENTREPRISE]') . "\n";
        $prompt .= "Adresse siège : " . ($company_address ?: '[ADRESSE_COMPLETE]') . "\n";
        $prompt .= "Téléphone : " . ($company_phone ?: '[TELEPHONE]') . "\n";
        $prompt .= "Email : " . ($company_email ?: '[EMAIL]') . "\n";
        if (!empty($services)) {
            $prompt .= "Services proposés : " . implode(', ', array_slice($services, 0, 5)) . "\n";
        }
        $prompt .= "\nVille cible : [VILLE]\n";
        $prompt .= "Code postal : [CODE_POSTAL]\n";
        $prompt .= "Département : [DÉPARTEMENT]\n";
        $prompt .= "Région : [RÉGION]\n\n\n";
        $prompt .= "Services : " . $service_name;
        if (!empty($service_description)) {
            $prompt .= " - " . $service_description;
        }
        $prompt .= "\n";
        if (!empty($keywords_list)) {
            $prompt .= "Mots-clés SEO : " . $keywords_list . "\n";
        }
        $prompt .= "```\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## ✍️ RÈGLES D'ÉCRITURE ABSOLUES\n\n\n";
        $prompt .= "### 1. LONGUEUR ET STRUCTURE\n\n";
        $prompt .= "- **Contenu concis et professionnel** : 1500-2000 mots\n\n";
        $prompt .= "- **Introduction courte** : 2-3 paragraphes présentant l'entreprise et ses valeurs\n\n";
        $prompt .= "- **Sections thématiques** avec titres H2 clairs\n\n";
        $prompt .= "- **Liste de prestations** : 8-12 services avec descriptions courtes (30-50 mots)\n\n";
        $prompt .= "- **FAQ** : 5-8 questions pertinentes avec réponses concises (50-80 mots)\n\n\n";
        $prompt .= "### 2. TON ET STYLE\n\n";
        $prompt .= "- **Professionnel mais accessible** : vocabulaire technique expliqué\n\n";
        $prompt .= "- **Local et personnalisé** : références constantes à [VILLE], [DÉPARTEMENT], climat\n";
        $prompt .= "  ⚠️ RAPPEL : Écrivez \"à [VILLE]\" et PAS \"à Rennes\" ou toute autre ville\n\n";
        $prompt .= "- **Orienté client** : \"vous\", \"votre maison\", \"votre projet\"\n\n";
        $prompt .= "- **Rassurant** : mentionne garanties, expertise, proximité\n\n";
        $prompt .= "- **Zéro blabla** : chaque phrase apporte une valeur concrète\n\n\n";
        $prompt .= "### 3. CONTENU TECHNIQUE OBLIGATOIRE\n\n";
        $prompt .= "Pour chaque service, détaille :\n\n";
        $prompt .= "- **Problématiques concrètes** des clients à [VILLE] ⚠️ Utilisez [VILLE]\n\n";
        $prompt .= "- **Solutions techniques précises** (matériaux, méthodes, étapes)\n\n";
        $prompt .= "- **Bénéfices mesurables** (durée de vie, économies, confort)\n\n";
        $prompt .= "- **Spécificités locales** (climat [DÉPARTEMENT], architecture [VILLE], réglementations)\n";
        $prompt .= "  ⚠️ Toujours utiliser les variables [VILLE] et [DÉPARTEMENT]\n\n\n";
        $prompt .= "Exemples de détails attendus :\n\n";
        $prompt .= "- \"Tuiles terre cuite traditionnelles pour une durée de vie de 50-70 ans\"\n\n";
        $prompt .= "- \"Isolation en laine minérale soufflée sur une épaisseur minimum de 320mm\"\n\n";
        $prompt .= "- \"Lavage à moyenne ou basse pression, souvent à l'eau chaude 100°C\"\n\n";
        $prompt .= "- \"Traitement hydrofuge haute qualité jusqu'à saturation du support\"\n\n\n";
        $prompt .= "### 4. STRUCTURE HTML SÉMANTIQUE\n\n\n";
        $prompt .= "🚨 ATTENTION : Générez UNIQUEMENT du HTML pur, PAS de Markdown !\n\n";
        $prompt .= "**Balises autorisées uniquement** :\n";
        $prompt .= "<h2>, <h3>, <h4>, <p>, <strong>, <em>, <br>\n\n";
        $prompt .= "**Interdictions absolues** :\n";
        $prompt .= "❌ PAS de Markdown : # ## ### ** ne sont PAS autorisés\n";
        $prompt .= "❌ PAS de <h1>, <div>, <span>, <style>, <script>, <html>, <head>, <body>\n";
        $prompt .= "❌ PAS de classes CSS, PAS d'attributs style\n";
        $prompt .= "❌ PAS de balises de code : ```html ou ``` \n\n";
        $prompt .= "✅ Exemple CORRECT :\n";
        $prompt .= "<h2>Titre principal</h2>\n";
        $prompt .= "<p>Paragraphe de texte avec <strong>texte en gras</strong>.</p>\n\n";
        $prompt .= "✅ Commencez IMMÉDIATEMENT par <h2>, pas de préambule\n\n";
        $prompt .= "**Hiérarchie stricte** :\n\n";
        $prompt .= "- H2 pour les sections principales (5-7 sections)\n\n";
        $prompt .= "- H3 pour les sous-sections (2-4 par H2)\n\n";
        $prompt .= "- Paragraphes courts : 3-5 lignes maximum\n\n";
        $prompt .= "- Listes à puces pour énumérations (3-6 items par liste)\n\n\n";
        $prompt .= "### 5. OPTIMISATION SEO NATURELLE\n\n";
        $prompt .= "- [VILLE] mentionnée **12-18 fois** naturellement dans le texte\n\n";
        $prompt .= "- [DÉPARTEMENT] mentionné **4-6 fois**\n\n";
        $prompt .= "- Mots-clés intégrés **fluidement** (densité 1-2%)\n\n";
        $prompt .= "- Variations sémantiques : \"couvreur\" → \"entreprise de couverture\", \"artisan toiture\"\n\n";
        $prompt .= "- Ancres géographiques : \"à [VILLE]\", \"dans le [DÉPARTEMENT]\", \"en [RÉGION]\"\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 📐 STRUCTURE EXACTE À SUIVRE\n\n\n";
        $prompt .= "### 🎯 SECTION 1 : DESCRIPTION COURTE (50-80 mots)\n\n";
        $prompt .= "Format :\n";
        $prompt .= "<p>Expert en " . strtolower($service_name) . " à [VILLE] dans le département [DÉPARTEMENT] ([CODE_POSTAL]). [1 phrase sur les solutions/bénéfices].</p>\n\n";
        $prompt .= "Exemple :\n";
        $prompt .= "<p>Expert en isolation à [VILLE] dans le département [DÉPARTEMENT] ([CODE_POSTAL]). Solutions efficaces pour une habitation confortable et économe en énergie.</p>\n\n\n";
        
        $prompt .= "### 📝 SECTION 2 : PRÉSENTATION (150-200 mots)\n\n";
        $prompt .= "<p>" . ($company_name ?: '[ENTREPRISE]') . " propose ses services de " . strtolower($service_name) . " à [VILLE] dans le département [DÉPARTEMENT], garantissant des solutions sur mesure pour [objectif principal]. Notre équipe qualifiée utilise des techniques modernes et des matériaux de qualité pour assurer [résultat]. Bénéficiez d'une intervention professionnelle, respectueuse de l'environnement et durable.</p>\n\n\n";
        
        $prompt .= "### ✅ SECTION 3 : GARANTIE (80-120 mots)\n\n";
        $prompt .= "<h2>Garantie satisfaction et performances</h2>\n";
        $prompt .= "<p>Chez " . ($company_name ?: '[ENTREPRISE]') . ", nous vous assurons une garantie décennale sur nos travaux de " . strtolower($service_name) . ", ainsi qu'un suivi personnalisé pour garantir votre entière satisfaction. Nous respectons les normes en vigueur et travaillons dans le souci de la propreté et de la sécurité sur chaque chantier.</p>\n\n\n";
        $prompt .= "### 🔧 SECTION 4 : NOS PRESTATIONS (OBLIGATOIRE - 10 services)\n\n";
        $prompt .= "Format EXACT à respecter :\n\n";
        $prompt .= "<h2>Nos Prestations " . strtolower($service_name) . "</h2>\n\n";
        $prompt .= "**Listez EXACTEMENT 10 prestations** au format :\n";
        $prompt .= "<p><strong>[Nom prestation]</strong> - [Description courte 25-40 mots expliquant les bénéfices]</p>\n\n";
        $prompt .= "Exemple pour isolation :\n";
        $prompt .= "<p><strong>Isolation combles perdus</strong> - Nous intervenons pour isoler vos combles perdus en utilisant des matériaux performants pour réduire les pertes de chaleur et améliorer le confort thermique de votre maison.</p>\n\n";
        $prompt .= "⚠️ PAS de <ul>, <ol> ou <li> - UNIQUEMENT des paragraphes <p>\n\n\n";
        $prompt .= "### ❓ SECTION 5 : FAQ (3-4 questions)\n\n";
        $prompt .= "Format :\n";
        $prompt .= "<h2>FAQ " . strtolower($service_name) . "</h2>\n\n";
        $prompt .= "Pour chaque question :\n";
        $prompt .= "<h3>[Question pertinente sur le service] ?</h3>\n";
        $prompt .= "<p>[Réponse détaillée 40-60 mots]</p>\n\n\n";
        $prompt .= "### 🚫 INTERDICTIONS\n\n";
        $prompt .= "❌ PAS de <ul>, <ol> ou <li>\n";
        $prompt .= "❌ PAS de section \"Pourquoi nous choisir\" longue\n";
        $prompt .= "❌ PAS de section contact détaillée\n";
        $prompt .= "❌ PAS de commentaires après le contenu\n\n";
        $prompt .= "✅ La structure doit être : Description courte → Présentation → Garantie → Nos Prestations (10) → FAQ (3-4)\n\n";
        $prompt .= "⚠️ LE CONTENU DOIT SE TERMINER APRÈS LA FAQ, RIEN D'AUTRE.\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 🎯 EXEMPLES DE CONTENU DE QUALITÉ\n\n\n";
        $prompt .= "### ❌ MAUVAIS (générique, creux)\n\n";
        $prompt .= "\"Nous proposons des solutions de qualité pour votre toiture. Notre équipe est professionnelle et expérimentée.\"\n\n\n";
        $prompt .= "### ✅ BON (précis, technique, local)\n\n";
        $prompt .= "\"Notre équipe de couvreurs professionnels assure non seulement la réparation des dégâts existants, mais aussi l'amélioration globale de la performance de votre toit. De la pose de tuiles terre cuite ou bac acier au traitement hydrofuge toiture haut de gamme, notre engagement est de vous fournir des solutions sur-mesure, durables et conformes aux règles de l'art.\"\n\n\n";
        $prompt .= "### ❌ MAUVAIS (vague)\n\n";
        $prompt .= "\"Nous utilisons des matériaux de qualité.\"\n\n\n";
        $prompt .= "### ✅ BON (détaillé)\n\n";
        $prompt .= "\"Tuiles : Pose et remplacement de tuiles béton, tuiles terre cuite traditionnelles et tuiles plates de pays. Durée de vie : 50-70 ans selon le matériau choisi. Ardoises : Installation d'ardoises naturelles (durée de vie 100+ ans) ou d'ardoises fibro-ciment pour une finition élégante et résistante.\"\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 🚫 INTERDICTIONS ABSOLUES\n\n\n";
        $prompt .= "### Contenu\n\n";
        $prompt .= "- ❌ Phrases creuses type \"leader sur le marché\", \"nous sommes les meilleurs\"\n\n";
        $prompt .= "- ❌ Répétitions inutiles du nom de l'entreprise (max 10-12 fois)\n\n";
        $prompt .= "- ❌ Sections trop courtes (<150 mots)\n\n";
        $prompt .= "- ❌ Manque de données techniques concrètes\n\n";
        $prompt .= "- ❌ Absence de géolocalisation naturelle\n\n\n";
        $prompt .= "### Structure\n\n";
        $prompt .= "- ❌ Sections markdown avec # ou ## (UNIQUEMENT HTML)\n\n";
        $prompt .= "- ❌ Titres non hiérarchisés\n\n";
        $prompt .= "- ❌ Paragraphes de plus de 6 lignes\n\n";
        $prompt .= "- ❌ Listes sans éléments <strong>\n\n";
        $prompt .= "- ❌ Absence de balises HTML\n\n\n";
        $prompt .= "### Format\n\n";
        $prompt .= "- ❌ Texte brut non formaté\n\n";
        $prompt .= "- ❌ Balises interdites (div, span, style)\n\n";
        $prompt .= "- ❌ Commentaires HTML\n\n";
        $prompt .= "- ❌ Wrapper <html> <body>\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## ✅ CHECKLIST QUALITÉ FINALE\n\n\n";
        $prompt .= "Avant de livrer, vérifie TOUS ces points :\n\n\n";
        $prompt .= "### Longueur et Structure\n\n";
        $prompt .= "- [ ] 2500-3500 mots au total\n\n";
        $prompt .= "- [ ] 7-8 sections H2 principales\n\n";
        $prompt .= "- [ ] 15-25 sous-sections H3\n\n";
        $prompt .= "- [ ] 6-8 FAQ avec réponses longues\n\n";
        $prompt .= "- [ ] Paragraphes 3-5 lignes max\n\n\n";
        $prompt .= "### Contenu et Qualité\n\n";
        $prompt .= "- [ ] Chaque section = 200+ mots minimum\n\n";
        $prompt .= "- [ ] Informations techniques précises (matériaux, méthodes, durées, épaisseurs)\n\n";
        $prompt .= "- [ ] Zéro phrase générique ou creuse\n\n";
        $prompt .= "- [ ] Contexte local omniprésent\n\n";
        $prompt .= "- [ ] Bénéfices concrets pour le client\n\n\n";
        $prompt .= "### SEO et Géolocalisation\n\n";
        $prompt .= "- [ ] [VILLE] présente 12-18 fois naturellement\n\n";
        $prompt .= "- [ ] [DÉPARTEMENT] présent 4-6 fois\n\n";
        $prompt .= "- [ ] Mots-clés intégrés fluidement\n\n";
        $prompt .= "- [ ] Variations sémantiques multiples\n\n";
        $prompt .= "- [ ] Title H2 principal optimisé\n\n\n";
        $prompt .= "### HTML et Format\n\n";
        $prompt .= "- [ ] HTML pur, valide, sémantique\n\n";
        $prompt .= "- [ ] Aucune balise interdite\n\n";
        $prompt .= "- [ ] Hiérarchie H2/H3 logique\n\n";
        $prompt .= "- [ ] Listes <ul> avec <strong>\n\n";
        $prompt .= "- [ ] Pas de wrapper extérieur\n\n\n";
        $prompt .= "### Conversion\n\n";
        $prompt .= "- [ ] Introduction engageante\n\n";
        $prompt .= "- [ ] Appels à l'action présents\n\n";
        $prompt .= "- [ ] Coordonnées complètes en fin\n\n";
        $prompt .= "- [ ] Éléments de réassurance (garanties, expertise)\n\n";
        $prompt .= "- [ ] Ton professionnel et local\n\n\n";
        $prompt .= "---\n\n\n";
        $prompt .= "## 🚀 INSTRUCTIONS FINALES\n\n\n";
        $prompt .= "1. **Lis attentivement** toutes les données fournies\n\n";
        $prompt .= "2. **Structure mentalement** l'article avant de rédiger\n\n";
        $prompt .= "3. **Rédige en HTML pur** dès le début (pas de markdown)\n\n";
        $prompt .= "4. **Intègre massivement** les détails techniques et locaux\n\n";
        $prompt .= "5. **Vérifie la checklist** avant de livrer\n\n";
        $prompt .= "6. **Livre un code HTML prêt** à coller dans WordPress\n\n\n\n";
        $prompt .= "**Génère maintenant un article HTML premium de 2500-3500 mots, dense en informations techniques, optimisé SEO et géolocalisé, respectant TOUTES les exigences ci-dessus.**\n";
    }
    
    $system_message = 'Tu es un rédacteur web senior spécialisé en BTP/couverture avec 10+ ans d\'expérience. Tu maîtrises parfaitement le vocabulaire technique du métier, les enjeux clients et les standards WordPress/SEO 2025.';
    
    // Générer le contenu principal avec plus de tokens pour un contenu de qualité
    $ai_response = $ai_service->call_ai($prompt, $system_message, array(
        'temperature' => 0.8,
        'max_tokens' => 4000,
    ));
    
    if (is_wp_error($ai_response)) {
        wp_send_json_error(array('message' => $ai_response->get_error_message()));
    }
    
    // Nettoyer la réponse de l'IA
    $content = $ai_response ?? '';
    
    // Supprimer les commentaires de validation à la fin
    $content = preg_replace('/\s*[-─═]{3,}.*$/s', '', $content);
    $content = preg_replace('/\s*✅.*$/s', '', $content);
    $content = preg_replace('/\s*\*\*Note.*$/s', '', $content);
    
    // Convertir le Markdown en HTML si l'IA a généré du Markdown
    $content = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $content);
    $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^#\s+(.+)$/m', '<h2>$1</h2>', $content);
    
    // Convertir le gras Markdown en HTML
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $content);
    
    // Convertir l'italique Markdown en HTML
    $content = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $content);
    $content = preg_replace('/_(.+?)_/s', '<em>$1</em>', $content);
    
    // Mettre à jour la réponse
    $ai_response = trim($content);
    
    // Demander à l'IA de générer les meta SEO selon les normes All in One SEO
    $meta_prompt = "Pour le service '$service_name' dans une ville [VILLE] du département [DÉPARTEMENT], génère des métadonnées SEO optimisées selon les normes All in One SEO. Réponds UNIQUEMENT au format JSON suivant (sans texte avant ou après) :\n\n";
    $meta_prompt .= "{\n";
    $meta_prompt .= "  \"meta_title\": \"titre SEO optimisé avec mot-clé principal en début (50-60 caractères max), format: [Service] [VILLE] [DÉPARTEMENT] | [Entreprise]\",\n";
    $meta_prompt .= "  \"meta_description\": \"description SEO engageante (150-160 caractères) incluant [VILLE] et [DÉPARTEMENT], avec bénéfice principal et CTA implicite\",\n";
    $meta_prompt .= "  \"meta_keywords\": \"mot-clé1, mot-clé2, mot-clé3 (optionnel, peu recommandé)\",\n";
    $meta_prompt .= "  \"og_title\": \"titre Open Graph (60-90 caractères)\",\n";
    $meta_prompt .= "  \"og_description\": \"description Open Graph (200-300 caractères) incluant [VILLE] et [DÉPARTEMENT]\",\n";
    $meta_prompt .= "  \"twitter_title\": \"titre Twitter (70 caractères max)\",\n";
    $meta_prompt .= "  \"twitter_description\": \"description Twitter (200 caractères max) incluant [VILLE] et [DÉPARTEMENT]\"\n";
    $meta_prompt .= "}\n\n";
    $meta_prompt .= "IMPORTANT : Les descriptions DOIVENT inclure [VILLE] et [DÉPARTEMENT] de manière naturelle. Le meta_title doit placer le mot-clé principal en début (poids SEO maximal).";
    
    $meta_response = $ai_service->call_ai($meta_prompt, 'Tu es un expert SEO spécialisé dans les normes All in One SEO. Tu génères des métadonnées optimisées au format JSON strict, en respectant les longueurs recommandées et en incluant systématiquement la localisation ([VILLE] et [DÉPARTEMENT]) dans les descriptions.', array(
        'temperature' => 0.7,
        'max_tokens' => 500,
    ));
    
    $meta_data = array();
    if (!is_wp_error($meta_response)) {
        // Essayer d'extraire le JSON de la réponse
        $json_start = strpos($meta_response, '{');
        $json_end = strrpos($meta_response, '}');
        if ($json_start !== false && $json_end !== false) {
            $json_str = substr($meta_response, $json_start, $json_end - $json_start + 1);
            $decoded = json_decode($json_str, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $meta_data = $decoded;
            }
        }
    }
    
    // Valeurs par défaut si l'IA n'a pas généré de meta (avec [VILLE] et [DÉPARTEMENT])
    $meta_title = $meta_data['meta_title'] ?? $service_name . ' [VILLE] [DÉPARTEMENT] | Service professionnel';
    $meta_description = $meta_data['meta_description'] ?? 'Service professionnel ' . strtolower($service_name) . ' à [VILLE] ([DÉPARTEMENT]). Intervention rapide et de qualité. Devis gratuit.';
    $meta_keywords = $meta_data['meta_keywords'] ?? strtolower($service_name) . ', [VILLE], [DÉPARTEMENT], service professionnel';
    $og_title = $meta_data['og_title'] ?? $meta_title;
    $og_description = $meta_data['og_description'] ?? ($meta_description ?: 'Service professionnel ' . strtolower($service_name) . ' à [VILLE] ([DÉPARTEMENT]). Intervention rapide et de qualité.');
    $twitter_title = $meta_data['twitter_title'] ?? $og_title;
    $twitter_description = $meta_data['twitter_description'] ?? $og_description;
    
    // Créer le post template
    $template_id = wp_insert_post(array(
        'post_title' => $service_name,
        'post_content' => $ai_response,
        'post_type' => 'ad_template',
        'post_status' => 'publish',
    ));
    
    if (is_wp_error($template_id)) {
        wp_send_json_error(array('message' => __('Erreur lors de la création du template', 'osmose-ads')));
    }
    
    // Définir l'image mise en avant
    if ($featured_image_id && wp_attachment_is_image($featured_image_id)) {
        set_post_thumbnail($template_id, $featured_image_id);
        update_post_meta($template_id, 'featured_image_id', $featured_image_id);
    }
    
    // Enregistrer les images de réalisations avec leurs mots-clés
    if (!empty($realization_images)) {
        $valid_images = array();
        $images_with_keywords = array();
        
        foreach ($realization_images as $img_id) {
            if (wp_attachment_is_image($img_id)) {
                $valid_images[] = $img_id;
                
                // Associer les mots-clés à l'image
                $img_keywords = isset($realization_keywords[$img_id]) ? $realization_keywords[$img_id] : '';
                if (!empty($img_keywords)) {
                    // Mettre à jour les mots-clés de l'image WordPress
                    update_post_meta($img_id, '_osmose_image_keywords', $img_keywords);
                }
                
                $images_with_keywords[] = array(
                    'id' => $img_id,
                    'keywords' => $img_keywords
                );
            }
        }
        
        if (!empty($valid_images)) {
            update_post_meta($template_id, 'realization_images', $valid_images);
            update_post_meta($template_id, 'realization_images_keywords', $images_with_keywords);
        }
    }
    
    // Enregistrer les meta
    update_post_meta($template_id, 'service_name', $service_name);
    update_post_meta($template_id, 'service_slug', $service_slug);
    update_post_meta($template_id, 'ai_prompt_used', $prompt);
    update_post_meta($template_id, 'ai_response_data', $ai_response);
    update_post_meta($template_id, 'meta_title', $meta_title);
    update_post_meta($template_id, 'meta_description', $meta_description);
    update_post_meta($template_id, 'meta_keywords', $meta_keywords);
    update_post_meta($template_id, 'og_title', $og_title);
    update_post_meta($template_id, 'og_description', $og_description);
    update_post_meta($template_id, 'twitter_title', $twitter_title);
    update_post_meta($template_id, 'twitter_description', $twitter_description);
    update_post_meta($template_id, 'is_active', true);
    update_post_meta($template_id, 'usage_count', 0);
    
    wp_send_json_success(array(
        'message' => __('Template créé avec succès avec images et métadonnées SEO', 'osmose-ads'),
        'template_id' => $template_id,
    ));
}

/**
 * Handler AJAX pour tracker les appels téléphoniques (accessible publiquement)
 */
function osmose_ads_track_call() {
    // Logger pour debug
    error_log('Osmose ADS: Track call handler called');
    error_log('Osmose ADS: POST data: ' . print_r($_POST, true));
    
    // Vérifier le nonce (moins strict pour le debug)
    $nonce = $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'osmose_ads_track_call')) {
        error_log('Osmose ADS: Nonce verification failed. Nonce received: ' . $nonce);
        // Ne pas bloquer pour le moment - continuer quand même
        // wp_send_json_error(array('message' => __('Erreur de sécurité', 'osmose-ads')));
        // return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
    
    // Vérifier que la table existe (elle devrait avoir été créée à l'activation)
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log('Osmose ADS: Call tracking table does not exist! Creating it now...');
        
        // Créer la table si elle n'existe pas (fallback)
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED,
            ad_slug varchar(255),
            page_url varchar(500),
            phone_number varchar(50),
            user_ip varchar(45),
            user_agent text,
            referrer varchar(500),
            call_time datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ad_id (ad_id),
            KEY idx_created_at (created_at),
            KEY idx_call_time (call_time),
            KEY idx_page_url (page_url(255))
        ) $charset_collate;";
        dbDelta($sql);
        
        // Vérifier à nouveau
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            error_log('Osmose ADS: Call tracking table created successfully');
        } else {
            error_log('Osmose ADS: ERROR - Failed to create call tracking table!');
            wp_send_json_error(array('message' => __('Impossible de créer la table de tracking', 'osmose-ads')));
            return;
        }
    } else {
        error_log('Osmose ADS: Call tracking table exists');
    }
    
    // Récupérer les données
    $ad_id = intval($_POST['ad_id'] ?? 0);
    $ad_slug = sanitize_text_field($_POST['ad_slug'] ?? '');
    $page_url = esc_url_raw($_POST['page_url'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    
    // Si page_url n'est pas défini, utiliser l'URL actuelle
    if (empty($page_url)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $page_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    // Récupérer les informations de l'utilisateur
    $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    $referrer = esc_url_raw($_SERVER['HTTP_REFERER'] ?? '');
    
    error_log('Osmose ADS: Inserting call tracking. Ad ID: ' . $ad_id . ', Slug: ' . $ad_slug . ', Phone: ' . $phone);
    
    // Enregistrer l'appel
    $result = $wpdb->insert(
        $table_name,
        array(
            'ad_id' => $ad_id ?: null,
            'ad_slug' => $ad_slug ?: '',
            'page_url' => $page_url ?: '',
            'phone_number' => $phone ?: '',
            'user_ip' => $user_ip ?: '',
            'user_agent' => $user_agent ?: '',
            'referrer' => $referrer ?: '',
            'call_time' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        $error = $wpdb->last_error;
        error_log('Osmose ADS: Database error: ' . $error);
        error_log('Osmose ADS: Last query: ' . $wpdb->last_query);
        wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement: ' . $error, 'osmose-ads')));
    } else {
        error_log('Osmose ADS: Call tracked successfully. Insert ID: ' . $wpdb->insert_id);
        wp_send_json_success(array('message' => __('Appel enregistré', 'osmose-ads'), 'insert_id' => $wpdb->insert_id));
    }
}

// Enregistrer les handlers AJAX pour le tracking
add_action('wp_ajax_osmose_ads_track_call', 'osmose_ads_track_call');
add_action('wp_ajax_nopriv_osmose_ads_track_call', 'osmose_ads_track_call'); // Accessible publiquement

function osmose_ads_handle_bulk_generate() {
    // Vérifier que les classes existent
    if (!class_exists('Ad_Template')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
    }
    
    $service_slug = sanitize_text_field($_POST['service_slug'] ?? '');
    $city_ids = array_map('intval', $_POST['city_ids'] ?? array());
    
    if (empty($service_slug) || empty($city_ids)) {
        wp_send_json_error(array('message' => __('Service et villes requis', 'osmose-ads')));
    }
    
    // Récupérer le template
    $template = Ad_Template::get_by_service_slug($service_slug);
    if (!$template) {
        wp_send_json_error(array('message' => __('Template non trouvé', 'osmose-ads')));
    }
    
    $created = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($city_ids as $city_id) {
        // Vérifier si l'annonce existe déjà
        $template_id = $template->post_id;
        $existing = get_posts(array(
            'post_type' => 'ad',
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'template_id', 'value' => $template_id, 'compare' => '='),
                array('key' => 'city_id', 'value' => $city_id, 'compare' => '='),
            ),
            'posts_per_page' => 1,
        ));
        
        if (!empty($existing)) {
            $skipped++;
            continue;
        }
        
        // Récupérer la ville
        $city = get_post($city_id);
        if (!$city) {
            $errors++;
            continue;
        }
        
        $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
        $service_name = get_post_meta($template_id, 'service_name', true);
        
        // Générer le slug
        $slug = $service_slug . '-' . sanitize_title($city_name);
        
        // Générer le contenu
        $content = $template->get_content_for_city($city_id);
        
        // Générer les métadonnées
        $meta = $template->get_meta_for_city($city_id);
        
        // Récupérer l'ID de la catégorie "Annonces"
        $category_id = get_option('osmose_ads_category_id');
        if (!$category_id) {
            // Créer la catégorie si elle n'existe pas
            $category_id = wp_create_category('Annonces');
            if (!is_wp_error($category_id)) {
                update_option('osmose_ads_category_id', $category_id);
            }
        }
        
        // Créer l'annonce
        $ad_id = wp_insert_post(array(
            'post_title' => $service_name . ' à ' . $city_name,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => 'ad',
            'post_status' => 'publish',
            'post_category' => $category_id ? array($category_id) : array(), // Assigner la catégorie "Annonces"
        ));
        
        if (is_wp_error($ad_id)) {
            $errors++;
            continue;
        }
        
        // Enregistrer les meta
        update_post_meta($ad_id, 'template_id', $template_id);
        update_post_meta($ad_id, 'city_id', $city_id);
        update_post_meta($ad_id, 'keyword', $service_name);
        update_post_meta($ad_id, 'status', 'published');
        update_post_meta($ad_id, 'published_at', current_time('mysql'));
        
        foreach ($meta as $key => $value) {
            if ($value) {
                update_post_meta($ad_id, $key, $value);
            }
        }
        
        // Incrémenter le compteur
        $template->increment_usage();
        
        $created++;
    }
    
    wp_send_json_success(array(
        'message' => sprintf(
            __('%d créées, %d ignorées, %d erreurs', 'osmose-ads'),
            $created,
            $skipped,
            $errors
        ),
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors,
    ));
}
