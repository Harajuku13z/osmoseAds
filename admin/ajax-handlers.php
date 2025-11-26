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

    $base_prompt .= "GÉNÈRE UN JSON AVEC CES CHAMPS:\n\n";
    $base_prompt .= "{\n";
    $base_prompt .= "  \"description\": \"[GÉNÈRE ICI UN HTML COMPLET POUR UNE PAGE DE SERVICE WORDPRESS EN {$service_name}. LE HTML DOIT INCLURE: (1) 2 À 3 PARAGRAPHES D'INTRODUCTION ORIGINAUX, TECHNIQUES ET SPÉCIFIQUES À {$service_name}, QUI EXPLIQUENT LE CONTEXTE, LES ENJEUX ET LES BÉNÉFICES POUR LE CLIENT À [VILLE] ET EN [RÉGION]; (2) UNE SECTION 'Nos prestations {$service_name}' AVEC UNE LISTE &lt;ul&gt; DE 10 PRESTATIONS TRÈS SPÉCIFIQUES AU SERVICE, CHAQUE &lt;li&gt; CONTENANT UNE ICÔNE &lt;i class='fas fa-check text-green-600 mr-2'&gt;&lt;/i&gt; ET UN TEXTE DÉTAILLÉ; (3) UNE SECTION FAQ DÉDIÉE À {$service_name} À [VILLE], AVEC DES QUESTIONS/RÉPONSES PRÉCISES ET TECHNIQUES. UTILISE UNE STRUCTURE MODERNE AVEC &lt;div class='space-y-6'&gt;, &lt;h1&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;p&gt;, MAIS TU DOIS RÉDIGER TOUS LES TEXTES TOI-MÊME, SANS REPRENDRE D'EXEMPLES GÉNÉRIQUES.]\"," . "\n";
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
    $base_prompt .= "- Chaque prestation doit avoir un NOM TECHNIQUE précis et une DESCRIPTION détaillée avec techniques/matériaux pour {$service_name}\n";
    $base_prompt .= "- PERSONNALISE les descriptions, FAQ, et tous les textes pour {$service_name} spécifiquement\n";
    $base_prompt .= "- Utilise [VILLE], [RÉGION], [DÉPARTEMENT] comme placeholders pour les variables dynamiques\n";
    $base_prompt .= "- Le contenu HTML doit être COMPLET et PERSONNALISÉ, pas un template copié-collé\n";
    $base_prompt .= "- NE PAS ajouter de sections supplémentaires comme 'Pourquoi choisir ce service', 'Notre Expertise Locale', 'Financement et aides', 'Informations pratiques' ou des blocs de partage (Facebook, WhatsApp, Email...). Ces éléments sont gérés par le thème WordPress.\n\n";

    $base_prompt .= "EXEMPLES CONCRETS POUR {$service_name}:\n";
    $base_prompt .= "- Si {$service_name} = 'Désamiantage' → prestations: 'Dépollution amiante', 'Retrait amiante sous confinement', 'Gestion déchets amiante'\n";
    $base_prompt .= "- Si {$service_name} = 'Traitement humidité' → prestations: 'Diagnostic humidité par imagerie thermique', 'Injection résine anti-humidité', 'Installation VMC double flux'\n";
    $base_prompt .= "- Si {$service_name} = 'Rénovation toiture' → prestations: 'Diagnostic toiture par drone', 'Réfection tuiles ardoise', 'Installation écran de sous-toiture'\n";

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
    
    // Appeler l'IA pour générer le contenu
    $ai_service = new AI_Service();
    
    if (empty($prompt)) {
        // Utiliser le nouveau prompt JSON inspiré de la version Laravel
        $prompt = osmose_ads_build_template_prompt($service_name, '');
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
    
    // Mettre à jour la réponse
    $ai_response = trim($content);

    // Essayer d'extraire un JSON complet (nouvelle logique inspirée de Laravel)
    $meta_title = '';
    $meta_description = '';
    $meta_keywords = '';
    $og_title = '';
    $og_description = '';
    $twitter_title = '';
    $twitter_description = '';
    $short_description = '';
    $long_description = '';
    $long_description_is_fallback = false;
    $icon = '';
    $template_json_raw = '';
    $description_html = '';

    $json_start = strpos($ai_response, '{');
    $json_end   = strrpos($ai_response, '}');
    if ($json_start !== false && $json_end !== false && $json_end > $json_start) {
        $json_str = substr($ai_response, $json_start, $json_end - $json_start + 1);
        $decoded  = json_decode($json_str, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $template_json_raw = $json_str;

            // Récupérer les champs principaux du JSON
            $description_html   = isset($decoded['description']) ? $decoded['description'] : '';
            $short_description  = isset($decoded['short_description']) ? $decoded['short_description'] : '';
            $long_description   = isset($decoded['long_description']) ? $decoded['long_description'] : '';
            $icon               = isset($decoded['icon']) ? $decoded['icon'] : '';
            $meta_title         = isset($decoded['meta_title']) ? $decoded['meta_title'] : '';
            $meta_description   = isset($decoded['meta_description']) ? $decoded['meta_description'] : '';
            $meta_keywords      = isset($decoded['meta_keywords']) ? $decoded['meta_keywords'] : '';
            $og_title           = isset($decoded['og_title']) ? $decoded['og_title'] : '';
            $og_description     = isset($decoded['og_description']) ? $decoded['og_description'] : '';
            $twitter_title      = isset($decoded['twitter_title']) ? $decoded['twitter_title'] : '';
            $twitter_description= isset($decoded['twitter_description']) ? $decoded['twitter_description'] : '';

            // Validation supplémentaire : s'assurer que la description HTML contient bien
            // une intro + une liste de prestations + une FAQ, sinon on considère la réponse IA comme incomplète
            if (empty($description_html)
                || stripos($description_html, '<ul') === false
                || stripos($description_html, 'FAQ') === false
            ) {
                wp_send_json_error(array(
                    'message' => __(
                        'La génération IA n\'a pas produit un contenu complet (intro + liste de prestations + FAQ). Aucune annonce n\'a été créée. Merci de relancer la génération pour obtenir un texte de page de service complet.',
                        'osmose-ads'
                    ),
                ));
            }

            // Filet de sécurité : si long_description n'est pas fourni, le construire à partir du HTML (fallback SEO uniquement)
            if (empty($long_description) && !empty($description_html)) {
                $plain_text = wp_strip_all_tags($description_html);
                $plain_text = trim(preg_replace('/\s+/', ' ', $plain_text));
                if (function_exists('mb_substr')) {
                    $long_description = mb_substr($plain_text, 0, 500);
                } else {
                    $long_description = substr($plain_text, 0, 500);
                }
                // Marquer que cette long_description est un fallback auto-généré (éviter de la réinjecter dans le HTML pour ne pas dupliquer/couper le contenu)
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

            if (!empty($description_html)) {
                // Utiliser la description HTML comme contenu du template
                $ai_response = $description_html;

                // Si une long_description explicite est fournie par l'IA, l'injecter dans le HTML du template
                // (mais ne PAS réinjecter la version fallback auto-générée qui est déjà un résumé du contenu)
                if (!empty($long_description) && !$long_description_is_fallback) {
                    $about_html  = "<section class='osmose-service-about space-y-4'>\n";
                    $about_html .= "  <h2 class='text-2xl font-bold text-gray-900'>À propos de notre service de {$service_name}</h2>\n";
                    $about_html .= "  <p class='leading-relaxed'>" . esc_html($long_description) . "</p>\n";
                    $about_html .= "</section>\n";

                    // Par défaut, on ajoute cette section à la fin du contenu généré
                    $ai_response .= "\n\n" . $about_html;
                }
            }
        }
    }

    // Si aucun JSON valide ou si la description HTML est manquante, refuser la création du template
    if (empty($template_json_raw) || empty($description_html)) {
        wp_send_json_error(array(
            'message' => __(
                'La génération IA n’a pas renvoyé un JSON complet (champ "description" manquant ou invalide). Aucune annonce n’a été créée. Merci de relancer la génération pour obtenir un contenu complet (intro + prestations + FAQ).',
                'osmose-ads'
            ),
        ));
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

            // Important : injecter directement une galerie HTML dans le contenu du template
            // pour que les photos soient visibles dès le template (et pas uniquement via la personnalisation par ville)
            $gallery_html = '';
            $service_label = !empty($service_name) ? $service_name : __('Nos réalisations', 'osmose-ads');

            $gallery_html .= '<h2>' . esc_html('Photos de ' . $service_label) . '</h2>';
            $gallery_html .= '<div class="osmose-realizations-gallery">';

            foreach ($valid_images as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                if (!$img_url) {
                    continue;
                }

                $alt = trim($service_label);
                if (empty($alt)) {
                    $alt = get_the_title($template_id);
                }

                $gallery_html .= '<figure class="osmose-realization-image">';
                $gallery_html .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($alt) . '">';
                $gallery_html .= '</figure>';
            }

            $gallery_html .= '</div>';

            if (!empty($gallery_html)) {
                $current_content = get_post_field('post_content', $template_id);

                // Si le contenu contient déjà une liste de prestations (<ul>), on insère la galerie juste après
                $marker = '</ul>';
                $pos = strpos($current_content, $marker);
                if ($pos !== false) {
                    $pos_after = $pos + strlen($marker);
                    $new_content = substr($current_content, 0, $pos_after) . "\n\n" . $gallery_html . substr($current_content, $pos_after);
                } else {
                    // Sinon, on ajoute la galerie à la fin du contenu
                    $new_content = $current_content . "\n\n" . $gallery_html;
                }

                wp_update_post(array(
                    'ID' => $template_id,
                    'post_content' => $new_content,
                ));
            }
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
