<?php
/**
 * Fields Builder Class
 *
 * Construye y renderiza campos de formulario dinámicamente
 *
 * @package Getso_Forms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Getso_Forms_Fields_Builder {

    /**
     * Tipos de campos soportados
     */
    private $supported_types = [
        'text',
        'email',
        'tel',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'number',
        'date',
        'file',
        'hidden',
        'rut'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Inicialización si es necesaria
    }

    /**
     * Construir campo completo
     *
     * @param array $field_config Configuración del campo
     * @return string HTML del campo
     */
    public function build_field($field_config) {
        if (!isset($field_config['type']) || !in_array($field_config['type'], $this->supported_types)) {
            return '';
        }

        $field_id = isset($field_config['id']) ? $field_config['id'] : 'field_' . uniqid();
        $field_name = isset($field_config['name']) ? $field_config['name'] : $field_id;
        $field_label = isset($field_config['label']) ? $field_config['label'] : '';
        $field_placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';
        $field_required = isset($field_config['required']) && $field_config['required'] ? true : false;
        $field_class = isset($field_config['class']) ? $field_config['class'] : '';
        $field_value = isset($field_config['value']) ? $field_config['value'] : '';

        $wrapper_class = 'getso-form-field getso-field-' . esc_attr($field_config['type']);
        if ($field_required) {
            $wrapper_class .= ' getso-field-required';
        }

        $html = '<div class="' . esc_attr($wrapper_class) . '" data-field-id="' . esc_attr($field_id) . '">';

        // Label
        if (!empty($field_label) && $field_config['type'] !== 'hidden') {
            $html .= '<label for="' . esc_attr($field_id) . '" class="getso-field-label">';
            $html .= esc_html($field_label);
            if ($field_required) {
                $html .= ' <span class="getso-required-mark">*</span>';
            }
            $html .= '</label>';
        }

        // Render field según tipo
        $html .= $this->render_field($field_config);

        // Error message container
        if ($field_config['type'] !== 'hidden') {
            $html .= '<span class="getso-field-error" id="error-' . esc_attr($field_id) . '"></span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderizar campo según tipo
     *
     * @param array $field_config Configuración del campo
     * @return string HTML del campo
     */
    public function render_field($field_config) {
        $type = $field_config['type'];
        $method_name = 'render_' . $type . '_field';

        if (method_exists($this, $method_name)) {
            return $this->$method_name($field_config);
        }

        return '';
    }

    /**
     * Renderizar campo text
     */
    private function render_text_field($config) {
        $html = '<input type="text" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        }
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        if (isset($config['maxlength'])) {
            $html .= 'maxlength="' . esc_attr($config['maxlength']) . '" ';
        }
        if (isset($config['pattern'])) {
            $html .= 'pattern="' . esc_attr($config['pattern']) . '" ';
        }
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo email
     */
    private function render_email_field($config) {
        $html = '<input type="email" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input getso-email-field ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        }
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo tel
     */
    private function render_tel_field($config) {
        $html = '<input type="tel" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input getso-tel-field ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        } else {
            $html .= 'placeholder="+56912345678" ';
        }
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        $html .= 'data-format="phone" ';
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo RUT chileno
     */
    private function render_rut_field($config) {
        $html = '<input type="text" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input getso-rut-field ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        } else {
            $html .= 'placeholder="12.345.678-9" ';
        }
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        $html .= 'data-format="rut" ';
        $html .= 'maxlength="12" ';
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo textarea
     */
    private function render_textarea_field($config) {
        $html = '<textarea ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-textarea ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        if (isset($config['rows'])) {
            $html .= 'rows="' . esc_attr($config['rows']) . '" ';
        } else {
            $html .= 'rows="4" ';
        }
        if (isset($config['maxlength'])) {
            $html .= 'maxlength="' . esc_attr($config['maxlength']) . '" ';
        }
        $html .= '>';
        if (!empty($config['value'])) {
            $html .= esc_textarea($config['value']);
        }
        $html .= '</textarea>';
        return $html;
    }

    /**
     * Renderizar campo select
     */
    private function render_select_field($config) {
        $html = '<select ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-select ' . esc_attr($config['class'] ?? '') . '" ';
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        $html .= '>';

        // Opción por defecto
        if (isset($config['placeholder']) && !empty($config['placeholder'])) {
            $html .= '<option value="">' . esc_html($config['placeholder']) . '</option>';
        }

        // Opciones
        // CORRECCIÓN: Se itera sobre un array de objetos (como en class-forms-manager.php)
        // en lugar de un array asociativo.
        if (isset($config['options']) && is_array($config['options'])) {
            foreach ($config['options'] as $option) {
                if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                    $option_value = $option['value'];
                    $option_label = $option['label'];
                    $selected = (isset($config['value']) && $config['value'] == $option_value) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>';
                    $html .= esc_html($option_label);
                    $html .= '</option>';
                }
            }
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Renderizar campo radio
     */
    private function render_radio_field($config) {
        $html = '<div class="getso-radio-group">';

        // CORRECCIÓN: Se itera sobre un array de objetos.
        if (isset($config['options']) && is_array($config['options'])) {
            $counter = 0;
            foreach ($config['options'] as $option) {
                if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                    $option_value = $option['value'];
                    $option_label = $option['label'];
                    $radio_id = $config['id'] . '_' . $counter;
                    $checked = (isset($config['value']) && $config['value'] == $option_value) ? ' checked' : '';
                    
                    $html .= '<label class="getso-radio-label">';
                    $html .= '<input type="radio" ';
                    $html .= 'id="' . esc_attr($radio_id) . '" ';
                    $html .= 'name="' . esc_attr($config['name']) . '" ';
                    $html .= 'value="' . esc_attr($option_value) . '" ';
                    $html .= 'class="getso-radio ' . esc_attr($config['class'] ?? '') . '" ';
                    if (isset($config['required']) && $config['required']) {
                        $html .= 'required ';
                    }
                    $html .= $checked;
                    $html .= '/>';
                    $html .= '<span class="getso-radio-text">' . esc_html($option_label) . '</span>';
                    $html .= '</label>';
                    
                    $counter++;
                }
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderizar campo checkbox
     */
    private function render_checkbox_field($config) {
        $html = '<div class="getso-checkbox-group">';

        // CORRECCIÓN: Se itera sobre un array de objetos.
        if (isset($config['options']) && is_array($config['options'])) {
            $counter = 0;
            $selected_values = isset($config['value']) ? (array) $config['value'] : [];
            
            foreach ($config['options'] as $option) {
                if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                    $option_value = $option['value'];
                    $option_label = $option['label'];
                    $checkbox_id = $config['id'] . '_' . $counter;
                    $checked = in_array($option_value, $selected_values) ? ' checked' : '';
                    
                    $html .= '<label class="getso-checkbox-label">';
                    $html .= '<input type="checkbox" ';
                    $html .= 'id="' . esc_attr($checkbox_id) . '" ';
                    $html .= 'name="' . esc_attr($config['name']) . '[]" ';
                    $html .= 'value="' . esc_attr($option_value) . '" ';
                    $html .= 'class="getso-checkbox ' . esc_attr($config['class'] ?? '') . '" ';
                    $html .= $checked;
                    $html .= '/>';
                    $html .= '<span class="getso-checkbox-text">' . esc_html($option_label) . '</span>';
                    $html .= '</label>';
                    
                    $counter++;
                }
            }
        } else {
            // Checkbox simple (único)
            $checked = (isset($config['value']) && $config['value']) ? ' checked' : '';
            $html .= '<label class="getso-checkbox-label">';
            $html .= '<input type="checkbox" ';
            $html .= 'id="' . esc_attr($config['id']) . '" ';
            $html .= 'name="' . esc_attr($config['name']) . '" ';
            $html .= 'value="1" ';
            $html .= 'class="getso-checkbox ' . esc_attr($config['class'] ?? '') . '" ';
            if (isset($config['required']) && $config['required']) {
                $html .= 'required ';
            }
            $html .= $checked;
            $html .= '/>';
            if (isset($config['checkbox_label'])) {
                $html .= '<span class="getso-checkbox-text">' . esc_html($config['checkbox_label']) . '</span>';
            }
            $html .= '</label>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderizar campo number
     */
    private function render_number_field($config) {
        $html = '<input type="number" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['placeholder'])) {
            $html .= 'placeholder="' . esc_attr($config['placeholder']) . '" ';
        }
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        if (isset($config['min'])) {
            $html .= 'min="' . esc_attr($config['min']) . '" ';
        }
        if (isset($config['max'])) {
            $html .= 'max="' . esc_attr($config['max']) . '" ';
        }
        if (isset($config['step'])) {
            $html .= 'step="' . esc_attr($config['step']) . '" ';
        }
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo date
     */
    private function render_date_field($config) {
        $html = '<input type="date" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input ' . esc_attr($config['class'] ?? '') . '" ';
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        if (isset($config['min'])) {
            $html .= 'min="' . esc_attr($config['min']) . '" ';
        }
        if (isset($config['max'])) {
            $html .= 'max="' . esc_attr($config['max']) . '" ';
        }
        $html .= '/>';
        return $html;
    }

    /**
     * Renderizar campo file
     */
    private function render_file_field($config) {
        $html = '<input type="file" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        $html .= 'class="getso-input getso-file-input ' . esc_attr($config['class'] ?? '') . '" ';
        if (isset($config['required']) && $config['required']) {
            $html .= 'required ';
        }
        if (isset($config['accept'])) {
            $html .= 'accept="' . esc_attr($config['accept']) . '" ';
        }
        if (isset($config['multiple']) && $config['multiple']) {
            $html .= 'multiple ';
        }
        $html .= '/>';
        
        // Info sobre tipos permitidos
        if (isset($config['accept'])) {
            $html .= '<small class="getso-file-info">Formatos permitidos: ' . esc_html($config['accept']) . '</small>';
        }
        
        return $html;
    }

    /**
     * Renderizar campo hidden
     */
    private function render_hidden_field($config) {
        $html = '<input type="hidden" ';
        $html .= 'id="' . esc_attr($config['id']) . '" ';
        $html .= 'name="' . esc_attr($config['name']) . '" ';
        if (!empty($config['value'])) {
            $html .= 'value="' . esc_attr($config['value']) . '" ';
        }
        $html .= '/>';
        return $html;
    }

    /**
     * Validar campo
     *
     * @param array $field_config Configuración del campo
     * @param mixed $value Valor a validar
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validate_field($field_config, $value) {
        $result = ['valid' => true, 'error' => ''];

        // Verificar si es requerido
        if (isset($field_config['required']) && $field_config['required']) {
            if (empty($value) && $value !== '0') {
                return [
                    'valid' => false,
                    'error' => 'Este campo es obligatorio'
                ];
            }
        }

        // Si está vacío y no es requerido, es válido
        if (empty($value) && $value !== '0') {
            return $result;
        }

        // Validaciones específicas por tipo
        switch ($field_config['type']) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return [
                        'valid' => false,
                        'error' => 'Formato de email inválido'
                    ];
                }
                break;

            case 'tel':
                // Validar formato chileno +56XXXXXXXXX
                if (!preg_match('/^\+?56[0-9]{9}$/', str_replace([' ', '-'], '', $value))) {
                    return [
                        'valid' => false,
                        'error' => 'Formato de teléfono inválido. Use: +56912345678'
                    ];
                }
                break;

            case 'rut':
                if (!$this->validate_chilean_rut($value)) {
                    return [
                        'valid' => false,
                        'error' => 'RUT inválido'
                    ];
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return [
                        'valid' => false,
                        'error' => 'Debe ser un número válido'
                    ];
                }
                
                if (isset($field_config['min']) && $value < $field_config['min']) {
                    return [
                        'valid' => false,
                        'error' => 'El valor mínimo es ' . $field_config['min']
                    ];
                }
                
                if (isset($field_config['max']) && $value > $field_config['max']) {
                    return [
                        'valid' => false,
                        'error' => 'El valor máximo es ' . $field_config['max']
                    ];
                }
                break;

            case 'date':
                if (!strtotime($value)) {
                    return [
                        'valid' => false,
                        'error' => 'Fecha inválida'
                    ];
                }
                break;
        }

        // Validar maxlength
        if (isset($field_config['maxlength']) && strlen($value) > $field_config['maxlength']) {
            return [
                'valid' => false,
                'error' => 'Máximo ' . $field_config['maxlength'] . ' caracteres'
            ];
        }

        // Validar pattern
        if (isset($field_config['pattern']) && !preg_match('/' . $field_config['pattern'] . '/', $value)) {
            return [
                'valid' => false,
                'error' => 'Formato inválido'
            ];
        }

        return $result;
    }

    /**
     * Validar RUT chileno
     *
     * @param string $rut RUT a validar
     * @return bool
     */
    private function validate_chilean_rut($rut) {
        // Limpiar RUT
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        
        if (strlen($rut) < 2) {
            return false;
        }

        // Separar número y dígito verificador
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);

        // Calcular dígito verificador
        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $multiplo * intval($numero[$i]);
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $dvCalculado = 11 - ($suma % 11);
        
        if ($dvCalculado == 11) {
            $dvCalculado = '0';
        } elseif ($dvCalculado == 10) {
            $dvCalculado = 'K';
        } else {
            $dvCalculado = strval($dvCalculado);
        }

        return strtoupper($dv) === strtoupper($dvCalculado);
    }

    /**
     * Obtener tipos soportados
     *
     * @return array
     */
    public function get_supported_types() {
        return $this->supported_types;
    }
}