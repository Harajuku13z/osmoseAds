<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Réglages centralisés (une seule option sérialisée).
 */
class OA_Settings {

    const OPTION = 'oa_settings';

    private static $cache = null;

    public static function defaults() {
        return array(
            // Entreprise
            'company_name'     => get_bloginfo('name'),
            'phone'            => '',
            'email'            => get_option('admin_email'),
            'address'          => '',
            'base_city'        => '',
            'postal_code'      => '',
            'years'            => '',
            'certifications'   => 'Garantie décennale, Devis gratuit',
            'usps'             => '',
            'quote_url'        => '',
            'logo_id'          => 0,
            'rating_value'     => '',
            'rating_count'     => '',

            // IA
            'ai_provider'      => 'openai',   // openai | compatible | manual
            'ai_key'           => '',
            'ai_model'         => 'gpt-5-mini',
            'ai_base_url'      => 'https://api.openai.com/v1',
            'ai_reasoning'     => 'low',
            'ai_timeout'       => 180,
            'chatgpt_client_id'     => '',
            'chatgpt_client_secret' => '',
            'chatgpt_model'         => '',

            // Contenu
            'word_target'      => 1200,
            'tone'             => 'expert, rassurant et concret',
            'person'           => 'nous',
            'extra_rules'      => '',
            'min_score'        => 80,
            'max_similarity'   => 30,
            'auto_retry'       => 1,

            // Publication
            'post_status'      => 'publish',
            'url_mode'         => 'flat',     // flat | silo
            'drip_per_day'     => 0,
            'workers'          => 3,
            'background'       => 1,
            'parent_page'      => 0,

            // SEO
            'title_suffix'     => '',
            'schema'           => 1,
            'external_links'   => 1,
            'nearby_links'     => 1,

            // Design
            'brand_color'      => '#1f6feb',
            'brand_color2'     => '#0b4fc4',
            'button_ink'       => '#ffffff',
            'accent_color'     => '#ff7a1a',
            'accent_ink'       => '#ffffff',
            'ink_color'        => '#0f172a',
            'heading_font'     => '',
            'body_font'        => '',
            'heading_upper'    => 0,
            'radius'           => 14,
            'quote_label'      => 'Devis gratuit',
            'sticky_cta'       => 1,
            'style_posts'      => 0,
            'text_photos'      => 0,
            'services_list'    => '',
            'services_title'   => 'Nos services',
            'realisations_list'=> '',
            'realisations_url' => '',
            'realisations_title' => 'Nos réalisations',
            'post_tagline'     => 'Des conseils d\'artisans et un devis gratuit, sur place.',
            'reviews_list'     => '',
            'reviews_title'    => 'Avis clients',
            'reviews_url'      => '',
            'reviews_count'    => '',
            'sidebar'          => 1,
            'lead_form'        => 1,
            'lead_email'       => '',
        );
    }

    public static function all() {
        if (null === self::$cache) {
            $saved       = get_option(self::OPTION, array());
            self::$cache = wp_parse_args(is_array($saved) ? $saved : array(), self::defaults());
        }
        return self::$cache;
    }

