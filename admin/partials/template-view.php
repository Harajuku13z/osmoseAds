<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

$template_id = intval($_GET['template_id']);
$template = get_post($template_id);

if (!$template || $template->post_type !== 'ad_template') {
    wp_die(__('Template non trouvé', 'osmose-ads'));
}

// Récupérer les meta
$service_name = get_post_meta($template_id, 'service_name', true);
$service_slug = get_post_meta($template_id, 'service_slug', true);
$featured_image_id = get_post_thumbnail_id($template_id);
$realization_images = get_post_meta($template_id, 'realization_images', true) ?: array();
$meta_title = get_post_meta($template_id, 'meta_title', true);
$meta_description = get_post_meta($template_id, 'meta_description', true);
$meta_keywords = get_post_meta($template_id, 'meta_keywords', true);
$og_title = get_post_meta($template_id, 'og_title', true);
$og_description = get_post_meta($template_id, 'og_description', true);
$twitter_title = get_post_meta($template_id, 'twitter_title', true);
$twitter_description = get_post_meta($template_id, 'twitter_description', true);
$short_description = get_post_meta($template_id, 'short_description', true);
$is_active = get_post_meta($template_id, 'is_active', true);
$usage_count = get_post_meta($template_id, 'usage_count', true) ?: 0;
$ai_prompt = get_post_meta($template_id, 'ai_prompt_used', true);

