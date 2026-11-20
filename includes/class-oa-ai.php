<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Couche IA.
 *
 * Fournisseurs :
 *  - openai     : API OpenAI (sorties JSON strictes via json_schema).
 *  - compatible : toute API compatible OpenAI (OpenRouter, Mistral, Groq, LM Studio…).
 *  - manual     : « ChatGPT manuel » — le plugin prépare le prompt, l'utilisateur le colle
 *                 dans son abonnement ChatGPT et recolle la réponse JSON.
 */
class OA_AI {

    /** @var array */
    private $s;

    public function __construct() {
        $this->s = OA_Settings::all();
    }

    public function provider() {
        return $this->s['ai_provider'];
    }

    public function is_manual() {
        return 'manual' === $this->s['ai_provider'];
    }

    public function is_ready() {
        if ($this->is_manual()) {
            return true;
        }
        return '' !== trim((string) $this->s['ai_key']) || 'compatible' === $this->s['ai_provider'];
    }

    private function base_url() {
        if ('openai' === $this->s['ai_provider']) {
            return 'https://api.openai.com/v1';
        }
        return untrailingslashit($this->s['ai_base_url'] ?: 'https://api.openai.com/v1');
    }

    private function is_reasoning_model($model) {
        return (bool) preg_match('/^(o\d|gpt-5)/i', $model);
    }

