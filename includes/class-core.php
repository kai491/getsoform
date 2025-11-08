<?php
/**
 * Clase Core del plugin Getso Forms
 * Maneja la inicialización de todos los componentes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Core {
    
    /**
     * Instancia única de la clase
     */
    private static $instance = null;
    
    /**
     * Obtener instancia única
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Enqueue scripts y estilos
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'public_enqueue_scripts'));
        
        // AJAX hooks
        $this->register_ajax_hooks();
    }
    
    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        // Clases principales
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-database.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-forms-manager.php';
        // CORRECCIÓN: Asegurarse de que fields-builder se cargue si existe
        if (file_exists(GETSO_FORMS_PLUGIN_DIR . 'includes/class-fields-builder.php')) {
            require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-fields-builder.php';
        }
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-submissions.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-webhooks.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-chatwoot.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-whatsapp.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-ai-generator.php';
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-shortcode.php';
        
        // Admin
        if (is_admin()) {
            require_once GETSO_FORMS_PLUGIN_DIR . 'admin/class-admin-menu.php';
        }
    }
    
    /**
     * Inicializar componentes
     */
    private function init_components() {
        // Shortcodes
        new Getso_Forms_Shortcode();
        
        // Admin
        if (is_admin()) {
            new Getso_Forms_Admin_Menu();
        }
    }
    
    /**
     * Registrar hooks AJAX
     */
    private function register_ajax_hooks() {
        // AJAX para usuarios logueados y no logueados
        $ajax_actions = array(
            'save_submission',
            'update_submission',
            'get_settings',
            'send_to_chatwoot',
            'generate_css_ai',
            'test_ai_connection',
            'test_webhooks',
            'test_chatwoot',
            'get_submission_details',
            'delete_submission',
            'save_form',
            'delete_form',
            'duplicate_form',
            'get_form',
            'apply_css_template',
            'toggle_active',
            'bulk_delete_submissions',
            'get_form_preview'
        );
        
        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_getso_forms_' . $action, array($this, 'ajax_handler'));
            add_action('wp_ajax_nopriv_getso_forms_' . $action, array($this, 'ajax_handler'));
        }
    }
    
    /**
     * Manejador AJAX general
     */
    public function ajax_handler() {
        $action = str_replace('getso_forms_', '', current_action());
        $action = str_replace('wp_ajax_nopriv_', '', $action);
        $action = str_replace('wp_ajax_', '', $action);
        
        $method = 'ajax_' . $action;
        
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            // Delegar a la clase correspondiente
            $this->delegate_ajax($action);
        }
    }
    
    /**
     * Delegar acción AJAX a clase correspondiente
     */
    private function delegate_ajax($action) {
        switch ($action) {
            case 'save_submission':
            case 'update_submission':
            case 'get_submission_details':
            case 'delete_submission':
                $submissions = new Getso_Forms_Submissions();
                $method = 'ajax_' . $action;
                if (method_exists($submissions, $method)) {
                    $submissions->$method();
                }
                break;
                
            case 'send_to_chatwoot':
                $chatwoot = new Getso_Forms_Chatwoot();
                $chatwoot->ajax_send_to_chatwoot();
                break;
                
            case 'generate_css_ai':
            case 'test_ai_connection':
            case 'apply_css_template':
                $ai = new Getso_Forms_AI_Generator();
                $method = 'ajax_' . $action;
                if (method_exists($ai, $method)) {
                    $ai->$method();
                }
                break;
                
            case 'test_webhooks':
                $webhooks = new Getso_Forms_Webhooks();
                $webhooks->ajax_test_webhooks();
                break;
                
            case 'test_chatwoot':
                $chatwoot = new Getso_Forms_Chatwoot();
                $chatwoot->ajax_test_connection();
                break;
                
            case 'save_form':
            case 'delete_form':
            case 'duplicate_form':
            case 'get_form':
                $manager = new Getso_Forms_Manager();
                $method = 'ajax_' . $action;
                if (method_exists($manager, $method)) {
                    $manager->$method();
                }
                break;
                
            case 'get_settings':
                $settings = get_option('getso_forms_settings', array());
                wp_send_json_success(array('settings' => $settings));
                break;
                
            default:
                wp_send_json_error(array('message' => 'Acción no encontrada'));
                break;
        }
    }
    
    /**
     * Enqueue scripts y estilos del admin
     */
    public function admin_enqueue_scripts($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'getso-forms') === false) {
            return;
        }
        
        // Estilos admin
        wp_enqueue_style(
            'getso-forms-admin',
            GETSO_FORMS_PLUGIN_URL . 'admin/css/admin-global.css',
            array(),
            GETSO_FORMS_VERSION
        );
        
        // Scripts para la LISTA de formularios
        if (strpos($hook, 'getso-forms-list') !== false) {
            wp_enqueue_script(
                'getso-forms-manager',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/forms-manager.js',
                array('jquery'),
                GETSO_FORMS_VERSION,
                true
            );

            wp_enqueue_script(
                'getso-forms-editor',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/form-editor.js',
                array('jquery', 'jquery-ui-sortable'),
                GETSO_FORMS_VERSION,
                true
            );
        }
        
        // --- CORRECCIÓN INICIA ---
        
        // Scripts para las páginas de "Nuevo" y "Editar"
        $editor_pages = [
            'getso-forms_page_getso-forms-new',   // Página "Nuevo"
            'getso-forms_page_getso-forms-edit',  // Página "Editar"
            'getso-forms_page_getso-forms-css'    // Página "Editor CSS"
        ];

        if (in_array($hook, $editor_pages)) {
            
            // ¡AQUÍ ESTÁ LA LÍNEA QUE FALTABA!
            // Carga el script para las Pestañas y el Modal de Campos
            wp_enqueue_script(
                'getso-forms-editor-admin',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/form-editor-admin.js',
                array('jquery', 'jquery-ui-sortable'), // jquery-ui-sortable para drag&drop de campos
                GETSO_FORMS_VERSION,
                true
            );

            // CodeMirror para editor CSS
            wp_enqueue_code_editor(array('type' => 'text/css'));
            
            wp_enqueue_script(
                'getso-forms-css-editor',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/css-editor.js',
                array('jquery', 'wp-codemirror'),
                GETSO_FORMS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'getso-forms-ai-chat',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/ai-chat.js',
                array('jquery'),
                GETSO_FORMS_VERSION,
                true
            );
        }
        
        // Chart.js para analytics (Dashboard)
        $dashboard_pages = [
            'toplevel_page_getso-forms', // Hook del dashboard principal
            'getso-forms_page_getso-forms-analytics' // Si tienes página de analytics
        ];

        if (in_array($hook, $dashboard_pages)) {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
            
            // Corregido: GETSO_FORMS_PLUGIN_URL
            wp_enqueue_script(
                'getso-forms-analytics',
                GETSO_FORMS_PLUGIN_URL . 'admin/js/analytics.js',
                array('jquery', 'chart-js'),
                GETSO_FORMS_VERSION,
                true
            );
        }
        
        // --- CORRECCIÓN TERMINA ---
        
        // Localización de scripts
        // CORRECCIÓN: Adjuntamos los datos a 'jquery' en lugar de 'getso-forms-manager'.
        // 'jquery' siempre está cargado, por lo que 'getsoFormsAdmin' estará
        // disponible en TODAS las páginas de admin del plugin (incluyendo Settings y Editor).
        wp_localize_script('jquery', 'getsoFormsAdmin', array( 
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('getso_forms_nonce'), // Usaremos este nonce global
            'pluginUrl' => GETSO_FORMS_PLUGIN_URL,
            'strings' => array(
                'confirmDelete' => __('¿Estás seguro de eliminar este formulario?', 'getso-forms'),
                'confirmDeleteSubmission' => __('¿Estás seguro de eliminar este envío?', 'getso-forms'),
                'saving' => __('Guardando...', 'getso-forms'),
                'saved' => __('Guardado', 'getso-forms'),
                'error' => __('Error', 'getso-forms'),
                'success' => __('Éxito', 'getso-forms')
            )
        ));
    }
    
    /**
     * Enqueue scripts y estilos públicos
     */
    public function public_enqueue_scripts() {
        // Solo cargar si hay un shortcode en la página
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'getso_form')) {
            // Estilos públicos
            wp_enqueue_style(
                'getso-forms-public',
                GETSO_FORMS_PLUGIN_URL . 'public/css/form-base.css',
                array(),
                GETSO_FORMS_VERSION
            );
            
            // Scripts públicos
            wp_enqueue_script(
                'getso-forms-validator',
                GETSO_FORMS_PLUGIN_URL . 'public/js/form-validator.js',
                array('jquery'),
                GETSO_FORMS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'getso-forms-handler',
                GETSO_FORMS_PLUGIN_URL . 'public/js/form-handler.js',
                array('jquery', 'getso-forms-validator'),
                GETSO_FORMS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'getso-forms-formatters',
                GETSO_FORMS_PLUGIN_URL . 'public/js/field-formatters.js',
                array('jquery'),
                GETSO_FORMS_VERSION,
                true
            );
            
            // Localización
            wp_localize_script('getso-forms-handler', 'getsoForms', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('getso_forms_public_nonce'),
                'strings' => array(
                    'processing' => __('Procesando...', 'getso-forms'),
                    'success' => __('¡Mensaje enviado exitosamente!', 'getso-forms'),
                    'error' => __('Error al enviar el formulario', 'getso-forms'),
                    'requiredField' => __('Este campo es requerido', 'getso-forms'),
                    'invalidEmail' => __('Email inválido', 'getso-forms'),
                    'invalidPhone' => __('Teléfono inválido', 'getso-forms')
                )
            ));
        }
    }
}