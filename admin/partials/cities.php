<?php
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que les constantes sont définies
if (!defined('OSMOSE_ADS_PLUGIN_DIR')) {
    wp_die(__('Erreur: Les constantes du plugin ne sont pas définies. Veuillez réactiver le plugin.', 'osmose-ads'));
}

// Nettoyer l'URL si des paramètres GET non désirés sont présents
if (isset($_GET['city_search']) || isset($_GET['city_code']) || isset($_GET['distance_km'])) {
    wp_safe_redirect(admin_url('admin.php?page=osmose-ads-cities'));
    exit;
}

// Inclure le header global
if (!defined('OSMOSE_ADS_HEADER_LOADED')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';
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
                                $city_meta = get_post_meta($city->ID);
                            ?>
                                <tr>
                                    <td><?php echo esc_html($city->post_title); ?></td>
                                    <td><?php echo esc_html($city_meta['postal_code'][0] ?? '-'); ?></td>
                                    <td><?php echo esc_html($city_meta['department'][0] ?? '-'); ?></td>
                                    <td><?php echo esc_html($city_meta['region'][0] ?? '-'); ?></td>
                                    <td><?php echo esc_html($city_meta['population'][0] ?? '-'); ?></td>
                                    <td>
                                        <a href="<?php echo get_delete_post_link($city->ID); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette ville ?', 'osmose-ads'); ?>');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <?php _e('Aucune ville importée pour le moment.', 'osmose-ads'); ?>
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
                <?php _e('Import en Masse', 'osmose-ads'); ?>
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
                                    <select id="department_code" name="department_code" class="form-select" required disabled>
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
                                    <select id="region_code" name="region_code" class="form-select" required disabled>
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
                        <input type="text" id="city_name" name="city_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="postal_code" class="form-label"><?php _e('Code Postal', 'osmose-ads'); ?></label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label"><?php _e('Département', 'osmose-ads'); ?></label>
                        <input type="text" id="department" name="department" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="region" class="form-label"><?php _e('Région', 'osmose-ads'); ?></label>
                        <input type="text" id="region" name="region" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="population" class="form-label"><?php _e('Population', 'osmose-ads'); ?></label>
                        <input type="number" id="population" name="population" class="form-control" min="0">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_city" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            <?php _e('Ajouter la Ville', 'osmose-ads'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.city-search-item {
    padding: 10px;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.city-search-item:hover {
    background-color: #f8f9fa;
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

#city-search-results {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    max-height: 300px;
    overflow-y: auto;
}
</style>

<!-- S'assurer que osmoseAds est défini AVANT le chargement du script externe -->
<script>
// Définir osmoseAds immédiatement dans le template pour garantir qu'il est disponible
window.osmoseAds = window.osmoseAds || {
    ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo wp_create_nonce('osmose_ads_nonce'); ?>',
    plugin_url: '<?php echo esc_url(OSMOSE_ADS_PLUGIN_URL); ?>'
};
console.log('Osmose ADS: osmoseAds defined in cities.php template:', window.osmoseAds);

// Vérification supplémentaire
if (!window.osmoseAds.ajax_url) {
    console.error('Osmose ADS: CRITICAL ERROR - ajax_url is missing!');
} else {
    console.log('Osmose ADS: ajax_url is set:', window.osmoseAds.ajax_url);
}

if (!window.osmoseAds.nonce) {
    console.warn('Osmose ADS: WARNING - nonce is missing!');
} else {
    console.log('Osmose ADS: nonce is set:', window.osmoseAds.nonce.substring(0, 10) + '...');
}
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

