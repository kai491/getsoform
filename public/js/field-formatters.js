/**
 * Getso Forms Field Formatters
 * 
 * Formateo automático de campos en tiempo real
 * 
 * @package Getso_Forms
 * @since 1.0.0
 */

(function() {
    'use strict';

    class GetsoFormsFieldFormatters {
        constructor() {
            this.init();
        }

        init() {
            document.addEventListener('DOMContentLoaded', () => {
                this.attachFormatters();
            });
        }

        /**
         * Adjuntar formateadores a los campos
         */
        attachFormatters() {
            // Formatear RUT
            const rutFields = document.querySelectorAll('.getso-rut-field, [data-format="rut"]');
            rutFields.forEach(field => {
                field.addEventListener('input', (e) => this.formatRUT(e));
                field.addEventListener('blur', (e) => this.formatRUT(e));
            });

            // Formatear teléfono
            const phoneFields = document.querySelectorAll('.getso-tel-field, [data-format="phone"]');
            phoneFields.forEach(field => {
                field.addEventListener('input', (e) => this.formatPhone(e));
                field.addEventListener('blur', (e) => this.formatPhone(e));
            });

            // Auto-agregar +56 al inicio en campos de teléfono vacíos
            phoneFields.forEach(field => {
                field.addEventListener('focus', (e) => {
                    if (!e.target.value || e.target.value.trim() === '') {
                        e.target.value = '+56';
                        // Posicionar cursor al final
                        setTimeout(() => {
                            e.target.setSelectionRange(3, 3);
                        }, 0);
                    }
                });
            });
        }

        /**
         * Formatear RUT chileno: 12.345.678-9
         */
        formatRUT(event) {
            const input = event.target;
            let value = input.value;

            // Eliminar todo excepto números y K
            value = value.replace(/[^0-9kK]/g, '');

            // Limitar longitud
            if (value.length > 9) {
                value = value.substring(0, 9);
            }

            if (value.length > 1) {
                // Separar número y dígito verificador
                const dv = value.slice(-1);
                let numero = value.slice(0, -1);

                // Formatear número con puntos
                numero = this.addThousandsSeparator(numero);

                // Construir RUT formateado
                value = numero + '-' + dv;
            }

            input.value = value;
        }

        /**
         * Formatear teléfono chileno: +56912345678
         */
        formatPhone(event) {
            const input = event.target;
            let value = input.value;

            // Eliminar todo excepto números y +
            value = value.replace(/[^0-9+]/g, '');

            // Si no empieza con +56, agregar
            if (!value.startsWith('+56')) {
                // Si empieza con 56
                if (value.startsWith('56')) {
                    value = '+' + value;
                }
                // Si empieza con 9 (número móvil sin código)
                else if (value.startsWith('9') && value.length === 9) {
                    value = '+56' + value;
                }
                // Si no tiene nada, agregar +56
                else if (value && !value.startsWith('+')) {
                    value = '+56' + value;
                }
            }

            // Limitar longitud: +56 (3) + 9 dígitos = 12 caracteres
            if (value.length > 12) {
                value = value.substring(0, 12);
            }

            // Validar que después de +56 solo haya 9 dígitos
            if (value.length > 3) {
                const phoneNumber = value.substring(3);
                if (phoneNumber.length > 9) {
                    value = value.substring(0, 12);
                }
            }

            input.value = value;
        }

        /**
         * Agregar separador de miles con puntos
         */
        addThousandsSeparator(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        /**
         * Formatear número con separador de miles
         */
        formatNumber(event) {
            const input = event.target;
            let value = input.value;

            // Eliminar caracteres no numéricos excepto punto decimal y menos
            value = value.replace(/[^0-9.-]/g, '');

            // Separar parte entera y decimal
            const parts = value.split('.');
            
            // Formatear parte entera con separador de miles
            if (parts[0]) {
                parts[0] = this.addThousandsSeparator(parts[0]);
            }

            // Reconstruir número
            value = parts.join('.');

            input.value = value;
        }

        /**
         * Limpiar formato para envío
         */
        static cleanFormatting(value, type) {
            switch (type) {
                case 'rut':
                    return value.replace(/[^0-9kK]/g, '');
                
                case 'phone':
                    return value.replace(/[\s\-\(\)]/g, '');
                
                case 'number':
                    return value.replace(/[^0-9.-]/g, '');
                
                default:
                    return value;
            }
        }
    }

    // Inicializar formateadores
    new GetsoFormsFieldFormatters();

    // Exponer clase globalmente
    window.GetsoFormsFieldFormatters = GetsoFormsFieldFormatters;

})();

/**
 * Helper global para limpiar valores antes de enviar
 */
function getsoCleanFieldValue(value, type) {
    return GetsoFormsFieldFormatters.cleanFormatting(value, type);
}
