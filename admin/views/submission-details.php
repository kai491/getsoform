<?php
/**
 * Submission Details Modal
 *
 * Modal con detalles completos de un envío
 *
 * @package Getso_Forms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Esta vista se carga via AJAX, no directamente
// El siguiente código es el template del modal

?>

<script type="text/html" id="submission-details-modal-template">
<div id="submission-details-modal" class="getso-modal">
    <div class="getso-modal-overlay"></div>
    <div class="getso-modal-content getso-modal-large">
        <div class="getso-modal-header">
            <h2>
                <span class="dashicons dashicons-visibility"></span>
                Detalles del Envío #{{submission_id}}
            </h2>
            <button type="button" class="getso-modal-close">&times;</button>
        </div>
        <div class="getso-modal-body">
            
            <!-- Información General -->
            <div class="details-section">
                <h3>
                    <span class="dashicons dashicons-info"></span>
                    Información General
                </h3>
                <table class="details-table">
                    <tr>
                        <th>ID del Envío:</th>
                        <td><strong>#{{submission_id}}</strong></td>
                    </tr>
                    <tr>
                        <th>Formulario:</th>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=getso-forms-editor&form_id={{form_id}}'); ?>">
                                {{form_name}}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Fecha y Hora:</th>
                        <td>{{submitted_at}}</td>
                    </tr>
                    <tr>
                        <th>Dirección IP:</th>
                        <td><code>{{ip_address}}</code></td>
                    </tr>
                    <tr>
                        <th>User Agent:</th>
                        <td><small>{{user_agent}}</small></td>
                    </tr>
                </table>
            </div>

            <!-- Datos del Formulario -->
            <div class="details-section">
                <h3>
                    <span class="dashicons dashicons-editor-table"></span>
                    Datos Enviados
                </h3>
                <div class="form-data-container">
                    {{form_data_html}}
                </div>
            </div>

            <!-- Estado Webhooks -->
            <div class="details-section">
                <h3>
                    <span class="dashicons dashicons-rest-api"></span>
                    Estado de Webhooks
                </h3>
                
                <!-- Webhook Primario -->
                <div class="webhook-status-box">
                    <h4>Webhook Primario</h4>
                    <table class="details-table">
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="status-badge status-{{webhook_primary_status_class}}">
                                    {{webhook_primary_status}}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Respuesta:</th>
                            <td>
                                <pre class="webhook-response">{{webhook_primary_response}}</pre>
                            </td>
                        </tr>
                        <tr>
                            <th>Código HTTP:</th>
                            <td><code>{{webhook_primary_http_code}}</code></td>
                        </tr>
                    </table>
                </div>

                <!-- Webhook Secundario -->
                <div class="webhook-status-box">
                    <h4>Webhook Secundario (Backup)</h4>
                    <table class="details-table">
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="status-badge status-{{webhook_secondary_status_class}}">
                                    {{webhook_secondary_status}}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Respuesta:</th>
                            <td>
                                <pre class="webhook-response">{{webhook_secondary_response}}</pre>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Estado Chatwoot -->
            <div class="details-section">
                <h3>
                    <span class="dashicons dashicons-format-chat"></span>
                    Integración Chatwoot
                </h3>
                <table class="details-table">
                    <tr>
                        <th>Estado:</th>
                        <td>
                            {{#chatwoot_enabled}}
                                <span class="status-badge status-success">
                                    <span class="dashicons dashicons-yes"></span>
                                    Enviado
                                </span>
                            {{/chatwoot_enabled}}
                            {{^chatwoot_enabled}}
                                <span class="status-badge status-neutral">No configurado</span>
                            {{/chatwoot_enabled}}
                        </td>
                    </tr>
                    {{#chatwoot_enabled}}
                    <tr>
                        <th>Contact ID:</th>
                        <td><code>{{chatwoot_contact_id}}</code></td>
                    </tr>
                    <tr>
                        <th>Conversation ID:</th>
                        <td><code>{{chatwoot_conversation_id}}</code></td>
                    </tr>
                    {{/chatwoot_enabled}}
                </table>
            </div>

        </div>
        <div class="getso-modal-footer">
            <button type="button" class="button button-large button-link-delete" id="delete-submission-btn" data-submission-id="{{submission_id}}">
                <span class="dashicons dashicons-trash"></span>
                Eliminar Envío
            </button>
            <button type="button" class="button button-large" id="close-details-modal">
                Cerrar
            </button>
        </div>
    </div>
</div>
</script>

<script>
/**
 * Función global para abrir modal de detalles
 */
function openSubmissionDetailsModal(submissionId) {
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'getso_forms_get_submission_details',
            nonce: '<?php echo wp_create_nonce('getso_forms_submission_details'); ?>',
            submission_id: submissionId
        },
        success: function(response) {
            if (response.success) {
                renderSubmissionDetailsModal(response.data);
            } else {
                alert('Error al cargar detalles: ' + response.data.message);
            }
        },
        error: function() {
            alert('Error de conexión al cargar detalles del envío.');
        }
    });
}

/**
 * Renderizar modal con datos
 */
