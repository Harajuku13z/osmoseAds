<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Génère (ou régénère) une page locale à partir d'un job.
 *
 * Pipeline : contexte (ville, voisines, pages sœurs, page mère) → IA (JSON strict)
 * → corrections déterministes → rendu HTML → analyse SEO + anti-duplication
 * → nouvelle tentative ciblée si besoin → publication + AIOSEO + schema.
 */
class OA_Generator {

    const MAX_AI_ATTEMPTS = 2;

    /** @return array|WP_Error */
    public static function context(array $payload) {
        $trade = OA_Trades::get($payload['trade']);
        if (!$trade) {
            return new WP_Error('oa_trade', 'Métier introuvable : ' . $payload['trade']);
        }
        $keyword = !empty($payload['keyword']) ? $payload['keyword'] : OA_Trades::keyword($payload['trade'], $payload['keyword_id']);
        if (!$keyword) {
            return new WP_Error('oa_kw', 'Mot-clé introuvable : ' . $payload['keyword_id']);
        }
        $keyword += array('id' => $payload['keyword_id'], 'secondary' => array(), 'angle' => '', 'hub' => false);

        $city = $payload['city'];
        if (empty($city['dept_code']) && !empty($city['code'])) {
            $fresh = OA_Geo::get($city['code']);
            if (!is_wp_error($fresh)) {
                $city = $fresh;
            }
        }

        $neighbours = OA_Geo::neighbours_for_content($city, 6);

        // Page mère (hub) existante pour ce métier et cette ville.
        $hub_id = 0;
        if (empty($keyword['hub'])) {
            $hub_id = self::find_page($city['code'], $trade['id'], null, true);
        }

        $photos = array_values(array_filter(array_map('intval', isset($payload['photos']) ? (array) $payload['photos'] : array()), 'wp_attachment_is_image'));
        if (!$photos) {
            // Aucune photo choisie pour ce lot : banque mémorisée du métier.
            $photos = self::bank_photos($trade['id'], $keyword['id']);
        }
        if (count($photos) > 1) {
            // Rotation propre à chaque ville : deux villes n'ouvrent pas sur la même photo.
            $shift  = abs(crc32($city['code'] . $keyword['id'])) % count($photos);
            $photos = array_merge(array_slice($photos, $shift), array_slice($photos, 0, $shift));
        }

        $existing = !empty($payload['post_id']) ? (int) $payload['post_id'] : self::find_page($city['code'], $trade['id'], $keyword['id']);

        return array(
            'existing_id' => $existing,
            'trade'       => $trade,
            'keyword'     => $keyword,
            'city'        => $city,
            'focus'       => OA_Trades::focus_keyphrase($keyword['label'], $city['name']),
            'neighbours'  => $neighbours,
            'siblings'    => isset($payload['siblings']) ? array_values(array_diff((array) $payload['siblings'], array($keyword['label']))) : array(),
            'hub_id'      => $hub_id,
            'hub_url'     => $hub_id ? get_permalink($hub_id) : '',
            'hub_label'   => $hub_id ? get_post_meta($hub_id, '_oa_keyword_label', true) : '',
            'photos'      => $photos,
            'image_count' => min(count($photos), 7),
        );
    }

    /* ------------------------------------------------------------------ */
    /* Banque de photos par métier                                         */
    /* ------------------------------------------------------------------ */

    /** @return array [trade_id => [ '_pool' => int[], keyword_id => int[] ]] */
    public static function bank() {
        $bank = get_option('oa_photo_bank', array());
        return is_array($bank) ? $bank : array();
    }

    /** Mémorise les photos d'un lot (fusion, sans doublon). */
    public static function remember_photos($trade_id, array $photos, $replace = false) {
        $bank = self::bank();
        $cur  = ($replace || !isset($bank[$trade_id])) ? array() : $bank[$trade_id];
        foreach ($photos as $key => $ids) {
            $ids = array_values(array_filter(array_map('intval', (array) $ids)));
            if (!$ids && !$replace) {
                continue;
            }
            $cur[$key] = array_values(array_unique(array_merge($replace ? array() : (isset($cur[$key]) ? $cur[$key] : array()), $ids)));
        }
        $bank[$trade_id] = array_filter($cur);
        update_option('oa_photo_bank', $bank, false);
    }

