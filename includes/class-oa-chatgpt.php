<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * « Continuer avec ChatGPT » — utilise le forfait ChatGPT (Go/Plus/Pro/Business) de l'utilisateur
 * pour les requêtes IA, sans clé API.
 *
 * OpenAI réserve cette fonction aux partenaires approuvés : elle ne s'active qu'avec un
 * identifiant client OpenAI (oaiapp_…) obtenu via https://openai.com/form/sign-in-with-chatgpt-interest/
 * Il peut être défini dans wp-config.php (OA_CHATGPT_CLIENT_ID / OA_CHATGPT_CLIENT_SECRET)
 * ou dans Réglages → IA.
 *
 * Flux : OAuth 2.0 Authorization Code + PKCE + OpenID Connect, puis Responses API
 * (store=false, stream=true) avec le jeton d'accès. Doc : https://developers.openai.com/siwc
 */
class OA_ChatGPT {

    const ISSUER    = 'https://auth.openai.com';
    const AUTHORIZE = 'https://auth.openai.com/api/accounts/authorize';
    const TOKEN     = 'https://auth.openai.com/api/accounts/oauth/token';
    const JWKS      = 'https://auth.openai.com/.well-known/jwks.json';
    const DISCOVERY = 'https://auth.openai.com/.well-known/openid-configuration';
    const RESOURCE  = 'https://api.openai.com/v1';
    const SCOPES    = 'openid profile email offline_access resource.invoke chatgpt.tokens.use.direct';
    const PLAN      = 'chatgpt.tokens.use.direct';
    const OPTION    = 'oa_chatgpt_auth';
    const USAGE_URL = 'https://chatgpt.com/#settings/Usage';
    const FORM_URL  = 'https://openai.com/form/sign-in-with-chatgpt-interest/';

    public static function hooks() {
        add_action('admin_post_oa_chatgpt_start', array(__CLASS__, 'start'));
        add_action('admin_post_oa_chatgpt_callback', array(__CLASS__, 'callback'));
        add_action('admin_post_oa_chatgpt_disconnect', array(__CLASS__, 'disconnect'));
    }

    /* ---------- Configuration ---------- */

    public static function client_id() {
        if (defined('OA_CHATGPT_CLIENT_ID') && OA_CHATGPT_CLIENT_ID) {
            return (string) OA_CHATGPT_CLIENT_ID;
        }
        return trim((string) OA_Settings::get('chatgpt_client_id'));
    }

    public static function client_secret() {
        if (defined('OA_CHATGPT_CLIENT_SECRET') && OA_CHATGPT_CLIENT_SECRET) {
            return (string) OA_CHATGPT_CLIENT_SECRET;
        }
        return trim((string) OA_Settings::get('chatgpt_client_secret'));
    }

    public static function from_config() {
        return defined('OA_CHATGPT_CLIENT_ID') && OA_CHATGPT_CLIENT_ID;
    }

    public static function is_configured() {
        return (bool) preg_match('/^oaiapp_[A-Za-z0-9_\-]+$/', self::client_id());
    }

    /** URL de retour à déclarer à l'identique auprès d'OpenAI. */
    public static function redirect_uri() {
        return admin_url('admin-post.php?action=oa_chatgpt_callback');
    }

    /* ---------- État de connexion (jetons chiffrés au repos) ---------- */

    public static function auth() {
        $raw = get_option(self::OPTION, '');
        if (!$raw) {
            return array();
        }
        $data = json_decode(self::decrypt($raw), true);
        return is_array($data) ? $data : array();
    }

    private static function store(array $auth) {
        update_option(self::OPTION, self::encrypt(wp_json_encode($auth)), false);
    }

    public static function is_connected() {
        $a = self::auth();
        return !empty($a['refresh_token']) || (!empty($a['access_token']) && (int) $a['expires_at'] > time());
    }

