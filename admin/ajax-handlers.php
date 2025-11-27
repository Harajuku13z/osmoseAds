<?php
/**
 * Gestionnaires AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Construire le prompt pour un template d'annonce (sans ville spécifique)
 * Inspiré directement de la version Laravel fournie, adapté au service WordPress.
 */
function osmose_ads_build_template_prompt($service_name, $ai_prompt = '') {
    $base_prompt = "Tu es un expert technique en {$service_name} avec une connaissance PROFONDE des prestations, techniques et matériaux spécifiques à ce domaine. Crée un template d'annonce TOTALEMENT personnalisé pour {$service_name}, destiné à une page de service WordPress.\n\n";

    $base_prompt .= "⚠️⚠️⚠️ SERVICE À PERSONNALISER: {$service_name} ⚠️⚠️⚠️\n\n";

    $base_prompt .= "🚫 INTERDICTIONS ABSOLUES:\n";
    $base_prompt .= "- INTERDIT d'utiliser des prestations génériques comme 'Diagnostic', 'Conseil', 'Maintenance générale', 'Installation professionnelle'\n";
    $base_prompt .= "- INTERDIT de copier du contenu générique applicable à tous les services\n";
    $base_prompt .= "- INTERDIT d'utiliser un vocabulaire vague ou général\n\n";

    // Forcer l'utilisation exclusive des placeholders ville/région pour éviter les cas comme "Paris" ou "Île-de-France"
    $base_prompt .= "⚠️ VILLES ET RÉGIONS ⚠️\n";
    $base_prompt .= "- INTERDIT ABSOLUMENT d'utiliser des noms de villes ou régions RÉELS (ex: Paris, Lyon, Marseille, Île-de-France, Bretagne, Normandie, etc.).\n";
    $base_prompt .= "- TU DOIS TOUJOURS utiliser UNIQUEMENT les placeholders [VILLE], [RÉGION], [DÉPARTEMENT], [CODE_POSTAL] dans tout le texte.\n";
    $base_prompt .= "- Si tu veux donner un exemple de lieu, tu utilises [VILLE] ou [RÉGION], JAMAIS une ville réelle.\n\n";

    $base_prompt .= "✅ OBLIGATIONS ABSOLUES POUR {$service_name}:\n";
    $base_prompt .= "- Chaque prestation DOIT être TECHNIQUE et SPÉCIFIQUE UNIQUEMENT à {$service_name}\n";
    $base_prompt .= "- Utilise le vocabulaire PROFESSIONNEL du métier de {$service_name}\n";
    $base_prompt .= "- Les prestations doivent mentionner des techniques, matériaux ou méthodes PRÉCISES liés à {$service_name}\n";
    $base_prompt .= "- Chaque description doit expliquer QUOI, COMMENT et POURQUOI spécifiquement pour {$service_name}\n\n";

    $base_prompt .= "IMPORTANT:\n";
    $base_prompt .= "- TU NE DOIS PAS REPRENDRE D'EXEMPLES DE PRESTATIONS GÉNÉRIQUES QUE TU CONNAIS DÉJÀ (comme ceux utilisés pour la toiture ou la plomberie).\n";
    $base_prompt .= "- POUR CHAQUE SERVICE, TU DOIS INVENTER DES PRESTATIONS UNIQUES, TRÈS SPÉCIFIQUES ET ADAPTÉES UNIQUEMENT À {$service_name}.\n\n";

    // Récupérer le nom de l'entreprise pour l'inclure dans le prompt
    $company_name = get_bloginfo('name');
    
    $base_prompt .= "GÉNÈRE UN JSON AVEC CES CHAMPS:\n\n";
    $base_prompt .= "{\n";
    $base_prompt .= "  \"description\": \"[GÉNÈRE ICI UN HTML COMPLET POUR UNE PAGE DE SERVICE WORDPRESS EN {$service_name}. LE HTML DOIT INCLURE: (1) UN TITRE &lt;h1&gt; avec le format: 'Expert en {$service_name} à [VILLE] dans le département [DÉPARTEMENT]' suivi d'une phrase d'accroche technique; (2) 2 À 3 PARAGRAPHES D'INTRODUCTION ORIGINAUX, TECHNIQUES ET SPÉCIFIQUES À {$service_name}, qui mentionnent le nom de l'entreprise [ENTREPRISE] et expliquent le contexte, les enjeux et les bénéfices pour le client à [VILLE] et en [RÉGION]. Les paragraphes doivent être détaillés (minimum 2-3 phrases chacun) et mentionner des techniques, matériaux ou méthodes spécifiques; (3) UNE SECTION 'Garantie satisfaction et performances' avec 1-2 paragraphes sur les garanties, le suivi personnalisé, le respect des normes, la propreté et la sécurité; (4) UNE SECTION 'Nos Prestations {$service_name}' AVEC UNE LISTE &lt;ul&gt; DE 10 PRESTATIONS TRÈS SPÉCIFIQUES ET TECHNIQUES AU SERVICE. CHAQUE PRESTATION DOIT AVOIR UN NOM TECHNIQUE PRÉCIS (ex: 'Isolation combles perdus', 'Isolation toiture', 'Traitement ponts thermiques' pour isolation) ET UNE DESCRIPTION DÉTAILLÉE DE 2-3 PHRASES EXPLIQUANT LA TECHNIQUE, LES MATÉRIAUX ET LES BÉNÉFICES. Format: &lt;li&gt;&lt;strong&gt;Nom technique de la prestation&lt;/strong&gt; - Description détaillée technique avec matériaux et bénéfices.&lt;/li&gt;; (5) UNE SECTION 'FAQ {$service_name}' AVEC 4 QUESTIONS TECHNIQUES ET DÉTAILLÉES avec des réponses complètes (minimum 2-3 phrases par réponse). IMPORTANT: INCLUS AU MOINS 2-3 LIENS INTERNES (vers d'autres pages du site) OU EXTERNES (vers des ressources pertinentes) DANS LE CONTENU POUR AMÉLIORER LE SEO. UTILISE UNE STRUCTURE MODERNE AVEC &lt;div class='space-y-6'&gt;, &lt;h1&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;a href='...'&gt;, MAIS TU DOIS RÉDIGER TOUS LES TEXTES TOI-MÊME, SANS REPRENDRE D'EXEMPLES GÉNÉRIQUES. REMPLACE [ENTREPRISE] par le nom de l'entreprise dans le contenu.]\"," . "\n";
    $base_prompt .= "  \"short_description\": \"[RÉSUME EN UNE PHRASE CLAIRE ET ATTRACTIVE LE SERVICE {$service_name} À [VILLE], AVEC UN ANGLE TECHNIQUE ET COMMERCIAL FORT, SANS ÊTRE GÉNÉRIQUE]\",\n";
    $base_prompt .= "  \"long_description\": \"[RÉDIGER 2 À 3 PHRASES EXPLICATIVES SUR NOTRE SERVICE DE {$service_name} À [VILLE] ET EN [RÉGION], EN INSISTANT SUR L'EXPERTISE TECHNIQUE, LES TYPES D'INTERVENTIONS, LES MATÉRIAUX UTILISÉS ET LES GARANTIES. LE TEXTE DOIT ÊTRE UNIQUE ET SPÉCIFIQUE À {$service_name}, PAS UN TEXTE GÉNÉRIQUE APPLICABLE À TOUS LES MÉTIERS.]\",\n";
    $base_prompt .= "  \"icon\": \"fas fa-tools\",\n";
    $base_prompt .= "  \"meta_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
    $base_prompt .= "  \"meta_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.\",\n";
    $base_prompt .= "  \"og_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
    $base_prompt .= "  \"og_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.\",\n";
    $base_prompt .= "  \"twitter_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
    $base_prompt .= "  \"twitter_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.\",\n";
    $base_prompt .= "  \"meta_keywords\": \"{$service_name}, [VILLE], [RÉGION], service professionnel, devis gratuit\"\n";
    $base_prompt .= "}\n\n";

    $base_prompt .= "⚠️⚠️⚠️ INSTRUCTIONS CRITIQUES - FORMAT JSON ⚠️⚠️⚠️:\n";
    $base_prompt .= "- TU DOIS RÉPONDRE UNIQUEMENT AVEC UN JSON VALIDE\n";
    $base_prompt .= "- COMMENCE DIRECTEMENT PAR { (accolade ouvrante)\n";
    $base_prompt .= "- TERMINE DIRECTEMENT PAR } (accolade fermante)\n";
    $base_prompt .= "- PAS de texte avant le JSON\n";
    $base_prompt .= "- PAS de texte après le JSON\n";
    $base_prompt .= "- PAS de ```json ou ``` autour du JSON\n";
    $base_prompt .= "- PAS de commentaires ou explications\n";
    $base_prompt .= "- JUSTE le JSON brut\n\n";

    $base_prompt .= "⚠️⚠️⚠️ INSTRUCTIONS CRITIQUES - CONTENU ⚠️⚠️⚠️:\n";
    $base_prompt .= "- REMPLACE TOUT le contenu par du contenu VRAIMENT spécifique à {$service_name}\n";
    $base_prompt .= "- REMPLACE [GÉNÈRE 10 PRESTATIONS SPÉCIFIQUES À {$service_name}] par 10 prestations TECHNIQUES RÉELLES pour {$service_name}\n";
    $base_prompt .= "- Chaque prestation doit avoir un NOM TECHNIQUE précis (ex: 'Isolation combles perdus', 'Isolation toiture', 'Traitement ponts thermiques' pour isolation) et une DESCRIPTION DÉTAILLÉE de 2-3 phrases avec techniques/matériaux/bénéfices pour {$service_name}\n";
    $base_prompt .= "- PERSONNALISE les descriptions, FAQ, et tous les textes pour {$service_name} spécifiquement\n";
    $base_prompt .= "- Utilise [VILLE], [RÉGION], [DÉPARTEMENT], [ENTREPRISE] comme placeholders pour les variables dynamiques\n";
    $base_prompt .= "- Le contenu HTML doit être COMPLET et PERSONNALISÉ, pas un template copié-collé\n";
    $base_prompt .= "- INCLUS le nom de l'entreprise [ENTREPRISE] dans l'introduction (ex: '[ENTREPRISE] propose ses services...')\n";
    $base_prompt .= "- INCLUS une section 'Garantie satisfaction et performances' avec 1-2 paragraphes sur les garanties, suivi personnalisé, normes, propreté, sécurité\n";
    $base_prompt .= "- NE PAS ajouter de sections supplémentaires comme 'Pourquoi choisir ce service', 'Notre Expertise Locale', 'Financement et aides', 'Informations pratiques' ou des blocs de partage (Facebook, WhatsApp, Email...). Ces éléments sont gérés par le thème WordPress.\n\n";

    $base_prompt .= "EXEMPLES CONCRETS DE PRESTATIONS TECHNIQUES SPÉCIFIQUES:\n";
    $base_prompt .= "- Si {$service_name} = 'Isolation' → prestations: 'Isolation combles perdus - Description technique avec matériaux', 'Isolation toiture - Description technique', 'Traitement ponts thermiques - Description technique', 'Isolation murs - Description technique', 'Isolation sols - Description technique', 'Isolation phonique - Description technique', 'Isolation thermique par l'extérieur - Description technique', 'Isolation écologique - Description technique', 'Isolation sous rampant - Description technique', 'Isolation par insufflation - Description technique'\n";
    $base_prompt .= "- Si {$service_name} = 'Couvreur' → prestations: 'Réfection toiture ardoise - Description technique', 'Pose tuiles canal - Description technique', 'Installation écran de sous-toiture - Description technique', 'Traitement charpente - Description technique', 'Pose zinguerie - Description technique', etc.\n";
    $base_prompt .= "- Si {$service_name} = 'Désamiantage' → prestations: 'Dépollution amiante sous confinement - Description technique', 'Retrait amiante friable - Description technique', 'Gestion déchets amiante - Description technique', etc.\n";
    $base_prompt .= "\n";
    $base_prompt .= "⚠️ CRITIQUE - QUALITÉ DES PRESTATIONS:\n";
    $base_prompt .= "- Chaque prestation DOIT avoir un NOM TECHNIQUE PRÉCIS (pas 'Diagnostic' ou 'Conseil' générique)\n";
    $base_prompt .= "- Chaque prestation DOIT avoir une DESCRIPTION DÉTAILLÉE de 2-3 phrases expliquant:\n";
    $base_prompt .= "  * LA TECHNIQUE utilisée\n";
    $base_prompt .= "  * LES MATÉRIAUX employés\n";
    $base_prompt .= "  * LES BÉNÉFICES pour le client\n";
    $base_prompt .= "- Les prestations doivent être UNIQUES à {$service_name}, pas applicables à d'autres services\n";

    if (!empty($ai_prompt)) {
        $base_prompt .= "\nINSTRUCTIONS PERSONNALISÉES SUPPLÉMENTAIRES:\n" . $ai_prompt;
    }

    return $base_prompt;
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

    // Gérer les images de réalisations envoyées soit comme tableau (modal de création rapide),
    // soit comme chaîne CSV (page de création simplifiée)
    $realization_images = array();
    if (isset($_POST['realization_images'])) {
        if (is_array($_POST['realization_images'])) {
            $realization_images = array_map('intval', $_POST['realization_images']);
        } else {
            $csv = sanitize_text_field($_POST['realization_images']);
            if (!empty($csv)) {
                $realization_images = array_filter(array_map('intval', explode(',', $csv)));
            }
        }
    }
    
    // Récupérer les mots-clés associés aux images de réalisations (deux formats possibles)
    $realization_keywords = array();
    // Format 1: tableau associatif envoyé sous le nom realization_keywords[image_id] (modal avancée)
    if (isset($_POST['realization_keywords']) && is_array($_POST['realization_keywords'])) {
        foreach ($_POST['realization_keywords'] as $img_id => $kw) {
            $realization_keywords[intval($img_id)] = sanitize_text_field($kw);
        }
    }
    // Format 2: chaîne CSV parallèle (ids dans realization_images, mots-clés dans realization_images_keywords)
    if (empty($realization_keywords) && isset($_POST['realization_images_keywords']) && !empty($realization_images)) {
        $keywords_csv = sanitize_text_field($_POST['realization_images_keywords']);
        if (!empty($keywords_csv)) {
            $keywords_list = explode('|||', $keywords_csv);
            foreach ($realization_images as $index => $img_id) {
                if (isset($keywords_list[$index])) {
                    $realization_keywords[$img_id] = sanitize_text_field($keywords_list[$index]);
                }
            }
        }
    }
    
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
    
    // Appeler l'IA pour générer le contenu en DEUX ÉTAPES
    $ai_service = new AI_Service();
    
    // ========== ÉTAPE 1 : Générer un JSON structuré avec les données brutes ==========
    $company_name = get_bloginfo('name');
    $step1_prompt = "Tu es un expert technique en {$service_name} avec une connaissance PROFONDE des prestations, techniques et matériaux spécifiques à ce domaine.\n\n";
    $step1_prompt .= "GÉNÈRE UN JSON COMPLET avec du CONTENU RÉEL et TECHNIQUE pour {$service_name}.\n\n";
    
    // Construire un exemple JSON complet selon le type de service
    $service_lower = strtolower($service_name);
    $example_json = '';
    
    if (stripos($service_lower, 'couvreur') !== false || stripos($service_lower, 'toiture') !== false) {
        $example_json = "{\n";
        $example_json .= "  \"title\": \"Expert en {$service_name} à [VILLE] dans le département [DÉPARTEMENT]\",\n";
        $example_json .= "  \"title_subtitle\": \"Expertise reconnue en réfection de toiture et zinguerie pour une protection durable\",\n";
        $example_json .= "  \"intro_paragraphs\": [\n";
        $example_json .= "    \"En tant que couvreur professionnel à [VILLE], {$company_name} intervient pour tous vos besoins en toiture. Notre équipe maîtrise les techniques de pose d'ardoise, de tuiles canal et de zinguerie, garantissant une étanchéité parfaite et une longévité optimale de votre toit.\",\n";
        $example_json .= "    \"Nous utilisons exclusivement des matériaux de qualité supérieure, conformes aux normes en vigueur, pour assurer la résistance de votre toiture aux intempéries de [RÉGION]. Chaque intervention est réalisée dans le respect des règles de l'art et des standards professionnels.\",\n";
        $example_json .= "    \"Que vous ayez besoin d'une réfection complète, d'une réparation d'urgence ou d'un entretien préventif, nos artisans qualifiés vous proposent des solutions sur mesure adaptées à votre budget et à vos contraintes.\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"guarantee_title\": \"Garantie satisfaction et performances\",\n";
        $example_json .= "  \"guarantee_paragraphs\": [\n";
        $example_json .= "    \"Chez {$company_name}, nous vous assurons une garantie décennale sur tous nos travaux de couverture, conformément à la législation en vigueur. Chaque intervention bénéficie d'un suivi personnalisé pour garantir votre entière satisfaction et la pérennité de votre toiture.\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"prestations_title\": \"Nos Prestations " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"prestations\": [\n";
        $example_json .= "    {\"name\": \"Réfection toiture ardoise\", \"description\": \"Nous réalisons la réfection complète de votre toiture en ardoise naturelle, matériau noble et durable. Notre équipe maîtrise les techniques de pose traditionnelle et moderne, garantissant une étanchéité parfaite et une esthétique soignée. L'ardoise offre une résistance exceptionnelle aux intempéries et une longévité de 50 à 100 ans.\"},\n";
        $example_json .= "    {\"name\": \"Pose zinguerie\", \"description\": \"La zinguerie est essentielle pour protéger les points sensibles de votre toiture. Nous installons des éléments de zinguerie en zinc ou en aluminium, garantissant une étanchéité parfaite aux jonctions et une protection durable contre les infiltrations d'eau.\"},\n";
        $example_json .= "    {\"name\": \"Réparation toiture d'urgence\", \"description\": \"En cas de fuite ou de dommage causé par une tempête, nous intervenons rapidement pour sécuriser votre toiture et éviter les dégâts des eaux. Notre équipe est disponible 24/7 pour les interventions d'urgence.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"faq_title\": \"FAQ " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"faq_questions\": [\n";
        $example_json .= "    {\"question\": \"Quand faut-il refaire sa toiture ?\", \"answer\": \"Il est recommandé de refaire sa toiture lorsque les tuiles ou ardoises présentent des signes d'usure importants, des fuites récurrentes, ou après une tempête ayant causé des dommages. Une inspection régulière par un professionnel permet d'anticiper les travaux nécessaires.\"},\n";
        $example_json .= "    {\"question\": \"Quelle est la durée de vie d'une toiture ?\", \"answer\": \"La durée de vie d'une toiture dépend du matériau utilisé : une toiture en ardoise peut durer 50 à 100 ans, une toiture en tuiles 30 à 50 ans. Un entretien régulier prolonge significativement la durée de vie de votre toiture.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"short_description\": \"Service professionnel de {$service_name} à [VILLE], spécialisé en réfection, réparation et entretien de toiture.\",\n";
        $example_json .= "  \"long_description\": \"{$company_name} propose ses services de {$service_name} à [VILLE] et en [RÉGION]. Notre expertise couvre la réfection complète, la réparation d'urgence, la pose de zinguerie et l'entretien préventif. Nous utilisons des matériaux de qualité supérieure et respectons les normes en vigueur pour garantir la durabilité de votre toiture.\",\n";
        $example_json .= "  \"meta_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"meta_description\": \"Service professionnel de {$service_name} à [VILLE]. Réfection, réparation et entretien de toiture. Devis gratuit, intervention rapide, garantie décennale.\",\n";
        $example_json .= "  \"meta_keywords\": \"{$service_name}, [VILLE], [RÉGION], réfection toiture, réparation toiture, zinguerie, ardoise, tuiles\",\n";
        $example_json .= "  \"og_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"og_description\": \"Service professionnel de {$service_name} à [VILLE]. Réfection, réparation et entretien de toiture. Devis gratuit, intervention rapide.\",\n";
        $example_json .= "  \"twitter_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"twitter_description\": \"Service professionnel de {$service_name} à [VILLE]. Réfection, réparation et entretien de toiture. Devis gratuit.\",\n";
        $example_json .= "  \"icon\": \"fas fa-tools\"\n";
        $example_json .= "}\n";
    } elseif (stripos($service_lower, 'isolation') !== false) {
        $example_json = "{\n";
        $example_json .= "  \"title\": \"Expert en {$service_name} à [VILLE] dans le département [DÉPARTEMENT]\",\n";
        $example_json .= "  \"title_subtitle\": \"Solutions d'isolation performantes pour réduire vos factures énergétiques\",\n";
        $example_json .= "  \"intro_paragraphs\": [\n";
        $example_json .= "    \"Spécialiste de l'isolation thermique à [VILLE], {$company_name} vous propose des solutions performantes pour améliorer le confort de votre habitation et réduire vos dépenses énergétiques. Nous intervenons sur tous types de bâtiments, en utilisant des matériaux écologiques et performants.\",\n";
        $example_json .= "    \"Notre expertise couvre l'isolation des combles perdus, des murs, des sols et des toitures, avec des techniques adaptées à chaque configuration. Nous privilégions les matériaux naturels comme la ouate de cellulose, la laine de roche ou le polystyrène expansé pour garantir des performances optimales.\",\n";
        $example_json .= "    \"Chaque intervention est précédée d'un diagnostic thermique approfondi pour identifier les ponts thermiques et les zones de déperdition. Nous vous proposons ensuite une solution sur mesure, respectueuse de l'environnement et conforme aux normes RT 2012 et RE 2020.\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"guarantee_title\": \"Garantie satisfaction et performances\",\n";
        $example_json .= "  \"guarantee_paragraphs\": [\n";
        $example_json .= "    \"Chez {$company_name}, nous garantissons des performances énergétiques optimales pour tous nos travaux d'isolation. Chaque intervention est suivie d'un contrôle qualité pour vérifier l'efficacité de l'isolation et vous assurer des économies d'énergie significatives.\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"prestations_title\": \"Nos Prestations " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"prestations\": [\n";
        $example_json .= "    {\"name\": \"Isolation combles perdus\", \"description\": \"L'isolation des combles perdus est l'intervention la plus rentable pour réduire vos pertes de chaleur. Nous utilisons la technique d'insufflation de ouate de cellulose ou de laine de roche, garantissant une isolation homogène et performante. Cette solution permet de réduire jusqu'à 30% vos factures de chauffage.\"},\n";
        $example_json .= "    {\"name\": \"Isolation murs par l'extérieur\", \"description\": \"L'isolation thermique par l'extérieur améliore l'efficacité énergétique de votre maison tout en préservant l'espace intérieur. Nous utilisons des panneaux isolants performants et un enduit de finition pour un résultat esthétique et durable.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"faq_title\": \"FAQ " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"faq_questions\": [\n";
        $example_json .= "    {\"question\": \"Quels sont les avantages de l'isolation des combles perdus ?\", \"answer\": \"L'isolation des combles perdus permet de limiter les pertes de chaleur, de réduire les factures de chauffage et d'améliorer le confort thermique de votre maison. C'est une solution efficace et rentable qui peut réduire jusqu'à 30% vos dépenses énergétiques.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"short_description\": \"Service professionnel d'isolation thermique à [VILLE], pour améliorer votre confort et réduire vos factures énergétiques.\",\n";
        $example_json .= "  \"long_description\": \"{$company_name} propose ses services d'isolation thermique à [VILLE] et en [RÉGION]. Notre expertise couvre l'isolation des combles, des murs, des sols et des toitures, avec des matériaux écologiques et performants conformes aux normes RT 2012 et RE 2020.\",\n";
        $example_json .= "  \"meta_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"meta_description\": \"Service professionnel d'isolation thermique à [VILLE]. Isolation combles, murs, sols. Devis gratuit, matériaux écologiques, conformité RT 2012.\",\n";
        $example_json .= "  \"meta_keywords\": \"{$service_name}, [VILLE], [RÉGION], isolation thermique, isolation combles, isolation murs, économies d'énergie\",\n";
        $example_json .= "  \"og_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"og_description\": \"Service professionnel d'isolation thermique à [VILLE]. Isolation combles, murs, sols. Devis gratuit.\",\n";
        $example_json .= "  \"twitter_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"twitter_description\": \"Service professionnel d'isolation thermique à [VILLE]. Isolation combles, murs, sols. Devis gratuit.\",\n";
        $example_json .= "  \"icon\": \"fas fa-tools\"\n";
        $example_json .= "}\n";
    } else {
        // Exemple générique
        $example_json = "{\n";
        $example_json .= "  \"title\": \"Expert en {$service_name} à [VILLE] dans le département [DÉPARTEMENT]\",\n";
        $example_json .= "  \"title_subtitle\": \"Service professionnel de qualité pour tous vos besoins en {$service_name}\",\n";
        $example_json .= "  \"intro_paragraphs\": [\n";
        $example_json .= "    \"En tant que spécialiste en {$service_name} à [VILLE], {$company_name} vous propose des solutions professionnelles adaptées à vos besoins. Notre équipe qualifiée maîtrise les techniques les plus récentes pour garantir des résultats optimaux.\",\n";
        $example_json .= "    \"Nous intervenons sur tous types de projets, en utilisant des matériaux de qualité supérieure et en respectant les normes en vigueur. Chaque intervention est réalisée avec professionnalisme et précision pour assurer votre satisfaction.\",\n";
        $example_json .= "    \"Que vous ayez besoin d'une intervention ponctuelle ou d'un suivi régulier, nos experts vous accompagnent dans tous vos projets de {$service_name} à [VILLE] et en [RÉGION].\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"guarantee_title\": \"Garantie satisfaction et performances\",\n";
        $example_json .= "  \"guarantee_paragraphs\": [\n";
        $example_json .= "    \"Chez {$company_name}, nous vous assurons une garantie sur tous nos travaux de {$service_name}. Chaque intervention bénéficie d'un suivi personnalisé pour garantir votre entière satisfaction et la qualité des prestations réalisées.\"\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"prestations_title\": \"Nos Prestations " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"prestations\": [\n";
        $example_json .= "    {\"name\": \"Prestation technique 1\", \"description\": \"Description détaillée de la première prestation avec techniques et matériaux spécifiques pour garantir des résultats optimaux à [VILLE]. Cette prestation permet d'améliorer significativement la qualité et la performance.\"},\n";
        $example_json .= "    {\"name\": \"Prestation technique 2\", \"description\": \"Description détaillée de la deuxième prestation avec techniques et matériaux spécifiques pour garantir des résultats optimaux à [VILLE]. Cette prestation offre des avantages significatifs en termes de durabilité.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"faq_title\": \"FAQ " . strtolower($service_name) . "\",\n";
        $example_json .= "  \"faq_questions\": [\n";
        $example_json .= "    {\"question\": \"Quels sont les avantages de faire appel à un professionnel pour {$service_name} ?\", \"answer\": \"Faire appel à un professionnel garantit une intervention de qualité, conforme aux normes en vigueur, avec des matériaux adaptés et une garantie sur les travaux réalisés. Un professionnel saura vous conseiller sur les meilleures solutions pour votre projet.\"}\n";
        $example_json .= "  ],\n";
        $example_json .= "  \"short_description\": \"Service professionnel de {$service_name} à [VILLE], pour tous vos besoins.\",\n";
        $example_json .= "  \"long_description\": \"{$company_name} propose ses services de {$service_name} à [VILLE] et en [RÉGION]. Notre expertise technique et notre savoir-faire garantissent des interventions de qualité adaptées à vos besoins spécifiques.\",\n";
        $example_json .= "  \"meta_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"meta_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.\",\n";
        $example_json .= "  \"meta_keywords\": \"{$service_name}, [VILLE], [RÉGION], service professionnel, devis gratuit\",\n";
        $example_json .= "  \"og_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"og_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit, intervention rapide.\",\n";
        $example_json .= "  \"twitter_title\": \"{$service_name} à [VILLE] - Service professionnel\",\n";
        $example_json .= "  \"twitter_description\": \"Service professionnel de {$service_name} à [VILLE]. Devis gratuit.\",\n";
        $example_json .= "  \"icon\": \"fas fa-tools\"\n";
        $example_json .= "}\n";
    }
    
    $step1_prompt .= "VOICI UN EXEMPLE DE STRUCTURE JSON (l'exemple montre seulement 2-3 prestations, mais tu DOIS en générer 10) :\n\n";
    $step1_prompt .= $example_json . "\n\n";
    $step1_prompt .= "IMPORTANT : L'exemple ci-dessus montre la STRUCTURE, mais tu DOIS générer un JSON COMPLET avec :\n\n";
    $step1_prompt .= "OBLIGATOIRE - PRESTATIONS :\n";
    $step1_prompt .= "- Génère EXACTEMENT 10 prestations différentes et spécifiques à {$service_name}\n";
    $step1_prompt .= "- Chaque prestation DOIT avoir un nom technique précis et unique (ex: 'Réfection toiture ardoise', 'Pose zinguerie', 'Isolation combles perdus')\n";
    $step1_prompt .= "- Chaque description DOIT être détaillée (2-3 phrases) expliquant : la technique utilisée, les matériaux employés, et les bénéfices pour le client\n";
    $step1_prompt .= "- Les prestations doivent être ADAPTÉES au service {$service_name} (pas génériques)\n";
    $step1_prompt .= "- Exemples de prestations pour {$service_name} : réfléchis aux interventions réelles d'un professionnel de ce domaine\n\n";
    $step1_prompt .= "OBLIGATOIRE - FAQ :\n";
    $step1_prompt .= "- Génère EXACTEMENT 4 questions différentes et spécifiques à {$service_name}\n";
    $step1_prompt .= "- Chaque question DOIT être technique et détaillée, adaptée au service {$service_name}\n";
    $step1_prompt .= "- Chaque réponse DOIT être complète (2-3 phrases) avec des informations techniques et pratiques\n";
    $step1_prompt .= "- Les questions doivent couvrir différents aspects : prix, délais, matériaux, garanties, techniques, etc.\n\n";
    $step1_prompt .= "AUTRES CHAMPS :\n";
    $step1_prompt .= "- 3 paragraphes d'introduction techniques et spécifiques à {$service_name}, mentionnant {$company_name}\n";
    $step1_prompt .= "- Tous les autres champs remplis avec du contenu réel et adapté à {$service_name}\n\n";
    $step1_prompt .= "RÈGLES STRICTES - PLACEHOLDERS GÉOGRAPHIQUES :\n";
    $step1_prompt .= "⚠️ INTERDICTION ABSOLUE d'utiliser des noms de villes, départements ou régions réels (Rennes, Paris, Ille-et-Vilaine, Bretagne, etc.)\n";
    $step1_prompt .= "⚠️ Tu DOIS utiliser UNIQUEMENT ces placeholders exacts :\n";
    $step1_prompt .= "   - [VILLE] pour la ville (PAS Rennes, Paris, Lyon, etc.)\n";
    $step1_prompt .= "   - [DÉPARTEMENT] pour le département (PAS Ille-et-Vilaine, Paris, Rhône, etc.)\n";
    $step1_prompt .= "   - [RÉGION] pour la région (PAS Bretagne, Île-de-France, Auvergne-Rhône-Alpes, etc.)\n";
    $step1_prompt .= "   - [ENTREPRISE] pour le nom de l'entreprise (sera remplacé automatiquement)\n";
    $step1_prompt .= "⚠️ Si tu utilises un nom de ville/département/région réel, le template sera refusé\n\n";
    $step1_prompt .= "RÈGLES STRICTES - CONTENU :\n";
    $step1_prompt .= "1. GÉNÈRE DU CONTENU RÉEL et TECHNIQUE spécifique à {$service_name}, pas de placeholders ou d'instructions\n";
    $step1_prompt .= "2. Les 10 prestations DOIVENT être différentes et adaptées à {$service_name}\n";
    $step1_prompt .= "3. Les 4 questions FAQ DOIVENT être différentes et adaptées à {$service_name}\n";
    $step1_prompt .= "4. Réponds UNIQUEMENT avec le JSON valide complet, sans texte avant ou après\n";
    
    $step1_system = 'Tu es un expert technique en ' . $service_name . '. Tu génères du CONTENU RÉEL et TECHNIQUE spécifique à ' . $service_name . '. OBLIGATOIRE : utilise UNIQUEMENT les placeholders [VILLE], [DÉPARTEMENT], [RÉGION], [ENTREPRISE] - INTERDICTION d\'utiliser des noms de villes/départements/régions réels. Génère EXACTEMENT 10 prestations différentes et 4 questions FAQ différentes, toutes adaptées au service ' . $service_name . '. Chaque champ du JSON doit contenir du texte réel et complet. Réponds UNIQUEMENT en JSON valide, sans texte avant ou après.';
    
    // Premier appel IA
    $step1_response = $ai_service->call_ai($step1_prompt, $step1_system, array(
        'temperature' => 0.8,
        'max_tokens' => 4000, // Augmenté pour permettre 10 prestations + 4 FAQ
    ));
    
    if (is_wp_error($step1_response)) {
        wp_send_json_error(array('message' => 'Erreur lors de la génération des données : ' . $step1_response->get_error_message()));
    }
    
    // Extraire le JSON de la première réponse
    $json_start = strpos($step1_response, '{');
    $json_end = strrpos($step1_response, '}');
    if ($json_start === false || $json_end === false || $json_end <= $json_start) {
        wp_send_json_error(array('message' => 'La première étape n\'a pas généré un JSON valide. Réponse reçue : ' . substr($step1_response, 0, 200)));
    }
    
    $step1_json = substr($step1_response, $json_start, $json_end - $json_start + 1);
    $step1_data = json_decode($step1_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($step1_data)) {
        wp_send_json_error(array('message' => 'Erreur de parsing JSON de la première étape : ' . json_last_error_msg()));
    }
    
    // Validation : détecter si l'IA a généré des placeholders au lieu de contenu réel
    $placeholder_patterns = array(
        '/\[Premier paragraphe/i',
        '/\[Deuxième paragraphe/i',
        '/\[Troisième paragraphe/i',
        '/\[Une phrase d\'accroche/i',
        '/\[Nom technique précis/i',
        '/\[Description détaillée/i',
        '/\[Question technique/i',
        '/\[Réponse complète/i',
        '/\[Résumé en une phrase/i',
        '/\[Rédiger 2 à 3 phrases/i',
        '/Génère un premier paragraphe/i',
        '/Génère un deuxième paragraphe/i',
        '/Génère un troisième paragraphe/i',
        '/Génère une phrase/i',
        '/Génère 10 prestations/i',
        '/Génère 4 questions/i',
        '/Génère un résumé/i',
        '/Génère 2 à 3 phrases/i',
        '/Génère une description/i',
        '/Génère 10 mots-clés/i',
    );
    
    $has_placeholders = false;
    $placeholder_fields = array();
    
    // Vérifier tous les champs texte du JSON
    foreach ($step1_data as $key => $value) {
        if (is_string($value)) {
            foreach ($placeholder_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $has_placeholders = true;
                    $placeholder_fields[] = $key;
                    break;
                }
            }
        } elseif (is_array($value)) {
            // Vérifier les tableaux (intro_paragraphs, prestations, faq_questions, etc.)
            foreach ($value as $item) {
                if (is_string($item)) {
                    foreach ($placeholder_patterns as $pattern) {
                        if (preg_match($pattern, $item)) {
                            $has_placeholders = true;
                            $placeholder_fields[] = $key;
                            break 2;
                        }
                    }
                } elseif (is_array($item)) {
                    // Pour les prestations et FAQ qui sont des tableaux d'objets
                    foreach ($item as $sub_key => $sub_value) {
                        if (is_string($sub_value)) {
                            foreach ($placeholder_patterns as $pattern) {
                                if (preg_match($pattern, $sub_value)) {
                                    $has_placeholders = true;
                                    $placeholder_fields[] = $key . '.' . $sub_key;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    if ($has_placeholders) {
        wp_send_json_error(array(
            'message' => 'L\'IA a généré des placeholders au lieu de contenu réel. Champs concernés : ' . implode(', ', array_unique($placeholder_fields)) . '. Merci de relancer la génération.'
        ));
    }
    
    // Validation : détecter si l'IA a utilisé des noms de villes/départements/régions réels au lieu des placeholders
    // Utilisation d'une détection contextuelle pour éviter les faux positifs
    $real_places = array(
        // Villes françaises courantes (minimum 4 caractères pour éviter les faux positifs)
        'Rennes', 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux',
        'Lille', 'Rouen', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon', 'Grenoble', 'Dijon', 'Angers', 'Nîmes',
        'Villeurbanne', 'Saint-Denis', 'Le Mans', 'Aix-en-Provence', 'Clermont-Ferrand', 'Brest', 'Limoges', 'Tours',
        'Amiens', 'Perpignan', 'Metz', 'Besançon', 'Boulogne-Billancourt', 'Orléans', 'Mulhouse', 'Caen', 'Roubaix',
        // Départements français (excluant "Var" et "Nord" qui sont trop courts et causent des faux positifs)
        'Ille-et-Vilaine', 'Rhône', 'Bouches-du-Rhône', 'Haute-Garonne', 'Alpes-Maritimes', 'Loire-Atlantique',
        'Bas-Rhin', 'Hérault', 'Gironde', 'Seine-Maritime', 'Marne', 'Seine-et-Marne', 'Isère', 'Puy-de-Dôme',
        'Finistère', 'Haute-Vienne', 'Indre-et-Loire', 'Somme', 'Pyrénées-Orientales', 'Moselle', 'Doubs',
        'Hauts-de-Seine', 'Loiret', 'Haut-Rhin', 'Calvados', 'Pas-de-Calais',
        // Régions françaises (excluant "Bretagne" qui peut apparaître dans "bretonnes", "breton", etc.)
        'Île-de-France', 'Auvergne-Rhône-Alpes', 'Provence-Alpes-Côte d\'Azur', 'Occitanie', 'Nouvelle-Aquitaine',
        'Hauts-de-France', 'Normandie', 'Grand Est', 'Pays de la Loire', 'Centre-Val de Loire', 'Bourgogne-Franche-Comté'
    );
    
    $has_real_places = false;
    $real_places_found = array();
    $json_string = json_encode($step1_data);
    
    // Détection avec word boundaries pour éviter les faux positifs
    foreach ($real_places as $place) {
        // Utiliser des word boundaries pour éviter les matches partiels
        if (preg_match('/\b' . preg_quote($place, '/') . '\b/i', $json_string)) {
            $has_real_places = true;
            $real_places_found[] = $place;
        }
    }
    
    // Détection spéciale pour "Var" et "Nord" uniquement dans un contexte géographique
    // Var : uniquement si précédé de "département du", "dans le", etc.
    if (preg_match('/(?:département\s+du\s+|dans\s+le\s+|du\s+département\s+du\s+|le\s+département\s+du\s+)\bVar\b/i', $json_string)) {
        $has_real_places = true;
        $real_places_found[] = 'Var';
    }
    
    // Nord : uniquement si précédé de "département du", "région", etc. (éviter "nord" comme direction)
    if (preg_match('/(?:département\s+du\s+|région\s+du\s+|dans\s+le\s+)\bNord\b/i', $json_string)) {
        $has_real_places = true;
        $real_places_found[] = 'Nord';
    }
    
    // Détection spéciale pour "Bretagne" uniquement dans un contexte géographique clair
    // Éviter les faux positifs avec "bretonnes", "breton", "bretonne", etc.
    if (preg_match('/(?:en\s+|région\s+|de\s+|dans\s+la\s+région\s+|en\s+région\s+)\bBretagne\b/i', $json_string)) {
        $has_real_places = true;
        $real_places_found[] = 'Bretagne';
    }
    
    if ($has_real_places) {
        wp_send_json_error(array(
            'message' => 'L\'IA a utilisé des noms de lieux réels (' . implode(', ', array_unique($real_places_found)) . ') au lieu des placeholders [VILLE], [DÉPARTEMENT], [RÉGION]. Merci de relancer la génération en utilisant uniquement les placeholders.'
        ));
    }
    
    // Validation : vérifier que l'IA a généré 10 prestations et 4 FAQ
    $prestations_count = 0;
    if (isset($step1_data['prestations']) && is_array($step1_data['prestations'])) {
        $prestations_count = count($step1_data['prestations']);
    }
    
    $faq_count = 0;
    if (isset($step1_data['faq_questions']) && is_array($step1_data['faq_questions'])) {
        $faq_count = count($step1_data['faq_questions']);
    }
    
    if ($prestations_count < 10) {
        wp_send_json_error(array(
            'message' => "L'IA n'a généré que {$prestations_count} prestation(s) au lieu de 10. Merci de relancer la génération pour obtenir toutes les prestations."
        ));
    }
    
    if ($faq_count < 4) {
        wp_send_json_error(array(
            'message' => "L'IA n'a généré que {$faq_count} question(s) FAQ au lieu de 4. Merci de relancer la génération pour obtenir toutes les questions."
        ));
    }
    
    // ========== ÉTAPE 2 : Convertir le JSON en HTML formaté ==========
    $step2_prompt = "Tu es un expert en conversion de données JSON vers HTML WordPress.\n\n";
    $step2_prompt .= "Voici un JSON avec les données d'une page de service WordPress pour {$service_name} :\n\n";
    $step2_prompt .= $step1_json . "\n\n";
    $step2_prompt .= "CONVERTIS ce JSON en HTML WordPress complet et formaté avec cette structure :\n\n";
    $step2_prompt .= "<div class='space-y-6'>\n";
    $step2_prompt .= "  <div class='space-y-4'>\n";
    $step2_prompt .= "    <h1 class='text-3xl font-bold'>[title]</h1>\n";
    $step2_prompt .= "    <p class='text-lg leading-relaxed'>[title_subtitle]</p>\n";
    $step2_prompt .= "    [Pour chaque paragraphe dans intro_paragraphs : <p class='text-lg leading-relaxed'>[paragraphe]</p>]\n";
    $step2_prompt .= "  </div>\n";
    $step2_prompt .= "  <div class='space-y-4'>\n";
    $step2_prompt .= "    <h2 class='text-2xl font-bold text-gray-900 mb-4'>[guarantee_title]</h2>\n";
    $step2_prompt .= "    [Pour chaque paragraphe dans guarantee_paragraphs : <p class='text-lg leading-relaxed'>[paragraphe]</p>]\n";
    $step2_prompt .= "  </div>\n";
    $step2_prompt .= "  <div class='space-y-4'>\n";
    $step2_prompt .= "    <h2 class='text-2xl font-bold text-gray-900 mb-4'>[prestations_title]</h2>\n";
    $step2_prompt .= "    <ul class='space-y-3'>\n";
    $step2_prompt .= "      [Pour chaque prestation dans prestations : <li><strong>[name]</strong> - [description]</li>]\n";
    $step2_prompt .= "    </ul>\n";
    $step2_prompt .= "  </div>\n";
    $step2_prompt .= "  <div class='space-y-4'>\n";
    $step2_prompt .= "    <h2 class='text-2xl font-bold text-gray-900 mb-4'>[faq_title]</h2>\n";
    $step2_prompt .= "    <div class='space-y-2'>\n";
    $step2_prompt .= "      [Pour chaque question dans faq_questions : <p><strong>[question]</strong></p><p>[answer]</p>]\n";
    $step2_prompt .= "    </div>\n";
    $step2_prompt .= "  </div>\n";
    $step2_prompt .= "</div>\n\n";
    $step2_prompt .= "IMPORTANT :\n";
    $step2_prompt .= "- Génère UNIQUEMENT le HTML, sans texte avant ou après\n";
    $step2_prompt .= "- Utilise les classes CSS fournies\n";
    $step2_prompt .= "- Ajoute 2-3 liens internes dans le contenu (vers la page d'accueil ou autres services)\n";
    $step2_prompt .= "- Respecte exactement la structure fournie\n";
    $step2_prompt .= "- Garde tous les placeholders [VILLE], [RÉGION], [DÉPARTEMENT], [ENTREPRISE] intacts\n";
    
    $step2_system = 'Tu es un expert en conversion JSON vers HTML WordPress. Tu génères du HTML propre et bien formaté. OBLIGATOIRE : préserve TOUS les placeholders [VILLE], [DÉPARTEMENT], [RÉGION], [ENTREPRISE] du JSON dans le HTML généré. Réponds UNIQUEMENT avec le HTML, sans texte avant ou après.';
    
    // Deuxième appel IA
    $ai_response = $ai_service->call_ai($step2_prompt, $step2_system, array(
        'temperature' => 0.3,
        'max_tokens' => 4000,
    ));
    
    if (is_wp_error($ai_response)) {
        wp_send_json_error(array('message' => 'Erreur lors de la conversion HTML : ' . $ai_response->get_error_message()));
    }
    
    // Nettoyer la réponse de l'IA
    $content = $ai_response ?? '';
    
    // Supprimer les commentaires de validation à la fin
    $content = preg_replace('/\s*[-─═]{3,}.*$/s', '', $content);
    $content = preg_replace('/\s*✅.*$/s', '', $content);
    $content = preg_replace('/\s*\*\*Note.*$/s', '', $content);
    
    // Convertir le Markdown en HTML si l'IA a généré du Markdown
    $content = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $content);
    $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</3>', $content);
    $content = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^#\s+(.+)$/m', '<h2>$1</h2>', $content);
    
    // Convertir le gras Markdown en HTML
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $content);
    
    // Convertir l'italique Markdown en HTML
    $content = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $content);
    $content = preg_replace('/_(.+?)_/s', '<em>$1</em>', $content);
    
    // Nettoyages spécifiques pour éviter les doublons de titres ou libellés techniques
    // Supprimer les paragraphes ou lignes qui ne contiennent que ces libellés
    $content = preg_replace('/(<p>)?\s*(Description\s+courte|Présentation|Garantie\s+satisfaction\s+et\s+performances|FAQ\s+' . preg_quote(strtolower($service_name), '/') . ')\s*(<\/p>)?/iu', '', $content);
    
    // Éviter les doublons immédiats de H2/H3 identiques
    $content = preg_replace('/(<h[23][^>]*>[^<]+<\/h[23]>)\s*(\1)+/i', '$1', $content);
    
    // Supprimer un éventuel H2 d'ouverture de type \"Article ...\" ou avec emoji qui ne sert à rien pour l'utilisateur
    // Exemple : <h2>🎯 Article Couvreur Premium à [VILLE]</h2>
    $content = preg_replace('/^<h2[^>]*>[^<]*(Article|Premium|🎯)[^<]*<\/h2>\s*/iu', '', $content);

    // Dernier filet de sécurité : remplacer les mentions directes de grandes villes ou régions par les placeholders
    $forbidden_cities = array('Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Lille');
    $forbidden_regions = array('Île-de-France', 'Ile-de-France', 'Bretagne', 'Normandie', 'Occitanie', 'Nouvelle-Aquitaine', 'PACA', 'Provence-Alpes-Côte d\'Azur', 'Grand Est', 'Hauts-de-France', 'Auvergne-Rhône-Alpes', 'Centre-Val de Loire', 'Pays de la Loire', 'Bourgogne-Franche-Comté');

    foreach ($forbidden_cities as $city_name) {
        $content = str_ireplace($city_name, '[VILLE]', $content);
    }
    foreach ($forbidden_regions as $region_name) {
        $content = str_ireplace($region_name, '[RÉGION]', $content);
    }
    
    // Nettoyer le HTML généré par l'étape 2
    $description_html = trim($ai_response);
    
    // Supprimer les balises markdown ou code si présentes
    $description_html = preg_replace('/```html\s*/i', '', $description_html);
    $description_html = preg_replace('/```\s*$/i', '', $description_html);
    $description_html = trim($description_html);
    
    // Utiliser les données de l'étape 1 pour les métadonnées
    $template_json_raw = $step1_json;
    $meta_title = isset($step1_data['meta_title']) ? $step1_data['meta_title'] : '';
    $meta_description = isset($step1_data['meta_description']) ? $step1_data['meta_description'] : '';
    $meta_keywords = isset($step1_data['meta_keywords']) ? $step1_data['meta_keywords'] : '';
    $og_title = isset($step1_data['og_title']) ? $step1_data['og_title'] : '';
    $og_description = isset($step1_data['og_description']) ? $step1_data['og_description'] : '';
    $twitter_title = isset($step1_data['twitter_title']) ? $step1_data['twitter_title'] : '';
    $twitter_description = isset($step1_data['twitter_description']) ? $step1_data['twitter_description'] : '';
    $short_description = isset($step1_data['short_description']) ? $step1_data['short_description'] : '';
    $long_description = isset($step1_data['long_description']) ? $step1_data['long_description'] : '';
    $long_description_is_fallback = false;
    $icon = isset($step1_data['icon']) ? $step1_data['icon'] : 'fas fa-tools';
    
    // Vérifier si le HTML contient les prestations
    $has_prestations_in_html = false;
    if (!empty($description_html) && strlen($description_html) >= 100) {
        // Vérifier s'il y a une liste (ul ou ol) avec au moins 8 items (pour les 10 prestations)
        preg_match_all('/<li[^>]*>/i', $description_html, $li_matches);
        $li_count = count($li_matches[0]);
        // Vérifier aussi s'il y a un titre de section prestations
        $has_prestations_title = (stripos($description_html, 'prestation') !== false || stripos($description_html, 'prestations') !== false);
        // Les prestations sont présentes si on a au moins 8 items dans une liste ET un titre de section prestations
        $has_prestations_in_html = ($li_count >= 8 && $has_prestations_title);
        
        // Si les prestations sont manquantes, reconstruire le HTML complet depuis l'étape 1
        if (!$has_prestations_in_html) {
            error_log('Osmose ADS: Prestations manquantes dans le HTML de l\'étape 2 (seulement ' . $li_count . ' items trouvés). Reconstruction depuis l\'étape 1.');
        }
    }
    
    // Si le HTML n'a pas été généré correctement OU si les prestations sont manquantes, utiliser les données de l'étape 1
    if (empty($description_html) || strlen($description_html) < 100 || !$has_prestations_in_html) {
        // Construire le HTML à partir des données de l'étape 1
        $description_html = "<div class='space-y-6'>";
        $description_html .= "<div class='space-y-4'>";
        $description_html .= "<h1 class='text-3xl font-bold'>" . esc_html($step1_data['title'] ?? $service_name . ' à [VILLE]') . "</h1>";
        if (!empty($step1_data['title_subtitle'])) {
            $description_html .= "<p class='text-lg leading-relaxed'>" . esc_html($step1_data['title_subtitle']) . "</p>";
        }
        if (!empty($step1_data['intro_paragraphs']) && is_array($step1_data['intro_paragraphs'])) {
            foreach ($step1_data['intro_paragraphs'] as $para) {
                if (!empty($para)) {
                    $description_html .= "<p class='text-lg leading-relaxed'>" . esc_html($para) . "</p>";
                }
            }
        }
        $description_html .= "</div>";
        
        // Section Garantie
        if (!empty($step1_data['guarantee_title']) || !empty($step1_data['guarantee_paragraphs'])) {
            $description_html .= "<div class='space-y-4'>";
            $description_html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['guarantee_title'] ?? 'Garantie satisfaction et performances') . "</h2>";
            if (!empty($step1_data['guarantee_paragraphs']) && is_array($step1_data['guarantee_paragraphs'])) {
                foreach ($step1_data['guarantee_paragraphs'] as $para) {
                    if (!empty($para)) {
                        $description_html .= "<p class='text-lg leading-relaxed'>" . esc_html($para) . "</p>";
                    }
                }
            }
            $description_html .= "</div>";
        }
        
        // Section Prestations
        if (!empty($step1_data['prestations']) && is_array($step1_data['prestations'])) {
            $description_html .= "<div class='space-y-4'>";
            $description_html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['prestations_title'] ?? 'Nos Prestations ' . strtolower($service_name)) . "</h2>";
            $description_html .= "<ul class='space-y-3'>";
            foreach ($step1_data['prestations'] as $prestation) {
                if (isset($prestation['name']) && isset($prestation['description'])) {
                    $description_html .= "<li><strong>" . esc_html($prestation['name']) . "</strong> - " . esc_html($prestation['description']) . "</li>";
                }
            }
            $description_html .= "</ul>";
            $description_html .= "</div>";
        }
        
        // Section FAQ
        if (!empty($step1_data['faq_questions']) && is_array($step1_data['faq_questions'])) {
            $description_html .= "<div class='space-y-4'>";
            $description_html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['faq_title'] ?? 'FAQ ' . strtolower($service_name)) . "</h2>";
            $description_html .= "<div class='space-y-2'>";
            foreach ($step1_data['faq_questions'] as $faq) {
                if (isset($faq['question']) && isset($faq['answer'])) {
                    $description_html .= "<p><strong>" . esc_html($faq['question']) . "</strong></p>";
                    $description_html .= "<p>" . esc_html($faq['answer']) . "</p>";
                }
            }
            $description_html .= "</div>";
            $description_html .= "</div>";
        }
        
        $description_html .= "</div>";
    }
    
    // Utiliser le HTML généré comme contenu final
    $ai_response = $description_html;
    
    // Filet de sécurité : si long_description n'est pas fourni, le construire à partir du HTML (fallback SEO uniquement)
    if (empty($long_description) && !empty($description_html)) {
        $plain_text = wp_strip_all_tags($description_html);
        $plain_text = trim(preg_replace('/\s+/', ' ', $plain_text));
        if (function_exists('mb_substr')) {
            $long_description = mb_substr($plain_text, 0, 500);
        } else {
            $long_description = substr($plain_text, 0, 500);
        }
        $long_description_is_fallback = true;
    }

    // Même chose pour la short_description
    if (empty($short_description) && !empty($long_description)) {
        if (function_exists('mb_substr')) {
            $short_description = mb_substr($long_description, 0, 160);
        } else {
            $short_description = substr($long_description, 0, 160);
        }
    }

    // Si aucun JSON valide ou si la description HTML est manquante, refuser la création du template
    if (empty($template_json_raw) || empty($description_html)) {
        wp_send_json_error(array(
            'message' => __(
                'La génération IA n\'a pas renvoyé un contenu complet. Aucune annonce n\'a été créée. Merci de relancer la génération.',
                'osmose-ads'
            ),
        ));
    }

    // Validation stricte : vérifier que le contenu HTML contient toutes les sections requises
    $has_title = (stripos($description_html, '<h1') !== false || stripos($description_html, '<h2') !== false);
    $has_list = (stripos($description_html, '<ul') !== false || stripos($description_html, '<ol') !== false);
    $has_faq = (stripos($description_html, 'FAQ') !== false || stripos($description_html, 'faq') !== false || stripos($description_html, 'Question') !== false);
    $has_guarantee = (stripos($description_html, 'Garantie') !== false || stripos($description_html, 'garantie') !== false);
    
    // Compter le nombre de prestations dans la liste (doit être au moins 8)
    $prestation_count = 0;
    if ($has_list) {
        preg_match_all('/<li[^>]*>/i', $description_html, $matches);
        $prestation_count = count($matches[0]);
    }
    
    // Compter le nombre de questions FAQ (doit être au moins 3)
    $faq_count = 0;
    if ($has_faq) {
        preg_match_all('/(Q\d+|Question|FAQ|Quels sont|Comment|Quelle|Combien)/i', $description_html, $matches);
        $faq_count = count($matches[0]);
    }
    
    // Si le contenu ne contient pas toutes les sections ou n'a pas assez de prestations/FAQ, compléter avec les données de l'étape 1
    if (!$has_title || !$has_list || !$has_faq || !$has_guarantee || $prestation_count < 8 || $faq_count < 3) {
        error_log('Osmose ADS: Contenu incomplet détecté - Titre: ' . ($has_title ? 'OUI' : 'NON') . ', Liste: ' . ($has_list ? 'OUI' : 'NON') . ', FAQ: ' . ($has_faq ? 'OUI' : 'NON') . ', Garantie: ' . ($has_guarantee ? 'OUI' : 'NON') . ', Prestations: ' . $prestation_count . ', FAQ: ' . $faq_count);
        
        // Utiliser les données de l'étape 1 pour compléter le contenu
        $intro_html = '';
        if (!empty($step1_data['intro_paragraphs']) && is_array($step1_data['intro_paragraphs'])) {
            foreach ($step1_data['intro_paragraphs'] as $para) {
                if (!empty($para)) {
                    $intro_html .= '<p class="text-lg leading-relaxed">' . esc_html($para) . '</p>';
                }
            }
        }
        
        // Si pas d'intro depuis step1, extraire du HTML existant
        if (empty($intro_html)) {
            $plain_text = wp_strip_all_tags($description_html);
            $intro_blocks = preg_split('/\n{2,}/', trim($plain_text));
            $intro_count = 0;
            foreach ($intro_blocks as $block) {
                $block = trim($block);
                if ($block !== '' && strlen($block) > 20 && $intro_count < 3) {
                    if (stripos($block, 'Q1') === false && stripos($block, 'Q2') === false && stripos($block, 'Question') === false) {
                        $intro_html .= '<p class="text-lg leading-relaxed">' . esc_html($block) . '</p>';
                        $intro_count++;
                    }
                }
            }
        }
        
        if (empty($intro_html)) {
            $company_name = get_bloginfo('name');
            $intro_html = '<p class="text-lg leading-relaxed">Expert en ' . esc_html($service_name) . ' à [VILLE] dans le département [DÉPARTEMENT]. Solutions efficaces pour une habitation confortable et performante.</p>';
            $intro_html .= '<p class="text-lg leading-relaxed">' . esc_html($company_name) . ' propose ses services de ' . esc_html(strtolower($service_name)) . ' à [VILLE] dans le département [DÉPARTEMENT], garantissant des solutions sur mesure pour améliorer l\'efficacité de votre habitat. Notre équipe qualifiée utilise des techniques modernes et des matériaux de qualité pour assurer une intervention optimale.</p>';
        }

        // Construire un HTML complet avec toutes les sections
        $html  = "<div class='space-y-6'>";
        $html .= "<div class='space-y-4'>";
        $html .= "<h1 class='text-3xl font-bold'>" . esc_html($step1_data['title'] ?? 'Expert en ' . $service_name . ' à [VILLE] dans le département [DÉPARTEMENT]') . "</h1>";
        if (!empty($step1_data['title_subtitle'])) {
            $html .= "<p class='text-lg leading-relaxed'>" . esc_html($step1_data['title_subtitle']) . "</p>";
        }
        $html .= $intro_html;
        $html .= "</div>";
        
        // Section Garantie (toujours ajoutée si manquante, utiliser les données de step1)
        if (!$has_guarantee) {
            $html .= "<div class='space-y-4'>";
            $html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['guarantee_title'] ?? 'Garantie satisfaction et performances') . "</h2>";
            if (!empty($step1_data['guarantee_paragraphs']) && is_array($step1_data['guarantee_paragraphs'])) {
                foreach ($step1_data['guarantee_paragraphs'] as $para) {
                    if (!empty($para)) {
                        $html .= "<p class='text-lg leading-relaxed'>" . esc_html($para) . "</p>";
                    }
                }
            } else {
                $company_name = get_bloginfo('name');
                $html .= "<p class='text-lg leading-relaxed'>Chez " . esc_html($company_name) . ", nous vous assurons une garantie décennale sur nos travaux de " . esc_html(strtolower($service_name)) . ", ainsi qu'un suivi personnalisé pour garantir votre entière satisfaction. Nous respectons les normes en vigueur et travaillons dans le souci de la propreté et de la sécurité sur chaque chantier.</p>";
            }
            $html .= "</div>";
        }

        // Bloc prestations (toujours ajouté si manquant ou si moins de 8 prestations, utiliser les données de step1)
        if (!$has_list || $prestation_count < 8) {
            $html .= "<div class='space-y-4'>";
            $html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['prestations_title'] ?? 'Nos Prestations ' . strtolower($service_name)) . "</h2>";
            $html .= "<ul class='space-y-3'>";
            
            // Utiliser les prestations de step1 si disponibles
            if (!empty($step1_data['prestations']) && is_array($step1_data['prestations'])) {
                foreach ($step1_data['prestations'] as $prestation) {
                    if (isset($prestation['name']) && isset($prestation['description'])) {
                        $html .= "<li><strong>" . esc_html($prestation['name']) . "</strong> - " . esc_html($prestation['description']) . "</li>";
                    }
                }
            } else {
                // Fallback : générer des prestations techniques spécifiques selon le service
                $prestations = array();
                $service_lower = strtolower($service_name);
                
                if (stripos($service_lower, 'isolation') !== false) {
                    $prestations = array(
                        "Isolation combles perdus - Nous intervenons pour isoler vos combles perdus en utilisant des matériaux performants pour réduire les pertes de chaleur et améliorer le confort thermique de votre maison.",
                        "Isolation toiture - L'isolation de la toiture est essentielle pour limiter les déperditions de chaleur. Nous vous proposons des solutions adaptées pour une isolation efficace et durable.",
                        "Traitement ponts thermiques - Nos experts identifient et traitent les ponts thermiques de votre habitation pour garantir une isolation optimale et des économies d'énergie significatives.",
                        "Isolation murs - Les murs mal isolés peuvent représenter jusqu'à 25% de pertes de chaleur. Nous intervenons pour renforcer l'isolation de vos murs, vous permettant de réaliser des économies d'énergie.",
                        "Isolation sols - Une bonne isolation des sols contribue à améliorer le confort thermique de votre maison. Nous vous proposons des solutions efficaces pour optimiser l'isolation de vos planchers.",
                        "Isolation phonique - Pour un confort acoustique optimal, nous réalisons des travaux d'isolation phonique pour réduire les nuisances sonores et améliorer la qualité de vie dans votre logement.",
                        "Isolation thermique par l'extérieur - L'isolation thermique par l'extérieur permet d'améliorer l'efficacité énergétique de votre maison tout en préservant l'espace intérieur. Nous vous proposons des solutions sur mesure.",
                        "Isolation écologique - Soucieux de l'environnement, nous privilégions des matériaux écologiques et respectueux de la planète pour vos travaux d'isolation, garantissant des performances énergétiques durables.",
                        "Isolation sous rampant - L'isolation sous rampant est essentielle pour limiter les déperditions de chaleur par la toiture. Nous réalisons une isolation efficace et adaptée à votre configuration pour un confort optimal.",
                        "Isolation par insufflation - L'isolation par insufflation permet d'atteindre les endroits difficiles d'accès. Nous utilisons cette technique pour assurer une isolation homogène et performante de votre habitation."
                    );
                } elseif (stripos($service_lower, 'couvreur') !== false || stripos($service_lower, 'toiture') !== false) {
                    $prestations = array(
                        "Réfection toiture ardoise - Nous réalisons la réfection complète de votre toiture en ardoise, en utilisant des matériaux de qualité supérieure pour garantir la durabilité et l'étanchéité de votre toit.",
                        "Pose tuiles canal - Spécialistes de la pose de tuiles canal, nous intervenons pour rénover ou installer votre toiture avec des tuiles adaptées au climat de [RÉGION].",
                        "Installation écran de sous-toiture - L'écran de sous-toiture est essentiel pour protéger votre charpente. Nous installons des écrans performants pour assurer une protection optimale contre l'humidité.",
                        "Traitement charpente - Nous réalisons le traitement et la protection de votre charpente contre les insectes xylophages et l'humidité, garantissant la pérennité de votre structure.",
                        "Pose zinguerie - La zinguerie est cruciale pour l'étanchéité de votre toiture. Nous installons des éléments de zinguerie de qualité pour protéger les points sensibles de votre toit.",
                        "Réparation toiture d'urgence - En cas d'urgence, nous intervenons rapidement pour réparer les dommages de votre toiture et éviter les infiltrations d'eau dans votre habitation.",
                        "Démoussage et nettoyage toiture - Nous réalisons le démoussage et le nettoyage de votre toiture pour préserver l'état de vos tuiles et améliorer l'esthétique de votre toit.",
                        "Isolation toiture - Nous proposons des solutions d'isolation de toiture performantes pour améliorer le confort thermique de votre habitation et réduire vos factures de chauffage.",
                        "Installation fenêtres de toit - Nous installons des fenêtres de toit de qualité pour apporter de la lumière naturelle dans vos combles et améliorer le confort de votre espace.",
                        "Entretien et maintenance toiture - Nous proposons des contrats d'entretien régulier pour maintenir votre toiture en parfait état et prévenir les problèmes futurs."
                    );
                } else {
                    for ($i = 1; $i <= 10; $i++) {
                        $prestations[] = "Prestation technique " . $service_name . " " . $i . " - Description détaillée de la prestation avec techniques et matériaux spécifiques pour garantir des résultats optimaux à [VILLE].";
                    }
                }
                
                foreach ($prestations as $prestation) {
                    $html .= "<li><strong>" . esc_html(explode(' - ', $prestation)[0]) . "</strong> - " . esc_html(explode(' - ', $prestation)[1] ?? $prestation) . "</li>";
                }
            }
            $html .= "</ul>";
            $html .= "</div>";
        }

        // Bloc FAQ (toujours ajouté si manquant ou si moins de 3 questions, utiliser les données de step1)
        if (!$has_faq || $faq_count < 3) {
            $html .= "<div class='space-y-4'>";
            $html .= "<h2 class='text-2xl font-bold text-gray-900 mb-4'>" . esc_html($step1_data['faq_title'] ?? 'FAQ ' . strtolower($service_name)) . "</h2>";
            $html .= "<div class='space-y-2'>";
            
            // Utiliser les questions FAQ de step1 si disponibles
            if (!empty($step1_data['faq_questions']) && is_array($step1_data['faq_questions'])) {
                foreach ($step1_data['faq_questions'] as $faq) {
                    if (isset($faq['question']) && isset($faq['answer'])) {
                        $html .= "<p><strong>" . esc_html($faq['question']) . "</strong></p>";
                        $html .= "<p>" . esc_html($faq['answer']) . "</p>";
                    }
                }
            } else {
                // Fallback : FAQ spécifiques selon le service
                $devis_url = get_option('osmose_ads_devis_url', '');
                $site_url = get_site_url();
                $service_lower = strtolower($service_name);
                
                if (stripos($service_lower, 'isolation') !== false) {
                    $html .= "<p><strong>Quels sont les avantages de l'isolation des combles perdus ?</strong></p>";
                    $html .= "<p>L'isolation des combles perdus permet de limiter les pertes de chaleur, de réduire les factures de chauffage et d'améliorer le confort thermique de votre maison. C'est une solution efficace et rentable.</p>";
                    
                    $html .= "<p><strong>Comment savoir si mon isolation actuelle est efficace ?</strong></p>";
                    $html .= "<p>Si vous constatez des variations de température importantes dans votre logement, des courants d'air ou des moisissures, il est probable que votre isolation ne soit pas optimale. Dans ce cas, il est recommandé de faire appel à des professionnels pour une évaluation.</p>";
                    
                    $html .= "<p><strong>Quels sont les matériaux d'isolation les plus performants ?</strong></p>";
                    $html .= "<p>Les matériaux d'isolation performants varient en fonction des besoins et des contraintes de chaque projet. Parmi les plus couramment utilisés, on retrouve la laine de roche, la ouate de cellulose, le polystyrène expansé, etc. Un professionnel saura vous conseiller sur le choix le plus adapté.</p>";
                    
                    $html .= "<p><strong>Combien de temps durent les travaux d'isolation ?</strong></p>";
                    $html .= "<p>La durée des travaux d'isolation dépend de la surface à traiter, des matériaux utilisés et de la complexité de l'intervention. En général, pour une maison standard, les travaux peuvent durer de quelques jours à quelques semaines.</p>";
                } elseif (stripos($service_lower, 'couvreur') !== false || stripos($service_lower, 'toiture') !== false) {
                    $html .= "<p><strong>Quand faut-il refaire sa toiture ?</strong></p>";
                    $html .= "<p>Il est recommandé de refaire sa toiture lorsque les tuiles ou ardoises présentent des signes d'usure importants, des fuites récurrentes, ou après une tempête ayant causé des dommages. Une inspection régulière permet d'anticiper les travaux nécessaires.</p>";
                    
                    $html .= "<p><strong>Quelle est la durée de vie d'une toiture ?</strong></p>";
                    $html .= "<p>La durée de vie d'une toiture dépend du matériau utilisé : une toiture en ardoise peut durer 50 à 100 ans, une toiture en tuiles 30 à 50 ans. Un entretien régulier prolonge significativement la durée de vie de votre toiture.</p>";
                    
                    $html .= "<p><strong>Quels sont les signes d'une toiture à réparer ?</strong></p>";
                    $html .= "<p>Les signes d'une toiture nécessitant des réparations incluent : des tuiles ou ardoises cassées ou manquantes, des fuites d'eau, des traces d'humidité dans les combles, des mousses importantes, ou des tuiles qui se soulèvent.</p>";
                    
                    $html .= "<p><strong>Combien coûte une réfection de toiture ?</strong></p>";
                    $html .= "<p>Le coût d'une réfection de toiture varie selon la surface, le matériau choisi, la complexité de la charpente et les travaux annexes. Il est recommandé de demander plusieurs devis pour comparer les offres et choisir la solution la plus adaptée à votre budget.</p>";
                } else {
                    $html .= "<p><strong>Quels sont les avantages de faire appel à un professionnel pour " . esc_html(strtolower($service_name)) . " ?</strong></p>";
                    $html .= "<p>Faire appel à un professionnel garantit une intervention de qualité, conforme aux normes en vigueur, avec des matériaux adaptés et une garantie sur les travaux réalisés. Un professionnel saura vous conseiller sur les meilleures solutions pour votre projet.</p>";
                    
                    $html .= "<p><strong>Comment obtenir un devis pour " . esc_html(strtolower($service_name)) . " à [VILLE] ?</strong></p>";
                    if (!empty($devis_url)) {
                        $html .= "<p>Contactez-nous pour une étude personnalisée. Nous analysons votre besoin et vous transmettons un <a href='" . esc_url($devis_url) . "' class='text-blue-600 hover:underline'>devis détaillé et gratuit</a> adapté à vos spécificités.</p>";
                    } else {
                        $html .= "<p>Contactez-nous pour une étude personnalisée. Nous analysons votre besoin et vous transmettons un devis détaillé et gratuit adapté à vos spécificités.</p>";
                    }
                    
                    $html .= "<p><strong>Quelles garanties proposez-vous sur vos prestations de " . esc_html(strtolower($service_name)) . " ?</strong></p>";
                    $html .= "<p>Nos interventions respectent les normes en vigueur et bénéficient des garanties légales associées aux travaux réalisés. Nous vous assurons également un suivi personnalisé pour garantir votre entière satisfaction.</p>";
                    
                    $html .= "<p><strong>Intervenez-vous uniquement à [VILLE] ?</strong></p>";
                    $html .= "<p>Nous intervenons à [VILLE] et dans tout le département [DÉPARTEMENT], en [RÉGION]. Découvrez nos autres <a href='" . esc_url($site_url) . "' class='text-blue-600 hover:underline'>services disponibles</a> dans votre région.</p>";
                }
            }
            
            $html .= "</div>";
            $html .= "</div>";
        }

        $html .= "</div>";

        // Remplacer le contenu incomplet par le contenu complet
        $description_html = $html;
        $ai_response = $description_html;
    }

    // Si aucune meta extraite depuis le JSON, fallback sur l'ancienne logique (2e appel IA dédié aux méta)
    if (empty($meta_title) && empty($meta_description)) {
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
    } else {
        // Compléter les champs manquants avec des valeurs par défaut cohérentes
        if (empty($meta_title)) {
            $meta_title = $service_name . ' à [VILLE] - Service professionnel';
        }
        if (empty($meta_description)) {
            $meta_description = 'Service professionnel de ' . $service_name . ' à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.';
        }
        if (empty($meta_keywords)) {
            $meta_keywords = strtolower($service_name) . ', [VILLE], [RÉGION], service professionnel, devis gratuit';
        }
        if (empty($og_title)) {
            $og_title = $meta_title;
        }
        if (empty($og_description)) {
            $og_description = $meta_description;
        }
        if (empty($twitter_title)) {
            $twitter_title = $og_title;
        }
        if (empty($twitter_description)) {
            $twitter_description = $og_description;
        }
    }

    // Remplacer les mentions génériques d'entreprise par le vrai nom de l'entreprise
    $company_name_for_meta = trim($company_name ?: get_bloginfo('name'));
    if (!empty($company_name_for_meta)) {
        $meta_placeholders = array(
            '[Entreprise]',
            '[ENTREPRISE]',
            'Nom de l\'entreprise',
            'Nom de l\'entreprise',
        );

        $replace_cb = function($value) use ($meta_placeholders, $company_name_for_meta) {
            if (!is_string($value) || $value === '') {
                return $value;
            }
            return str_replace($meta_placeholders, $company_name_for_meta, $value);
        };

        // Remplacer dans les métadonnées
        $meta_title           = $replace_cb($meta_title);
        $meta_description     = $replace_cb($meta_description);
        $meta_keywords        = $replace_cb($meta_keywords);
        $og_title             = $replace_cb($og_title);
        $og_description       = $replace_cb($og_description);
        $twitter_title        = $replace_cb($twitter_title);
        $twitter_description  = $replace_cb($twitter_description);
        
        // Remplacer aussi dans le contenu HTML
        $ai_response = $replace_cb($ai_response);
    }

    // S'assurer qu'une meta_description existe toujours (fallback si vide)
    if (empty($meta_description)) {
        $meta_description = 'Service professionnel de ' . $service_name . ' à [VILLE]. Devis gratuit, intervention rapide, garantie sur tous nos travaux.';
    }

    // Limiter meta_description à 160 caractères (norme SEO)
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($meta_description) > 160) {
            $meta_description = mb_substr($meta_description, 0, 157) . '...';
        }
    } else {
        if (strlen($meta_description) > 160) {
            $meta_description = substr($meta_description, 0, 157) . '...';
        }
    }

    // Normaliser meta_keywords : s'assurer d'avoir au moins 10 mots-clés pertinents autour du service
    $base_keyword = strtolower(trim($service_name));
    $keyword_items = array();
    if (!empty($meta_keywords)) {
        $parts = preg_split('/\s*,\s*/', strtolower($meta_keywords));
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $keyword_items[] = $part;
            }
        }
    }

    // Ensemble de mots-clés supplémentaires possibles (avec placeholders)
    $extras = array();
    if (!empty($base_keyword)) {
        $extras = array(
            $base_keyword,
            $base_keyword . ' [VILLE]',
            $base_keyword . ' [VILLE] [DÉPARTEMENT]',
            $base_keyword . ' entreprise',
            'entreprise ' . $base_keyword . ' [VILLE]',
            $base_keyword . ' artisan [VILLE]',
            $base_keyword . ' dépannage [VILLE]',
            $base_keyword . ' urgence [VILLE]',
            $base_keyword . ' devis gratuit [VILLE]',
            $base_keyword . ' prix [VILLE]',
            $base_keyword . ' professionnel [VILLE]',
        );
    }

    foreach ($extras as $extra_kw) {
        if (count($keyword_items) >= 10) {
            break;
        }
        if (!in_array($extra_kw, $keyword_items, true)) {
            $keyword_items[] = $extra_kw;
        }
    }

    // Si toujours moins de 10 (cas sans service_name), compléter avec des combinaisons génériques
    if (count($keyword_items) < 10) {
        $fallbacks = array(
            '[VILLE] [DÉPARTEMENT] artisan',
            '[VILLE] [DÉPARTEMENT] entreprise',
            '[VILLE] devis gratuit',
            '[VILLE] prix travaux',
            '[VILLE] professionnel',
        );
        foreach ($fallbacks as $fb) {
            if (count($keyword_items) >= 10) {
                break;
            }
            if (!in_array($fb, $keyword_items, true)) {
                $keyword_items[] = $fb;
            }
        }
    }

    if (!empty($keyword_items)) {
        $meta_keywords = implode(', ', $keyword_items);
    }
    
    // Préparer l'extrait pour le template (utiliser short_description ou meta_description)
    $template_excerpt = '';
    if (!empty($short_description)) {
        $template_excerpt = $short_description;
    } elseif (!empty($meta_description)) {
        $template_excerpt = $meta_description;
    }
    // Limiter l'extrait à 160 caractères
    if (!empty($template_excerpt)) {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($template_excerpt) > 160) {
                $template_excerpt = mb_substr($template_excerpt, 0, 157) . '...';
            }
        } else {
            if (strlen($template_excerpt) > 160) {
                $template_excerpt = substr($template_excerpt, 0, 157) . '...';
            }
        }
    }

    // Créer le post template
    $template_id = wp_insert_post(array(
        'post_title' => $service_name,
        'post_content' => $ai_response,
        'post_excerpt' => $template_excerpt,
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
            // On stocke uniquement les IDs et mots-clés en meta.
            // L'injection de la galerie HTML est gérée dynamiquement au moment
            // de la génération des annonces par ville (Ad_Template::get_content_for_city).
            update_post_meta($template_id, 'realization_images', $valid_images);
            update_post_meta($template_id, 'realization_images_keywords', $images_with_keywords);
        }
    }
    
    // Enregistrer les meta
    update_post_meta($template_id, 'service_name', $service_name);
    update_post_meta($template_id, 'service_slug', $service_slug);
    // Sauvegarder aussi les mots-clés du service pour les utiliser plus tard (tags, SEO, etc.)
    if (!empty($service_keywords)) {
        update_post_meta($template_id, 'service_keywords', $service_keywords);
    }
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
    
    $view_url = admin_url('admin.php?page=osmose-ads-templates&template_id=' . $template_id);
    
    wp_send_json_success(array(
        'message'     => __('Template créé avec succès avec images et métadonnées SEO', 'osmose-ads'),
        'template_id' => $template_id,
        'view_url'    => $view_url,
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
    // Augmenter les limites pour éviter les timeouts
    @set_time_limit(300); // 5 minutes
    @ini_set('max_execution_time', 300);
    @ini_set('memory_limit', '512M');
    
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
    
    // Désactiver les hooks pour accélérer
    remove_action('post_updated', 'wp_save_post_revision');
    wp_suspend_cache_addition(true);
    
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

        // Vérifier si une annonce avec le même slug existe déjà (peu importe le template)
        $existing_slug_post = get_page_by_path($slug, OBJECT, 'ad');
        if ($existing_slug_post && $existing_slug_post->post_status !== 'trash') {
            $skipped++;
            continue;
        }
        
        // Générer le contenu
        $content = $template->get_content_for_city($city_id);
        
        // Vérifier que tous les placeholders ont été remplacés
        $placeholders = array('[VILLE]', '[RÉGION]', '[DÉPARTEMENT]', '[CODE_POSTAL]');
        $remaining_placeholders = array();
        foreach ($placeholders as $placeholder) {
            if (strpos($content, $placeholder) !== false) {
                $remaining_placeholders[] = $placeholder;
            }
        }
        
        // Si des placeholders restent, forcer le remplacement manuel
        if (!empty($remaining_placeholders)) {
            error_log('Osmose ADS: Placeholders non remplacés détectés dans le contenu: ' . implode(', ', $remaining_placeholders));
            // Forcer le remplacement des variables
                $city = get_post($city_id);
                if ($city) {
                    $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;

                    $department_code = get_post_meta($city_id, 'department', true);
                    $department_name = get_post_meta($city_id, 'department_name', true);
                    $department = $department_name ?: $department_code;

                    $region_code = get_post_meta($city_id, 'region', true);
                    $region_name = get_post_meta($city_id, 'region_name', true);
                    $region = $region_name ?: $region_code;

                    $postal_code = get_post_meta($city_id, 'postal_code', true);
                    
                    $replacements = array(
                        '[VILLE]' => $city_name,
                        '[RÉGION]' => $region ?: '',
                        '[DÉPARTEMENT]' => $department ?: '',
                        '[CODE_POSTAL]' => $postal_code ?: '',
                    );
                    
                    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
                }
        }
        
        // Générer les métadonnées
        $meta = $template->get_meta_for_city($city_id);
        
        // Vérifier aussi les métadonnées pour les placeholders
        foreach ($meta as $key => $value) {
            if (is_string($value)) {
                foreach ($placeholders as $placeholder) {
                    if (strpos($value, $placeholder) !== false) {
                        error_log("Osmose ADS: Placeholder $placeholder détecté dans $key, remplacement forcé");
                        // Forcer le remplacement
                        $city = get_post($city_id);
                        if ($city) {
                            $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;

                            $department_code = get_post_meta($city_id, 'department', true);
                            $department_name = get_post_meta($city_id, 'department_name', true);
                            $department = $department_name ?: $department_code;

                            $region_code = get_post_meta($city_id, 'region', true);
                            $region_name = get_post_meta($city_id, 'region_name', true);
                            $region = $region_name ?: $region_code;

                            $postal_code = get_post_meta($city_id, 'postal_code', true);
                            
                            $replacements = array(
                                '[VILLE]' => $city_name,
                                '[RÉGION]' => $region ?: '',
                                '[DÉPARTEMENT]' => $department ?: '',
                                '[CODE_POSTAL]' => $postal_code ?: '',
                            );
                            
                            $meta[$key] = str_replace(array_keys($replacements), array_values($replacements), $value);
                        }
                    }
                }
            }
        }
        
        // Préparer l'extrait pour l'annonce (utiliser meta_description)
        $ad_excerpt = '';
        if (!empty($meta['meta_description'])) {
            $ad_excerpt = $meta['meta_description'];
        } else {
            // Fallback si meta_description est vide
            $ad_excerpt = 'Service professionnel de ' . $service_name . ' à ' . $city_name . '. Devis gratuit, intervention rapide, garantie sur tous nos travaux.';
        }
        // L'extrait est déjà limité à 160 caractères dans get_meta_for_city, mais on s'assure quand même
        if (!empty($ad_excerpt)) {
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($ad_excerpt) > 160) {
                    $ad_excerpt = mb_substr($ad_excerpt, 0, 157) . '...';
                }
            } else {
                if (strlen($ad_excerpt) > 160) {
                    $ad_excerpt = substr($ad_excerpt, 0, 157) . '...';
                }
            }
        }
        
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
            'post_excerpt' => $ad_excerpt,
            'post_type' => 'ad',
            'post_status' => 'publish',
            'post_category' => $category_id ? array($category_id) : array(), // Assigner la catégorie "Annonces"
        ));
        
        if (is_wp_error($ad_id)) {
            $errors++;
            continue;
        }
        
        // Générer automatiquement des étiquettes (tags) SEO pour l'annonce
        $tags = array();
        
        // Mot-clé principal = nom du service
        if (!empty($service_name)) {
            $tags[] = $service_name;
        }
        
        // Service + ville
        if (!empty($service_name) && !empty($city_name)) {
            $tags[] = $service_name . ' ' . $city_name;
        }
        
        // Ville seule
        if (!empty($city_name)) {
            $tags[] = $city_name;
        }
        
        // Récupérer les mots-clés du template (si définis)
        $template_keywords = get_post_meta($template_id, 'service_keywords', true);
        if (!empty($template_keywords)) {
            $keywords_array = array_map('trim', explode(',', $template_keywords));
            foreach ($keywords_array as $kw) {
                if (!empty($kw)) {
                    $tags[] = $kw;
                    // Variante avec ville
                    if (!empty($city_name)) {
                        $tags[] = $kw . ' ' . $city_name;
                    }
                }
            }
        }
        
        // Ajouter quelques tags génériques basés sur le service et la ville
        if (!empty($service_name) && !empty($city_name)) {
            $tags[] = 'artisan ' . $city_name;
            $tags[] = 'entreprise ' . strtolower($service_name);
            $tags[] = strtolower($service_name) . ' ' . $city_name . ' devis';
            $tags[] = strtolower($service_name) . ' ' . $city_name . ' prix';
        }
        
        // Nettoyer et dédupliquer
        $tags = array_filter(array_unique(array_map('sanitize_text_field', $tags)));
        
        // S'assurer qu'on a au moins 10 tags (compléter avec des combinaisons si nécessaire)
        if (count($tags) < 10 && !empty($service_name) && !empty($city_name)) {
            $base = strtolower($service_name);
            $ville = $city_name;
            $extras = array(
                $base . ' professionnel ' . $ville,
                $base . ' pas cher ' . $ville,
                'entreprise ' . $base . ' ' . $ville,
                'spécialiste ' . $base . ' ' . $ville,
                'travaux ' . $base . ' ' . $ville,
                'devis ' . $base . ' ' . $ville,
                'réparation ' . $base . ' ' . $ville,
                'installation ' . $base . ' ' . $ville,
            );
            foreach ($extras as $extra) {
                if (count($tags) >= 10) {
                    break;
                }
                $tags[] = sanitize_text_field($extra);
            }
        }
        
        if (!empty($tags)) {
            // Assigner les tags à l'annonce (créera les termes si nécessaire)
            wp_set_post_terms($ad_id, $tags, 'post_tag', false);
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
    
    // Réactiver le cache
    wp_suspend_cache_addition(false);
    
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

/**
 * Supprimer une annonce individuelle
 */
function osmose_ads_handle_delete_ad() {
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
    }
    
    $ad_id = intval($_POST['ad_id'] ?? 0);
    
    if (!$ad_id) {
        wp_send_json_error(array('message' => __('ID d\'annonce manquant', 'osmose-ads')));
    }
    
    $post = get_post($ad_id);
    if (!$post || $post->post_type !== 'ad') {
        wp_send_json_error(array('message' => __('Annonce non trouvée', 'osmose-ads')));
    }
    
    $deleted = wp_delete_post($ad_id, true); // true = suppression définitive
    
    if ($deleted) {
        wp_send_json_success(array('message' => __('Annonce supprimée avec succès', 'osmose-ads')));
    } else {
        wp_send_json_error(array('message' => __('Erreur lors de la suppression de l\'annonce', 'osmose-ads')));
    }
}

