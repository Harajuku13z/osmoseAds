<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

$ads = get_posts(array(
    'post_type' => 'ad',
    'posts_per_page' => 50,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC',
));

// Récupérer les templates disponibles
$templates = get_posts(array(
    'post_type' => 'ad_template',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));

// Récupérer les villes disponibles
$cities = get_posts(array(
    'post_type' => 'city',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));
?>

<div class="osmose-ads-page">
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="text-muted mb-0"><?php _e('Gérez vos annonces géolocalisées', 'osmose-ads'); ?></p>
    </div>
    <div>
        <button type="button" id="create-ads-btn" class="btn btn-primary" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
            <i class="bi bi-plus-circle me-2"></i>
            <?php _e('Créer des Annonces', 'osmose-ads'); ?>
        </button>
    </div>
</div>

<!-- Modal de création d'annonces -->
<div id="create-ads-modal" class="card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 900px; width: 90%; z-index: 1050; box-shadow: 0 0 20px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-plus-circle me-2"></i><?php _e('Créer des Annonces', 'osmose-ads'); ?></h2>
        <button type="button" class="btn-close cancel-create-ads" aria-label="<?php _e('Fermer', 'osmose-ads'); ?>"></button>
    </div>
    <div class="card-body">
        <form id="create-ads-form">
            <div class="mb-4">
                <label class="form-label fw-bold"><?php _e('Sélectionner un Template', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                <select name="template_id" id="ads-template-select" class="form-select" required>
                    <option value=""><?php _e('-- Choisir un template --', 'osmose-ads'); ?></option>
                    <?php foreach ($templates as $template): 
                        $service_name = get_post_meta($template->ID, 'service_name', true) ?: $template->post_title;
                    ?>
                        <option value="<?php echo esc_attr($template->ID); ?>">
                            <?php echo esc_html($template->post_title . ($service_name !== $template->post_title ? ' (' . $service_name . ')' : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted"><?php _e('Choisissez le template de service à utiliser pour générer les annonces', 'osmose-ads'); ?></small>
                <?php if (empty($templates)): ?>
                    <div class="alert alert-warning mt-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php _e('Aucun template disponible. Créez d\'abord un template dans la section Templates.', 'osmose-ads'); ?>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-template-create'); ?>" class="alert-link"><?php _e('Créer un template', 'osmose-ads'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold"><?php _e('Sélectionner les Villes', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #f9f9f9;">
                    <label class="mb-3 d-block">
                        <input type="checkbox" id="select-all-cities-ads" class="me-2">
                        <strong><?php _e('Tout sélectionner', 'osmose-ads'); ?></strong>
                    </label>
                    <hr class="my-2">
                    <?php if (!empty($cities)): ?>
                        <div id="cities-list-ads">
                            <?php foreach ($cities as $city): 
                                $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
                                $department = get_post_meta($city->ID, 'department', true);
                                $department_name = get_post_meta($city->ID, 'department_name', true);
                            ?>
                                <label class="d-block mb-2" style="cursor: pointer; padding: 5px; border-radius: 3px; transition: background 0.2s;">
                                    <input type="checkbox" name="city_ids[]" value="<?php echo esc_attr($city->ID); ?>" class="city-checkbox-ads me-2">
                                    <span>
                                        <?php echo esc_html($city_name); ?>
                                        <?php if ($department): ?>
                                            <span class="text-muted">(<?php echo esc_html($department . ($department_name ? ' - ' . $department_name : '')); ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php _e('Aucune ville disponible. Ajoutez des villes dans la section Villes.', 'osmose-ads'); ?>
                            <a href="<?php echo admin_url('admin.php?page=osmose-ads-cities'); ?>" class="alert-link"><?php _e('Ajouter des villes', 'osmose-ads'); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
                <small class="form-text text-muted d-block mt-2">
                    <span id="selected-cities-count">0</span> <?php _e('ville(s) sélectionnée(s)', 'osmose-ads'); ?>
                </small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="generate-ads-btn">
                    <i class="bi bi-magic me-1"></i>
                    <?php _e('Générer les Annonces', 'osmose-ads'); ?>
                </button>
                <button type="button" class="btn btn-secondary cancel-create-ads">
                    <?php _e('Annuler', 'osmose-ads'); ?>
                </button>
            </div>
        </form>
        
        <div id="create-ads-result" class="mt-4"></div>
    </div>
</div>

<div id="osmose-ads-modal-backdrop-ads" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040;"></div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Titre', 'osmose-ads'); ?></th>
                <th><?php _e('Ville', 'osmose-ads'); ?></th>
                <th><?php _e('Template', 'osmose-ads'); ?></th>
                <th><?php _e('Statut', 'osmose-ads'); ?></th>
                <th><?php _e('Date', 'osmose-ads'); ?></th>
                <th><?php _e('Actions', 'osmose-ads'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ads)): ?>
                <?php foreach ($ads as $ad): 
                    $city_id = get_post_meta($ad->ID, 'city_id', true);
                    $template_id = get_post_meta($ad->ID, 'template_id', true);
                    $city = $city_id ? get_post($city_id) : null;
                    $template = $template_id ? get_post($template_id) : null;
                    $status = get_post_meta($ad->ID, 'status', true);
                    if (!$status) {
                        $status = $ad->post_status === 'publish' ? 'published' : 'draft';
                    }
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($ad->post_title); ?></strong></td>
                        <td><?php echo $city ? esc_html($city->post_title) : '—'; ?></td>
                        <td><?php echo $template ? esc_html($template->post_title) : '—'; ?></td>
                        <td>
                            <?php 
                            $status_labels = array(
                                'published' => __('Publié', 'osmose-ads'),
                                'draft' => __('Brouillon', 'osmose-ads'),
                                'archived' => __('Archivé', 'osmose-ads'),
                            );
                            echo esc_html($status_labels[$status] ?? $status);
                            ?>
                        </td>
                        <td><?php echo esc_html(get_the_date('d/m/Y', $ad->ID)); ?></td>
                        <td>
                            <a href="<?php echo get_permalink($ad->ID); ?>" target="_blank"><?php _e('Voir', 'osmose-ads'); ?></a> |
                            <a href="<?php echo get_edit_post_link($ad->ID); ?>"><?php _e('Modifier', 'osmose-ads'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6"><?php _e('Aucune annonce trouvée', 'osmose-ads'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
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
    var $createBtn = $('#create-ads-btn');
    var $modal = $('#create-ads-modal');
    var $backdrop = $('#osmose-ads-modal-backdrop-ads');
    
    // Ouvrir le modal
    $createBtn.on('click', function(e) {
        e.preventDefault();
        $modal.show();
        $backdrop.show();
    });
    
    // Fermer le modal
    $('.cancel-create-ads').on('click', function() {
        $modal.hide();
        $backdrop.hide();
        $('#create-ads-result').html('');
        $('#create-ads-form')[0].reset();
        $('#selected-cities-count').text('0');
    });
    
    // Fermer avec le backdrop
    $backdrop.on('click', function() {
        $('.cancel-create-ads').trigger('click');
    });
    
    // Sélectionner/Désélectionner toutes les villes
    $('#select-all-cities-ads').on('change', function() {
        $('.city-checkbox-ads').prop('checked', $(this).prop('checked'));
        updateCitiesCount();
    });
    
    // Mettre à jour le compteur de villes sélectionnées
    function updateCitiesCount() {
        var count = $('.city-checkbox-ads:checked').length;
        $('#selected-cities-count').text(count);
    }
    
    $('.city-checkbox-ads').on('change', updateCitiesCount);
    
    // Soumettre le formulaire
    $('#create-ads-form').on('submit', function(e) {
        e.preventDefault();
        
        var templateId = $('#ads-template-select').val();
        var cityIds = $('.city-checkbox-ads:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!templateId) {
            alert(<?php echo wp_json_encode(__('Veuillez sélectionner un template', 'osmose-ads')); ?>);
            return;
        }
        
        if (cityIds.length === 0) {
            alert(<?php echo wp_json_encode(__('Veuillez sélectionner au moins une ville', 'osmose-ads')); ?>);
            return;
        }
        
        // Récupérer le service_slug du template
        var templateOption = $('#ads-template-select option:selected');
        var serviceName = templateOption.text().trim();
        
        // Extraire le service_slug depuis le template
        var serviceSlug = '';
        <?php 
        // Créer un mapping des template IDs vers service slugs
        $template_slugs = array();
        foreach ($templates as $t) {
            $slug = get_post_meta($t->ID, 'service_slug', true);
            if (!$slug) {
                $service_name = get_post_meta($t->ID, 'service_name', true);
                $slug = $service_name ? sanitize_title($service_name) : $t->post_name;
            }
            $template_slugs[$t->ID] = $slug;
        }
        ?>
        var templateSlugs = <?php echo json_encode($template_slugs); ?>;
        serviceSlug = templateSlugs[templateId] || '';
        
        if (!serviceSlug) {
            alert(<?php echo wp_json_encode(__('Erreur: Impossible de déterminer le service', 'osmose-ads')); ?>);
            return;
        }
        
        var formData = {
            action: 'osmose_ads_bulk_generate',
            nonce: osmoseAds.nonce,
            service_slug: serviceSlug,
            city_ids: cityIds,
            template_id: templateId // Ajouter le template_id pour référence
        };
        
        $('#create-ads-result').html('<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i><?php echo esc_js(__('Génération en cours...', 'osmose-ads')); ?></div>');
        $('#generate-ads-btn').prop('disabled', true);
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#create-ads-result').html(
                        '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + response.data.message + '</div>'
                    );
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#create-ads-result').html(
                        '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + response.data.message + '</div>'
                    );
                    $('#generate-ads-btn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                $('#create-ads-result').html(
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo esc_js(__('Erreur lors de la génération', 'osmose-ads')); ?></div>'
                );
                $('#generate-ads-btn').prop('disabled', false);
            }
        });
    });
});
</script>

<style>
.city-checkbox-ads:hover + span,
label:hover .city-checkbox-ads + span {
    background: #f0f0f0;
}

#cities-list-ads label:hover {
    background: #f0f0f0;
}

#create-ads-modal .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#create-ads-modal .btn-close {
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    border: 0;
    border-radius: .375rem;
    opacity: .5;
    padding: .5rem .5rem;
}
</style>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

