<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Données géographiques officielles (geo.api.gouv.fr), mises en cache.
 */
class OA_Geo {

    const API    = 'https://geo.api.gouv.fr';
    const FIELDS = 'nom,code,codesPostaux,population,centre,departement,region';

    private static function request($path, array $args = array(), $ttl = WEEK_IN_SECONDS) {
        $url = add_query_arg($args, self::API . $path);
        $key = 'oa_geo_' . md5($url);
        $hit = get_transient($key);
        if (false !== $hit) {
            return $hit;
        }
        $res = wp_remote_get($url, array('timeout' => 20, 'headers' => array('Accept' => 'application/json')));
        if (is_wp_error($res)) {
            return $res;
        }
        if (200 !== (int) wp_remote_retrieve_response_code($res)) {
            return new WP_Error('oa_geo', 'API géographique indisponible (' . wp_remote_retrieve_response_code($res) . ')');
        }
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if (!is_array($data)) {
            return new WP_Error('oa_geo', 'Réponse géographique invalide');
        }
        set_transient($key, $data, $ttl);
        return $data;
    }

    private static function normalize(array $c) {
        $lon = isset($c['centre']['coordinates'][0]) ? (float) $c['centre']['coordinates'][0] : 0;
        $lat = isset($c['centre']['coordinates'][1]) ? (float) $c['centre']['coordinates'][1] : 0;
        return array(
            'code'        => (string) $c['code'],
            'name'        => (string) $c['nom'],
            'postcode'    => isset($c['codesPostaux'][0]) ? (string) $c['codesPostaux'][0] : '',
            'population'  => isset($c['population']) ? (int) $c['population'] : 0,
            'lat'         => $lat,
            'lon'         => $lon,
            'dept_code'   => isset($c['departement']['code']) ? (string) $c['departement']['code'] : '',
            'dept_name'   => isset($c['departement']['nom']) ? (string) $c['departement']['nom'] : '',
            'region'      => isset($c['region']['nom']) ? (string) $c['region']['nom'] : '',
        );
    }

    /** Autocomplétion par nom ou code postal. */
    public static function search($query) {
        $query = trim($query);
        if ('' === $query) {
            return array();
        }
        $args = preg_match('/^\d{5}$/', $query)
            ? array('codePostal' => $query)
            : array('nom' => $query, 'boost' => 'population', 'limit' => 10);
        $args['fields'] = self::FIELDS;
        $data = self::request('/communes', $args, DAY_IN_SECONDS);
        if (is_wp_error($data)) {
            return $data;
        }
        return array_map(array(__CLASS__, 'normalize'), $data);
    }

    public static function get($code) {
        $data = self::request('/communes/' . rawurlencode($code), array('fields' => self::FIELDS));
        if (is_wp_error($data)) {
            return $data;
        }
        return self::normalize($data);
    }

    /** Communes d'un département (pour le calcul de proximité). */
    public static function department($dept_code) {
        $data = self::request('/departements/' . rawurlencode($dept_code) . '/communes', array('fields' => self::FIELDS), MONTH_IN_SECONDS);
        if (is_wp_error($data)) {
            return $data;
        }
        return array_map(array(__CLASS__, 'normalize'), $data);
    }

    public static function distance($a, $b) {
        $r    = 6371;
        $dlat = deg2rad($b['lat'] - $a['lat']);
        $dlon = deg2rad($b['lon'] - $a['lon']);
        $h    = sin($dlat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dlon / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($h)));
    }

    /**
     * Communes autour d'une ville.
     *
     * @param array $center     Commune normalisée.
     * @param float $radius     Rayon en km.
     * @param int   $min_pop    Population minimale.
     * @param array $extra_depts Départements voisins à inclure.
     */
    public static function nearby(array $center, $radius = 20, $min_pop = 0, array $extra_depts = array(), $limit = 200) {
        $depts = array_unique(array_filter(array_merge(array($center['dept_code']), $extra_depts)));
        $out   = array();
        foreach ($depts as $dept) {
            $list = self::department($dept);
            if (is_wp_error($list)) {
                continue;
            }
            foreach ($list as $c) {
                if ($c['code'] === $center['code'] || $c['population'] < $min_pop || !$c['lat']) {
                    continue;
                }
                $d = self::distance($center, $c);
                if ($d <= $radius) {
                    $c['distance'] = round($d, 1);
                    $out[]         = $c;
                }
            }
        }
        usort($out, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        return array_slice($out, 0, $limit);
    }

    /** Quelques communes proches et significatives, pour la personnalisation du texte. */
    public static function neighbours_for_content(array $city, $count = 6) {
        $list = self::nearby($city, 25, 800, array(), 60);
        if (is_wp_error($list) || empty($list)) {
            return array();
        }
        // Mélange distance / taille pour citer des communes que les habitants connaissent.
        usort($list, function ($a, $b) {
            $sa = $a['distance'] - log(max(1, $a['population']));
            $sb = $b['distance'] - log(max(1, $b['population']));
            return $sa <=> $sb;
        });
        return array_slice($list, 0, $count);
    }
}
