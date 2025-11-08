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
    async updatePreview() {
        if (!this.previewIframe) return;

        try {
            const response = await this.makeAjaxRequest({
                action: 'getso_forms_get_form_preview',
                nonce: getsoFormsEditor.nonce,
                form_id: getsoFormsEditor.formId
            });

            if (response.success) {
                const formHTML = response.data.html;
                const customCSS = this.cssEditor ? this.cssEditor.getValue() : '';
                const previewDocument = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <link rel="stylesheet" href="${getsoFormsAdmin.pluginUrl}public/css/form-base.css" />
                        <style>
                            body { margin: 0; padding: 20px; background: #f0f0f1; }
                            ${customCSS}
                        </style>
                    </head>
                    <body>
                        <div class="getso-form-container">
                            ${formHTML}
                        </div>
                    </body>
                    </html>
                `;
                const iframeDoc = this.previewIframe.contentDocument || this.previewIframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(previewDocument);
                iframeDoc.close();
            } else {
                throw new Error(response.data.message);
            }
        } catch (error) {
            console.error('Error getting form preview:', error);
            const iframeDoc = this.previewIframe.contentDocument || this.previewIframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(`<p>Error al cargar la vista previa: ${error.message}</p>`);
            iframeDoc.close();
        }
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