    public static function get($key, $default = null) {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @param array $input
     * @param bool  $from_form  true = formulaire complet : une case absente vaut « décochée ».
     */
    public static function save(array $input, $from_form = false) {
        $defaults = self::defaults();
        $current  = self::all();
        $clean    = $current;

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $input)) {
                if ($from_form && in_array($key, self::checkboxes(), true)) {
                    $clean[$key] = 0;
                }
                continue;
            }
            $value = wp_unslash($input[$key]);
            if (is_int($default)) {
                $clean[$key] = (int) $value;
            } elseif (in_array($key, array('usps', 'extra_rules', 'certifications', 'address', 'services_list', 'realisations_list', 'reviews_list'), true)) {
                $clean[$key] = sanitize_textarea_field($value);
            } elseif (in_array($key, array('quote_url', 'ai_base_url', 'realisations_url', 'reviews_url'), true)) {
                $clean[$key] = esc_url_raw(trim($value));
            } elseif (in_array($key, array('brand_color', 'brand_color2', 'button_ink', 'accent_color', 'accent_ink', 'ink_color'), true)) {
                $clean[$key] = sanitize_hex_color($value) ?: $default;
            } elseif ('email' === $key || 'lead_email' === $key) {
                $clean[$key] = sanitize_email($value);
            } else {
                $clean[$key] = sanitize_text_field($value);
            }
        }

        // Garde-fous.
        $clean['word_target']    = max(600, min(3000, (int) $clean['word_target']));
        $clean['min_score']      = max(0, min(100, (int) $clean['min_score']));
        $clean['max_similarity'] = max(5, min(90, (int) $clean['max_similarity']));
        $clean['workers']        = max(1, min(8, (int) $clean['workers']));
        $clean['ai_timeout']     = max(30, min(600, (int) $clean['ai_timeout']));
        $clean['drip_per_day']   = max(0, min(500, (int) $clean['drip_per_day']));
        $clean['radius']         = max(0, min(32, (int) $clean['radius']));
        foreach (array('heading_font', 'body_font') as $f) {
            $clean[$f] = trim(preg_replace('/[^\p{L}0-9 \-]/u', '', (string) $clean[$f]));
        }
        if (!in_array($clean['ai_provider'], array('openai', 'compatible', 'manual', 'chatgpt'), true)) {
            $clean['ai_provider'] = 'openai';
        }
        if (!in_array($clean['post_status'], array('publish', 'draft', 'pending'), true)) {
            $clean['post_status'] = 'publish';
        }
        if (!in_array($clean['url_mode'], array('flat', 'silo'), true)) {
            $clean['url_mode'] = 'flat';
        }

        update_option(self::OPTION, $clean, false);
        self::$cache = null;
        return $clean;
    }

    public static function checkboxes() {
        return array('auto_retry', 'background', 'schema', 'external_links', 'nearby_links', 'sticky_cta', 'heading_upper', 'style_posts', 'text_photos', 'sidebar', 'lead_form');
    }

    /** Clé API masquée pour l'affichage. */
    public static function masked_key($field = 'ai_key') {
        $key = (string) self::get($field);
        if (strlen($key) < 12) {
            return $key ? '••••' : '';
        }
        return substr($key, 0, 5) . str_repeat('•', 12) . substr($key, -4);
    }

    /**
     * Liste « Titre | URL | Image | Texte » (une entrée par ligne) → tableau.
     * Sert aux blocs Services et Réalisations des pages.
     */
    /** Mots « utiles » d'un texte (minuscules, sans accents, sans mots vides). */
    public static function words($text) {
        $text = strtolower(remove_accents((string) $text));
        $stop = array('de', 'du', 'des', 'la', 'le', 'les', 'et', 'a', 'au', 'aux', 'en', 'sur', 'pour', 'par', 'un', 'une', 'pres', 'chalon', 'saone', 'avant', 'apres', 'votre', 'vos', 'nos', 'd', 'l');
        $out  = array();
        foreach (preg_split('/[^a-z0-9]+/', $text) as $w) {
            if (strlen($w) > 2 && !in_array($w, $stop, true)) {
                $out[] = substr($w, 0, 6); // racine grossière : démoussage / démousser, toiture / toitures…
            }
        }
        return array_values(array_unique($out));
    }

    /** Avis « Nom | Texte | mots-clés » (une ligne par avis). */
    public static function reviews() {
        $out = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) self::get('reviews_list')) as $line) {
            $p = array_map('trim', explode('|', $line));
            if (count($p) < 2 || '' === $p[0] || '' === $p[1]) {
                continue;
            }
            $out[] = array('name' => $p[0], 'text' => $p[1], 'tags' => isset($p[2]) ? $p[2] : '');
        }
        return $out;
    }

    public static function cards($key) {
        $out = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) self::get($key)) as $line) {
            $p = array_map('trim', explode('|', $line));
            if ('' === $p[0]) {
                continue;
            }
            $imgs  = isset($p[2]) ? array_values(array_filter(array_map('trim', explode(',', $p[2])))) : array();
            $out[] = array(
                'title' => $p[0],
                'url'   => isset($p[1]) ? $p[1] : '',
                'img'   => $imgs ? $imgs[0] : '',
                'imgs'  => $imgs,
                'text'  => isset($p[3]) ? $p[3] : '',
                'video' => isset($p[4]) ? $p[4] : '',
                'tags'  => isset($p[5]) ? $p[5] : '',
            );
        }
        return $out;
    }
}
