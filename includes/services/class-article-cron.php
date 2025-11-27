<?php
/**
 * Gestion du cron pour la génération automatique d'articles
 */

if (!defined('ABSPATH')) {
    exit;
}

class Osmose_Article_Cron {
    
    private $generator;
    
    public function __construct() {
        if (!class_exists('Osmose_Article_Generator')) {
            require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-article-generator.php';
        }
        $this->generator = new Osmose_Article_Generator();
        
        // Enregistrer le hook de cron pour la publication
        add_action('osmose_article_publish', array($this, 'publish_scheduled_article'));
        
        // Enregistrer le hook de cron pour la génération quotidienne
        add_action('osmose_articles_daily_generation', array($this, 'generate_daily_articles'));
        
        // Vérifier et planifier le cron à chaque chargement admin
        add_action('admin_init', array($this, 'schedule_cron'));
        
        // Vérifier les publications programmées à chaque chargement (pour les sites peu visités)
        add_action('init', array($this, 'check_scheduled_publications'), 20);
    }
    
    /**
     * Planifier le cron selon la configuration
     */
    public function schedule_cron() {
        $auto_generate = get_option('osmose_articles_auto_generate', 0);
        
        if (!$auto_generate) {
            // Désactiver le cron si la génération automatique est désactivée
            $this->unschedule_cron();
            return;
        }
        
        $publish_hours = get_option('osmose_articles_publish_hours', array('09:00'));
        
        if (empty($publish_hours)) {
            return;
        }
        
        // Planifier pour chaque heure configurée
        foreach ($publish_hours as $hour) {
            $this->schedule_hourly_cron($hour);
        }
    }
    
    /**
     * Planifier le cron pour une heure spécifique
     */
    private function schedule_hourly_cron($hour) {
        // Nom unique pour ce cron (basé sur l'heure)
        $hook_name = 'osmose_articles_generate_' . str_replace(':', '_', $hour);
        
        // Vérifier si le cron est déjà planifié
        if (!wp_next_scheduled($hook_name)) {
            // Convertir l'heure en timestamp pour aujourd'hui
            $time_parts = explode(':', $hour);
            $hour_int = intval($time_parts[0]);
            $minute_int = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
            
            $timestamp = mktime($hour_int, $minute_int, 0, date('n'), date('j'), date('Y'));
            
            // Si l'heure est déjà passée aujourd'hui, planifier pour demain
            if ($timestamp < time()) {
                $timestamp = strtotime('+1 day', $timestamp);
            }
            
            // Planifier le cron quotidien
            wp_schedule_event($timestamp, 'daily', $hook_name);
        }
        
        // Ajouter l'action pour ce hook (même si déjà planifié, pour être sûr)
        if (!has_action($hook_name, array($this, 'generate_articles_at_hour'))) {
            add_action($hook_name, array($this, 'generate_articles_at_hour'));
        }
    }
    
