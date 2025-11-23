<?php
if (!defined('ABSPATH')) {
    exit;
}

// Chemin du logo
$logo_paths = array(
    OSMOSE_ADS_PLUGIN_DIR . '../logo.jpg',
    OSMOSE_ADS_PLUGIN_DIR . 'img/logo.jpg',
    ABSPATH . 'logo.jpg'
);

$logo_url = '';
foreach ($logo_paths as $path) {
    if (file_exists($path)) {
        $logo_url = str_replace(ABSPATH, home_url('/'), $path);
        break;
    }
}

if (empty($logo_url)) {
    $logo_url = OSMOSE_ADS_PLUGIN_URL . '../logo.jpg';
}

// Traitement formulaire simple
if (isset($_POST['add_city'])) {
    $city_name = sanitize_text_field($_POST['city_name'] ?? '');
    $postal_code = sanitize_text_field($_POST['postal_code'] ?? '');
    $department = sanitize_text_field($_POST['department'] ?? '');
    $region = sanitize_text_field($_POST['region'] ?? '');
    $population = intval($_POST['population'] ?? 0);
    
    if (!empty($city_name)) {
        $city_id = wp_insert_post(array(
            'post_title' => $city_name,
            'post_type' => 'city',
            'post_status' => 'publish',
        ));
        
        if ($city_id) {
            update_post_meta($city_id, 'name', $city_name);
            update_post_meta($city_id, 'postal_code', $postal_code);
            update_post_meta($city_id, 'department', $department);
            update_post_meta($city_id, 'region', $region);
            update_post_meta($city_id, 'population', $population);
        }
    }
}

$cities = get_posts(array(
    'post_type' => 'city',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));
?>

