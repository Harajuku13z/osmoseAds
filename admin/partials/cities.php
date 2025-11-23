<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

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

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="text-muted mb-0"><?php _e('Gérez vos villes et importez en masse via l\'API officielle française', 'osmose-ads'); ?></p>
    </div>
</div>

<!-- Section 1: Liste des Villes avec scroll interne -->
<div class="row mb-4">
    <div class="col-12">
        <div class="osmose-ads-card">
            <h2 class="mb-3">
                <i class="bi bi-list-ul me-2"></i>
                <?php _e('Liste des Villes', 'osmose-ads'); ?>
                <span class="badge bg-primary ms-2"><?php echo count($cities); ?></span>
            </h2>
            
            <div class="cities-scroll-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                <?php if (!empty($cities)): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
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
                                        <a href="<?php echo get_edit_post_link($city->ID); ?>" class="btn btn-sm btn-outline-primary">
                                            <?php _e('Modifier', 'osmose-ads'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info m-3" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <?php _e('Aucune ville trouvée. Utilisez la section "Import en Masse" pour importer des villes via l\'API officielle française.', 'osmose-ads'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Import en Masse -->
<div class="row mb-4">
    <div class="col-12">
        <div class="osmose-ads-card">
            <h2 class="mb-3">
                <i class="bi bi-download me-2"></i>
                <?php _e('Import en Masse via API Officielle', 'osmose-ads'); ?>
            </h2>
            <p class="text-muted mb-4">
                <?php _e('Importez des villes depuis l\'API géographique officielle de la France (geo.api.gouv.fr)', 'osmose-ads'); ?>
            </p>
            
            <!-- Sous-sections pour les méthodes d'import -->
            <div class="accordion" id="importMethodsAccordion">
                <!-- Import par Département -->
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDepartment" aria-expanded="true" aria-controls="collapseDepartment">
                            <i class="bi bi-geo-alt me-2"></i>
                            <?php _e('Import par Département', 'osmose-ads'); ?>
                        </button>
                    </h3>
                    <div id="collapseDepartment" class="accordion-collapse collapse show" data-bs-parent="#importMethodsAccordion">
                        <div class="accordion-body">
                            <form id="import-department-form">
                                <div class="mb-3">
                                    <label for="department_code" class="form-label"><?php _e('Département', 'osmose-ads'); ?></label>
                                    <select id="department_code" name="department_code" class="form-select" required>
                                        <option value=""><?php _e('Chargement...', 'osmose-ads'); ?></option>
                                    </select>
                                    <div class="form-text"><?php _e('Sélectionnez un département pour importer toutes ses communes', 'osmose-ads'); ?></div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>
                                    <?php _e('Importer les Villes', 'osmose-ads'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Import par Région -->
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegion" aria-expanded="false" aria-controls="collapseRegion">
                            <i class="bi bi-map me-2"></i>
                            <?php _e('Import par Région', 'osmose-ads'); ?>
                        </button>
                    </h3>
                    <div id="collapseRegion" class="accordion-collapse collapse" data-bs-parent="#importMethodsAccordion">
                        <div class="accordion-body">
                            <form id="import-region-form">
                                <div class="mb-3">
                                    <label for="region_code" class="form-label"><?php _e('Région', 'osmose-ads'); ?></label>
                                    <select id="region_code" name="region_code" class="form-select" required>
                                        <option value=""><?php _e('Chargement...', 'osmose-ads'); ?></option>
                                    </select>
                                    <div class="form-text"><?php _e('Sélectionnez une région pour importer toutes ses communes', 'osmose-ads'); ?></div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>
                                    <?php _e('Importer les Villes', 'osmose-ads'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Import par Rayon -->
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDistance" aria-expanded="false" aria-controls="collapseDistance">
                            <i class="bi bi-geo me-2"></i>
                            <?php _e('Import par Rayon', 'osmose-ads'); ?>
                        </button>
                    </h3>
                    <div id="collapseDistance" class="accordion-collapse collapse" data-bs-parent="#importMethodsAccordion">
                        <div class="accordion-body">
                            <form id="import-distance-form">
                                <div class="mb-3">
                                    <label for="city_search" class="form-label"><?php _e('Ville de Référence', 'osmose-ads'); ?></label>
                                    <input type="text" 
                                           id="city_search" 
                                           name="city_search" 
                                           class="form-control" 
                                           placeholder="<?php _e('Rechercher une ville...', 'osmose-ads'); ?>"
                                           required>
                                    <div id="city-search-results" class="mt-2"></div>
                                    <input type="hidden" id="city_code" name="city_code">
                                    <div class="form-text"><?php _e('Recherchez une ville pour servir de point de départ', 'osmose-ads'); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="distance_km" class="form-label"><?php _e('Rayon (km)', 'osmose-ads'); ?></label>
                                    <input type="number" 
                                           id="distance_km" 
                                           name="distance_km" 
                                           value="10" 
                                           min="1" 
                                           max="100" 
                                           class="form-control"
                                           required>
                                    <div class="form-text"><?php _e('Rayon de recherche autour de la ville (1-100 km)', 'osmose-ads'); ?></div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>
                                    <?php _e('Importer les Villes', 'osmose-ads'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Résultat de l'import -->
            <div id="import-result" class="mt-4"></div>
        </div>
    </div>
</div>

<!-- Section 3: Ajout Manuel -->
<div class="row mb-4">
    <div class="col-12">
        <div class="osmose-ads-card">
            <h2 class="mb-3">
                <i class="bi bi-plus-circle me-2"></i>
                <?php _e('Ajouter une Ville Manuellement', 'osmose-ads'); ?>
            </h2>
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city_name" class="form-label"><?php _e('Nom de la Ville', 'osmose-ads'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="city_name" id="city_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="postal_code" class="form-label"><?php _e('Code Postal', 'osmose-ads'); ?></label>
                        <input type="text" name="postal_code" id="postal_code" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="department" class="form-label"><?php _e('Département', 'osmose-ads'); ?></label>
                        <input type="text" name="department" id="department" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="region" class="form-label"><?php _e('Région', 'osmose-ads'); ?></label>
                        <input type="text" name="region" id="region" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="population" class="form-label"><?php _e('Population', 'osmose-ads'); ?></label>
                        <input type="number" name="population" id="population" class="form-control">
                    </div>
                </div>
                <button type="submit" name="add_city" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    <?php _e('Ajouter la Ville', 'osmose-ads'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Scroll personnalisé pour la liste des villes */
.cities-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #f1f5f9;
}

.cities-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.cities-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 8px;
}

.cities-scroll-container::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 8px;
}

.cities-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #2563eb;
}

/* Sticky header dans le scroll */
.cities-scroll-container thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
}

