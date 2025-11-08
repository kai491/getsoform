# Getso Forms - Guía de Implementación AJAX Handlers

Este documento contiene los 8 AJAX handlers que debes implementar en tu plugin principal para que todas las funcionalidades trabajen correctamente.

---

## 1. AI CSS Generator

```php
/**
 * AJAX Handler: Generar CSS con IA
 * Action: getso_forms_ai_generate_css
 */
function getso_forms_ai_generate_css_handler() {
    // Verificar nonce
    check_ajax_referer('getso_forms_editor_ajax', 'nonce');
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    // Obtener datos
    $prompt = sanitize_textarea_field($_POST['prompt']);
    $current_css = sanitize_textarea_field($_POST['current_css']);
    $form_html = wp_kses_post($_POST['form_html']);
    $conversation_history = isset($_POST['conversation_history']) ? json_decode(stripslashes($_POST['conversation_history']), true) : [];
    
    // Obtener settings de IA
    $ai_provider = get_option('getso_forms_ai_provider', 'claude'); // claude|openai|gemini
    $api_key = get_option('getso_forms_ai_api_key_' . $ai_provider);
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key no configurada. Ve a Settings.']);
    }
    
    // Construir prompt completo
    $system_prompt = "Eres un experto en diseño web y CSS. Tu tarea es generar CSS profesional para formularios HTML basándote en las instrucciones del usuario.";
    
    $full_prompt = "HTML del formulario:\n{$form_html}\n\n";
    $full_prompt .= "CSS actual:\n{$current_css}\n\n";
    $full_prompt .= "Instrucciones del usuario:\n{$prompt}\n\n";
    $full_prompt .= "Genera SOLO el CSS, sin explicaciones. Respeta las clases existentes: .getso-form, .getso-input, .getso-field-label, etc.";
    
    try {
        // Llamar a la IA según el provider
        $ai_generator = new Getso_Forms_AI_Generator();
        $result = $ai_generator->generate_css([
            'provider' => $ai_provider,
            'api_key' => $api_key,
            'prompt' => $full_prompt,
            'history' => $conversation_history
        ]);
        
        if ($result['success']) {
            wp_send_json_success([
                'css' => $result['css'],
                'explanation' => $result['explanation'] ?? 'CSS generado exitosamente.'
            ]);
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_getso_forms_ai_generate_css', 'getso_forms_ai_generate_css_handler');
```

---

## 2. Save CSS

```php
/**
 * AJAX Handler: Guardar CSS del formulario
 * Action: getso_forms_save_css
 */
function getso_forms_save_css_handler() {
    check_ajax_referer('getso_forms_editor_ajax', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $form_id = intval($_POST['form_id']);
    $custom_css = sanitize_textarea_field($_POST['custom_css']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'getso_forms';
    
    $updated = $wpdb->update(
        $table_name,
        ['custom_css' => $custom_css],
        ['id' => $form_id],
        ['%s'],
        ['%d']
    );
    
    if ($updated !== false) {
        wp_send_json_success(['message' => 'CSS guardado correctamente']);
    } else {
        wp_send_json_error(['message' => 'Error al guardar CSS']);
    }
}
add_action('wp_ajax_getso_forms_save_css', 'getso_forms_save_css_handler');
```

---

## 3. Duplicate Form

```php
/**
 * AJAX Handler: Duplicar formulario
 * Action: getso_forms_duplicate_form
 */
function getso_forms_duplicate_form_handler() {
    check_ajax_referer('getso_forms_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $form_id = intval($_POST['form_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'getso_forms';
    
    // Obtener formulario original
    $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id), ARRAY_A);
    
    if (!$original) {
        wp_send_json_error(['message' => 'Formulario no encontrado']);
    }
    
    // Crear copia
    unset($original['id']);
    $original['name'] = $original['name'] . ' (Copia)';
    $original['created_at'] = current_time('mysql');
    
    $inserted = $wpdb->insert($table_name, $original);
    
    if ($inserted) {
        $new_form_id = $wpdb->insert_id;
        $edit_url = admin_url('admin.php?page=getso-forms-editor&form_id=' . $new_form_id);
        
        wp_send_json_success([
            'message' => 'Formulario duplicado',
            'new_form_id' => $new_form_id,
            'edit_url' => $edit_url
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al duplicar formulario']);
    }
}
add_action('wp_ajax_getso_forms_duplicate_form', 'getso_forms_duplicate_form_handler');
```

---

## 4. Delete Form

