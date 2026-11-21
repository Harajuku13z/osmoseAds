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
        add_shortcode('oa_services', array(__CLASS__, 'sc_services'));
        add_shortcode('oa_realisations', array(__CLASS__, 'sc_realisations'));
        add_shortcode('oa_cta_band', array(__CLASS__, 'sc_cta_band'));
        add_shortcode('oa_avis', array(__CLASS__, 'sc_avis'));
        add_shortcode('oa_sidebar', array(__CLASS__, 'sc_sidebar'));
        add_action('init', array(__CLASS__, 'register_leads'));
        add_action('admin_post_oa_lead', array(__CLASS__, 'lead'));
        add_action('admin_post_nopriv_oa_lead', array(__CLASS__, 'lead'));
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
        return '<div class="oa-page oa-post">' . $hero . '<div class="oa-layout"><div class="oa-main"><div class="oa-article">' . $content . '</div>' . self::sc_cta_band() . self::sc_services() . self::sc_realisations() . self::sc_avis() . $cta . '</div>' . self::sc_sidebar() . '</div></div>';
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

    /** Grille de cartes (services / réalisations). */
    private static function cards_html($items, $class, $more = '') {
        $out = '<div class="oa-cards ' . esc_attr($class) . '">';
        foreach ($items as $it) {
            $tag  = $it['url'] ? 'a' : 'div';
            $href = $it['url'] ? ' href="' . esc_url($it['url']) . '"' : '';
            $out .= '<' . $tag . ' class="oa-card"' . $href . '>';
            if ($it['img']) {
                $out .= '<span class="oa-card__img"><img src="' . esc_url($it['img']) . '" alt="' . esc_attr($it['title']) . '" loading="lazy" decoding="async"></span>';
            }
            $out .= '<span class="oa-card__body"><strong class="oa-card__title">' . esc_html($it['title']) . '</strong>';
            if ($it['text']) {
                $out .= '<span class="oa-card__text">' . esc_html($it['text']) . '</span>';
            }
            if ($it['url']) {
                $out .= '<span class="oa-card__more">' . esc_html($more ?: 'En savoir plus') . ' →</span>';
            }
            $out .= '</span></' . $tag . '>';
        }
        return $out . '</div>';
    }

    /** Pages sœurs (même métier, même ville) d'une page locale. */
    private static function siblings($post_id) {
        $city  = get_post_meta($post_id, '_oa_city_code', true);
        $trade = get_post_meta($post_id, '_oa_trade', true);
        if (!$city || !$trade) {
            return array();
        }
        return get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'post__not_in'   => array($post_id),
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array('key' => '_oa_city_code', 'value' => $city),
                array('key' => '_oa_trade', 'value' => $trade),
            ),
        ));
    }

    /**
     * Bloc services. Sur une page locale : les sous-services du métier dans la ville
     * (autres pages générées). Ailleurs : la liste des services des réglages.
     */
    public static function sc_services() {
        $post_id  = get_the_ID();
        $siblings = ($post_id && get_post_meta($post_id, '_oa_generated', true)) ? self::siblings($post_id) : array();
        if ($siblings) {
            $city  = get_post_meta($post_id, '_oa_city', true);
            $items = array();
            foreach ($siblings as $p) {
                $thumb   = get_the_post_thumbnail_url($p, 'medium_large');
                $text    = wp_strip_all_tags(get_the_excerpt($p));
                $text    = mb_strlen($text) > 105 ? rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, 105), ' ')), ' ,;:') . '…' : $text;
                $items[] = array(
                    'title' => OA_Prompts::ucfirst(get_post_meta($p->ID, '_oa_keyword_label', true) ?: $p->post_title),
                    'url'   => get_permalink($p),
                    'img'   => $thumb ?: '',
                    'text'  => $text,
                );
            }
            $where = is_array($city) && !empty($city['name']) ? ' ' . OA_Trades::preposition_city($city['name']) : '';
            return '<section class="oa-block oa-services alignwide"><p class="oa-eyebrow">' . esc_html('Nos prestations') . '</p><h2>' . esc_html('Nos autres services' . $where) . '</h2>'
                . self::cards_html($items, 'oa-cards--services', 'Découvrir') . '</section>';
        }
        $items = OA_Settings::cards('services_list');
        if (!$items) {
            return '';
        }
        return '<section class="oa-block oa-services alignwide"><p class="oa-eyebrow">' . esc_html('Ce que nous faisons') . '</p><h2>' . esc_html(OA_Settings::get('services_title') ?: 'Nos services') . '</h2>'
            . self::cards_html($items, 'oa-cards--services', 'Découvrir') . '</section>';
    }

    /** Les 2 ou 3 réalisations les plus proches du sujet de la page. */
    private static function pick_realisations($items, $max = 3) {
        $post_id = get_the_ID();
        $topic   = get_post_meta($post_id, '_oa_keyword_label', true) ?: get_the_title($post_id);
        $main    = OA_Settings::words($topic);
        $scored  = array();
        foreach ($items as $k => $it) {
            $hay   = OA_Settings::words($it['title'] . ' ' . $it['tags']);
            $score = 3 * count(array_intersect($main, $hay));
            if ($score > 0) {
                $score += $it['video'] ? 1 : 0; // une vidéo avant/après convertit mieux
                $scored[] = array($score, -$k, $it);
            }
        }
        rsort($scored);
        $picked = array_slice(wp_list_pluck($scored, 2), 0, $max);
        if (count($picked) < 2) {
            // Pas assez de chantiers proches : on complète avec les plus récents.
            foreach ($items as $it) {
                if (count($picked) >= 2) {
                    break;
                }
                if (!in_array($it, $picked, true)) {
                    $picked[] = $it;
                }
            }
        }
        return $picked;
    }

    public static function sc_realisations() {
        $items = OA_Settings::cards('realisations_list');
        if (!$items) {
            return '';
        }
        $out = '';
        foreach (self::pick_realisations($items) as $it) {
            $url  = $it['url'] ? esc_url($it['url']) : '';
            $out .= '<article class="oa-rcard"><h3 class="oa-rcard__title">' . ($url ? '<a href="' . $url . '">' . esc_html($it['title']) . '</a>' : esc_html($it['title'])) . '</h3>';
            if ($it['text']) {
                $out .= '<p class="oa-rcard__text">' . esc_html($it['text']) . '</p>';
            }
            if ($it['video']) {
                $out .= '<div class="oa-rcard__video"><span class="oa-rcard__badge">Vidéo</span><video muted loop playsinline preload="none" data-src="' . esc_url($it['video']) . '"' . ($it['img'] ? ' poster="' . esc_url($it['img']) . '"' : '') . ' aria-label="' . esc_attr($it['title']) . '"></video></div>'
                    . ($url ? '<p class="oa-rcard__cta"><a class="oa-btn oa-btn--quote" href="' . $url . '">Voir le projet →</a></p>' : '');
            } elseif ($it['imgs']) {
                $out .= '<div class="oa-rcard__thumbs">';
                foreach (array_slice($it['imgs'], 0, 3) as $i => $src) {
                    $out .= '<a class="oa-zoom oa-rcard__thumb" href="' . esc_url($src) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($it['title'] . ' – photo ' . ($i + 1)) . '" loading="lazy" decoding="async"></a>';
                }
                if ($url) {
                    $out .= '<a class="oa-rcard__more" href="' . $url . '">Voir plus</a>';
                }
                $out .= '</div>';
            }
            $out .= '</article>';
        }
        $all = OA_Settings::get('realisations_url');
        return '<section class="oa-block oa-realisations alignwide"><p class="oa-eyebrow">' . esc_html('Nos chantiers') . '</p><h2>' . esc_html(OA_Settings::get('realisations_title') ?: 'Nos réalisations') . '</h2>'
            . '<div class="oa-rcards">' . $out . '</div>'
            . ($all ? '<p class="oa-block__more"><a class="oa-btn oa-btn--ghost" href="' . esc_url($all) . '">' . esc_html('Voir toutes nos réalisations') . '</a></p>' : '') . '</section>';
    }

    /** Bandeau d'appel à l'action en milieu de page. */
    public static function sc_cta_band($atts = array()) {
        $atts = shortcode_atts(array('title' => 'Un devis gratuit en 48 h', 'text' => 'Diagnostic sur place, devis détaillé, sans engagement.'), $atts);
        $cta  = self::sc_cta();
        if (!$cta) {
            return '';
        }
        return '<aside class="oa-band alignwide"><div class="oa-band__txt"><strong>' . esc_html($atts['title']) . '</strong><span>' . esc_html($atts['text']) . '</span></div>' . $cta . '</aside>';
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
        if ($siblings && false === strpos((string) get_post_field('post_content', $post_id), '[oa_services]')) {
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

    /* ------------------------------------------------------------------ Avis */

    private static $shown_reviews = array();

    /** Les avis les plus proches du sujet de la page (mots communs), puis les autres. */
    private static function pick_reviews($max, $exclude = array()) {
        $items = OA_Settings::reviews();
        if (!$items) {
            return array();
        }
        $post_id = get_the_ID();
        $main    = OA_Settings::words(get_post_meta($post_id, '_oa_keyword_label', true) ?: get_the_title($post_id));
        $n       = count($items);
        $scored  = array();
        foreach ($items as $k => $it) {
            if (in_array($k, $exclude, true)) {
                continue;
            }
            $len   = mb_strlen($it['text']);
            $score = 3 * count(array_intersect($main, OA_Settings::words($it['text'] . ' ' . $it['tags'])));
            $score += ($len >= 70 && $len <= 420) ? 1 : 0;
            // Départage stable mais différent d'une page à l'autre.
            $scored[] = array($score, (($k + $post_id * 7) % $n) * -1, $k);
        }
        rsort($scored);
        $keys = array_slice(wp_list_pluck($scored, 2), 0, $max);
        $out  = array();
        foreach ($keys as $k) {
            $out[$k] = $items[$k];
        }
        return $out;
    }

    private static function google_g() {
        return '<svg class="oa-g" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.09A6.6 6.6 0 0 1 5.49 12c0-.73.13-1.43.35-2.09V7.07H2.18A11 11 0 0 0 1 12c0 1.78.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/></svg>';
    }

    /** Note globale : G · 5,0 ★★★★★ · +100 avis Google. */
    private static function rating_badge() {
        $count = trim((string) OA_Settings::get('reviews_count'));
        $url   = OA_Settings::get('reviews_url');
        $txt   = self::google_g() . '<strong>5,0</strong><span class="oa-stars" aria-label="5 étoiles sur 5">★★★★★</span><span>' . esc_html(($count ? $count . ' ' : '') . 'avis Google') . '</span>';
        return $url ? '<a class="oa-rating" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . $txt . '</a>' : '<p class="oa-rating">' . $txt . '</p>';
    }

    private static function review_html($r, $limit = 300) {
        $text = $r['text'];
        if (mb_strlen($text) > $limit) {
            $text = rtrim(mb_substr($text, 0, $limit - 1), " ,.;") . '…';
        }
        $initial = mb_strtoupper(mb_substr($r['name'], 0, 1));
        return '<figure class="oa-review"><div class="oa-review__head"><span class="oa-review__avatar" aria-hidden="true">' . esc_html($initial) . '</span><figcaption><strong>' . esc_html($r['name']) . '</strong><span class="oa-stars" aria-label="5 étoiles sur 5">★★★★★</span></figcaption>' . self::google_g() . '</div>'
            . '<blockquote>' . esc_html($text) . '</blockquote></figure>';
    }

    /** Bloc « Avis clients » : 3 avis proches du sujet + note Google. */
    public static function sc_avis() {
        $picked = self::pick_reviews(3);
        if (!$picked) {
            return '';
        }
        self::$shown_reviews = array_keys($picked);
        $out = '';
        foreach ($picked as $r) {
            $out .= self::review_html($r);
        }
        $url = OA_Settings::get('reviews_url');
        return '<section class="oa-block oa-avis alignwide" id="avis"><div class="oa-avis__top"><div><p class="oa-eyebrow">' . esc_html('Témoignages') . '</p><h2>' . esc_html(OA_Settings::get('reviews_title') ?: 'Avis clients') . '</h2></div>' . self::rating_badge() . '</div>'
            . '<div class="oa-reviews">' . $out . '</div>'
            . ($url ? '<p class="oa-block__more"><a class="oa-btn oa-btn--ghost" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html('Lire tous les avis sur Google') . '</a></p>' : '') . '</section>';
    }

    /* --------------------------------------------------- Colonne de droite */

    public static function sc_sidebar() {
        $s = OA_Settings::all();
        if (!$s['sidebar'] && !$s['lead_form']) {
            return '';
        }
        $out = '';
        if ($s['sidebar']) {
            $out .= '<div class="oa-side__card oa-side__cta"><p class="oa-side__title">' . esc_html('Devis gratuit en 48 h') . '</p><p>' . esc_html('Diagnostic sur place et devis détaillé, sans engagement.') . '</p>' . self::sc_cta() . self::sc_trust() . '</div>';
            $side = self::pick_reviews(2, self::$shown_reviews);
            if ($side) {
                $out .= '<div class="oa-side__card oa-side__avis">' . self::rating_badge();
                foreach ($side as $r) {
                    $out .= self::review_html($r, 190);
                }
                $out .= '</div>';
            }
        }
        if ($s['lead_form']) {
            $out .= self::lead_form();
        }
        return '<aside class="oa-side' . ($s['sidebar'] ? '' : ' oa-side--form-only') . '" aria-label="' . esc_attr('Contact et devis') . '">' . $out . '</aside>';
    }

    private static function lead_form() {
        $post_id = get_the_ID();
        $topic   = get_post_meta($post_id, '_oa_keyword_label', true);
        $state   = isset($_GET['oa_lead']) ? sanitize_key(wp_unslash($_GET['oa_lead'])) : '';
        $head    = '<p class="oa-side__title">' . esc_html('Demandez votre devis') . '</p>';
        if ('ok' === $state) {
            return '<div class="oa-side__card oa-side__form" id="oa-devis">' . $head . '<p class="oa-form__ok" role="status">' . esc_html('Merci ! Votre demande est bien envoyée. Nous vous rappelons très vite.') . '</p></div>';
        }
        $opts = array();
        if ($topic) {
            $opts[] = $topic;
        }
        foreach (OA_Settings::cards('services_list') as $c) {
            $opts[] = $c['title'];
        }
        $opts[] = 'Autre';
        $select = '';
        foreach (array_unique($opts) as $o) {
            $select .= '<option>' . esc_html($o) . '</option>';
        }
        $f = function ($name, $label, $type, $extra = '') {
            return '<label class="oa-form__f"><span>' . esc_html($label) . '</span><input type="' . $type . '" name="' . $name . '"' . $extra . '></label>';
        };
        return '<div class="oa-side__card oa-side__form" id="oa-devis">' . $head . '<p>' . esc_html('Réponse sous 48 h, sans engagement.') . '</p>'
            . ('err' === $state ? '<p class="oa-form__err" role="alert">' . esc_html('Merci d\'indiquer votre nom et un téléphone ou un e-mail valide.') . '</p>' : '')
            . '<form class="oa-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
            . '<input type="hidden" name="action" value="oa_lead"><input type="hidden" name="oa_page" value="' . (int) $post_id . '"><input type="hidden" name="oa_js" value="">'
            . '<label class="oa-form__hp" aria-hidden="true">Site web <input type="text" name="oa_site" tabindex="-1" autocomplete="off"></label>'
            . $f('oa_nom', 'Nom et prénom *', 'text', ' required autocomplete="name"')
            . $f('oa_tel', 'Téléphone *', 'tel', ' required autocomplete="tel" inputmode="tel"')
            . $f('oa_email', 'E-mail', 'email', ' autocomplete="email"')
            . $f('oa_cp', 'Code postal', 'text', ' inputmode="numeric" maxlength="5" autocomplete="postal-code"')
            . '<label class="oa-form__f"><span>' . esc_html('Type de travaux') . '</span><select name="oa_service">' . $select . '</select></label>'
            . '<label class="oa-form__f"><span>' . esc_html('Votre projet') . '</span><textarea name="oa_msg" rows="3" placeholder="' . esc_attr('Surface, urgence, questions…') . '"></textarea></label>'
            . '<button type="submit" class="oa-btn oa-btn--quote">' . esc_html('Recevoir mon devis gratuit') . '</button>'
            . '<p class="oa-form__legal">' . esc_html('Vos coordonnées servent uniquement à vous recontacter pour ce projet.') . '</p></form></div>';
    }

    /** Demandes de devis : type de contenu privé, visible dans l'admin (Osmose Ads → Demandes). */
    public static function register_leads() {
        register_post_type('oa_lead', array(
            'labels'          => array('name' => 'Demandes de devis', 'singular_name' => 'Demande de devis', 'menu_name' => 'Demandes de devis'),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'oa-dashboard',
            'supports'        => array('title', 'editor'),
            'capability_type' => 'post',
            'capabilities'    => array('create_posts' => 'do_not_allow'),
            'map_meta_cap'    => true,
        ));
    }

    public static function lead() {
        $post_id = isset($_POST['oa_page']) ? absint($_POST['oa_page']) : 0;
        $back    = $post_id ? get_permalink($post_id) : home_url('/');
        $back    = $back ?: home_url('/');
        $get     = function ($k) {
            return isset($_POST[$k]) ? trim(sanitize_text_field(wp_unslash($_POST[$k]))) : '';
        };
        // Robots : champ piège rempli ou JavaScript absent → on fait semblant d'accepter.
        if ('' !== $get('oa_site') || '1' !== $get('oa_js')) {
            wp_safe_redirect(add_query_arg('oa_lead', 'ok', $back) . '#oa-devis');
            exit;
        }
        $name  = $get('oa_nom');
        $tel   = $get('oa_tel');
        $email = sanitize_email($get('oa_email'));
        if ('' === $name || (strlen(preg_replace('/\D/', '', $tel)) < 9 && !is_email($email))) {
            wp_safe_redirect(add_query_arg('oa_lead', 'err', $back) . '#oa-devis');
            exit;
        }
        $ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $key = 'oa_lead_' . md5($ip . wp_salt());
        $hit = (int) get_transient($key);
        if ($hit >= 5) {
            wp_safe_redirect(add_query_arg('oa_lead', 'ok', $back) . '#oa-devis');
            exit;
        }
        set_transient($key, $hit + 1, HOUR_IN_SECONDS);

        $msg   = isset($_POST['oa_msg']) ? sanitize_textarea_field(wp_unslash($_POST['oa_msg'])) : '';
        $lines = array(
            'Nom : ' . $name,
            'Téléphone : ' . $tel,
            'E-mail : ' . ($email ?: '—'),
            'Code postal : ' . ($get('oa_cp') ?: '—'),
            'Travaux : ' . ($get('oa_service') ?: '—'),
            'Page : ' . ($post_id ? get_the_title($post_id) . ' — ' . get_permalink($post_id) : '—'),
            '',
            'Projet :',
            $msg ?: '—',
        );
        $body = implode("\n", $lines);
        wp_insert_post(array(
            'post_type'    => 'oa_lead',
            'post_status'  => 'private',
            'post_title'   => $name . ' — ' . ($get('oa_service') ?: 'Demande de devis'),
            'post_content' => $body,
        ));
        $to      = OA_Settings::get('lead_email') ?: OA_Settings::get('email') ?: get_option('admin_email');
        $headers = $email ? array('Reply-To: ' . $name . ' <' . $email . '>') : array();
        wp_mail($to, 'Nouvelle demande de devis : ' . $name . ($get('oa_cp') ? ' (' . $get('oa_cp') . ')' : ''), $body, $headers);
        wp_safe_redirect(add_query_arg('oa_lead', 'ok', $back) . '#oa-devis');
        exit;
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
