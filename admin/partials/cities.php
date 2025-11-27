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

// Définir osmoseAds IMMÉDIATEMENT après le header, avant tout autre script
?>
<script>
// Définir osmoseAds IMMÉDIATEMENT pour garantir qu'il est disponible
window.osmoseAds = {
    ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo wp_create_nonce('osmose_ads_nonce'); ?>',
    plugin_url: '<?php echo esc_url(OSMOSE_ADS_PLUGIN_URL); ?>'
};
console.log('Osmose ADS: osmoseAds defined at top of cities.php:', window.osmoseAds);
</script>
<?php

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

// Traitement suppression de toutes les villes
if (isset($_POST['delete_all_cities']) && wp_verify_nonce($_POST['delete_all_nonce'], 'osmose_ads_delete_all_cities')) {
    global $wpdb;
    
    // Récupérer tous les IDs des villes
    $city_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
        'city'
    ));
    
    $deleted = 0;
    if (!empty($city_ids)) {
        // Désactiver les hooks pour accélérer
        remove_action('before_delete_post', '_wp_delete_post_menu_item');
        
        foreach ($city_ids as $city_id) {
            if (wp_delete_post($city_id, true)) {
                $deleted++;
            }
        }
    }
    
    $delete_message = sprintf(__('%d ville(s) supprimée(s) avec succès', 'osmose-ads'), $deleted);
    $delete_success = true;
}

// Traitement import en masse (optimisé)
if (isset($_POST['import_communes']) && wp_verify_nonce($_POST['import_nonce'], 'osmose_ads_import_communes')) {
    $communes_json = wp_unslash($_POST['communes_json'] ?? '');
    $import_type = sanitize_text_field($_POST['import_type'] ?? '');
    
    if (!empty($communes_json)) {
        $communes = json_decode($communes_json, true);
        
        if (is_array($communes) && !empty($communes)) {
            if (!class_exists('France_Geo_API')) {
                require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
            }
            
            // OPTIMISATION : Récupérer tous les codes INSEE existants en une seule requête
            global $wpdb;
            $existing_codes = $wpdb->get_col($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = %s AND p.post_status = 'publish' AND pm.meta_key = 'insee_code'",
                'city'
            ));
            $existing_codes = array_flip($existing_codes); // Pour recherche O(1)
            
            // Désactiver les hooks pour accélérer l'import
            remove_action('post_updated', 'wp_save_post_revision');
            add_filter('wp_insert_post_data', function($data) {
                $data['post_modified'] = current_time('mysql');
                $data['post_modified_gmt'] = current_time('mysql', 1);
                return $data;
            });
            
            // Désactiver le cache de requête pendant l'import
            wp_suspend_cache_addition(true);
            
            $geo_api = new France_Geo_API();
            $imported = 0;
            $skipped = 0;
            $batch_size = 50; // Traiter par lots
            
            // Préparer toutes les données d'abord
            $to_import = array();
            foreach ($communes as $commune) {
                $normalized = $geo_api->normalize_commune_data($commune);
                
                if (empty($normalized['name']) || empty($normalized['code'])) {
                    $skipped++;
                    continue;
                }
                
                // Vérification rapide avec tableau
                if (isset($existing_codes[$normalized['code']])) {
                    $skipped++;
                    continue;
                }
                
                $to_import[] = $normalized;
                // Mettre à jour le tableau des codes existants pour éviter les doublons dans le même import
                $existing_codes[$normalized['code']] = true;
            }
            
            // Import par lots
            foreach (array_chunk($to_import, $batch_size) as $batch) {
                foreach ($batch as $normalized) {
                $city_id = wp_insert_post(array(
                    'post_title' => $normalized['name'],
                    'post_type' => 'city',
                    'post_status' => 'publish',
                ), true); // true = wp_error en cas d'erreur
                    
                    if ($city_id && !is_wp_error($city_id)) {
                        // Préparer les meta
                        $meta_data = array(
                            'name' => $normalized['name'],
                            'insee_code' => $normalized['code'],
                            'postal_code' => $normalized['postal_code'],
                            'all_postal_codes' => $normalized['all_postal_codes'] ?? $normalized['postal_code'],
                            'department' => $normalized['department'],
                            'department_name' => $normalized['department_name'] ?? '',
                            'region' => $normalized['region'],
                            'region_name' => $normalized['region_name'] ?? '',
                            'population' => intval($normalized['population'] ?? 0),
                            'surface' => floatval($normalized['surface'] ?? 0),
                        );
                        
                        if (isset($normalized['latitude'])) {
                            $meta_data['latitude'] = floatval($normalized['latitude']);
                        }
                        if (isset($normalized['longitude'])) {
                            $meta_data['longitude'] = floatval($normalized['longitude']);
                        }
                        
                        // OPTIMISATION : Insérer toutes les meta via SQL direct pour plus de rapidité
                        $meta_values = array();
                        foreach ($meta_data as $key => $value) {
                            if ($value !== '' && $value !== null) {
                                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $city_id, $key, maybe_serialize($value));
                            }
                        }
                        
                        if (!empty($meta_values)) {
                            $sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $meta_values);
                            $wpdb->query($sql);
                        }
                        
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }
            }
            
            // Réactiver le cache
            wp_suspend_cache_addition(false);
            
            // Message de succès
            $import_message = sprintf(
                __('%d ville(s) importée(s), %d ignorée(s) (déjà existantes)', 'osmose-ads'),
                $imported,
                $skipped
            );
            $import_success = true;
        }
    }
}

