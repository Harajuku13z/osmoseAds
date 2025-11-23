<?php
/**
 * Service de personnalisation de contenu par ville avec IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class City_Content_Personalizer {
    
    private $ai_service;
    
    public function __construct() {
        if (!class_exists('AI_Service')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-ai-service.php';
        }
        $this->ai_service = new AI_Service();
    }
    
    /**
     * Générer du contenu personnalisé pour une ville
     */
    public function generate_personalized_content($template_content, $service_name, $city) {
        // Vérifier le cache
        $cache_key = $this->get_cache_key($service_name, $city->ID, $template_content);
        $cached_content = get_transient($cache_key);
        
        if ($cached_content !== false) {
            return $cached_content;
        }
        
        // Construire le contexte de la ville
        $city_context = $this->build_city_context($city);
        
        // Construire le prompt
        $prompt = $this->build_personalization_prompt($template_content, $service_name, $city, $city_context);
        
        $system_message = 'Tu es un expert en rédaction web SEO spécialisé dans le contenu géolocalisé. Tu génères du contenu unique, naturel et optimisé pour le référencement local.';
        
        // Appeler l'IA
        $personalized_content = $this->ai_service->call_ai($prompt, $system_message, array(
            'temperature' => 0.8,
            'max_tokens' => 3000,
        ));
        
        if (is_wp_error($personalized_content)) {
            // En cas d'erreur, retourner null pour utiliser le fallback
            return null;
        }
        
        // Post-traitement
        $personalized_content = $this->post_process_content($personalized_content, $city);
        
        // Mettre en cache (30 jours)
        set_transient($cache_key, $personalized_content, 30 * DAY_IN_SECONDS);
        
        return $personalized_content;
    }
    
    /**
     * Générer des métadonnées personnalisées
     */
    public function generate_personalized_meta($service_name, $city, $base_meta) {
        $cache_key = 'osmose_ads_meta_' . md5($service_name . '_' . $city->ID);
        $cached_meta = get_transient($cache_key);
        
        if ($cached_meta !== false) {
            return $cached_meta;
        }
        
        $city_context = $this->build_city_context($city);
        $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
        $department = get_post_meta($city->ID, 'department', true);
        
        $prompt = "Génère des métadonnées SEO pour : \"$service_name à $city_name\" (Département: $department).\n\n";
        $prompt .= "Contexte de la ville :\n" . wp_json_encode($city_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "Génère un JSON avec ces champs (en français) :\n";
        $prompt .= "- meta_title (max 60 caractères)\n";
        $prompt .= "- meta_description (max 160 caractères)\n";
        $prompt .= "- meta_keywords (10-15 mots-clés séparés par des virgules)\n";
        $prompt .= "- og_title (max 60 caractères)\n";
        $prompt .= "- og_description (max 200 caractères)\n";
        $prompt .= "- twitter_title (max 60 caractères)\n";
        $prompt .= "- twitter_description (max 200 caractères)\n\n";
        $prompt .= "Réponds UNIQUEMENT avec le JSON, sans texte avant ou après.";
        
        $system_message = 'Tu es un expert SEO. Tu génères des métadonnées optimisées et uniques. Réponds UNIQUEMENT en JSON valide.';
        
        $response = $this->ai_service->call_ai($prompt, $system_message, array(
            'temperature' => 0.7,
            'max_tokens' => 500,
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        // Parser le JSON
        $meta = json_decode(trim($response), true);
        if (!$meta || !is_array($meta)) {
            return null;
        }
        
        // Fusionner avec les métadonnées de base
        $meta = wp_parse_args($meta, $base_meta);
        
        // Mettre en cache
        set_transient($cache_key, $meta, 30 * DAY_IN_SECONDS);
        
        return $meta;
    }
    
    /**
     * Construire le contexte de la ville
     */
    private function build_city_context($city) {
        $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
        $department = get_post_meta($city->ID, 'department', true);
        $region = get_post_meta($city->ID, 'region', true);
        $postal_code = get_post_meta($city->ID, 'postal_code', true);
        $population = get_post_meta($city->ID, 'population', true);
        
        $context = array(
            'name' => $city_name,
            'department' => $department,
            'region' => $region,
            'postal_code' => $postal_code,
            'population' => $population,
        );
        
        // Déterminer le type de zone
        if ($population) {
            if ($population > 100000) {
                $context['zone_type'] = 'grande_ville';
            } elseif ($population > 20000) {
                $context['zone_type'] = 'ville_moyenne';
            } elseif ($population > 5000) {
                $context['zone_type'] = 'petite_ville';
            } else {
                $context['zone_type'] = 'zone_rurale';
            }
        } else {
            $context['zone_type'] = 'non_défini';
        }
        
        // Ajouter des informations contextuelles selon la région
        $context['notes'] = $this->get_regional_notes($region, $department);
        
        return $context;
    }
    
    /**
     * Obtenir des notes régionales
     */
    private function get_regional_notes($region, $department) {
        $notes = array();
        
        // Exemples de notes régionales (à enrichir)
        if (stripos($region, 'Île-de-France') !== false) {
            $notes[] = 'Zone urbaine dense';
            $notes[] = 'Immobilier ancien fréquent';
        }
        
        if (stripos($region, 'Normandie') !== false || stripos($region, 'Bretagne') !== false) {
            $notes[] = 'Climat océanique';
            $notes[] = 'Problèmes d\'humidité fréquents';
        }
        
        return implode(', ', $notes);
    }
    
    /**
     * Construire le prompt de personnalisation
     */
    private function build_personalization_prompt($template_content, $service_name, $city, $city_context) {
        $city_name = $city_context['name'];
        $department = $city_context['department'];
        
        $prompt = "Tu dois personnaliser ce contenu pour le service \"$service_name\" dans la ville de $city_name (Département: $department).\n\n";
        
        $prompt .= "CONTEXTE DE LA VILLE :\n";
        $prompt .= wp_json_encode($city_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        $prompt .= "CONTENU DU TEMPLATE À PERSONNALISER :\n";
        $prompt .= strip_tags($template_content) . "\n\n";
        
        $prompt .= "INSTRUCTIONS :\n";
        $prompt .= "1. Génère un contenu HTML COMPLET et UNIQUE (100% original) adapté à $city_name\n";
        $prompt .= "2. Intègre naturellement le nom de la ville et le département dans le contenu\n";
        $prompt .= "3. Adapte le contenu au contexte local (type de zone, caractéristiques régionales)\n";
        $prompt .= "4. Conserve la structure HTML (H2, H3, listes, paragraphes)\n";
        $prompt .= "5. Inclus une section FAQ pertinente pour cette ville\n";
        $prompt .= "6. Le contenu doit être optimisé SEO mais naturel\n";
        $prompt .= "7. Utilise [VILLE], [DÉPARTEMENT], [RÉGION] comme placeholders si nécessaire (ils seront remplacés)\n";
        $prompt .= "8. Réponds UNIQUEMENT avec le HTML, sans texte avant ou après\n";
        
        return $prompt;
    }
    
    /**
     * Post-traiter le contenu
     */
    private function post_process_content($content, $city) {
        // Nettoyer le contenu
        $content = trim($content);
        
        // S'assurer que c'est du HTML valide
        if (!preg_match('/<[^>]+>/', $content)) {
            // Si pas de HTML, wraper dans des paragraphes
            $content = '<p>' . nl2br(esc_html($content)) . '</p>';
        }
        
        // Remplacer les placeholders
        $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
        $department = get_post_meta($city->ID, 'department', true);
        $region = get_post_meta($city->ID, 'region', true);
        $postal_code = get_post_meta($city->ID, 'postal_code', true);
        
        $replacements = array(
            '[VILLE]' => $city_name,
            '[RÉGION]' => $region ?: '',
            '[DÉPARTEMENT]' => $department ?: '',
            '[CODE_POSTAL]' => $postal_code ?: '',
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Générer la clé de cache
     */
    private function get_cache_key($service_name, $city_id, $template_content) {
        $content_hash = substr(md5($template_content), 0, 20);
        return 'osmose_ads_content_' . md5($service_name . '_' . $city_id . '_' . $content_hash);
    }
}



