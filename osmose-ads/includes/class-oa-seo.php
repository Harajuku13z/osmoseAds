<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO : analyse type TruSEO (AIOSEO), écriture des métadonnées dans AIOSEO / Yoast / Rank Math,
 * schema.org et métadonnées de secours si aucun plugin SEO n'est actif.
 */
class OA_Seo {

    public static function hooks() {
        add_filter('aioseo_schema_output', array(__CLASS__, 'aioseo_schema'), 20);
        add_action('wp_head', array(__CLASS__, 'fallback_head'), 2);
        add_filter('pre_get_document_title', array(__CLASS__, 'fallback_title'), 20);
    }

    /* ------------------------------------------------------------------ */
    /* Analyse                                                             */
    /* ------------------------------------------------------------------ */

    private static $transitions = array(
        'ainsi', 'alors', 'aussi', 'autrement dit', 'bref', 'c\'est pourquoi', 'car', 'cependant', 'certes', 'd\'abord',
        'd\'ailleurs', 'de plus', 'de même', 'donc', 'du coup', 'en effet', 'en outre', 'en revanche', 'en résumé', 'en somme',
        'en particulier', 'enfin', 'ensuite', 'finalement', 'mais', 'néanmoins', 'notamment', 'or', 'par ailleurs',
        'par conséquent', 'par contre', 'par exemple', 'pourtant', 'puis', 'quant à', 'toutefois', 'tout d\'abord',
        'également', 'en conclusion', 'premièrement', 'deuxièmement', 'en fait', 'grâce à', 'afin de', 'pour cela',
        'dans ce cas', 'à cet effet', 'de ce fait', 'en général', 'surtout', 'avant tout', 'en pratique', 'concrètement',
    );

    public static function normalize($s) {
        $s = html_entity_decode(wp_strip_all_tags((string) $s), ENT_QUOTES, 'UTF-8');
        $s = str_replace(array('’', '‘', '–', '—', "\xc2\xa0"), array("'", "'", '-', '-', ' '), $s);
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
    }

    private static function count_phrase($haystack, $needle) {
        $needle = self::normalize($needle);
        if ('' === $needle) {
            return 0;
        }
        return substr_count(self::normalize($haystack), $needle);
    }

    public static function word_count($text) {
        return count(preg_split('/\s+/u', trim(self::normalize($text)), -1, PREG_SPLIT_NO_EMPTY));
    }

