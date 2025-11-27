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

// Initialiser les variables de message si elles ne sont pas définies
if (!isset($save_success)) {
    $save_success = false;
}
if (!isset($save_message)) {
    $save_message = '';
}

// Récupérer les meta
$service_name = get_post_meta($template_id, 'service_name', true);
$service_slug = get_post_meta($template_id, 'service_slug', true);
$featured_image_id = get_post_thumbnail_id($template_id);
$realization_images_raw = get_post_meta($template_id, 'realization_images', true);
// Gérer les deux formats : tableau d'IDs ou tableau d'objets avec id/keywords
$realization_images = array();
if (is_array($realization_images_raw)) {
    foreach ($realization_images_raw as $item) {
        if (is_numeric($item)) {
            $realization_images[] = intval($item);
        } elseif (is_array($item) && isset($item['id'])) {
            $realization_images[] = intval($item['id']);
        }
    }
} elseif (!empty($realization_images_raw)) {
    $realization_images = array_map('intval', explode(',', $realization_images_raw));
}
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

    <form method="post" action="<?php echo admin_url('admin.php?page=osmose-ads-templates&template_id=' . $template_id); ?>" id="template-edit-form">
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
                            <label class="form-label"><?php _e('Service (mot-clé principal)', 'osmose-ads'); ?></label>
                            <input type="text" name="service_name" class="form-control" value="<?php echo esc_attr($service_name); ?>" placeholder="<?php esc_attr_e('Ex: Couvreur zingueur, Isolation des combles, etc.', 'osmose-ads'); ?>">
                            <small class="form-text text-muted">
                                <?php _e('Ce champ définit le service principal utilisé pour générer les annonces (slug, SEO, etc.).', 'osmose-ads'); ?>
                            </small>
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

                <!-- Photos des réalisations -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-images me-2"></i><?php _e('Photos des Réalisations', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2 mb-3">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                <?php _e('Les mots-clés que vous saisissez seront utilisés comme attribut ALT des images pour améliorer le référencement (SEO).', 'osmose-ads'); ?>
                            </small>
                        </div>
                        <div id="realization-images-container" class="mb-3 d-flex flex-wrap gap-3" style="min-height: 80px; padding: 15px; border: 2px dashed #ddd; border-radius: 8px; background: #f8f9fa;">
                            <?php if (!empty($realization_images) && is_array($realization_images)): 
                                foreach ($realization_images as $index => $img_id): 
                                    if (!wp_attachment_is_image($img_id)) continue;
                                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                                    $img_keywords = get_post_meta($img_id, '_osmose_image_keywords', true);
                            ?>
                                <div class="realization-image-item" data-image-id="<?php echo $img_id; ?>" style="position: relative; width: 120px;">
                                    <img src="<?php echo esc_url($img_url); ?>" class="img-thumbnail" style="width: 100%; height: auto; cursor: pointer;" alt="Preview">
                                    <button type="button" class="btn btn-sm btn-danger remove-realization-image" data-image-id="<?php echo $img_id; ?>" style="position: absolute; top: 5px; right: 5px; padding: 2px 6px; font-size: 12px;" title="<?php _e('Supprimer', 'osmose-ads'); ?>">
                                        <i class="bi bi-x"></i>
                                    </button>
                                    <input type="text" 
                                           class="form-control form-control-sm mt-2" 
                                           name="realization_keywords[<?php echo $img_id; ?>]" 
                                           value="<?php echo esc_attr($img_keywords); ?>" 
                                           placeholder="<?php esc_attr_e('Mots-clés ALT', 'osmose-ads'); ?>"
                                           style="font-size: 11px;">
                                </div>
                            <?php endforeach; 
                            else: ?>
                                <p class="text-muted mb-0 w-100 text-center"><?php _e('Aucune photo ajoutée', 'osmose-ads'); ?></p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-realization-images">
                            <i class="bi bi-images me-1"></i><?php _e('Ajouter des photos', 'osmose-ads'); ?>
                        </button>
                        <input type="hidden" name="realization_images" id="realization_images" value="<?php echo esc_attr(is_array($realization_images) ? implode(',', $realization_images) : ''); ?>">
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
        
        // Sécurité : vérifier que la médiathèque WordPress est disponible
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php echo esc_js(__('La bibliothèque média WordPress n\'est pas disponible sur cette page. Veuillez recharger la page et vérifier que vous êtes bien connecté(e).', 'osmose-ads')); ?>');
            return;
        }
        
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
    
    // Gestion des images de réalisations
    var realizationImagesFrame;
    var selectedRealizationImages = <?php 
        $images_data = array();
        if (!empty($realization_images) && is_array($realization_images)) {
            foreach ($realization_images as $img_id) {
                if (wp_attachment_is_image($img_id)) {
                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                    $keywords = get_post_meta($img_id, '_osmose_image_keywords', true);
                    $images_data[] = array(
                        'id' => $img_id,
                        'url' => $img_url ? $img_url : wp_get_attachment_image_url($img_id, 'full'),
                        'keywords' => $keywords ? $keywords : ''
                    );
                }
            }
        }
        echo json_encode($images_data);
    ?>;
    
    function updateRealizationImagesDisplay() {
        var container = $('#realization-images-container');
        container.empty();
        
        if (selectedRealizationImages.length === 0) {
            container.html('<p class="text-muted mb-0 w-100 text-center"><?php _e('Aucune photo ajoutée', 'osmose-ads'); ?></p>');
            $('#realization_images').val('');
            return;
        }
        
        var ids = [];
        selectedRealizationImages.forEach(function(img) {
            ids.push(img.id);
            var item = $('<div class="realization-image-item" data-image-id="' + img.id + '" style="position: relative; width: 120px;">' +
                '<img src="' + img.url + '" class="img-thumbnail" style="width: 100%; height: auto; cursor: pointer;" alt="Preview">' +
                '<button type="button" class="btn btn-sm btn-danger remove-realization-image" data-image-id="' + img.id + '" style="position: absolute; top: 5px; right: 5px; padding: 2px 6px; font-size: 12px;" title="<?php _e('Supprimer', 'osmose-ads'); ?>">' +
                '<i class="bi bi-x"></i></button>' +
                '<input type="text" class="form-control form-control-sm mt-2" name="realization_keywords[' + img.id + ']" value="' + (img.keywords || '') + '" placeholder="<?php esc_attr_e('Mots-clés ALT', 'osmose-ads'); ?>" style="font-size: 11px;">' +
                '</div>');
            container.append(item);
        });
        
        $('#realization_images').val(ids.join(','));
    }
    
    $('#add-realization-images').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php echo esc_js(__('La bibliothèque média WordPress n\'est pas disponible sur cette page. Veuillez recharger la page et vérifier que vous êtes bien connecté(e).', 'osmose-ads')); ?>');
            return;
        }
        
        if (realizationImagesFrame) {
            realizationImagesFrame.open();
            return;
        }
        
        realizationImagesFrame = wp.media({
            title: '<?php _e('Choisir des photos de réalisations', 'osmose-ads'); ?>',
            button: {
                text: '<?php _e('Ajouter les photos', 'osmose-ads'); ?>'
            },
            multiple: true
        });
        
        realizationImagesFrame.on('select', function() {
            var attachments = realizationImagesFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                // Vérifier si l'image n'est pas déjà dans la liste
                var exists = selectedRealizationImages.some(function(img) {
                    return img.id === attachment.id;
                });
                
                if (!exists) {
                    var keywords = prompt('<?php echo esc_js(__('Mots-clés pour l\'attribut ALT de cette image :', 'osmose-ads')); ?>\n<?php echo esc_js(__('(Ces mots-clés seront utilisés comme texte alternatif pour le SEO)', 'osmose-ads')); ?>', '');
                    if (keywords === null) {
                        keywords = ''; // Si l'utilisateur annule, on met une chaîne vide
                    }
                    
                    var imgUrl = attachment.url;
                    if (attachment.sizes && attachment.sizes.thumbnail) {
                        imgUrl = attachment.sizes.thumbnail.url;
                    }
                    
                    selectedRealizationImages.push({
                        id: attachment.id,
                        url: imgUrl,
                        keywords: keywords || ''
                    });
                }
            });
            
            updateRealizationImagesDisplay();
        });
        
        realizationImagesFrame.open();
    });
    
    $(document).on('click', '.remove-realization-image', function() {
        var imgId = parseInt($(this).data('image-id'));
        selectedRealizationImages = selectedRealizationImages.filter(function(img) {
            return img.id !== imgId;
        });
        updateRealizationImagesDisplay();
    });
    
    // Initialiser le champ hidden avec les images existantes
    if (selectedRealizationImages.length > 0) {
        var ids = selectedRealizationImages.map(function(img) { return img.id; });
        $('#realization_images').val(ids.join(','));
    } else {
        // Si pas d'images, s'assurer que le champ est vide
        $('#realization_images').val('');
    }
});
</script>
<?php