// Rafraîchir les noms de départements/régions manquants
if (isset($_POST['refresh_city_names']) && wp_verify_nonce($_POST['refresh_city_names_nonce'], 'osmose_ads_refresh_city_names')) {
    if (!class_exists('France_Geo_API')) {
        require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-france-geo-api.php';
    }

    $geo_api = new France_Geo_API();
    $departments = $geo_api->get_departments();
    $regions = $geo_api->get_regions();

    if (is_wp_error($departments) || is_wp_error($regions)) {
        $refresh_error = __('Impossible de contacter l\'API pour récupérer les listes de départements/régions. Veuillez réessayer.', 'osmose-ads');
    } else {
        $dept_map = array();
        foreach ($departments as $dept) {
            if (!empty($dept['code'])) {
                $dept_map[$dept['code']] = $dept['nom'] ?? '';
            }
        }

        $region_map = array();
        foreach ($regions as $region) {
            if (!empty($region['code'])) {
                $region_map[$region['code']] = $region['nom'] ?? '';
            }
        }

        $city_ids = get_posts(array(
            'post_type' => 'city',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));

        $updated_departments = 0;
        $updated_regions = 0;

        if (!empty($city_ids)) {
            foreach ($city_ids as $city_id) {
                $dept_code = get_post_meta($city_id, 'department', true);
                $dept_name = get_post_meta($city_id, 'department_name', true);
                if (!empty($dept_code) && empty($dept_name) && isset($dept_map[$dept_code]) && !empty($dept_map[$dept_code])) {
                    update_post_meta($city_id, 'department_name', $dept_map[$dept_code]);
                    $updated_departments++;
                }

                $region_code = get_post_meta($city_id, 'region', true);
                $region_name = get_post_meta($city_id, 'region_name', true);
                if (!empty($region_code) && empty($region_name) && isset($region_map[$region_code]) && !empty($region_map[$region_code])) {
                    update_post_meta($city_id, 'region_name', $region_map[$region_code]);
                    $updated_regions++;
                }
            }
        }

        $refresh_message = sprintf(
            __('Mise à jour terminée : %d nom(s) de département et %d nom(s) de région complétés.', 'osmose-ads'),
            $updated_departments,
            $updated_regions
        );
    }
}

// Afficher les messages
if (isset($import_success) && $import_success) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($import_message) . '</p></div>';
}
if (isset($delete_success) && $delete_success) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($delete_message) . '</p></div>';
}
if (!empty($refresh_message)) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($refresh_message) . '</p></div>';
}
if (!empty($refresh_error)) {
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($refresh_error) . '</p></div>';
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
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    <?php _e('Liste des Villes', 'osmose-ads'); ?>
                    <span class="badge bg-primary ms-2"><?php echo count($cities); ?></span>
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($cities)): ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer TOUTES les villes ? Cette action est irréversible !', 'osmose-ads'); ?>');">
                            <?php wp_nonce_field('osmose_ads_delete_all_cities', 'delete_all_nonce'); ?>
                            <input type="hidden" name="delete_all_cities" value="1">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>
                                <?php _e('Supprimer toutes les villes', 'osmose-ads'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('osmose_ads_refresh_city_names', 'refresh_city_names_nonce'); ?>
                        <input type="hidden" name="refresh_city_names" value="1">
                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="<?php esc_attr_e('Compléter les noms de départements/régions manquants via l’API officielle', 'osmose-ads'); ?>">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            <?php _e('Mettre à jour les noms', 'osmose-ads'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
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
                                    <td>
                                        <?php
                                        $dept_code = $city_meta['department'][0] ?? '';
                                        $dept_name = $city_meta['department_name'][0] ?? '';
                                        if ($dept_name && $dept_code) {
                                            echo esc_html($dept_name . ' (' . $dept_code . ')');
                                        } elseif ($dept_name) {
                                            echo esc_html($dept_name);
                                        } elseif ($dept_code) {
                                            echo esc_html($dept_code);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $region_code = $city_meta['region'][0] ?? '';
                                        $region_name = $city_meta['region_name'][0] ?? '';
                                        if ($region_name && $region_code) {
                                            echo esc_html($region_name . ' (' . $region_code . ')');
                                        } elseif ($region_name) {
                                            echo esc_html($region_name);
                                        } elseif ($region_code) {
                                            echo esc_html($region_code);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
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
            <div id="import-result" class="mt-4">
                <?php if (isset($import_success) && $import_success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong><?php echo esc_html($import_message); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Nonce caché pour la sécurité -->
            <input type="hidden" id="import_nonce" name="import_nonce" value="<?php echo wp_create_nonce('osmose_ads_import_communes'); ?>">
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