    private static function sentences($text) {
        $text  = trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES, 'UTF-8')));
        $parts = preg_split('/(?<=[.!?])\s+(?=[\p{Lu}«"])/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter(array_map('trim', $parts), function ($x) {
            return mb_strlen($x) > 3;
        }));
    }

    /**
     * Analyse une page.
     *
     * @return array ['score' => int, 'checks' => [ ['id','label','ok'(bool),'detail','weight'] ], 'words' => int]
     */
    public static function analyze($focus, $seo_title, $description, $slug, $html) {
        $content = strip_shortcodes($html);
        $text    = wp_strip_all_tags($content);
        $words   = self::word_count($text);
        $nfocus  = self::normalize($focus);
        $checks  = array();

        $add = function ($id, $label, $ok, $detail, $weight, $group = 'seo') use (&$checks) {
            $checks[] = compact('id', 'label', 'ok', 'detail', 'weight', 'group');
        };

        // --- SEO de base ---
        $t = self::normalize($seo_title);
        $add('kp_title', 'Expression-clé dans le titre SEO', false !== strpos($t, $nfocus), $seo_title, 8);
        $add('kp_title_start', 'Titre SEO commençant par l\'expression-clé', 0 === strpos($t, $nfocus), '', 5);
        $tl = mb_strlen($seo_title);
        $add('title_len', 'Longueur du titre SEO (40–60 car.)', $tl >= 40 && $tl <= 60, $tl . ' caractères', 4);

        $d = self::normalize($description);
        $dl = mb_strlen($description);
        $add('kp_desc', 'Expression-clé dans la méta-description', false !== strpos($d, $nfocus), '', 6);
        $add('desc_len', 'Longueur de la méta-description (120–160 car.)', $dl >= 120 && $dl <= 160, $dl . ' caractères', 4);

        $nslug = sanitize_title($focus);
        $add('kp_url', 'Expression-clé dans l\'URL', false !== strpos($slug, $nslug), $slug, 5);

        $kw_words = count(explode(' ', $nfocus));
        $add('kp_length', 'Longueur de l\'expression-clé (≤ 8 mots)', $kw_words <= 8, $kw_words . ' mots', 2);

        $first_p = '';
        if (preg_match('/<div class="oa-intro">\s*<p[^>]*>(.*?)<\/p>/su', $content, $m) || preg_match('/<p[^>]*>(.*?)<\/p>/su', $content, $m)) {
            $first_p = $m[1];
        }
        $add('kp_intro', 'Expression-clé dans l\'introduction', false !== strpos(self::normalize($first_p), $nfocus), '', 7);

        preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/su', $content, $hm);
        $heads    = $hm[1];
        $with_kp  = 0;
        foreach ($heads as $h) {
            if (false !== strpos(self::normalize($h), $nfocus)) {
                $with_kp++;
            }
        }
        $ratio = $heads ? $with_kp / count($heads) : 0;
        $add('kp_subheads', 'Expression-clé dans les sous-titres (sans excès)', $with_kp >= 1 && $ratio <= 0.75, $with_kp . '/' . count($heads) . ' sous-titres', 5);

        preg_match_all('/<img[^>]+alt="([^"]*)"/u', $content, $am);
        $alt_ok = false;
        foreach ($am[1] as $alt) {
            if (false !== strpos(self::normalize($alt), $nfocus)) {
                $alt_ok = true;
                break;
            }
        }
        $add('kp_alt', 'Expression-clé dans l\'attribut alt d\'une image', $alt_ok, count($am[1]) . ' image(s)', 4);

        $occ     = self::count_phrase($text, $focus);
        $density = $words ? round($occ / $words * 100, 2) : 0;
        $add('kp_density', 'Densité de l\'expression-clé (0,4 %–2,5 %)', $density >= 0.4 && $density <= 2.5, $occ . ' occurrences — ' . $density . ' %', 7);

        $add('length', 'Longueur du contenu (≥ 600 mots)', $words >= 600, $words . ' mots', 8);

        preg_match_all('/<a\s[^>]*href="([^"]+)"/u', $content, $lm);
        $internal = 0;
        $external = 0;
        $host     = wp_parse_url(home_url(), PHP_URL_HOST);
        foreach ($lm[1] as $href) {
            $h = wp_parse_url($href, PHP_URL_HOST);
            if (!$h || $h === $host) {
                $internal++;
            } else {
                $external++;
            }
        }
        $add('internal_links', 'Liens internes', $internal >= 1, $internal . ' lien(s) + maillage automatique', 4);
        $add('external_links', 'Lien externe d\'autorité', $external >= 1, $external . ' lien(s)', 3);
        $add('images', 'Images dans le contenu', count($am[1]) >= 1, count($am[1]) . ' image(s)', 3);

        // --- Lisibilité ---
        $sents = self::sentences($text);
        $n     = max(1, count($sents));
        $long  = 0;
        $trans = 0;
        $pass  = 0;
        $rep   = 0;
        $prev  = '';
        $streak = 1;
        foreach ($sents as $sent) {
            $wc = self::word_count($sent);
            if ($wc > 20) {
                $long++;
            }
            $ns = ' ' . self::normalize($sent) . ' ';
            foreach (self::$transitions as $tw) {
                if (false !== strpos($ns, ' ' . $tw . ' ') || false !== strpos($ns, ' ' . $tw . ',')) {
                    $trans++;
                    break;
                }
            }
            if (preg_match('/\b(est|sont|été|était|étaient|sera|seront|soit|soient)\s+(\w+\s+)?\w+(é|ée|és|ées)\s+par\b/u', $ns)) {
                $pass++;
            }
            $first = strtok(trim($ns), ' ');
            if ($first === $prev) {
                $streak++;
                if (3 === $streak) {
                    $rep++;
                }
            } else {
                $streak = 1;
            }
            $prev = $first;
        }
        $long_pct  = round($long / $n * 100);
        $trans_pct = round($trans / $n * 100);
        $pass_pct  = round($pass / $n * 100);
        $add('sentence_len', 'Phrases courtes (≤ 25 % de plus de 20 mots)', $long_pct <= 25, $long_pct . ' % de phrases longues', 6, 'read');
        $add('transitions', 'Mots de transition (≥ 30 % des phrases)', $trans_pct >= 30, $trans_pct . ' %', 5, 'read');
        $add('passive', 'Voix passive (≤ 10 %)', $pass_pct <= 10, $pass_pct . ' %', 3, 'read');
        $add('repetition', 'Débuts de phrases variés', 0 === $rep, $rep . ' répétition(s)', 2, 'read');

        preg_match_all('/<p[^>]*>(.*?)<\/p>/su', $content, $pm);
        $long_p = 0;
        foreach ($pm[1] as $p) {
            if (self::word_count($p) > 150) {
                $long_p++;
            }
        }
        $add('paragraph_len', 'Paragraphes courts (≤ 150 mots)', 0 === $long_p, $long_p . ' paragraphe(s) trop long(s)', 3, 'read');

        $blocks = preg_split('/<h[23][^>]*>/u', $content);
        $max_block = 0;
        foreach ($blocks as $b) {
            $max_block = max($max_block, self::word_count($b));
        }
        $add('subhead_dist', 'Sous-titres réguliers (≤ 300 mots entre deux titres)', $max_block <= 300, $max_block . ' mots max', 3, 'read');

        $total = 0;
        $got   = 0;
        foreach ($checks as $c) {
            $total += $c['weight'];
            if ($c['ok']) {
                $got += $c['weight'];
            }
        }
        $score = $total ? (int) round($got / $total * 100) : 0;

        return array('score' => $score, 'checks' => $checks, 'words' => $words, 'occurrences' => $occ);
    }

    /** Liste lisible des critères échoués (pour relancer l'IA). */
    public static function failed_summary(array $analysis) {
        $out = array();
        foreach ($analysis['checks'] as $c) {
            if (!$c['ok']) {
                $out[] = $c['label'] . ($c['detail'] ? ' (actuellement : ' . $c['detail'] . ')' : '');
            }
        }
        return implode(' ; ', $out);
    }

    /* ------------------------------------------------------------------ */
    /* Similarité (anti contenu dupliqué entre villes)                     */
    /* ------------------------------------------------------------------ */

    public static function shingles($text, $size = 5) {
        $w = preg_split('/\s+/u', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY);
        $set = array();
        for ($i = 0, $n = count($w) - $size; $i <= $n; $i++) {
            $set[crc32(implode(' ', array_slice($w, $i, $size)))] = true;
        }
        return $set;
    }

    /**
     * Similarité max (%) avec les pages déjà générées pour le même mot-clé dans d'autres villes.
     * Les noms de ville sont neutralisés pour ne mesurer que le fond.
     */
    public static function similarity($html, $keyword_id, $city_name, $exclude_id = 0) {
        $ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => array('publish', 'draft', 'future', 'pending'),
            'posts_per_page' => 6,
            'fields'         => 'ids',
            'post__not_in'   => $exclude_id ? array($exclude_id) : array(),
            'meta_query'     => array(array('key' => '_oa_keyword', 'value' => $keyword_id)),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        if (!$ids) {
            return 0;
        }
        $mine = self::shingles(str_ireplace($city_name, 'VILLE', strip_shortcodes($html)));
        if (!$mine) {
            return 0;
        }
        $max = 0;
        foreach ($ids as $id) {
            $other_city = get_post_meta($id, '_oa_city_name', true);
            $theirs     = self::shingles(str_ireplace($other_city, 'VILLE', strip_shortcodes(get_post_field('post_content', $id))));
            if (!$theirs) {
                continue;
            }
            $inter = count(array_intersect_key($mine, $theirs));
            $union = count($mine + $theirs);
            $max   = max($max, $union ? $inter / $union * 100 : 0);
        }
        return (int) round($max);
    }

    /* ------------------------------------------------------------------ */
    /* Écriture des métadonnées                                            */
    /* ------------------------------------------------------------------ */

    public static function active_plugin() {
        if (function_exists('aioseo') || defined('AIOSEO_VERSION')) {
            return 'aioseo';
        }
        if (defined('WPSEO_VERSION')) {
            return 'yoast';
        }
        if (class_exists('RankMath')) {
            return 'rankmath';
        }
        return '';
    }

    public static function write_meta($post_id, $focus, $title, $description, array $secondary, $score, $image_url = '') {
        update_post_meta($post_id, '_oa_seo_title', $title);
        update_post_meta($post_id, '_oa_seo_desc', $description);

        // AIOSEO
        if (class_exists('\AIOSEO\Plugin\Common\Models\Post')) {
            try {
                $p = \AIOSEO\Plugin\Common\Models\Post::getPost($post_id);
                $p->post_id             = $post_id;
                $p->title               = $title;
                $p->description         = $description;
                $p->og_title            = $title;
                $p->og_description      = $description;
                $p->twitter_title       = $title;
                $p->twitter_description = $description;
                $p->keyphrases          = wp_json_encode(array(
                    'focus'      => array('keyphrase' => $focus, 'score' => $score, 'analysis' => new stdClass()),
                    'additional' => array_map(function ($k) {
                        return array('keyphrase' => $k, 'score' => 0, 'analysis' => new stdClass());
                    }, array_slice($secondary, 0, 3)),
                ));
                $p->seo_score           = $score;
                if ($image_url) {
                    $p->og_image_type       = 'featured';
                    $p->twitter_image_type  = 'featured';
                }
                $p->updated = gmdate('Y-m-d H:i:s');
                if (!$p->exists()) {
                    $p->created = gmdate('Y-m-d H:i:s');
                }
                $p->save();
            } catch (\Throwable $e) {
                self::write_aioseo_table($post_id, $focus, $title, $description, $score);
            }
        } else {
            self::write_aioseo_table($post_id, $focus, $title, $description, $score);
        }

        // Yoast
        update_post_meta($post_id, '_yoast_wpseo_title', $title);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus);

        // Rank Math
        update_post_meta($post_id, 'rank_math_title', $title);
        update_post_meta($post_id, 'rank_math_description', $description);
        update_post_meta($post_id, 'rank_math_focus_keyword', implode(',', array_merge(array($focus), array_slice($secondary, 0, 2))));
    }

    /** Écriture directe si AIOSEO est installé mais pas chargé (ex. génération en cron). */
    private static function write_aioseo_table($post_id, $focus, $title, $description, $score) {
        global $wpdb;
        $table = $wpdb->prefix . 'aioseo_posts';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return;
        }
        $data = array(
            'post_id'        => $post_id,
            'title'          => $title,
            'description'    => $description,
            'og_title'       => $title,
            'og_description' => $description,
            'keyphrases'     => wp_json_encode(array('focus' => array('keyphrase' => $focus, 'score' => $score, 'analysis' => new stdClass()), 'additional' => array())),
            'seo_score'      => $score,
            'updated'        => gmdate('Y-m-d H:i:s'),
        );
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE post_id = %d", $post_id));
        if ($exists) {
            $wpdb->update($table, $data, array('post_id' => $post_id));
        } else {
            $data['created'] = gmdate('Y-m-d H:i:s');
            $wpdb->insert($table, $data);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Schema.org                                                          */
    /* ------------------------------------------------------------------ */

    public static function is_oa_page($post_id = 0) {
        $post_id = $post_id ?: get_queried_object_id();
        return $post_id && get_post_meta($post_id, '_oa_generated', true);
    }

    public static function graphs($post_id) {
        $s     = OA_Settings::all();
        $trade = OA_Trades::get(get_post_meta($post_id, '_oa_trade', true));
        $city  = get_post_meta($post_id, '_oa_city', true);
        $data  = get_post_meta($post_id, '_oa_data', true);
        $url   = get_permalink($post_id);
        if (!is_array($city)) {
            return array();
        }

        $business_id = home_url('/#oa-business');
        $place       = array('@type' => 'City', 'name' => $city['name'], 'containedInPlace' => array('@type' => 'AdministrativeArea', 'name' => $city['dept_name']));
        $business    = array(
            '@type'      => $trade && !empty($trade['schema_type']) ? $trade['schema_type'] : 'HomeAndConstructionBusiness',
            '@id'        => $business_id,
            'name'       => $s['company_name'],
            'url'        => home_url('/'),
            'areaServed' => $place,
        );
        if ($s['phone']) {
            $business['telephone'] = $s['phone'];
        }
        if ($s['email']) {
            $business['email'] = $s['email'];
        }
        if ($s['address'] || $s['base_city']) {
            $business['address'] = array_filter(array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => $s['address'],
                'addressLocality' => $s['base_city'],
                'postalCode'      => $s['postal_code'],
                'addressCountry'  => 'FR',
            ));
        }
        if ($s['logo_id'] && ($logo = wp_get_attachment_image_url($s['logo_id'], 'full'))) {
            $business['logo'] = $logo;
        }
        if ($img = get_the_post_thumbnail_url($post_id, 'full')) {
            $business['image'] = $img;
        }
        if ($s['rating_value'] && $s['rating_count']) {
            $business['aggregateRating'] = array('@type' => 'AggregateRating', 'ratingValue' => $s['rating_value'], 'reviewCount' => $s['rating_count']);
        }

        $graphs   = array($business);
        $graphs[] = array(
            '@type'       => 'Service',
            '@id'         => $url . '#service',
            'name'        => get_the_title($post_id),
            'serviceType' => get_post_meta($post_id, '_oa_keyword_label', true),
            'description' => get_post_meta($post_id, '_oa_seo_desc', true),
            'provider'    => array('@id' => $business_id),
            'areaServed'  => $place,
            'url'         => $url,
        );

        if (is_array($data) && !empty($data['faq'])) {
            $faq = array('@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array());
            foreach ($data['faq'] as $f) {
                $faq['mainEntity'][] = array(
                    '@type'          => 'Question',
                    'name'           => wp_strip_all_tags(str_replace('*', '', $f['question'])),
                    'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags(str_replace('*', '', $f['answer']))),
                );
            }
            $graphs[] = $faq;
        }
        return $graphs;
    }

    public static function aioseo_schema($graphs) {
        if (!OA_Settings::get('schema') || !is_singular('page') || !self::is_oa_page()) {
            return $graphs;
        }
        return array_merge((array) $graphs, self::graphs(get_queried_object_id()));
    }

    /* ------------------------------------------------------------------ */
    /* Secours sans plugin SEO                                             */
    /* ------------------------------------------------------------------ */

    public static function fallback_head() {
        if (!is_singular('page') || !self::is_oa_page()) {
            return;
        }
        $id     = get_queried_object_id();
        $plugin = self::active_plugin();
        if (!$plugin) {
            $desc = get_post_meta($id, '_oa_seo_desc', true);
            if ($desc) {
                echo '<meta name="description" content="' . esc_attr($desc) . "\" />\n";
                echo '<meta property="og:title" content="' . esc_attr(get_post_meta($id, '_oa_seo_title', true)) . "\" />\n";
                echo '<meta property="og:description" content="' . esc_attr($desc) . "\" />\n";
                echo '<meta property="og:type" content="website" />' . "\n";
                echo '<meta property="og:url" content="' . esc_url(get_permalink($id)) . "\" />\n";
                if ($img = get_the_post_thumbnail_url($id, 'large')) {
                    echo '<meta property="og:image" content="' . esc_url($img) . "\" />\n";
                }
            }
        }
        // Schema autonome si AIOSEO ne l'a pas intégré à son graphe.
        if ('aioseo' !== $plugin && OA_Settings::get('schema')) {
            $json = array('@context' => 'https://schema.org', '@graph' => self::graphs($id));
            echo '<script type="application/ld+json">' . wp_json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
        }
    }

    public static function fallback_title($title) {
        if (self::active_plugin() || !is_singular('page') || !self::is_oa_page()) {
            return $title;
        }
        $t = get_post_meta(get_queried_object_id(), '_oa_seo_title', true);
        return $t ? $t : $title;
    }
}
