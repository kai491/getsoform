<?php
/**
 * Dashboard View
 *
 * Vista principal del plugin con estadísticas y gráficos
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

// Obtener estadísticas
$total_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
$active_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE active = 1");
$total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");

// Submissions últimos 30 días
$submissions_30d = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $submissions_table WHERE submitted_at >= %s",
    date('Y-m-d H:i:s', strtotime('-30 days'))
));

// Tasa de éxito webhooks
$webhook_success = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE webhook_primary_status = 'success'");
$webhook_success_rate = $total_submissions > 0 ? round(($webhook_success / $total_submissions) * 100, 2) : 0;

// Top 3 formularios más usados
$top_forms = $wpdb->get_results("
    SELECT 
        f.id,
        f.name,
        COUNT(s.id) as submission_count
    FROM $forms_table f
    LEFT JOIN $submissions_table s ON f.id = s.form_id
    GROUP BY f.id
    ORDER BY submission_count DESC
    LIMIT 3
", ARRAY_A);

// Datos para gráfico: Envíos por día (últimos 7 días)
$submissions_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $submissions_table WHERE DATE(submitted_at) = %s",
        $date
    ));
    $submissions_by_day[] = [
        'date' => date('d/m', strtotime($date)),
        'count' => intval($count)
    ];
}

// Datos para gráfico: Envíos por formulario
$submissions_by_form = $wpdb->get_results("
    SELECT 
        f.name as form_name,
        COUNT(s.id) as count
    FROM $forms_table f
    LEFT JOIN $submissions_table s ON f.id = s.form_id
    GROUP BY f.id
    ORDER BY count DESC
    LIMIT 5
", ARRAY_A);

?>

<div class="wrap getso-forms-dashboard">
    <h1>
        <span class="dashicons dashicons-chart-bar"></span>
        Dashboard - Getso Forms
    </h1>

    <!-- Stats Cards -->
    <div class="getso-stats-grid">
        <div class="getso-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-editor-table"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_forms); ?></h3>
                <p>Total Formularios</p>
                <small><?php echo $active_forms; ?> activos</small>
            </div>
        </div>

        <div class="getso-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-email"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_submissions); ?></h3>
                <p>Total Envíos</p>
                <small><?php echo $submissions_30d; ?> en 30 días</small>
            </div>
        </div>

        <div class="getso-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-rest-api"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo $webhook_success_rate; ?>%</h3>
                <p>Tasa Éxito Webhooks</p>
                <small><?php echo number_format($webhook_success); ?> exitosos</small>
            </div>
        </div>

        <div class="getso-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo date('d/m/Y'); ?></h3>
                <p>Fecha Actual</p>
                <small><?php echo date('H:i'); ?> hrs</small>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="getso-charts-section">
        <div class="getso-chart-container">
            <div class="chart-header">
                <h2>Envíos por Día (Últimos 7 días)</h2>
            </div>
            <canvas id="submissions-by-day-chart"></canvas>
        </div>

        <div class="getso-chart-container">
            <div class="chart-header">
                <h2>Envíos por Formulario</h2>
            </div>
            <canvas id="submissions-by-form-chart"></canvas>
        </div>
    </div>

    <!-- Top Forms -->
    <div class="getso-top-forms">
        <h2>
            <span class="dashicons dashicons-star-filled"></span>
            Top 3 Formularios Más Usados
        </h2>
        
        <?php if (!empty($top_forms)): ?>
            <div class="top-forms-list">
                <?php foreach ($top_forms as $index => $form): ?>
                    <div class="top-form-item">
                        <div class="top-form-rank">
                            <span class="rank-number"><?php echo $index + 1; ?></span>
                        </div>
                        <div class="top-form-info">
                            <h3><?php echo esc_html($form['name']); ?></h3>
                            <p><?php echo number_format($form['submission_count']); ?> envíos</p>
                        </div>
                        <div class="top-form-actions">
                            <a href="<?php echo admin_url('admin.php?page=getso-forms-editor&form_id=' . $form['id']); ?>" 
                               class="button button-small">
                                <span class="dashicons dashicons-edit"></span>
                                Editar
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=getso-forms-submissions&form_id=' . $form['id']); ?>" 
                               class="button button-small">
                                <span class="dashicons dashicons-list-view"></span>
                                Ver Envíos
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data-message">
                <span class="dashicons dashicons-info"></span>
                <p>No hay datos suficientes para mostrar. Crea tu primer formulario para comenzar.</p>
                <a href="<?php echo admin_url('admin.php?page=getso-forms-editor&form_id=0'); ?>" 
                   class="button button-primary">
                    Crear Formulario
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="getso-recent-activity">
        <h2>
            <span class="dashicons dashicons-backup"></span>
            Actividad Reciente
        </h2>
        
        <?php
        $recent_submissions = $wpdb->get_results("
            SELECT 
                s.*,
                f.name as form_name
            FROM $submissions_table s
            LEFT JOIN $forms_table f ON s.form_id = f.id
            ORDER BY s.submitted_at DESC
            LIMIT 10
        ", ARRAY_A);
        ?>

        <?php if (!empty($recent_submissions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Formulario</th>
                        <th>Fecha</th>
                        <th>Estado Webhook</th>
                        <th>Estado Chatwoot</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_submissions as $submission): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($submission['form_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($submission['submitted_at'])); ?>
                            </td>
                            <td>
                                <?php
                                $webhook_status = $submission['webhook_primary_status'];
                                $status_class = $webhook_status === 'success' ? 'success' : 'error';
                                ?>
                                <span class="status-badge status-<?php echo $status_class; ?>">
                                    <?php echo esc_html($webhook_status ?: 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($submission['chatwoot_contact_id'])): ?>
                                    <span class="status-badge status-success">
                                        <span class="dashicons dashicons-yes"></span>
                                        Enviado
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-neutral">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="button button-small view-submission-btn" 
                                        data-submission-id="<?php echo $submission['id']; ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data-message">
                <span class="dashicons dashicons-info"></span>
                <p>No hay envíos recientes.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="getso-quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="quick-actions-grid">
            <a href="<?php echo admin_url('admin.php?page=getso-forms-editor&form_id=0'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <span>Crear Formulario</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=getso-forms'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-list-view"></span>
                <span>Ver Formularios</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=getso-forms-submissions'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-email"></span>
                <span>Ver Envíos</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=getso-forms-settings'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-admin-settings"></span>
                <span>Configuración</span>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Datos para gráficos
    const submissionsByDay = <?php echo json_encode($submissions_by_day); ?>;
    const submissionsByForm = <?php echo json_encode($submissions_by_form); ?>;

    // Gráfico: Envíos por día
    const ctxDay = document.getElementById('submissions-by-day-chart');
    if (ctxDay) {
        new Chart(ctxDay, {
            type: 'line',
            data: {
                labels: submissionsByDay.map(item => item.date),
                datasets: [{
                    label: 'Envíos',
                    data: submissionsByDay.map(item => item.count),
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Gráfico: Envíos por formulario
    const ctxForm = document.getElementById('submissions-by-form-chart');
    if (ctxForm) {
        new Chart(ctxForm, {
            type: 'bar',
            data: {
                labels: submissionsByForm.map(item => item.form_name),
                datasets: [{
                    label: 'Envíos',
                    data: submissionsByForm.map(item => item.count),
                    backgroundColor: [
                        'rgba(0, 115, 170, 0.8)',
                        'rgba(0, 160, 210, 0.8)',
                        'rgba(0, 180, 220, 0.8)',
                        'rgba(100, 200, 255, 0.8)',
                        'rgba(150, 220, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Ver detalles de envío
    $('.view-submission-btn').on('click', function() {
        const submissionId = $(this).data('submission-id');
        // Implementar modal con detalles (se hace en submission-details.php)
        window.location.href = '<?php echo admin_url('admin.php?page=getso-forms-submissions&submission_id='); ?>' + submissionId;
    });
});
</script>

<style>
.getso-forms-dashboard {
    padding: 20px 20px 40px;
}

.getso-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.getso-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0073aa 0%, #00a0d2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon .dashicons {
    font-size: 30px;
    color: #fff;
}

.stat-content h3 {
    margin: 0;
    font-size: 28px;
    color: #0073aa;
}

.stat-content p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #666;
}

.stat-content small {
    font-size: 12px;
    color: #999;
}

.getso-charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.getso-chart-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.chart-header {
    margin-bottom: 20px;
}

.chart-header h2 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.getso-chart-container canvas {
    max-height: 300px;
}

.getso-top-forms,
.getso-recent-activity,
.getso-quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.top-forms-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.top-form-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: #f9f9f9;
}

.top-form-rank {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0073aa 0%, #00a0d2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.rank-number {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}

.top-form-info {
    flex: 1;
}

.top-form-info h3 {
    margin: 0 0 5px;
    font-size: 16px;
}

.top-form-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.top-form-actions {
    display: flex;
    gap: 10px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-badge.status-success {
    background: #46b450;
    color: #fff;
}

.status-badge.status-error {
    background: #dc3232;
    color: #fff;
}

.status-badge.status-neutral {
    background: #999;
    color: #fff;
}

.no-data-message {
    text-align: center;
    padding: 40px;
    color: #666;
}

.no-data-message .dashicons {
    font-size: 48px;
    opacity: 0.3;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    border: 2px solid #0073aa;
    border-radius: 8px;
    text-decoration: none;
    color: #0073aa;
    transition: all 0.3s ease;
}

.quick-action-card:hover {
    background: #0073aa;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quick-action-card .dashicons {
    font-size: 32px;
}
</style>
