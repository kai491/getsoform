<?php
/**
 * Getso Forms Uninstall
 * Se ejecuta cuando el plugin es eliminado
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Eliminar tablas
$tables = array(
    $wpdb->prefix . 'getso_forms',
    $wpdb->prefix . 'getso_form_submissions',
    $wpdb->prefix . 'getso_forms_ai_history'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Eliminar opciones
$options = array(
    'getso_forms_version',
    'getso_forms_ai_provider',
    'getso_forms_ai_api_key',
    'getso_forms_ai_model',
    'getso_forms_ai_requests_per_hour',
    'getso_forms_max_forms'
);

foreach ($options as $option) {
    delete_option($option);
}

// Eliminar transients
delete_transient('getso_forms_ai_requests');
