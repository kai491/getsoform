/**
 * Getso Forms AI Chat
 * 
 * Maneja la interacción con la IA para generar CSS
 * 
 * @package Getso_Forms
 * @since 1.0.0
 */

class GetsoFormsAIChat {
    constructor() {
        this.messagesContainer = document.getElementById('ai-messages');
        this.promptInput = document.getElementById('ai-prompt-input');
        this.sendButton = document.getElementById('send-ai-prompt');
        this.statusDiv = document.getElementById('ai-status');
        this.cssEditor = null;
        this.previewIframe = document.getElementById('css-preview-iframe');
        
        this.conversationHistory = [];
        this.isProcessing = false;
        this.rateLimit = {
            requests: 0,
            resetTime: Date.now() + (60 * 60 * 1000) // 1 hora
        };
        this.maxRequestsPerHour = 20;
        
        this.init();
    }

    init() {
        // Event listeners
        this.sendButton.addEventListener('click', () => this.sendPrompt());
        this.promptInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && e.ctrlKey) {
                this.sendPrompt();
            }
        });

        // Template buttons
        document.querySelectorAll('.ai-template').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const template = e.currentTarget.dataset.template;
                this.applyTemplate(template);
            });
        });
    }

    /**
     * Enviar prompt a la IA
     */
    async sendPrompt() {
        const prompt = this.promptInput.value.trim();
        
        if (!prompt) {
            this.showStatus('Por favor escribe un mensaje', 'error');
            return;
        }

        if (this.isProcessing) {
            this.showStatus('Procesando solicitud anterior...', 'warning');
            return;
        }

        // Verificar rate limit
        if (!this.checkRateLimit()) {
            this.showStatus('Has alcanzado el límite de solicitudes por hora. Intenta más tarde.', 'error');
            return;
        }

        this.isProcessing = true;
        this.sendButton.disabled = true;
        this.promptInput.disabled = true;
        
        // Agregar mensaje del usuario al chat
        this.addMessageToChat('user', prompt);
        this.promptInput.value = '';

        // Mostrar estado de carga
        this.showStatus('Generando CSS con IA...', 'loading');

        try {
            // Preparar contexto
            const currentCSS = this.cssEditor ? this.cssEditor.getValue() : '';
            const formHTML = this.getFormPreviewHTML();

            // Enviar solicitud AJAX
            const response = await this.makeAjaxRequest({
                action: 'getso_forms_ai_generate_css',
                nonce: getsoFormsEditor.nonce,
                prompt: prompt,
                current_css: currentCSS,
                form_html: formHTML,
                conversation_history: this.conversationHistory
            });

            if (response.success) {
                const generatedCSS = response.data.css;
                const explanation = response.data.explanation || 'CSS generado exitosamente.';

                // Agregar respuesta de la IA al chat
                this.addMessageToChat('assistant', explanation);

                // Actualizar editor CSS
                if (this.cssEditor) {
                    this.cssEditor.setValue(generatedCSS);
                }

                // Actualizar preview
                this.updatePreview();

                this.showStatus('CSS generado exitosamente', 'success');
                
                // Incrementar contador de rate limit
                this.rateLimit.requests++;
            } else {
                throw new Error(response.data.message || 'Error al generar CSS');
            }

        } catch (error) {
            console.error('Error AI Chat:', error);
            this.addMessageToChat('assistant', 'Lo siento, hubo un error al generar el CSS. Por favor intenta nuevamente.');
            this.showStatus('Error: ' + error.message, 'error');
        } finally {
            this.isProcessing = false;
            this.sendButton.disabled = false;
            this.promptInput.disabled = false;
            this.promptInput.focus();
        }
    }

    /**
     * Aplicar template predefinido
     */
    async applyTemplate(templateName) {
        if (this.isProcessing) {
            this.showStatus('Procesando solicitud anterior...', 'warning');
            return;
        }

        const templates = {
            modern: 'Crea un diseño moderno con bordes redondeados, sombras sutiles, colores azul #0073aa, inputs con altura de 45px, y animaciones suaves en hover.',
            minimal: 'Crea un diseño minimalista con líneas limpias, sin bordes decorativos, colores neutros grises, tipografía sans-serif, y espaciado generoso.',
            colorful: 'Crea un diseño colorido y alegre con degradados vibrantes, inputs con fondos de colores pastel, bordes coloridos, y botones con colores brillantes.',
            dark: 'Crea un diseño en modo oscuro con fondo negro/gris oscuro, texto blanco, inputs con fondo gris oscuro, y acentos en color azul neón.'
        };

        const prompt = templates[templateName];
        if (!prompt) return;

        this.promptInput.value = prompt;
        await this.sendPrompt();
    }

    /**
     * Agregar mensaje al chat
     */
    addMessageToChat(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${role}`;
        
        const roleLabel = role === 'user' ? 'Tú' : 'Asistente';
        const timestamp = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="message-header">
                <strong>${roleLabel}</strong>
                <span class="message-time">${timestamp}</span>
            </div>
            <div class="message-content">${this.escapeHtml(content)}</div>
        `;
        
        this.messagesContainer.appendChild(messageDiv);
        
        // Scroll al final
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;

        // Agregar al historial
        this.conversationHistory.push({ role, content });
        
        // Limitar historial a últimos 10 mensajes
        if (this.conversationHistory.length > 10) {
            this.conversationHistory = this.conversationHistory.slice(-10);
        }
    }

    /**
     * Actualizar preview del formulario
     */
    updatePreview() {
        if (!this.previewIframe) return;

        const formHTML = this.getFormPreviewHTML();
        const customCSS = this.cssEditor ? this.cssEditor.getValue() : '';

        const previewDocument = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                        background: #f0f0f1;
                    }
                    /* Estilos base del formulario */
                    .getso-form {
                        max-width: 600px;
                        margin: 0 auto;
                        background: #fff;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .getso-form-field {
                        margin-bottom: 20px;
                    }
                    .getso-field-label {
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                        color: #333;
                    }
                    .getso-required-mark {
                        color: #dc3232;
                    }
                    .getso-input,
                    .getso-textarea,
                    .getso-select {
                        width: 100%;
                        padding: 10px 12px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 14px;
                        transition: border-color 0.3s ease;
                    }
                    .getso-input:focus,
                    .getso-textarea:focus,
                    .getso-select:focus {
                        outline: none;
                        border-color: #0073aa;
                    }
                    .getso-submit-btn {
                        background: #0073aa;
                        color: #fff;
                        border: none;
                        padding: 12px 30px;
                        border-radius: 4px;
                        font-size: 16px;
                        cursor: pointer;
                        transition: background 0.3s ease;
                    }
                    .getso-submit-btn:hover {
                        background: #005a87;
                    }
                    /* CSS personalizado */
                    ${customCSS}
                </style>
            </head>
            <body>
                <form class="getso-form">
                    ${formHTML}
                    <button type="button" class="getso-submit-btn">Enviar</button>
                </form>
            </body>
            </html>
        `;

        // Actualizar iframe
        const iframeDoc = this.previewIframe.contentDocument || this.previewIframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(previewDocument);
        iframeDoc.close();
    }

    /**
     * Obtener HTML del formulario para preview
     */
    getFormPreviewHTML() {
        // Obtener campos del formulario
        const fields = this.getFormFields();
        
        if (!fields || fields.length === 0) {
            return '<p>No hay campos en el formulario. Agrega campos en la pestaña "Campos".</p>';
        }

        let html = '';
        
        fields.forEach(field => {
            html += `
                <div class="getso-form-field getso-field-${field.type}">
                    ${field.label ? `<label class="getso-field-label">${field.label}${field.required ? ' <span class="getso-required-mark">*</span>' : ''}</label>` : ''}
                    ${this.renderFieldPreview(field)}
                </div>
            `;
        });

        return html;
    }

    /**
     * Renderizar campo para preview
     */
    renderFieldPreview(field) {
        switch (field.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
            case 'date':
                return `<input type="${field.type}" class="getso-input" placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}>`;
            
            case 'textarea':
                return `<textarea class="getso-textarea" placeholder="${field.placeholder || ''}" rows="4" ${field.required ? 'required' : ''}></textarea>`;
            
            case 'select':
                const options = field.options || {};
                let selectHTML = `<select class="getso-select" ${field.required ? 'required' : ''}>`;
                if (field.placeholder) {
                    selectHTML += `<option value="">${field.placeholder}</option>`;
                }
                Object.entries(options).forEach(([value, label]) => {
                    selectHTML += `<option value="${value}">${label}</option>`;
                });
                selectHTML += '</select>';
                return selectHTML;
            
            default:
                return `<input type="text" class="getso-input" placeholder="${field.placeholder || ''}">`;
        }
    }

    /**
     * Obtener campos del formulario desde el DOM
     */
    getFormFields() {
        const fields = [];
        const fieldItems = document.querySelectorAll('.getso-field-item');
        
        fieldItems.forEach(item => {
            const hiddenInput = item.querySelector('input[type="hidden"]');
            if (hiddenInput) {
                try {
                    const fieldData = JSON.parse(hiddenInput.value);
                    fields.push(fieldData);
                } catch (e) {
                    console.error('Error parsing field data:', e);
                }
            }
        });
        
        return fields;
    }

    /**
     * Mostrar estado
     */
    showStatus(message, type = 'info') {
        this.statusDiv.innerHTML = `
            <div class="ai-status-message ai-status-${type}">
                ${this.getStatusIcon(type)}
                ${message}
            </div>
        `;

        // Auto-ocultar después de 5 segundos
        if (type !== 'loading') {
            setTimeout(() => {
                this.statusDiv.innerHTML = '';
            }, 5000);
        }
    }

    /**
     * Obtener icono de estado
     */
    getStatusIcon(type) {
        const icons = {
            success: '<span class="dashicons dashicons-yes-alt"></span>',
            error: '<span class="dashicons dashicons-dismiss"></span>',
            warning: '<span class="dashicons dashicons-warning"></span>',
            loading: '<span class="dashicons dashicons-update-alt spin"></span>',
            info: '<span class="dashicons dashicons-info"></span>'
        };
        return icons[type] || icons.info;
    }

    /**
     * Verificar rate limit
     */
    checkRateLimit() {
        const now = Date.now();
        
        // Reset si pasó 1 hora
        if (now > this.rateLimit.resetTime) {
            this.rateLimit.requests = 0;
            this.rateLimit.resetTime = now + (60 * 60 * 1000);
        }

        return this.rateLimit.requests < this.maxRequestsPerHour;
    }

    /**
     * Hacer solicitud AJAX
     */
    async makeAjaxRequest(data) {
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (typeof value === 'object') {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });

        const response = await fetch(ajaxurl, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Setear referencia al editor CSS
     */
    setCSSEditor(editor) {
        this.cssEditor = editor;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('ai-messages')) {
        window.getsoAIChat = new GetsoFormsAIChat();
    }
});
