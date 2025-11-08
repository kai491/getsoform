<?php
/**
 * Clase Admin Menu
 * Crea el menú de administración del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }
    
    /**
     * Agregar menú
     */
    public function add_menu() {
        // Menú principal
        add_menu_page(
            __('Getso Forms', 'getso-forms'),
            __('Getso Forms', 'getso-forms'),
            'manage_options',
            'getso-forms',
            array($this, 'dashboard_page'),
            'dashicons-feedback',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'getso-forms',
            __('Dashboard', 'getso-forms'),
            __('Dashboard', 'getso-forms'),
            'manage_options',
            'getso-forms',
            array($this, 'dashboard_page')
        );
        
        // Formularios
        add_submenu_page(
            'getso-forms',
            __('Formularios', 'getso-forms'),
            __('Formularios', 'getso-forms'),
            'manage_options',
            'getso-forms-list',
            array($this, 'forms_list_page')
        );
        
        // Nuevo formulario
        add_submenu_page(
            'getso-forms',
            __('Nuevo Formulario', 'getso-forms'),
            __('Nuevo Formulario', 'getso-forms'),
            'manage_options',
            'getso-forms-new',
            array($this, 'form_editor_page')
        );
        
        // Envíos
        add_submenu_page(
            'getso-forms',
            __('Envíos', 'getso-forms'),
            __('Envíos', 'getso-forms'),
            'manage_options',
            'getso-forms-submissions',
            array($this, 'submissions_page')
        );
        
        // Configuración
        add_submenu_page(
            'getso-forms',
            __('Configuración', 'getso-forms'),
            __('Configuración', 'getso-forms'),
            'manage_options',
            'getso-forms-settings',
            array($this, 'settings_page')
        );
        
        // Editor de formulario (oculto del menú)
        add_submenu_page(
            null,
            __('Editar Formulario', 'getso-forms'),
            __('Editar Formulario', 'getso-forms'),
            'manage_options',
            'getso-forms-edit',
            array($this, 'form_editor_page')
        );
        
        // Editor CSS (oculto del menú)
        add_submenu_page(
            null,
            __('Editor CSS', 'getso-forms'),
            __('Editor CSS', 'getso-forms'),
            'manage_options',
            'getso-forms-css',
            array($this, 'css_editor_page')
        );
    }
    
    /**
     * Dashboard
     */
    public function dashboard_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Lista de formularios
     */
    public function forms_list_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/forms-list.php';
    }
    
    /**
     * Editor de formulario
     */
    public function form_editor_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/form-editor.php';
    }
    
    /**
     * Envíos
     */
    public function submissions_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/submissions.php';
    }
    
    /**
     * Configuración
     */
    public function settings_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Editor CSS con IA
     */
    public function css_editor_page() {
        include GETSO_FORMS_PLUGIN_DIR . 'admin/views/css-editor.php';
    }
}
