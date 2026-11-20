<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * File d'attente de génération.
 *
 * - Verrouillage atomique (UPDATE … LIMIT 1) : plusieurs travailleurs en parallèle sans doublon.
 * - Traitement depuis l'admin (N travailleurs AJAX) ET en arrière-plan (WP-Cron chaque minute).
 * - Jobs bloqués > 10 min remis en file, 3 tentatives max.
 * - Publication progressive (drip) : X pages / jour programmées.
 */
class OA_Queue {

    const MAX_ATTEMPTS = 3;
    const STALE_MIN    = 10;

    public static function hooks() {
        add_action('oa_process_queue', array(__CLASS__, 'cron'));
    }

    private static function t() {
        return OA_Install::table('jobs');
    }

    /**
     * Crée un lot : villes × mots-clés.
     *
     * @param string $trade_id
     * @param array  $cities    Communes normalisées (OA_Geo).
     * @param array  $keywords  [ ['id','label','secondary','angle','hub'] … ] (≤ 8)
     * @param array  $photos    [ keyword_id => int[] , '_pool' => int[] ]
     * @param array  $opts      ['status','skip_existing']
     */
    public static function create_batch($trade_id, array $cities, array $keywords, array $photos, array $opts = array()) {
        global $wpdb;
        $s        = OA_Settings::all();
        $batch_id = substr(md5(uniqid('', true)), 0, 12);
        $now      = current_time('mysql');
        $keywords = array_slice($keywords, 0, OA_Trades::MAX_PER_CITY);
        $labels   = wp_list_pluck($keywords, 'label');
        $pool     = isset($photos['_pool']) ? array_map('intval', (array) $photos['_pool']) : array();
        $skip     = !empty($opts['skip_existing']);
        $status   = !empty($opts['status']) ? $opts['status'] : $s['post_status'];

        // Le hub d'abord, pour que les pages filles puissent s'y relier.
        usort($keywords, function ($a, $b) {
            return (int) !empty($b['hub']) - (int) !empty($a['hub']);
        });

        $drip      = (int) $s['drip_per_day'];
        $slot      = 0;
        $start_day = strtotime(current_time('Y-m-d') . ' 08:00:00');
        $created   = 0;
        $skipped   = 0;

        foreach ($cities as $ci => $city) {
            foreach ($keywords as $ki => $kw) {
                $kw_id = $kw['id'];

                if ($skip && OA_Generator::find_page($city['code'], $trade_id, $kw_id)) {
                    $skipped++;
                    continue;
                }
                $pending = $wpdb->get_var($wpdb->prepare(
                    'SELECT id FROM ' . self::t() . " WHERE city_code = %s AND keyword_id = %s AND trade = %s AND status IN ('pending','running','manual') LIMIT 1",
                    $city['code'], $kw_id, $trade_id
                ));
                if ($pending) {
                    $skipped++;
                    continue;
                }

                $kw_photos = !empty($photos[$kw_id]) ? array_map('intval', (array) $photos[$kw_id]) : array();
                $payload   = array(
                    'batch_id'   => $batch_id,
                    'trade'      => $trade_id,
                    'keyword_id' => $kw_id,
                    'keyword'    => $kw,
                    'city'       => $city,
                    'siblings'   => $labels,
                    'photos'     => array_values(array_unique(array_merge($kw_photos, $pool))),
                    'status'     => $status,
                );

                $publish_at = null;
                if ($drip > 0 && 'publish' === $status) {
                    $day        = intdiv($slot, $drip);
                    $minute     = (int) floor(($slot % $drip) * (600 / $drip)); // étalé entre 8 h et 18 h
                    $publish_at = gmdate('Y-m-d H:i:s', $start_day + DAY_IN_SECONDS * $day + 60 * $minute);
                    $payload['publish_at'] = $publish_at;
                    $slot++;
                }

                $wpdb->insert(self::t(), array(
                    'batch_id'   => $batch_id,
                    'trade'      => $trade_id,
                    'keyword_id' => $kw_id,
                    'city_code'  => $city['code'],
                    'city_name'  => $city['name'],
                    'payload'    => wp_json_encode($payload),
                    'status'     => 'pending',
                    'priority'   => !empty($kw['hub']) ? 10 : 0,
                    'publish_at' => $publish_at,
                    'created_at' => $now,
                ));
                $created++;
            }
        }

        return array('batch_id' => $batch_id, 'created' => $created, 'skipped' => $skipped);
    }

