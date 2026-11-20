<?php
if (!defined('ABSPATH')) {
    exit;
}

class OA_Admin {

    const CAP = 'manage_options';

    private static $pages = array(
        'oa-dashboard' => 'Tableau de bord',
        'oa-generate'  => 'Générer des pages',
        'oa-queue'     => 'File d\'attente',
        'oa-pages'     => 'Pages & scores SEO',
        'oa-trades'    => 'Métiers & mots-clés',
        'oa-settings'  => 'Réglages',
    );

    public static function hooks() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_init', array(__CLASS__, 'activation_redirect'));
        add_action('admin_post_oa_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_oa_save_trade', array(__CLASS__, 'save_trade'));
        add_action('admin_post_oa_delete_trade', array(__CLASS__, 'delete_trade'));
        add_filter('display_post_states', array(__CLASS__, 'post_states'), 10, 2);

        $ajax = array(
            'city_search', 'city_nearby', 'city_bulk', 'trade', 'suggest_keywords', 'create_batch', 'process',
            'queue', 'job_action', 'manual_prompt', 'manual_import', 'regenerate', 'reanalyze', 'test_ai', 'connect_ai', 'models',
            'bank_get', 'bank_save', 'photo_targets', 'rerender',
        );
        foreach ($ajax as $a) {
            add_action('wp_ajax_oa_' . $a, array(__CLASS__, 'ajax_' . $a));
        }
    }

    public static function menu() {
        $icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg>');
        add_menu_page('Osmose Ads', 'Osmose Ads', self::CAP, 'oa-dashboard', array(__CLASS__, 'render'), $icon, 25);
        foreach (self::$pages as $slug => $title) {
            add_submenu_page('oa-dashboard', $title . ' — Osmose Ads', $title, self::CAP, $slug, array(__CLASS__, 'render'));
        }
    }

    public static function activation_redirect() {
        if (get_option('oa_activation_redirect')) {
            delete_option('oa_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=' . (OA_Settings::get('phone') ? 'oa-dashboard' : 'oa-settings')));
                exit;
            }
        }
    }

    public static function post_states($states, $post) {
        if (get_post_meta($post->ID, '_oa_generated', true)) {
            $score           = (int) get_post_meta($post->ID, '_oa_score', true);
            $states['oa']    = 'Osmose · SEO ' . $score;
        }
        return $states;
    }

    public static function assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        if (!isset(self::$pages[$page])) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style('oa-admin', OA_URL . 'admin/admin.css', array(), OA_VERSION);
        wp_enqueue_script('oa-admin', OA_URL . 'admin/admin.js', array('jquery'), OA_VERSION, true);
        wp_localize_script('oa-admin', 'OA', array(
            'ajax'     => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('oa_admin'),
            'page'     => $page,
            'max'      => OA_Trades::MAX_PER_CITY,
            'workers'  => (int) OA_Settings::get('workers'),
            'manual'   => 'manual' === OA_Settings::get('ai_provider'),
            'queueUrl' => admin_url('admin.php?page=oa-queue'),
            'trades'   => array_map(function ($t) {
                return array('id' => $t['id'], 'name' => $t['name']);
            }, array_values(OA_Trades::all())),
        ));
    }

    public static function render() {
        if (!current_user_can(self::CAP)) {
            wp_die('Accès refusé');
        }
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'oa-dashboard';
        $view = OA_DIR . 'admin/views/' . substr($page, 3) . '.php';
        echo '<div class="wrap oa-wrap">';
        include OA_DIR . 'admin/views/header.php';
        if (is_readable($view)) {
            include $view;
        }
        echo '</div>';
    }

    /* ------------------------------------------------------------------ */
    /* Formulaires                                                         */
    /* ------------------------------------------------------------------ */

    public static function save_settings() {
        if (!current_user_can(self::CAP)) {
            wp_die('Accès refusé');
        }
        check_admin_referer('oa_settings');
        $input = isset($_POST['oa']) && is_array($_POST['oa']) ? $_POST['oa'] : array();
        // La clé masquée affichée ne doit pas écraser la vraie clé.
        foreach (array('ai_key', 'chatgpt_client_secret') as $secret) {
            if (isset($input[$secret]) && (false !== strpos($input[$secret], '•'))) {
                unset($input[$secret]);
            }
        }
        OA_Settings::save($input, true);
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        wp_safe_redirect(admin_url('admin.php?page=oa-settings&saved=1' . ($tab ? '#' . $tab : '')));
        exit;
    }

    /** Parse « Libellé | secondaire, secondaire | angle » par ligne. */
    public static function parse_keyword_lines($text, $first_is_hub = false) {
        $out   = array();
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', (string) $text)));
        $i     = 0;
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            $label = preg_replace('/^[\d\.\-\)\s]+/', '', $parts[0]);
            $label = trim($label, " *-•");
            if ('' === $label) {
                continue;
            }
            $id = sanitize_title($label);
            if (isset($out[$id])) {
                continue;
            }
            $out[$id] = array(
                'label'     => $label,
                'secondary' => isset($parts[1]) ? array_values(array_filter(array_map('trim', explode(',', $parts[1])))) : array(),
                'angle'     => isset($parts[2]) ? $parts[2] : '',
                'hub'       => $first_is_hub && 0 === $i,
            );
            $i++;
        }
        return $out;
    }

    public static function save_trade() {
        if (!current_user_can(self::CAP)) {
            wp_die('Accès refusé');
        }
        check_admin_referer('oa_trade');
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $id   = sanitize_key(wp_unslash($_POST['trade_id'] ?? '')) ?: sanitize_title($name);
        if (!$name || !$id) {
            wp_safe_redirect(admin_url('admin.php?page=oa-trades&error=1'));
            exit;
        }
        $links = array();
        foreach (array_filter(array_map('trim', preg_split('/\r?\n/', wp_unslash($_POST['links'] ?? '')))) as $line) {
            $p = array_map('trim', explode('|', $line));
            if (wp_http_validate_url($p[0])) {
                $links[esc_url_raw($p[0])] = sanitize_text_field(isset($p[1]) ? $p[1] : wp_parse_url($p[0], PHP_URL_HOST));
            }
        }
        $custom      = get_option('oa_custom_trades', array());
        $custom[$id] = array(
            'name'        => $name,
            'schema_type' => sanitize_text_field(wp_unslash($_POST['schema_type'] ?? 'HomeAndConstructionBusiness')),
            'lexicon'     => sanitize_textarea_field(wp_unslash($_POST['lexicon'] ?? '')),
            'links'       => $links,
            'keywords'    => self::parse_keyword_lines(sanitize_textarea_field(wp_unslash($_POST['keywords'] ?? '')), !empty($_POST['first_is_hub'])),
            'custom'      => true,
        );
        update_option('oa_custom_trades', $custom, false);
        wp_safe_redirect(admin_url('admin.php?page=oa-trades&saved=1&edit=' . $id));
        exit;
    }

    public static function delete_trade() {
        if (!current_user_can(self::CAP)) {
            wp_die('Accès refusé');
        }
        check_admin_referer('oa_trade_delete');
        $id     = sanitize_key($_GET['trade_id'] ?? '');
        $custom = get_option('oa_custom_trades', array());
        unset($custom[$id]);
        update_option('oa_custom_trades', $custom, false);
        wp_safe_redirect(admin_url('admin.php?page=oa-trades&deleted=1'));
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* AJAX                                                                */
    /* ------------------------------------------------------------------ */

    private static function guard() {
        if (!current_user_can(self::CAP) || !check_ajax_referer('oa_admin', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Session expirée, rechargez la page.'), 403);
        }
    }

    private static function out($res) {
        if (is_wp_error($res)) {
            wp_send_json_error(array('message' => $res->get_error_message()));
        }
        wp_send_json_success($res);
    }

    private static function post($key, $default = '') {
        return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : $default;
    }

    public static function ajax_city_search() {
        self::guard();
        self::out(OA_Geo::search(sanitize_text_field(self::post('q'))));
    }

    public static function ajax_city_nearby() {
        self::guard();
        $center = OA_Geo::get(sanitize_text_field(self::post('code')));
        if (is_wp_error($center)) {
            self::out($center);
        }
        $extra = array_filter(array_map('trim', explode(',', sanitize_text_field(self::post('depts')))));
        self::out(OA_Geo::nearby($center, (float) self::post('radius', 20), (int) self::post('min_pop', 0), $extra, 300));
    }

    /** Résout une liste collée (noms ou codes postaux, un par ligne). */
    public static function ajax_city_bulk() {
        self::guard();
        $lines = array_slice(array_filter(array_map('trim', preg_split('/\r?\n|;/', sanitize_textarea_field(self::post('lines'))))), 0, 300);
        $found = array();
        $miss  = array();
        foreach ($lines as $l) {
            $r = OA_Geo::search($l);
            if (!is_wp_error($r) && $r) {
                $found[$r[0]['code']] = $r[0];
            } else {
                $miss[] = $l;
            }
        }
        self::out(array('cities' => array_values($found), 'missing' => $miss));
    }

    public static function ajax_trade() {
        self::guard();
        $t = OA_Trades::get(sanitize_key(self::post('trade')));
        if (!$t) {
            self::out(new WP_Error('oa', 'Métier introuvable'));
        }
        $t['keywords'] = array_values($t['keywords']);
        self::out($t);
    }

    public static function ajax_suggest_keywords() {
        self::guard();
        $t = OA_Trades::get(sanitize_key(self::post('trade')));
        if (!$t) {
            self::out(new WP_Error('oa', 'Métier introuvable'));
        }
        $ai = new OA_AI();
        if ($ai->is_manual()) {
            self::out(new WP_Error('oa', 'Les suggestions automatiques nécessitent une clé API. En mode manuel, ajoutez vos mots-clés à la main.'));
        }
        $city = sanitize_text_field(self::post('city')) ?: 'votre ville';
        $txt  = $ai->text('Tu es consultant SEO local expert du bâtiment en France.', OA_Prompts::keyword_suggestion($t['name'], $city, wp_list_pluck($t['keywords'], 'label')));
        if (is_wp_error($txt)) {
            self::out($txt);
        }
        self::out(array_values(self::parse_keyword_lines($txt)));
    }

    public static function ajax_create_batch() {
        self::guard();
        $trade = sanitize_key(self::post('trade'));
        if (!OA_Trades::get($trade)) {
            self::out(new WP_Error('oa', 'Choisissez un métier.'));
        }
        $cities   = json_decode(self::post('cities', '[]'), true);
        $keywords = json_decode(self::post('keywords', '[]'), true);
        $photos   = json_decode(self::post('photos', '{}'), true);
        if (!is_array($cities) || !$cities) {
            self::out(new WP_Error('oa', 'Ajoutez au moins une ville.'));
        }
        if (!is_array($keywords) || !$keywords) {
            self::out(new WP_Error('oa', 'Sélectionnez au moins un mot-clé.'));
        }
        if (count($keywords) > OA_Trades::MAX_PER_CITY) {
            self::out(new WP_Error('oa', 'Maximum ' . OA_Trades::MAX_PER_CITY . ' pages par ville.'));
        }

        $clean_cities = array();
        foreach ($cities as $c) {
            if (!empty($c['code']) && !empty($c['name'])) {
                $clean_cities[] = array(
                    'code'       => sanitize_text_field($c['code']),
                    'name'       => sanitize_text_field($c['name']),
                    'postcode'   => sanitize_text_field($c['postcode'] ?? ''),
                    'population' => (int) ($c['population'] ?? 0),
                    'lat'        => (float) ($c['lat'] ?? 0),
                    'lon'        => (float) ($c['lon'] ?? 0),
                    'dept_code'  => sanitize_text_field($c['dept_code'] ?? ''),
                    'dept_name'  => sanitize_text_field($c['dept_name'] ?? ''),
                    'region'     => sanitize_text_field($c['region'] ?? ''),
                );
            }
        }
        $clean_kw = array();
        foreach ($keywords as $k) {
            if (empty($k['label'])) {
                continue;
            }
            $id            = !empty($k['id']) ? sanitize_title($k['id']) : sanitize_title($k['label']);
            $clean_kw[]    = array(
                'id'        => $id,
                'label'     => sanitize_text_field($k['label']),
                'secondary' => array_values(array_filter(array_map('sanitize_text_field', (array) ($k['secondary'] ?? array())))),
                'angle'     => sanitize_text_field($k['angle'] ?? ''),
                'hub'       => !empty($k['hub']),
            );
        }
        $clean_photos = array();
        foreach ((array) $photos as $key => $ids) {
            $clean_photos[sanitize_title($key) ?: '_pool'] = array_values(array_filter(array_map('absint', (array) $ids)));
        }
        if (isset($photos['_pool'])) {
            $clean_photos['_pool'] = array_values(array_filter(array_map('absint', (array) $photos['_pool'])));
        }

        $status = sanitize_key(self::post('status'));
        $res    = OA_Queue::create_batch($trade, $clean_cities, $clean_kw, $clean_photos, array(
            'status'        => in_array($status, array('publish', 'draft', 'pending'), true) ? $status : '',
            'skip_existing' => '1' === self::post('skip_existing', '1'),
        ));
        self::out($res);
    }

    public static function ajax_process() {
        self::guard();
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
        $r = OA_Queue::process_one();
        self::out(array('job' => $r, 'stats' => OA_Queue::stats(), 'fatal' => get_option('oa_last_fatal')));
    }

    public static function ajax_queue() {
        self::guard();
        $jobs = OA_Queue::jobs(array('status' => sanitize_key(self::post('status')), 'limit' => 200));
        foreach ($jobs as &$j) {
            $j['edit'] = $j['post_id'] ? get_edit_post_link($j['post_id'], 'raw') : '';
            $j['view'] = $j['post_id'] ? get_permalink($j['post_id']) : '';
            $kw        = OA_Trades::keyword($j['trade'], $j['keyword_id']);
            $j['kw']   = $kw ? $kw['label'] : $j['keyword_id'];
        }
        self::out(array('jobs' => $jobs, 'stats' => OA_Queue::stats(), 'fatal' => get_option('oa_last_fatal')));
    }

    public static function ajax_job_action() {
        self::guard();
        $act = sanitize_key(self::post('act'));
        OA_Queue::action($act, absint(self::post('id')));
        self::out(OA_Queue::stats());
    }

    public static function ajax_manual_prompt() {
        self::guard();
        self::out(array('prompt' => OA_Queue::manual_prompt(absint(self::post('id')))));
    }

    public static function ajax_manual_import() {
        self::guard();
        $res = OA_Queue::import_manual(absint(self::post('id')), self::post('json'));
        if (!is_wp_error($res)) {
            $res['view'] = get_permalink($res['post_id']);
        }
        self::out($res);
    }

    public static function ajax_regenerate() {
        self::guard();
        self::out(array('job' => OA_Queue::enqueue_regeneration(absint(self::post('post_id')))));
    }

    /** Ré-analyse après retouches manuelles dans l'éditeur. */
    public static function ajax_reanalyze() {
        self::guard();
        $id    = absint(self::post('post_id'));
        $focus = get_post_meta($id, '_oa_focus', true);
        if (!$focus) {
            self::out(new WP_Error('oa', 'Page inconnue'));
        }
        $a = OA_Seo::analyze($focus, get_post_meta($id, '_oa_seo_title', true), get_post_meta($id, '_oa_seo_desc', true), get_post_field('post_name', $id), get_post_field('post_content', $id));
        update_post_meta($id, '_oa_score', $a['score']);
        update_post_meta($id, '_oa_checks', $a['checks']);
        update_post_meta($id, '_oa_words', $a['words']);
        self::out($a);
    }

    /* ---- Banque de photos ---- */

    private static function thumbs(array $ids) {
        $out = array();
        foreach ($ids as $id) {
            if ($url = wp_get_attachment_image_url($id, 'thumbnail')) {
                $out[] = array('id' => (int) $id, 'url' => $url);
            }
        }
        return $out;
    }

    public static function ajax_bank_get() {
        self::guard();
        $trade = sanitize_key(self::post('trade'));
        $bank  = OA_Generator::bank();
        $pool  = isset($bank[$trade]['_pool']) ? $bank[$trade]['_pool'] : array();
        // Les photos par mot-clé sont aussi affichées (fusionnées) pour une vue d'ensemble.
        $all = $pool;
        if (!empty($bank[$trade])) {
            foreach ($bank[$trade] as $list) {
                $all = array_merge($all, (array) $list);
            }
        }
        self::out(array('photos' => self::thumbs(array_values(array_unique(array_map('intval', $all))))));
    }

    public static function ajax_bank_save() {
        self::guard();
        $trade = sanitize_key(self::post('trade'));
        if (!OA_Trades::get($trade)) {
            self::out(new WP_Error('oa', 'Métier introuvable'));
        }
        $ids = array_values(array_filter(array_map('absint', (array) json_decode(self::post('ids', '[]'), true))));
        OA_Generator::remember_photos($trade, array('_pool' => $ids), true);
        self::out(array('photos' => self::thumbs($ids)));
    }

    /** Pages à traiter : sans image (mode missing) ou toutes celles du métier (mode all). */
    public static function ajax_photo_targets() {
        self::guard();
        $trade = sanitize_key(self::post('trade'));
        $mq    = array(array('key' => '_oa_generated', 'value' => '1'));
        if ($trade) {
            $mq[] = array('key' => '_oa_trade', 'value' => $trade);
        }
        if ('missing' === self::post('mode')) {
            $mq[] = array('key' => '_thumbnail_id', 'compare' => 'NOT EXISTS');
        }
        self::out(get_posts(array(
            'post_type'      => 'page',
            'post_status'    => array('publish', 'future', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => $mq,
        )));
    }

    /** Re-met en page (sans IA) une liste de pages avec la banque de photos. */
    public static function ajax_rerender() {
        self::guard();
        $ids  = array_slice(array_filter(array_map('absint', (array) json_decode(self::post('ids', '[]'), true))), 0, 20);
        $done = array();
        foreach ($ids as $id) {
            $trade  = get_post_meta($id, '_oa_trade', true);
            $kw     = get_post_meta($id, '_oa_keyword', true);
            $photos = OA_Generator::bank_photos($trade, $kw);
            $res    = OA_Generator::rerender($id, $photos ?: null);
            $done[] = is_wp_error($res)
                ? array('id' => $id, 'error' => $res->get_error_message())
                : array('id' => $id, 'score' => $res['score'], 'images' => (int) has_post_thumbnail($id));
        }
        self::out($done);
    }

    public static function ajax_test_ai() {
        self::guard();
        $ai = new OA_AI();
        if ($ai->is_manual()) {
            self::out(array('message' => 'Mode ChatGPT manuel : aucun test nécessaire.'));
        }
        $t0  = microtime(true);
        $txt = $ai->text('Réponds en une phrase courte.', 'Dis « Connexion réussie » et donne le nom de ton modèle.');
        if (is_wp_error($txt)) {
            self::out($txt);
        }
        self::out(array('message' => $txt . ' (' . round(microtime(true) - $t0, 1) . ' s)'));
    }

    /** Connexion guidée : enregistre la clé, la vérifie, active OpenAI et choisit un modèle disponible. */
    public static function ajax_connect_ai() {
        self::guard();
        $key = trim(sanitize_text_field(self::post('key')));
        if (!preg_match('/^sk-[A-Za-z0-9_\-]{20,}$/', $key)) {
            self::out(new WP_Error('oa_key', 'Clé invalide : copiez la clé complète (elle commence par sk-).'));
        }
        $before = OA_Settings::all();
        OA_Settings::save(array('ai_provider' => 'openai', 'ai_key' => $key, 'ai_base_url' => 'https://api.openai.com/v1'));

        $models = (new OA_AI())->list_models();
        if (is_wp_error($models)) {
            OA_Settings::save(array('ai_provider' => $before['ai_provider'], 'ai_key' => $before['ai_key'], 'ai_base_url' => $before['ai_base_url']));
            self::out(new WP_Error('oa_key', 'OpenAI refuse cette clé : ' . $models->get_error_message()));
        }
        $model = $before['ai_model'];
        if ($models && !in_array($model, $models, true)) {
            foreach (array('gpt-5-mini', 'gpt-5', 'gpt-4.1-mini', 'gpt-4o-mini') as $pref) {
                if (in_array($pref, $models, true)) {
                    $model = $pref;
                    break;
                }
            }
            OA_Settings::save(array('ai_model' => $model));
        }

        $txt = (new OA_AI())->text('Réponds en une phrase courte.', 'Dis « Connexion réussie ».');
        $msg = is_wp_error($txt)
            ? 'Clé enregistrée, mais l\'essai de génération a échoué : ' . $txt->get_error_message() . ' (vérifiez le crédit dans Billing).'
            : 'Connecté — modèle ' . $model . ' prêt.';
        self::out(array('message' => $msg, 'model' => $model, 'masked' => OA_Settings::masked_key()));
    }

    public static function ajax_models() {
        self::guard();
        self::out((new OA_AI())->list_models());
    }
}
