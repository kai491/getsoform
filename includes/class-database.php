<?php
/**
 * Clase Database
 * Maneja la creación y actualización de tablas de la base de datos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Database {
    
    /**
     * Nombre de las tablas
     */
    const TABLE_FORMS = 'getso_forms';
    const TABLE_SUBMISSIONS = 'getso_form_submissions';
    const TABLE_AI_HISTORY = 'getso_forms_ai_history';
    
    /**
     * Crear todas las tablas
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Tabla de formularios
        self::create_forms_table($charset_collate);
        
        // Tabla de envíos
        self::create_submissions_table($charset_collate);
        
        // Tabla de historial IA
        self::create_ai_history_table($charset_collate);
    }
    
    /**
     * Crear tabla de formularios
     */
    private static function create_forms_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_FORMS;
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_name varchar(255) NOT NULL,
            form_slug varchar(100) NOT NULL,
            form_description text DEFAULT NULL,
            form_fields longtext NOT NULL,
            form_css longtext DEFAULT NULL,
            form_settings longtext NOT NULL,
            shortcode varchar(100) NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY form_slug (form_slug),
            UNIQUE KEY shortcode (shortcode),
            KEY active (active)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Crear tabla de envíos
     */
    private static function create_submissions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_SUBMISSIONS;
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            form_data longtext NOT NULL,
            webhook_primary_status varchar(20) DEFAULT 'pending',
            webhook_secondary_status varchar(20) DEFAULT 'pending',
            webhook_primary_response text DEFAULT NULL,
            webhook_secondary_response text DEFAULT NULL,
            chatwoot_status varchar(20) DEFAULT 'pending',
            chatwoot_contact_id varchar(50) DEFAULT NULL,
            chatwoot_conversation_id varchar(50) DEFAULT NULL,
            chatwoot_response text DEFAULT NULL,
            whatsapp_status varchar(20) DEFAULT 'pending',
            clicked_button varchar(20) DEFAULT 'submit',
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            error_code varchar(20) DEFAULT NULL,
            error_message text DEFAULT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY submitted_at (submitted_at),
            KEY chatwoot_contact_id (chatwoot_contact_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Crear tabla de historial IA
     */
    private static function create_ai_history_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_AI_HISTORY;
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            prompt text NOT NULL,
            generated_css longtext NOT NULL,
            provider varchar(20) NOT NULL,
            model varchar(50) NOT NULL,
            tokens_used int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY created_at (created_at),
            KEY provider (provider)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Actualizar tablas (para futuras versiones)
     */
    public static function update_tables() {
        // Por ahora, simplemente recrear las tablas
        self::create_tables();
    }
    
    /**
     * Obtener nombre completo de tabla
     */
    public static function get_table_name($table) {
        global $wpdb;
        
        switch ($table) {
            case 'forms':
                return $wpdb->prefix . self::TABLE_FORMS;
            case 'submissions':
                return $wpdb->prefix . self::TABLE_SUBMISSIONS;
            case 'ai_history':
                return $wpdb->prefix . self::TABLE_AI_HISTORY;
            default:
                return '';
        }
    }
    
    /**
     * Verificar si las tablas existen
     */
    public static function tables_exist() {
        global $wpdb;
        
        $forms_table = self::get_table_name('forms');
        $submissions_table = self::get_table_name('submissions');
        $ai_table = self::get_table_name('ai_history');
        
        $tables = array($forms_table, $submissions_table, $ai_table);
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($result !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Limpiar datos de prueba (útil para desarrollo)
     */
    public static function clean_test_data() {
        global $wpdb;
        
        $submissions_table = self::get_table_name('submissions');
        
        // Eliminar envíos de prueba
        $wpdb->query(
            "DELETE FROM $submissions_table WHERE form_data LIKE '%\"test\":true%'"
        );
    }
}
