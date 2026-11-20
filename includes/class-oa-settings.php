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
            'accent_color'     => '#ff7a1a',
            'sticky_cta'       => 1,
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
            } elseif (in_array($key, array('usps', 'extra_rules', 'certifications', 'address'), true)) {
                $clean[$key] = sanitize_textarea_field($value);
            } elseif (in_array($key, array('quote_url', 'ai_base_url'), true)) {
                $clean[$key] = esc_url_raw(trim($value));
            } elseif (in_array($key, array('brand_color', 'accent_color'), true)) {
                $clean[$key] = sanitize_hex_color($value) ?: $default;
            } elseif ('email' === $key) {
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
        if (!in_array($clean['ai_provider'], array('openai', 'compatible', 'manual'), true)) {
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
        return array('auto_retry', 'background', 'schema', 'external_links', 'nearby_links', 'sticky_cta');
    }

    /** Clé API masquée pour l'affichage. */
    public static function masked_key() {
        $key = (string) self::get('ai_key');
        if (strlen($key) < 12) {
            return $key ? '••••' : '';
        }
        return substr($key, 0, 5) . str_repeat('•', 12) . substr($key, -4);
    }
}