    /** Photos à utiliser : propres au mot-clé, puis banque du métier, puis toutes les banques. */
    public static function bank_photos($trade_id, $keyword_id) {
        $bank = self::bank();
        $ids  = array();
        if (!empty($bank[$trade_id])) {
            $t   = $bank[$trade_id];
            $ids = array_merge(isset($t[$keyword_id]) ? $t[$keyword_id] : array(), isset($t['_pool']) ? $t['_pool'] : array());
            if (!$ids) {
                foreach ($t as $list) {
                    $ids = array_merge($ids, (array) $list);
                }
            }
        }
        if (!$ids) {
            foreach ($bank as $t) {
                foreach ((array) $t as $list) {
                    $ids = array_merge($ids, (array) $list);
                }
            }
        }
        return array_values(array_filter(array_unique(array_map('intval', $ids)), 'wp_attachment_is_image'));
    }

    /**
     * Re-met en page une page existante (nouvelles photos, nouveau design) SANS appel IA :
     * le texte déjà rédigé est conservé.
     *
     * @param int        $post_id
     * @param int[]|null $photos  null = photos d'origine, sinon banque du métier.
     */
    public static function rerender($post_id, $photos = null) {
        $payload = get_post_meta($post_id, '_oa_payload', true);
        $data    = get_post_meta($post_id, '_oa_data', true);
        if (!is_array($payload) || !is_array($data) || empty($payload['city'])) {
            return new WP_Error('oa_payload', 'Données d\'origine absentes pour cette page.');
        }
        $payload['post_id'] = $post_id;
        $payload['photos']  = null === $photos ? (isset($payload['photos']) ? $payload['photos'] : array()) : $photos;
        $ctx = self::context($payload);
        if (is_wp_error($ctx)) {
            return $ctx;
        }
        $built = self::build($data, $ctx, $post_id);
        return self::save($built, $ctx, $payload, 0);
    }

