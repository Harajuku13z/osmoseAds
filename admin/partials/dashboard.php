<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Vérifier si la configuration est terminée
$setup_completed = get_option('osmose_ads_setup_completed', false);

// Statistiques
$templates_count = wp_count_posts('ad_template');
$ads_count = wp_count_posts('ad');
$cities_count = wp_count_posts('city');

// Statistiques d'appels
global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
$total_calls = 0;
$calls_today = 0;
$calls_this_week = 0;
$calls_this_month = 0;

if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $total_calls = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $calls_today = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
        current_time('Y-m-d')
    ));
    $calls_this_week = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(created_at, 1) = YEARWEEK(%s, 1)",
        current_time('mysql')
    ));
    $calls_this_month = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
        current_time('Y'),
        current_time('m')
    ));
}

// Récupérer les annonces avec numéros de suivi
$ads_with_tracking = get_posts(array(
    'post_type' => 'ad',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'tracking_number',
            'compare' => 'EXISTS'
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="text-muted mb-0"><?php _e('Gérez vos annonces géolocalisées en toute simplicité', 'osmose-ads'); ?></p>
    </div>
</div>
        <?php if (!$setup_completed): ?>
            <div class="osmose-ads-card" style="border-left: 4px solid #f59e0b;">
                <h2 style="color: #f59e0b; border-bottom-color: #fbbf24;">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Configuration requise', 'osmose-ads'); ?>
                </h2>
                <p><?php _e('Veuillez compléter la configuration initiale pour utiliser l\'extension.', 'osmose-ads'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=osmose-ads-setup'); ?>" class="osmose-btn osmose-btn-primary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Commencer la Configuration', 'osmose-ads'); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="osmose-ads-dashboard">
            <!-- Statistiques -->
            <div class="osmose-ads-stats">
                <div class="osmose-ads-card stat-box">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($templates_count->publish ?? 0); ?></h3>
                        <p><?php _e('Templates', 'osmose-ads'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="osmose-btn osmose-btn-outline">
                            <?php _e('Voir les Templates', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="osmose-ads-card stat-box">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2563eb, #1e40af);">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($ads_count->publish ?? 0); ?></h3>
                        <p><?php _e('Annonces Publiées', 'osmose-ads'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-ads'); ?>" class="osmose-btn osmose-btn-outline">
                            <?php _e('Voir les Annonces', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="osmose-ads-card stat-box">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1e40af, #1e3a5f);">
                        <span class="dashicons dashicons-location"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($cities_count->publish ?? 0); ?></h3>
                        <p><?php _e('Villes', 'osmose-ads'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-cities'); ?>" class="osmose-btn osmose-btn-outline">
                            <?php _e('Gérer les Villes', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="osmose-ads-card stat-box">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <span class="dashicons dashicons-phone"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($total_calls); ?></h3>
                        <p><?php _e('Appels Trackés', 'osmose-ads'); ?></p>
                        <small style="display: block; margin-top: 5px; color: #6b7280;">
                            <?php echo number_format_i18n($calls_today); ?> <?php _e('aujourd\'hui', 'osmose-ads'); ?>
                        </small>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-calls'); ?>" class="osmose-btn osmose-btn-outline">
                            <?php _e('Voir les Stats', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Annonces avec numéros de suivi -->
            <?php if (!empty($ads_with_tracking)): ?>
                <div class="osmose-ads-card mt-4">
                    <h2>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Annonces Récentes avec Numéros de Suivi', 'osmose-ads'); ?>
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?php _e('Titre', 'osmose-ads'); ?></th>
                                    <th><?php _e('N° de Suivi', 'osmose-ads'); ?></th>
                                    <th><?php _e('Vues', 'osmose-ads'); ?></th>
                                    <th><?php _e('Appels', 'osmose-ads'); ?></th>
                                    <th><?php _e('Date', 'osmose-ads'); ?></th>
                                    <th><?php _e('Actions', 'osmose-ads'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads_with_tracking as $ad): 
                                    $tracking_number = get_post_meta($ad->ID, 'tracking_number', true);
                                    $view_count = intval(get_post_meta($ad->ID, 'view_count', true)) ?: 0;
                                    
                                    // Compter les appels pour cette annonce
                                    $ad_call_count = 0;
                                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                                        $ad_call_count = (int) $wpdb->get_var($wpdb->prepare(
                                            "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d",
                                            $ad->ID
                                        ));
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($ad->post_title); ?></strong>
                                        </td>
                                        <td>
                                            <code style="background: #f0f7ff; padding: 4px 8px; border-radius: 4px; color: #3b82f6; font-weight: 600;">
                                                <?php echo esc_html($tracking_number); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format_i18n($view_count); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo number_format_i18n($ad_call_count); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo date_i18n('d/m/Y', strtotime($ad->post_date)); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo get_permalink($ad->ID); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> <?php _e('Voir', 'osmose-ads'); ?>
                                            </a>
                                            <a href="<?php echo get_edit_post_link($ad->ID); ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i> <?php _e('Modifier', 'osmose-ads'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions Rapides -->
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-lightning"></span>
                    <?php _e('Actions Rapides', 'osmose-ads'); ?>
                </h2>
                <div class="osmose-ads-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-bulk'); ?>" class="osmose-btn osmose-btn-primary osmose-btn-large">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Générer des Annonces en Masse', 'osmose-ads'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=osmose-ads-templates'); ?>" class="osmose-btn osmose-btn-secondary osmose-btn-large">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Créer un Nouveau Template', 'osmose-ads'); ?>
                    </a>
                </div>
            </div>
        </div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

