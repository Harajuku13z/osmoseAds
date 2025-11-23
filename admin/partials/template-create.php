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
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-link mb-2">
                <i class="bi bi-arrow-left me-1"></i> <?php _e('Retour à la liste', 'osmose-ads'); ?>
            </a>
            <h1 class="h3 mb-1"><?php _e('Créer un nouveau Template', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Configurez votre template de service avec toutes les informations nécessaires', 'osmose-ads'); ?></p>
        </div>
    </div>
    
    <form id="create-template-form" class="row g-4">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Onglets -->
            <div class="card mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="template-creation-tabs" role="tablist">
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
                </div>
                <div class="card-body">
                    <div class="tab-content" id="template-creation-content">
                        <!-- Panel Services préconfigurés -->
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
                </div>
            </div>
            
            <!-- Images -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-images me-2"></i><?php _e('Images du Template', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Image mise en avant -->
                    <div class="mb-4">
                        <label class="form-label fw-bold"><?php _e('Image mise en avant', 'osmose-ads'); ?></label>
                        <div id="create-featured-image-preview" class="mb-2" style="min-height: 200px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f8f9fa;">
                            <p class="text-muted mb-0"><?php _e('Aucune image sélectionnée', 'osmose-ads'); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" id="create-set-featured-image">
                                <i class="bi bi-image me-1"></i><?php _e('Choisir une image', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="create-remove-featured-image" style="display: none;">
                                <i class="bi bi-x me-1"></i><?php _e('Retirer', 'osmose-ads'); ?>
                            </button>
                        </div>
                        <input type="hidden" name="featured_image_id" id="create_featured_image_id" value="">
                        <small class="form-text text-muted"><?php _e('Image principale qui illustrera le service', 'osmose-ads'); ?></small>
                    </div>
                    
                    <hr>
                    
                    <!-- Photos des réalisations -->
                    <div>
                        <label class="form-label fw-bold"><?php _e('Photos des réalisations', 'osmose-ads'); ?></label>
                        <div id="create-realization-images-container" class="d-flex flex-wrap gap-3 mb-3" style="min-height: 100px;">
                            <div class="text-muted text-center" style="width: 100%; padding: 40px;">
                                <?php _e('Aucune photo ajoutée', 'osmose-ads'); ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="create-add-realization-images">
                            <i class="bi bi-images me-1"></i><?php _e('Ajouter des photos', 'osmose-ads'); ?>
                        </button>
                        <small class="form-text text-muted d-block mt-2"><?php _e('Photos d\'exemples de réalisations pour enrichir le contenu. Les mots-clés seront associés à ces images.', 'osmose-ads'); ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Prompt IA -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-robot me-2"></i><?php _e('Configuration IA', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php _e('Prompt IA personnalisé (Optionnel)', 'osmose-ads'); ?></label>
                        <textarea name="ai_prompt" rows="6" class="form-control" placeholder="<?php _e('Laissez vide pour utiliser le prompt optimisé automatique', 'osmose-ads'); ?>"></textarea>
                        <small class="form-text text-muted"><?php _e('Si vide, un prompt professionnel optimisé sera généré automatiquement selon vos choix', 'osmose-ads'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Résumé -->
            <div class="card mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i><?php _e('Résumé', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="template-summary">
                        <p class="text-muted mb-0"><?php _e('Configurez votre template pour voir le résumé ici', 'osmose-ads'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 btn-lg mb-2">
                        <i class="bi bi-magic me-2"></i>
                        <?php _e('Générer le Template', 'osmose-ads'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>
                        <?php _e('Annuler', 'osmose-ads'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Résultat -->
    <div id="template-result" class="mt-4"></div>
</div>

<script>
// Définir osmoseAds si non défini
if (typeof osmoseAds === 'undefined') {
    window.osmoseAds = {
        ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('osmose_ads_nonce'); ?>',
        plugin_url: '<?php echo esc_url(OSMOSE_ADS_PLUGIN_URL); ?>'
    };
}

jQuery(document).ready(function($) {
    console.log('Osmose ADS: Page de création de template chargée');
    
    // Vérifier WordPress Media Library
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        console.warn('WordPress Media Library non disponible');
    }
    
    // Gestion du changement d'onglet
    $('#preset-service').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            var serviceName = selected.data('name');
            var keywords = selected.data('keywords');
            var description = selected.data('description');
            var sections = selected.data('sections');
            
            $('#preset-service-name-input').val(serviceName);
            $('#preset-service-name').text(serviceName);
            $('#preset-service-description').text(description);
            $('#preset-service-keywords').text(keywords);
            
            // Afficher les sections
            if (sections && Object.keys(sections).length > 0) {
                var sectionsHtml = '<strong><?php _e('Sections:', 'osmose-ads'); ?></strong><ul class="mb-0 mt-2">';
                $.each(sections, function(key, title) {
                    sectionsHtml += '<li>' + title + '</li>';
                });
                sectionsHtml += '</ul>';
                $('#preset-service-sections').html(sectionsHtml);
            }
            
            $('#preset-service-details').show();
            updateSummary();
        } else {
            $('#preset-service-details').hide();
            $('#preset-service-name-input').val('');
            updateSummary();
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
        updateSummary();
    });
    
    // Mettre à jour le résumé
    function updateSummary() {
        var creationMode = $('.nav-link.active').data('bs-target') === '#preset-panel' ? 'preset' : 'custom';
        var summary = '<div class="mb-2"><strong><?php _e('Mode:', 'osmose-ads'); ?></strong> ';
        summary += creationMode === 'preset' ? '<?php _e('Service préconfiguré', 'osmose-ads'); ?>' : '<?php _e('Mots-clés personnalisés', 'osmose-ads'); ?>';
        summary += '</div>';
        
        if (creationMode === 'preset') {
            var serviceName = $('#preset-service-name-input').val();
            if (serviceName) {
                summary += '<div class="mb-2"><strong><?php _e('Service:', 'osmose-ads'); ?></strong> ' + serviceName + '</div>';
            }
        } else {
            var customName = $('#custom-service-name').val();
            var keywords = $('#custom-service-keywords').val();
            if (customName) {
                summary += '<div class="mb-2"><strong><?php _e('Service:', 'osmose-ads'); ?></strong> ' + customName + '</div>';
            }
            if (keywords) {
                summary += '<div class="mb-2"><strong><?php _e('Mots-clés:', 'osmose-ads'); ?></strong> ' + keywords + '</div>';
            }
        }
        
        var featuredImg = $('#create_featured_image_id').val();
        var realizationImgs = $('input[name="realization_images[]"]').length;
        
        summary += '<div class="mb-2"><strong><?php _e('Image mise en avant:', 'osmose-ads'); ?></strong> ';
        summary += featuredImg ? '<span class="text-success">✓</span>' : '<span class="text-muted">-</span>';
        summary += '</div>';
        
        summary += '<div class="mb-2"><strong><?php _e('Photos réalisations:', 'osmose-ads'); ?></strong> ' + realizationImgs + '</div>';
        
        $('#template-summary').html(summary);
    }
    
    // Écouter les changements pour mettre à jour le résumé
    $('#preset-service, #custom-service-name, #custom-service-keywords').on('change input', updateSummary);
    
    // Media Library pour l'image mise en avant
    var createFeaturedImageFrame;
    $('#create-set-featured-image').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert(<?php echo wp_json_encode(__('La bibliothèque média WordPress n\'est pas disponible. Veuillez recharger la page.', 'osmose-ads')); ?>);
            return;
        }
        
        if (createFeaturedImageFrame) {
            createFeaturedImageFrame.open();
            return;
        }
        
        createFeaturedImageFrame = wp.media({
            title: <?php echo wp_json_encode(__('Choisir l\'image mise en avant', 'osmose-ads')); ?>,
            button: {
                text: <?php echo wp_json_encode(__('Utiliser cette image', 'osmose-ads')); ?>
            },
            multiple: false
        });
        
        createFeaturedImageFrame.on('select', function() {
            var attachment = createFeaturedImageFrame.state().get('selection').first().toJSON();
            $('#create_featured_image_id').val(attachment.id);
            $('#create-featured-image-preview').html('<img src="' + attachment.url + '" class="img-fluid rounded" style="max-width: 100%; max-height: 400px; object-fit: contain;">');
            $('#create-set-featured-image').text(<?php echo wp_json_encode(__('Changer l\'image', 'osmose-ads')); ?>);
            $('#create-remove-featured-image').show();
            updateSummary();
        });
        
        createFeaturedImageFrame.open();
    });
    
    $('#create-remove-featured-image').on('click', function(e) {
        e.preventDefault();
        $('#create_featured_image_id').val('');
        $('#create-featured-image-preview').html('<p class="text-muted mb-0">' + <?php echo wp_json_encode(__('Aucune image sélectionnée', 'osmose-ads')); ?> + '</p>');
        $('#create-set-featured-image').text(<?php echo wp_json_encode(__('Choisir une image', 'osmose-ads')); ?>);
        $(this).hide();
        updateSummary();
    });
    
    // Media Library pour les images de réalisations
    var createRealizationImageFrame;
    var realizationImageKeywords = {}; // Stocker les mots-clés par image
    
    $('#create-add-realization-images').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert(<?php echo wp_json_encode(__('La bibliothèque média WordPress n\'est pas disponible. Veuillez recharger la page.', 'osmose-ads')); ?>);
            return;
        }
        
        if (createRealizationImageFrame) {
            createRealizationImageFrame.open();
            return;
        }
        
        createRealizationImageFrame = wp.media({
            title: <?php echo wp_json_encode(__('Ajouter des photos de réalisations', 'osmose-ads')); ?>,
            button: {
                text: <?php echo wp_json_encode(__('Ajouter les images', 'osmose-ads')); ?>
            },
            multiple: true
        });
        
        createRealizationImageFrame.on('select', function() {
            var attachments = createRealizationImageFrame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                var imageId = attachment.id;
                if ($('#create-realization-images-container').find('[data-image-id="' + imageId + '"]').length === 0) {
                    var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    
                    // Récupérer les mots-clés du service
                    var keywords = '';
                    var creationMode = $('.nav-link.active').data('bs-target') === '#preset-panel' ? 'preset' : 'custom';
                    if (creationMode === 'preset') {
                        var selected = $('#preset-service').find(':selected');
                        keywords = selected.data('keywords') || '';
                    } else {
                        keywords = $('#custom-service-keywords').val() || '';
                    }
                    
                    // Stocker les mots-clés pour cette image
                    realizationImageKeywords[imageId] = keywords;
                    
                    var imageHtml = '<div class="position-relative realization-image-wrapper" data-image-id="' + imageId + '" style="width: 200px;">' +
                        '<div class="card">' +
                        '<img src="' + thumbUrl + '" class="card-img-top" style="height: 150px; object-fit: cover;">' +
                        '<div class="card-body p-2">' +
                        '<small class="text-muted d-block mb-1"><strong><?php _e('Mots-clés:', 'osmose-ads'); ?></strong></small>' +
                        '<input type="text" class="form-control form-control-sm realization-keywords-input" data-image-id="' + imageId + '" value="' + (keywords || '') + '" placeholder="<?php _e('Mots-clés pour cette image', 'osmose-ads'); ?>">' +
                        '<button type="button" class="btn btn-sm btn-danger w-100 mt-2 create-remove-image"><i class="bi bi-trash me-1"></i><?php _e('Retirer', 'osmose-ads'); ?></button>' +
                        '<input type="hidden" name="realization_images[]" value="' + imageId + '">' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                    
                    // Supprimer le message "Aucune photo"
                    $('#create-realization-images-container .text-muted:contains("Aucune photo")').parent().remove();
                    $('#create-realization-images-container').append(imageHtml);
                }
            });
            updateSummary();
        });
        
        createRealizationImageFrame.open();
    });
    
    // Supprimer une image de réalisation
    $(document).on('click', '.create-remove-image', function() {
        var imageId = $(this).closest('[data-image-id]').data('image-id');
        $(this).closest('.realization-image-wrapper').remove();
        delete realizationImageKeywords[imageId];
        
        // Afficher le message si plus d'images
        if ($('#create-realization-images-container .realization-image-wrapper').length === 0) {
            $('#create-realization-images-container').html('<div class="text-muted text-center" style="width: 100%; padding: 40px;"><?php _e('Aucune photo ajoutée', 'osmose-ads'); ?></div>');
        }
        updateSummary();
    });
    
    // Mettre à jour les mots-clés quand on change d'onglet ou de service
    $(document).on('change', '#preset-service, #custom-service-keywords', function() {
        var keywords = '';
        var creationMode = $('.nav-link.active').data('bs-target') === '#preset-panel' ? 'preset' : 'custom';
        
        if (creationMode === 'preset') {
            var selected = $('#preset-service').find(':selected');
            keywords = selected.data('keywords') || '';
        } else {
            keywords = $('#custom-service-keywords').val() || '';
        }
        
        // Mettre à jour tous les champs de mots-clés des images
        $('.realization-keywords-input').val(keywords);
        $('.realization-keywords-input').each(function() {
            var imgId = $(this).data('image-id');
            realizationImageKeywords[imgId] = keywords;
        });
    });
    
    // Soumission du formulaire
    $('#create-template-form').on('submit', function(e) {
        e.preventDefault();
        
        var realizationImages = [];
        var realizationKeywords = {};
        
        $('input[name="realization_images[]"]').each(function() {
            var imgId = $(this).val();
            realizationImages.push(imgId);
            
            // Récupérer les mots-clés spécifiques à cette image
            var keywordsInput = $('.realization-keywords-input[data-image-id="' + imgId + '"]');
            if (keywordsInput.length) {
                realizationKeywords[imgId] = keywordsInput.val() || '';
            }
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
            realization_keywords: realizationKeywords,
        };
        
        // Vérifier que osmoseAds est défini
        if (typeof osmoseAds === 'undefined') {
            console.error('Osmose ADS: osmoseAds n\'est pas défini!');
            alert(<?php echo wp_json_encode(__('Erreur: Configuration AJAX non disponible. Veuillez recharger la page.', 'osmose-ads')); ?>);
            return;
        }
        
        $('#template-result').html('<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i><?php echo esc_js(__('Création en cours...', 'osmose-ads')); ?></div>');
        $('button[type="submit"]').prop('disabled', true);
        
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
                            window.location.href = '<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>';
                        }
                    }, 2000);
                } else {
                    $('#template-result').html(
                        '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + response.data.message + '</div>'
                    );
                    $('button[type="submit"]').prop('disabled', false);
                }
            },
            error: function() {
                $('#template-result').html(
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo esc_js(__('Erreur lors de la création', 'osmose-ads')); ?></div>'
                );
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });
    
    // Initialiser le résumé
    updateSummary();
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

