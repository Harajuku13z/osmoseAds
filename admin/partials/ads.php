<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Pagination
$per_page = 50;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$ads_query = new WP_Query(array(
    'post_type'      => 'ad',
    'posts_per_page' => $per_page,
    'post_status'    => 'any',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => $current_page,
));

$ads = $ads_query->posts;

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

// Construire la liste des départements disponibles à partir des villes
$departments = array();
if (!empty($cities)) {
    foreach ($cities as $city) {
        $dept_code = get_post_meta($city->ID, 'department', true);
        $dept_name = get_post_meta($city->ID, 'department_name', true);
        if (!empty($dept_code)) {
            if (!isset($departments[$dept_code])) {
                $departments[$dept_code] = $dept_name ?: $dept_code;
            }
        }
    }
    ksort($departments);
}
?>

<div class="osmose-ads-page">
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="text-muted mb-0"><?php _e('Gérez vos annonces géolocalisées', 'osmose-ads'); ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" id="create-ads-btn" class="btn btn-primary" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;">
            <i class="bi bi-plus-circle me-2"></i>
            <?php _e('Créer des Annonces', 'osmose-ads'); ?>
        </button>
        <?php if (!empty($ads)): ?>
            <button type="button" id="delete-all-ads-btn" class="btn btn-danger">
                <i class="bi bi-trash me-2"></i>
                <?php _e('Supprimer toutes les annonces', 'osmose-ads'); ?>
            </button>
        <?php endif; ?>
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

                <?php if (!empty($departments)): ?>
                    <div class="mb-3">
                        <label class="form-label"><?php _e('Filtrer / sélectionner par département (optionnel)', 'osmose-ads'); ?></label>
                        <div style="max-height: 120px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #fff;">
                            <?php foreach ($departments as $dept_code => $dept_label): ?>
                                <label class="d-block mb-1" style="cursor: pointer;">
                                    <input type="checkbox" class="department-checkbox-ads me-2" data-department="<?php echo esc_attr($dept_code); ?>">
                                    <span><?php echo esc_html($dept_code . ' - ' . $dept_label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-text text-muted">
                            <?php _e('Cocher un ou plusieurs départements pour sélectionner automatiquement toutes les villes associées. Utilisez un département à la fois pour éviter les erreurs.', 'osmose-ads'); ?>
                        </small>
                    </div>
                <?php endif; ?>

                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #f9f9f9;">
                    <label class="mb-3 d-block">
                        <input type="checkbox" id="select-all-cities-ads" class="me-2">
                        <strong><?php _e('Tout sélectionner (villes visibles)', 'osmose-ads'); ?></strong>
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
                                    <input
                                        type="checkbox"
                                        name="city_ids[]"
                                        value="<?php echo esc_attr($city->ID); ?>"
                                        class="city-checkbox-ads me-2"
                                        data-department="<?php echo esc_attr($department); ?>"
                                    >
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
        
        <!-- Barre de progression pour la génération -->
        <div id="ads-generation-progress" style="display:none; margin-top:20px; padding:15px; background:#f0f0f1; border-radius:4px;">
            <div style="width:100%; height:24px; background:#ddd; border-radius:4px; overflow:hidden; margin-bottom:8px;">
                <div id="ads-generation-progress-bar" style="width:0%; height:100%; background:#2271b1; transition:width 0.3s;"></div>
            </div>
            <p id="ads-generation-progress-text" style="margin:0; font-size:13px; color:#444;"></p>
        </div>
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
                            <a href="<?php echo get_edit_post_link($ad->ID); ?>"><?php _e('Modifier', 'osmose-ads'); ?></a> |
                            <a href="#" class="osmose-delete-ad-link" data-ad-id="<?php echo esc_attr($ad->ID); ?>" style="color: #d63638;">
                                <?php _e('Supprimer', 'osmose-ads'); ?>
                            </a>
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

    <?php if ($ads_query->max_num_pages > 1): ?>
        <div class="tablenav bottom" style="margin-top: 15px;">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'current'   => $current_page,
                    'total'     => $ads_query->max_num_pages,
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
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
    var nonce = osmoseAds.nonce;
    
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

    // Suppression d'une annonce individuelle
    $('.osmose-delete-ad-link').on('click', function(e) {
        e.preventDefault();
        var adId = $(this).data('ad-id');
        if (!adId) return;

        if (!confirm('<?php echo esc_js(__('Confirmez-vous la suppression définitive de cette annonce ?', 'osmose-ads')); ?>')) {
            return;
        }

        $.post(
            osmoseAds.ajax_url,
            {
                action: 'osmose_ads_delete_ad',
                nonce: nonce,
                ad_id: adId
            },
            function(response) {
                if (response && response.success) {
                    location.reload();
                } else {
                    alert(response && response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Erreur lors de la suppression', 'osmose-ads')); ?>');
                }
            }
        );
    });

    // Suppression de toutes les annonces
    $('#delete-all-ads-btn').on('click', function(e) {
        e.preventDefault();

        if (!confirm('<?php echo esc_js(__('ATTENTION : ceci va supprimer TOUTES les annonces générées. Confirmez-vous ?', 'osmose-ads')); ?>')) {
            return;
        }

        $.post(
            osmoseAds.ajax_url,
            {
                action: 'osmose_ads_delete_all_ads',
                nonce: nonce
            },
            function(response) {
                if (response && response.success) {
                    alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Toutes les annonces ont été supprimées', 'osmose-ads')); ?>');
                    location.reload();
                } else {
                    alert(response && response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Erreur lors de la suppression des annonces', 'osmose-ads')); ?>');
                }
            }
        );
    });
    
    // Sélectionner/Désélectionner toutes les villes
    $('#select-all-cities-ads').on('change', function() {
        $('.city-checkbox-ads').prop('checked', $(this).prop('checked'));
        updateCitiesCount();
    });

    // Sélection par département : coche / décoche toutes les villes du département
    $('.department-checkbox-ads').on('change', function() {
        var deptCode = $(this).data('department');
        var isChecked = $(this).is(':checked');

        if (!deptCode) {
            return;
        }

        $('.city-checkbox-ads').each(function() {
            var cityDept = $(this).data('department');
            if (cityDept == deptCode) {
                $(this).prop('checked', isChecked);
            }
        });

        // Si on décoche au moins un département, on enlève aussi le "tout sélectionner"
        if (!isChecked) {
            $('#select-all-cities-ads').prop('checked', false);
        }

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
        
        // Traitement par lots pour éviter les timeouts
        var total = cityIds.length;
        var processed = 0;
        var batchSize = 50; // nombre de villes traitées par requête
        var totalCreated = 0;
        var totalSkipped = 0;
        var totalErrors = 0;
        var isAborted = false;
        var allCityIds = cityIds.slice(); // copie pour pouvoir modifier

        $('#create-ads-result').empty();
        $('#ads-generation-progress').show();
        $('#ads-generation-progress-bar').css('width', '0%');
        $('#ads-generation-progress-text').text('<?php echo esc_js(__('Initialisation de la génération...', 'osmose-ads')); ?>');
        $('#generate-ads-btn').prop('disabled', true);

        function updateProgress() {
            var percent = total > 0 ? Math.round((processed / total) * 100) : 0;
            if (percent > 100) percent = 100;
            $('#ads-generation-progress-bar').css('width', percent + '%');
            $('#ads-generation-progress-text').text(
                percent + '% - ' +
                '<?php echo esc_js(__('Traitées :', 'osmose-ads')); ?> ' + processed + '/' + total +
                ' | <?php echo esc_js(__('Créées', 'osmose-ads')); ?> : ' + totalCreated +
                ' | <?php echo esc_js(__('Ignorées', 'osmose-ads')); ?> : ' + totalSkipped +
                ' | <?php echo esc_js(__('Erreurs', 'osmose-ads')); ?> : ' + totalErrors
            );
        }

        function processNextBatch() {
            if (isAborted) {
                return;
            }

            if (allCityIds.length === 0) {
                // Terminé
                updateProgress();
                $('#generate-ads-btn').prop('disabled', false);

                var finalMessage = '<?php echo esc_js(__('Génération terminée.', 'osmose-ads')); ?> ' +
                    '<?php echo esc_js(__('Créées :', 'osmose-ads')); ?> ' + totalCreated +
                    ' | <?php echo esc_js(__('Ignorées :', 'osmose-ads')); ?> ' + totalSkipped +
                    ' | <?php echo esc_js(__('Erreurs :', 'osmose-ads')); ?> ' + totalErrors;

                $('#create-ads-result').html(
                    '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + finalMessage + '</div>'
                );
                
                setTimeout(function() {
                    location.reload();
                }, 2000);
                return;
            }

            var batch = allCityIds.splice(0, batchSize);

            $.ajax({
                url: osmoseAds.ajax_url,
                type: 'POST',
                data: {
                    action: 'osmose_ads_bulk_generate',
                    nonce: osmoseAds.nonce,
                    service_slug: serviceSlug,
                    city_ids: batch,
                    template_id: templateId
                },
                success: function(response) {
                    if (response && response.success && response.data) {
                        totalCreated += parseInt(response.data.created || 0, 10);
                        totalSkipped += parseInt(response.data.skipped || 0, 10);
                        totalErrors += parseInt(response.data.errors || 0, 10);
                    } else {
                        // si erreur pour ce lot, on compte toutes les villes du lot comme erreurs
                        totalErrors += batch.length;
                    }
                    processed += batch.length;
                    updateProgress();
                    // Traiter le lot suivant
                    processNextBatch();
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX pour un lot:', error);
                    totalErrors += batch.length;
                    processed += batch.length;
                    updateProgress();

                    // On affiche une erreur mais on continue les lots suivants
                    $('#create-ads-result').html(
                        '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?php echo esc_js(__('Erreur lors de la génération pour un lot de villes. La génération continue pour les lots suivants.', 'osmose-ads')); ?></div>'
                    );
                    processNextBatch();
                }
            });
        }

        // Lancer le premier lot
        updateProgress();
        processNextBatch();
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