/**
 * Supprimer toutes les annonces
 */
function osmose_ads_handle_delete_all_ads() {
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
    }
    
    $ads = get_posts(array(
        'post_type' => 'ad',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    if (empty($ads)) {
        wp_send_json_success(array(
            'message' => __('Aucune annonce à supprimer', 'osmose-ads'),
            'deleted' => 0,
        ));
    }
    
    $deleted = 0;
    foreach ($ads as $ad_id) {
        $result = wp_delete_post($ad_id, true);
        if ($result) {
            $deleted++;
        }
    }
    
    wp_send_json_success(array(
        'message' => sprintf(__('Annonces supprimées: %d', 'osmose-ads'), $deleted),
        'deleted' => $deleted,
    ));
}

/**
 * Handler pour supprimer un template
 */
function osmose_ads_handle_delete_template() {
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(array('message' => __('Permissions insuffisantes', 'osmose-ads')));
    }
    
    $template_id = intval($_POST['template_id'] ?? 0);
    
    if (!$template_id) {
        wp_send_json_error(array('message' => __('ID de template manquant', 'osmose-ads')));
    }
    
    // Vérifier que c'est bien un template
    $template = get_post($template_id);
    if (!$template || $template->post_type !== 'ad_template') {
        wp_send_json_error(array('message' => __('Template non trouvé', 'osmose-ads')));
    }
    
    // Vérifier les annonces associées
    $ads_count = get_posts(array(
        'post_type' => 'ad',
        'meta_key' => 'template_id',
        'meta_value' => $template_id,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ));
    
    $ads_count = count($ads_count);
    
    // Option : supprimer aussi les annonces associées si demandé
    $delete_ads = isset($_POST['delete_ads']) && $_POST['delete_ads'] === 'true';
    
    if ($ads_count > 0 && !$delete_ads) {
        // Il y a des annonces associées, demander confirmation
        wp_send_json_error(array(
            'message' => sprintf(
                __('Ce template est utilisé par %d annonce(s). Voulez-vous aussi supprimer ces annonces ?', 'osmose-ads'),
                $ads_count
            ),
            'has_ads' => true,
            'ads_count' => $ads_count,
        ));
    }
    
    // Supprimer les annonces associées si demandé
    if ($delete_ads && $ads_count > 0) {
        $ads = get_posts(array(
            'post_type' => 'ad',
            'meta_key' => 'template_id',
            'meta_value' => $template_id,
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        foreach ($ads as $ad) {
            wp_delete_post($ad->ID, true); // true = force delete (bypass trash)
        }
    }
    
    // Supprimer le template
    $deleted = wp_delete_post($template_id, true);
    
    if ($deleted) {
        wp_send_json_success(array(
            'message' => __('Template supprimé avec succès', 'osmose-ads'),
            'deleted_ads' => $delete_ads ? $ads_count : 0,
        ));
    } else {
        wp_send_json_error(array('message' => __('Erreur lors de la suppression', 'osmose-ads')));
    }
}