// Afficher le message de sauvegarde
if (isset($save_success) && $save_success) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($save_message) . '</p></div>';
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-link mb-2">
                <i class="bi bi-arrow-left me-1"></i> <?php _e('Retour à la liste', 'osmose-ads'); ?>
            </a>
            <h1 class="h3 mb-1"><?php echo esc_html($template->post_title); ?></h1>
            <p class="text-muted mb-0">
                <?php _e('Service:', 'osmose-ads'); ?> <strong><?php echo esc_html($service_name); ?></strong> |
                <?php _e('Utilisations:', 'osmose-ads'); ?> <strong><?php echo number_format_i18n($usage_count); ?></strong> |
                <span class="<?php echo ($is_active !== '0') ? 'text-success' : 'text-danger'; ?>">
                    <?php echo ($is_active !== '0') ? __('Actif', 'osmose-ads') : __('Inactif', 'osmose-ads'); ?>
                </span>
            </p>
        </div>
    </div>

    <form method="post" action="" id="template-edit-form">
        <?php wp_nonce_field('osmose_ads_save_template_' . $template_id, '_wpnonce'); ?>
        <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
        <input type="hidden" name="save_template" value="1">
        
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Contenu du template -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i><?php _e('Contenu du Template', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Titre', 'osmose-ads'); ?></label>
                            <input type="text" name="template_title" class="form-control" value="<?php echo esc_attr($template->post_title); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Description courte', 'osmose-ads'); ?></label>
                            <textarea name="short_description" class="form-control" rows="2"><?php echo esc_textarea($short_description); ?></textarea>
                            <small class="form-text text-muted"><?php _e('Description utilisée dans les aperçus', 'osmose-ads'); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Contenu HTML', 'osmose-ads'); ?></label>
                            <?php
                            wp_editor($template->post_content, 'template_content', array(
                                'textarea_name' => 'template_content',
                                'textarea_rows' => 20,
                                'media_buttons' => true,
                                'tinymce' => array(
                                    'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,code,fullscreen',
                                    'toolbar2' => '',
                                ),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Images de réalisations -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-images me-2"></i><?php _e('Photos des Réalisations', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="realization-images-container" class="d-flex flex-wrap gap-3 mb-3">
                            <?php if (!empty($realization_images)): ?>
                                <?php foreach ($realization_images as $img_id): ?>
                                    <?php $img = wp_get_attachment_image_src($img_id, 'thumbnail'); ?>
                                    <?php if ($img): ?>
                                        <div class="realization-image-item position-relative" data-image-id="<?php echo $img_id; ?>">
                                            <img src="<?php echo esc_url($img[0]); ?>" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-image" style="margin: 5px;">
                                                <i class="bi bi-x"></i>
                                            </button>
                                            <input type="hidden" name="realization_images[]" value="<?php echo $img_id; ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary" id="add-realization-image">
                            <i class="bi bi-plus-circle me-1"></i>
                            <?php _e('Ajouter une photo', 'osmose-ads'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="col-lg-4">
                <!-- Image mise en avant -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-image me-2"></i><?php _e('Image mise en avant', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="featured-image-preview" class="mb-3">
                            <?php if ($featured_image_id): ?>
                                <?php echo wp_get_attachment_image($featured_image_id, 'medium', false, array('class' => 'img-fluid', 'style' => 'max-width: 100%; height: auto;')); ?>
                            <?php else: ?>
                                <p class="text-muted"><?php _e('Aucune image', 'osmose-ads'); ?></p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100" id="set-featured-image">
                            <?php echo $featured_image_id ? __('Changer l\'image', 'osmose-ads') : __('Choisir une image', 'osmose-ads'); ?>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm w-100 mt-2" id="remove-featured-image" style="<?php echo !$featured_image_id ? 'display: none;' : ''; ?>">
                            <?php _e('Retirer l\'image', 'osmose-ads'); ?>
                        </button>
                        <input type="hidden" name="featured_image_id" id="featured_image_id" value="<?php echo $featured_image_id; ?>">
                    </div>
                </div>

                <!-- Meta SEO -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-search me-2"></i><?php _e('Métadonnées SEO', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Meta Title', 'osmose-ads'); ?></label>
                            <input type="text" name="meta_title" class="form-control" value="<?php echo esc_attr($meta_title); ?>" maxlength="60">
                            <small class="form-text text-muted"><?php _e('Recommandé: 50-60 caractères', 'osmose-ads'); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Meta Description', 'osmose-ads'); ?></label>
                            <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo esc_textarea($meta_description); ?></textarea>
                            <small class="form-text text-muted"><?php _e('Recommandé: 150-160 caractères', 'osmose-ads'); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Meta Keywords', 'osmose-ads'); ?></label>
                            <input type="text" name="meta_keywords" class="form-control" value="<?php echo esc_attr($meta_keywords); ?>" placeholder="mot-clé1, mot-clé2, mot-clé3">
                            <small class="form-text text-muted"><?php _e('Séparez par des virgules', 'osmose-ads'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Réseaux sociaux -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-share me-2"></i><?php _e('Open Graph / Twitter', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php _e('OG Title', 'osmose-ads'); ?></label>
                            <input type="text" name="og_title" class="form-control" value="<?php echo esc_attr($og_title); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('OG Description', 'osmose-ads'); ?></label>
                            <textarea name="og_description" class="form-control" rows="2"><?php echo esc_textarea($og_description); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Twitter Title', 'osmose-ads'); ?></label>
                            <input type="text" name="twitter_title" class="form-control" value="<?php echo esc_attr($twitter_title); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e('Twitter Description', 'osmose-ads'); ?></label>
                            <textarea name="twitter_description" class="form-control" rows="2"><?php echo esc_textarea($twitter_description); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Statut -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-toggle-on me-2"></i><?php _e('Statut', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php checked($is_active !== '0', true); ?>>
                            <label class="form-check-label" for="is_active">
                                <?php _e('Template actif', 'osmose-ads'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save me-1"></i>
                            <?php _e('Enregistrer les modifications', 'osmose-ads'); ?>
                        </button>
                        <a href="<?php echo get_edit_post_link($template_id); ?>" class="btn btn-secondary w-100">
                            <i class="bi bi-pencil me-1"></i>
                            <?php _e('Éditer dans WordPress', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // WordPress Media Library pour l'image mise en avant
    var featuredImageFrame;
    $('#set-featured-image').on('click', function(e) {
        e.preventDefault();
        
        if (featuredImageFrame) {
            featuredImageFrame.open();
            return;
        }
        
        featuredImageFrame = wp.media({
            title: '<?php _e('Choisir l\'image mise en avant', 'osmose-ads'); ?>',
            button: {
                text: '<?php _e('Utiliser cette image', 'osmose-ads'); ?>'
            },
            multiple: false
        });
        
        featuredImageFrame.on('select', function() {
            var attachment = featuredImageFrame.state().get('selection').first().toJSON();
            $('#featured_image_id').val(attachment.id);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" class="img-fluid" style="max-width: 100%; height: auto;">');
            $('#set-featured-image').text('<?php _e('Changer l\'image', 'osmose-ads'); ?>');
            $('#remove-featured-image').show();
        });
        
        featuredImageFrame.open();
    });
    
    $('#remove-featured-image').on('click', function(e) {
        e.preventDefault();
        $('#featured_image_id').val('');
        $('#featured-image-preview').html('<p class="text-muted"><?php _e('Aucune image', 'osmose-ads'); ?></p>');
        $('#set-featured-image').text('<?php _e('Choisir une image', 'osmose-ads'); ?>');
        $(this).hide();
    });
    
    // WordPress Media Library pour les images de réalisations
    var realizationImageFrame;
    $('#add-realization-image').on('click', function(e) {
        e.preventDefault();
        
        // Sécurité : vérifier que la médiathèque WordPress est disponible
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php echo esc_js(__('La bibliothèque média WordPress n\'est pas disponible sur cette page. Veuillez recharger la page et vérifier que vous êtes bien connecté(e).', 'osmose-ads')); ?>');
            return;
        }
        
        if (realizationImageFrame) {
            realizationImageFrame.open();
            return;
        }
        
        realizationImageFrame = wp.media({
            title: '<?php _e('Ajouter des photos de réalisations', 'osmose-ads'); ?>',
            button: {
                text: '<?php _e('Ajouter les images sélectionnées', 'osmose-ads'); ?>'
            },
            multiple: true
        });
        
        realizationImageFrame.on('select', function() {
            var attachments = realizationImageFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                var imageId = attachment.id;
                // Vérifier si l'image n'est pas déjà ajoutée
                if ($('#realization-images-container').find('[data-image-id="' + imageId + '"]').length === 0) {
                    var thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                    var imageHtml = '<div class="realization-image-item position-relative" data-image-id="' + imageId + '">' +
                        '<img src="' + thumbUrl + '" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">' +
                        '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-image" style="margin: 5px;"><i class="bi bi-x"></i></button>' +
                        '<input type="hidden" name="realization_images[]" value="' + imageId + '">' +
                        '</div>';
                    $('#realization-images-container').append(imageHtml);
                }
            });
        });
        
        realizationImageFrame.open();
    });
    
    // Supprimer une image de réalisation
    $(document).on('click', '.remove-image', function() {
        $(this).closest('.realization-image-item').remove();
    });
});
</script>
<?php
