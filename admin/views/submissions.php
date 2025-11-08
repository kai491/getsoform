<?php
/**
 * Submissions View
 *
 * Lista de todos los envíos de formularios
 *
 * @package Getso_Forms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

global $wpdb;
$forms_table = $wpdb->prefix . 'getso_forms';
$submissions_table = $wpdb->prefix . 'getso_form_submissions';

// Obtener filtros
$filter_form_id = isset($_GET['filter_form']) ? intval($_GET['filter_form']) : 0;
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
$filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';

// Paginación
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Construir query con filtros
$where_clauses = ['1=1'];
$query_params = [];

if ($filter_form_id > 0) {
    $where_clauses[] = 's.form_id = %d';
    $query_params[] = $filter_form_id;
}

if (!empty($filter_status)) {
    $where_clauses[] = 's.webhook_primary_status = %s';
    $query_params[] = $filter_status;
}

if (!empty($filter_date_from)) {
    $where_clauses[] = 'DATE(s.submitted_at) >= %s';
    $query_params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_clauses[] = 'DATE(s.submitted_at) <= %s';
    $query_params[] = $filter_date_to;
}

$where_sql = implode(' AND ', $where_clauses);

// Contar total de registros
$total_query = "SELECT COUNT(*) FROM $submissions_table s WHERE $where_sql";
if (!empty($query_params)) {
    $total_items = $wpdb->get_var($wpdb->prepare($total_query, $query_params));
} else {
    $total_items = $wpdb->get_var($total_query);
}

$total_pages = ceil($total_items / $per_page);

// Obtener envíos
$submissions_query = "
    SELECT 
        s.*,
        f.name as form_name
    FROM $submissions_table s
    LEFT JOIN $forms_table f ON s.form_id = f.id
    WHERE $where_sql
    ORDER BY s.submitted_at DESC
    LIMIT %d OFFSET %d
";

$query_params[] = $per_page;
$query_params[] = $offset;

$submissions = $wpdb->get_results($wpdb->prepare($submissions_query, $query_params), ARRAY_A);

// Obtener todos los formularios para el filtro
$all_forms = $wpdb->get_results("SELECT id, name FROM $forms_table ORDER BY name ASC", ARRAY_A);

?>

<div class="wrap getso-forms-submissions">
    <h1>
        <span class="dashicons dashicons-email"></span>
        Envíos de Formularios
    </h1>

    <!-- Filtros -->
    <div class="getso-filters-section">
        <form method="get" class="getso-filters-form">
            <input type="hidden" name="page" value="getso-forms-submissions">
            
            <div class="filter-group">
                <label for="filter_form">Formulario:</label>
                <select name="filter_form" id="filter_form">
                    <option value="0">Todos los formularios</option>
                    <?php foreach ($all_forms as $form): ?>
                        <option value="<?php echo $form['id']; ?>" <?php selected($filter_form_id, $form['id']); ?>>
                            <?php echo esc_html($form['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_status">Estado Webhook:</label>
                <select name="filter_status" id="filter_status">
                    <option value="">Todos los estados</option>
                    <option value="success" <?php selected($filter_status, 'success'); ?>>Success</option>
                    <option value="error" <?php selected($filter_status, 'error'); ?>>Error</option>
                    <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_date_from">Desde:</label>
                <input type="date" 
                       name="filter_date_from" 
                       id="filter_date_from" 
                       value="<?php echo esc_attr($filter_date_from); ?>">
            </div>

            <div class="filter-group">
                <label for="filter_date_to">Hasta:</label>
                <input type="date" 
                       name="filter_date_to" 
                       id="filter_date_to" 
                       value="<?php echo esc_attr($filter_date_to); ?>">
            </div>

            <div class="filter-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-filter"></span>
                    Filtrar
                </button>
                <a href="<?php echo admin_url('admin.php?page=getso-forms-submissions'); ?>" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    Limpiar
                </a>
            </div>
        </form>

        <!-- Exportar CSV -->
        <div class="export-section">
            <button type="button" class="button" id="export-csv-btn">
                <span class="dashicons dashicons-download"></span>
                Exportar CSV
            </button>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="getso-quick-stats">
        <div class="stat-box">
            <strong><?php echo number_format($total_items); ?></strong>
            <span>Total Envíos</span>
        </div>
        <?php
        $success_count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE webhook_primary_status = 'success'");
        $error_count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE webhook_primary_status = 'error'");
        ?>
        <div class="stat-box stat-success">
            <strong><?php echo number_format($success_count); ?></strong>
            <span>Exitosos</span>
        </div>
        <div class="stat-box stat-error">
            <strong><?php echo number_format($error_count); ?></strong>
            <span>Con Errores</span>
        </div>
    </div>

    <!-- Tabla de envíos -->
    <?php if (!empty($submissions)): ?>
        <table class="wp-list-table widefat fixed striped getso-submissions-table">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        <input type="checkbox" id="select-all-submissions">
                    </th>
                    <th>ID</th>
                    <th>Formulario</th>
                    <th>Fecha y Hora</th>
                    <th>Webhook Primario</th>
                    <th>Webhook Secundario</th>
                    <th>Chatwoot</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="submission-checkbox" value="<?php echo $submission['id']; ?>">
                        </td>
                        <td>
                            <strong>#<?php echo $submission['id']; ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=getso-forms-editor&form_id=' . $submission['form_id']); ?>">
                                <?php echo esc_html($submission['form_name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($submission['submitted_at'])); ?><br>
                            <small><?php echo date('H:i:s', strtotime($submission['submitted_at'])); ?></small>
                        </td>
                        <td>
                            <?php
                            $status = $submission['webhook_primary_status'] ?: 'N/A';
                            $status_class = 'status-neutral';
                            if ($status === 'success') $status_class = 'status-success';
                            elseif ($status === 'error') $status_class = 'status-error';
                            elseif ($status === 'pending') $status_class = 'status-warning';
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                            <?php if ($submission['webhook_primary_response']): ?>
                                <br><small class="response-code">
                                    Código: <?php echo esc_html(substr($submission['webhook_primary_response'], 0, 50)); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = $submission['webhook_secondary_status'] ?: 'N/A';
                            $status_class = 'status-neutral';
                            if ($status === 'success') $status_class = 'status-success';
                            elseif ($status === 'error') $status_class = 'status-error';
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($submission['chatwoot_contact_id'])): ?>
                                <span class="status-badge status-success">
                                    <span class="dashicons dashicons-yes"></span>
                                    Enviado
                                </span>
                                <br><small>
                                    Contact: <?php echo esc_html($submission['chatwoot_contact_id']); ?>
                                </small>
                            <?php else: ?>
                                <span class="status-badge status-neutral">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" 
                                    class="button button-small view-submission-details" 
                                    data-submission-id="<?php echo $submission['id']; ?>">
                                <span class="dashicons dashicons-visibility"></span>
                                Ver Detalles
                            </button>
                            <button type="button" 
                                    class="button button-small button-link-delete delete-submission" 
                                    data-submission-id="<?php echo $submission['id']; ?>">
                                <span class="dashicons dashicons-trash"></span>
                                Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%s elementos'), number_format_i18n($total_items)); ?>
                    </span>
                    <?php
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain'
                    ]);
                    
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Acciones masivas -->
        <div class="getso-bulk-actions">
            <select id="bulk-action-selector">
                <option value="">Acciones en lote</option>
                <option value="delete">Eliminar seleccionados</option>
                <option value="export">Exportar seleccionados</option>
            </select>
            <button type="button" class="button" id="apply-bulk-action">Aplicar</button>
        </div>

    <?php else: ?>
        <div class="no-submissions-message">
            <span class="dashicons dashicons-email"></span>
            <h3>No hay envíos</h3>
            <p>No se encontraron envíos con los filtros seleccionados.</p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkboxes
    $('#select-all-submissions').on('change', function() {
        $('.submission-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Ver detalles de envío
    $('.view-submission-details').on('click', function() {
        const submissionId = $(this).data('submission-id');
        // Abrir modal con detalles (implementado en submission-details.php)
        openSubmissionDetailsModal(submissionId);
    });

    // Eliminar envío
    $('.delete-submission').on('click', function() {
        if (!confirm('¿Estás seguro de eliminar este envío? Esta acción no se puede deshacer.')) {
            return;
        }

        const submissionId = $(this).data('submission-id');
        const $row = $(this).closest('tr');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'getso_forms_delete_submission',
                nonce: '<?php echo wp_create_nonce('getso_forms_delete_submission'); ?>',
                submission_id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error al eliminar: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error de conexión al eliminar el envío.');
            }
        });
    });

    // Exportar CSV
    $('#export-csv-btn').on('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('action', 'getso_forms_export_csv');
        params.set('nonce', '<?php echo wp_create_nonce('getso_forms_export_csv'); ?>');
        
        window.location.href = ajaxurl + '?' + params.toString();
    });

    // Acciones en lote
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedIds = $('.submission-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!action || selectedIds.length === 0) {
            alert('Selecciona una acción y al menos un envío.');
            return;
        }

        if (action === 'delete') {
            if (!confirm(`¿Eliminar ${selectedIds.length} envíos seleccionados?`)) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'getso_forms_bulk_delete_submissions',
                    nonce: '<?php echo wp_create_nonce('getso_forms_bulk_delete'); ?>',
                    submission_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });
});
</script>

<style>
.getso-forms-submissions {
    padding: 20px;
}

.getso-filters-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.getso-filters-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    font-size: 12px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.export-section {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.getso-quick-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    flex: 1;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.stat-box strong {
    display: block;
    font-size: 24px;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-box.stat-success strong {
    color: #46b450;
}

.stat-box.stat-error strong {
    color: #dc3232;
}

.stat-box span {
    font-size: 12px;
    color: #666;
}

.getso-submissions-table {
    background: #fff;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.status-success {
    background: #46b450;
    color: #fff;
}

.status-badge.status-error {
    background: #dc3232;
    color: #fff;
}

.status-badge.status-warning {
    background: #ffb900;
    color: #fff;
}

.status-badge.status-neutral {
    background: #999;
    color: #fff;
}

.response-code {
    color: #666;
    font-size: 11px;
}

.getso-bulk-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.no-submissions-message {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.no-submissions-message .dashicons {
    font-size: 64px;
    color: #ddd;
}

.no-submissions-message h3 {
    margin: 15px 0 5px;
    color: #666;
}
</style>
