<?php
/**
 * Clase Submissions
 * Maneja el guardado y procesamiento de envíos de formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Submissions {
    
    /**
     * Guardar envío
     */
    public function save_submission($form_id, $form_data, $metadata = array()) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $insert_data = array(
            'form_id' => $form_id,
            'form_data' => wp_json_encode($form_data),
            'clicked_button' => !empty($metadata['clicked_button']) ? $metadata['clicked_button'] : 'submit',
            'user_agent' => !empty($metadata['user_agent']) ? $metadata['user_agent'] : '',
            'ip_address' => $this->get_client_ip(),
            'status' => 'pending'
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error al guardar envío', 'getso-forms'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Actualizar envío
     */
    public function update_submission($submission_id, $data) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $result = $wpdb->update($table, $data, array('id' => $submission_id));
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar', 'getso-forms'));
        }
        
        return true;
    }
    
    /**
     * Obtener envío
     */
    public function get_submission($id) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        if ($submission) {
            $submission->form_data = json_decode($submission->form_data, true);
        }
        
        return $submission;
    }
    
    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * AJAX: Guardar envío
     */
    public function ajax_save_submission() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'getso_forms_public_nonce')) {
            wp_send_json_error(array('message' => 'Nonce inválido'));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form_data_raw = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        // CORRECCIÓN: form-handler.js envía JSON.stringify(data), debemos parsearlo
        if (is_string($form_data_raw)) {
            $form_data = json_decode(stripslashes($form_data_raw), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => 'Error al decodificar datos JSON'));
                return;
            }
        } else {
            $form_data = $form_data_raw;
        }

        if (!$form_id || empty($form_data)) {
            wp_send_json_error(array('message' => 'Datos incompletos'));
            return;
        }

        // Sanitizar datos
        $sanitized_data = array();
        foreach ($form_data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        
        $metadata = array(
            'clicked_button' => isset($_POST['clicked_button']) ? sanitize_text_field($_POST['clicked_button']) : 'submit',
            'user_agent' => isset($_POST['user_agent']) ? sanitize_text_field($_POST['user_agent']) : ''
        );
        
        $submission_id = $this->save_submission($form_id, $sanitized_data, $metadata);
        
        if (is_wp_error($submission_id)) {
            wp_send_json_error(array('message' => $submission_id->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => 'Envío guardado',
                'submission_id' => $submission_id
            ));
        }
    }
    
    /**
     * AJAX: Actualizar envío
     */
    public function ajax_update_submission() {
        check_ajax_referer('getso_forms_public_nonce', 'nonce');
        
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $update_data = isset($_POST['update_data']) ? $_POST['update_data'] : array();
        
        $result = $this->update_submission($submission_id, $update_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => 'Actualizado'));
        }
    }
    
    /**
     * AJAX: Obtener detalles de envío
     */
    public function ajax_get_submission_details() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $submission = $this->get_submission($id);
        
        if (!$submission) {
            wp_send_json_error(array('message' => 'No encontrado'));
            return;
        }
        
        ob_start();
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/submission-details.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * AJAX: Eliminar envío
     */
    public function ajax_delete_submission() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
            return;
        }
        
        global $wpdb;
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $table = Getso_Forms_Database::get_table_name('submissions');
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Eliminado'));
        } else {
            wp_send_json_error(array('message' => 'Error al eliminar'));
        }
    }
}
