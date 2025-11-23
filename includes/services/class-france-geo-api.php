<?php
/**
 * Service pour l'API Géographique de la France
 * Utilise l'API officielle data.gouv.fr
 */

if (!defined('ABSPATH')) {
    exit;
}

class France_Geo_API {
    
    private $api_base_url = 'https://geo.api.gouv.fr';
    
    /**
     * Récupérer toutes les communes d'un département
     */
    public function get_communes_by_department($department_code) {
        $url = $this->api_base_url . '/departements/' . $department_code . '/communes';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $communes = json_decode($body, true);
        
        if (!is_array($communes)) {
            return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
        }
        
        return $communes;
    }
    
    /**
     * Récupérer toutes les communes d'une région
     */
    public function get_communes_by_region($region_code) {
        $url = $this->api_base_url . '/regions/' . $region_code . '/communes';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $communes = json_decode($body, true);
        
        if (!is_array($communes)) {
            return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
        }
        
        return $communes;
    }
    
    /**
     * Récupérer les communes dans un rayon autour d'une ville
     */
    public function get_communes_by_distance($city_code, $distance_km = 10) {
        // Récupérer d'abord les coordonnées de la ville de référence
        $url = $this->api_base_url . '/communes/' . $city_code . '?fields=nom,code,codeDepartement,codeRegion,centre,population,codesPostaux';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $commune_ref = json_decode($body, true);
        
        if (!isset($commune_ref['centre']['coordinates'])) {
            return new WP_Error('no_coordinates', __('Coordonnées non disponibles pour cette commune', 'osmose-ads'));
        }
        
        $lon = $commune_ref['centre']['coordinates'][0];
        $lat = $commune_ref['centre']['coordinates'][1];
        $dept = $commune_ref['codeDepartement'] ?? '';
        
        // Récupérer toutes les communes du département (plus rapide)
        if (!empty($dept)) {
            $all_communes = $this->get_communes_by_department($dept);
            if (is_wp_error($all_communes)) {
                return $all_communes;
            }
        } else {
            return new WP_Error('no_department', __('Impossible de déterminer le département', 'osmose-ads'));
        }
        
        // Filtrer par distance
        $filtered_communes = array();
        foreach ($all_communes as $commune_data) {
            if (isset($commune_data['centre']['coordinates'])) {
                $commune_lon = $commune_data['centre']['coordinates'][0];
                $commune_lat = $commune_data['centre']['coordinates'][1];
                
                $distance = $this->calculate_distance($lat, $lon, $commune_lat, $commune_lon);
                
                if ($distance <= $distance_km) {
                    $commune_data['_distance'] = round($distance, 2);
                    $filtered_communes[] = $commune_data;
                }
            }
        }
        
        // Trier par distance
        usort($filtered_communes, function($a, $b) {
            return ($a['_distance'] ?? 0) <=> ($b['_distance'] ?? 0);
        });
        
        return $filtered_communes;
    }
    
    /**
     * Récupérer tous les départements
     */
    public function get_departments() {
        $cache_key = 'osmose_ads_departments';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = $this->api_base_url . '/departements';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $departments = json_decode($body, true);
        
        if (!is_array($departments)) {
            return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
        }
        
        // Mettre en cache pour 7 jours
        set_transient($cache_key, $departments, 7 * DAY_IN_SECONDS);
        
        return $departments;
    }
    
    /**
     * Récupérer toutes les régions
     */
    public function get_regions() {
        $cache_key = 'osmose_ads_regions';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = $this->api_base_url . '/regions';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $regions = json_decode($body, true);
        
        if (!is_array($regions)) {
            return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
        }
        
        // Mettre en cache pour 7 jours
        set_transient($cache_key, $regions, 7 * DAY_IN_SECONDS);
        
        return $regions;
    }
    
    /**
     * Rechercher une commune par nom
     */
    public function search_commune($name) {
        $url = $this->api_base_url . '/communes';
        $url .= '?nom=' . urlencode($name) . '&fields=nom,code,codeDepartement,codeRegion,centre,population,codesPostaux';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $communes = json_decode($body, true);
        
        return is_array($communes) ? $communes : array();
    }
    
    /**
     * Calculer la distance entre deux points (formule de Haversine)
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // Rayon de la Terre en km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Normaliser les données d'une commune pour l'insertion
     */
    public function normalize_commune_data($commune) {
        $data = array(
            'name' => $commune['nom'] ?? '',
            'code' => $commune['code'] ?? '',
            'postal_code' => $commune['codesPostaux'][0] ?? '',
            'department' => $commune['codeDepartement'] ?? '',
            'region' => $commune['codeRegion'] ?? '',
            'population' => $commune['population'] ?? 0,
        );
        
        // Récupérer le nom du département
        if (!empty($data['department'])) {
            $departments = $this->get_departments();
            if (!is_wp_error($departments)) {
                foreach ($departments as $dept) {
                    if ($dept['code'] === $data['department']) {
                        $data['department_name'] = $dept['nom'];
                        break;
                    }
                }
            }
        }
        
        // Récupérer le nom de la région
        if (!empty($data['region'])) {
            $regions = $this->get_regions();
            if (!is_wp_error($regions)) {
                foreach ($regions as $region) {
                    if ($region['code'] === $data['region']) {
                        $data['region_name'] = $region['nom'];
                        break;
                    }
                }
            }
        }
        
        return $data;
    }
}

