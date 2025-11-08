<?php
/**
 * Clase Forms Manager
 * Maneja todas las operaciones CRUD de formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Manager {
    
    /**
     * Crear nuevo formulario
     */
    public static function create_form($data) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        // Validar datos requeridos
        if (empty($data['form_name']) || empty($data['form_fields'])) {
            return new WP_Error('missing_data', __('Faltan datos requeridos', 'getso-forms'));
        }
        
        // Generar slug único
        $slug = !empty($data['form_slug']) ? $data['form_slug'] : sanitize_title($data['form_name']);
        $slug = self::generate_unique_slug($slug);
        
        // Generar shortcode único
        $shortcode = '[getso_form id="' . $slug . '"]';
        
        // Preparar datos
        $insert_data = array(
            'form_name' => sanitize_text_field($data['form_name']),
            'form_slug' => $slug,
            'form_description' => !empty($data['form_description']) ? sanitize_textarea_field($data['form_description']) : '',
            'form_fields' => is_array($data['form_fields']) ? wp_json_encode($data['form_fields']) : $data['form_fields'],
            'form_css' => !empty($data['form_css']) ? $data['form_css'] : '',
            'form_settings' => !empty($data['form_settings']) ? wp_json_encode($data['form_settings']) : wp_json_encode(self::get_default_settings()),
            'shortcode' => $shortcode,
            'active' => isset($data['active']) ? intval($data['active']) : 1
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error al crear formulario', 'getso-forms'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Obtener formulario por ID o slug
     */
    public static function get_form($identifier) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        if (is_numeric($identifier)) {
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $identifier
            ));
        } else {
            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE form_slug = %s",
                $identifier
            ));
        }
        
        if (!$form) {
            return null;
        }
        
        // Decodificar JSON
        $form->form_fields = json_decode($form->form_fields, true);
        $form->form_settings = json_decode($form->form_settings, true);
        
        return $form;
    }
    
    /**
     * Actualizar formulario
     */
    public static function update_form($id, $data) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        // Verificar que el formulario existe
        $existing = self::get_form($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Formulario no encontrado', 'getso-forms'));
        }
        
        // Preparar datos para actualizar
        $update_data = array();
        
        if (isset($data['form_name'])) {
            $update_data['form_name'] = sanitize_text_field($data['form_name']);
        }
        
        if (isset($data['form_slug'])) {
            $slug = sanitize_title($data['form_slug']);
            // Verificar que el slug sea único (excepto para este formulario)
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE form_slug = %s AND id != %d",
                $slug,
                $id
            ));
            
            if ($exists > 0) {
                return new WP_Error('duplicate_slug', __('El slug ya existe', 'getso-forms'));
            }
            
            $update_data['form_slug'] = $slug;
            $update_data['shortcode'] = '[getso_form id="' . $slug . '"]';
        }
        
        if (isset($data['form_description'])) {
            $update_data['form_description'] = sanitize_textarea_field($data['form_description']);
        }
        
        if (isset($data['form_fields'])) {
            $update_data['form_fields'] = is_array($data['form_fields']) ? wp_json_encode($data['form_fields']) : $data['form_fields'];
        }
        
        if (isset($data['form_css'])) {
            $update_data['form_css'] = $data['form_css']; // CSS no se sanitiza para preservar caracteres
        }
        
        if (isset($data['form_settings'])) {
            $update_data['form_settings'] = is_array($data['form_settings']) ? wp_json_encode($data['form_settings']) : $data['form_settings'];
        }
        
        if (isset($data['active'])) {
            $update_data['active'] = intval($data['active']);
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('No hay datos para actualizar', 'getso-forms'));
        }
        
        $result = $wpdb->update($table, $update_data, array('id' => $id));
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error al actualizar formulario', 'getso-forms'));
        }
        
        return true;
    }
    
    /**
     * Eliminar formulario
     */
    public static function delete_form($id) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        $result = $wpdb->delete($table, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar formulario', 'getso-forms'));
        }
        
        return true;
    }
    
    /**
     * Duplicar formulario
     */
    public static function duplicate_form($id) {
        $original = self::get_form($id);
        
        if (!$original) {
            return new WP_Error('not_found', __('Formulario no encontrado', 'getso-forms'));
        }
        
        // Crear copia
        $copy_data = array(
            'form_name' => $original->form_name . ' (Copia)',
            'form_description' => $original->form_description,
            'form_fields' => $original->form_fields,
            'form_css' => $original->form_css,
            'form_settings' => $original->form_settings,
            'active' => 0 // Desactivado por defecto
        );
        
        return self::create_form($copy_data);
    }
    
    /**
     * Listar todos los formularios
     */
    public static function list_forms($args = array()) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        $defaults = array(
            'active' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => null,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        
        if ($args['active'] !== null) {
            $where .= $wpdb->prepare(' AND active = %d', $args['active']);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $limit_clause = '';
        if ($args['limit']) {
            $limit_clause = $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        $forms = $wpdb->get_results(
            "SELECT * FROM $table WHERE $where ORDER BY $orderby $limit_clause"
        );
        
        // Decodificar JSON en cada formulario
        foreach ($forms as &$form) {
            $form->form_fields = json_decode($form->form_fields, true);
            $form->form_settings = json_decode($form->form_settings, true);
        }
        
        return $forms;
    }
    
    /**
     * Contar formularios
     */
    public static function count_forms($active = null) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        
        if ($active !== null) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE active = %d",
                $active
            ));
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    /**
     * Generar slug único
     */
    private static function generate_unique_slug($base_slug) {
        global $wpdb;
        
        $table = Getso_Forms_Database::get_table_name('forms');
        $slug = $base_slug;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE form_slug = %s", $slug)) > 0) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Configuración por defecto para formularios
     */
    public static function get_default_settings() {
        return array(
            'form_mode' => 'dynamic', // 'dynamic' o 'custom'
            'custom_html' => '',
            'custom_js' => '',
            'webhooks' => array(
                'enabled' => false,
                'mode' => 'production',
                'primary_test' => '',
                'primary_prod' => '',
                'secondary_test' => '',
                'secondary_prod' => '',
                'timeout' => 15,
                'retries' => 0
            ),
            'chatwoot' => array(
                'enabled' => false,
                'url' => '',
                'api_token' => '',
                'account_id' => '',
                'inbox_id' => '',
                'auto_assign' => false,
                'create_conversation' => true
            ),
            'whatsapp' => array(
                'enabled' => false,
                'number' => '',
                'message_template' => ''
            ),
            'messages' => array(
                'submit_button' => __('Enviar', 'getso-forms'), // <-- CLAVE DE TEXTO DE BOTÓN
                'success' => __('¡Gracias! Tu mensaje ha sido enviado exitosamente.', 'getso-forms'),
                'error' => __('Hubo un error al enviar el formulario. Por favor intenta nuevamente.', 'getso-forms')
            ),
            'notifications' => array(
                'enabled' => false,
                'email' => get_option('admin_email'),
                'subject' => __('Nuevo envío de formulario', 'getso-forms')
            ),
            'security' => array(
                'honeypot' => true,
                'recaptcha' => false,
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => ''
            )
        );
    }
    
    /**
     * Cargar plantillas predefinidas
     */
    public static function load_default_templates() {
        // Plantilla 1: Formulario Deudores
        $deudores_template = array(
            'form_name' => 'Formulario Deudores',
            'form_slug' => 'contacto-deudores',
            'form_description' => 'Formulario para contacto de deudores',
            'form_fields' => array(
                'fields' => array(
                    array(
                        'id' => 'field_nombre',
                        'type' => 'text',
                        'name' => 'nombre',
                        'label' => 'Nombre Completo',
                        'required' => true,
                        'placeholder' => 'Ingresa tu nombre completo',
                        'validation' => 'text',
                        'width' => '50%',
                        'css_class' => 'field-nombre'
                    ),
                    array(
                        'id' => 'field_telefono',
                        'type' => 'tel',
                        'name' => 'telefono',
                        'label' => 'Teléfono de Contacto',
                        'required' => true,
                        'placeholder' => '+56912345678',
                        'validation' => 'phone',
                        'format' => 'chile',
                        'width' => '50%',
                        'css_class' => 'field-telefono'
                    ),
                    array(
                        'id' => 'field_rut',
                        'type' => 'text',
                        'name' => 'rut',
                        'label' => 'RUT Deudor',
                        'required' => true,
                        'placeholder' => '12.345.678-9',
                        'validation' => 'rut_chile',
                        'width' => '50%',
                        'css_class' => 'field-rut'
                    ),
                    array(
                        'id' => 'field_correo',
                        'type' => 'email',
                        'name' => 'correo',
                        'label' => 'Email',
                        'required' => true,
                        'placeholder' => 'tucorreo@ejemplo.com',
                        'validation' => 'email',
                        'width' => '50%',
                        'css_class' => 'field-correo'
                    ),
                    array(
                        'id' => 'field_empresa',
                        'type' => 'select',
                        'name' => 'empresa',
                        'label' => 'Empresa con la que tiene deuda',
                        'required' => true,
                        'options' => array(
                            array('value' => '', 'label' => 'Selecciona una empresa'),
                            array('value' => 'CMR', 'label' => 'CMR'),
                            array('value' => 'Banco Falabella', 'label' => 'Banco Falabella'),
                            array('value' => 'Forum', 'label' => 'Forum'),
                            array('value' => 'Maf', 'label' => 'Maf'),
                            array('value' => 'Eurocapital', 'label' => 'Eurocapital')
                        ),
                        'width' => '100%',
                        'css_class' => 'field-empresa'
                    ),
                    array(
                        'id' => 'field_mensaje',
                        'type' => 'textarea',
                        'name' => 'mensaje',
                        'label' => 'Mensaje (Opcional)',
                        'required' => false,
                        'placeholder' => 'Cuéntanos más sobre tu situación...',
                        'rows' => 4,
                        'width' => '100%',
                        'css_class' => 'field-mensaje'
                    )
                )
            ),
            'form_css' => self::get_default_css(),
            'form_settings' => array(
                'webhooks' => array(
                    'enabled' => true,
                    'mode' => 'production',
                    'primary_test' => 'https://auto.roneira.cl/webhook-test/1dbfd8ec-f39e-4bb6-be42-0dd9d18cecfa',
                    'primary_prod' => 'https://auto.roneira.cl/webhook/1dbfd8ec-f39e-4bb6-be42-0dd9d18cecfa',
                    'secondary_test' => '',
                    'secondary_prod' => '',
                    'timeout' => 15,
                    'retries' => 0
                ),
                'chatwoot' => array('enabled' => false),
                'whatsapp' => array(
                    'enabled' => true,
                    'number' => '56900000000',
                    'message_template' => 'Hola, quisiera conversar sobre mi situación. Mis datos son:\n\n*Nombre:* {nombre}\n*RUT:* {rut}\n*Teléfono:* {telefono}\n*Empresa:* {empresa}'
                ),
                'messages' => array(
                    'submit_button' => 'Enviar Consulta',
                    'success' => '¡Gracias! Tu mensaje ha sido enviado. Un ejecutivo te contactará pronto.'
                )
            ),
            'active' => 1
        );
        
        self::create_form($deudores_template);
        
        // Plantilla 2: Formulario Empresas
        $empresas_template = array(
            'form_name' => 'Formulario Empresas',
            'form_slug' => 'contacto-empresas',
            'form_description' => 'Formulario para consultas empresariales',
            'form_fields' => array(
                'fields' => array(
                    array(
                        'id' => 'field_nombre',
                        'type' => 'text',
                        'name' => 'nombre',
                        'label' => 'Nombre Completo',
                        'required' => true,
                        'placeholder' => 'Nombre Completo',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_cargo',
                        'type' => 'text',
                        'name' => 'cargo',
                        'label' => 'Cargo',
                        'required' => true,
                        'placeholder' => 'Cargo',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_correo',
                        'type' => 'email',
                        'name' => 'correo',
                        'label' => 'Email',
                        'required' => true,
                        'placeholder' => 'Email',
                        'validation' => 'email',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_telefono',
                        'type' => 'tel',
                        'name' => 'telefono',
                        'label' => 'Teléfono',
                        'required' => true,
                        'placeholder' => 'Teléfono',
                        'validation' => 'phone',
                        'format' => 'chile',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_empresa',
                        'type' => 'text',
                        'name' => 'empresa',
                        'label' => 'Empresa',
                        'required' => true,
                        'placeholder' => 'Empresa',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_industria',
                        'type' => 'select',
                        'name' => 'industria',
                        'label' => 'Industria',
                        'required' => true,
                        'options' => array(
                            array('value' => '', 'label' => 'Selecciona industria'),
                            array('value' => 'Banca/Financiero', 'label' => 'Banca/Financiero'),
                            array('value' => 'Retail', 'label' => 'Retail'),
                            array('value' => 'Automotriz', 'label' => 'Automotriz'),
                            array('value' => 'Tecnología', 'label' => 'Tecnología'),
                            array('value' => 'Otro', 'label' => 'Otro')
                        ),
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_mensaje',
                        'type' => 'textarea',
                        'name' => 'mensaje',
                        'label' => 'Cuéntenos su situación',
                        'required' => false,
                        'placeholder' => 'Cuéntenos su situación (opcional)',
                        'rows' => 3,
                        'width' => '100%'
                    )
                )
            ),
            'form_css' => self::get_default_css(),
            'form_settings' => self::get_default_settings(),
            'active' => 1
        );
        
        self::create_form($empresas_template);
        
        // Plantilla 3: Formulario Getso
        $getso_template = array(
            'form_name' => 'Formulario Getso',
            'form_slug' => 'contacto-getso',
            'form_description' => 'Formulario de contacto Getso con diseño moderno',
            'form_fields' => array(
                'fields' => array(
                    array(
                        'id' => 'field_nombre',
                        'type' => 'text',
                        'name' => 'nombre',
                        'label' => 'Nombre',
                        'required' => true,
                        'placeholder' => 'Nombre',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_email',
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email',
                        'required' => true,
                        'placeholder' => 'Email',
                        'validation' => 'email',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_telefono',
                        'type' => 'tel',
                        'name' => 'telefono',
                        'label' => 'Teléfono',
                        'required' => true,
                        'placeholder' => 'Teléfono',
                        'validation' => 'phone',
                        'format' => 'chile',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_empresa',
                        'type' => 'text',
                        'name' => 'empresa',
                        'label' => 'Empresa',
                        'required' => false,
                        'placeholder' => 'Empresa',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_cargo',
                        'type' => 'text',
                        'name' => 'cargo',
                        'label' => 'Cargo',
                        'required' => false,
                        'placeholder' => 'Cargo',
                        'width' => '50%'
                    ),
                    array(
                        'id' => 'field_mensaje',
                        'type' => 'textarea',
                        'name' => 'mensaje',
                        'label' => 'Mensaje',
                        'required' => true,
                        'placeholder' => 'Mensaje',
                        'rows' => 4,
                        'width' => '100%'
                    )
                )
            ),
            'form_css' => "/* Formulario Getso - Diseño con Degradado */
.getso-form-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px;
    border-radius: 12px;
}

.getso-form-container {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.getso-form-group input,
.getso-form-group textarea {
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    padding: 12px;
    transition: all 0.3s ease;
}

.getso-form-group input:focus,
.getso-form-group textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.getso-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 32px;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.getso-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}",
            'form_settings' => self::get_default_settings(),
            'active' => 1
        );
        
        self::create_form($getso_template);
    }
    
    /**
     * CSS por defecto para formularios
     */
    private static function get_default_css() {
        return "/* Estilos del Formulario */
.getso-form-wrapper {
    --primary-color: #0d2a57;
    --primary-hover: #1a4578;
    --bg-light: #e8eef5;
    --text-dark: #2c3e50;
    --border-color: #cbd5e0;
    --white: #ffffff;
}

.getso-form-container {
    background: var(--white);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    max-width: 800px;
    margin: 0 auto;
}

.getso-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.getso-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.getso-form-group label {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
}

.getso-form-group input,
.getso-form-group select,
.getso-form-group textarea {
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.getso-form-group input:focus,
.getso-form-group select:focus,
.getso-form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(13, 42, 87, 0.1);
}

.getso-btn-primary {
    background: var(--primary-color);
    color: var(--white);
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.getso-btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

@media (max-width: 768px) {
    .getso-form-row {
        grid-template-columns: 1fr;
    }
    .getso-form-container {
        padding: 24px;
    }
}";
    }
    
    /**
     * AJAX: Guardar formulario
     */
    public function ajax_save_form() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        // --- INICIA CORRECCIÓN (Error 4) ---
        // La función estaba buscando 'form_data', pero el editor envía campos individuales.
        // Debemos ensamblar el array $data aquí, leyendo $_POST.

        $data = array();
        
        // 1. Campos Simples (de la pestaña Campos)
        $data['form_name'] = isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '';
        $data['form_description'] = isset($_POST['form_description']) ? sanitize_textarea_field($_POST['form_description']) : '';
        $data['active'] = isset($_POST['form_active']) ? 1 : 0; // 'form_active' se envía si está marcado
        
        // 2. CSS (de la pestaña CSS/IA)
        // No sanitizar CSS para no romper reglas como '>' o 'url()'
        $data['form_css'] = isset($_POST['custom_css']) ? $_POST['custom_css'] : ''; 

        // 3. Campos (Fields) (de la pestaña Campos)
        // El JS (form-editor-admin.js) envía un array de strings JSON
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            $sanitized_fields = array();
            // stripslashes es crucial porque WordPress añade slashes mágicos a los datos POST
            $field_json_strings = stripslashes_deep($_POST['fields']);
            
            foreach ($field_json_strings as $json_string) {
                $field_data = json_decode($json_string, true);
                if (is_array($field_data)) {
                    // Aquí se podría hacer una sanitización más profunda por cada clave del campo si se quisiera
                    $sanitized_fields[] = $field_data;
                }
            }
            // Guardamos en el formato esperado: { "fields": [...] }
            $data['form_fields'] = wp_json_encode(array('fields' => $sanitized_fields));
        }

        // 4. Ajustes (Settings) (de las pestañas Configuración, Webhooks, Chatwoot)
        // Empezamos con los defaults para asegurarnos de que todas las claves existan
        $settings = self::get_default_settings();

        // Sobrescribir con los datos del POST
        
        // Pestaña Configuración
        if (isset($_POST['submit_button_text'])) $settings['messages']['submit_button'] = sanitize_text_field($_POST['submit_button_text']);
        if (isset($_POST['success_message'])) $settings['messages']['success'] = sanitize_textarea_field($_POST['success_message']);
        if (isset($_POST['error_message'])) $settings['messages']['error'] = sanitize_textarea_field($_POST['error_message']);
        if (isset($_POST['redirect_url'])) $settings['redirect_url'] = esc_url_raw($_POST['redirect_url']); // No está en defaults, pero lo añadimos
        if (isset($_POST['enable_captcha'])) $settings['security']['recaptcha'] = true;
        // 'store_submissions' no está en los defaults, pero la lógica del editor lo implica.
        // Lo guardaremos en la raíz de settings.
        $settings['store_submissions'] = isset($_POST['store_submissions']) ? 1 : 0;


        // Pestaña Webhooks
        $settings['webhooks']['enabled'] = (isset($_POST['webhook_primary_enabled']) || isset($_POST['webhook_secondary_enabled'])) ? true : false;
        $settings['webhooks']['primary_prod'] = isset($_POST['webhook_primary_url']) ? esc_url_raw($_POST['webhook_primary_url']) : '';
        $settings['webhooks']['secondary_prod'] = isset($_POST['webhook_secondary_url']) ? esc_url_raw($_POST['webhook_secondary_url']) : '';
        // Asumimos que test = prod por ahora, ya que el editor no los diferencia
        $settings['webhooks']['primary_test'] = $settings['webhooks']['primary_prod'];
        $settings['webhooks']['secondary_test'] = $settings['webhooks']['secondary_prod'];

        // Pestaña Chatwoot
        $settings['chatwoot']['enabled'] = isset($_POST['chatwoot_enabled']) ? 1 : 0;
        $settings['chatwoot']['account_id'] = isset($_POST['chatwoot_account_id']) ? sanitize_text_field($_POST['chatwoot_account_id']) : '';
        $settings['chatwoot']['inbox_id'] = isset($_POST['chatwoot_inbox_id']) ? sanitize_text_field($_POST['chatwoot_inbox_id']) : '';
        // El editor de form-editor.php (v2) no envía 'url' o 'api_token', los obtenemos de la config global.
        // El editor sí envía 'create_contact' y 'create_conversation'
        $settings['chatwoot']['create_contact'] = isset($_POST['chatwoot_create_contact']) ? 1 : 0;
        $settings['chatwoot']['create_conversation'] = isset($_POST['chatwoot_create_conversation']) ? 1 : 0;

        // Asignamos el array de settings completo
        $data['form_settings'] = wp_json_encode($settings);
        
        // --- TERMINA CORRECCIÓN ---

        if ($form_id > 0) {
            // Actualizar
            $result = self::update_form($form_id, $data);
            $new_form_id = $form_id;
        } else {
            // Crear
            $result = self::create_form($data);
            $new_form_id = $result; // create_form devuelve el nuevo ID
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Formulario guardado exitosamente', 'getso-forms'),
                'form_id' => $new_form_id // Devolvemos el ID
            ));
        }
    }
    
    /**
     * AJAX: Eliminar formulario
     */
    public function ajax_delete_form() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $result = self::delete_form($form_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => __('Formulario eliminado', 'getso-forms')));
        }
    }
    
    /**
     * AJAX: Duplicar formulario
     */
    public function ajax_duplicate_form() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $result = self::duplicate_form($form_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Formulario duplicado', 'getso-forms'),
                'new_form_id' => $result
            ));
        }
    }
    
    /**
     * AJAX: Obtener formulario
     */
    public function ajax_get_form() {
        check_ajax_referer('getso_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $form = self::get_form($form_id);
        
        if (!$form) {
            wp_send_json_error(array('message' => __('Formulario no encontrado', 'getso-forms')));
        } else {
            wp_send_json_success(array('form' => $form));
        }
    }

    /**
     * AJAX: Obtener preview del formulario (HTML)
     */
    public function ajax_get_form_preview() {
        check_ajax_referer('getso_forms_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'getso-forms')]);
            return;
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        if (empty($form_id)) {
            wp_send_json_error(['message' => __('ID de formulario no válido', 'getso-forms')]);
            return;
        }

        $form = self::get_form($form_id);
        if (!$form) {
            wp_send_json_error(['message' => __('Formulario no encontrado', 'getso-forms')]);
            return;
        }

        if (!class_exists('Getso_Forms_Fields_Builder')) {
            wp_send_json_error(['message' => __('La clase Fields_Builder no existe.', 'getso-forms')]);
            return;
        }

        $builder = new Getso_Forms_Fields_Builder();
        $html = '<div class="getso-form-preview-wrapper">';
        
        $fields = $form->form_fields['fields'] ?? [];
        foreach ($fields as $field) {
            $html .= $builder->render_field($field);
        }

        $html .= '<button type="submit" class="getso-btn getso-btn-primary">' . esc_html($form->form_settings['messages']['submit_button'] ?? 'Enviar') . '</button>';
        $html .= '</div>';

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Toggle activo/inactivo
     */
    public function ajax_toggle_active() {
        check_ajax_referer('getso_forms_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $active = isset($_POST['active']) ? intval($_POST['active']) : 0;

        $result = self::update_form($form_id, array('active' => $active));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Estado actualizado', 'getso-forms'),
                'active' => $active
            ));
        }
    }

    /**
     * AJAX: Bulk delete submissions
     */
    public function ajax_bulk_delete_submissions() {
        check_ajax_referer('getso_forms_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'getso-forms')));
            return;
        }

        $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : array();

        if (empty($ids)) {
            wp_send_json_error(array('message' => __('No se seleccionaron envíos', 'getso-forms')));
            return;
        }

        global $wpdb;
        $table = Getso_Forms_Database::get_table_name('submissions');

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = "DELETE FROM $table WHERE id IN ($placeholders)";

        $result = $wpdb->query($wpdb->prepare($query, $ids));

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error al eliminar envíos', 'getso-forms')));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('%d envíos eliminados', 'getso-forms'), $result),
                'deleted' => $result
            ));
        }
    }
}