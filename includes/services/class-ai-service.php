<?php
/**
 * Service d'intégration avec les APIs IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Service {
    
    private $api_key;
    private $provider;
    
    public function __construct() {
        $this->api_key = get_option('osmose_ads_openai_api_key', '');
        $this->provider = get_option('osmose_ads_ai_provider', 'openai');
    }
    
    /**
     * Appeler l'API IA
     */
    public function call_ai($prompt, $system_message = '', $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API non configurée', 'osmose-ads'));
        }
        
        $default_options = array(
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 2000,
        );
        
        $options = wp_parse_args($options, $default_options);
        
        if ($this->provider === 'groq') {
            return $this->call_groq_api($prompt, $system_message, $options);
        }
        
        // Par défaut : OpenAI
        return $this->call_openai_api($prompt, $system_message, $options);
    }
    
    /**
     * Appeler l'API OpenAI
     */
    private function call_openai_api($prompt, $system_message, $options) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $messages = array();
        if (!empty($system_message)) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_message,
            );
        }
        $messages[] = array(
            'role' => 'user',
            'content' => $prompt,
        );
        
        $body = array(
            'model' => $options['model'],
            'messages' => $messages,
            'temperature' => $options['temperature'],
            'max_tokens' => $options['max_tokens'],
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }
        
        if (isset($body['choices'][0]['message']['content'])) {
            return $body['choices'][0]['message']['content'];
        }
        
        return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
    }
    
    /**
     * Appeler l'API Groq
     */
    private function call_groq_api($prompt, $system_message, $options) {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        
        $messages = array();
        if (!empty($system_message)) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_message,
            );
        }
        $messages[] = array(
            'role' => 'user',
            'content' => $prompt,
        );
        
        $body = array(
            'model' => 'mixtral-8x7b-32768', // Modèle Groq par défaut
            'messages' => $messages,
            'temperature' => $options['temperature'],
            'max_tokens' => $options['max_tokens'],
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }
        
        if (isset($body['choices'][0]['message']['content'])) {
            return $body['choices'][0]['message']['content'];
        }
        
        return new WP_Error('invalid_response', __('Réponse invalide de l\'API', 'osmose-ads'));
    }
}



