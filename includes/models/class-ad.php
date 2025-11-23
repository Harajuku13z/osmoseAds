<?php
/**
 * Modèle Ad
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad {
    
    private $post_id;
    
    public function __construct($post_id) {
        $this->post_id = $post_id;
    }
    
    /**
     * Récupérer la ville associée
     */
    public function get_city() {
        $city_id = get_post_meta($this->post_id, 'city_id', true);
        if ($city_id) {
            return get_post($city_id);
        }
        return null;
    }
    
    /**
     * Récupérer le template associé
     */
    public function get_template() {
        $template_id = get_post_meta($this->post_id, 'template_id', true);
        if ($template_id) {
            return new Ad_Template($template_id);
        }
        return null;
    }
    
    /**
     * Récupérer le contenu final de l'annonce
     */
    public function get_content() {
        $template = $this->get_template();
        $city = $this->get_city();
        
        if ($template && $city) {
            return $template->get_content_for_city($city->ID);
        }
        
        // Fallback : contenu direct
        return get_post_field('post_content', $this->post_id);
    }
    
    /**
     * Récupérer les métadonnées finales
     */
    public function get_meta() {
        $template = $this->get_template();
        $city = $this->get_city();
        
        if ($template && $city) {
            return $template->get_meta_for_city($city->ID);
        }
        
        // Fallback : métadonnées directes
        return array(
            'meta_title' => get_post_meta($this->post_id, 'meta_title', true),
            'meta_description' => get_post_meta($this->post_id, 'meta_description', true),
            'meta_keywords' => get_post_meta($this->post_id, 'meta_keywords', true),
            'og_title' => get_post_meta($this->post_id, 'og_title', true),
            'og_description' => get_post_meta($this->post_id, 'og_description', true),
            'twitter_title' => get_post_meta($this->post_id, 'twitter_title', true),
            'twitter_description' => get_post_meta($this->post_id, 'twitter_description', true),
        );
    }
    
    /**
     * Récupérer la date de publication
     */
    public function get_publication_date() {
        $published_at = get_post_meta($this->post_id, 'published_at', true);
        if ($published_at) {
            return $published_at;
        }
        
        $post = get_post($this->post_id);
        return $post->post_date;
    }
    
    /**
     * Récupérer la date formatée
     */
    public function get_formatted_publication_date($format = 'd/m/Y') {
        $date = $this->get_publication_date();
        return date_i18n($format, strtotime($date));
    }
    
    /**
     * Récupérer le statut
     */
    public function get_status() {
        $status = get_post_meta($this->post_id, 'status', true);
        if (!$status) {
            $post = get_post($this->post_id);
            return $post->post_status === 'publish' ? 'published' : 'draft';
        }
        return $status;
    }
    
    /**
     * Récupérer une annonce par slug
     */
    public static function get_by_slug($slug) {
        $posts = get_posts(array(
            'post_type' => 'ad',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ));
        
        if (!empty($posts)) {
            return new self($posts[0]->ID);
        }
        
        return null;
    }
    
    /**
     * Récupérer les annonces similaires
     */
    public function get_related_ads($limit = 5) {
        $city = $this->get_city();
        if (!$city) {
            return array();
        }
        
        $city_id = $city->ID;
        $posts = get_posts(array(
            'post_type' => 'ad',
            'posts_per_page' => $limit + 1, // +1 pour exclure la current
            'post_status' => 'publish',
            'meta_key' => 'city_id',
            'meta_value' => $city_id,
            'post__not_in' => array($this->post_id),
        ));
        
        return array_slice($posts, 0, $limit);
    }
}



