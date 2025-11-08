<?php
/**
 * Form Editor View
 *
 * Vista del editor de formularios con drag & drop, preview y tabs
 *
 * @package Getso_Forms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function para renderizar item de campo (usado en el loop PHP arriba)
if (!function_exists('render_field_item')) {
    function render_field_item($field, $index) {
        // Asegurarse de que $field es un array, no un string JSON
        if (is_string($field)) {
            $field = json_decode($field, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Manejar error de JSON malformado si es necesario
                return ''; 
            }
        }
        
        ob_start();
        ?>
        <div class="getso-field-item" data-index="<?php echo intval($index); ?>">
            <div class="field-item-header">
                <span class="field-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </span>
                <span class="field-type-badge"><?php echo esc_html($field['type'] ?? 'text'); ?></span>
                <strong><?php echo esc_html($field['label'] ?: ($field['name'] ?? 'campo_'. $index)); ?></strong>
                <?php if ($field['required'] ?? false): ?>
                    <span class="required-badge">Requerido</span>
                <?php endif; ?>
            </div>
            <div class="field-item-actions">
                <button type="button" class="button button-small edit-field-btn" data-index="<?php echo intval($index); ?>">
                    <span class="dashicons dashicons-edit"></span>
                    Editar
                </button>
                <button type="button" class="button button-small delete-field-btn" data-index="<?php echo intval($index); ?>">
                    <span class="dashicons dashicons-trash"></span>
                    Eliminar
                </button>
            </div>
            <!-- CORRECCIÓN: Aseguramos que el valor sea un string JSON con wp_unslash para manejar ' -->
            <input type="hidden" name="fields[]" value='<?php echo esc_attr(wp_json_encode($field)); ?>'>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'getso_forms';

// --- INICIO DE LA CORRECCIÓN ---

// --- BLOQUE DE LÓGICA DE GUARDADO ---
// CORRECCIÓN: Primero, revisamos si el formulario se está enviando (POST)
if (isset($_POST['action']) && $_POST['action'] === 'getso_forms_save_form') {

    // CORRECCIÓN: La verificación del nonce AHORA se hace aquí, SÓLO al guardar.
    // Esto es seguro y no bloqueará la carga de la página.
    check_admin_referer('getso_forms_save_form', 'getso_forms_editor_nonce');

    // 1. Recolectar y sanitizar datos básicos
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $form_name = sanitize_text_field($_POST['form_name']);
    $form_description = sanitize_textarea_field($_POST['form_description']);
    $form_active = isset($_POST['form_active']) ? 1 : 0;
    
    // 2. Recolectar campos (como un array de strings JSON)
    // El JavaScript debe poblar estos campos antes de enviar
    $fields_array = isset($_POST['fields']) ? array_values(wp_unslash($_POST['fields'])) : [];
    $fields_json = json_encode($fields_array);

    // 3. Recolectar CSS
    $custom_css = sanitize_textarea_field($_POST['custom_css']);

    // 4. Recolectar Configuración General
    $settings = [
        'submit_button_text' => sanitize_text_field($_POST['submit_button_text'] ?? 'Enviar'),
        'success_message' => sanitize_textarea_field($_POST['success_message'] ?? '¡Gracias! Tu mensaje ha sido enviado correctamente.'),
        'error_message' => sanitize_textarea_field($_POST['error_message'] ?? 'Ha ocurrido un error. Por favor intenta nuevamente.'),
        'redirect_url' => esc_url_raw($_POST['redirect_url'] ?? ''),
        'enable_captcha' => isset($_POST['enable_captcha']) ? 1 : 0,
        'store_submissions' => isset($_POST['store_submissions']) ? 1 : 0,
    ];
    $settings_json = json_encode($settings);

    // 5. Recolectar Configuración de Webhooks
    $webhook_config = [
        'primary' => [
            'url' => esc_url_raw($_POST['webhook_primary_url'] ?? ''),
            'enabled' => isset($_POST['webhook_primary_enabled']) ? 1 : 0,
        ],
        'secondary' => [
            'url' => esc_url_raw($_POST['webhook_secondary_url'] ?? ''),
            'enabled' => isset($_POST['webhook_secondary_enabled']) ? 1 : 0,
        ],
    ];
    $webhook_config_json = json_encode($webhook_config);

    // 6. Recolectar Configuración de Chatwoot
    $chatwoot_config = [
        'enabled' => isset($_POST['chatwoot_enabled']) ? 1 : 0,
        'account_id' => sanitize_text_field($_POST['chatwoot_account_id'] ?? ''),
        'inbox_id' => sanitize_text_field($_POST['chatwoot_inbox_id'] ?? ''),
        'create_contact' => isset($_POST['chatwoot_create_contact']) ? 1 : 0,
        'create_conversation' => isset($_POST['chatwoot_create_conversation']) ? 1 : 0,
    ];
    $chatwoot_config_json = json_encode($chatwoot_config);

    // 7. Preparar datos para la BD
    $data_to_save = [
        'name' => $form_name,
        'description' => $form_description,
        'active' => $form_active,
        'fields' => $fields_json,
        'custom_css' => $custom_css,
        'settings' => $settings_json,
        'webhook_config' => $webhook_config_json,
        'chatwoot_config' => $chatwoot_config_json,
    ];
    
    $format = ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'];

    // 8. Insertar o Actualizar en la BD
    if ($form_id > 0) {
        // Actualizar formulario existente
        $wpdb->update($table_name, $data_to_save, ['id' => $form_id], $format, ['%d']);
        $redirect_id = $form_id;
    } else {
        // Crear nuevo formulario
        $data_to_save['created_at'] = current_time('mysql');
        $format[] = '%s';
        
        $wpdb->insert($table_name, $data_to_save, $format);
        $redirect_id = $wpdb->insert_id;
    }

    // 9. Redirigir para prevenir re-envío (Patrón PRG)
    // Se añade &message=1 para mostrar un aviso de "Guardado"
    $redirect_url = admin_url('admin.php?page=getso-forms-edit&form_id=' . $redirect_id . '&message=1');
    wp_safe_redirect($redirect_url);
    exit;

} // --- FIN DEL BLOQUE DE LÓGICA DE GUARDADO ---


// --- BLOQUE DE LÓGICA DE VISUALIZACIÓN (GET) ---

// CORRECCIÓN: El nonce 'check_admin_referer' que estaba aquí (línea 18) se ha eliminado.
// Ahora la página cargará sin el error "El enlace ha caducado".


// CORRECCIÓN: Mostrar un aviso de "Guardado con éxito" si venimos de la redirección
if (isset($_GET['message']) && $_GET['message'] === '1') {
    echo '<div class="notice notice-success is-dismissible"><p>' . __('¡Formulario guardado con éxito!', 'getso-forms') . '</p></div>';
}


// Obtener ID del formulario de la URL (para Cargar o para Nuevo)
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// Cargar formulario si existe
$form_data = null;
$default_settings = [
    'submit_button_text' => 'Enviar',
    'success_message' => '¡Gracias! Tu mensaje ha sido enviado correctamente.',
    'error_message' => 'Ha ocurrido un error. Por favor intenta nuevamente.',
    'redirect_url' => '',
    'enable_captcha' => 0,
    'store_submissions' => 1,
];
$default_webhooks = [
    'primary' => ['url' => '', 'enabled' => 0],
    'secondary' => ['url' => '', 'enabled' => 0],
];
$default_chatwoot = [
    'enabled' => 0,
    'account_id' => '',
    'inbox_id' => '',
    'create_contact' => 1,
    'create_conversation' => 1,
];

if ($form_id > 0) {
    $form_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id), ARRAY_A);
    
    if (!$form_data) {
        wp_die(__('Formulario no encontrado.'));
    }
    
    // Decodificar JSON
    // Se usan array_merge para asegurar que siempre existan las claves, incluso si se añadieron en una nueva versión
    $form_data['fields'] = json_decode($form_data['fields'], true) ?: [];
    $form_data['settings'] = array_merge($default_settings, json_decode($form_data['settings'], true) ?: []);
    $form_data['webhook_config'] = array_merge($default_webhooks, json_decode($form_data['webhook_config'], true) ?: []);
    $form_data['chatwoot_config'] = array_merge($default_chatwoot, json_decode($form_data['chatwoot_config'], true) ?: []);
    
} else {
    // Rellenar datos por defecto para un formulario nuevo
    $form_data = [
        'id' => 0,
        'name' => '',
        'description' => '',
        'active' => 1,
        'fields' => [],
        'custom_css' => '',
        'settings' => $default_settings,
        'webhook_config' => $default_webhooks,
        'chatwoot_config' => $default_chatwoot,
    ];
}

$is_new = $form_id === 0;
$page_title = $is_new ? 'Crear Formulario' : 'Editar Formulario: ' . esc_html($form_data['name']);
?>

<div class="wrap getso-forms-editor">
    <h1><?php echo $page_title; ?></h1>

    <form id="getso-form-editor" method="post">
        <?php wp_nonce_field('getso_forms_save_form', 'getso_forms_editor_nonce'); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <input type="hidden" name="action" value="getso_forms_save_form">

        <!-- Tabs Navigation -->
        <nav class="nav-tab-wrapper getso-tabs">
            <a href="#tab-fields" class="nav-tab nav-tab-active">
                <span class="dashicons dashicons-editor-table"></span>
                Campos
            </a>
            <a href="#tab-css" class="nav-tab">
                <span class="dashicons dashicons-admin-customizer"></span>
                CSS / IA
            </a>
            <a href="#tab-settings" class="nav-tab">
                <span class="dashicons dashicons-admin-settings"></span>
                Configuración
            </a>
            <a href="#tab-webhooks" class="nav-tab">
                <span class="dashicons dashicons-rest-api"></span>
                Webhooks
            </a>
            <a href="#tab-chatwoot" class="nav-tab">
                <span class="dashicons dashicons-format-chat"></span>
                Chatwoot
            </a>
        </nav>

        <!-- Tab Content -->
        <div class="getso-tab-content">
            
            <!-- TAB: CAMPOS -->
            <div id="tab-fields" class="getso-tab-panel active">
                <div class="getso-editor-layout">
                    
                    <!-- Form Basic Info -->
                    <div class="getso-form-basic-info">
                        <table class="form-table">
                            <tr>
                                <th><label for="form_name">Nombre del Formulario *</label></th>
                                <td>
                                    <input type="text" 
                                           id="form_name" 
                                           name="form_name" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($form_data['name'] ?? ''); ?>" 
                                           required>
                                    <p class="description">Identificador interno del formulario</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="form_description">Descripción</label></th>
                                <td>
                                    <textarea id="form_description" 
                                              name="form_description" 
                                              class="large-text" 
                                              rows="3"><?php echo esc_textarea($form_data['description'] ?? ''); ?></textarea>
                                    <p class="description">Opcional. Describe el propósito del formulario</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="form_active">Estado</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               id="form_active" 
                                               name="form_active" 
                                               value="1" 
                                               <?php checked($form_data['active'] ?? 1, 1); ?>>
                                        Formulario activo
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Fields Builder -->
                    <div class="getso-form-basic-info" style="margin-top: 20px;">
                        <h3>Modo del Formulario</h3>
                        <p>Selecciona cómo quieres construir este formulario.</p>
                        <table class="form-table">
                            <tr>
                                <th><label>Tipo de Constructor</label></th>
                                <td>
                                    <label>
                                        <input type="radio" name="form_settings[form_mode]" value="dynamic" <?php checked($form_data['form_settings']['form_mode'] ?? 'dynamic', 'dynamic'); ?>>
                                        <strong>Constructor Dinámico</strong> (Arrastrar y soltar campos)
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="form_settings[form_mode]" value="custom" <?php checked($form_data['form_settings']['form_mode'] ?? 'dynamic', 'custom'); ?>>
                                        <strong>HTML Personalizado</strong> (Pegar tu propio código)
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="getso-custom-code-container" style="display: none;">
                        <div class="getso-fields-section">
                            <h2>Código Personalizado</h2>
                            <p>Pega tu código HTML, CSS y JS. El formulario debe tener la clase <code>getso-contact-form</code> para que el envío funcione.</p>

                            <table class="form-table">
                                <tr>
                                    <th><label for="custom_html">HTML del Formulario</label></th>
                                    <td>
                                        <textarea name="form_settings[custom_html]" rows="15" class="large-text" style="font-family: monospace;"><?php echo esc_textarea($form_data['form_settings']['custom_html'] ?? ''); ?></textarea>
                                        <p class="description">Pega tu HTML aquí, incluyendo <code>&lt;form&gt;</code> y <code>&lt;style&gt;</code>.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="custom_js">JavaScript</label></th>
                                    <td>
                                        <textarea name="form_settings[custom_js]" rows="8" class="large-text" style="font-family: monospace;"><?php echo esc_textarea($form_data['form_settings']['custom_js'] ?? ''); ?></textarea>
                                        <p class="description">(Opcional) JavaScript que se cargará al final de la página.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="getso-fields-section">
                        <div class="getso-section-header">
                            <h2>Campos del Formulario</h2>
                            <button type="button" class="button button-primary" id="add-field-btn">
                                <span class="dashicons dashicons-plus-alt"></span>
                                Agregar Campo
                            </button>
                        </div>

                        <div id="fields-container" class="getso-fields-container">
                            <?php if (!empty($form_data['fields'])): ?>
                                <?php foreach ($form_data['fields'] as $index => $field): ?>
                                    <?php 
                                        // CORRECCIÓN: Se ha quitado "$this->" de la llamada a la función.
                                        // La función está definida al final de este archivo.
                                        echo render_field_item($field, $index); 
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="getso-no-fields">
                                    <span class="dashicons dashicons-editor-table"></span>
                                    <p>No hay campos. Haz clic en "Agregar Campo" para comenzar.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="getso-preview-section">
                        <div class="getso-section-header">
                            <h2>Vista Previa</h2>
                            <button type="button" class="button" id="refresh-preview-btn">
                                <span class="dashicons dashicons-update"></span>
                                Actualizar
                            </button>
                        </div>
                        <div class="getso-preview-container">
                            <div id="form-preview-iframe" class="getso-preview-frame">
                                <!-- Preview generado dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: CSS / IA -->
            <div id="tab-css" class="getso-tab-panel">
                <div class="getso-css-editor-layout">
                    <!-- Sidebar IA Chat -->
                    <div class="getso-ai-sidebar">
                        <h3>Asistente de CSS con IA</h3>
                        
                        <div class="getso-ai-templates">
                            <label>Plantillas rápidas:</label>
                            <button type="button" class="button button-small ai-template" data-template="modern">
                                Moderno
                            </button>
                            <button type="button" class="button button-small ai-template" data-template="minimal">
                                Minimalista
                            </button>
                            <button type="button" class="button button-small ai-template" data-template="colorful">
                                Colorido
                            </button>
                            <button type="button" class="button button-small ai-template" data-template="dark">
                                Modo Oscuro
                            </button>
                        </div>

                        <div class="getso-ai-chat" id="ai-chat-container">
                            <div class="getso-ai-messages" id="ai-messages">
                                <div class="ai-message assistant">
                                    <strong>Asistente:</strong>
                                    <p>¡Hola! Puedo ayudarte a crear estilos CSS personalizados para tu formulario. Describe cómo quieres que se vea y lo generaré para ti.</p>
                                </div>
                            </div>
                        </div>

                        <div class="getso-ai-input-wrapper">
                            <textarea id="ai-prompt-input" 
                                      placeholder="Ej: Quiero un formulario con bordes redondeados, colores azules y sombras suaves..."
                                      rows="3"></textarea>
                            <button type="button" class="button button-primary" id="send-ai-prompt">
                                <span class="dashicons dashicons-format-chat"></span>
                                Enviar
                            </button>
                        </div>

                        <div class="getso-ai-status" id="ai-status"></div>
                    </div>

                    <!-- Editor CSS -->
                    <div class="getso-css-editor-main">
                        <div class="getso-editor-toolbar">
                            <button type="button" class="button" id="save-css-btn">
                                <span class="dashicons dashicons-saved"></span>
                                Guardar CSS
                            </button>
                            <button type="button" class="button" id="restore-css-btn">
                                <span class="dashicons dashicons-undo"></span>
                                Restaurar
                            </button>
                        </div>
                        
                        <textarea id="custom-css-editor" name="custom_css"><?php echo esc_textarea($form_data['custom_css'] ?? ''); ?></textarea>
                    </div>

                    <!-- Preview Iframe -->
                    <div class="getso-css-preview">
                        <h4>Vista Previa en Vivo</h4>
                        <iframe id="css-preview-iframe" class="getso-css-preview-frame"></iframe>
                    </div>
                </div>
            </div>

            <!-- TAB: CONFIGURACIÓN -->
            <div id="tab-settings" class="getso-tab-panel">
                <h2>Configuración General</h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="submit_button_text">Texto del Botón</label></th>
                        <td>
                            <input type="text" 
                                   id="submit_button_text" 
                                   name="submit_button_text" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr($form_data['settings']['submit_button_text']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="success_message">Mensaje de Éxito</label></th>
                        <td>
                            <textarea id="success_message" 
                                      name="success_message" 
                                      class="large-text" 
                                      rows="3"><?php echo esc_textarea($form_data['settings']['success_message']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="error_message">Mensaje de Error</label></th>
                        <td>
                            <textarea id="error_message" 
                                      name="error_message" 
                                      class="large-text" 
                                      rows="3"><?php echo esc_textarea($form_data['settings']['error_message']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="redirect_url">URL de Redirección</label></th>
                        <td>
                            <input type="url" 
                                   id="redirect_url" 
                                   name="redirect_url" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr($form_data['settings']['redirect_url']); ?>">
                            <p class="description">Opcional. Redirigir después del envío exitoso</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enable_captcha">Protección</label></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="enable_captcha" 
                                       name="enable_captcha" 
                                       value="1" 
                                       <?php checked($form_data['settings']['enable_captcha'], 1); ?>>
                                Habilitar protección anti-spam
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="store_submissions">Almacenamiento</label></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="store_submissions" 
                                       name="store_submissions" 
                                       value="1" 
                                       <?php checked($form_data['settings']['store_submissions'], 1); ?>>
                                Guardar envíos en base de datos
                            </label>
                        </td>
                    </tr>
                </table>

                <?php if (!$is_new): ?>
                    <h3>Shortcode</h3>
                    <div class="getso-shortcode-box">
                        <code>[getso_form id="<?php echo $form_id; ?>"]</code>
                        <button type="button" class="button button-small copy-shortcode-btn" data-shortcode='[getso_form id="<?php echo $form_id; ?>"]'>
                            <span class="dashicons dashicons-clipboard"></span>
                            Copiar
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB: WEBHOOKS -->
            <div id="tab-webhooks" class="getso-tab-panel">
                <h2>Configuración de Webhooks</h2>
                
                <div class="getso-webhook-section">
                    <h3>Webhook Primario</h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="webhook_primary_url">URL</label></th>
                            <td>
                                <input type="url" 
                                       id="webhook_primary_url" 
                                       name="webhook_primary_url" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($form_data['webhook_config']['primary']['url'] ?? ''); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="webhook_primary_enabled">Estado</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="webhook_primary_enabled" 
                                           name="webhook_primary_enabled" 
                                           value="1" 
                                           <?php checked($form_data['webhook_config']['primary']['enabled'] ?? 0, 1); ?>>
                                    Habilitado
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="getso-webhook-section">
                    <h3>Webhook Secundario (Backup)</h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="webhook_secondary_url">URL</label></th>
                            <td>
                                <input type="url" 
                                       id="webhook_secondary_url" 
                                       name="webhook_secondary_url" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($form_data['webhook_config']['secondary']['url'] ?? ''); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="webhook_secondary_enabled">Estado</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="webhook_secondary_enabled" 
                                           name="webhook_secondary_enabled" 
                                           value="1" 
                                           <?php checked($form_data['webhook_config']['secondary']['enabled'] ?? 0, 1); ?>>
                                    Habilitado
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="getso-webhook-test">
                    <button type="button" class="button" id="test-webhooks-btn">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Probar Webhooks
                    </button>
                    <div id="webhook-test-result"></div>
                </div>
            </div>

            <!-- TAB: CHATWOOT -->
            <div id="tab-chatwoot" class="getso-tab-panel">
                <h2>Integración con Chatwoot</h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="chatwoot_enabled">Estado</label></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="chatwoot_enabled" 
                                       name="chatwoot_enabled" 
                                       value="1" 
                                       <?php checked($form_data['chatwoot_config']['enabled'] ?? 0, 1); ?>>
                                Enviar datos a Chatwoot
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chatwoot_account_id">Account ID</label></th>
                        <td>
                            <input type="text" 
                                   id="chatwoot_account_id" 
                                   name="chatwoot_account_id" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr($form_data['chatwoot_config']['account_id'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chatwoot_inbox_id">Inbox ID</label></th>
                        <td>
                            <input type="text" 
                                   id="chatwoot_inbox_id" 
                                   name="chatwoot_inbox_id" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr($form_data['chatwoot_config']['inbox_id'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chatwoot_create_contact">Crear Contacto</label></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="chatwoot_create_contact" 
                                       name="chatwoot_create_contact" 
                                       value="1" 
                                       <?php checked($form_data['chatwoot_config']['create_contact'] ?? 1, 1); ?>>
                                Crear contacto automáticamente
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chatwoot_create_conversation">Crear Conversación</label></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="chatwoot_create_conversation" 
                                       name="chatwoot_create_conversation" 
                                       value="1" 
                                       <?php checked($form_data['chatwoot_config']['create_conversation'] ?? 1, 1); ?>>
                                Crear conversación automáticamente
                            </label>
                        </td>
                    </tr>
                </table>

                <div class="getso-chatwoot-test">
                    <button type="button" class="button" id="test-chatwoot-btn">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Probar Conexión Chatwoot
                    </button>
                    <div id="chatwoot-test-result"></div>
                </div>
            </div>

        </div>

        <!-- Submit Buttons -->
        <div class="getso-form-actions">
            <button type="submit" class="button button-primary button-large" id="save-form-btn">
                <span class="dashicons dashicons-saved"></span>
                <?php echo $is_new ? 'Crear Formulario' : 'Guardar Cambios'; ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=getso-forms'); ?>" class="button button-large">
                Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Modal: Add/Edit Field -->
<div id="field-modal" class="getso-modal" style="display: none;">
    <div class="getso-modal-overlay"></div>
    <div class="getso-modal-content">
        <div class="getso-modal-header">
            <h2 id="field-modal-title">Agregar Campo</h2>
            <button type="button" class="getso-modal-close">&times;</button>
        </div>
        <div class="getso-modal-body">
            <form id="field-form">
                <input type="hidden" id="field-index" value="">
                
                <div class="form-row">
                    <label for="field-type">Tipo de Campo *</label>
                    <select id="field-type" required>
                        <option value="">Seleccionar...</option>
                        <option value="text">Texto</option>
                        <option value="email">Email</option>
                        <option value="tel">Teléfono</option>
                        <option value="rut">RUT</option>
                        <option value="textarea">Área de Texto</option>
                        <option value="select">Lista Desplegable</option>
                        <option value="radio">Opción Múltiple (Radio)</option>
                        <option value="checkbox">Casillas de Verificación</option>
                        <option value="number">Número</option>
                        <option value="date">Fecha</option>
                        <option value="file">Archivo</option>
                        <option value="hidden">Campo Oculto</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="field-name">Nombre Interno *</label>
                    <input type="text" id="field-name" placeholder="ej: nombre_cliente" required>
                    <small>Sin espacios, solo letras, números y guión bajo</small>
                </div>

                <div class="form-row">
                    <label for="field-label">Etiqueta</label>
                    <input type="text" id="field-label" placeholder="ej: Nombre Completo">
                </div>

                <div class="form-row">
                    <label for="field-placeholder">Placeholder</label>
                    <input type="text" id="field-placeholder" placeholder="Texto de ayuda...">
                </div>

                <div class="form-row">
                    <label>
                        <input type="checkbox" id="field-required">
                        Campo obligatorio
                    </label>
                </div>

                <div class="form-row" id="field-options-row" style="display: none;">
                    <label for="field-options">Opciones</label>
                    <textarea id="field-options" rows="5" placeholder="Una opción por línea&#10;valor|Etiqueta&#10;ej:&#10;opcion1|Opción 1&#10;opcion2|Opción 2"></textarea>
                    <small>Formato: valor|Etiqueta (una por línea)</small>
                </div>

                <div class="form-row">
                    <label for="field-class">Clase CSS</label>
                    <input type="text" id="field-class" placeholder="clase-personalizada">
                </div>
            </form>
        </div>
        <div class="getso-modal-footer">
            <button type="button" class="button button-primary" id="save-field-btn">Guardar Campo</button>
            <button type="button" class="button" id="cancel-field-btn">Cancelar</button>
        </div>
    </div>
</div>

<script>
// Variables globales
var getsoFormsEditor = {
    formId: <?php echo $form_id; ?>,
    // CORRECCIÓN: Aseguramos que los campos se pasen correctamente a JS
    fields: <?php echo json_encode(array_values($form_data['fields'])); ?>,
    nonce: '<?php echo wp_create_nonce('getso_forms_editor_ajax'); ?>',
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>'
};
</script>