```php
/**
 * AJAX Handler: Eliminar formulario
 * Action: getso_forms_delete_form
 */
function getso_forms_delete_form_handler() {
    check_ajax_referer('getso_forms_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $form_id = intval($_POST['form_id']);
    
    global $wpdb;
    $forms_table = $wpdb->prefix . 'getso_forms';
    $submissions_table = $wpdb->prefix . 'getso_form_submissions';
    
    // Eliminar envíos asociados primero
    $wpdb->delete($submissions_table, ['form_id' => $form_id], ['%d']);
    
    // Eliminar formulario
    $deleted = $wpdb->delete($forms_table, ['id' => $form_id], ['%d']);
    
    if ($deleted) {
        wp_send_json_success(['message' => 'Formulario eliminado correctamente']);
    } else {
        wp_send_json_error(['message' => 'Error al eliminar formulario']);
    }
}
add_action('wp_ajax_getso_forms_delete_form', 'getso_forms_delete_form_handler');
```

---

## 5. Toggle Active

```php
/**
 * AJAX Handler: Activar/Desactivar formulario
 * Action: getso_forms_toggle_active
 */
function getso_forms_toggle_active_handler() {
    check_ajax_referer('getso_forms_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $form_id = intval($_POST['form_id']);
    $active = intval($_POST['active']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'getso_forms';
    
    $updated = $wpdb->update(
        $table_name,
        ['active' => $active],
        ['id' => $form_id],
        ['%d'],
        ['%d']
    );
    
    if ($updated !== false) {
        $status = $active ? 'activado' : 'desactivado';
        wp_send_json_success(['message' => "Formulario {$status}"]);
    } else {
        wp_send_json_error(['message' => 'Error al cambiar estado']);
    }
}
add_action('wp_ajax_getso_forms_toggle_active', 'getso_forms_toggle_active_handler');
```

---

## 6. Get Submission Details

```php
/**
 * AJAX Handler: Obtener detalles de un envío
 * Action: getso_forms_get_submission_details
 */
function getso_forms_get_submission_details_handler() {
    check_ajax_referer('getso_forms_submission_details', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $submission_id = intval($_POST['submission_id']);
    
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'getso_form_submissions';
    $forms_table = $wpdb->prefix . 'getso_forms';
    
    $submission = $wpdb->get_row($wpdb->prepare("
        SELECT 
            s.*,
            f.name as form_name
        FROM {$submissions_table} s
        LEFT JOIN {$forms_table} f ON s.form_id = f.id
        WHERE s.id = %d
    ", $submission_id), ARRAY_A);
    
    if (!$submission) {
        wp_send_json_error(['message' => 'Envío no encontrado']);
    }
    
    wp_send_json_success($submission);
}
add_action('wp_ajax_getso_forms_get_submission_details', 'getso_forms_get_submission_details_handler');
```

---

## 7. Delete Submission

```php
/**
 * AJAX Handler: Eliminar envío
 * Action: getso_forms_delete_submission
 */
function getso_forms_delete_submission_handler() {
    check_ajax_referer('getso_forms_delete_submission', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $submission_id = intval($_POST['submission_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'getso_form_submissions';
    
    $deleted = $wpdb->delete($table_name, ['id' => $submission_id], ['%d']);
    
    if ($deleted) {
        wp_send_json_success(['message' => 'Envío eliminado correctamente']);
    } else {
        wp_send_json_error(['message' => 'Error al eliminar envío']);
    }
}
add_action('wp_ajax_getso_forms_delete_submission', 'getso_forms_delete_submission_handler');
```

---

## 8. Bulk Delete Submissions

```php
/**
 * AJAX Handler: Eliminar múltiples envíos
 * Action: getso_forms_bulk_delete_submissions
 */
function getso_forms_bulk_delete_submissions_handler() {
    check_ajax_referer('getso_forms_bulk_delete', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permisos insuficientes']);
    }
    
    $submission_ids = isset($_POST['submission_ids']) ? array_map('intval', $_POST['submission_ids']) : [];
    
    if (empty($submission_ids)) {
        wp_send_json_error(['message' => 'No se seleccionaron envíos']);
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'getso_form_submissions';
    
    $placeholders = implode(',', array_fill(0, count($submission_ids), '%d'));
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE id IN ({$placeholders})",
        $submission_ids
    ));
    
    if ($deleted !== false) {
        wp_send_json_success([
            'message' => "{$deleted} envíos eliminados",
            'count' => $deleted
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al eliminar envíos']);
    }
}
add_action('wp_ajax_getso_forms_bulk_delete_submissions', 'getso_forms_bulk_delete_submissions_handler');
```

