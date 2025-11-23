<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Gérer l'affichage/édition d'un template spécifique
if (isset($_GET['template_id']) && !empty($_GET['template_id'])) {
    $template_id = intval($_GET['template_id']);
    $template = get_post($template_id);
    
    if ($template && $template->post_type === 'ad_template') {
        // Afficher la page de visualisation/édition du template
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/template-view.php';
        require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
        return;
    }
}

// Traitement de la sauvegarde du template
if (isset($_POST['save_template']) && isset($_POST['template_id'])) {
    check_admin_referer('osmose_ads_save_template_' . $_POST['template_id']);
    
    $template_id = intval($_POST['template_id']);
    
    if (current_user_can('edit_post', $template_id)) {
        // Mettre à jour le contenu
        wp_update_post(array(
            'ID' => $template_id,
            'post_content' => wp_kses_post($_POST['template_content'] ?? ''),
            'post_title' => sanitize_text_field($_POST['template_title'] ?? ''),
        ));
        
        // Mettre à jour les meta
        $meta_fields = array(
            'featured_image_id', 'realization_images', 'meta_title', 'meta_description',
            'meta_keywords', 'og_title', 'og_description', 'twitter_title',
            'twitter_description', 'short_description', 'is_active'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($template_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Gérer l'image mise en avant
        if (isset($_POST['featured_image_id'])) {
            set_post_thumbnail($template_id, intval($_POST['featured_image_id']));
        }
        
        // Gérer les images de réalisations (array)
        if (isset($_POST['realization_images'])) {
            $images = array_map('intval', $_POST['realization_images']);
            update_post_meta($template_id, 'realization_images', $images);
        }
        
        $save_message = __('Template mis à jour avec succès', 'osmose-ads');
        $save_success = true;
    }
}

$templates = get_posts(array(
    'post_type' => 'ad_template',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC',
));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="text-muted mb-0"><?php _e('Gérez vos templates de services', 'osmose-ads'); ?></p>
    </div>
    <div>
        <button type="button" id="create-template-btn" class="btn btn-primary" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
            <i class="bi bi-plus-circle me-2"></i>
            <?php _e('Créer un Template', 'osmose-ads'); ?>
        </button>
    </div>
</div>
    
    <div id="create-template-modal" class="card" style="display: none; max-width: 900px; margin: 20px auto;">
        <div class="card-header">
            <h2 class="mb-0"><?php _e('Créer un Template depuis un Service', 'osmose-ads'); ?></h2>
        </div>
        <div class="card-body">
            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4" id="template-creation-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="preset-tab" data-bs-toggle="tab" data-bs-target="#preset-panel" type="button" role="tab">
                        <i class="bi bi-star me-1"></i><?php _e('Service préconfiguré', 'osmose-ads'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom-panel" type="button" role="tab">
                        <i class="bi bi-pencil me-1"></i><?php _e('Mots-clés personnalisés', 'osmose-ads'); ?>
                    </button>
                </li>
            </ul>
            
            <form id="create-template-form">
                <!-- Panel Services préconfigurés -->
                <div class="tab-content" id="template-creation-content">
                    <div class="tab-pane fade show active" id="preset-panel" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Sélectionner un service préconfiguré', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                            <select name="preset_service" id="preset-service" class="form-select">
                                <option value=""><?php _e('-- Choisir un service --', 'osmose-ads'); ?></option>
                                <?php
                                require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/preset-services.php';
                                $preset_services = osmose_ads_get_preset_services();
                                foreach ($preset_services as $key => $service):
                                ?>
                                    <option value="<?php echo esc_attr($key); ?>" 
                                            data-name="<?php echo esc_attr($service['name']); ?>"
                                            data-keywords="<?php echo esc_attr($service['keywords']); ?>"
                                            data-description="<?php echo esc_attr($service['description']); ?>"
                                            data-sections='<?php echo json_encode($service['sections']); ?>'>
                                        <?php echo esc_html($service['category'] . ' - ' . $service['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?php _e('Choisissez un service préconfiguré pour générer automatiquement un contenu de qualité', 'osmose-ads'); ?></small>
                        </div>
                        
                        <div id="preset-service-details" class="alert alert-info" style="display: none;">
                            <h6 class="alert-heading"><?php _e('Service sélectionné:', 'osmose-ads'); ?> <span id="preset-service-name"></span></h6>
                            <p class="mb-2"><strong><?php _e('Description:', 'osmose-ads'); ?></strong> <span id="preset-service-description"></span></p>
                            <p class="mb-2"><strong><?php _e('Mots-clés:', 'osmose-ads'); ?></strong> <span id="preset-service-keywords"></span></p>
                            <div id="preset-service-sections"></div>
                        </div>
                        
                        <input type="hidden" name="creation_mode" value="preset" id="creation-mode-preset">
                        <input type="hidden" name="service_name" id="preset-service-name-input" value="">
                    </div>
                    
                    <!-- Panel Mots-clés personnalisés -->
                    <div class="tab-pane fade" id="custom-panel" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Nom du Service', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="service_name" id="custom-service-name" class="form-control" placeholder="<?php _e('Ex: Dépannage et réparation de fuites d\'eau', 'osmose-ads'); ?>">
                            <small class="form-text text-muted"><?php _e('Nom complet du service que vous proposez', 'osmose-ads'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Mots-clés principaux', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="service_keywords" id="custom-service-keywords" class="form-control" placeholder="<?php _e('Ex: fuite eau, plomberie, réparation, dépannage urgence', 'osmose-ads'); ?>">
                            <small class="form-text text-muted"><?php _e('Séparez les mots-clés par des virgules. Ces mots-clés seront utilisés pour générer un contenu optimisé SEO', 'osmose-ads'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Description du service', 'osmose-ads'); ?></label>
                            <textarea name="service_description" id="custom-service-description" class="form-control" rows="3" placeholder="<?php _e('Décrivez brièvement votre service...', 'osmose-ads'); ?>"></textarea>
                            <small class="form-text text-muted"><?php _e('Description courte qui sera utilisée pour améliorer la qualité du contenu généré', 'osmose-ads'); ?></small>
                        </div>
                        
                        <input type="hidden" name="creation_mode" value="custom" id="creation-mode-custom">
                    </div>
                </div>
                
                <!-- Sections communes (images et prompt) -->
                <hr class="my-4">
                <h5 class="mb-3"><?php _e('Enrichissement du contenu', 'osmose-ads'); ?></h5>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Image mise en avant', 'osmose-ads'); ?></label>
                    <div id="create-featured-image-preview" class="mb-2" style="min-height: 150px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="create-set-featured-image">
                        <i class="bi bi-image me-1"></i><?php _e('Choisir une image', 'osmose-ads'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="create-remove-featured-image" style="display: none;">
                        <i class="bi bi-x me-1"></i><?php _e('Retirer', 'osmose-ads'); ?>
                    </button>
                    <input type="hidden" name="featured_image_id" id="create_featured_image_id" value="">
                    <small class="form-text text-muted"><?php _e('Image principale qui illustrera le service', 'osmose-ads'); ?></small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Photos des réalisations', 'osmose-ads'); ?></label>
                    <div id="create-realization-images-container" class="d-flex flex-wrap gap-2 mb-2" style="min-height: 50px;"></div>
                    <button type="button" class="btn btn-primary btn-sm" id="create-add-realization-images">
                        <i class="bi bi-images me-1"></i><?php _e('Ajouter des photos', 'osmose-ads'); ?>
                    </button>
                    <small class="form-text text-muted"><?php _e('Photos d\'exemples de réalisations pour enrichir le contenu', 'osmose-ads'); ?></small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Prompt IA personnalisé (Optionnel)', 'osmose-ads'); ?></label>
                    <textarea name="ai_prompt" rows="5" class="form-control" placeholder="<?php _e('Laissez vide pour utiliser le prompt optimisé automatique', 'osmose-ads'); ?>"></textarea>
                    <small class="form-text text-muted"><?php _e('Si vide, un prompt professionnel optimisé sera généré automatiquement selon vos choix', 'osmose-ads'); ?></small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-magic me-1"></i><?php _e('Générer le Template', 'osmose-ads'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary cancel-modal">
                        <?php _e('Annuler', 'osmose-ads'); ?>
                    </button>
                </div>
            </form>
            <div id="template-result" class="mt-3"></div>
        </div>
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
    // Enqueue WordPress Media si disponible
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        console.warn('WordPress Media Library non disponible');
    }
    
    $('#create-template-btn').on('click', function(e) {
        e.preventDefault();
        $('#create-template-modal').show();
        // Enqueue media si nécessaire
        if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
            // Media déjà chargé
        }
    });
    
    $('.cancel-modal').on('click', function() {
        $('#create-template-modal').hide();
    });
    
    // Media Library pour l'image mise en avant (création)
    var createFeaturedImageFrame;
    $('#create-set-featured-image').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php _e('La bibliothèque média WordPress n\'est pas disponible. Veuillez recharger la page.', 'osmose-ads'); ?>');
            return;
        }
        
        if (createFeaturedImageFrame) {
            createFeaturedImageFrame.open();
            return;
        }
        
        createFeaturedImageFrame = wp.media({
            title: '<?php _e('Choisir l\'image mise en avant', 'osmose-ads'); ?>',
            button: {
                text: '<?php _e('Utiliser cette image', 'osmose-ads'); ?>'
            },
            multiple: false
        });
        
        createFeaturedImageFrame.on('select', function() {
            var attachment = createFeaturedImageFrame.state().get('selection').first().toJSON();
            $('#create_featured_image_id').val(attachment.id);
            $('#create-featured-image-preview').html('<img src="' + attachment.url + '" class="img-fluid" style="max-width: 100%; height: auto; max-height: 200px;">');
            $('#create-set-featured-image').text('<?php _e('Changer l\'image', 'osmose-ads'); ?>');
            $('#create-remove-featured-image').show();
        });
        
        createFeaturedImageFrame.open();
    });
    
    $('#create-remove-featured-image').on('click', function(e) {
        e.preventDefault();
        $('#create_featured_image_id').val('');
        $('#create-featured-image-preview').html('<p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>');
        $('#create-set-featured-image').text('<?php _e('Choisir une image', 'osmose-ads'); ?>');
        $(this).hide();
    });
    
    // Media Library pour les images de réalisations (création)
    var createRealizationImageFrame;
    $('#create-add-realization-images').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php _e('La bibliothèque média WordPress n\'est pas disponible. Veuillez recharger la page.', 'osmose-ads'); ?>');
            return;
        }
        
        if (createRealizationImageFrame) {
            createRealizationImageFrame.open();
            return;
        }
        
        createRealizationImageFrame = wp.media({
            title: '<?php _e('Ajouter des photos de réalisations', 'osmose-ads'); ?>',
            button: {
                text: '<?php _e('Ajouter les images', 'osmose-ads'); ?>'
            },
            multiple: true
        });
        
        createRealizationImageFrame.on('select', function() {
            var attachments = createRealizationImageFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                var imageId = attachment.id;
                if ($('#create-realization-images-container').find('[data-image-id="' + imageId + '"]').length === 0) {
                    var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    var imageHtml = '<div class="position-relative" data-image-id="' + imageId + '" style="display: inline-block;">' +
                        '<img src="' + thumbUrl + '" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">' +
                        '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 create-remove-image" style="margin: 2px; padding: 2px 6px;"><i class="bi bi-x"></i></button>' +
                        '<input type="hidden" name="realization_images[]" value="' + imageId + '">' +
                        '</div>';
                    $('#create-realization-images-container').append(imageHtml);
                }
            });
        });
        
        createRealizationImageFrame.open();
    });
    
    $(document).on('click', '.create-remove-image', function() {
        $(this).closest('[data-image-id]').remove();
    });
    
        // Gestion du changement d'onglet
        $('#preset-service').on('change', function() {
            var selected = $(this).find(':selected');
            if (selected.val()) {
                $('#preset-service-name-input').val(selected.data('name'));
                $('#preset-service-name').text(selected.data('name'));
                $('#preset-service-description').text(selected.data('description'));
                $('#preset-service-keywords').text(selected.data('keywords'));
                
                // Afficher les sections
                var sections = selected.data('sections');
                if (sections && Object.keys(sections).length > 0) {
                    var sectionsHtml = '<strong><?php _e('Sections:', 'osmose-ads'); ?></strong><ul class="mb-0 mt-2">';
                    $.each(sections, function(key, title) {
                        sectionsHtml += '<li>' + title + '</li>';
                    });
                    sectionsHtml += '</ul>';
                    $('#preset-service-sections').html(sectionsHtml);
                }
                
                $('#preset-service-details').show();
            } else {
                $('#preset-service-details').hide();
                $('#preset-service-name-input').val('');
            }
        });
        
        // Gestion du changement d'onglet
        $('#preset-tab, #custom-tab').on('click', function() {
            var targetId = $(this).data('bs-target');
            if (targetId === '#preset-panel') {
                $('#creation-mode-preset').val('preset');
                $('#creation-mode-custom').val('');
                $('#custom-service-name').prop('required', false);
                $('#preset-service').prop('required', true);
            } else {
                $('#creation-mode-custom').val('custom');
                $('#creation-mode-preset').val('');
                $('#preset-service').prop('required', false);
                $('#custom-service-name').prop('required', true);
            }
        });
        
        $('#create-template-form').on('submit', function(e) {
        e.preventDefault();
        
        var realizationImages = [];
        $('input[name="realization_images[]"]').each(function() {
            realizationImages.push($(this).val());
        });
        
        // Déterminer le mode de création
        var creationMode = $('.nav-link.active').data('bs-target') === '#preset-panel' ? 'preset' : 'custom';
        var formData = {
            action: 'osmose_ads_create_template',
            nonce: osmoseAds.nonce,
            creation_mode: creationMode,
            preset_service: $('#preset-service').val(),
            service_name: creationMode === 'preset' ? $('#preset-service-name-input').val() : $('#custom-service-name').val(),
            service_keywords: $('#custom-service-keywords').val(),
            service_description: $('#custom-service-description').val(),
            ai_prompt: $('textarea[name="ai_prompt"]').val(),
            featured_image_id: $('#create_featured_image_id').val(),
            realization_images: realizationImages,
        };
        
        $('#template-result').html('<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i><?php _e('Création en cours...', 'osmose-ads'); ?></div>');
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#template-result').html(
                        '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + response.data.message + '</div>'
                    );
                    setTimeout(function() {
                        if (response.data.template_id) {
                            window.location.href = '<?php echo admin_url('admin.php?page=osmose-ads-templates&template_id='); ?>' + response.data.template_id;
                        } else {
                            location.reload();
                        }
                    }, 2000);
                } else {
                    $('#template-result').html(
                        '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + response.data.message + '</div>'
                    );
                }
            },
            error: function() {
                $('#template-result').html(
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php _e('Erreur lors de la création', 'osmose-ads'); ?></div>'
                );
            }
        });
    });
});
</script>
<?php
// Enqueue WordPress Media scripts sur cette page
wp_enqueue_media();
?>


<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

