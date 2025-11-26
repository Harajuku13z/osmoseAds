<?php
/**
 * Modèle City
 * Classe simple pour gérer les villes
 */

if (!defined('ABSPATH')) {
    exit;
}

class City {
    
    public $post_id;
    
    public function __construct($post_id) {
        $this->post_id = intval($post_id);
    }
    
    /**
     * Récupérer le post WordPress
     */
    public function get_post() {
        return get_post($this->post_id);
    }
    
    /**
     * Récupérer le nom de la ville
     */
    public function get_name() {
        $post = $this->get_post();
        if ($post) {
            // Essayer d'abord le meta 'name'
            $name = get_post_meta($this->post_id, 'name', true);
            if ($name) {
                return $name;
            }
            // Sinon utiliser le titre
            return $post->post_title;
        }
        return '';
    }
    
    /**
     * Récupérer le département
     */
    public function get_department() {
        return get_post_meta($this->post_id, 'department', true);
    }
    
    /**
     * Récupérer la région
     */
    public function get_region() {
        return get_post_meta($this->post_id, 'region', true);
    }
    
    /**
     * Récupérer le code postal
     */
    public function get_postal_code() {
        return get_post_meta($this->post_id, 'postal_code', true);
    }
    
    /**
     * Récupérer toutes les informations de la ville
     */
    public function get_all_data() {
        return array(
            'id' => $this->post_id,
            'name' => $this->get_name(),
            'department' => $this->get_department(),
            'region' => $this->get_region(),
            'postal_code' => $this->get_postal_code(),
        );
    }
    
    /**
     * Récupérer une ville par son nom
     */
    public static function get_by_name($name) {
        $posts = get_posts(array(
            'post_type' => 'city',
            'title' => $name,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ));
        
        if (!empty($posts)) {
            return new self($posts[0]->ID);
        }
        
        // Essayer aussi avec le meta 'name'
        $posts = get_posts(array(
            'post_type' => 'city',
            'meta_key' => 'name',
            'meta_value' => $name,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ));
        
        if (!empty($posts)) {
            return new self($posts[0]->ID);
        }
        
        return null;
    }
    
    /**
     * Récupérer une ville par code postal
     */
    public static function get_by_postal_code($postal_code) {
        $posts = get_posts(array(
            'post_type' => 'city',
            'meta_key' => 'postal_code',
            'meta_value' => $postal_code,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ));
        
        if (!empty($posts)) {
            return new self($posts[0]->ID);
        }
        
        return null;
    }
}

