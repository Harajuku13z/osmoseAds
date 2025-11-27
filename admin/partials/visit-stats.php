<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_visits';

// Vérifier que la table existe
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

// Récupérer les statistiques
$total_visits = 0;
$visits_today = 0;
$visits_this_week = 0;
$visits_this_month = 0;
$visits_by_ad = array();
$visits_by_referrer = array();
$visits_by_device = array();
$visits_by_browser = array();
$recent_visits = array();

// Filtrer par annonce si demandé
$filter_ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

if ($table_exists) {
    // Total des visites
    if ($filter_ad_id > 0) {
        $total_visits = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d",
            $filter_ad_id
        ));
    } else {
        $total_visits = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    // Visites aujourd'hui
    if ($filter_ad_id > 0) {
        $visits_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s AND ad_id = %d",
            current_time('Y-m-d'),
            $filter_ad_id
        ));
    } else {
        $visits_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(visit_date) = %s",
            current_time('Y-m-d')
        ));
    }
    
    // Visites cette semaine
    if ($filter_ad_id > 0) {
        $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1) AND ad_id = %d",
            current_time('Y-m-d'),
            $filter_ad_id
        ));
    } else {
        $visits_this_week = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(visit_date, 1) = YEARWEEK(%s, 1)",
            current_time('Y-m-d')
        ));
    }
    
    // Visites ce mois
    if ($filter_ad_id > 0) {
        $visits_this_month = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d AND ad_id = %d",
            current_time('Y'),
            current_time('m'),
            $filter_ad_id
        ));
    } else {
        $visits_this_month = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEAR(visit_date) = %d AND MONTH(visit_date) = %d",
            current_time('Y'),
            current_time('m')
        ));
    }
    
    // Visites par annonce
    $visits_by_ad = $wpdb->get_results(
        "SELECT ad_id, ad_slug, city_name, COUNT(*) as count 
         FROM $table_name 
         GROUP BY ad_id, ad_slug, city_name 
         ORDER BY count DESC 
         LIMIT 20",
        ARRAY_A
    );
    
    // Visites par referrer (domaine)
    $visits_by_referrer = $wpdb->get_results(
        "SELECT referrer_domain, COUNT(*) as count 
         FROM $table_name 
         WHERE referrer_domain IS NOT NULL AND referrer_domain != ''
         GROUP BY referrer_domain 
         ORDER BY count DESC 
         LIMIT 20",
        ARRAY_A
    );
    
    // Visites par type d'appareil
    $visits_by_device = $wpdb->get_results(
        "SELECT device_type, COUNT(*) as count 
         FROM $table_name 
         WHERE device_type IS NOT NULL
         GROUP BY device_type 
         ORDER BY count DESC",
        ARRAY_A
    );
    
    // Visites par navigateur
    $visits_by_browser = $wpdb->get_results(
        "SELECT browser, COUNT(*) as count 
         FROM $table_name 
         WHERE browser IS NOT NULL
         GROUP BY browser 
         ORDER BY count DESC",
        ARRAY_A
    );
    
    // Dernières visites
    $recent_visits = $wpdb->get_results(
        "SELECT * FROM $table_name 
         ORDER BY visit_time DESC 
         LIMIT 100",
        ARRAY_A
    );
}

?>

