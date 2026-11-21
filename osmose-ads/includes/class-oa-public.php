<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Front : styles, shortcodes dynamiques, maillage interne, barre d'appel mobile, suivi des clics.
 */
class OA_Public {

    /** Vrai pendant que le plugin construit le hero d'un article (l'image à la une y est autorisée). */
    private static $building = false;

    public static function hooks() {
        add_shortcode('oa_cta', array(__CLASS__, 'sc_cta'));
        add_shortcode('oa_trust', array(__CLASS__, 'sc_trust'));
        add_shortcode('oa_maillage', array(__CLASS__, 'sc_maillage'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('wp_footer', array(__CLASS__, 'sticky_bar'));
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_filter('post_thumbnail_html', array(__CLASS__, 'hide_duplicate_thumbnail'), 10, 2);
        add_filter('the_content', array(__CLASS__, 'wrap_post'), 20);
        add_action('wp_ajax_oa_track', array(__CLASS__, 'track'));
        add_action('wp_ajax_nopriv_oa_track', array(__CLASS__, 'track'));
        add_action('save_post_page', array(__CLASS__, 'bump_mesh'));
        add_action('deleted_post', array(__CLASS__, 'bump_mesh'));
    }

    private static function is_oa() {
        return (is_singular('page') && OA_Seo::is_oa_page()) || self::is_styled_post();
    }

    /** Article de blog habillé avec le design des pages locales (option « style_posts »). */
    private static function is_styled_post() {
        return is_singular('post') && OA_Settings::get('style_posts');
    }

    public static function body_class($classes) {
        if (self::is_oa()) {
            $classes[] = 'oa-local-page';
        }
        if (self::is_styled_post()) {
            $classes[] = 'oa-blog-post';
        }
        return $classes;
    }

    /** Habille le contenu d'un article : hero (image à la une), contenu stylé, bloc devis final. */
    public static function wrap_post($content) {
        if (is_admin() || !self::is_styled_post() || !in_the_loop() || !is_main_query() || get_the_ID() !== get_queried_object_id()) {
            return $content;
        }
        $s     = OA_Settings::all();
        self::$building = true;
        $img   = get_the_post_thumbnail(get_the_ID(), 'full', array('loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async'));
        self::$building = false;
        $cats  = get_the_category();
        $cats  = array_values(array_filter($cats, function ($c) {
            return (int) $c->term_id !== (int) get_option('default_category');
        }));
        $kick  = $cats ? '<p class="oa-hero__kicker">' . esc_html($cats[0]->name) . '</p>' : '';
        $hero  = '<div class="oa-hero alignwide' . ($img ? ' has-media' : '') . '">' . ($img ? '<figure class="oa-hero__media">' . $img . '</figure>' : '')
            . '<div class="oa-hero__inner">' . $kick . '<p class="oa-hero__tagline">' . esc_html($s['post_tagline']) . '</p>' . self::sc_cta() . self::sc_trust() . '</div></div>';
        $home  = $s['quote_url'] && 0 === strpos($s['quote_url'], home_url()) ? $s['quote_url'] : home_url('/');
        $cta   = '<section class="oa-cta alignwide"><h2>' . esc_html('Un projet ? Parlons-en') . '</h2><p>' . esc_html('Décrivez-nous vos travaux : nous venons faire le diagnostic chez vous et vous remettons un devis gratuit et détaillé.') . '</p>'
            . ($s['company_name'] ? '<p class="oa-cta__company">' . esc_html($s['company_name']) . esc_html(', votre artisan de confiance.') . '</p>' : '') . self::sc_cta() . '</section>';
        return '<div class="oa-page oa-post">' . $hero . '<div class="oa-article">' . $content . '</div>' . $cta . '</div>';
    }

    /**
     * Le hero de la page affiche déjà la photo principale : on évite que le thème
     * la répète au-dessus du titre (l'image reste l'image à la une pour l'OG / Google).
     */
    public static function hide_duplicate_thumbnail($html, $post_id) {
        if (!is_admin() && self::is_styled_post() && (int) $post_id === get_queried_object_id() && !self::$building) {
            return '';
        }
        if (is_admin() || !is_singular('page') || (int) $post_id !== get_queried_object_id() || !OA_Seo::is_oa_page($post_id)) {
            return $html;
        }
        $content = get_post_field('post_content', $post_id);
        return false !== strpos($content, 'oa-hero__media') ? '' : $html;
    }

    public static function assets() {
        if (!self::is_oa()) {
            return;
        }
        $s = OA_Settings::all();
        // Polices Google éventuelles (titres / texte).
        $families = array();
        foreach (array('heading_font' => '400;700', 'body_font' => '400;500;700;800') as $key => $weights) {
            if ($s[$key] && !in_array($s[$key], array('inherit', 'system-ui'), true)) {
                $families[] = 'family=' . str_replace(' ', '+', $s[$key]) . ':wght@' . $weights;
            }
        }
        $deps = array();
        if ($families) {
            wp_enqueue_style('oa-fonts', 'https://fonts.googleapis.com/css2?' . implode('&', array_unique($families)) . '&display=swap', array(), null);
            $deps[] = 'oa-fonts';
        }
        wp_enqueue_style('oa-front', OA_URL . 'public/front.css', $deps, OA_VERSION);
        wp_add_inline_style('oa-front', self::css_vars($s));
        wp_enqueue_script('oa-front', OA_URL . 'public/front.js', array(), OA_VERSION, true);
        wp_localize_script('oa-front', 'OA_FRONT', array(
            'ajax' => admin_url('admin-ajax.php'),
            'post' => get_queried_object_id(),
        ));
    }

    /** Variables CSS issues des réglages « Design ». */
    public static function css_vars(array $s) {
        $font = function ($name, $fallback) {
            return $name ? "'" . esc_attr($name) . "', " . $fallback : 'inherit';
        };
        $vars = array(
            '--oa-brand'       => $s['brand_color'],
            '--oa-brand-2'     => $s['brand_color2'],
            '--oa-btn-ink'     => $s['button_ink'],
            '--oa-accent'      => $s['accent_color'],
            '--oa-accent-ink'  => $s['accent_ink'],
            '--oa-ink'         => $s['ink_color'],
            '--oa-radius'      => (int) $s['radius'] . 'px',
            '--oa-head-font'   => $font($s['heading_font'], 'system-ui, sans-serif'),
            '--oa-body-font'   => $font($s['body_font'], 'system-ui, sans-serif'),
            '--oa-head-tt'     => $s['heading_upper'] ? 'uppercase' : 'none',
            '--oa-head-weight' => $s['heading_upper'] ? '400' : '800',
        );
        $css = '';
        foreach ($vars as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        return '.oa-page,.oa-mesh,.oa-sticky,body.oa-local-page{' . $css . '}';
    }

    private static function tel_href($phone) {
        return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
    }

    public static function sc_cta() {
        $s    = OA_Settings::all();
        $out  = '';
        if ($s['phone']) {
            $out .= '<a class="oa-btn oa-btn--call" data-oa-track="call" href="' . esc_attr(self::tel_href($s['phone'])) . '"><span aria-hidden="true">✆</span> ' . esc_html($s['phone']) . '</a>';
        }
        $quote = $s['quote_url'] ?: ($s['email'] ? 'mailto:' . $s['email'] : '');
        if ($quote) {
            $out .= '<a class="oa-btn oa-btn--quote" data-oa-track="quote" href="' . esc_url($quote, array('http', 'https', 'mailto')) . '">' . esc_html($s['quote_label'] ?: 'Devis gratuit') . '</a>';
        }
        return $out ? '<div class="oa-actions">' . $out . '</div>' : '';
    }

    public static function sc_trust() {
        $items = array_filter(array_map('trim', preg_split('/[\n,]+/', (string) OA_Settings::get('certifications'))));
        $years = OA_Settings::get('years');
        if ($years) {
            array_unshift($items, $years . ' ans d\'expérience');
        }
        if (!$items) {
            return '';
        }
        $out = '<ul class="oa-trust">';
        foreach (array_slice($items, 0, 5) as $it) {
            $out .= '<li><span aria-hidden="true">✓</span> ' . esc_html($it) . '</li>';
        }
        return $out . '</ul>';
    }

    public static function bump_mesh() {
        update_option('oa_mesh_ver', time(), false);
    }

    /** Maillage interne : silo ville (pages sœurs) + même service dans les villes voisines. */
    public static function sc_maillage() {
        $post_id = get_the_ID();
        if (!$post_id || !get_post_meta($post_id, '_oa_generated', true)) {
            return '';
        }
        $cache_key = 'oa_mesh_' . $post_id . '_' . get_option('oa_mesh_ver', 0);
        $cached    = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $city_code = get_post_meta($post_id, '_oa_city_code', true);
        $city      = get_post_meta($post_id, '_oa_city', true);
        $trade     = get_post_meta($post_id, '_oa_trade', true);
        $kw        = get_post_meta($post_id, '_oa_keyword', true);
        $label     = get_post_meta($post_id, '_oa_keyword_label', true);

        $siblings = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'post__not_in'   => array($post_id),
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array('key' => '_oa_city_code', 'value' => $city_code),
                array('key' => '_oa_trade', 'value' => $trade),
            ),
        ));

        $html = '';
        if ($siblings) {
            $html .= '<nav class="oa-mesh__block" aria-label="Autres prestations"><h2 class="oa-mesh__title">Nos autres prestations ' . esc_html(OA_Trades::preposition_city($city['name'])) . '</h2><ul class="oa-mesh__list">';
            foreach ($siblings as $p) {
                $html .= '<li><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(get_post_meta($p->ID, '_oa_keyword_label', true) ?: $p->post_title) . '</a></li>';
            }
            $html .= '</ul></nav>';
        }

        if (OA_Settings::get('nearby_links') && is_array($city) && !empty($city['lat'])) {
            $same = get_posts(array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => 300,
                'post__not_in'   => array($post_id),
                'fields'         => 'ids',
                'meta_query'     => array(
                    array('key' => '_oa_keyword', 'value' => $kw),
                    array('key' => '_oa_trade', 'value' => $trade),
                ),
            ));
            $near = array();
            foreach ($same as $id) {
                $c = get_post_meta($id, '_oa_city', true);
                if (is_array($c) && !empty($c['lat'])) {
                    $near[$id] = OA_Geo::distance($city, $c);
                }
            }
            asort($near);
            $near = array_slice($near, 0, 8, true);
            if ($near) {
                $html .= '<nav class="oa-mesh__block" aria-label="Villes voisines"><h2 class="oa-mesh__title">' . esc_html($label) . ' dans les communes voisines</h2><ul class="oa-mesh__list oa-mesh__list--cities">';
                foreach ($near as $id => $dist) {
                    $html .= '<li><a href="' . esc_url(get_permalink($id)) . '">' . esc_html($label . ' ' . OA_Trades::preposition_city(get_post_meta($id, '_oa_city_name', true))) . '</a></li>';
                }
                $html .= '</ul></nav>';
            }
        }

