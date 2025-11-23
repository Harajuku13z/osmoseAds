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
            <button type="submit" class="button button-primary"><?php _e('Générer les Annonces', 'osmose-ads'); ?></button>
        </p>
    </form>
    
    <div id="generation-result" style="margin-top: 20px;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#select-all-cities').on('change', function() {
        $('.city-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    $('#bulk-generation-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'osmose_ads_bulk_generate',
            nonce: osmoseAds.nonce,
            service_slug: $('#service-select').val(),
            city_ids: $('.city-checkbox:checked').map(function() {
                return $(this).val();
            }).get(),
        };
        
        if (formData.city_ids.length === 0) {
            alert('<?php _e('Veuillez sélectionner au moins une ville', 'osmose-ads'); ?>');
            return;
        }
        
        $('#generation-result').html('<p><?php _e('Génération en cours...', 'osmose-ads'); ?></p>');
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#generation-result').html(
                        '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                    );
                } else {
                    $('#generation-result').html(
                        '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                    );
                }
            },
            error: function() {
                $('#generation-result').html(
                    '<div class="notice notice-error"><p><?php _e('Erreur lors de la génération', 'osmose-ads'); ?></p></div>'
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

