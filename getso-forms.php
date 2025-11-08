<?php
/**
 * Plugin Name: Getso Forms
 * Plugin URI: https://getso.cl
 * Description: Sistema avanzado de formularios con editor CSS impulsado por IA (Claude, OpenAI, Gemini)
 * Version: 1.0.0
 * Author: Getso
 * Author URI: https://getso.cl
 * Text Domain: getso-forms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GETSO_FORMS_VERSION', '1.0.0');
define('GETSO_FORMS_PLUGIN_FILE', __FILE__);
define('GETSO_FORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GETSO_FORMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GETSO_FORMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader para las clases del plugin
 */
spl_autoload_register(function ($class) {
    // Prefix del namespace
    $prefix = 'Getso_Forms_';
    
    // Base directory para el namespace
    $base_dir = GETSO_FORMS_PLUGIN_DIR . 'includes/';
    
    // Verificar si la clase usa el namespace del plugin
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);
    
    // Convertir namespace a path
    // Getso_Forms_Database -> class-database.php
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Autoloader para clases de admin
 */
spl_autoload_register(function ($class) {
    $prefix = 'Getso_Forms_Admin_';
    $base_dir = GETSO_FORMS_PLUGIN_DIR . 'admin/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-admin-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Activación del plugin
 */
register_activation_hook(__FILE__, 'getso_forms_activate');

function getso_forms_activate() {
    require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-database.php';
    require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-forms-manager.php';
    
    // Crear tablas
    Getso_Forms_Database::create_tables();
    
    // Cargar formularios predefinidos si no existen
    $forms_count = Getso_Forms_Manager::count_forms();
    if ($forms_count === 0) {
        Getso_Forms_Manager::load_default_templates();
    }
    
    // Opciones por defecto
    add_option('getso_forms_version', GETSO_FORMS_VERSION);
    add_option('getso_forms_ai_provider', 'claude');
    add_option('getso_forms_ai_model', 'claude-sonnet-4-20250514');
    add_option('getso_forms_ai_api_key', '');
    add_option('getso_forms_max_forms', 20);
    add_option('getso_forms_ai_requests_per_hour', 10);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Desactivación del plugin
 */
register_deactivation_hook(__FILE__, 'getso_forms_deactivate');

function getso_forms_deactivate() {
    flush_rewrite_rules();
}

/**
 * Inicializar el plugin
 */
add_action('plugins_loaded', 'getso_forms_init');

function getso_forms_init() {
    // Cargar idiomas
    load_plugin_textdomain('getso-forms', false, dirname(GETSO_FORMS_PLUGIN_BASENAME) . '/languages');
    
    // Inicializar core
    if (class_exists('Getso_Forms_Core')) {
        Getso_Forms_Core::instance();
    }
}

/**
 * Verificar versión y actualizar si es necesario
 */
add_action('admin_init', 'getso_forms_check_version');

function getso_forms_check_version() {
    $current_version = get_option('getso_forms_version', '0.0.0');
    
    if (version_compare($current_version, GETSO_FORMS_VERSION, '<')) {
        // Actualizar base de datos si es necesario
        require_once GETSO_FORMS_PLUGIN_DIR . 'includes/class-database.php';
        Getso_Forms_Database::update_tables();
        
        // Actualizar versión
        update_option('getso_forms_version', GETSO_FORMS_VERSION);
    }
}

/**
 * Enlaces en la página de plugins
 */
add_filter('plugin_action_links_' . GETSO_FORMS_PLUGIN_BASENAME, 'getso_forms_action_links');

function getso_forms_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=getso-forms-settings') . '">' . __('Configuración', 'getso-forms') . '</a>';
    $forms_link = '<a href="' . admin_url('admin.php?page=getso-forms') . '">' . __('Formularios', 'getso-forms') . '</a>';
    
    array_unshift($links, $settings_link, $forms_link);
    
    return $links;
}