---

## BONUS: Export CSV

```php
/**
 * AJAX Handler: Exportar envíos a CSV
 * Action: getso_forms_export_csv
 */
function getso_forms_export_csv_handler() {
    check_ajax_referer('getso_forms_export_csv', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Permisos insuficientes');
    }
    
    // Obtener filtros de la URL
    $filter_form_id = isset($_GET['filter_form']) ? intval($_GET['filter_form']) : 0;
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
    $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
    
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'getso_form_submissions';
    $forms_table = $wpdb->prefix . 'getso_forms';
    
    // Construir query con filtros
    $where_clauses = ['1=1'];
    $query_params = [];
    
    if ($filter_form_id > 0) {
        $where_clauses[] = 's.form_id = %d';
        $query_params[] = $filter_form_id;
    }
    
    if (!empty($filter_status)) {
        $where_clauses[] = 's.webhook_primary_status = %s';
        $query_params[] = $filter_status;
    }
    
    if (!empty($filter_date_from)) {
        $where_clauses[] = 'DATE(s.submitted_at) >= %s';
        $query_params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_clauses[] = 'DATE(s.submitted_at) <= %s';
        $query_params[] = $filter_date_to;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $query = "
        SELECT 
            s.*,
            f.name as form_name
        FROM {$submissions_table} s
        LEFT JOIN {$forms_table} f ON s.form_id = f.id
        WHERE {$where_sql}
        ORDER BY s.submitted_at DESC
    ";
    
    if (!empty($query_params)) {
        $submissions = $wpdb->get_results($wpdb->prepare($query, $query_params), ARRAY_A);
    } else {
        $submissions = $wpdb->get_results($query, ARRAY_A);
    }
    
    // Configurar headers para descarga
    $filename = 'getso-forms-submissions-' . date('Y-m-d-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear archivo CSV
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para Excel)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, [
        'ID',
        'Formulario',
        'Fecha',
        'Datos',
        'Webhook Primario',
        'Webhook Secundario',
        'Chatwoot Contact ID',
        'IP',
        'User Agent'
    ]);
    
    // Datos
    foreach ($submissions as $submission) {
        fputcsv($output, [
            $submission['id'],
            $submission['form_name'],
            $submission['submitted_at'],
            $submission['form_data'],
            $submission['webhook_primary_status'],
            $submission['webhook_secondary_status'],
            $submission['chatwoot_contact_id'],
            $submission['ip_address'],
            $submission['user_agent']
        ]);
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_getso_forms_export_csv', 'getso_forms_export_csv_handler');
```

---

## Notas de Implementación

### Seguridad
- ✅ Todos los handlers verifican nonce
- ✅ Todos verifican permisos `manage_options`
- ✅ Sanitización de inputs
- ✅ Prepared statements en queries SQL

### Variables Globales JavaScript Requeridas

En el archivo que enqueue los scripts admin, asegúrate de localizar:

```php
wp_localize_script('getso-forms-manager', 'getsoFormsData', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('getso_forms_nonce')
]);

wp_localize_script('getso-forms-editor', 'getsoFormsEditor', [
    'formId' => $form_id,
    'fields' => $form_fields,
    'nonce' => wp_create_nonce('getso_forms_editor_ajax'),
    'ajaxUrl' => admin_url('admin-ajax.php')
]);
```

### Testing

Para probar cada handler, puedes usar la consola del navegador:

```javascript
// Ejemplo: Test duplicate form
jQuery.post(ajaxurl, {
    action: 'getso_forms_duplicate_form',
    nonce: getsoFormsData.nonce,
    form_id: 1
}, function(response) {
    console.log(response);
});
```

---

## Orden de Implementación Recomendado

1. **getso_forms_save_css** (más simple)
2. **getso_forms_toggle_active** (simple)
3. **getso_forms_duplicate_form** (mediano)
4. **getso_forms_delete_form** (mediano)
5. **getso_forms_get_submission_details** (mediano)
6. **getso_forms_delete_submission** (simple)
7. **getso_forms_bulk_delete_submissions** (mediano)
8. **getso_forms_ai_generate_css** (complejo, requiere AI Generator)
9. **getso_forms_export_csv** (bonus, mediano)

---

**Archivo:** `includes/class-ajax-handlers.php`  
**O agregar directo en:** `getso-forms.php` (main plugin file)

¡Listo para integrar! 🚀