<div class="osmose-visit-stats-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Statistiques de Visites', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Analysez les visites de vos annonces et leur provenance', 'osmose-ads'); ?></p>
        </div>
        <?php if ($filter_ad_id > 0): ?>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-visits'); ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i><?php _e('Retirer le filtre', 'osmose-ads'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php _e('La table de tracking des visites n\'existe pas encore. Elle sera créée automatiquement lors de la première visite.', 'osmose-ads'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques globales -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-primary mb-2"><?php echo number_format_i18n($total_visits); ?></div>
                    <h6 class="text-muted"><?php _e('Total des Visites', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-success mb-2"><?php echo number_format_i18n($visits_today); ?></div>
                    <h6 class="text-muted"><?php _e('Aujourd\'hui', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-info mb-2"><?php echo number_format_i18n($visits_this_week); ?></div>
                    <h6 class="text-muted"><?php _e('Cette Semaine', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-warning mb-2"><?php echo number_format_i18n($visits_this_month); ?></div>
                    <h6 class="text-muted"><?php _e('Ce Mois', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Colonne gauche -->
        <div class="col-lg-8">
            <!-- Visites par annonce -->
            <?php if (!empty($visits_by_ad)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i><?php _e('Visites par Annonce', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php _e('Annonce', 'osmose-ads'); ?></th>
                                        <th><?php _e('Ville', 'osmose-ads'); ?></th>
                                        <th class="text-center"><?php _e('Visites', 'osmose-ads'); ?></th>
                                        <th class="text-center"><?php _e('Pourcentage', 'osmose-ads'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visits_by_ad as $ad_visit): 
                                        $percentage = $total_visits > 0 ? ($ad_visit['count'] / $total_visits) * 100 : 0;
                                        $ad = $ad_visit['ad_id'] ? get_post($ad_visit['ad_id']) : null;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($ad): ?>
                                                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-visits&ad_id=' . $ad_visit['ad_id']); ?>">
                                                        <?php echo esc_html($ad->post_title); ?>
                                                    </a>
                                                    <br><small class="text-muted">#<?php echo esc_html($ad_visit['ad_id']); ?></small>
                                                <?php else: ?>
                                                    <?php echo esc_html($ad_visit['ad_slug'] ?: 'Annonce #' . $ad_visit['ad_id']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($ad_visit['city_name'] ?: '—'); ?></td>
                                            <td class="text-center">
                                                <strong><?php echo number_format_i18n($ad_visit['count']); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%">
                                                        <?php echo number_format_i18n($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Dernières visites -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php _e('Dernières Visites', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_visits)): ?>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th><?php _e('Date/Heure', 'osmose-ads'); ?></th>
                                        <th><?php _e('Annonce', 'osmose-ads'); ?></th>
                                        <th><?php _e('Ville', 'osmose-ads'); ?></th>
                                        <th><?php _e('Provenance', 'osmose-ads'); ?></th>
                                        <th><?php _e('Appareil', 'osmose-ads'); ?></th>
                                        <th><?php _e('Navigateur', 'osmose-ads'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_visits as $visit): 
                                        $ad = $visit['ad_id'] ? get_post($visit['ad_id']) : null;
                                        $referrer_domain_short = $visit['referrer_domain'] ? (strlen($visit['referrer_domain']) > 30 ? substr($visit['referrer_domain'], 0, 30) . '...' : $visit['referrer_domain']) : 'Direct';
                                    ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date_i18n('d/m/Y H:i', strtotime($visit['visit_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($ad): ?>
                                                    <a href="<?php echo get_permalink($visit['ad_id']); ?>" target="_blank">
                                                        <?php echo esc_html(strlen($ad->post_title) > 40 ? substr($ad->post_title, 0, 40) . '...' : $ad->post_title); ?>
                                                    </a>
                                                    <br><small class="text-muted">#<?php echo esc_html($visit['ad_id']); ?></small>
                                                <?php else: ?>
                                                    <?php echo esc_html($visit['ad_slug'] ?: 'Annonce #' . $visit['ad_id']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($visit['city_name'] ?: '—'); ?></td>
                                            <td>
                                                <?php if ($visit['referrer_domain']): ?>
                                                    <a href="<?php echo esc_url($visit['referrer']); ?>" target="_blank" title="<?php echo esc_attr($visit['referrer']); ?>">
                                                        <?php echo esc_html($referrer_domain_short); ?>
                                                    </a>
                                                    <?php if ($visit['utm_source']): ?>
                                                        <br><small class="text-muted">UTM: <?php echo esc_html($visit['utm_source']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php _e('Direct', 'osmose-ads'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo esc_html($visit['device_type'] ?: '—'); ?></small></td>
                                            <td><small><?php echo esc_html($visit['browser'] ?: '—'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-eye-slash me-2"></i>
                            <?php _e('Aucune visite enregistrée pour le moment.', 'osmose-ads'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Colonne droite -->
        <div class="col-lg-4">
            <!-- Visites par provenance -->
            <?php if (!empty($visits_by_referrer)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i><?php _e('Provenance', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($visits_by_referrer as $referrer): 
                                $percentage = $total_visits > 0 ? ($referrer['count'] / $total_visits) * 100 : 0;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong><?php echo esc_html($referrer['referrer_domain']); ?></strong>
                                        <br><small class="text-muted"><?php echo number_format_i18n($referrer['count']); ?> visites</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo number_format_i18n($percentage, 1); ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Visites par appareil -->
            <?php if (!empty($visits_by_device)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-device-hdd me-2"></i><?php _e('Type d\'Appareil', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($visits_by_device as $device): 
                            $percentage = $total_visits > 0 ? ($device['count'] / $total_visits) * 100 : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo esc_html($device['device_type']); ?></span>
                                    <span class="text-muted"><?php echo number_format_i18n($device['count']); ?> (<?php echo number_format_i18n($percentage, 1); ?>%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Visites par navigateur -->
            <?php if (!empty($visits_by_browser)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-browser-chrome me-2"></i><?php _e('Navigateur', 'osmose-ads'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($visits_by_browser as $browser): 
                            $percentage = $total_visits > 0 ? ($browser['count'] / $total_visits) * 100 : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo esc_html($browser['browser']); ?></span>
                                    <span class="text-muted"><?php echo number_format_i18n($browser['count']); ?> (<?php echo number_format_i18n($percentage, 1); ?>%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

