<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_call_tracking';

// Récupérer l'ID de l'appel
$call_id = isset($_GET['call_id']) ? intval($_GET['call_id']) : 0;

if (!$call_id) {
    echo '<div class="alert alert-danger">' . __('ID d\'appel manquant', 'osmose-ads') . '</div>';
    require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
    exit;
}

// Récupérer les détails de l'appel
$call = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE id = %d",
    $call_id
), ARRAY_A);

if (!$call) {
    echo '<div class="alert alert-danger">' . __('Appel introuvable', 'osmose-ads') . '</div>';
    require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
    exit;
}

// Récupérer l'annonce associée si disponible
$ad = null;
if ($call['ad_id']) {
    $ad = get_post($call['ad_id']);
}

// Déterminer si c'est un bot
$is_bot = isset($call['is_bot']) && intval($call['is_bot']) === 1;

// Formater les dates
$created_date = date_i18n('d/m/Y à H:i:s', strtotime($call['created_at']));
$call_time = $call['call_time'] ? date_i18n('d/m/Y à H:i:s', strtotime($call['call_time'])) : '—';
?>

<div class="osmose-call-details-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Détails de l\'Appel', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Informations complètes sur l\'appel', 'osmose-ads'); ?></p>
        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-calls'); ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?php _e('Retour à la liste', 'osmose-ads'); ?>
            </a>
        </div>
    </div>
    
    <!-- Informations principales -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i><?php _e('Informations Générales', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php _e('Date/Heure:', 'osmose-ads'); ?></strong><br>
                        <span><?php echo esc_html($created_date); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php _e('Type:', 'osmose-ads'); ?></strong><br>
                        <?php if ($is_bot): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-robot"></i> <?php _e('Bot', 'osmose-ads'); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success">
                                <i class="bi bi-person"></i> <?php _e('Humain', 'osmose-ads'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <strong><?php _e('Source:', 'osmose-ads'); ?></strong><br>
                        <span class="badge bg-info"><?php echo esc_html($call['source'] ?: '—'); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><?php _e('Téléphone:', 'osmose-ads'); ?></strong><br>
                        <strong class="text-primary"><?php echo esc_html($call['phone_number'] ?: '—'); ?></strong>
                    </div>
                    <div>
                        <strong><?php _e('Heure de l\'appel:', 'osmose-ads'); ?></strong><br>
                        <span><?php echo esc_html($call_time); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i><?php _e('Origine', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php _e('Page d\'origine:', 'osmose-ads'); ?></strong><br>
                        <?php if ($call['page_url']): ?>
                            <a href="<?php echo esc_url($call['page_url']); ?>" target="_blank" class="text-break">
                                <?php echo esc_html($call['page_url']); ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <strong><?php _e('Referrer:', 'osmose-ads'); ?></strong><br>
                        <?php if ($call['referrer']): ?>
                            <a href="<?php echo esc_url($call['referrer']); ?>" target="_blank" class="text-break">
                                <?php echo esc_html($call['referrer']); ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?php _e('Annonce:', 'osmose-ads'); ?></strong><br>
                        <?php if ($ad): ?>
                            <a href="<?php echo get_edit_post_link($call['ad_id']); ?>" target="_blank">
                                #<?php echo esc_html($call['ad_id']); ?> - <?php echo esc_html($ad->post_title); ?>
                            </a>
                            <br>
                            <small class="text-muted"><?php echo esc_html($call['ad_slug'] ?: '—'); ?></small>
                        <?php else: ?>
                            <?php echo esc_html($call['ad_id'] ? '#' . $call['ad_id'] : '—'); ?>
                            <?php if ($call['ad_slug']): ?>
                                <br><small class="text-muted"><?php echo esc_html($call['ad_slug']); ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informations techniques -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-globe me-2"></i><?php _e('Informations Réseau', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php _e('Adresse IP:', 'osmose-ads'); ?></strong><br>
                        <code><?php echo esc_html($call['user_ip'] ?: '—'); ?></code>
                    </div>
                    <div>
                        <strong><?php _e('User Agent:', 'osmose-ads'); ?></strong><br>
                        <code class="text-break" style="font-size: 0.9em; word-break: break-all; white-space: pre-wrap; display: block; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <?php echo esc_html($call['user_agent'] ?: '—'); ?>
                        </code>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-database me-2"></i><?php _e('Informations Système', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><?php _e('ID d\'enregistrement:', 'osmose-ads'); ?></strong><br>
                        <code>#<?php echo esc_html($call['id']); ?></code>
                    </div>
                    <div class="mb-3">
                        <strong><?php _e('Date de création (BDD):', 'osmose-ads'); ?></strong><br>
                        <small><?php echo esc_html($call['created_at']); ?></small>
                    </div>
                    <?php if ($call['call_time'] && $call['call_time'] !== $call['created_at']): ?>
                        <div>
                            <strong><?php _e('Heure de l\'appel (BDD):', 'osmose-ads'); ?></strong><br>
                            <small><?php echo esc_html($call['call_time']); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="<?php echo admin_url('admin.php?page=osmose-ads-calls'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> <?php _e('Retour à la liste', 'osmose-ads'); ?>
                </a>
                <?php if ($call['page_url']): ?>
                    <a href="<?php echo esc_url($call['page_url']); ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> <?php _e('Voir la page', 'osmose-ads'); ?>
                    </a>
                <?php endif; ?>
                <?php if ($ad): ?>
                    <a href="<?php echo get_edit_post_link($call['ad_id']); ?>" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-pencil"></i> <?php _e('Modifier l\'annonce', 'osmose-ads'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>






