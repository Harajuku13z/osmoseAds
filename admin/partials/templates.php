<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

$templates = get_posts(array(
    'post_type' => 'ad_template',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC',
));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
    <div>
        <a href="#" id="create-template-btn" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>
            <?php _e('Créer depuis un Service', 'osmose-ads'); ?>
        </a>
    </div>
</div>
    
    <div id="create-template-modal" style="display: none;">
        <h2><?php _e('Créer un Template depuis un Service', 'osmose-ads'); ?></h2>
        <form id="create-template-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Nom du Service', 'osmose-ads'); ?></th>
                    <td>
                        <input type="text" name="service_name" class="regular-text" required>
                        <p class="description"><?php _e('Ex: Dépannage et réparation de fuites d\'eau', 'osmose-ads'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Prompt IA (Optionnel)', 'osmose-ads'); ?></th>
                    <td>
                        <textarea name="ai_prompt" rows="5" class="large-text"></textarea>
                        <p class="description"><?php _e('Si vide, un prompt par défaut sera utilisé', 'osmose-ads'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Générer le Template', 'osmose-ads'); ?></button>
                <button type="button" class="button cancel-modal"><?php _e('Annuler', 'osmose-ads'); ?></button>
            </p>
        </form>
        <div id="template-result"></div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Nom', 'osmose-ads'); ?></th>
                <th><?php _e('Service', 'osmose-ads'); ?></th>
                <th><?php _e('Utilisations', 'osmose-ads'); ?></th>
                <th><?php _e('Statut', 'osmose-ads'); ?></th>
                <th><?php _e('Actions', 'osmose-ads'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($templates)): ?>
                <?php foreach ($templates as $template): 
                    $service_name = get_post_meta($template->ID, 'service_name', true);
                    $usage_count = get_post_meta($template->ID, 'usage_count', true);
                    $is_active = get_post_meta($template->ID, 'is_active', true);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($template->post_title); ?></strong></td>
                        <td><?php echo esc_html($service_name); ?></td>
                        <td><?php echo number_format_i18n($usage_count ?? 0); ?></td>
                        <td>
                            <?php if ($is_active !== '0'): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Actif', 'osmose-ads'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-dismiss" style="color: red;"></span> <?php _e('Inactif', 'osmose-ads'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($template->ID); ?>"><?php _e('Modifier', 'osmose-ads'); ?></a> |
                            <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates&template_id=' . $template->ID); ?>"><?php _e('Voir', 'osmose-ads'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php _e('Aucun template trouvé', 'osmose-ads'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#create-template-btn').on('click', function(e) {
        e.preventDefault();
        $('#create-template-modal').show();
    });
    
    $('.cancel-modal').on('click', function() {
        $('#create-template-modal').hide();
    });
    
    $('#create-template-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'osmose_ads_create_template',
            nonce: osmoseAds.nonce,
            service_name: $('input[name="service_name"]').val(),
            ai_prompt: $('textarea[name="ai_prompt"]').val(),
        };
        
        $('#template-result').html('<p><?php _e('Création en cours...', 'osmose-ads'); ?></p>');
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#template-result').html(
                        '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                    );
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#template-result').html(
                        '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                    );
                }
            },
            error: function() {
                $('#template-result').html(
                    '<div class="notice notice-error"><p><?php _e('Erreur lors de la création', 'osmose-ads'); ?></p></div>'
                );
            }
        });
    });
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

