<?php
/**
 * Clase AI Generator
 * Genera CSS usando IA (Claude, OpenAI, Gemini)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_AI_Generator {
    
    private $api_key;
    private $provider;
    private $model;
    
    public function __construct() {
        // CORRECCIÓN 1: Cargar el proveedor y el modelo guardados
        $this->provider = get_option('getso_forms_ai_provider', 'claude');
        $this->model = get_option('getso_forms_ai_model', 'claude-sonnet-4-20250514');
        
        // CORRECCIÓN 2: Cargar la API key ESPECÍFICA del proveedor guardado
        // Esto soluciona el error en el CHAT DE IA del editor de formularios.
        if (!empty($this->provider)) {
            // Construye el nombre de la opción dinámicamente, ej: 'getso_forms_ai_api_key_gemini'
            $this->api_key = get_option('getso_forms_ai_api_key_' . $this->provider, '');
        } else {
            $this->api_key = ''; // Si no hay proveedor, no hay clave
        }
    }
    
    /**
     * Generar CSS con IA
     */
    public function generate_css($form_id, $prompt) {
        // Esta función ahora usará la API Key correcta cargada por el constructor
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key no configurada. Revisa la página de Configuración.');
        }
        
        // Verificar rate limit
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit', 'Límite de solicitudes alcanzado');
        }
        
        $form = Getso_Forms_Manager::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', 'Formulario no encontrado');
        }
        
        // Construir contexto
        $context = $this->build_context($form);
        
        // Construir prompt completo
        $full_prompt = $this->build_prompt($context, $prompt);
        
        // Llamar a IA
        $response = $this->call_ai($full_prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Parsear y sanitizar CSS
        $css = $this->sanitize_css($response);
        
        // Guardar en historial
        $this->save_to_history($form_id, $prompt, $css);
        
        return $css;
    }
    
    /**
     * Construir contexto del formulario
     */
    private function build_context($form) {
        $fields = $form->form_fields['fields'] ?? array();
        $field_names = array_column($fields, 'name');
        
        return array(
            'form_name' => $form->form_name,
            'fields' => $field_names,
            'current_css' => $form->form_css,
            'classes' => array(
                '.getso-form-wrapper',
                '.getso-form-container',
                '.getso-form-row',
                '.getso-form-group',
                '.getso-form-group input',
                '.getso-form-group select',
                '.getso-form-group textarea',
                '.getso-btn-primary',
                '.getso-btn-whatsapp'
            )
        );
    }
    
    /**
     * Construir prompt completo
     */
    private function build_prompt($context, $user_prompt) {
        return "Eres un experto en CSS y diseño web.

Tu tarea es modificar el CSS de un formulario web basándote en las instrucciones del usuario.

CONTEXTO DEL FORMULARIO:
- Nombre: {$context['form_name']}
- Campos: " . implode(', ', $context['fields']) . "

CLASES CSS DISPONIBLES:
" . implode("\n", $context['classes']) . "

CSS ACTUAL:
{$context['current_css']}

INSTRUCCIONES DEL USUARIO:
{$user_prompt}

IMPORTANTE:
1. Responde SOLO con código CSS válido
2. Usa las clases existentes
3. Mantén estructura responsive
4. NO incluyas explicaciones
5. NO uses selectores genéricos (body, html)

Genera el CSS:";
    }
    
    /**
     * Llamar a IA según proveedor
     */
    private function call_ai($prompt) {
        // CORRECCIÓN: Asegurarse de que las propiedades de la clase están seteadas
        if (empty($this->api_key) || empty($this->provider) || empty($this->model)) {
            return new WP_Error('config_missing', 'Falta API key, proveedor o modelo en la clase.');
        }

        switch ($this->provider) {
            case 'openai':
                return $this->call_openai($prompt);
            case 'claude':
                return $this->call_claude($prompt);
            case 'gemini':
                return $this->call_gemini($prompt);
            default:
                return new WP_Error('invalid_provider', 'Proveedor inválido');
        }
    }
    
    /**
     * OpenAI API
     */
    private function call_openai($prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => $this->model,
                'messages' => array(
                    array('role' => 'system', 'content' => 'Eres un experto en CSS.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.3,
                'max_tokens' => 2000
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['choices'][0]['message']['content'];
    }
    
    /**
     * Claude API
     */
    private function call_claude($prompt) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => $this->model,
                'max_tokens' => 2000,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['content'][0]['text'];
    }
    
    /**
     * Gemini API
     */
    private function call_gemini($prompt) {
        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $this->api_key,
            array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode(array(
                    'contents' => array(
                        array('parts' => array(array('text' => $prompt)))
                    ),
                    'generationConfig' => array(
                        'temperature' => 0.3,
                        'maxOutputTokens' => 2000
                    )
                )),
                'timeout' => 30
            )
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }
        
        return $body['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Sanitizar CSS
     */
    private function sanitize_css($css) {
        // Eliminar etiquetas style y markdown
        $css = preg_replace('/<\/?style[^>]*>/i', '', $css);
        $css = preg_replace('/```css\n?/i', '', $css);
        $css = preg_replace('/```\n?/i', '', $css);
        
        // Eliminar @import (seguridad)
        $css = preg_replace('/@import\s+[^;]+;/i', '', $css);
        
        return trim($css);
    }
    
    /**
     * Verificar rate limit
     */
    private function check_rate_limit() {
        $limit = get_option('getso_forms_ai_requests_per_hour', 10);
        $requests = get_transient('getso_forms_ai_requests');
        
        if ($requests === false) {
            set_transient('getso_forms_ai_requests', 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($requests >= $limit) {
            return false;
        }
        
        set_transient('getso_forms_ai_requests', $requests + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Guardar en historial
     */
    private function save_to_history($form_id, $prompt, $css) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('ai_history');
        
        $wpdb->insert($table, array(
            'form_id' => $form_id,
            'prompt' => $prompt,
            'generated_css' => $css,
            'provider' => $this->provider,
            'model' => $this->model,
            'tokens_used' => 0
        ));
    }
    
    /**
     * AJAX: Generar CSS
     */
    public function ajax_generate_css_ai() {
        // Esta función se llama desde el editor de formularios.
        // El constructor ya cargó la API key guardada en la base de datos.
        // No necesitamos hacer nada más aquí.
        
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt vacío'));
            return;
        }
        
        $css = $this->generate_css($form_id, $prompt);
        
        if (is_wp_error($css)) {
            wp_send_json_error(array('message' => $css->get_error_message()));
        } else {
            wp_send_json_success(array('css' => $css));
        }
    }
    
    /**
     * AJAX: Test de conexión
     */
    public function ajax_test_ai_connection() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }

        // CORRECCIÓN 3: Leer los datos que envía el JavaScript de la página de Configuración
        // Esto permite probar la conexión ANTES de guardar.
        $this->provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : $this->provider;
        $this->model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : $this->model;
        $this->api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : $this->api_key;

        // Si la clave de la POST data está vacía, intentar cargar la específica (por si acaso)
        if (empty($this->api_key) && !empty($this->provider)) {
             $this->api_key = get_option('getso_forms_ai_api_key_' . $this->provider, '');
        }

        if (empty($this->api_key)) {
            wp_send_json_error(array('message' => '❌ API key no proporcionada'));
            return;
        }
        
        $test_prompt = 'Responde solo con: OK';
        $response = $this->call_ai($test_prompt);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => '❌ Error: ' . $response->get_error_message()));
        } else {
            // Limpiar la respuesta por si la IA añade markdown
            $clean_response = trim(str_replace('`', '', $response));
            if (strtoupper($clean_response) === 'OK') {
                wp_send_json_success(array('message' => '✅ Conexión exitosa con ' . $this->provider));
            } else {
                wp_send_json_error(array('message' => '❌ Error: La IA respondió, pero no con "OK". Respuesta: ' . $clean_response));
            }
        }
    }
    
    /**
     * AJAX: Aplicar template CSS
     */
    public function ajax_apply_css_template() {
        // Esta función también usa la configuración guardada (cargada en el constructor)
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        $template = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : '';
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $prompts = array(
            'corporativo' => 'Diseño corporativo profesional con colores azul marino y gris, aspecto formal',
            'minimalista' => 'Diseño minimalista ultra limpio con mucho espacio en blanco, bordes sutiles',
            'moderno' => 'Diseño moderno con degradados vibrantes, sombras suaves y animaciones',
            'oscuro' => 'Tema oscuro elegante con fondo negro, acentos en color neón o púrpura'
        );
        
        if (!isset($prompts[$template])) {
            wp_send_json_error(array('message' => 'Template no válido'));
            return;
        }
        
        $css = $this->generate_css($form_id, $prompts[$template]);
        
        if (is_wp_error($css)) {
            wp_send_json_error(array('message' => $css->get_error_message()));
        } else {
            wp_send_json_success(array('css' => $css));
        }
    }
}