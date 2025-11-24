<?php
/**
 * Modèle Ad_Template
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Template {
    
    public $post_id;
    
    public function __construct($post_id) {
        $this->post_id = $post_id;
    }
    
    /**
     * Récupérer le contenu personnalisé pour une ville
     * Avec système de cache de 30 jours
     */
    public function get_content_for_city($city_id, $use_ai = null) {
        $template_content = get_post_field('post_content', $this->post_id);
        
        if (empty($template_content)) {
            return '';
        }
        
        // Déterminer si on utilise l'IA
        if ($use_ai === null) {
            $use_ai = get_option('osmose_ads_ai_personalization', false);
        }
        
        // Générer une clé de cache unique
        $cache_key = 'osmose_content_' . $this->post_id . '_' . $city_id . '_' . md5(substr($template_content, 0, 100));
        
        // Vérifier le cache
        $cached_content = get_transient($cache_key);
        if ($cached_content !== false) {
            return $cached_content;
        }
        
        $final_content = '';
        
        if ($use_ai) {
            // Personnalisation IA avancée (fonctionnalité future)
            // Vérifier que le fichier existe avant de l'inclure
            $personalizer_file = OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-city-content-personalizer.php';
            
            if (file_exists($personalizer_file)) {
                if (!class_exists('City_Content_Personalizer')) {
                    require_once $personalizer_file;
                }
                
                if (class_exists('City_Content_Personalizer')) {
                    $personalizer = new City_Content_Personalizer();
                    $city = get_post($city_id);
                    
                    if ($city) {
                        $service_name = get_post_meta($this->post_id, 'service_name', true);
                        $personalized_content = $personalizer->generate_personalized_content(
                            $template_content,
                            $service_name,
                            $city
                        );
                        
                        if ($personalized_content) {
                            $final_content = $personalized_content;
                        }
                    }
                }
            }
        }
        
        // Fallback : remplacement de variables basique
        if (empty($final_content)) {
            $final_content = $this->replace_variables($template_content, $city_id);
        }
        
        // Mettre en cache pour 30 jours (2592000 secondes)
        set_transient($cache_key, $final_content, 2592000);
        
        return $final_content;
    }
    
    /**
     * Récupérer les métadonnées personnalisées pour une ville
     */
    public function get_meta_for_city($city_id, $use_ai = null) {
        $meta = array(
            'meta_title' => get_post_meta($this->post_id, 'meta_title', true),
            'meta_description' => get_post_meta($this->post_id, 'meta_description', true),
            'meta_keywords' => get_post_meta($this->post_id, 'meta_keywords', true),
            'og_title' => get_post_meta($this->post_id, 'og_title', true),
            'og_description' => get_post_meta($this->post_id, 'og_description', true),
            'twitter_title' => get_post_meta($this->post_id, 'twitter_title', true),
            'twitter_description' => get_post_meta($this->post_id, 'twitter_description', true),
        );
        
        // Déterminer si on utilise l'IA
        if ($use_ai === null) {
            $use_ai = get_option('osmose_ads_ai_personalization', false);
        }
        
        if ($use_ai) {
            // Personnalisation IA avancée (fonctionnalité future)
            $personalizer_file = OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-city-content-personalizer.php';
            
            if (file_exists($personalizer_file)) {
                if (!class_exists('City_Content_Personalizer')) {
                    require_once $personalizer_file;
                }
                
                if (class_exists('City_Content_Personalizer')) {
                    $personalizer = new City_Content_Personalizer();
                    $city = get_post($city_id);
                    
                    if ($city) {
                        $service_name = get_post_meta($this->post_id, 'service_name', true);
                        $personalized_meta = $personalizer->generate_personalized_meta(
                            $service_name,
                            $city,
                            $meta
                        );
                        
                        if ($personalized_meta) {
                            return $personalized_meta;
                        }
                    }
                }
            }
        }
        
        // Fallback : remplacement de variables
        foreach ($meta as $key => $value) {
            if ($value) {
                $meta[$key] = $this->replace_variables($value, $city_id);
            }
        }
        
        return $meta;
    }
    
    /**
     * Remplacer les variables dans le contenu
     */
    private function replace_variables($content, $city_id) {
        $city = get_post($city_id);
        if (!$city) {
            return $content;
        }
        
        $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
        $department = get_post_meta($city_id, 'department', true);
        $region = get_post_meta($city_id, 'region', true);
        $postal_code = get_post_meta($city_id, 'postal_code', true);
        
        $site_url = get_site_url();
        $company_phone = get_option('osmose_ads_company_phone', '');
        $company_phone_raw = get_option('osmose_ads_company_phone_raw', $company_phone);
        
        $replacements = array(
            '[VILLE]' => $city_name,
            '[RÉGION]' => $region ?: '',
            '[DÉPARTEMENT]' => $department ?: '',
            '[CODE_POSTAL]' => $postal_code ?: '',
            '[FORM_URL]' => $site_url . '/devis',
            '[URL]' => $site_url . '/ads/[SLUG]', // Sera remplacé plus tard
            '[PHONE]' => $company_phone,
            '[PHONE_RAW]' => $company_phone_raw,
            '[TITRE]' => get_the_title($this->post_id),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Incrémenter le compteur d'utilisation
     */
    public function increment_usage() {
        $count = (int) get_post_meta($this->post_id, 'usage_count', true);
        update_post_meta($this->post_id, 'usage_count', $count + 1);
    }
    
    /**
     * Décrémenter le compteur d'utilisation
     */
    public function decrement_usage() {
        $count = (int) get_post_meta($this->post_id, 'usage_count', true);
        if ($count > 0) {
            update_post_meta($this->post_id, 'usage_count', $count - 1);
        }
    }
    
    /**
     * Récupérer un template par slug de service
     */
    public static function get_by_service_slug($service_slug) {
        $posts = get_posts(array(
            'post_type' => 'ad_template',
            'meta_key' => 'service_slug',
            'meta_value' => $service_slug,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ));
        
        if (!empty($posts)) {
            return new self($posts[0]->ID);
        }
        
        return null;
    }
    
    /**
     * Vérifier si le template est actif
     */
    public function is_active() {
        $is_active = get_post_meta($this->post_id, 'is_active', true);
        return $is_active !== '0' && $is_active !== false;
    }
    
    /**
     * Récupérer le compteur d'utilisation
     */
    public function get_usage_count() {
        return (int) get_post_meta($this->post_id, 'usage_count', true);
    }
    
    /**
     * Invalider le cache pour toutes les villes
     */
    public function clear_cache() {
        global $wpdb;
        
        // Supprimer tous les transients commençant par osmose_content_{template_id}_
        $prefix = 'osmose_content_' . $this->post_id . '_';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            '_transient_' . $wpdb->esc_like($prefix) . '%'
        ));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            '_transient_timeout_' . $wpdb->esc_like($prefix) . '%'
        ));
    }
}

