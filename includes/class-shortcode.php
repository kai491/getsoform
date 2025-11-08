<?php
/**
 * Clase Shortcode
 * Maneja el renderizado de formularios mediante shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Shortcode {
    
    public function __construct() {
        add_shortcode('getso_form', array($this, 'render'));
    }
    
    /**
     * Renderizar formulario
     */
    public function render($atts) {
        $atts = shortcode_atts(array(
            'id' => ''
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>Error: ID de formulario no especificado</p>';
        }
        
        $form = Getso_Forms_Manager::get_form($atts['id']);
        
        if (!$form || $form->active != 1) {
            return '<p>Formulario no encontrado o inactivo</p>';
        }
        
        ob_start();
        $this->render_form($form);
        return ob_get_clean();
    }
    
    /**
     * Renderizar HTML del formulario
     */
    private function render_form($form) {
        $form_id = 'getso-form-' . $form->id . '-' . wp_rand(1000, 9999);
        $fields = $form->form_fields['fields'] ?? array();
        ?>
        
        <style>
        <?php echo $form->form_css; ?>
        </style>
        
        <div class="getso-form-wrapper" id="<?php echo esc_attr($form_id); ?>" data-form-id="<?php echo esc_attr($form->id); ?>">
            <div class="getso-form-container">
                <form class="getso-contact-form" novalidate>
                    
                    <?php 
                    $current_row = array();
                    foreach ($fields as $field) {
                        $width = $field['width'] ?? '100%';
                        
                        if ($width === '100%' && !empty($current_row)) {
                            $this->render_row($current_row, $form_id);
                            $current_row = array();
                        }
                        
                        $current_row[] = $field;
                        
                        if ($width === '100%') {
                            $this->render_row($current_row, $form_id);
                            $current_row = array();
                        } elseif (count($current_row) >= 2) {
                            $this->render_row($current_row, $form_id);
                            $current_row = array();
                        }
                    }
                    
                    if (!empty($current_row)) {
                        $this->render_row($current_row, $form_id);
                    }
                    ?>
                    
                    <input type="text" name="website" class="getso-honeypot" tabindex="-1" autocomplete="off">
                    
                    <div class="getso-form-buttons">
                        <button type="submit" class="getso-btn getso-btn-primary" data-action="submit">
                            <?php 
                            // CORRECCIÓN: El texto del botón se guarda en 'submit_button_text', no en 'messages.submit_button'
                            echo esc_html($form->form_settings['submit_button_text'] ?? 'Enviar'); 
                            ?>
                        </button>
                        
                        <?php if (isset($form->form_settings['whatsapp']) && $form->form_settings['whatsapp']['enabled']) : ?>
                        <button type="button" class="getso-btn getso-btn-whatsapp" data-action="whatsapp">
                            💬 WhatsApp
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="getso-form-response" style="display: none;"></div>
                </form>
            </div>
        </div>
        
        <script>
        (function() {
            const formWrapper = document.getElementById('<?php echo esc_js($form_id); ?>');
            const formId = <?php echo intval($form->id); ?>;
            const formSettings = <?php echo wp_json_encode($form->form_settings); ?>;
            
            // Lógica de envío se maneja en form-handler.js
        })();
        </script>
        
        <?php
    }
    
    /**
     * Renderizar fila de campos
     */
    private function render_row($fields, $form_id) {
        ?>
        <div class="getso-form-row">
            <?php foreach ($fields as $field) {
                $this->render_field($field, $form_id);
            } ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar campo individual
     */
    private function render_field($field, $form_id) {
        $field_id = $form_id . '-' . $field['name'];
        $required = !empty($field['required']);
        $css_class = $field['css_class'] ?? '';
        ?>
        
        <div class="getso-form-group <?php echo esc_attr($css_class); ?>">
            <label for="<?php echo esc_attr($field_id); ?>">
                <?php echo esc_html($field['label']); ?>
                <?php if ($required) : ?>
                <span class="required">*</span>
                <?php endif; ?>
            </label>
            
            <?php
            switch ($field['type']) {
                case 'textarea':
                    $this->render_textarea($field, $field_id, $required);
                    break;
                    
                case 'select':
                    $this->render_select($field, $field_id, $required);
                    break;
                    
                default:
                    $this->render_input($field, $field_id, $required);
                    break;
            }
            ?>
            
            <span class="getso-error-message" data-field="<?php echo esc_attr($field['name']); ?>"></span>
        </div>
        
        <?php
    }
    
    /**
     * Renderizar input
     */
    private function render_input($field, $field_id, $required) {
        ?>
        <input 
            type="<?php echo esc_attr($field['type']); ?>"
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($field['name']); ?>"
            <?php echo $required ? 'required' : ''; ?>
            placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
            data-validation="<?php echo esc_attr($field['validation'] ?? ''); ?>"
            data-format="<?php echo esc_attr($field['format'] ?? ''); ?>"
        >
        <?php
    }
    
    /**
     * Renderizar textarea
     */
    private function render_textarea($field, $field_id, $required) {
        ?>
        <textarea 
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($field['name']); ?>"
            <?php echo $required ? 'required' : ''; ?>
            rows="<?php echo esc_attr($field['rows'] ?? 4); ?>"
            placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
        ></textarea>
        <?php
    }
    
    /**
     * Renderizar select
     */
    private function render_select($field, $field_id, $required) {
        ?>
        <select 
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($field['name']); ?>"
            <?php echo $required ? 'required' : ''; ?>
        >
            <?php foreach ($field['options'] as $option) : ?>
            <option value="<?php echo esc_attr($option['value']); ?>">
                <?php echo esc_html($option['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}