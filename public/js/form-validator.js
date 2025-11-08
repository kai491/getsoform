/**
 * Getso Forms Validator
 * 
 * Validación de formularios en frontend
 * 
 * @package Getso_Forms
 * @since 1.0.0
 */

(function() {
    'use strict';

    class GetsoFormsValidator {
        constructor(formElement) {
            this.form = formElement;
            this.fields = {};
            this.errors = {};
            
            this.init();
        }

        init() {
            // Recopilar todos los campos
            this.collectFields();
            
            // Adjuntar event listeners
            this.attachEventListeners();
        }

        /**
         * Recopilar campos del formulario
         */
        collectFields() {
            const inputs = this.form.querySelectorAll('input:not([type="hidden"]), textarea, select');
            
            inputs.forEach(input => {
                const fieldId = input.id || input.name;
                if (fieldId) {
                    this.fields[fieldId] = {
                        element: input,
                        type: input.type || input.tagName.toLowerCase(),
                        required: input.hasAttribute('required'),
                        errorElement: document.getElementById('error-' + fieldId)
                    };
                }
            });
        }

        /**
         * Adjuntar event listeners
         */
        attachEventListeners() {
            // Validar en blur (cuando el usuario sale del campo)
            Object.values(this.fields).forEach(field => {
                field.element.addEventListener('blur', () => {
                    this.validateField(field);
                });

                // Limpiar error en input
                field.element.addEventListener('input', () => {
                    this.clearFieldError(field);
                });
            });

            // Validar formulario completo en submit
            this.form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    this.focusFirstError();
                }
            });
        }

        /**
         * Validar campo individual
         */
        validateField(field) {
            const value = field.element.value.trim();
            const fieldId = field.element.id || field.element.name;
            
            // Limpiar error previo
            this.clearFieldError(field);

            // Validar campo requerido
            if (field.required && !value) {
                this.setFieldError(field, 'Este campo es obligatorio');
                return false;
            }

            // Si está vacío y no es requerido, es válido
            if (!value) {
                return true;
            }

            // Validaciones específicas por tipo
            let isValid = true;
            let errorMessage = '';

            switch (field.type) {
                case 'email':
                    if (!this.validateEmail(value)) {
                        errorMessage = 'Formato de email inválido';
                        isValid = false;
                    }
                    break;

                case 'tel':
                    if (!this.validateChileanPhone(value)) {
                        errorMessage = 'Formato de teléfono inválido. Use: +56912345678';
                        isValid = false;
                    }
                    break;

                case 'text':
                    // Detectar si es campo RUT por clase o nombre
                    if (field.element.classList.contains('getso-rut-field') || 
                        field.element.dataset.format === 'rut') {
                        if (!this.validateChileanRUT(value)) {
                            errorMessage = 'RUT inválido';
                            isValid = false;
                        }
                    }
                    break;

                case 'number':
                    if (!this.validateNumber(field.element, value)) {
                        errorMessage = 'Número inválido';
                        isValid = false;
                    }
                    break;

                case 'url':
                    if (!this.validateURL(value)) {
                        errorMessage = 'URL inválida';
                        isValid = false;
                    }
                    break;
            }

            if (!isValid) {
                this.setFieldError(field, errorMessage);
                return false;
            }

            return true;
        }

        /**
         * Validar formulario completo
         */
        validateForm() {
            this.errors = {};
            let isValid = true;

            Object.values(this.fields).forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            return isValid;
        }

        /**
         * Validar email
         */
        validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        /**
         * Validar teléfono chileno
         */
        validateChileanPhone(phone) {
            // Limpiar caracteres no numéricos excepto +
            const cleaned = phone.replace(/[\s\-\(\)]/g, '');
            
            // Validar formato: +56XXXXXXXXX (9 dígitos después de +56)
            const regex = /^\+?56[0-9]{9}$/;
            return regex.test(cleaned);
        }

        /**
         * Validar RUT chileno
         */
        validateChileanRUT(rut) {
            // Limpiar RUT
            rut = rut.replace(/[^0-9kK]/g, '');
            
            if (rut.length < 2) {
                return false;
            }

            // Separar número y dígito verificador
            const dv = rut.slice(-1).toUpperCase();
            const numero = rut.slice(0, -1);

            // Calcular dígito verificador
            let suma = 0;
            let multiplo = 2;

            for (let i = numero.length - 1; i >= 0; i--) {
                suma += multiplo * parseInt(numero.charAt(i));
                multiplo = multiplo < 7 ? multiplo + 1 : 2;
            }

            const dvCalculado = 11 - (suma % 11);
            let dvEsperado = '';

            if (dvCalculado === 11) {
                dvEsperado = '0';
            } else if (dvCalculado === 10) {
                dvEsperado = 'K';
            } else {
                dvEsperado = dvCalculado.toString();
            }

            return dv === dvEsperado;
        }

        /**
         * Validar número
         */
        validateNumber(element, value) {
            const num = parseFloat(value);
            
            if (isNaN(num)) {
                return false;
            }

            // Validar min
            if (element.hasAttribute('min')) {
                const min = parseFloat(element.getAttribute('min'));
                if (num < min) {
                    return false;
                }
            }

            // Validar max
            if (element.hasAttribute('max')) {
                const max = parseFloat(element.getAttribute('max'));
                if (num > max) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Validar URL
         */
        validateURL(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }

        /**
         * Establecer error en campo
         */
        setFieldError(field, message) {
            const fieldId = field.element.id || field.element.name;
            this.errors[fieldId] = message;

            // Agregar clase de error
            field.element.classList.add('getso-field-error');
            field.element.parentElement.classList.add('has-error');

            // Mostrar mensaje de error
            if (field.errorElement) {
                field.errorElement.textContent = message;
                field.errorElement.style.display = 'block';
            }
        }

        /**
         * Limpiar error de campo
         */
        clearFieldError(field) {
            const fieldId = field.element.id || field.element.name;
            delete this.errors[fieldId];

            // Quitar clase de error
            field.element.classList.remove('getso-field-error');
            field.element.parentElement.classList.remove('has-error');

            // Ocultar mensaje de error
            if (field.errorElement) {
                field.errorElement.textContent = '';
                field.errorElement.style.display = 'none';
            }
        }

        /**
         * Enfocar primer campo con error
         */
        focusFirstError() {
            const firstErrorField = Object.keys(this.errors)[0];
            if (firstErrorField && this.fields[firstErrorField]) {
                this.fields[firstErrorField].element.focus();
                
                // Scroll suave al campo
                this.fields[firstErrorField].element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        /**
         * Obtener errores
         */
        getErrors() {
            return this.errors;
        }

        /**
         * Verificar si hay errores
         */
        hasErrors() {
            return Object.keys(this.errors).length > 0;
        }
    }

    // Inicializar validadores en todos los formularios Getso
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.getso-form');
        
        forms.forEach(form => {
            new GetsoFormsValidator(form);
        });
    });

    // Exponer clase globalmente
    window.GetsoFormsValidator = GetsoFormsValidator;

})();