    /** Régénère une page existante. */
    public static function enqueue_regeneration($post_id) {
        global $wpdb;
        $payload = get_post_meta($post_id, '_oa_payload', true);
        if (!is_array($payload) || empty($payload['city'])) {
            return new WP_Error('oa_payload', 'Cette page ne peut pas être régénérée (données d\'origine absentes).');
        }
        $payload['post_id'] = $post_id;
        $wpdb->insert(self::t(), array(
            'batch_id'   => 'regen',
            'trade'      => $payload['trade'],
            'keyword_id' => $payload['keyword_id'],
            'city_code'  => $payload['city']['code'],
            'city_name'  => $payload['city']['name'],
            'payload'    => wp_json_encode($payload),
            'status'     => 'pending',
            'priority'   => 20,
            'created_at' => current_time('mysql'),
        ));
        return (int) $wpdb->insert_id;
    }

    private static function release_stale() {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            'UPDATE ' . self::t() . " SET status = IF(attempts >= %d, 'failed', 'pending'), claim = '', message = 'Délai dépassé, relancé automatiquement'
             WHERE status = 'running' AND started_at < %s",
            self::MAX_ATTEMPTS,
            gmdate('Y-m-d H:i:s', current_time('timestamp') - self::STALE_MIN * MINUTE_IN_SECONDS)
        ));
    }

    /** Réserve atomiquement le prochain job. */
    private static function claim() {
        global $wpdb;
        $claim = substr(md5(uniqid('', true)), 0, 16);
        $wpdb->query($wpdb->prepare(
            'UPDATE ' . self::t() . " SET status = 'running', claim = %s, attempts = attempts + 1, started_at = %s
             WHERE status = 'pending' ORDER BY priority DESC, id ASC LIMIT 1",
            $claim,
            current_time('mysql')
        ));
        if (!$wpdb->rows_affected) {
            return null;
        }
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::t() . ' WHERE claim = %s', $claim), ARRAY_A);
    }

    /**
     * Traite un job.
     *
     * @return array|null  Résumé, ou null si la file est vide.
     */
    public static function process_one() {
        global $wpdb;
        self::release_stale();
        $job = self::claim();
        if (!$job) {
            return null;
        }

        @set_time_limit(max(300, (int) OA_Settings::get('ai_timeout') * 3));
        $payload = json_decode($job['payload'], true);
        try {
            $res = OA_Generator::run($payload);
        } catch (\Throwable $e) {
            $res = new WP_Error('oa_fatal', $e->getMessage());
        }

        if (is_wp_error($res) && 'oa_manual' === $res->get_error_code()) {
            $data              = $res->get_error_data();
            $payload['prompt'] = $data['prompt'];
            $wpdb->update(self::t(), array(
                'status'   => 'manual',
                'claim'    => '',
                'attempts' => 0,
                'payload'  => wp_json_encode($payload),
                'message'  => 'En attente : copiez le prompt dans ChatGPT puis collez la réponse.',
            ), array('id' => $job['id']));
            return array('id' => (int) $job['id'], 'status' => 'manual');
        }

        if (is_wp_error($res)) {
            $fatal  = in_array($res->get_error_code(), array('oa_no_key', 'oa_trade', 'oa_kw'), true) || false !== strpos($res->get_error_message(), '401');
            $status = ($fatal || (int) $job['attempts'] >= self::MAX_ATTEMPTS) ? 'failed' : 'pending';
            $wpdb->update(self::t(), array(
                'status'      => $status,
                'claim'       => '',
                'message'     => $res->get_error_message(),
                'finished_at' => current_time('mysql'),
            ), array('id' => $job['id']));
            if ($fatal) {
                update_option('oa_last_fatal', array('time' => time(), 'message' => $res->get_error_message()), false);
            }
            return array('id' => (int) $job['id'], 'status' => $status, 'message' => $res->get_error_message());
        }

        self::mark_done($job['id'], $res);
        return array('id' => (int) $job['id'], 'status' => 'done', 'post_id' => $res['post_id'], 'score' => $res['score']);
    }

    public static function mark_done($job_id, array $res) {
        global $wpdb;
        $msg = 'Score SEO ' . $res['score'] . '/100 — similarité ' . $res['similarity'] . ' %';
        $wpdb->update(self::t(), array(
            'status'      => 'done',
            'claim'       => '',
            'post_id'     => $res['post_id'],
            'seo_score'   => $res['score'],
            'tokens'      => $res['tokens'],
            'message'     => $msg,
            'finished_at' => current_time('mysql'),
        ), array('id' => $job_id));
        delete_option('oa_last_fatal');
    }

    /** Import manuel (réponse collée depuis ChatGPT). */
    public static function import_manual($job_id, $json) {
        global $wpdb;
        $job = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::t() . ' WHERE id = %d', $job_id), ARRAY_A);
        if (!$job) {
            return new WP_Error('oa_job', 'Tâche introuvable');
        }
        $data = OA_AI::decode_json($json);
        if (!$data) {
            return new WP_Error('oa_json', 'Le texte collé ne contient pas de JSON valide. Copiez toute la réponse de ChatGPT.');
        }
        $payload = json_decode($job['payload'], true);
        unset($payload['prompt']);
        $res = OA_Generator::run_with_data($payload, $data);
        if (is_wp_error($res)) {
            return $res;
        }
        self::mark_done($job_id, $res);
        return $res;
    }

    public static function manual_prompt($job_id) {
        global $wpdb;
        $payload = json_decode((string) $wpdb->get_var($wpdb->prepare('SELECT payload FROM ' . self::t() . ' WHERE id = %d', $job_id)), true);
        if (!empty($payload['prompt'])) {
            return $payload['prompt'];
        }
        $ctx = OA_Generator::context($payload);
        if (is_wp_error($ctx)) {
            return $ctx;
        }
        return OA_AI::manual_prompt(OA_Prompts::system(), OA_Prompts::user($ctx), OA_Prompts::page_schema());
    }

    /** WP-Cron : traite la file en arrière-plan pendant ~45 s. */
    public static function cron() {
        if (!OA_Settings::get('background') || 'manual' === OA_Settings::get('ai_provider')) {
            return;
        }
        if (get_transient('oa_cron_lock')) {
            return;
        }
        set_transient('oa_cron_lock', 1, 120);
        $deadline = time() + 45;
        while (time() < $deadline) {
            $r = self::process_one();
            if (null === $r) {
                break;
            }
            if ('failed' === $r['status'] && get_option('oa_last_fatal')) {
                break;
            }
        }
        delete_transient('oa_cron_lock');
    }

    public static function stats($batch_id = '') {
        global $wpdb;
        $where = $batch_id ? $wpdb->prepare(' WHERE batch_id = %s', $batch_id) : '';
        $rows  = $wpdb->get_results('SELECT status, COUNT(*) n, AVG(NULLIF(seo_score,0)) s, SUM(tokens) t FROM ' . self::t() . $where . ' GROUP BY status', ARRAY_A);
        $out   = array('pending' => 0, 'running' => 0, 'done' => 0, 'failed' => 0, 'manual' => 0, 'cancelled' => 0, 'avg_score' => 0, 'tokens' => 0);
        $sum   = 0;
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
            $out['tokens']    += (int) $r['t'];
            if ('done' === $r['status']) {
                $out['avg_score'] = (int) round($r['s']);
            }
            $sum += (int) $r['n'];
        }
        $out['total'] = $sum;
        return $out;
    }

    public static function jobs($args = array()) {
        global $wpdb;
        $args  = wp_parse_args($args, array('status' => '', 'limit' => 100, 'offset' => 0));
        $where = '1=1';
        if ($args['status']) {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }
        return $wpdb->get_results(
            'SELECT id, batch_id, trade, keyword_id, city_name, status, attempts, post_id, seo_score, tokens, message, publish_at, created_at, finished_at FROM '
            . self::t() . " WHERE $where ORDER BY FIELD(status,'running','manual','pending','failed','done','cancelled'), priority DESC, id DESC LIMIT " . (int) $args['limit'] . ' OFFSET ' . (int) $args['offset'],
            ARRAY_A
        );
    }

    public static function action($action, $id = 0) {
        global $wpdb;
        $t = self::t();
        switch ($action) {
            case 'retry':
                return $wpdb->query($wpdb->prepare("UPDATE $t SET status='pending', attempts=0, message='' WHERE id=%d AND status IN ('failed','cancelled')", $id));
            case 'cancel':
                return $wpdb->query($wpdb->prepare("UPDATE $t SET status='cancelled' WHERE id=%d AND status IN ('pending','manual','failed')", $id));
            case 'delete':
                return $wpdb->query($wpdb->prepare("DELETE FROM $t WHERE id=%d AND status <> 'running'", $id));
            case 'retry_failed':
                return $wpdb->query("UPDATE $t SET status='pending', attempts=0, message='' WHERE status='failed'");
            case 'cancel_pending':
                return $wpdb->query("UPDATE $t SET status='cancelled' WHERE status IN ('pending','manual')");
            case 'clear_done':
                return $wpdb->query("DELETE FROM $t WHERE status IN ('done','cancelled')");
        }
        return false;
    }
}