        $html = $html ? '<div class="oa-mesh">' . $html . '</div>' : '';
        set_transient($cache_key, $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }

    public static function sticky_bar() {
        if (!self::is_oa() || !OA_Settings::get('sticky_cta')) {
            return;
        }
        $s = OA_Settings::all();
        if (!$s['phone'] && !$s['quote_url']) {
            return;
        }
        echo '<div class="oa-sticky" role="complementary" aria-label="Contact rapide">';
        if ($s['phone']) {
            echo '<a class="oa-sticky__call" data-oa-track="call" href="' . esc_attr(self::tel_href($s['phone'])) . '">✆ Appeler</a>';
        }
        if ($s['quote_url']) {
            echo '<a class="oa-sticky__quote" data-oa-track="quote" href="' . esc_url($s['quote_url']) . '">' . esc_html($s['quote_label'] ?: 'Devis gratuit') . '</a>';
        }
        echo '</div>';
    }

    /** Clic sur « Appeler » / « Devis » (limité à 1 par minute par visiteur et par page). */
    public static function track() {
        $post_id = isset($_POST['post']) ? absint($_POST['post']) : 0;
        $kind    = isset($_POST['kind']) && 'quote' === $_POST['kind'] ? 'quote' : 'call';
        if (!$post_id || !get_post_meta($post_id, '_oa_generated', true)) {
            wp_send_json_error();
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        if (preg_match('/bot|crawl|spider|slurp|preview|headless/i', $ua)) {
            wp_send_json_success();
        }
        $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $key = 'oa_trk_' . md5($ip . $post_id . $kind . wp_salt());
        if (get_transient($key)) {
            wp_send_json_success();
        }
        set_transient($key, 1, MINUTE_IN_SECONDS);
        global $wpdb;
        $wpdb->insert(OA_Install::table('calls'), array('post_id' => $post_id, 'kind' => $kind, 'created_at' => current_time('mysql')));
        wp_send_json_success();
    }
}
