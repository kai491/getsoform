<?php
/**
 * Vista: Editor CSS con IA
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$form = Getso_Forms_Manager::get_form($form_id);

if (!$form) {
    echo '<div class="wrap"><h1>Formulario no encontrado</h1></div>';
    return;
}

// Configuración IA
$ai_provider = get_option('getso_forms_ai_provider', 'claude');
$ai_api_key = get_option('getso_forms_ai_api_key', '');
$ai_configured = !empty($ai_api_key);
?>

<div class="wrap getso-css-editor-wrap">
    <h1>🎨 Editor CSS con IA - <?php echo esc_html($form->form_name); ?></h1>
    
    <input type="hidden" id="getso-form-id" value="<?php echo $form_id; ?>">
    
    <?php if (!$ai_configured) : ?>
    <div class="notice notice-warning">
        <p>⚠️ IA no configurada. <a href="<?php echo admin_url('admin.php?page=getso-forms-settings'); ?>">Configurar API Key</a></p>
    </div>
    <?php endif; ?>
    
    <div class="getso-editor-container">
        <div class="getso-editor-sidebar">
            <h2>🤖 Asistente IA</h2>
            
            <div class="ai-status">
                <strong>Proveedor:</strong> 
                <span class="ai-provider-badge"><?php echo strtoupper($ai_provider); ?></span>
                <?php if ($ai_configured) : ?>
                <span class="ai-status-indicator" style="color: green;">● Conectado</span>
                <?php else : ?>
                <span class="ai-status-indicator" style="color: red;">● Desconectado</span>
                <?php endif; ?>
            </div>
            
            <div class="ai-templates">
                <h3>Plantillas Rápidas:</h3>
                <button class="button template-btn" data-template="corporativo">🏢 Corporativo</button>
                <button class="button template-btn" data-template="minimalista">⚪ Minimalista</button>
                <button class="button template-btn" data-template="moderno">✨ Moderno</button>
                <button class="button template-btn" data-template="oscuro">🌙 Oscuro</button>
            </div>
            
            <div class="ai-chat-container">
                <h3>Chat con IA:</h3>
                <div id="ai-chat-history" class="ai-chat-history"></div>
                
                <div class="ai-chat-input">
                    <textarea id="ai-prompt" placeholder="Ejemplo: Cambia el color primario a verde oscuro y agrega sombras suaves..." rows="3"></textarea>
                    <button id="ai-send-btn" class="button button-primary" <?php echo !$ai_configured ? 'disabled' : ''; ?>>
                        🚀 Enviar a IA
                    </button>
                </div>
            </div>
            
            <div class="ai-history">
                <h3>Historial:</h3>
                <div id="ai-history-list"></div>
            </div>
        </div>
        
        <div class="getso-editor-main">
            <div class="editor-header">
                <h2>📝 Editor CSS Manual</h2>
                <div class="editor-actions">
                    <button id="save-css-btn" class="button button-primary">💾 Guardar CSS</button>
                    <button id="reset-css-btn" class="button">↶ Restaurar</button>
                </div>
            </div>
            
            <textarea id="css-editor" style="display:none;"><?php echo esc_textarea($form->form_css); ?></textarea>
        </div>
        
        <div class="getso-editor-preview">
            <div class="preview-header">
                <h2>👁️ Preview</h2>
                <div class="preview-controls">
                    <button class="preview-device active" data-device="desktop">🖥️ Desktop</button>
                    <button class="preview-device" data-device="tablet">💻 Tablet</button>
                    <button class="preview-device" data-device="mobile">📱 Mobile</button>
                    <button id="refresh-preview-btn" class="button button-small">🔄 Refrescar</button>
                </div>
            </div>
            
            <div class="preview-container" id="preview-container">
                <iframe id="preview-iframe"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
.getso-css-editor-wrap {
    margin-right: 20px;
}

.getso-editor-container {
    display: grid;
    grid-template-columns: 300px 1fr 400px;
    gap: 20px;
    margin-top: 20px;
}

.getso-editor-sidebar,
.getso-editor-main,
.getso-editor-preview {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ai-status {
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
    margin-bottom: 20px;
}

.ai-provider-badge {
    background: #0d2a57;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.ai-templates {
    margin-bottom: 20px;
}

.template-btn {
    display: block;
    width: 100%;
    margin-bottom: 8px;
    text-align: left;
}

.ai-chat-history {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    background: #f9f9f9;
}

.ai-chat-message {
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 4px;
}

.ai-chat-message.user {
    background: #e3f2fd;
    text-align: right;
}

.ai-chat-message.ai {
    background: #f1f8e9;
}

.ai-chat-message.error {
    background: #ffebee;
}

.ai-chat-input textarea {
    width: 100%;
    margin-bottom: 10px;
}

#ai-send-btn {
    width: 100%;
}

.CodeMirror {
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.preview-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    background: #f5f5f5;
}

#preview-iframe {
    width: 100%;
    height: 600px;
    border: none;
    background: white;
}

.preview-container.mobile #preview-iframe {
    width: 375px;
    margin: 0 auto;
    display: block;
}

.preview-container.tablet #preview-iframe {
    width: 768px;
    margin: 0 auto;
    display: block;
}

.preview-device {
    margin-right: 5px;
}

.preview-device.active {
    background: #0d2a57;
    color: white;
}

.editor-header,
.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.preview-controls {
    display: flex;
    gap: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Inicializar CodeMirror
    const cssEditor = wp.codeEditor.initialize('css-editor', {
        codemirror: {
            mode: 'css',
            lineNumbers: true,
            lineWrapping: true,
            theme: 'default'
        }
    });
    
    // Preview inicial
    updatePreview();
    
    // Guardar CSS
    $('#save-css-btn').on('click', function() {
        const css = cssEditor.codemirror.getValue();
        
        $.post(ajaxurl, {
            action: 'getso_forms_save_form',
            nonce: getsoFormsAdmin.nonce,
            form_id: <?php echo $form_id; ?>,
            form_data: {
                form_css: css
            }
        }, function(response) {
            if (response.success) {
                alert('✅ CSS guardado');
            } else {
                alert('❌ Error al guardar');
            }
        });
    });
    
    // Enviar a IA
    $('#ai-send-btn').on('click', function() {
        const prompt = $('#ai-prompt').val().trim();
        
        if (!prompt) {
            alert('Por favor escribe una instrucción');
            return;
        }
        
        addMessageToChat('user', prompt);
        $(this).prop('disabled', true).text('⏳ Generando...');
        
        $.post(ajaxurl, {
            action: 'getso_forms_generate_css_ai',
            nonce: getsoFormsAdmin.nonce,
            form_id: <?php echo $form_id; ?>,
            prompt: prompt
        }, function(response) {
            $('#ai-send-btn').prop('disabled', false).text('🚀 Enviar a IA');
            
            if (response.success) {
                cssEditor.codemirror.setValue(response.data.css);
                updatePreview();
                addMessageToChat('ai', '✅ CSS actualizado');
                $('#ai-prompt').val('');
            } else {
                addMessageToChat('error', '❌ ' + response.data.message);
            }
        });
    });
    
    // Plantillas
    $('.template-btn').on('click', function() {
        const template = $(this).data('template');
        
        $(this).prop('disabled', true).text('⏳ Aplicando...');
        
        $.post(ajaxurl, {
            action: 'getso_forms_apply_css_template',
            nonce: getsoFormsAdmin.nonce,
            form_id: <?php echo $form_id; ?>,
            template: template
        }, function(response) {
            $('.template-btn').prop('disabled', false);
            
            if (response.success) {
                cssEditor.codemirror.setValue(response.data.css);
                updatePreview();
                addMessageToChat('ai', '✅ Plantilla aplicada: ' + template);
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
    
    // Preview devices
    $('.preview-device').on('click', function() {
        $('.preview-device').removeClass('active');
        $(this).addClass('active');
        
        const device = $(this).data('device');
        $('#preview-container').removeClass('mobile tablet desktop').addClass(device);
    });
    
    // Refrescar preview
    $('#refresh-preview-btn').on('click', updatePreview);
    
    // Actualizar preview
    function updatePreview() {
        const css = cssEditor.codemirror.getValue();
        const iframe = document.getElementById('preview-iframe');
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        
        // HTML del formulario
        const formHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { padding: 20px; font-family: Arial, sans-serif; }
                    ${css}
                </style>
            </head>
            <body>
                <?php echo do_shortcode('[getso_form id="' . $form->form_slug . '"]'); ?>
            </body>
            </html>
        `;
        
        iframeDoc.open();
        iframeDoc.write(formHtml);
        iframeDoc.close();
    }
    
    // Agregar mensaje al chat
    function addMessageToChat(type, message) {
        const chatHistory = $('#ai-chat-history');
        const messageDiv = $('<div>').addClass('ai-chat-message').addClass(type);
        
        const icon = type === 'user' ? '👤' : type === 'ai' ? '🤖' : '⚠️';
        messageDiv.html(`<strong>${icon}</strong> ${message}`);
        
        chatHistory.append(messageDiv);
        chatHistory.scrollTop(chatHistory[0].scrollHeight);
    }
    
    // Enter para enviar (Ctrl+Enter)
    $('#ai-prompt').on('keypress', function(e) {
        if (e.ctrlKey && e.which === 13) {
            $('#ai-send-btn').click();
        }
    });
});
</script>
