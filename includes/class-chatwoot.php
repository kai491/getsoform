<?php
/**
 * Clase Chatwoot
 * Integración con Chatwoot CRM
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Chatwoot {
    
    /**
     * Enviar contacto a Chatwoot
     */
    public function send($form_settings, $form_data, $submission_id) {
        if (!isset($form_settings['chatwoot']) || $form_settings['chatwoot']['enabled'] !== true) {
            return array('success' => true, 'message' => 'Chatwoot desactivado');
        }
        
        $config = $form_settings['chatwoot'];
        
        $url = rtrim($config['url'] ?? '', '/');
        $api_token = $config['api_token'] ?? '';
        $account_id = $config['account_id'] ?? '';
        $inbox_id = $config['inbox_id'] ?? '';
        
        if (empty($url) || empty($api_token) || empty($account_id)) {
            return array('success' => false, 'message' => 'Configuración incompleta');
        }
        
        // Crear o actualizar contacto
        $contact_result = $this->create_or_update_contact($url, $api_token, $account_id, $form_data);
        
        if (!$contact_result['success']) {
            $this->update_submission_chatwoot($submission_id, $contact_result);
            return $contact_result;
        }
        
        $contact_id = $contact_result['contact_id'];
        
        // Crear conversación si está habilitado
        if (($config['create_conversation'] ?? true) && !empty($inbox_id)) {
            $conversation_result = $this->create_conversation($url, $api_token, $account_id, $contact_id, $inbox_id, $form_data);
            
            if ($conversation_result['success']) {
                $contact_result['conversation_id'] = $conversation_result['conversation_id'];
            }
        }
        
        $this->update_submission_chatwoot($submission_id, $contact_result);
        
        return $contact_result;
    }
    
    /**
     * Crear o actualizar contacto
     */
    private function create_or_update_contact($url, $api_token, $account_id, $form_data) {
        $email = $form_data['email'] ?? $form_data['correo'] ?? '';
        
        if (empty($email)) {
            return array('success' => false, 'message' => 'Email requerido');
        }
        
        // Buscar contacto existente
        $search_response = wp_remote_get(
            "{$url}/api/v1/accounts/{$account_id}/contacts/search?q={$email}",
            array(
                'headers' => array(
                    'api_access_token' => $api_token,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 15
            )
        );
        
        if (!is_wp_error($search_response)) {
            $search_body = json_decode(wp_remote_retrieve_body($search_response), true);
            
            if (!empty($search_body['payload']) && count($search_body['payload']) > 0) {
                $contact_id = $search_body['payload'][0]['id'];
                
                // Actualizar contacto existente
                $contact_data = $this->prepare_contact_data($form_data);
                
                wp_remote_request(
                    "{$url}/api/v1/accounts/{$account_id}/contacts/{$contact_id}",
                    array(
                        'method' => 'PUT',
                        'headers' => array(
                            'api_access_token' => $api_token,
                            'Content-Type' => 'application/json'
                        ),
                        'body' => wp_json_encode($contact_data),
                        'timeout' => 15
                    )
                );
                
                return array('success' => true, 'contact_id' => $contact_id);
            }
        }
        
        // Crear nuevo contacto
        $contact_data = $this->prepare_contact_data($form_data);
        
        $create_response = wp_remote_post(
            "{$url}/api/v1/accounts/{$account_id}/contacts",
            array(
                'headers' => array(
                    'api_access_token' => $api_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($contact_data),
                'timeout' => 15
            )
        );
        
        if (is_wp_error($create_response)) {
            return array('success' => false, 'message' => $create_response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($create_response);
        $response_body = json_decode(wp_remote_retrieve_body($create_response), true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array('success' => true, 'contact_id' => $response_body['payload']['contact']['id']);
        }
        
        return array('success' => false, 'message' => 'Error al crear contacto');
    }
    
    /**
     * Preparar datos del contacto
     */
    private function prepare_contact_data($form_data) {
        $contact_data = array(
            'name' => $form_data['nombre'] ?? $form_data['name'] ?? '',
            'email' => $form_data['email'] ?? $form_data['correo'] ?? '',
            'phone_number' => $form_data['telefono'] ?? $form_data['phone'] ?? ''
        );
        
        // Atributos personalizados
        $custom_attributes = array();
        
        foreach ($form_data as $key => $value) {
            if (!in_array($key, array('nombre', 'name', 'email', 'correo', 'telefono', 'phone'))) {
                $custom_attributes[$key] = $value;
            }
        }
        
        if (!empty($custom_attributes)) {
            $contact_data['custom_attributes'] = $custom_attributes;
        }
        
        return $contact_data;
    }
    
    /**
     * Crear conversación
     */
    private function create_conversation($url, $api_token, $account_id, $contact_id, $inbox_id, $form_data) {
        $conversation_data = array(
            'contact_id' => $contact_id,
            'inbox_id' => intval($inbox_id)
        );
        
        $create_response = wp_remote_post(
            "{$url}/api/v1/accounts/{$account_id}/conversations",
            array(
                'headers' => array(
                    'api_access_token' => $api_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($conversation_data),
                'timeout' => 15
            )
        );
        
        if (is_wp_error($create_response)) {
            return array('success' => false, 'message' => $create_response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($create_response);
        $response_body = json_decode(wp_remote_retrieve_body($create_response), true);
        
        if ($response_code >= 200 && $response_code < 300) {
            $conversation_id = $response_body['id'];
            
            // Enviar mensaje con datos del formulario
            $message = $this->format_message($form_data);
            
            wp_remote_post(
                "{$url}/api/v1/accounts/{$account_id}/conversations/{$conversation_id}/messages",
                array(
                    'headers' => array(
                        'api_access_token' => $api_token,
                        'Content-Type' => 'application/json'
                    ),
                    'body' => wp_json_encode(array(
                        'content' => $message,
                        'message_type' => 'incoming',
                        'private' => false
                    )),
                    'timeout' => 15
                )
            );
            
            return array('success' => true, 'conversation_id' => $conversation_id);
        }
        
        return array('success' => false, 'message' => 'Error al crear conversación');
    }
    
    /**
     * Formatear mensaje con datos del formulario
     */
    private function format_message($form_data) {
        $message = "📋 **Nuevo contacto desde formulario web**\n\n";
        
        foreach ($form_data as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $message .= "• **{$label}:** {$value}\n";
        }
        
        return $message;
    }
    
    /**
     * Actualizar estado Chatwoot en envío
     */
    private function update_submission_chatwoot($submission_id, $result) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $update_data = array(
            'chatwoot_status' => $result['success'] ? 'success' : 'error',
            'chatwoot_response' => wp_json_encode($result)
        );
        
        if (isset($result['contact_id'])) {
            $update_data['chatwoot_contact_id'] = $result['contact_id'];
        }
        
        if (isset($result['conversation_id'])) {
            $update_data['chatwoot_conversation_id'] = $result['conversation_id'];
        }
        
        $wpdb->update($table, $update_data, array('id' => $submission_id));
    }
    
    /**
     * AJAX: Enviar a Chatwoot
     */
    public function ajax_send_to_chatwoot() {
        check_ajax_referer('getso_forms_public_nonce', 'nonce');
        
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        $form_settings = isset($_POST['form_settings']) ? $_POST['form_settings'] : array();
        
        $result = $this->send($form_settings, $form_data, $submission_id);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Test de conexión
     */
    public function ajax_test_connection() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        $url = rtrim(sanitize_text_field($_POST['chatwoot_url'] ?? ''), '/');
        $api_token = sanitize_text_field($_POST['chatwoot_api_token'] ?? '');
        $account_id = sanitize_text_field($_POST['chatwoot_account_id'] ?? '');
        
        if (empty($url) || empty($api_token) || empty($account_id)) {
            wp_send_json_error(array('message' => '❌ Datos incompletos'));
            return;
        }
        
        $response = wp_remote_get(
            "{$url}/api/v1/accounts/{$account_id}",
            array(
                'headers' => array('api_access_token' => $api_token),
                'timeout' => 15
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => '❌ Error: ' . $response->get_error_message()));
            return;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 200 && $code < 300) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $account_name = $body['name'] ?? 'Desconocido';
            wp_send_json_success(array('message' => "✅ Conexión exitosa! Cuenta: {$account_name}"));
        } else {
            wp_send_json_error(array('message' => "❌ Error HTTP {$code}"));
        }
    }
}
