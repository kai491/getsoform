<?php
/**
 * Vista: Lista de Formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

$forms = Getso_Forms_Manager::list_forms();
$max_forms = get_option('getso_forms_max_forms', 20);
$forms_count = count($forms);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">📋 Formularios</h1>
    
    <?php if ($forms_count < $max_forms) : ?>
    <a href="<?php echo admin_url('admin.php?page=getso-forms-new'); ?>" class="page-title-action">+ Nuevo Formulario</a>
    <?php else : ?>
    <span class="page-title-action" style="opacity: 0.5;" title="Límite alcanzado">+ Nuevo Formulario</span>
    <p class="description">Has alcanzado el límite de <?php echo $max_forms; ?> formularios.</p>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if (empty($forms)) : ?>
        <div class="notice notice-info">
            <p>No hay formularios creados. <a href="<?php echo admin_url('admin.php?page=getso-forms-new'); ?>">Crear el primero</a></p>
        </div>
    <?php else : ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Shortcode</th>
                <th>Campos</th>
                <th>Envíos</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forms as $form) : 
                global $wpdb;
                $submissions_table = Getso_Forms_Database::get_table_name('submissions');
                $submissions_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $submissions_table WHERE form_id = %d",
                    $form->id
                ));
                
                $fields_count = count($form->form_fields['fields'] ?? array());
            ?>
            <tr>
                <td><strong><?php echo $form->id; ?></strong></td>
                <td>
                    <strong><?php echo esc_html($form->form_name); ?></strong>
                    <?php if ($form->form_description) : ?>
                    <br><small class="description"><?php echo esc_html($form->form_description); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <code>[getso_form id="<?php echo $form->form_slug; ?>"]</code>
                    <button class="button button-small copy-shortcode" data-shortcode='[getso_form id="<?php echo $form->form_slug; ?>"]'>
                        📋 Copiar
                    </button>
                </td>
                <td><?php echo $fields_count; ?> campos</td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=getso-forms-submissions&form_id=' . $form->id); ?>">
                        <?php echo $submissions_count; ?> envíos
                    </a>
                </td>
                <td>
                    <?php if ($form->active) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Activo
                    <?php else : ?>
                        <span class="dashicons dashicons-dismiss" style="color: gray;"></span> Inactivo
                    <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y', strtotime($form->created_at)); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=getso-forms-edit&id=' . $form->id); ?>" class="button button-small">
                        ✏️ Editar
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=getso-forms-css&id=' . $form->id); ?>" class="button button-small">
                        🎨 CSS/IA
                    </a>
                    <button class="button button-small duplicate-form" data-id="<?php echo $form->id; ?>">
                        📋 Duplicar
                    </button>
                    <button class="button button-small delete-form" data-id="<?php echo $form->id; ?>">
                        🗑️ Eliminar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Copiar shortcode
    $('.copy-shortcode').on('click', function() {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode);
        $(this).text('✅ Copiado');
        setTimeout(() => $(this).text('📋 Copiar'), 2000);
    });
    
    // Duplicar
    $('.duplicate-form').on('click', function() {
        if (!confirm('¿Duplicar este formulario?')) return;
        
        const id = $(this).data('id');
        
        $.post(ajaxurl, {
            action: 'getso_forms_duplicate_form',
            nonce: getsoFormsAdmin.nonce,
            form_id: id
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
    
    // Eliminar
    $('.delete-form').on('click', function() {
        if (!confirm(getsoFormsAdmin.strings.confirmDelete)) return;
        
        const id = $(this).data('id');
        
        $.post(ajaxurl, {
            action: 'getso_forms_delete_form',
            nonce: getsoFormsAdmin.nonce,
            form_id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Eliminado');
                location.reload();
            } else {
                alert('❌ ' + response.data.message);
            }
        });
    });
});
</script>
