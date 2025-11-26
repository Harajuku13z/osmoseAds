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
                            // S'assurer que les placeholders sont remplacés même dans le contenu personnalisé
                            $final_content = $this->replace_variables($final_content, $city_id);
                        }
                    }
                }
            }
        }
        
        // Fallback : remplacement de variables basique
        if (empty($final_content)) {
            $final_content = $this->replace_variables($template_content, $city_id);
        } else {
            // S'assurer que même si on a du contenu, les placeholders sont remplacés
            $final_content = $this->replace_variables($final_content, $city_id);
        }
        
        // Nettoyage : supprimer toute ancienne galerie statique déjà présente dans le template
        // (galeries ajoutées directement dans le contenu avant cette version)
        $final_content = preg_replace(
            '#<h2>Photos de.*?</h2>\s*<div class="osmose-realizations-gallery">.*?</div>#is',
            '',
            $final_content
        );
        
        // Ajouter les images de réalisations dans le contenu avec ALT optimisé (mot-clé + ville)
        $realization_images = get_post_meta($this->post_id, 'realization_images', true);
        if (!empty($realization_images) && is_array($realization_images)) {
            $city = get_post($city_id);
            $city_name = '';
            if ($city) {
                $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
            }
            
            $service_name = get_post_meta($this->post_id, 'service_name', true);
            $focus_keyword = trim($service_name . ' ' . $city_name);
            
            // Construire une section HTML pour les photos (après la liste des prestations)
            $gallery_html = '';
            
            // Titre de section incluant le mot-clé et la ville pour le SEO
            if (!empty($focus_keyword)) {
                $gallery_html .= '<h2>' . esc_html('Photos de ' . $focus_keyword) . '</h2>';
            } elseif (!empty($city_name)) {
                $gallery_html .= '<h2>' . esc_html('Nos réalisations à ' . $city_name) . '</h2>';
            }
            
            $gallery_html .= '<div class="osmose-realizations-gallery">';
            
            foreach ($realization_images as $img_id) {
                if (!wp_attachment_is_image($img_id)) {
                    continue;
                }
                
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                if (!$img_url) {
                    continue;
                }
                
                // Récupérer les mots-clés SEO spécifiques à cette image (définis lors de la création)
                $image_keywords = get_post_meta($img_id, '_osmose_image_keywords', true);
                $image_keywords = is_string($image_keywords) ? trim($image_keywords) : '';
                
                // Construire l'ALT en priorité avec: mots-clés image + ville
                if (!empty($image_keywords) && !empty($city_name)) {
                    $alt = trim($image_keywords . ' ' . $city_name);
                } elseif (!empty($focus_keyword)) {
                    // Sinon, utiliser le focus keyword (service + ville)
                    $alt = $focus_keyword;
                } else {
                    // Dernier fallback : service + ville, ou titre du template
                    $alt = trim(($service_name ?: '') . (empty($city_name) ? '' : ' ' . $city_name));
                }
                
                if (empty($alt)) {
                    $alt = get_the_title($this->post_id);
                }
                
                $gallery_html .= '<figure class="osmose-realization-image">';
                $gallery_html .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($alt) . '">';
                $gallery_html .= '</figure>';
            }
            
            $gallery_html .= '</div>';
            
            // Tenter d'insérer la galerie juste après la liste des prestations
            $inserted = false;
            $marker = '</ul>';
            $pos = strpos($final_content, $marker);
            if ($pos !== false) {
                $pos_after = $pos + strlen($marker);
                $final_content = substr($final_content, 0, $pos_after) . "\n\n" . $gallery_html . substr($final_content, $pos_after);
                $inserted = true;
            }
            
            // Fallback : ajouter la galerie à la fin du contenu si aucun <ul> trouvé
            if (!$inserted && !empty($gallery_html)) {
                $final_content .= "\n\n" . $gallery_html;
            }
        }

        // Ajouter des liens internes/externes si le contenu n'en contient pas assez
        $link_count = substr_count($final_content, '<a ');
        if ($link_count < 2) {
            $site_url = get_site_url();
            $devis_url = get_option('osmose_ads_devis_url', '');
            
            // Chercher des opportunités pour ajouter des liens dans le contenu
            // Lien vers la page d'accueil ou autres services
            if (strpos($final_content, 'nos services') !== false || strpos($final_content, 'autres services') !== false) {
                $final_content = preg_replace(
                    '/(nos services|autres services)/i',
                    '<a href="' . esc_url($site_url) . '" class="text-blue-600 hover:underline">$1</a>',
                    $final_content,
                    1
                );
            }
            
            // Lien vers devis si mentionné
            if (!empty($devis_url) && strpos($final_content, 'devis') !== false) {
                $final_content = preg_replace(
                    '/(devis (?:gratuit|détaillé|personnalisé)?)/i',
                    '<a href="' . esc_url($devis_url) . '" class="text-blue-600 hover:underline">$1</a>',
                    $final_content,
                    1
                );
            }
            
            // Si toujours pas assez de liens, ajouter une section avec liens à la fin
            if (substr_count($final_content, '<a ') < 2) {
                $links_section = '<div class="osmose-internal-links space-y-2 mt-6">';
                $links_section .= '<p class="text-sm text-gray-600">En savoir plus : ';
                $links_section .= '<a href="' . esc_url($site_url) . '" class="text-blue-600 hover:underline">Nos services</a>';
                if (!empty($devis_url)) {
                    $links_section .= ' | <a href="' . esc_url($devis_url) . '" class="text-blue-600 hover:underline">Demander un devis</a>';
                }
                $links_section .= '</p>';
                $links_section .= '</div>';
                $final_content .= "\n\n" . $links_section;
            }
        }

        // Validation finale : s'assurer qu'aucun placeholder ne reste
        $placeholders = array('[VILLE]', '[RÉGION]', '[DÉPARTEMENT]', '[CODE_POSTAL]');
        $remaining_placeholders = array();
        foreach ($placeholders as $placeholder) {
            if (strpos($final_content, $placeholder) !== false) {
                $remaining_placeholders[] = $placeholder;
            }
        }
        
        // Si des placeholders restent, forcer le remplacement une dernière fois
        if (!empty($remaining_placeholders)) {
            error_log('Osmose ADS: Placeholders non remplacés détectés dans le contenu final: ' . implode(', ', $remaining_placeholders) . ' - Remplacement forcé');
            $final_content = $this->replace_variables($final_content, $city_id);
            
            // Vérifier à nouveau après remplacement
            $still_remaining = array();
            foreach ($placeholders as $placeholder) {
                if (strpos($final_content, $placeholder) !== false) {
                    $still_remaining[] = $placeholder;
                }
            }
            if (!empty($still_remaining)) {
                error_log('Osmose ADS: ERREUR - Placeholders toujours présents après remplacement: ' . implode(', ', $still_remaining));
            }
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

        // S'assurer qu'une meta_description existe toujours (fallback si vide)
        if (empty($meta['meta_description'])) {
            $service_name = get_post_meta($this->post_id, 'service_name', true);
            $city = get_post($city_id);
            $city_name = '';
            if ($city) {
                $city_name = get_post_meta($city_id, 'name', true) ?: $city->post_title;
            }
            $meta['meta_description'] = 'Service professionnel de ' . $service_name . ' à ' . $city_name . '. Devis gratuit, intervention rapide, garantie sur tous nos travaux.';
        }

        // Limiter meta_description à 160 caractères (norme SEO)
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($meta['meta_description']) > 160) {
                $meta['meta_description'] = mb_substr($meta['meta_description'], 0, 157) . '...';
            }
        } else {
            if (strlen($meta['meta_description']) > 160) {
                $meta['meta_description'] = substr($meta['meta_description'], 0, 157) . '...';
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
        
        $company_name = get_bloginfo('name');
        
        $replacements = array(
            '[VILLE]' => $city_name,
            '[RÉGION]' => $region ?: '',
            '[DÉPARTEMENT]' => $department ?: '',
            '[CODE_POSTAL]' => $postal_code ?: '',
            '[ENTREPRISE]' => $company_name,
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

