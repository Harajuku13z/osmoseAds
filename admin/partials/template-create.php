<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';
?>

<div class="osmose-template-create-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?php _e('Créer un Template', 'osmose-ads'); ?></h1>
            <p class="text-muted"><?php _e('Générez un nouveau template d\'annonce avec l\'IA - Simple et rapide', 'osmose-ads'); ?></p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> <?php _e('Retour', 'osmose-ads'); ?>
        </a>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mb-4">
        <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i><?php _e('Comment ça marche ?', 'osmose-ads'); ?></h5>
        <p class="mb-2">
            <?php _e('1. Entrez un mot-clé principal (ex: "Couverture et toiture", "Plomberie", etc.)', 'osmose-ads'); ?><br>
            <?php _e('2. Ajoutez une courte description (optionnel)', 'osmose-ads'); ?><br>
            <?php _e('3. L\'IA génère automatiquement un contenu professionnel en utilisant les informations configurées dans les réglages.', 'osmose-ads'); ?>
        </p>
    </div>

    <!-- Formulaire de création simplifié -->
    <div class="card">
        <div class="card-body">
            <form id="createTemplateForm">
                <?php wp_nonce_field('osmose_ads_nonce', 'nonce'); ?>
                
                <input type="hidden" name="creation_mode" value="custom">

                <!-- Mot-clé principal -->
                <div class="mb-4">
                    <label for="service_name" class="form-label fw-bold"><?php _e('Mot-clé principal (Service)', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="service_name" 
                           name="service_name" 
                           placeholder="<?php _e('Ex: Couverture et toiture', 'osmose-ads'); ?>"
                           required>
                    <small class="form-text text-muted"><?php _e('Le service principal pour lequel vous voulez créer des annonces', 'osmose-ads'); ?></small>
                </div>

                <!-- Description courte -->
                <div class="mb-4">
                    <label for="service_description" class="form-label fw-bold"><?php _e('Description courte (optionnel)', 'osmose-ads'); ?></label>
                    <textarea class="form-control" 
                              id="service_description" 
                              name="service_description" 
                              rows="3" 
                              placeholder="<?php _e('Décrivez brièvement ce service en 1-2 phrases...', 'osmose-ads'); ?>"></textarea>
                    <small class="form-text text-muted"><?php _e('Cette description aidera l\'IA à générer un contenu plus précis', 'osmose-ads'); ?></small>
                </div>
                
                <!-- Informations automatiques -->
                <div class="alert alert-success">
                    <strong><i class="bi bi-check-circle me-2"></i><?php _e('Informations utilisées automatiquement :', 'osmose-ads'); ?></strong>
                    <ul class="mb-0 mt-2">
                        <li><?php _e('Nom de l\'entreprise :', 'osmose-ads'); ?> <strong><?php echo esc_html(get_bloginfo('name')); ?></strong></li>
                        <li><?php _e('Téléphone :', 'osmose-ads'); ?> <strong><?php echo esc_html(get_option('osmose_ads_company_phone', 'Non configuré')); ?></strong></li>
                        <li><?php _e('Email :', 'osmose-ads'); ?> <strong><?php echo esc_html(get_option('osmose_ads_company_email', 'Non configuré')); ?></strong></li>
                        <li><?php _e('Adresse :', 'osmose-ads'); ?> <strong><?php echo esc_html(get_option('osmose_ads_company_address', 'Non configurée')); ?></strong></li>
                        <li><?php _e('Services configurés :', 'osmose-ads'); ?> <strong><?php 
                            $services = get_option('osmose_ads_services', array());
                            echo !empty($services) ? implode(', ', array_slice($services, 0, 3)) . (count($services) > 3 ? '...' : '') : 'Aucun';
                        ?></strong></li>
                    </ul>
                    <p class="mb-0 mt-2">
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-settings'); ?>" class="alert-link">
                            <i class="bi bi-gear"></i> <?php _e('Modifier ces informations dans les réglages', 'osmose-ads'); ?>
                        </a>
                    </p>
                </div>
                
                <hr class="my-4">
                
                <!-- Images (optionnel) -->
                <div class="mb-4">
                    <h5 class="mb-3"><i class="bi bi-images me-2"></i><?php _e('Images (Optionnel)', 'osmose-ads'); ?></h5>
                    
                    <!-- Image mise en avant -->
                    <div class="mb-4">
                        <label class="form-label"><?php _e('Image mise en avant', 'osmose-ads'); ?></label>
                        <div id="featured-image-preview" class="mb-2" style="min-height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f8f9fa;">
                            <p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="select-featured-image">
                                <i class="bi bi-image"></i> <?php _e('Choisir une image', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="remove-featured-image" style="display: none;">
                                <i class="bi bi-x"></i> <?php _e('Retirer', 'osmose-ads'); ?>
                            </button>
                        </div>
                        <input type="hidden" name="featured_image_id" id="featured_image_id" value="">
                    </div>
                    
                    <!-- Photos des réalisations -->
                    <div class="mb-3">
                        <label class="form-label"><?php _e('Photos des réalisations', 'osmose-ads'); ?></label>
                        <small class="form-text text-muted d-block mb-2"><?php _e('Ces photos seront automatiquement intégrées dans le contenu généré par l\'IA', 'osmose-ads'); ?></small>
                        <div id="realization-images-container" class="mb-2 d-flex flex-wrap gap-2" style="min-height: 80px; padding: 10px; border: 2px dashed #ddd; border-radius: 8px; background: #f8f9fa;">
                            <p class="text-muted mb-0 w-100 text-center"><?php _e('Aucune photo ajoutée', 'osmose-ads'); ?></p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-realization-images">
                            <i class="bi bi-images"></i> <?php _e('Ajouter des photos', 'osmose-ads'); ?>
                        </button>
                        <input type="hidden" name="realization_images" id="realization_images" value="">
                        <input type="hidden" name="realization_images_keywords" id="realization_images_keywords" value="">
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> <?php _e('Annuler', 'osmose-ads'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="btn-generate-template">
                        <i class="bi bi-magic"></i> <?php _e('Générer le Template', 'osmose-ads'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Zone de résultat -->
    <div id="creation-result" class="mt-4"></div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Osmose ADS: Page de création de template chargée (version simplifiée)');
    
    // Media Uploader pour l'image mise en avant
    let featuredImageFrame;
    
    $('#select-featured-image').on('click', function(e) {
        e.preventDefault();
        
        if (featuredImageFrame) {
            featuredImageFrame.open();
            return;
        }
        
        featuredImageFrame = wp.media({
            title: <?php echo wp_json_encode(__('Choisir une image mise en avant', 'osmose-ads')); ?>,
            button: {
                text: <?php echo wp_json_encode(__('Sélectionner', 'osmose-ads')); ?>
            },
            multiple: false
        });
        
        featuredImageFrame.on('select', function() {
            const attachment = featuredImageFrame.state().get('selection').first().toJSON();
            $('#featured_image_id').val(attachment.id);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto; border-radius: 8px;">');
            $('#remove-featured-image').show();
        });
        
        featuredImageFrame.open();
    });
    
    $('#remove-featured-image').on('click', function(e) {
        e.preventDefault();
        $('#featured_image_id').val('');
        $('#featured-image-preview').html('<p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>');
        $(this).hide();
    });
    
    // Media Uploader pour les photos de réalisations
    let realizationImagesFrame;
    let selectedRealizationImages = [];
    
    $('#add-realization-images').on('click', function(e) {
        e.preventDefault();
        
        if (realizationImagesFrame) {
            realizationImagesFrame.open();
            return;
        }
        
        realizationImagesFrame = wp.media({
            title: <?php echo wp_json_encode(__('Choisir des photos de réalisations', 'osmose-ads')); ?>,
            button: {
                text: <?php echo wp_json_encode(__('Sélectionner', 'osmose-ads')); ?>
            },
            multiple: true
        });
        
        realizationImagesFrame.on('select', function() {
            const attachments = realizationImagesFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                // Vérifier si l'image n'est pas déjà ajoutée
                if (!selectedRealizationImages.find(img => img.id === attachment.id)) {
                    const keywords = prompt(<?php echo wp_json_encode(__('Mots-clés pour cette image (optionnel) :', 'osmose-ads')); ?>, '');
                    
                    selectedRealizationImages.push({
                        id: attachment.id,
                        url: attachment.url,
                        keywords: keywords || ''
                    });
                }
            });
            
            updateRealizationImagesDisplay();
        });
        
        realizationImagesFrame.open();
    });
    
    function updateRealizationImagesDisplay() {
        const container = $('#realization-images-container');
        
        if (selectedRealizationImages.length === 0) {
            container.html('<p class="text-muted mb-0 w-100 text-center"><?php _e('Aucune photo ajoutée', 'osmose-ads'); ?></p>');
        } else {
            let html = '';
            selectedRealizationImages.forEach(function(image, index) {
                html += '<div class="position-relative" style="width: 120px;">' +
                    '<img src="' + image.url + '" class="img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">' +
                    '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 remove-realization-image" data-index="' + index + '" style="padding: 2px 6px; line-height: 1;">' +
                    '<i class="bi bi-x"></i>' +
                    '</button>' +
                    (image.keywords ? '<small class="d-block text-center mt-1" style="font-size: 0.7rem; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + image.keywords + '">' + image.keywords + '</small>' : '') +
                    '</div>';
            });
            container.html(html);
        }
        
        // Mettre à jour les champs cachés
        const imageIds = selectedRealizationImages.map(img => img.id).join(',');
        const imageKeywords = selectedRealizationImages.map(img => img.keywords).join('|||');
        
        $('#realization_images').val(imageIds);
        $('#realization_images_keywords').val(imageKeywords);
    }
    
    // Supprimer une photo de réalisation
    $(document).on('click', '.remove-realization-image', function() {
        const index = $(this).data('index');
        selectedRealizationImages.splice(index, 1);
        updateRealizationImagesDisplay();
    });
    
    // Soumission du formulaire
    $('#createTemplateForm').on('submit', function(e) {
        e.preventDefault();
        
        const serviceName = $('#service_name').val().trim();
        if (!serviceName) {
            alert(<?php echo wp_json_encode(__('Veuillez entrer un mot-clé principal', 'osmose-ads')); ?>);
            return;
        }
        
        const $btn = $('#btn-generate-template');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i><?php _e('Génération en cours...', 'osmose-ads'); ?>');
        
        const formData = {
            action: 'osmose_ads_create_template',
            nonce: $('input[name="nonce"]').val(),
            creation_mode: 'custom',
            service_name: serviceName,
            service_description: $('#service_description').val().trim(),
            service_keywords: serviceName, // Utiliser le service_name comme keywords
            featured_image_id: $('#featured_image_id').val(),
            realization_images: $('#realization_images').val(),
            realization_images_keywords: $('#realization_images_keywords').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Réponse AJAX:', response);
                
                if (response.success) {
                    $('#creation-result').html(
                        '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        '<h5 class="alert-heading"><i class="bi bi-check-circle me-2"></i><?php _e('Succès !', 'osmose-ads'); ?></h5>' +
                        '<p class="mb-0">' + response.data.message + '</p>' +
                        '<hr>' +
                        '<div class="d-flex gap-2">' +
                        '<a href="' + response.data.view_url + '" class="btn btn-sm btn-primary"><?php _e('Voir le template', 'osmose-ads'); ?></a>' +
                        '<a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-sm btn-secondary"><?php _e('Retour à la liste', 'osmose-ads'); ?></a>' +
                        '</div>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>'
                    );
                    
                    // Réinitialiser le formulaire
                    $('#createTemplateForm')[0].reset();
                    $('#featured_image_id').val('');
                    $('#featured-image-preview').html('<p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>');
                    $('#remove-featured-image').hide();
                    
                    // Réinitialiser les photos de réalisations
                    selectedRealizationImages = [];
                    updateRealizationImagesDisplay();
                    
                    // Scroll vers le résultat
                    $('html, body').animate({
                        scrollTop: $('#creation-result').offset().top - 100
                    }, 500);
                } else {
                    $('#creation-result').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i><?php _e('Erreur', 'osmose-ads'); ?></h5>' +
                        '<p class="mb-0">' + (response.data?.message || <?php echo wp_json_encode(__('Une erreur est survenue', 'osmose-ads')); ?>) + '</p>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error, xhr.responseText);
                $('#creation-result').html(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i><?php _e('Erreur', 'osmose-ads'); ?></h5>' +
                    '<p class="mb-0"><?php _e('Erreur de communication avec le serveur', 'osmose-ads'); ?></p>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>
