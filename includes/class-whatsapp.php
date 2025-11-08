<?php
/**
 * Clase WhatsApp
 * Genera enlaces de WhatsApp con mensajes predefinidos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Whatsapp {
    
    /**
     * Generar enlace de WhatsApp
     */
    public function generate_link($form_settings, $form_data) {
        if (!isset($form_settings['whatsapp']) || $form_settings['whatsapp']['enabled'] !== true) {
            return null;
        }
        
        $number = $form_settings['whatsapp']['number'] ?? '';
        $template = $form_settings['whatsapp']['message_template'] ?? '';
        
        if (empty($number) || empty($template)) {
            return null;
        }
        
        // Reemplazar variables en el template
        $message = $this->replace_variables($template, $form_data);
        
        return 'https://wa.me/' . $number . '?text=' . urlencode($message);
    }
    
    /**
     * Reemplazar variables en template
     */
    private function replace_variables($template, $form_data) {
        foreach ($form_data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
}
