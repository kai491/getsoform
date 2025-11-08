/**
 * Getso Forms Editor
 * Maneja el guardado AJAX del editor de formularios.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        $('#getso-form-editor').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $('#save-form-btn');
            const btnHtml = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Guardando...');

            // Serializar todos los datos del formulario
            const formData = $form.serialize();

            $.post(ajaxurl, formData, function(response) {
                $btn.prop('disabled', false).html(btnHtml);
                
                if (response.success) {
                    showNotification('✅ ' + response.data.message, 'success');
                    
                    // Si es un formulario nuevo, actualizar la URL para que tenga el ID
                    if (response.data.form_id && getsoFormsEditor.formId === 0) {
                        const newUrl = window.location.href.replace('form_id=0', 'form_id=' + response.data.form_id);
                        window.history.pushState({ path: newUrl }, '', newUrl);
                        // Actualizar el ID en la variable global
                        getsoFormsEditor.formId = response.data.form_id;
                        // Actualizar el ID en el input oculto
                        $form.find('input[name="form_id"]').val(response.data.form_id);
                    }
                } else {
                    showNotification('❌ ' + response.data.message, 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(btnHtml);
                showNotification('❌ Error de conexión con el servidor.', 'error');
            });
        });

        // Helper para notificaciones
        function showNotification(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wp-heading-inline').after($notice);
            $notice.fadeIn().delay(3000).fadeOut();
        }

        // Lógica para mostrar/ocultar el modo de formulario
        const $modeSelector = $('input[name="form_settings[form_mode]"]');
        const $dynamicContainer = $('#getso-fields-section, #tab-css'); // Tab CSS también se oculta
        const $customContainer = $('#getso-custom-code-container');

        function toggleModeView() {
            if ($('input[name="form_settings[form_mode]"]:checked').val() === 'custom') {
                $dynamicContainer.hide();
                $customContainer.show();
            } else {
                $dynamicContainer.show();
                $customContainer.hide();
            }
        }
        // Al cargar
        toggleModeView();
        // Al cambiar
        $modeSelector.on('change', toggleModeView);
    });

})(jQuery);
