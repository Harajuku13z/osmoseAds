<?php
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier si la configuration est terminée
$setup_completed = get_option('osmose_ads_setup_completed', false);

// Statistiques
$templates_count = wp_count_posts('ad_template');
$ads_count = wp_count_posts('ad');
$cities_count = wp_count_posts('city');

// Chemin du logo - vérifier plusieurs emplacements possibles
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

// Si pas trouvé, essayer via URL directe
if (empty($logo_url)) {
    $logo_url = OSMOSE_ADS_PLUGIN_URL . '../logo.jpg';
}
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
                <p class="description"><?php _e('Gérez vos annonces géolocalisées en toute simplicité', 'osmose-ads'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="osmose-ads-container">
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
            </div>
            
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
    </div>
</div>



