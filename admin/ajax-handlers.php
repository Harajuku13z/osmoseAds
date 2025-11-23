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
        // Construire le nouveau prompt premium basé sur le modèle fourni
        $images_info = '';
        if ($featured_image_id) {
            $images_info .= "\n- Une image mise en avant professionnelle est disponible pour illustrer le service.";
        }
        if (!empty($realization_images)) {
            $images_info .= "\n- Des photos de réalisations concrètes sont disponibles pour enrichir le contenu.";
        }
        
        // Construire la liste des mots-clés pour le prompt
        $keywords_list = '';
        if (!empty($service_keywords)) {
            $keywords_array = array_map('trim', explode(',', $service_keywords));
            $keywords_list = implode(', ', $keywords_array);
        }
        
        // Construire le prompt complet selon le nouveau modèle
        $prompt = "# Prompt de Génération d'Articles SEO Premium\n\n";
        $prompt .= "## IDENTITÉ ET EXPERTISE\n\n";
        $prompt .= "Tu es un rédacteur web expert en SEO local pour le secteur du BTP, spécialisé dans la création de contenus premium pour les artisans. Tu maîtrises parfaitement le vocabulaire technique du métier, les enjeux clients et les meilleures pratiques éditoriales WordPress.\n\n";
        $prompt .= "---\n\n";
        $prompt .= "## MISSION PRINCIPALE\n\n";
        $prompt .= "Créer un article HTML complet, géolocalisé et optimisé SEO pour promouvoir les services d'une entreprise artisanale dans une ville spécifique.\n\n";
        $prompt .= "---\n\n";
        $prompt .= "## DONNÉES D'ENTRÉE (À FOURNIR)\n\n";
        $prompt .= "### Informations Entreprise\n\n";
        $prompt .= "- **Nom de l'entreprise** : " . ($company_name ?: '[NOM_ENTREPRISE]') . "\n";
        $prompt .= "- **Ville cible** : [VILLE]\n";
        $prompt .= "- **Département** : [DÉPARTEMENT]\n";
        $prompt .= "- **Région** : [RÉGION]\n";
        $prompt .= "- **Téléphone** : " . ($company_phone ?: '[TELEPHONE]') . "\n";
        $prompt .= "- **Email** : " . ($company_email ?: '[EMAIL]') . "\n\n";
        $prompt .= "### Informations Services\n\n";
        $prompt .= "- **Service principal** : " . $service_name . "\n";
        
        if (!empty($service_description)) {
            $prompt .= "- **Description du service** : " . $service_description . "\n";
        }
        
        if (!empty($keywords_list)) {
            $prompt .= "- **Mots-clés SEO prioritaires** : " . $keywords_list . "\n";
        }
        
        if (!empty($images_info)) {
            $prompt .= "\n" . $images_info . "\n";
        }
        
        $prompt .= "\n---\n\n";
        $prompt .= "## MÉTHODOLOGIE DE CRÉATION\n\n";
        $prompt .= "### PHASE 1 : Recherche et Analyse (Mental Map)\n\n";
        $prompt .= "Avant de rédiger, structure ta réflexion autour de :\n\n";
        $prompt .= "**Contexte géographique**\n\n";
        $prompt .= "- Particularités climatiques de [RÉGION]/[DÉPARTEMENT]\n";
        $prompt .= "- Enjeux architecturaux locaux\n";
        $prompt .= "- Problématiques spécifiques dans cette zone\n\n";
        $prompt .= "**Besoins et pain points clients**\n\n";
        $prompt .= "- Quels problèmes rencontrent les propriétaires ?\n";
        $prompt .= "- Quelles sont leurs craintes ? (coût, durée, qualité)\n";
        $prompt .= "- Quelles sont leurs attentes ? (durabilité, esthétique, garanties)\n\n";
        $prompt .= "### PHASE 2 : Architecture de l'Article\n\n";
        $prompt .= "Conçois une structure narrative qui suit ce schéma :\n\n";
        $prompt .= "1. **Accroche contextualisée** (150-200 mots)\n";
        $prompt .= "   - Interpelle le propriétaire de [VILLE]\n";
        $prompt .= "   - Évoque les enjeux locaux\n";
        $prompt .= "   - Présente l'entreprise comme expert de proximité\n\n";
        $prompt .= "2. **Section Présentation d'Entreprise** (200-250 mots)\n";
        $prompt .= "   - Légitimité et expertise\n";
        $prompt .= "   - Spécialités et savoir-faire unique\n";
        $prompt .= "   - Zone d'intervention (insister sur [VILLE])\n\n";
        $prompt .= "3. **Sections Techniques par Service** (300-400 mots chacune)\n";
        $prompt .= "   - Titre H2 engageant et géolocalisé\n";
        $prompt .= "   - Problématique client\n";
        $prompt .= "   - Solutions techniques détaillées\n";
        $prompt .= "   - Bénéfices concrets\n";
        $prompt .= "   - Sous-sections H3 pour les détails\n\n";
        $prompt .= "4. **Section Matériaux et Techniques** (250-300 mots)\n";
        $prompt .= "   - Liste descriptive des matériaux travaillés\n";
        $prompt .= "   - Avantages de chaque solution\n";
        $prompt .= "   - Conseils adaptés au climat local\n\n";
        $prompt .= "5. **Section Protocole/Process** (200-250 mots)\n";
        $prompt .= "   - Étapes d'intervention détaillées\n";
        $prompt .= "   - Méthodes professionnelles\n\n";
        $prompt .= "6. **Services Complémentaires** (200-250 mots)\n";
        $prompt .= "   - Prestations annexes\n";
        $prompt .= "   - Vision globale de l'habitat\n\n";
        $prompt .= "7. **Section Pourquoi Nous Choisir** (200-250 mots)\n";
        $prompt .= "   - Arguments de différenciation\n";
        $prompt .= "   - Garanties et engagements\n";
        $prompt .= "   - Proximité et réactivité\n\n";
        $prompt .= "8. **FAQ Exhaustive** (6-8 questions)\n";
        $prompt .= "   - Questions réelles de propriétaires\n";
        $prompt .= "   - Réponses complètes de 120-180 mots\n";
        $prompt .= "   - Ton rassurant et pédagogique\n\n";
        $prompt .= "9. **Call-to-Action Final** (100-150 mots)\n";
        $prompt .= "   - Récapitulatif de la proposition de valeur\n";
        $prompt .= "   - Invitation claire à l'action (devis gratuit)\n";
        $prompt .= "   - Coordonnées complètes formatées\n\n";
        $prompt .= "---\n\n";
        $prompt .= "## EXIGENCES RÉDACTIONNELLES CRITIQUES\n\n";
        $prompt .= "### Style et Ton\n\n";
        $prompt .= "- **Professionnel mais accessible** : Vocabulaire technique expliqué simplement\n";
        $prompt .= "- **Rassurant et empathique** : Comprend les préoccupations du propriétaire\n";
        $prompt .= "- **Orienté bénéfices** : Chaque argument technique = avantage concret\n";
        $prompt .= "- **Local et personnalisé** : Références fréquentes à [VILLE], [DÉPARTEMENT], climat local\n";
        $prompt .= "- **Engageant** : Utilise \"vous\", \"votre maison\", \"votre projet\"\n\n";
        $prompt .= "### Qualité du Contenu\n\n";
        $prompt .= "- **Densité informationnelle élevée** : Chaque paragraphe apporte de la valeur\n";
        $prompt .= "- **Zéro remplissage** : Pas de phrases creuses ou génériques\n";
        $prompt .= "- **Exemples concrets** : Situations réelles, problèmes types, solutions éprouvées\n";
        $prompt .= "- **Données tangibles** : Épaisseurs, températures, durées de vie, garanties\n";
        $prompt .= "- **Transparence** : Explique les choix techniques, les matériaux, les méthodes\n\n";
        $prompt .= "### Optimisation SEO Naturelle\n\n";
        $prompt .= "- **Intégration fluide des mots-clés** : Contextualisés, jamais forcés\n";
        $prompt .= "- **Variations sémantiques** : Synonymes, termes connexes du métier\n";
        $prompt .= "- **Longue traîne** : Questions naturelles, formulations variées\n";
        $prompt .= "- **Géolocalisation organique** : [VILLE] + service mentionnés 8-12 fois naturellement\n";
        $prompt .= "- **Richesse lexicale** : Vocabulaire technique varié\n\n";
        $prompt .= "### Structure HTML Irréprochable\n\n";
        $prompt .= "Utilise uniquement : `<h2>`, `<h3>`, `<p>`, `<ul>`, `<ol>`, `<li>`, `<strong>`, `<em>`, `<a>`, `<blockquote>`, `<br>` (avec parcimonie)\n\n";
        $prompt .= "**Interdictions absolues** : Pas de `<h1>`, `<div>`, `<span>`, `<style>`, `<script>`, `<html>`, `<head>`, `<body>`\n\n";
        $prompt .= "---\n\n";
        $prompt .= "## LONGUEUR ET STRUCTURE FINALE\n\n";
        $prompt .= "- **Longueur totale** : 2500-3500 mots\n";
        $prompt .= "- **Paragraphes courts** : 3-5 lignes maximum\n";
        $prompt .= "- **Hiérarchie claire** : H2 pour sections principales, H3 pour sous-sections\n";
        $prompt .= "- **Listes à puces** pour la lisibilité\n\n";
        $prompt .= "---\n\n";
        $prompt .= "## FORMAT DE LIVRAISON\n\n";
        $prompt .= "Du code HTML pur, prêt pour WordPress, commençant directement par le H2 principal.\n";
        $prompt .= "Aucune balise wrapper. Compatible wp_kses_post.\n\n";
        $prompt .= "**Génère maintenant un article HTML premium répondant à toutes ces exigences, avec une recherche approfondie des besoins clients et une structure narrative engageante.**\n";
    }
    
    $system_message = 'Tu es un rédacteur web expert en SEO local pour le secteur du BTP, spécialisé dans la création de contenus premium pour les artisans. Tu maîtrises parfaitement le vocabulaire technique du métier, les enjeux clients et les meilleures pratiques éditoriales WordPress.';
    
    // Générer le contenu principal avec plus de tokens pour un contenu de qualité
    $ai_response = $ai_service->call_ai($prompt, $system_message, array(
        'temperature' => 0.8,
        'max_tokens' => 4000,
    ));
    
    if (is_wp_error($ai_response)) {
        wp_send_json_error(array('message' => $ai_response->get_error_message()));
    }
    
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
        
        // Créer l'annonce
        $ad_id = wp_insert_post(array(
            'post_title' => $service_name . ' à ' . $city_name,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => 'ad',
            'post_status' => 'publish',
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
