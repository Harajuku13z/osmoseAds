<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_call_tracking';

// Vérifier que la table existe
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

// Récupérer les statistiques
$stats = array();
$calls_by_page = array();
$calls_by_city = array();
$total_calls = 0;
$calls_today = 0;
$calls_this_week = 0;
$calls_this_month = 0;

if ($table_exists) {
    // Total des appels
    $total_calls = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // Appels aujourd'hui
    $calls_today = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
        current_time('Y-m-d')
    ));
    
    // Appels cette semaine
    $calls_this_week = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(created_at, 1) = YEARWEEK(%s, 1)",
        current_time('mysql')
    ));
    
    // Appels ce mois
    $calls_this_month = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
        current_time('Y'),
        current_time('m')
    ));
    
    // Appels par page
    $calls_by_page = $wpdb->get_results(
        "SELECT page_url, COUNT(*) as count 
         FROM $table_name 
         GROUP BY page_url 
         ORDER BY count DESC 
         LIMIT 20",
        ARRAY_A
    );
    
    // Appels par ville (basé sur l'annonce associée)
    $calls_by_city = $wpdb->get_results(
        "SELECT pm.meta_value as city_id, COUNT(*) as count
         FROM $table_name ct
         INNER JOIN {$wpdb->postmeta} pm ON ct.ad_id = pm.post_id AND pm.meta_key = 'city_id'
         WHERE pm.meta_value IS NOT NULL AND pm.meta_value != ''
         GROUP BY pm.meta_value
         ORDER BY count DESC
         LIMIT 15",
        ARRAY_A
    );
    
    // Derniers appels
    $recent_calls = $wpdb->get_results(
        "SELECT * FROM $table_name 
         ORDER BY created_at DESC 
         LIMIT 50",
        ARRAY_A
    );
} else {
    $recent_calls = array();
}

?>

<div class="osmose-call-stats-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Statistiques d\'Appels', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Suivez les appels générés depuis votre site', 'osmose-ads'); ?></p>
        </div>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php _e('La table de tracking n\'existe pas encore. Elle sera créée automatiquement lors du premier appel.', 'osmose-ads'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques globales -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-primary mb-2"><?php echo number_format_i18n($total_calls); ?></div>
                    <h6 class="text-muted"><?php _e('Total des Appels', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-success mb-2"><?php echo number_format_i18n($calls_today); ?></div>
                    <h6 class="text-muted"><?php _e('Aujourd\'hui', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-info mb-2"><?php echo number_format_i18n($calls_this_week); ?></div>
                    <h6 class="text-muted"><?php _e('Cette Semaine', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-4 text-warning mb-2"><?php echo number_format_i18n($calls_this_month); ?></div>
                    <h6 class="text-muted"><?php _e('Ce Mois', 'osmose-ads'); ?></h6>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Appels par page -->
    <?php if (!empty($calls_by_page)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i><?php _e('Appels par Page', 'osmose-ads'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php _e('Page', 'osmose-ads'); ?></th>
                                <th class="text-center"><?php _e('Nombre d\'Appels', 'osmose-ads'); ?></th>
                                <th class="text-center"><?php _e('Pourcentage', 'osmose-ads'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calls_by_page as $page): 
                                $percentage = $total_calls > 0 ? ($page['count'] / $total_calls) * 100 : 0;
                                $page_url_short = strlen($page['page_url']) > 80 ? substr($page['page_url'], 0, 80) . '...' : $page['page_url'];
                            ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url($page['page_url']); ?>" target="_blank" title="<?php echo esc_attr($page['page_url']); ?>">
                                            <?php echo esc_html($page_url_short); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo number_format_i18n($page['count']); ?></strong>
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

    <!-- Appels par ville -->
    <?php if (!empty($calls_by_city)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i><?php _e('Villes qui génèrent le plus d\'appels', 'osmose-ads'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php _e('Ville', 'osmose-ads'); ?></th>
                                <th><?php _e('Département', 'osmose-ads'); ?></th>
                                <th class="text-center"><?php _e('Appels', 'osmose-ads'); ?></th>
                                <th class="text-center"><?php _e('Pourcentage', 'osmose-ads'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calls_by_city as $city_stat): 
                                $city_id = intval($city_stat['city_id']);
                                $city_post = $city_id ? get_post($city_id) : null;
                                $city_name = $city_post ? $city_post->post_title : __('Ville inconnue', 'osmose-ads');
                                $department = $city_id ? get_post_meta($city_id, 'department_name', true) : '';
                                if (!$department) {
                                    $department = $city_id ? get_post_meta($city_id, 'department', true) : '';
                                }
                                $percentage = $total_calls > 0 ? ($city_stat['count'] / $total_calls) * 100 : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($city_name); ?>
                                        <?php if ($city_post): ?>
                                            <br><small class="text-muted">#<?php echo esc_html($city_id); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($department ?: '—'); ?></td>
                                    <td class="text-center"><strong><?php echo number_format_i18n($city_stat['count']); ?></strong></td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_attr($percentage); ?>%">
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
    
    <!-- Derniers appels -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php _e('Derniers Appels', 'osmose-ads'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($recent_calls)): ?>
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th><?php _e('Date/Heure', 'osmose-ads'); ?></th>
                                <th><?php _e('Page', 'osmose-ads'); ?></th>
                                <th><?php _e('Téléphone', 'osmose-ads'); ?></th>
                                <th><?php _e('Ad ID', 'osmose-ads'); ?></th>
                                <th><?php _e('IP', 'osmose-ads'); ?></th>
                                <th><?php _e('Referrer', 'osmose-ads'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_calls as $call): 
                                $ad = $call['ad_id'] ? get_post($call['ad_id']) : null;
                                $page_url_short = strlen($call['page_url']) > 60 ? substr($call['page_url'], 0, 60) . '...' : $call['page_url'];
                                $referrer_short = $call['referrer'] ? (strlen($call['referrer']) > 40 ? substr($call['referrer'], 0, 40) . '...' : $call['referrer']) : '—';
                            ?>
                                <tr>
                                    <td>
                                        <small><?php echo date_i18n('d/m/Y H:i', strtotime($call['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($call['page_url']); ?>" target="_blank" title="<?php echo esc_attr($call['page_url']); ?>">
                                            <?php echo esc_html($page_url_short); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($call['phone_number'] ?: '—'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($ad): ?>
                                            <a href="<?php echo get_edit_post_link($call['ad_id']); ?>">
                                                #<?php echo esc_html($call['ad_id']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html($call['ad_id'] ?: '—'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo esc_html($call['user_ip'] ?: '—'); ?></small></td>
                                    <td>
                                        <?php if ($call['referrer']): ?>
                                            <a href="<?php echo esc_url($call['referrer']); ?>" target="_blank" title="<?php echo esc_attr($call['referrer']); ?>">
                                                <?php echo esc_html($referrer_short); ?>
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-telephone-x me-2"></i>
                    <?php _e('Aucun appel enregistré pour le moment.', 'osmose-ads'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