/* Styles pour les cartes */
.osmose-ads-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 30px;
    border: 1px solid #e2e8f0;
}

.osmose-ads-card h2 {
    color: #1e3a5f;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #3b82f6;
    display: flex;
    align-items: center;
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
    transition: background 0.2s;
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
    // Fonction pour s'assurer que osmoseAds est disponible
    function ensureOsmoseAds() {
        if (typeof osmoseAds === 'undefined') {
            window.osmoseAds = {
                ajax_url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                nonce: '<?php echo esc_js(wp_create_nonce('osmose_ads_nonce')); ?>'
            };
            console.log('Osmose ADS: Created osmoseAds object', window.osmoseAds);
        }
        return window.osmoseAds;
    }
    
    // Attendre que tout soit prêt
    var osmoseAds = ensureOsmoseAds();
    console.log('Osmose ADS: Starting to load departments and regions...', osmoseAds);
    
    // Charger les départements
    function loadDepartments() {
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'osmose_ads_get_departments',
                nonce: osmoseAds.nonce
            },
            success: function(response) {
                console.log('Departments response:', response);
                var select = $('#department_code');
                
                if (response && response.success && Array.isArray(response.data) && response.data.length > 0) {
                    select.empty();
                    select.append('<option value=""><?php _e('-- Sélectionner un département --', 'osmose-ads'); ?></option>');
                    $.each(response.data, function(i, dept) {
                        if (dept && dept.code && dept.nom) {
                            select.append('<option value="' + dept.code + '">' + dept.nom + ' (' + dept.code + ')</option>');
                        }
                    });
                    console.log('Departments loaded successfully:', response.data.length, 'items');
                } else {
                    console.error('Departments error - invalid response:', response);
                    select.html('<option value=""><?php _e('Erreur lors du chargement des départements', 'osmose-ads'); ?></option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading departments:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status,
                    readyState: xhr.readyState
                });
                $('#department_code').html('<option value=""><?php _e('Erreur de connexion - Vérifiez la console', 'osmose-ads'); ?></option>');
            }
        });
    }
    
    // Charger les régions
    function loadRegions() {
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'osmose_ads_get_regions',
                nonce: osmoseAds.nonce
            },
            success: function(response) {
                console.log('Regions response:', response);
                var select = $('#region_code');
                
                if (response && response.success && Array.isArray(response.data) && response.data.length > 0) {
                    select.empty();
                    select.append('<option value=""><?php _e('-- Sélectionner une région --', 'osmose-ads'); ?></option>');
                    $.each(response.data, function(i, region) {
                        if (region && region.code && region.nom) {
                            select.append('<option value="' + region.code + '">' + region.nom + '</option>');
                        }
                    });
                    console.log('Regions loaded successfully:', response.data.length, 'items');
                } else {
                    console.error('Regions error - invalid response:', response);
                    select.html('<option value=""><?php _e('Erreur lors du chargement des régions', 'osmose-ads'); ?></option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading regions:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status,
                    readyState: xhr.readyState
                });
                $('#region_code').html('<option value=""><?php _e('Erreur de connexion - Vérifiez la console', 'osmose-ads'); ?></option>');
            }
        });
    }
    
    // Charger les données avec un délai pour s'assurer que les selects existent
    setTimeout(function() {
        if ($('#department_code').length) {
            loadDepartments();
        } else {
            console.error('Department select not found!');
        }
        
        if ($('#region_code').length) {
            loadRegions();
        } else {
            console.error('Region select not found!');
        }
    }, 200);
    
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
        var resultDiv = $('#import-result');
        resultDiv.html(
            '<div class="alert alert-info">' +
            '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
            '<?php _e('Import en cours, veuillez patienter...', 'osmose-ads'); ?>' +
            '</div>'
        );
        
        // Désactiver le bouton pendant l'import
        $('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: osmoseAds.ajax_url,
            type: 'POST',
            timeout: 300000,
            data: {
                action: 'osmose_ads_import_cities',
                nonce: osmoseAds.nonce,
                import_type: type,
                ...data
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false);
                
                if (response.success) {
                    var imported = response.data.imported || 0;
                    var skipped = response.data.skipped || 0;
                    var total = response.data.total || 0;
                    
                    resultDiv.html(
                        '<div class="alert alert-success">' +
                        '<i class="bi bi-check-circle me-2"></i>' +
                        '<strong>' + response.data.message + '</strong><br>' +
                        '<small>Total trouvé: ' + total + ' | ' +
                        'Importées: ' + imported + ' | ' +
                        'Ignorées (déjà existantes): ' + skipped + '</small>' +
                        '</div>'
                    );
                    
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    resultDiv.html(
                        '<div class="alert alert-danger">' +
                        '<i class="bi bi-exclamation-triangle me-2"></i>' +
                        (response.data && response.data.message ? response.data.message : '<?php _e('Erreur lors de l\'import', 'osmose-ads'); ?>') +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                
                var errorMsg = '<?php _e('Erreur lors de l\'import', 'osmose-ads'); ?>';
                if (status === 'timeout') {
                    errorMsg = '<?php _e('Le délai d\'import a été dépassé. L\'import peut continuer en arrière-plan.', 'osmose-ads'); ?>';
                }
                
                resultDiv.html(
                    '<div class="alert alert-danger">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>' +
                    errorMsg +
                    '</div>'
                );
            }
        });
    }
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>
