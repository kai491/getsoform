<?php
/**
 * Clase Webhooks
 * Maneja el envío de datos a webhooks externos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Webhooks {
    
    /**
     * Enviar datos a webhooks
     */
    public function send($form_settings, $form_data, $submission_id) {
        if (!isset($form_settings['webhooks']) || $form_settings['webhooks']['enabled'] !== true) {
            return array('success' => true, 'message' => 'Webhooks desactivados');
        }
        
        $webhooks = $form_settings['webhooks'];
        $mode = $webhooks['mode'] ?? 'production';
        
        $primary_url = $mode === 'test' ? ($webhooks['primary_test'] ?? '') : ($webhooks['primary_prod'] ?? '');
        $secondary_url = $mode === 'test' ? ($webhooks['secondary_test'] ?? '') : ($webhooks['secondary_prod'] ?? '');
        
        $timeout = isset($webhooks['timeout']) ? intval($webhooks['timeout']) : 15;
        
        $results = array(
            'primary' => null,
            'secondary' => null
        );
        
        // Webhook primario
        if (!empty($primary_url)) {
            $results['primary'] = $this->send_to_url($primary_url, $form_data, $timeout);
            $this->update_submission_webhook($submission_id, 'primary', $results['primary']);
        }
        
        // Webhook secundario
        if (!empty($secondary_url)) {
            $results['secondary'] = $this->send_to_url($secondary_url, $form_data, $timeout);
            $this->update_submission_webhook($submission_id, 'secondary', $results['secondary']);
        }
        
        return $results;
    }
    
    /**
     * Enviar a URL específica
     */
    private function send_to_url($url, $data, $timeout) {
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($data),
            'timeout' => $timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        return array(
            'success' => $code >= 200 && $code < 300,
            'code' => $code,
            'response' => $body
        );
    }
    
    /**
     * Actualizar estado de webhook en envío
     */
    private function update_submission_webhook($submission_id, $type, $result) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $status_field = 'webhook_' . $type . '_status';
        $response_field = 'webhook_' . $type . '_response';
        
        $wpdb->update(
            $table,
            array(
                $status_field => $result['success'] ? 'success' : 'error',
                $response_field => isset($result['response']) ? $result['response'] : $result['message']
            ),
            array('id' => $submission_id)
        );
    }
    
    /**
     * AJAX: Test de webhooks
     */
    public function ajax_test_webhooks() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        $url = isset($_POST['webhook_url']) ? esc_url_raw($_POST['webhook_url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => 'URL no proporcionada'));
            return;
        }
        
        $test_data = array(
            'test' => true,
            'nombre' => 'Test Usuario',
            'email' => 'test@test.com',
            'mensaje' => 'Este es un mensaje de prueba'
        );
        
        $result = $this->send_to_url($url, $test_data, 15);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => '✅ Webhook OK (HTTP ' . $result['code'] . ')'));
        } else {
            wp_send_json_error(array('message' => '❌ Error: ' . $result['message']));
        }
    }
}