<div class="osmose-ads-page">
    <!-- Header avec logo -->
    <div class="osmose-ads-header">
        <div class="osmose-ads-header-content">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Osmose" class="osmose-ads-logo">
            <?php endif; ?>
            <div>
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p class="description"><?php _e('Gérez vos villes et importez en masse via l\'API officielle française', 'osmose-ads'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="osmose-ads-container">
        
        <!-- Onglets -->
        <div class="osmose-ads-tabs">
            <button class="osmose-tab-btn active" data-tab="import">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Import en Masse', 'osmose-ads'); ?>
            </button>
            <button class="osmose-tab-btn" data-tab="manual">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Ajout Manuel', 'osmose-ads'); ?>
            </button>
            <button class="osmose-tab-btn" data-tab="list">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('Liste des Villes', 'osmose-ads'); ?>
                <span class="badge"><?php echo count($cities); ?></span>
            </button>
        </div>
        
        <!-- Contenu des onglets -->
        
        <!-- Onglet Import en Masse -->
        <div class="osmose-tab-content active" id="tab-import">
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Import en Masse via API Officielle', 'osmose-ads'); ?>
                </h2>
                <p class="description">
                    <?php _e('Importez des villes depuis l\'API géographique officielle de la France (data.gouv.fr)', 'osmose-ads'); ?>
                </p>
                
                <!-- Sous-onglets pour les méthodes d'import -->
                <div class="osmose-ads-subtabs">
                    <button class="osmose-subtab-btn active" data-subtab="department">
                        <?php _e('Par Département', 'osmose-ads'); ?>
                    </button>
                    <button class="osmose-subtab-btn" data-subtab="region">
                        <?php _e('Par Région', 'osmose-ads'); ?>
                    </button>
                    <button class="osmose-subtab-btn" data-subtab="distance">
                        <?php _e('Par Rayon', 'osmose-ads'); ?>
                    </button>
                </div>
                
                <!-- Import par Département -->
                <div class="osmose-subtab-content active" id="subtab-department">
                    <form id="import-department-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="department_code"><?php _e('Département', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <select id="department_code" name="department_code" class="regular-text" required>
                                        <option value=""><?php _e('Chargement...', 'osmose-ads'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Sélectionnez un département pour importer toutes ses communes', 'osmose-ads'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="osmose-btn osmose-btn-primary">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Importer les Villes', 'osmose-ads'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Import par Région -->
                <div class="osmose-subtab-content" id="subtab-region">
                    <form id="import-region-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="region_code"><?php _e('Région', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <select id="region_code" name="region_code" class="regular-text" required>
                                        <option value=""><?php _e('Chargement...', 'osmose-ads'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Sélectionnez une région pour importer toutes ses communes', 'osmose-ads'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="osmose-btn osmose-btn-primary">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Importer les Villes', 'osmose-ads'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Import par Rayon -->
                <div class="osmose-subtab-content" id="subtab-distance">
                    <form id="import-distance-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="city_search"><?php _e('Ville de Référence', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="city_search" 
                                           name="city_search" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Rechercher une ville...', 'osmose-ads'); ?>"
                                           required>
                                    <div id="city-search-results" style="margin-top: 10px;"></div>
                                    <input type="hidden" id="city_code" name="city_code">
                                    <p class="description"><?php _e('Recherchez une ville pour servir de point de départ', 'osmose-ads'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="distance_km"><?php _e('Rayon (km)', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="distance_km" 
                                           name="distance_km" 
                                           value="10" 
                                           min="1" 
                                           max="100" 
                                           class="small-text"
                                           required>
                                    <p class="description"><?php _e('Rayon de recherche autour de la ville (1-100 km)', 'osmose-ads'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="osmose-btn osmose-btn-primary">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Importer les Villes', 'osmose-ads'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Résultat de l'import -->
                <div id="import-result" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <!-- Onglet Ajout Manuel -->
        <div class="osmose-tab-content" id="tab-manual">
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Ajouter une Ville Manuellement', 'osmose-ads'); ?>
                </h2>
                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Nom de la Ville', 'osmose-ads'); ?></th>
                            <td><input type="text" name="city_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Code Postal', 'osmose-ads'); ?></th>
                            <td><input type="text" name="postal_code" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Département', 'osmose-ads'); ?></th>
                            <td><input type="text" name="department" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Région', 'osmose-ads'); ?></th>
                            <td><input type="text" name="region" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Population', 'osmose-ads'); ?></th>
                            <td><input type="number" name="population" class="regular-text"></td>
                        </tr>
                    </table>
                    <?php submit_button(__('Ajouter la Ville', 'osmose-ads'), 'primary', 'add_city', false, array('class' => 'osmose-btn osmose-btn-primary')); ?>
                </form>
            </div>
        </div>
        
        <!-- Onglet Liste -->
        <div class="osmose-tab-content" id="tab-list">
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('Liste des Villes', 'osmose-ads'); ?>
                    <span class="badge"><?php echo count($cities); ?></span>
                </h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Nom', 'osmose-ads'); ?></th>
                            <th><?php _e('Code Postal', 'osmose-ads'); ?></th>
                            <th><?php _e('Département', 'osmose-ads'); ?></th>
                            <th><?php _e('Région', 'osmose-ads'); ?></th>
                            <th><?php _e('Population', 'osmose-ads'); ?></th>
                            <th><?php _e('Actions', 'osmose-ads'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cities)): ?>
                            <?php foreach ($cities as $city): 
                                $name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
                                $postal_code = get_post_meta($city->ID, 'postal_code', true);
                                $department = get_post_meta($city->ID, 'department_name', true) ?: get_post_meta($city->ID, 'department', true);
                                $region = get_post_meta($city->ID, 'region_name', true) ?: get_post_meta($city->ID, 'region', true);
                                $population = get_post_meta($city->ID, 'population', true);
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($name); ?></strong></td>
                                    <td><?php echo esc_html($postal_code); ?></td>
                                    <td><?php echo esc_html($department); ?></td>
                                    <td><?php echo esc_html($region); ?></td>
                                    <td><?php echo $population ? number_format_i18n($population) : '—'; ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($city->ID); ?>"><?php _e('Modifier', 'osmose-ads'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6"><?php _e('Aucune ville trouvée', 'osmose-ads'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<style>
/* Onglets */
.osmose-ads-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    border-bottom: 2px solid #e2e8f0;
}

.osmose-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    bottom: -2px;
}

.osmose-tab-btn:hover {
    color: #3b82f6;
}

.osmose-tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.osmose-tab-btn .badge {
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.osmose-tab-content {
    display: none;
}

.osmose-tab-content.active {
    display: block;
}

/* Sous-onglets */
.osmose-ads-subtabs {
    display: flex;
    gap: 5px;
    margin: 20px 0;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
}

.osmose-subtab-btn {
    padding: 8px 16px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.osmose-subtab-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.osmose-subtab-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.osmose-subtab-content {
    display: none;
}

.osmose-subtab-content.active {
    display: block;
}

/* Résultats de recherche ville */
#city-search-results {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    display: none;
}

.city-search-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
}

.city-search-item:hover {
    background: #f8fafc;
}

.city-search-item:last-child {
    border-bottom: none;
}

.city-search-item strong {
    color: #1e3a5f;
}

.city-search-item small {
    color: #64748b;
    display: block;
    margin-top: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.osmose-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.osmose-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.osmose-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Gestion des sous-onglets
    $('.osmose-subtab-btn').on('click', function() {
        var subtab = $(this).data('subtab');
        
        $('.osmose-subtab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.osmose-subtab-content').removeClass('active');
        $('#subtab-' + subtab).addClass('active');
    });
    
    // Charger les départements
    $.ajax({
        url: osmoseAds.ajax_url,
        type: 'POST',
        data: {
            action: 'osmose_ads_get_departments',
            nonce: osmoseAds.nonce
        },
        success: function(response) {
            if (response.success) {
                var select = $('#department_code');
                select.html('<option value=""><?php _e('-- Sélectionner un département --', 'osmose-ads'); ?></option>');
                $.each(response.data, function(i, dept) {
                    select.append('<option value="' + dept.code + '">' + dept.nom + ' (' + dept.code + ')</option>');
                });
            }
        }
    });
    
    // Charger les régions
    $.ajax({
        url: osmoseAds.ajax_url,
        type: 'POST',
        data: {
            action: 'osmose_ads_get_regions',
            nonce: osmoseAds.nonce
        },
        success: function(response) {
            if (response.success) {
                var select = $('#region_code');
                select.html('<option value=""><?php _e('-- Sélectionner une région --', 'osmose-ads'); ?></option>');
                $.each(response.data, function(i, region) {
                    select.append('<option value="' + region.code + '">' + region.nom + '</option>');
                });
            }
        }
    });
    
    // Recherche de ville
    var searchTimeout;
    $('#city_search').on('input', function() {
        clearTimeout(searchTimeout);
        var search = $(this).val();
        
        if (search.length < 3) {
            $('#city-search-results').hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: osmoseAds.ajax_url,
                type: 'POST',
                data: {
                    action: 'osmose_ads_search_city',
                    nonce: osmoseAds.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var results = $('#city-search-results');
                        results.empty();
                        
                        $.each(response.data.slice(0, 5), function(i, city) {
                            var postalCode = city.codesPostaux ? city.codesPostaux[0] : '';
                            var dept = city.codeDepartement || '';
                            results.append(
                                '<div class="city-search-item" data-code="' + city.code + '">' +
                                '<strong>' + city.nom + '</strong>' +
                                '<small>' + postalCode + ' - ' + dept + '</small>' +
                                '</div>'
                            );
                        });
                        
                        results.show();
                    } else {
                        $('#city-search-results').hide().empty();
                    }
                }
            });
        }, 500);
    });
    
    // Sélection d'une ville dans les résultats
    $(document).on('click', '.city-search-item', function() {
        var code = $(this).data('code');
        var name = $(this).find('strong').text();
        
        $('#city_code').val(code);
        $('#city_search').val(name);
        $('#city-search-results').hide();
    });
    
    // Import par département
    $('#import-department-form').on('submit', function(e) {
        e.preventDefault();
        importCities('department', {
            department_code: $('#department_code').val()
        });
    });
    
    // Import par région
    $('#import-region-form').on('submit', function(e) {
        e.preventDefault();
        importCities('region', {
            region_code: $('#region_code').val()
        });
    });
    
    // Import par rayon
    $('#import-distance-form').on('submit', function(e) {
        e.preventDefault();
        importCities('distance', {
            city_code: $('#city_code').val(),
            distance: $('#distance_km').val()
        });
    });
    
    function importCities(type, data) {
        $('#import-result').html('<p><?php _e('Import en cours...', 'osmose-ads'); ?></p>');
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            data: {
                action: 'osmose_ads_import_cities',
                nonce: osmoseAds.nonce,
                import_type: type,
                ...data
            },
            success: function(response) {
                if (response.success) {
                    $('#import-result').html(
                        '<div class="osmose-ads-card" style="border-left: 4px solid #10b981;">' +
                        '<p style="color: #10b981; font-weight: 600;">' +
                        '<span class="dashicons dashicons-yes-alt"></span> ' +
                        response.data.message +
                        '</p>' +
                        '</div>'
                    );
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#import-result').html(
                        '<div class="osmose-ads-card" style="border-left: 4px solid #ef4444;">' +
                        '<p style="color: #ef4444; font-weight: 600;">' +
                        '<span class="dashicons dashicons-dismiss"></span> ' +
                        response.data.message +
                        '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#import-result').html(
                    '<div class="osmose-ads-card" style="border-left: 4px solid #ef4444;">' +
                    '<p style="color: #ef4444; font-weight: 600;"><?php _e('Erreur lors de l\'import', 'osmose-ads'); ?></p>' +
                    '</div>'
                );
            }
        });
    }
});
</script>
