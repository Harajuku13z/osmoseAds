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
        // Utiliser l'endpoint officiel selon la documentation
        // GET /departements/{code}/communes
        $url = $this->api_base_url . '/departements/' . urlencode($department_code) . '/communes';
        
        // Ajouter les paramètres pour obtenir toutes les informations nécessaires
        $url .= '?fields=nom,code,codeDepartement,codeRegion,centre,population,codesPostaux,surface';
        
        $response = wp_remote_get($url, array(
            'timeout' => 60,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new WP_Error('api_error', sprintf(__('Erreur API : code %d', 'osmose-ads'), $code));
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
        // Utiliser l'endpoint officiel selon la documentation
        // Les régions n'ont pas d'endpoint direct, on doit récupérer via les départements
        // GET /regions/{code}/departements puis pour chaque département GET /departements/{code}/communes
        
        $departments_url = $this->api_base_url . '/regions/' . urlencode($region_code) . '/departements';
        
        $response = wp_remote_get($departments_url, array(
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
            return new WP_Error('invalid_response', __('Réponse invalide de l\'API pour les départements', 'osmose-ads'));
        }
        
        // Récupérer toutes les communes de tous les départements de la région
        $all_communes = array();
        foreach ($departments as $department) {
            $dept_code = $department['code'] ?? '';
            if (!empty($dept_code)) {
                $communes = $this->get_communes_by_department($dept_code);
                if (!is_wp_error($communes) && is_array($communes)) {
                    $all_communes = array_merge($all_communes, $communes);
                }
            }
        }
        
        return $all_communes;
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
     * Utilise l'endpoint GET /communes?nom={nom}
     */
    public function search_commune($name, $limit = 5) {
        if (empty($name) || strlen($name) < 2) {
            return array();
        }
        
        $url = $this->api_base_url . '/communes';
        $url .= '?nom=' . urlencode($name);
        $url .= '&fields=nom,code,codeDepartement,codeRegion,centre,population,codesPostaux';
        $url .= '&boost=population'; // Prioriser les villes les plus peuplées
        $url .= '&limit=' . intval($limit);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $communes = json_decode($body, true);
        
        return is_array($communes) ? $communes : array();
    }
    
    /**
     * Récupérer les informations d'une commune par son code INSEE
     * Utilise l'endpoint GET /communes/{code}
     */
    public function get_commune_by_code($code) {
        $url = $this->api_base_url . '/communes/' . urlencode($code);
        $url .= '?fields=nom,code,codeDepartement,codeRegion,centre,population,codesPostaux,surface';
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code_response = wp_remote_retrieve_response_code($response);
        if ($code_response !== 200) {
            return new WP_Error('not_found', __('Commune non trouvée', 'osmose-ads'));
        }
        
        $body = wp_remote_retrieve_body($response);
        $commune = json_decode($body, true);
        
        return is_array($commune) ? $commune : new WP_Error('invalid_response', __('Réponse invalide', 'osmose-ads'));
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
        // Récupérer le code postal principal (le premier dans le tableau)
        $postal_codes = $commune['codesPostaux'] ?? array();
        $postal_code = is_array($postal_codes) && !empty($postal_codes) ? $postal_codes[0] : '';
        
        // Si plusieurs codes postaux, les joindre avec des virgules
        $all_postal_codes = is_array($postal_codes) ? implode(', ', $postal_codes) : $postal_code;
        
        $data = array(
            'name' => $commune['nom'] ?? '',
            'code' => $commune['code'] ?? '',
            'postal_code' => $postal_code,
            'all_postal_codes' => $all_postal_codes,
            'department' => $commune['codeDepartement'] ?? '',
            'region' => $commune['codeRegion'] ?? '',
            'population' => isset($commune['population']) ? intval($commune['population']) : 0,
            'surface' => isset($commune['surface']) ? floatval($commune['surface']) : 0,
        );
        
        // Récupérer les coordonnées si disponibles
        if (isset($commune['centre']['coordinates']) && is_array($commune['centre']['coordinates'])) {
            $data['longitude'] = $commune['centre']['coordinates'][0] ?? 0;
            $data['latitude'] = $commune['centre']['coordinates'][1] ?? 0;
        }
        
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

