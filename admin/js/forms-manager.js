/**
 * Getso Forms Manager
 * 
 * Gestión de formularios en lista
 * 
 * @package Getso_Forms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    class GetsoFormsManager {
        constructor() {
            this.init();
        }

        init() {
            this.attachEventListeners();
        }

        /**
         * Adjuntar event listeners
         */
        attachEventListeners() {
            // Duplicar formulario
            $(document).on('click', '.duplicate-form-btn', (e) => {
                e.preventDefault();
                const formId = $(e.currentTarget).data('form-id');
                this.duplicateForm(formId);
            });

            // Eliminar formulario
            $(document).on('click', '.delete-form-btn', (e) => {
                e.preventDefault();
                const formId = $(e.currentTarget).data('form-id');
                const formName = $(e.currentTarget).data('form-name');
                this.deleteForm(formId, formName);
            });

            // Copiar shortcode
            $(document).on('click', '.copy-shortcode-btn', (e) => {
                e.preventDefault();
                const shortcode = $(e.currentTarget).data('shortcode');
                this.copyShortcode(shortcode, e.currentTarget);
            });

            // Toggle activo/inactivo
            $(document).on('change', '.toggle-form-active', (e) => {
                const formId = $(e.currentTarget).data('form-id');
                const isActive = $(e.currentTarget).is(':checked');
                this.toggleFormActive(formId, isActive);
            });
        }

        /**
         * Duplicar formulario
         */
        async duplicateForm(formId) {
            if (!confirm('¿Duplicar este formulario?')) {
                return;
            }

            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'getso_forms_duplicate_form',
                        nonce: getsoFormsAdmin.nonce,
                        form_id: formId
                    }
                });

                if (response.success) {
                    this.showNotification('Formulario duplicado exitosamente', 'success');
                    
                    // Redirigir al editor del nuevo formulario
                    setTimeout(() => {
                        window.location.href = response.data.edit_url;
                    }, 1000);
                } else {
                    throw new Error(response.data.message || 'Error al duplicar formulario');
                }

            } catch (error) {
                console.error('Error duplicating form:', error);
                this.showNotification('Error al duplicar: ' + error.message, 'error');
            }
        }

        /**
         * Eliminar formulario
         */
        async deleteForm(formId, formName) {
            const confirmMessage = `¿Estás seguro de eliminar el formulario "${formName}"?\n\n` +
                'Esta acción también eliminará todos los envíos asociados y no se puede deshacer.';

            if (!confirm(confirmMessage)) {
                return;
            }

            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'getso_forms_delete_form',
                        nonce: getsoFormsAdmin.nonce,
                        form_id: formId
                    }
                });

                if (response.success) {
                    this.showNotification('Formulario eliminado exitosamente', 'success');
                    
                    // Remover fila de la tabla
                    const $row = $(`[data-form-id="${formId}"]`).closest('tr');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si no quedan filas, recargar página
                        if ($('table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    throw new Error(response.data.message || 'Error al eliminar formulario');
                }

            } catch (error) {
                console.error('Error deleting form:', error);
                this.showNotification('Error al eliminar: ' + error.message, 'error');
            }
        }

        /**
         * Copiar shortcode al clipboard
         */
        copyShortcode(shortcode, buttonElement) {
            // Método moderno con Clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode)
                    .then(() => {
                        this.showCopySuccess(buttonElement);
                        this.showNotification('Shortcode copiado al portapapeles', 'success');
                    })
                    .catch(() => {
                        this.fallbackCopyShortcode(shortcode, buttonElement);
                    });
            } else {
                this.fallbackCopyShortcode(shortcode, buttonElement);
            }
        }

        /**
         * Método fallback para copiar shortcode
         */
        fallbackCopyShortcode(shortcode, buttonElement) {
            const textarea = document.createElement('textarea');
            textarea.value = shortcode;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                this.showCopySuccess(buttonElement);
                this.showNotification('Shortcode copiado al portapapeles', 'success');
            } catch (err) {
                this.showNotification('Error al copiar. Copia manualmente: ' + shortcode, 'error');
            }
            
            document.body.removeChild(textarea);
        }

        /**
         * Mostrar feedback visual al copiar
         */
        showCopySuccess(buttonElement) {
            const $btn = $(buttonElement);
            const originalHTML = $btn.html();
            
            $btn.html('<span class="dashicons dashicons-yes"></span> ¡Copiado!')
                .addClass('copied');
            
            setTimeout(() => {
                $btn.html(originalHTML).removeClass('copied');
            }, 2000);
        }

        /**
         * Toggle estado activo/inactivo
         */
        async toggleFormActive(formId, isActive) {
            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'getso_forms_toggle_active',
                        nonce: getsoFormsAdmin.nonce,
                        form_id: formId,
                        active: isActive ? 1 : 0
                    }
                });

                if (response.success) {
                    const statusText = isActive ? 'activado' : 'desactivado';
                    this.showNotification(`Formulario ${statusText}`, 'success');
                    
                    // Actualizar badge visual
                    const $row = $(`[data-form-id="${formId}"]`).closest('tr');
                    const $statusBadge = $row.find('.form-status-badge');
                    
                    if (isActive) {
                        $statusBadge.removeClass('inactive').addClass('active').text('Activo');
                    } else {
                        $statusBadge.removeClass('active').addClass('inactive').text('Inactivo');
                    }
                } else {
                    throw new Error(response.data.message || 'Error al cambiar estado');
                }

            } catch (error) {
                console.error('Error toggling form active:', error);
                this.showNotification('Error al cambiar estado: ' + error.message, 'error');
                
                // Revertir checkbox
                const $checkbox = $(`[data-form-id="${formId}"]`);
                $checkbox.prop('checked', !isActive);
            }
        }

        /**
         * Mostrar notificación
         */
        showNotification(message, type = 'success') {
            const notification = $('<div>', {
                class: 'getso-notification getso-notification-' + type,
                html: `
                    <span class="dashicons dashicons-${this.getNotificationIcon(type)}"></span>
                    <span>${message}</span>
                `
            });

            $('body').append(notification);

            setTimeout(() => {
                notification.addClass('show');
            }, 10);

            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        /**
         * Obtener icono de notificación
         */
        getNotificationIcon(type) {
            const icons = {
                success: 'yes-alt',
                error: 'dismiss',
                warning: 'warning',
                info: 'info'
            };
            return icons[type] || 'info';
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        window.getsoFormsManager = new GetsoFormsManager();
    });

})(jQuery);