    /**
     * Recherche une page générée.
     *
     * @param string|null $keyword_id  null = n'importe lequel
     * @param bool        $hub_only
     */
    public static function find_page($city_code, $trade_id, $keyword_id = null, $hub_only = false) {
        $mq = array(
            array('key' => '_oa_city_code', 'value' => $city_code),
            array('key' => '_oa_trade', 'value' => $trade_id),
        );
        if ($keyword_id) {
            $mq[] = array('key' => '_oa_keyword', 'value' => $keyword_id);
        }
        if ($hub_only) {
            $mq[] = array('key' => '_oa_hub', 'value' => '1');
        }
        $ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => array('publish', 'draft', 'future', 'pending', 'private'),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => $mq,
        ));
        return $ids ? (int) $ids[0] : 0;
    }

    /**
     * Exécute un job complet avec l'IA.
     *
     * @return array|WP_Error ['post_id','score','tokens','similarity']
     */
    public static function run(array $payload) {
        $ctx = self::context($payload);
        if (is_wp_error($ctx)) {
            return $ctx;
        }

        $ai     = new OA_AI();
        $s      = OA_Settings::all();
        $tokens = 0;
        $best   = null;
        $note   = '';
        $tries  = $s['auto_retry'] ? self::MAX_AI_ATTEMPTS : 1;

        for ($attempt = 1; $attempt <= $tries; $attempt++) {
            $res = $ai->json(OA_Prompts::system(), OA_Prompts::user($ctx, $note), OA_Prompts::page_schema(), 'local_page', $attempt > 1 ? 0.95 : 0.8);
            if (is_wp_error($res)) {
                if ($best) {
                    break; // On garde la meilleure version obtenue.
                }
                return $res;
            }
            $tokens += $res['tokens'];

            $built = self::build($res['data'], $ctx, $ctx['existing_id']);
            $rank  = $built['analysis']['score'] - max(0, $built['similarity'] - $s['max_similarity']);
            if (!$best || $rank > $best['rank']) {
                $best         = $built;
                $best['rank'] = $rank;
            }

            $ok_score = $built['analysis']['score'] >= $s['min_score'];
            $ok_sim   = $built['similarity'] <= $s['max_similarity'];
            if ($ok_score && $ok_sim) {
                break;
            }
            $note = '';
            if (!$ok_score) {
                $note .= 'Critères SEO non atteints : ' . OA_Seo::failed_summary($built['analysis']) . '. ';
            }
            if (!$ok_sim) {
                $note .= 'Le texte ressemble trop (' . $built['similarity'] . ' %) à une page existante pour une autre ville : change la structure, les exemples, les formulations et l\'ordre des idées. ';
            }
        }

        return self::save($best, $ctx, $payload, $tokens);
    }

    /** Import d'une réponse JSON collée depuis ChatGPT (mode manuel). */
    public static function run_with_data(array $payload, array $data) {
        $ctx = self::context($payload);
        if (is_wp_error($ctx)) {
            return $ctx;
        }
        $built = self::build($data, $ctx, $ctx['existing_id']);
        return self::save($built, $ctx, $payload, 0);
    }

    /** Complète les clés manquantes et nettoie les types. */
    public static function sanitize_data(array $d) {
        $str  = function ($v) {
            return is_scalar($v) ? trim((string) $v) : '';
        };
        $list = function ($v) use ($str) {
            return array_values(array_filter(array_map($str, is_array($v) ? $v : array($v)), 'strlen'));
        };
        $out = array(
            'seo_title'        => $str(isset($d['seo_title']) ? $d['seo_title'] : ''),
            'meta_description' => $str(isset($d['meta_description']) ? $d['meta_description'] : ''),
            'h1'               => $str(isset($d['h1']) ? $d['h1'] : ''),
            'hero_tagline'     => $str(isset($d['hero_tagline']) ? $d['hero_tagline'] : ''),
            'intro'            => $list(isset($d['intro']) ? $d['intro'] : array()),
            'sections'         => array(),
            'steps'            => array('heading' => '', 'items' => array()),
            'local'            => array('heading' => '', 'paragraphs' => array()),
            'faq'              => array(),
            'cta'              => array('heading' => 'Demandez votre devis gratuit', 'text' => ''),
            'image_alts'       => $list(isset($d['image_alts']) ? $d['image_alts'] : array()),
            'excerpt'          => $str(isset($d['excerpt']) ? $d['excerpt'] : ''),
        );
        foreach ((array) (isset($d['sections']) ? $d['sections'] : array()) as $sec) {
            if (!is_array($sec) || empty($sec['heading'])) {
                continue;
            }
            $out['sections'][] = array(
                'heading'    => $str($sec['heading']),
                'paragraphs' => $list(isset($sec['paragraphs']) ? $sec['paragraphs'] : array()),
                'bullets'    => $list(isset($sec['bullets']) ? $sec['bullets'] : array()),
            );
        }
        if (!empty($d['steps']) && is_array($d['steps'])) {
            $out['steps']['heading'] = $str(isset($d['steps']['heading']) ? $d['steps']['heading'] : 'Déroulé de notre intervention');
            foreach ((array) (isset($d['steps']['items']) ? $d['steps']['items'] : array()) as $it) {
                if (is_array($it) && !empty($it['title'])) {
                    $out['steps']['items'][] = array('title' => $str($it['title']), 'text' => $str(isset($it['text']) ? $it['text'] : ''));
                }
            }
        }
        if (!empty($d['local']) && is_array($d['local'])) {
            $out['local'] = array(
                'heading'    => $str(isset($d['local']['heading']) ? $d['local']['heading'] : ''),
                'paragraphs' => $list(isset($d['local']['paragraphs']) ? $d['local']['paragraphs'] : array()),
            );
        }
        foreach ((array) (isset($d['faq']) ? $d['faq'] : array()) as $f) {
            if (is_array($f) && !empty($f['question']) && !empty($f['answer'])) {
                $out['faq'][] = array('question' => $str($f['question']), 'answer' => $str($f['answer']));
            }
        }
        if (!empty($d['cta']) && is_array($d['cta'])) {
            $out['cta'] = array(
                'heading' => $str(isset($d['cta']['heading']) ? $d['cta']['heading'] : '') ?: 'Demandez votre devis gratuit',
                'text'    => $str(isset($d['cta']['text']) ? $d['cta']['text'] : ''),
            );
        }
        return $out;
    }

    private static function trim_to($text, $max) {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $cut = preg_replace('/\s+\S*$/u', '', $cut);
        return rtrim($cut, " ,;:-–") . '…';
    }

    private static function lcfirst($s) {
        // Ne touche pas aux sigles / noms propres en début de phrase (« RGE », « Chalon »…).
        if (preg_match('/^(\p{Lu}{2,}|\p{Lu}\p{Ll}+-)/u', $s)) {
            return $s;
        }
        return mb_strtolower(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    /** Corrections déterministes des points SEO que l'on peut garantir sans l'IA. */
    private static function fix(array $d, array $ctx) {
        $s     = OA_Settings::all();
        $focus = $ctx['focus'];
        $Focus = OA_Prompts::ucfirst($focus);
        $nf    = OA_Seo::normalize($focus);

        // Titre SEO : doit commencer par l'expression-clé, 40–60 caractères.
        $title = $d['seo_title'];
        if (0 !== strpos(OA_Seo::normalize($title), $nf)) {
            $title = $Focus . ' : devis gratuit';
        }
        if ($s['title_suffix'] && mb_strlen($title . ' ' . $s['title_suffix']) <= 60) {
            $title .= ' ' . $s['title_suffix'];
        }
        if (mb_strlen($title) > 60) {
            $title = self::trim_to($title, 60);
            if (0 !== strpos(OA_Seo::normalize($title), $nf)) {
                $title = $Focus;
            }
        }
        if (mb_strlen($title) < 40) {
            $dept  = $ctx['city']['dept_code'] ? ' (' . $ctx['city']['dept_code'] . ')' : '';
            $title = $Focus . $dept;
            foreach (array(' : devis gratuit', ' – artisan qualifié') as $add) {
                if (mb_strlen($title . $add) <= 60) {
                    $title .= $add;
                    break;
                }
            }
        }
        $d['seo_title'] = $title;

        // Méta-description : contient l'expression-clé, 120–160 caractères.
        $desc = $d['meta_description'];
        if (false === strpos(OA_Seo::normalize($desc), $nf)) {
            $desc = $Focus . ' : ' . self::lcfirst($desc);
        }
        if (mb_strlen($desc) > 158) {
            $desc = self::trim_to($desc, 158);
        }
        if (mb_strlen($desc) < 120) {
            $desc .= ' Devis gratuit et rapide' . ($s['phone'] ? ' au ' . $s['phone'] : '') . '.';
            $desc  = self::trim_to($desc, 158);
        }
        $d['meta_description'] = $desc;

        // H1 : contient l'expression-clé.
        if ('' === $d['h1'] || false === strpos(OA_Seo::normalize($d['h1']), $nf)) {
            $d['h1'] = $Focus;
        }
        $d['h1'] = self::trim_to($d['h1'], 80);

        // Intro : l'expression-clé dans le premier paragraphe.
        if (empty($d['intro'])) {
            $d['intro'] = array($Focus . ' : ' . $s['company_name'] . ' vous accompagne avec un devis clair et un suivi de chantier rigoureux.');
        } elseif (false === strpos(OA_Seo::normalize($d['intro'][0]), $nf)) {
            $d['intro'][0] = 'Pour votre projet de ' . $focus . ', ' . self::lcfirst($d['intro'][0]);
        }

        if ('' === $d['hero_tagline']) {
            $d['hero_tagline'] = $d['excerpt'] ?: $Focus;
        }
        return $d;
    }

    /** Construit HTML + analyse sans rien enregistrer. */
    private static function build(array $raw, array $ctx, $post_id) {
        $data     = self::fix(self::sanitize_data($raw), $ctx);
        $renderer = new OA_Renderer($data, $ctx, $ctx['photos']);
        $html     = $renderer->html();
        $slug     = $post_id ? get_post_field('post_name', $post_id) : sanitize_title($ctx['focus']);
        $analysis = OA_Seo::analyze($ctx['focus'], $data['seo_title'], $data['meta_description'], $slug, $html);
        $sim      = OA_Seo::similarity($html, $ctx['keyword']['id'], $ctx['city']['name'], $post_id);

        return array(
            'data'       => $data,
            'html'       => $html,
            'analysis'   => $analysis,
            'similarity' => $sim,
            'images'     => $renderer->used_images(),
        );
    }

    private static function save(array $built, array $ctx, array $payload, $tokens) {
        $s       = OA_Settings::all();
        $data    = $built['data'];
        $post_id = $ctx['existing_id'];
        $is_hub  = !empty($ctx['keyword']['hub']);

        $parent = (int) $s['parent_page'];
        if ('silo' === $s['url_mode'] && !$is_hub && $ctx['hub_id']) {
            $parent = $ctx['hub_id'];
        }

        $status = !empty($payload['status']) ? $payload['status'] : $s['post_status'];
        $post   = array(
            'post_type'      => 'page',
            'post_title'     => $data['h1'],
            'post_content'   => $built['html'],
            'post_excerpt'   => $data['excerpt'],
            'post_parent'    => $parent,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        );

        if ($post_id) {
            $post['ID'] = $post_id;
        } else {
            $post['post_name']   = sanitize_title($ctx['focus']);
            $post['post_status'] = $status;
            $post['post_author'] = get_current_user_id() ?: self::default_author();
            if ('publish' === $status && !empty($payload['publish_at']) && strtotime($payload['publish_at']) > current_time('timestamp')) {
                $post['post_status']   = 'future';
                $post['post_date']     = $payload['publish_at'];
                $post['post_date_gmt'] = get_gmt_from_date($payload['publish_at']);
            }
        }

        // Le HTML est produit et échappé par OA_Renderer : on évite que kses ne retire
        // les attributs utiles quand la génération tourne en tâche de fond (sans utilisateur).
        kses_remove_filters();
        $result = $post_id ? wp_update_post(wp_slash($post), true) : wp_insert_post(wp_slash($post), true);
        kses_init();
        if (is_wp_error($result)) {
            return $result;
        }
        $post_id = (int) $result;

        // Le slug final (unicité) peut différer : on ré-analyse l'URL si besoin.
        $slug = get_post_field('post_name', $post_id);
        if (false === strpos($slug, sanitize_title($ctx['focus']))) {
            $built['analysis'] = OA_Seo::analyze($ctx['focus'], $data['seo_title'], $data['meta_description'], $slug, $built['html']);
        }

        if (!empty($built['images'])) {
            set_post_thumbnail($post_id, $built['images'][0]);
        }

        $meta = array(
            '_oa_generated'     => 1,
            '_oa_trade'         => $ctx['trade']['id'],
            '_oa_keyword'       => $ctx['keyword']['id'],
            '_oa_keyword_label' => $ctx['keyword']['label'],
            '_oa_city_code'     => $ctx['city']['code'],
            '_oa_city_name'     => $ctx['city']['name'],
            '_oa_city'          => $ctx['city'],
            '_oa_focus'         => $ctx['focus'],
            '_oa_hub'           => $is_hub ? 1 : 0,
            '_oa_score'         => $built['analysis']['score'],
            '_oa_checks'        => $built['analysis']['checks'],
            '_oa_words'         => $built['analysis']['words'],
            '_oa_similarity'    => $built['similarity'],
            '_oa_data'          => $data,
            '_oa_payload'       => array_diff_key($payload, array('post_id' => 1, 'publish_at' => 1)),
            '_oa_batch'         => isset($payload['batch_id']) ? $payload['batch_id'] : '',
            '_oa_generated_at'  => current_time('mysql'),
        );
        foreach ($meta as $k => $v) {
            update_post_meta($post_id, $k, $v);
        }

        OA_Seo::write_meta(
            $post_id,
            $ctx['focus'],
            $data['seo_title'],
            $data['meta_description'],
            (array) $ctx['keyword']['secondary'],
            $built['analysis']['score'],
            $built['images'] ? wp_get_attachment_image_url($built['images'][0], 'full') : ''
        );

        // Un hub qui vient d'être créé : rattacher les pages sœurs déjà existantes (mode silo).
        if ($is_hub && 'silo' === $s['url_mode']) {
            self::attach_children($post_id, $ctx['city']['code'], $ctx['trade']['id']);
        }

        return array(
            'post_id'    => $post_id,
            'score'      => $built['analysis']['score'],
            'similarity' => $built['similarity'],
            'tokens'     => $tokens,
        );
    }

    private static function attach_children($hub_id, $city_code, $trade_id) {
        $ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => 50,
            'fields'         => 'ids',
            'post__not_in'   => array($hub_id),
            'meta_query'     => array(
                array('key' => '_oa_city_code', 'value' => $city_code),
                array('key' => '_oa_trade', 'value' => $trade_id),
            ),
        ));
        foreach ($ids as $id) {
            if ((int) wp_get_post_parent_id($id) !== $hub_id) {
                wp_update_post(array('ID' => $id, 'post_parent' => $hub_id));
            }
        }
    }

    private static function default_author() {
        $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
        return $admins ? (int) $admins[0] : 1;
    }
}
