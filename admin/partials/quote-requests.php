<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

global $wpdb;
$table_name = $wpdb->prefix . 'osmose_ads_quote_requests';

// Vérifier que la table existe
$table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);

// Gérer les actions
if (isset($_GET['action']) && isset($_GET['quote_id']) && current_user_can('manage_options')) {
    $quote_id = intval($_GET['quote_id']);
    $action = sanitize_text_field($_GET['action']);
    
    if ($action === 'delete' && wp_verify_nonce($_GET['_wpnonce'], 'delete_quote_' . $quote_id)) {
        $wpdb->delete($table_name, array('id' => $quote_id), array('%d'));
        echo '<div class="notice notice-success"><p>' . __('Demande supprimée avec succès', 'osmose-ads') . '</p></div>';
    } elseif ($action === 'update_status' && isset($_GET['status']) && wp_verify_nonce($_GET['_wpnonce'], 'update_status_' . $quote_id)) {
        $status = sanitize_text_field($_GET['status']);
        $wpdb->update($table_name, array('status' => $status), array('id' => $quote_id), array('%s'), array('%d'));
        echo '<div class="notice notice-success"><p>' . __('Statut mis à jour', 'osmose-ads') . '</p></div>';
    }
}

// Filtrer par statut
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Récupérer les demandes
$where_clause = '';
if ($status_filter !== 'all') {
    $where_clause = $wpdb->prepare("WHERE status = %s", $status_filter);
}

$quotes = array();
$total_quotes = 0;
$pending_quotes = 0;
$processed_quotes = 0;

if ($table_exists) {
    $total_quotes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $pending_quotes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    $processed_quotes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('processed', 'contacted')");
    
    $quotes = $wpdb->get_results(
        "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT 100",
        ARRAY_A
    );
}
?>

<div class="osmose-quote-requests-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Demandes de Devis', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Gérez les demandes de devis reçues via le simulateur', 'osmose-ads'); ?></p>
        </div>
        <div>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-quotes&status=all'); ?>" class="btn btn-sm <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php _e('Toutes', 'osmose-ads'); ?> (<?php echo $total_quotes; ?>)
            </a>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-quotes&status=pending'); ?>" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                <?php _e('En attente', 'osmose-ads'); ?> (<?php echo $pending_quotes; ?>)
            </a>
            <a href="<?php echo admin_url('admin.php?page=osmose-ads-quotes&status=processed'); ?>" class="btn btn-sm <?php echo $status_filter === 'processed' ? 'btn-success' : 'btn-outline-success'; ?>">
                <?php _e('Traitées', 'osmose-ads'); ?> (<?php echo $processed_quotes; ?>)
            </a>
        </div>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php _e('La table de demandes de devis n\'existe pas encore. Elle sera créée automatiquement lors de la première demande.', 'osmose-ads'); ?>
        </div>
    <?php elseif (empty($quotes)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <?php _e('Aucune demande de devis pour le moment.', 'osmose-ads'); ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'osmose-ads'); ?></th>
                                <th><?php _e('Nom', 'osmose-ads'); ?></th>
                                <th><?php _e('Contact', 'osmose-ads'); ?></th>
                                <th><?php _e('Type', 'osmose-ads'); ?></th>
                                <th><?php _e('Travaux', 'osmose-ads'); ?></th>
                                <th><?php _e('Statut', 'osmose-ads'); ?></th>
                                <th><?php _e('Actions', 'osmose-ads'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $quote): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date_i18n('d/m/Y H:i', strtotime($quote['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($quote['first_name'] . ' ' . $quote['last_name']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="mailto:<?php echo esc_attr($quote['email']); ?>">
                                                <?php echo esc_html($quote['email']); ?>
                                            </a>
                                        </div>
                                        <div>
                                            <a href="tel:<?php echo esc_attr($quote['phone']); ?>">
                                                <?php echo esc_html($quote['phone']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo esc_html(ucfirst($quote['property_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo esc_html($quote['work_type'] ?: '—'); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'bg-secondary';
                                        if ($quote['status'] === 'pending') {
                                            $status_class = 'bg-warning text-dark';
                                        } elseif ($quote['status'] === 'processed' || $quote['status'] === 'contacted') {
                                            $status_class = 'bg-success';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php
                                            $status_labels = array(
                                                'pending' => __('En attente', 'osmose-ads'),
                                                'contacted' => __('Contacté', 'osmose-ads'),
                                                'processed' => __('Traité', 'osmose-ads'),
                                                'cancelled' => __('Annulé', 'osmose-ads'),
                                            );
                                            echo esc_html($status_labels[$quote['status']] ?? $quote['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#quoteModal<?php echo $quote['id']; ?>">
                                                <i class="bi bi-eye"></i> <?php _e('Voir', 'osmose-ads'); ?>
                                            </button>
                                            <?php if ($quote['status'] === 'pending'): ?>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=osmose-ads-quotes&action=update_status&quote_id=' . $quote['id'] . '&status=contacted'), 'update_status_' . $quote['id']); ?>" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=osmose-ads-quotes&action=delete&quote_id=' . $quote['id']), 'delete_quote_' . $quote['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer cette demande ?', 'osmose-ads')); ?>');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Modal pour voir les détails -->
                                <div class="modal fade" id="quoteModal<?php echo $quote['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php _e('Détails de la demande', 'osmose-ads'); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Nom:', 'osmose-ads'); ?></strong><br>
                                                        <?php echo esc_html($quote['first_name'] . ' ' . $quote['last_name']); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Email:', 'osmose-ads'); ?></strong><br>
                                                        <a href="mailto:<?php echo esc_attr($quote['email']); ?>"><?php echo esc_html($quote['email']); ?></a>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Téléphone:', 'osmose-ads'); ?></strong><br>
                                                        <a href="tel:<?php echo esc_attr($quote['phone']); ?>"><?php echo esc_html($quote['phone']); ?></a>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Type de logement:', 'osmose-ads'); ?></strong><br>
                                                        <?php echo esc_html(ucfirst($quote['property_type'])); ?>
                                                    </div>
                                                </div>
                                                <?php if ($quote['address'] || $quote['city'] || $quote['postal_code']): ?>
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <strong><?php _e('Adresse:', 'osmose-ads'); ?></strong><br>
                                                            <?php echo esc_html(trim($quote['address'] . ' ' . $quote['postal_code'] . ' ' . $quote['city'])); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="row mb-3">
                                                    <div class="col-12">
                                                        <strong><?php _e('Travaux demandés:', 'osmose-ads'); ?></strong><br>
                                                        <?php echo esc_html($quote['work_type'] ?: '—'); ?>
                                                    </div>
                                                </div>
                                                <?php if ($quote['message']): ?>
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <strong><?php _e('Message:', 'osmose-ads'); ?></strong><br>
                                                            <?php echo nl2br(esc_html($quote['message'])); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Date de demande:', 'osmose-ads'); ?></strong><br>
                                                        <?php echo date_i18n('d/m/Y à H:i', strtotime($quote['created_at'])); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong><?php _e('Statut:', 'osmose-ads'); ?></strong><br>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo esc_html($status_labels[$quote['status']] ?? $quote['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <?php if ($quote['status'] === 'pending'): ?>
                                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=osmose-ads-quotes&action=update_status&quote_id=' . $quote['id'] . '&status=contacted'), 'update_status_' . $quote['id']); ?>" class="btn btn-success">
                                                        <i class="bi bi-check"></i> <?php _e('Marquer comme contacté', 'osmose-ads'); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Fermer', 'osmose-ads'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

