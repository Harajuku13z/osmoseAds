<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Récupérer les services
$services = get_option('osmose_ads_services', array());

// Récupérer les villes
$cities = get_posts(array(
    'post_type' => 'city',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));

// Récupérer les templates existants
$templates = get_posts(array(
    'post_type' => 'ad_template',
    'posts_per_page' => -1,
    'post_status' => 'publish',
));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
</div>
    
    <form id="bulk-generation-form">
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Service', 'osmose-ads'); ?></th>
                <td>
                    <select id="service-select" name="service_slug" required>
                        <option value=""><?php _e('-- Sélectionner un service --', 'osmose-ads'); ?></option>
                        <?php foreach ($services as $service): 
                            $slug = sanitize_title($service);
                        ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($service); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Le template sera créé automatiquement si nécessaire', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Villes', 'osmose-ads'); ?></th>
                <td>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                        <label>
                            <input type="checkbox" id="select-all-cities">
                            <strong><?php _e('Tout sélectionner', 'osmose-ads'); ?></strong>
                        </label>
                        <hr>
                        <?php if (!empty($cities)): ?>
                            <?php foreach ($cities as $city): 
                                $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
                            ?>
                                <label style="display: block; padding: 5px 0;">
                                    <input type="checkbox" name="city_ids[]" value="<?php echo esc_attr($city->ID); ?>" class="city-checkbox">
                                    <?php echo esc_html($city_name); ?>
                                    <?php 
                                    $department = get_post_meta($city->ID, 'department', true);
                                    if ($department) {
                                        echo ' (' . esc_html($department) . ')';
                                    }
                                    ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php _e('Aucune ville trouvée. Ajoutez des villes dans la section "Villes".', 'osmose-ads'); ?></p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary" id="bulk-generate-submit"><?php _e('Générer les Annonces', 'osmose-ads'); ?></button>
        </p>
    </form>
    
    <div id="generation-progress" style="margin-top: 20px; display: none;">
        <div style="margin-bottom:8px;">
            <strong><?php _e('Progression de la génération', 'osmose-ads'); ?></strong>
        </div>
        <div style="width:100%; background:#f1f1f1; border-radius:4px; overflow:hidden; height:18px; margin-bottom:8px;">
            <div id="generation-progress-bar" style="width:0%; height:100%; background:#2271b1; transition:width 0.3s;"></div>
        </div>
        <p id="generation-progress-text" style="margin:0; font-size:13px; color:#444;"></p>
    </div>

    <div id="generation-result" style="margin-top: 20px;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#select-all-cities').on('change', function() {
        $('.city-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    $('#bulk-generation-form').on('submit', function(e) {
        e.preventDefault();
        
        var serviceSlug = $('#service-select').val();
        var allCityIds = $('.city-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!serviceSlug) {
            alert('<?php _e('Veuillez sélectionner un service', 'osmose-ads'); ?>');
            return;
        }
        
        if (allCityIds.length === 0) {
            alert('<?php _e('Veuillez sélectionner au moins une ville', 'osmose-ads'); ?>');
            return;
        }

        var total = allCityIds.length;
        var processed = 0;
        var batchSize = 50; // nombre de villes traitées par requête
        var totalCreated = 0;
        var totalSkipped = 0;
        var totalErrors = 0;
        var isAborted = false;

        $('#generation-result').empty();
        $('#generation-progress').show();
        $('#generation-progress-bar').css('width', '0%');
        $('#generation-progress-text').text('<?php _e('Initialisation de la génération...', 'osmose-ads'); ?>');
        $('#bulk-generate-submit').prop('disabled', true);

        function updateProgress() {
            var percent = total > 0 ? Math.round((processed / total) * 100) : 0;
            if (percent > 100) percent = 100;
            $('#generation-progress-bar').css('width', percent + '%');
            $('#generation-progress-text').text(
                percent + '% - ' +
                '<?php _e('Traitées :', 'osmose-ads'); ?> ' + processed + '/' + total +
                ' | <?php _e('Créées', 'osmose-ads'); ?> : ' + totalCreated +
                ' | <?php _e('Ignorées', 'osmose-ads'); ?> : ' + totalSkipped +
                ' | <?php _e('Erreurs', 'osmose-ads'); ?> : ' + totalErrors
            );
        }

        function processNextBatch() {
            if (isAborted) {
                return;
            }

            if (allCityIds.length === 0) {
                // Terminé
                updateProgress();
                $('#bulk-generate-submit').prop('disabled', false);

                var finalMessage = '<?php _e('Génération terminée.', 'osmose-ads'); ?> ' +
                    '<?php _e('Créées :', 'osmose-ads'); ?> ' + totalCreated +
                    ' | <?php _e('Ignorées :', 'osmose-ads'); ?> ' + totalSkipped +
                    ' | <?php _e('Erreurs :', 'osmose-ads'); ?> ' + totalErrors;

                $('#generation-result').html(
                    '<div class="notice notice-success"><p>' + finalMessage + '</p></div>'
                );
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
                    city_ids: batch
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
                error: function() {
                    totalErrors += batch.length;
                    processed += batch.length;
                    updateProgress();

                    // On affiche une erreur mais on continue les lots suivants
                    $('#generation-result').html(
                        '<div class="notice notice-error"><p><?php _e('Erreur lors de la génération pour un lot de villes. La génération continue pour les lots suivants.', 'osmose-ads'); ?></p></div>'
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

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