    /**
     * Génère un objet JSON conforme au schéma.
     *
     * @return array|WP_Error  ['data' => array, 'tokens' => int]
     */
    public function json($system, $user, array $schema, $schema_name = 'page', $temperature = 0.8) {
        if ($this->is_manual()) {
            return new WP_Error('oa_manual', 'Mode ChatGPT manuel', array('prompt' => self::manual_prompt($system, $user, $schema)));
        }
        if (!$this->is_ready()) {
            return new WP_Error('oa_no_key', 'Aucune clé API configurée (Osmose Ads → Réglages → IA).');
        }

        $model = $this->s['ai_model'] ?: 'gpt-5-mini';
        $body  = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system),
                array('role' => 'user', 'content' => $user),
            ),
        );

        if ('openai' === $this->s['ai_provider']) {
            $body['response_format'] = array(
                'type'        => 'json_schema',
                'json_schema' => array('name' => $schema_name, 'strict' => true, 'schema' => $schema),
            );
        } else {
            $body['response_format'] = array('type' => 'json_object');
            $body['messages'][1]['content'] .= "\n\nRéponds UNIQUEMENT avec un objet JSON valide respectant ce schéma :\n" . wp_json_encode($schema);
        }

        if ($this->is_reasoning_model($model)) {
            if (!empty($this->s['ai_reasoning'])) {
                $body['reasoning_effort'] = $this->s['ai_reasoning'];
            }
        } else {
            $body['temperature'] = $temperature;
        }

        $result = $this->post('/chat/completions', $body);

        // Certains modèles refusent un paramètre : on le retire et on relance une fois.
        if (is_wp_error($result) && 'oa_param' === $result->get_error_code()) {
            $param = $result->get_error_data();
            unset($body[$param]);
            $result = $this->post('/chat/completions', $body);
        }
        if (is_wp_error($result)) {
            return $result;
        }

        $content = isset($result['choices'][0]['message']['content']) ? $result['choices'][0]['message']['content'] : '';
        if (!empty($result['choices'][0]['message']['refusal'])) {
            return new WP_Error('oa_refusal', 'Le modèle a refusé : ' . $result['choices'][0]['message']['refusal']);
        }
        $data = self::decode_json($content);
        if (null === $data) {
            return new WP_Error('oa_json', 'Réponse IA non exploitable (JSON invalide).');
        }
        $tokens = isset($result['usage']['total_tokens']) ? (int) $result['usage']['total_tokens'] : 0;
        self::track_usage($tokens);

        return array('data' => $data, 'tokens' => $tokens);
    }

    /** Texte libre court (suggestions de mots-clés, tests). */
    public function text($system, $user) {
        $schema = array(
            'type'                 => 'object',
            'properties'           => array('text' => array('type' => 'string')),
            'required'             => array('text'),
            'additionalProperties' => false,
        );
        $res = $this->json($system, $user, $schema, 'text', 0.5);
        if (is_wp_error($res)) {
            return $res;
        }
        return (string) $res['data']['text'];
    }

    private function post($path, array $body, $attempt = 1) {
        $headers = array('Content-Type' => 'application/json');
        if ('' !== trim((string) $this->s['ai_key'])) {
            $headers['Authorization'] = 'Bearer ' . trim($this->s['ai_key']);
        }
        $res = wp_remote_post($this->base_url() . $path, array(
            'timeout' => (int) $this->s['ai_timeout'],
            'headers' => $headers,
            'body'    => wp_json_encode($body),
        ));

        if (is_wp_error($res)) {
            if ($attempt < 3) {
                sleep(2 * $attempt);
                return $this->post($path, $body, $attempt + 1);
            }
            return new WP_Error('oa_http', 'Connexion IA impossible : ' . $res->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);

        if (200 === $code && is_array($data)) {
            return $data;
        }

        $message = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP ' . $code;
        $param   = isset($data['error']['param']) ? $data['error']['param'] : '';

        if (400 === $code && $param && in_array($param, array('temperature', 'reasoning_effort', 'response_format'), true)) {
            return new WP_Error('oa_param', $message, $param);
        }
        if ((429 === $code || $code >= 500) && $attempt < 4) {
            $retry = (int) wp_remote_retrieve_header($res, 'retry-after');
            sleep(max(2, min(20, $retry ?: 3 * $attempt)));
            return $this->post($path, $body, $attempt + 1);
        }
        if (401 === $code) {
            $message = 'Clé API refusée (401). Vérifiez la clé dans les réglages.';
        } elseif (429 === $code && false !== stripos($message, 'quota')) {
            $message = 'Quota/crédit API épuisé : ' . $message;
        }
        return new WP_Error('oa_api', $message);
    }

    public static function decode_json($content) {
        $content = trim((string) $content);
        // Tolère les blocs ```json … ``` (mode manuel / modèles compatibles).
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $content, $m)) {
            $content = $m[1];
        }
        $start = strpos($content, '{');
        $end   = strrpos($content, '}');
        if (false === $start || false === $end) {
            return null;
        }
        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        return is_array($data) ? $data : null;
    }

    public static function manual_prompt($system, $user, array $schema) {
        return $system . "\n\n---\n\n" . $user
            . "\n\n---\n\nFORMAT DE SORTIE OBLIGATOIRE : réponds uniquement avec un bloc de code JSON valide (aucun texte avant ou après) respectant ce schéma JSON :\n"
            . wp_json_encode($schema, JSON_UNESCAPED_UNICODE);
    }

    public function list_models() {
        if ($this->is_manual()) {
            return array();
        }
        $headers = array();
        if ('' !== trim((string) $this->s['ai_key'])) {
            $headers['Authorization'] = 'Bearer ' . trim($this->s['ai_key']);
        }
        $res = wp_remote_get($this->base_url() . '/models', array('timeout' => 20, 'headers' => $headers));
        if (is_wp_error($res)) {
            return $res;
        }
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($data['data'])) {
            return new WP_Error('oa_models', isset($data['error']['message']) ? $data['error']['message'] : 'Liste des modèles indisponible');
        }
        $ids = wp_list_pluck($data['data'], 'id');
        $ids = array_values(array_filter($ids, function ($id) {
            return !preg_match('/(embedding|whisper|tts|dall-e|image|audio|realtime|moderation|transcribe|search|sora)/i', $id);
        }));
        sort($ids);
        return $ids;
    }

    private static function track_usage($tokens) {
        $month = gmdate('Y-m');
        $usage = get_option('oa_usage', array());
        if (!isset($usage[$month])) {
            $usage[$month] = array('tokens' => 0, 'calls' => 0);
        }
        $usage[$month]['tokens'] += $tokens;
        $usage[$month]['calls']  += 1;
        update_option('oa_usage', array_slice($usage, -12, null, true), false);
    }
}
