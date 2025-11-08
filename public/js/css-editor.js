/**
 * Getso Forms CSS Editor
 * 
 * Maneja el editor CSS con CodeMirror
 * 
 * @package Getso_Forms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    class GetsoFormsCSSEditor {
        constructor() {
            this.editor = null;
            this.originalCSS = '';
            this.hasChanges = false;
            
            this.init();
        }

        init() {
            // Esperar a que CodeMirror esté disponible
            if (typeof CodeMirror === 'undefined') {
                console.error('CodeMirror no está cargado');
                return;
            }

            this.initializeEditor();
            this.attachEventListeners();
            this.setupKeyboardShortcuts();
            
            // Conectar con AI Chat
            if (window.getsoAIChat) {
                window.getsoAIChat.setCSSEditor(this.editor);
            }
        }

        /**
         * Inicializar CodeMirror
         */
        initializeEditor() {
            const textarea = document.getElementById('custom-css-editor');
            
            if (!textarea) {
                return;
            }

            this.originalCSS = textarea.value;

            this.editor = CodeMirror.fromTextArea(textarea, {
                mode: 'css',
                theme: 'material',
                lineNumbers: true,
                lineWrapping: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                styleActiveLine: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                extraKeys: {
                    'Ctrl-Space': 'autocomplete',
                    'Tab': function(cm) {
                        cm.replaceSelection('  ');
                    }
                },
                hint: CodeMirror.hint.css
            });

            // Detectar cambios
            this.editor.on('change', () => {
                this.hasChanges = true;
                this.updateSaveButtonState();
            });

            // Auto-actualizar preview en tiempo real (con debounce)
            let previewTimeout;
            this.editor.on('change', () => {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(() => {
                    this.updatePreview();
                }, 500);
            });

            // Actualizar preview inicial
            setTimeout(() => this.updatePreview(), 100);
        }

        /**
         * Adjuntar event listeners
         */
        attachEventListeners() {
            // Botón guardar CSS
            $('#save-css-btn').on('click', (e) => {
                e.preventDefault();
                this.saveCSS();
            });

            // Botón restaurar
            $('#restore-css-btn').on('click', (e) => {
                e.preventDefault();
                this.restoreCSS();
            });

            // Prevenir salir sin guardar
            $(window).on('beforeunload', (e) => {
                if (this.hasChanges) {
                    e.preventDefault();
                    return 'Tienes cambios sin guardar. ¿Estás seguro de salir?';
                }
            });
        }

        /**
         * Configurar atajos de teclado
         */
        setupKeyboardShortcuts() {
            // Ctrl+S para guardar
            $(document).on('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.saveCSS();
                }
            });

            // Ctrl+Z para deshacer (CodeMirror lo maneja por defecto)
            // Ctrl+Shift+Z para rehacer
        }

        /**
         * Guardar CSS
         */
        async saveCSS() {
            const cssContent = this.editor.getValue();
            const formId = getsoFormsEditor.formId;

            if (!formId) {
                this.showNotification('Error: ID de formulario no encontrado', 'error');
                return;
            }

            // Mostrar loading
            $('#save-css-btn').prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Guardando...');

            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'getso_forms_save_css',
                        nonce: getsoFormsEditor.nonce,
                        form_id: formId,
                        custom_css: cssContent
                    }
                });

                if (response.success) {
                    this.hasChanges = false;
                    this.originalCSS = cssContent;
                    this.showNotification('CSS guardado exitosamente', 'success');
                } else {
                    throw new Error(response.data.message || 'Error al guardar CSS');
                }

            } catch (error) {
                console.error('Error saving CSS:', error);
                this.showNotification('Error al guardar: ' + error.message, 'error');
            } finally {
                $('#save-css-btn').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar CSS');
            }
        }

        /**
         * Restaurar CSS original
         */
        restoreCSS() {
            if (!confirm('¿Estás seguro de restaurar el CSS? Se perderán los cambios no guardados.')) {
                return;
            }

            this.editor.setValue(this.originalCSS);
            this.hasChanges = false;
            this.updatePreview();
            this.showNotification('CSS restaurado', 'info');
        }

        /**
         * Actualizar preview
         */
        updatePreview() {
            if (window.getsoAIChat) {
                window.getsoAIChat.updatePreview();
            }
        }

        /**
         * Actualizar estado del botón guardar
         */
        updateSaveButtonState() {
            const btn = $('#save-css-btn');
            if (this.hasChanges) {
                btn.addClass('has-changes');
            } else {
                btn.removeClass('has-changes');
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

        /**
         * Obtener valor del editor
         */
        getValue() {
            return this.editor ? this.editor.getValue() : '';
        }

        /**
         * Establecer valor del editor
         */
        setValue(value) {
            if (this.editor) {
                this.editor.setValue(value);
            }
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('#custom-css-editor').length) {
            window.getsoFormsCSSEditor = new GetsoFormsCSSEditor();
        }
    });

})(jQuery);
