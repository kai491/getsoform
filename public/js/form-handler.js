(function($) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.getso-form-wrapper');
        forms.forEach(formWrapper => {
            const form = formWrapper.querySelector('form');
            if (form) {
                // Inicializa un validador para este formulario específico
                const validator = new GetsoFormsValidator(form);

                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // 1. Ejecutar la validación
                    if (!validator.validateForm()) {
                        // La validación falló, enfocar el primer error
                        validator.focusFirstError();
                        return;
                    }

                    // 2. Si la validación es exitosa, enviar
                    const handler = new GetsoFormHandler(formWrapper, validator);
                    handler.handleSubmit(e);
                });
            }
        });
    });

    class GetsoFormHandler {
        constructor(formWrapper, validator) {
            this.formWrapper = formWrapper;
            this.form = formWrapper.querySelector('form');
            this.validator = validator; // Guardar referencia al validador
            this.formId = formWrapper.dataset.formId;
            this.submitButton = this.form.querySelector('button[type="submit"]');
        }

        async handleSubmit(e) {
            // Validar honeypot
            const honeypot = this.form.querySelector('[name="website"]');
            if (honeypot && honeypot.value !== '') {
                return; // Envío silencioso (es un bot)
            }

            this.setLoading(true);

            // Recopilar datos
            const formData = new FormData(this.form);
            const data = {};
            formData.forEach((value, key) => {
                if (key !== 'website') {
                    // Limpiar formato antes de enviar (ej. quitar puntos del RUT)
                    const field = this.validator.fields[key];
                    let cleanValue = value;

                    if (field && typeof getsoCleanFieldValue === 'function') {
                        const format = field.element.dataset.format;
                        if (format) {
                            cleanValue = getsoCleanFieldValue(value, format);
                        }
                    }
                    data[key] = cleanValue;
                }
            });

            // Enviar a backend
            try {
                const response = await fetch(getsoForms.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'getso_forms_save_submission', // Asegúrate que este action esté registrado
                        nonce: getsoForms.nonce,
                        form_id: this.formId,
                        form_data: JSON.stringify(data)
                    })
                });

                const result = await response.json();
                const strings = getsoForms.strings || { success: "Enviado", error: "Error", processing: "Procesando" };

                if (result.success) {
                    this.showSuccess(strings.success);
                    this.form.reset();
                } else {
                    this.showError(result.data.message || strings.error);
                }
            } catch (error) {
                const strings = getsoForms.strings || { error: "Error de conexión" };
                this.showError(strings.error);
            } finally {
                this.setLoading(false);
            }
        }

        setLoading(loading) {
            if (this.submitButton) {
                const strings = getsoForms.strings || { processing: "Procesando", submit: "Enviar" };
                if (loading) {
                    this.submitButton.disabled = true;
                    this.submitButton.innerHTML = `<span class="dashicons dashicons-update-alt spin"></span> ${strings.processing}`;
                } else {
                    this.submitButton.disabled = false;
                    this.submitButton.innerHTML = this.submitButton.dataset.originalText || strings.submit;
                }
            }
        }

        showSuccess(message) {
            const responseDiv = this.form.querySelector('.getso-form-response');
            responseDiv.className = 'getso-form-response success show';
            responseDiv.textContent = '✅ ' + message;
            responseDiv.style.display = 'block';
        }

        showError(message) {
            const responseDiv = this.form.querySelector('.getso-form-response');
            responseDiv.className = 'getso-form-response error show';
            responseDiv.textContent = '❌ ' + message;
            responseDiv.style.display = 'block';
        }
    }
})(jQuery);