    public static function plan_enabled() {
        $a = self::auth();
        return !empty($a['scopes']) && in_array(self::PLAN, (array) $a['scopes'], true);
    }

    /** Infos affichables (jamais de jeton). */
    public static function account() {
        $a = self::auth();
        return array(
            'email'        => isset($a['email']) ? $a['email'] : '',
            'name'         => isset($a['name']) ? $a['name'] : '',
            'connected_at' => isset($a['connected_at']) ? (int) $a['connected_at'] : 0,
            'plan'         => self::plan_enabled(),
        );
    }

    private static function key() {
        return hash('sha256', wp_salt('auth') . '|oa_chatgpt', true);
    }

    private static function encrypt($plain) {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($plain);
        }
        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'v1:' . base64_encode($iv . $tag . $ct);
    }

    private static function decrypt($stored) {
        if (0 !== strpos((string) $stored, 'v1:')) {
            return (string) base64_decode((string) $stored);
        }
        $bin = base64_decode(substr($stored, 3));
        if (strlen($bin) < 29) {
            return '';
        }
        $out = openssl_decrypt(substr($bin, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($bin, 0, 12), substr($bin, 12, 16));
        return false === $out ? '' : $out;
    }

    /* ---------- OAuth : démarrage ---------- */

    public static function start() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        check_admin_referer('oa_chatgpt_start');
        if (!self::is_configured()) {
            self::back('error', 'Identifiant client OpenAI manquant.');
        }

        $state    = self::random(32);
        $verifier = self::random(64);
        $nonce    = self::random(32);
        set_transient('oa_siwc_' . hash('sha256', $state), array(
            'verifier' => $verifier,
            'nonce'    => $nonce,
            'user'     => get_current_user_id(),
            'redirect' => self::redirect_uri(),
        ), 10 * MINUTE_IN_SECONDS);

        $params = array(
            'client_id'               => self::client_id(),
            'redirect_uri'            => self::redirect_uri(),
            'response_type'           => 'code',
            'scope'                   => self::SCOPES,
            'resource'                => self::RESOURCE,
            'state'                   => $state,
            'nonce'                   => $nonce,
            'code_challenge'          => self::b64url(hash('sha256', $verifier, true)),
            'code_challenge_method'   => 'S256',
            'ext_login_session_id'    => wp_hash(wp_get_session_token() ?: 'oa'),
            'ext_login_started_at_ms' => (string) floor(microtime(true) * 1000),
        );
        $a = self::auth();
        if (!empty($a['email'])) {
            $params['login_hint'] = $a['email'];
        }
        if (isset($_GET['reconsent'])) {
            $params['prompt'] = 'consent';
        }

        // Domaine externe : wp_redirect (et non wp_safe_redirect).
        wp_redirect(self::AUTHORIZE . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        exit;
    }

    /* ---------- OAuth : retour ---------- */

    public static function callback() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $key   = 'oa_siwc_' . hash('sha256', $state);
        $tx    = $state ? get_transient($key) : false;
        delete_transient($key); // usage unique

        if (!$tx || (int) $tx['user'] !== get_current_user_id()) {
            self::back('error', 'Connexion non vérifiée (session expirée). Recommencez.');
        }
        if (isset($_GET['error'])) {
            $err = sanitize_key(wp_unslash($_GET['error']));
            self::back('error', 'access_denied' === $err ? 'Connexion annulée dans ChatGPT.' : 'ChatGPT a refusé la connexion (' . $err . ').');
        }
        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        if ('' === $code) {
            self::back('error', 'Le retour de ChatGPT ne contient pas de code.');
        }

        $tokens = self::token_request(array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $tx['redirect'],
            'client_id'     => self::client_id(),
            'code_verifier' => $tx['verifier'],
            'resource'      => self::RESOURCE,
        ));
        if (is_wp_error($tokens)) {
            self::back('error', $tokens->get_error_message());
        }
        if (empty($tokens['id_token'])) {
            self::back('error', 'Réponse OpenAI incomplète (pas de jeton d\'identité).');
        }
        $claims = self::verify_id_token($tokens['id_token'], $tx['nonce']);
        if (is_wp_error($claims)) {
            self::back('error', $claims->get_error_message());
        }

        $prev = self::auth();
        if (!empty($prev['sub']) && $prev['sub'] !== $claims['sub'] && !empty($prev['refresh_token'])) {
            self::revoke($prev['refresh_token']);
        }

        $scopes = isset($tokens['scope']) ? preg_split('/\s+/', trim($tokens['scope'])) : array();
        self::store(array(
            'sub'           => $claims['sub'],
            'email'         => (!empty($claims['email']) && true === ($claims['email_verified'] ?? false)) ? $claims['email'] : (isset($claims['email']) ? $claims['email'] : ''),
            'name'          => isset($claims['name']) ? $claims['name'] : '',
            'access_token'  => isset($tokens['access_token']) ? $tokens['access_token'] : '',
            'refresh_token' => isset($tokens['refresh_token']) ? $tokens['refresh_token'] : '',
            'expires_at'    => time() + (int) ($tokens['expires_in'] ?? 3600),
            'scopes'        => array_values(array_filter($scopes)),
            'connected_at'  => time(),
        ));

        if (!self::plan_enabled()) {
            self::back('warning', 'Compte ChatGPT connecté, mais l\'utilisation du forfait n\'a pas été autorisée. Cliquez sur « Autoriser mon forfait » pour la donner.');
        }

        // Choix d'un modèle disponible dans le forfait.
        $models = self::models();
        if (!is_wp_error($models) && $models) {
            $current = OA_Settings::get('chatgpt_model');
            if (!$current || !isset($models[$current])) {
                OA_Settings::save(array('chatgpt_model' => (string) key($models)));
            }
        }
        OA_Settings::save(array('ai_provider' => 'chatgpt'));
        self::back('success', 'ChatGPT connecté : les pages seront générées avec votre forfait.');
    }

    public static function disconnect() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        check_admin_referer('oa_chatgpt_disconnect');
        $a  = self::auth();
        $ok = empty($a['refresh_token']) ? true : self::revoke($a['refresh_token']);
        delete_option(self::OPTION);
        if ('chatgpt' === OA_Settings::get('ai_provider')) {
            OA_Settings::save(array('ai_provider' => 'manual'));
        }
        self::back($ok ? 'success' : 'warning', $ok
            ? 'ChatGPT déconnecté.'
            : 'Déconnecté ici, mais la révocation côté OpenAI n\'a pas été confirmée : retirez l\'application dans ChatGPT → Paramètres → Sécurité et connexion.');
    }

    private static function back($type, $message) {
        set_transient('oa_chatgpt_notice_' . get_current_user_id(), array('type' => $type, 'message' => $message), 120);
        wp_safe_redirect(admin_url('admin.php?page=oa-settings#ia'));
        exit;
    }

    /** Message à afficher une fois dans les réglages. */
    public static function pull_notice() {
        $k = 'oa_chatgpt_notice_' . get_current_user_id();
        $n = get_transient($k);
        delete_transient($k);
        return $n;
    }

    /* ---------- Jetons ---------- */

    private static function token_request(array $form) {
        $headers = array('Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded');
        $secret  = self::client_secret();
        if ($secret) { // client confidentiel : secret uniquement dans l'en-tête Basic
            $headers['Authorization'] = 'Basic ' . base64_encode(rawurlencode(self::client_id()) . ':' . rawurlencode($secret));
        }
        $res = wp_remote_post(self::TOKEN, array('timeout' => 30, 'headers' => $headers, 'body' => $form));
        if (is_wp_error($res)) {
            return new WP_Error('oa_siwc_http', 'OpenAI injoignable : ' . $res->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if (200 !== $code || !is_array($data)) {
            $err = is_array($data) && isset($data['error']) ? (is_array($data['error']) ? ($data['error']['code'] ?? 'erreur') : $data['error']) : 'HTTP ' . $code;
            return new WP_Error('oa_siwc_token', 'Échange de jeton refusé par OpenAI (' . sanitize_text_field((string) $err) . ').', $err);
        }
        return $data;
    }

    /** Jeton d'accès valide (renouvelé si besoin). */
    public static function access_token($force_refresh = false) {
        $a = self::auth();
        if (empty($a['access_token']) && empty($a['refresh_token'])) {
            return new WP_Error('oa_siwc_reauth', 'ChatGPT n\'est pas connecté (Réglages → IA → Continuer avec ChatGPT).');
        }
        if (!$force_refresh && !empty($a['access_token']) && (int) $a['expires_at'] > time() + 120) {
            return $a['access_token'];
        }
        if (empty($a['refresh_token'])) {
            return new WP_Error('oa_siwc_reauth', 'Session ChatGPT expirée : reconnectez-vous.');
        }

        // Un seul renouvellement à la fois (jeton de rafraîchissement rotatif).
        if (get_transient('oa_siwc_refreshing')) {
            sleep(3);
            $b = self::auth();
            return (!empty($b['access_token']) && (int) $b['expires_at'] > time() + 60) ? $b['access_token'] : new WP_Error('oa_siwc_busy', 'Renouvellement de session en cours, réessai bientôt.');
        }
        set_transient('oa_siwc_refreshing', 1, 30);
        $t = self::token_request(array(
            'grant_type'    => 'refresh_token',
            'client_id'     => self::client_id(),
            'refresh_token' => $a['refresh_token'],
            'resource'      => self::RESOURCE,
        ));
        delete_transient('oa_siwc_refreshing');

        if (is_wp_error($t)) {
            $err = (string) $t->get_error_data();
            if (in_array($err, array('invalid_grant', 'invalid_refresh_token', 'token_expired', 'refresh_token_expired', 'refresh_token_invalidated', 'refresh_token_reused'), true)) {
                $a['access_token']  = '';
                $a['refresh_token'] = '';
                self::store($a);
                return new WP_Error('oa_siwc_reauth', 'Session ChatGPT révoquée ou expirée : reconnectez-vous dans Réglages → IA.');
            }
            return $t;
        }
        $a['access_token'] = $t['access_token'];
        $a['expires_at']   = time() + (int) ($t['expires_in'] ?? 3600);
        if (!empty($t['refresh_token'])) {
            $a['refresh_token'] = $t['refresh_token'];
        }
        if (!empty($t['scope'])) {
            $a['scopes'] = array_values(array_filter(preg_split('/\s+/', trim($t['scope']))));
        }
        self::store($a);
        return $a['access_token'];
    }

    private static function revoke($refresh_token) {
        $endpoint = self::discovery('revocation_endpoint');
        if (!$endpoint) {
            return false;
        }
        for ($i = 0; $i < 3; $i++) {
            $res = wp_remote_post($endpoint, array('timeout' => 15, 'body' => array(
                'token'           => $refresh_token,
                'token_type_hint' => 'refresh_token',
                'client_id'       => self::client_id(),
            )));
            if (!is_wp_error($res) && 200 === (int) wp_remote_retrieve_response_code($res)) {
                return true;
            }
            if (!is_wp_error($res) && (int) wp_remote_retrieve_response_code($res) < 500) {
                return false;
            }
            sleep(1 + $i);
        }
        return false;
    }

    private static function discovery($key) {
        $doc = get_transient('oa_siwc_discovery');
        if (!is_array($doc)) {
            $res = wp_remote_get(self::DISCOVERY, array('timeout' => 15));
            $doc = is_wp_error($res) ? array() : json_decode(wp_remote_retrieve_body($res), true);
            if (is_array($doc) && $doc) {
                set_transient('oa_siwc_discovery', $doc, DAY_IN_SECONDS);
            }
        }
        return is_array($doc) && isset($doc[$key]) ? $doc[$key] : '';
    }

    /* ---------- Vérification du jeton d'identité (RS256 / JWKS) ---------- */

    public static function verify_id_token($jwt, $nonce) {
        $parts = explode('.', (string) $jwt);
        if (3 !== count($parts)) {
            return new WP_Error('oa_siwc_jwt', 'Jeton d\'identité mal formé.');
        }
        $header  = json_decode(self::b64url_decode($parts[0]), true);
        $payload = json_decode(self::b64url_decode($parts[1]), true);
        if (!is_array($header) || !is_array($payload) || 'RS256' !== ($header['alg'] ?? '')) {
            return new WP_Error('oa_siwc_jwt', 'Jeton d\'identité non pris en charge.');
        }
        $pem = self::jwk_pem(isset($header['kid']) ? $header['kid'] : '');
        if (!$pem) {
            return new WP_Error('oa_siwc_jwt', 'Clé de signature OpenAI introuvable.');
        }
        if (1 !== openssl_verify($parts[0] . '.' . $parts[1], self::b64url_decode($parts[2]), $pem, OPENSSL_ALGO_SHA256)) {
            return new WP_Error('oa_siwc_jwt', 'Signature du jeton d\'identité invalide.');
        }
        $aud = isset($payload['aud']) ? (array) $payload['aud'] : array();
        $now = time();
        if (self::ISSUER !== ($payload['iss'] ?? '')
            || !in_array(self::client_id(), $aud, true)
            || (int) ($payload['exp'] ?? 0) < $now - 5
            || (int) ($payload['iat'] ?? 0) > $now + 5
            || empty($payload['sub']) || !is_string($payload['sub'])
            || !hash_equals((string) $nonce, (string) ($payload['nonce'] ?? ''))) {
            return new WP_Error('oa_siwc_jwt', 'Jeton d\'identité refusé (émetteur, audience, expiration ou nonce).');
        }
        return $payload;
    }

    private static function jwk_pem($kid, $retry = true) {
        $jwks = get_transient('oa_siwc_jwks');
        if (!is_array($jwks)) {
            $res  = wp_remote_get(self::JWKS, array('timeout' => 15));
            $jwks = is_wp_error($res) ? array() : json_decode(wp_remote_retrieve_body($res), true);
            if (!empty($jwks['keys'])) {
                set_transient('oa_siwc_jwks', $jwks, 6 * HOUR_IN_SECONDS);
            }
        }
        foreach ((array) ($jwks['keys'] ?? array()) as $k) {
            if ('RSA' === ($k['kty'] ?? '') && (!$kid || $kid === ($k['kid'] ?? '')) && !empty($k['n']) && !empty($k['e'])) {
                return self::rsa_pem(self::b64url_decode($k['n']), self::b64url_decode($k['e']));
            }
        }
        if ($retry) { // clé inconnue : rotation possible, on recharge une fois
            delete_transient('oa_siwc_jwks');
            return self::jwk_pem($kid, false);
        }
        return '';
    }

    private static function rsa_pem($n, $e) {
        $int = function ($b) {
            if (ord($b[0]) > 0x7f) {
                $b = "\0" . $b;
            }
            return self::der(0x02, $b);
        };
        $rsa  = self::der(0x30, $int($n) . $int($e));
        $spki = self::der(0x30, hex2bin('300d06092a864886f70d0101010500') . self::der(0x03, "\0" . $rsa));
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function der($tag, $data) {
        $len = strlen($data);
        if ($len < 128) {
            return chr($tag) . chr($len) . $data;
        }
        $bytes = ltrim(pack('N', $len), "\0");
        return chr($tag) . chr(0x80 | strlen($bytes)) . $bytes . $data;
    }

    /* ---------- Modèles et génération ---------- */

    /** @return array|WP_Error  [slug => libellé] */
    public static function models() {
        $token = self::access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        $res = wp_remote_get(self::RESOURCE . '/models', array('timeout' => 20, 'headers' => array('Authorization' => 'Bearer ' . $token)));
        if (is_wp_error($res)) {
            return $res;
        }
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $out  = array();
        foreach ((array) ($data['models'] ?? array()) as $m) {
            if (!empty($m['slug']) && 'list' === ($m['visibility'] ?? 'list')) {
                $out[$m['slug']] = !empty($m['display_name']) ? $m['display_name'] : $m['slug'];
            }
        }
        if (!$out && !empty($data['data'])) { // format liste classique
            foreach ($data['data'] as $m) {
                $out[$m['id']] = $m['id'];
            }
        }
        return $out ?: new WP_Error('oa_siwc_models', 'Aucun modèle disponible pour ce compte ChatGPT.');
    }

    /**
     * Génération JSON stricte via la Responses API, facturée sur le forfait ChatGPT.
     *
     * @return array|WP_Error ['data' => array, 'tokens' => int]
     */
    public static function json($system, $user, array $schema, $name, $model, $effort = '', $attempt = 1) {
        if (!self::plan_enabled()) {
            return new WP_Error('oa_siwc_plan', 'L\'utilisation du forfait ChatGPT n\'est pas autorisée : Réglages → IA → « Autoriser mon forfait ».');
        }
        $token = self::access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        $body = array(
            'model'        => $model,
            'instructions' => $system,
            'input'        => array(array('role' => 'user', 'content' => $user)),
            'text'         => array('format' => array('type' => 'json_schema', 'name' => $name, 'schema' => $schema, 'strict' => true)),
            'store'        => false,
            'stream'       => true,
        );
        if ($effort && preg_match('/^(o\d|gpt-[5-9])/i', $model)) {
            $body['reasoning'] = array('effort' => $effort);
        }

        $res = wp_remote_post(self::RESOURCE . '/responses', array(
            'timeout' => max(60, (int) OA_Settings::get('ai_timeout')),
            'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'Accept' => 'text/event-stream'),
            'body'    => wp_json_encode($body),
        ));
        if (is_wp_error($res)) {
            if ($attempt < 3) {
                sleep(2 * $attempt);
                return self::json($system, $user, $schema, $name, $model, $effort, $attempt + 1);
            }
            return new WP_Error('oa_http', 'Connexion à ChatGPT impossible : ' . $res->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($res);
        $raw    = (string) wp_remote_retrieve_body($res);
        $parsed = 200 === $status ? self::parse_stream($raw) : array('error' => self::error_from_body($raw), 'status' => $status);

        if (!empty($parsed['error'])) {
            $err   = $parsed['error'];
            $code  = $err['code'];
            $retry = in_array($code, array('subscription_sharing_usage_unavailable', 'subscription_sharing_user_unavailable'), true) || 503 === ($parsed['status'] ?? 0);
            if ((401 === ($parsed['status'] ?? 0) || 'subscription_sharing_invalid_user' === $code) && 1 === $attempt) {
                $fresh = self::access_token(true);
                if (!is_wp_error($fresh)) {
                    return self::json($system, $user, $schema, $name, $model, $effort, $attempt + 1);
                }
            }
            if ($retry && $attempt < 3) {
                sleep(5 * $attempt);
                return self::json($system, $user, $schema, $name, $model, $effort, $attempt + 1);
            }
            return new WP_Error('oa_siwc_' . ($code ?: 'error'), self::explain($code, $err['message']));
        }

        $data = OA_AI::decode_json($parsed['text']);
        if (null === $data) {
            return new WP_Error('oa_json', 'Réponse ChatGPT non exploitable (JSON invalide).');
        }
        OA_AI::track_usage($parsed['tokens']);
        return array('data' => $data, 'tokens' => $parsed['tokens']);
    }

    /** Lit le flux SSE complet ; succès uniquement sur response.completed. */
    public static function parse_stream($raw) {
        $text = '';
        $done = false;
        $tokens = 0;
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            if (0 !== strpos($line, 'data:')) {
                continue;
            }
            $ev = json_decode(trim(substr($line, 5)), true);
            if (!is_array($ev)) {
                continue;
            }
            $type = isset($ev['type']) ? $ev['type'] : '';
            if ('response.output_text.delta' === $type) {
                $text .= (string) $ev['delta'];
            } elseif ('response.completed' === $type) {
                $done   = true;
                $tokens = (int) ($ev['response']['usage']['total_tokens'] ?? 0);
                if ('' === $text) { // repli : texte final complet
                    foreach ((array) ($ev['response']['output'] ?? array()) as $o) {
                        foreach ((array) ($o['content'] ?? array()) as $c) {
                            if ('output_text' === ($c['type'] ?? '')) {
                                $text .= $c['text'];
                            }
                        }
                    }
                }
            } elseif ('response.failed' === $type || 'error' === $type) {
                $e = 'error' === $type ? $ev : ($ev['response']['error'] ?? array());
                return array('error' => array('code' => (string) ($e['code'] ?? 'response_failed'), 'message' => (string) ($e['message'] ?? '')));
            } elseif ('response.incomplete' === $type) {
                $why = (string) ($ev['response']['incomplete_details']['reason'] ?? 'incomplete');
                return array('error' => array('code' => 'incomplete', 'message' => 'Réponse interrompue (' . $why . ').'));
            }
        }
        if (!$done) {
            return array('error' => array('code' => 'stream_cut', 'message' => 'Le flux s\'est arrêté avant la fin de la réponse.'));
        }
        return array('text' => $text, 'tokens' => $tokens);
    }

    private static function error_from_body($raw) {
        $d = json_decode($raw, true);
        if (isset($d['error']) && is_array($d['error'])) {
            return array('code' => (string) ($d['error']['code'] ?? ''), 'message' => (string) ($d['error']['message'] ?? ''));
        }
        if (isset($d['detail'])) {
            return array('code' => '', 'message' => is_string($d['detail']) ? $d['detail'] : wp_json_encode($d['detail']));
        }
        return array('code' => '', 'message' => wp_strip_all_tags(substr($raw, 0, 200)));
    }

    private static function explain($code, $message) {
        $map = array(
            'subscription_sharing_usage_limit_exceeded'  => 'Limite d\'utilisation de votre forfait ChatGPT atteinte (ou limite fixée pour Osmose Ads). Consultez ChatGPT → Paramètres → Utilisation ; la génération reprendra après réinitialisation.',
            'subscription_sharing_user_not_eligible'     => 'Ce compte ChatGPT ne peut pas utiliser son forfait ici (forfait Go, Plus, Pro ou Business requis, ou restriction de l\'espace de travail).',
            'subscription_sharing_usage_unavailable'     => 'ChatGPT ne peut pas vérifier votre quota pour le moment. Réessai plus tard.',
            'subscription_sharing_user_unavailable'      => 'Compte ChatGPT momentanément indisponible. Réessai plus tard.',
            'subscription_sharing_unsupported_capability'=> 'Option non prise en charge avec le forfait ChatGPT (essayez un autre modèle).',
            'subscription_sharing_invalid_user'          => 'Session ChatGPT invalide : reconnectez-vous dans Réglages → IA.',
            'chatpass_v2_scope_not_authorized'           => 'Autorisation insuffisante : reconnectez ChatGPT et autorisez l\'utilisation du forfait.',
        );
        return isset($map[$code]) ? $map[$code] : ('ChatGPT : ' . ($message ?: $code ?: 'erreur inconnue'));
    }

    /* ---------- Utilitaires ---------- */

    private static function random($bytes) {
        return self::b64url(random_bytes($bytes));
    }

    public static function b64url($bin) {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64url_decode($s) {
        $s = strtr((string) $s, '-_', '+/');
        return (string) base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }
}