function renderSubmissionDetailsModal(data) {
    // Obtener template
    const template = document.getElementById('submission-details-modal-template').innerHTML;
    
    // Parsear form_data JSON
    let formDataHTML = '<table class="form-data-table">';
    try {
        const formData = JSON.parse(data.form_data);
        for (const [key, value] of Object.entries(formData)) {
            formDataHTML += `
                <tr>
                    <th>${escapeHtml(key)}:</th>
                    <td>${escapeHtml(value)}</td>
                </tr>
            `;
        }
    } catch (e) {
        formDataHTML += '<tr><td colspan="2">Error al parsear datos</td></tr>';
    }
    formDataHTML += '</table>';

    // Preparar datos para el template
    const templateData = {
        submission_id: data.id,
        form_id: data.form_id,
        form_name: data.form_name || 'Desconocido',
        submitted_at: formatDate(data.submitted_at),
        ip_address: data.ip_address || 'N/A',
        user_agent: data.user_agent || 'N/A',
        form_data_html: formDataHTML,
        webhook_primary_status: data.webhook_primary_status || 'N/A',
        webhook_primary_status_class: getStatusClass(data.webhook_primary_status),
        webhook_primary_response: data.webhook_primary_response || 'Sin respuesta',
        webhook_primary_http_code: data.webhook_primary_http_code || 'N/A',
        webhook_secondary_status: data.webhook_secondary_status || 'N/A',
        webhook_secondary_status_class: getStatusClass(data.webhook_secondary_status),
        webhook_secondary_response: data.webhook_secondary_response || 'Sin respuesta',
        chatwoot_enabled: !!data.chatwoot_contact_id,
        chatwoot_contact_id: data.chatwoot_contact_id || 'N/A',
        chatwoot_conversation_id: data.chatwoot_conversation_id || 'N/A'
    };

    // Renderizar template (simple replace)
    let html = template;
    for (const [key, value] of Object.entries(templateData)) {
        const regex = new RegExp(`{{${key}}}`, 'g');
        html = html.replace(regex, value);
    }

    // Manejar condicionales {{#chatwoot_enabled}}
    if (templateData.chatwoot_enabled) {
        html = html.replace(/{{#chatwoot_enabled}}([\s\S]*?){{\/chatwoot_enabled}}/g, '$1');
        html = html.replace(/{{^chatwoot_enabled}}[\s\S]*?{{\/chatwoot_enabled}}/g, '');
    } else {
        html = html.replace(/{{#chatwoot_enabled}}[\s\S]*?{{\/chatwoot_enabled}}/g, '');
        html = html.replace(/{{^chatwoot_enabled}}([\s\S]*?){{\/chatwoot_enabled}}/g, '$1');
    }

    // Insertar modal en el DOM
    const existingModal = document.getElementById('submission-details-modal');
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', html);

    // Adjuntar event listeners
    attachModalEventListeners();

    // Mostrar modal
    setTimeout(() => {
        const modal = document.getElementById('submission-details-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }, 10);
}

/**
 * Adjuntar event listeners al modal
 */
function attachModalEventListeners() {
    const modal = document.getElementById('submission-details-modal');
    if (!modal) return;

    // Cerrar modal
    const closeBtn = modal.querySelector('.getso-modal-close');
    const closeFooterBtn = modal.querySelector('#close-details-modal');
    const overlay = modal.querySelector('.getso-modal-overlay');

    [closeBtn, closeFooterBtn, overlay].forEach(element => {
        if (element) {
            element.addEventListener('click', () => {
                modal.style.display = 'none';
                setTimeout(() => modal.remove(), 300);
            });
        }
    });

    // Eliminar envío
    const deleteBtn = modal.querySelector('#delete-submission-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            const submissionId = this.dataset.submissionId;
            
            if (!confirm('¿Estás seguro de eliminar este envío? Esta acción no se puede deshacer.')) {
                return;
            }

            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'getso_forms_delete_submission',
                    nonce: '<?php echo wp_create_nonce('getso_forms_delete_submission'); ?>',
                    submission_id: submissionId
                },
                success: function(response) {
                    if (response.success) {
                        modal.style.display = 'none';
                        location.reload();
                    } else {
                        alert('Error al eliminar: ' + response.data.message);
                    }
                }
            });
        });
    }
}

/**
 * Helpers
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('es-CL');
}

function getStatusClass(status) {
    if (status === 'success') return 'success';
    if (status === 'error') return 'error';
    if (status === 'pending') return 'warning';
    return 'neutral';
}
</script>

<style>
.getso-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    align-items: center;
    justify-content: center;
}

.getso-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
}

.getso-modal-content {
    position: relative;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: modalSlideIn 0.3s ease;
}

.getso-modal-large {
    max-width: 1000px;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.getso-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #ddd;
}

.getso-modal-header h2 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.getso-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    line-height: 1;
    padding: 0;
    width: 32px;
    height: 32px;
}

.getso-modal-close:hover {
    color: #000;
}

.getso-modal-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
}

.details-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.details-section:last-child {
    border-bottom: none;
}

.details-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 15px;
    font-size: 16px;
    color: #0073aa;
}

.details-table {
    width: 100%;
    border-collapse: collapse;
}

.details-table th {
    text-align: left;
    padding: 10px;
    background: #f5f5f5;
    font-weight: 600;
    width: 200px;
}

.details-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.form-data-container {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
}

.form-data-table {
    width: 100%;
}

.form-data-table th {
    background: none;
    text-align: left;
    padding: 8px;
    font-weight: 600;
    width: 30%;
}

.form-data-table td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

.webhook-status-box {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}

.webhook-status-box h4 {
    margin: 0 0 10px;
    font-size: 14px;
}

.webhook-response {
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 3px;
    font-size: 12px;
    max-height: 150px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

.getso-modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>