    /**
     * Désactiver tous les crons
     */
    private function unschedule_cron() {
        $publish_hours = get_option('osmose_articles_publish_hours', array());
        
        foreach ($publish_hours as $hour) {
            $hook_name = 'osmose_articles_generate_' . str_replace(':', '_', $hour);
            $timestamp = wp_next_scheduled($hook_name);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook_name);
            }
        }
    }
    
    /**
     * Générer les articles à une heure spécifique
     */
    public function generate_articles_at_hour() {
        $articles_per_day = get_option('osmose_articles_per_day', 1);
        $expected_count = $articles_per_day;
        $success_count = 0;
        $errors = array();
        
        // Générer le nombre d'articles configuré
        for ($i = 0; $i < $articles_per_day; $i++) {
            $result = $this->generator->generate_article();
            
            if (!is_wp_error($result) && $result) {
                $success_count++;
                // Planifier la publication selon l'heure configurée
                $this->schedule_publication($result);
            } else {
                $error_msg = is_wp_error($result) ? $result->get_error_message() : __('Erreur inconnue lors de la génération', 'osmose-ads');
                $errors[] = $error_msg;
            }
        }
        
        // Enregistrer un échec si aucun article n'a été créé
        if ($success_count === 0 && $expected_count > 0) {
            $this->record_generation_failure($expected_count, $errors);
        } elseif ($success_count < $expected_count) {
            // Enregistrer un échec partiel
            $this->record_generation_failure($expected_count - $success_count, $errors, true);
        }
    }
    
    /**
     * Enregistrer un échec de génération
     */
    private function record_generation_failure($expected_count, $errors = array(), $partial = false) {
        $failures = get_option('osmose_articles_generation_failures', array());
        
        $failure = array(
            'date' => current_time('mysql'),
            'timestamp' => current_time('timestamp'),
            'expected_count' => $expected_count,
            'errors' => $errors,
            'partial' => $partial,
        );
        
        // Ajouter l'échec au début du tableau
        array_unshift($failures, $failure);
        
        // Garder seulement les 50 derniers échecs
        $failures = array_slice($failures, 0, 50);
        
        update_option('osmose_articles_generation_failures', $failures);
    }
    
    /**
     * Générer les articles quotidiens (méthode alternative)
     */
    public function generate_daily_articles() {
        $articles_per_day = get_option('osmose_articles_per_day', 1);
        $publish_hours = get_option('osmose_articles_publish_hours', array('09:00'));
        
        // Répartir les articles sur les heures de publication
        $articles_per_hour = ceil($articles_per_day / count($publish_hours));
        
        foreach ($publish_hours as $index => $hour) {
            $articles_to_generate = ($index < $articles_per_day % count($publish_hours)) 
                ? $articles_per_hour 
                : ($articles_per_hour - 1);
            
            for ($i = 0; $i < $articles_to_generate; $i++) {
                $result = $this->generator->generate_article();
                
                if (!is_wp_error($result) && $result) {
                    // Planifier la publication à l'heure spécifiée
                    $this->schedule_publication($result, $hour);
                }
            }
        }
    }
    
    /**
     * Planifier la publication d'un article
     */
    private function schedule_publication($post_id, $hour = null) {
        // Récupérer les heures de publication configurées
        $publish_hours = get_option('osmose_articles_publish_hours', array('09:00'));
        
        if (!$hour && !empty($publish_hours)) {
            // Utiliser la première heure configurée
            $hour = $publish_hours[0];
        }
        
        if ($hour) {
            // Convertir l'heure en timestamp
            $time_parts = explode(':', $hour);
            $hour_int = intval($time_parts[0]);
            $minute_int = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
            
            $publish_time = mktime($hour_int, $minute_int, 0, date('n'), date('j'), date('Y'));
            
            // Si l'heure est déjà passée aujourd'hui, planifier pour demain
            if ($publish_time < time()) {
                $publish_time = strtotime('+1 day', $publish_time);
            }
        } else {
            // Utiliser l'heure actuelle + quelques minutes
            $publish_time = strtotime('+10 minutes');
        }
        
        // Stocker la date de publication prévue dans les meta
        update_post_meta($post_id, '_scheduled_publish_time', $publish_time);
        
        // Programmer la publication via WordPress cron
        $scheduled = wp_schedule_single_event($publish_time, 'osmose_article_publish', array($post_id));
        
        // Si le cron ne peut pas être programmé (site peu visité), on vérifiera manuellement
        if (is_wp_error($scheduled)) {
            error_log('Osmose ADS: Impossible de programmer le cron pour l\'article ' . $post_id . ': ' . $scheduled->get_error_message());
        }
    }
    
    /**
     * Vérifier les publications programmées (pour les sites peu visités où le cron ne se déclenche pas)
     */
    public function check_scheduled_publications() {
        // Ne vérifier que toutes les 5 minutes pour éviter la surcharge
        $last_check = get_transient('osmose_articles_last_publish_check');
        if ($last_check && (time() - $last_check) < 300) {
            return;
        }
        
        set_transient('osmose_articles_last_publish_check', time(), 600);
        
        // Récupérer les articles en brouillon avec une date de publication programmée
        $scheduled_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => 50,
            'meta_query' => array(
                array(
                    'key' => '_scheduled_publish_time',
                    'value' => time(),
                    'compare' => '<=',
                ),
                array(
                    'key' => 'article_auto_generated',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        ));
        
        foreach ($scheduled_posts as $post) {
            $scheduled_time = get_post_meta($post->ID, '_scheduled_publish_time', true);
            if ($scheduled_time && $scheduled_time <= time()) {
                $this->publish_scheduled_article($post->ID);
                delete_post_meta($post->ID, '_scheduled_publish_time');
            }
        }
    }
    
    /**
     * Publier un article programmé
     */
    public function publish_scheduled_article($post_id) {
        $post = get_post($post_id);
        
        if ($post && $post->post_status === 'draft') {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish',
            ));
        }
    }
}

// Initialiser le cron
new Osmose_Article_Cron();

