<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Traiter le formulaire
if (isset($_POST['osmose_simulator_config_submit']) && current_user_can('manage_options')) {
    check_admin_referer('osmose_simulator_config');
    
    // Récupérer les valeurs
    $page_slug = sanitize_title($_POST['simulator_page_slug'] ?? 'simulateur-devis');
    $page_title = sanitize_text_field($_POST['simulator_page_title'] ?? 'Simulateur de Devis');
    $email_notification = isset($_POST['simulator_email_notification']) ? 1 : 0;
    $email_recipient = sanitize_email($_POST['simulator_email_recipient'] ?? get_option('admin_email'));
    
    // Mettre à jour les options
    update_option('osmose_ads_simulator_page_slug', $page_slug);
    update_option('osmose_ads_simulator_title', $page_title);
    update_option('osmose_ads_simulator_email_notification', $email_notification);
    update_option('osmose_ads_simulator_email_recipient', $email_recipient);
    
    // Créer ou mettre à jour la page
    $page_id = get_option('osmose_ads_simulator_page_id');
    $page = null;
    
    if ($page_id) {
        $page = get_post($page_id);
    }
    
    if (!$page || $page->post_status !== 'publish') {
        // Créer la page
        $page_data = array(
            'post_title'    => $page_title,
            'post_content'  => '[osmose_simulator]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => $page_slug,
            'post_author'   => get_current_user_id(),
        );
        
        if ($page_id && $page) {
            $page_data['ID'] = $page_id;
            $page_id = wp_update_post($page_data);
        } else {
            $page_id = wp_insert_post($page_data);
        }
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('osmose_ads_simulator_page_id', $page_id);
            echo '<div class="notice notice-success"><p>' . __('Page créée/mise à jour avec succès !', 'osmose-ads') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Erreur lors de la création de la page.', 'osmose-ads') . '</p></div>';
        }
    } else {
        // Mettre à jour la page existante
        $page_data = array(
            'ID'           => $page_id,
            'post_title'   => $page_title,
            'post_name'    => $page_slug,
        );
        
        wp_update_post($page_data);
        echo '<div class="notice notice-success"><p>' . __('Configuration mise à jour avec succès !', 'osmose-ads') . '</p></div>';
    }
}

// Récupérer les valeurs actuelles
$page_id = get_option('osmose_ads_simulator_page_id');
$page_slug = get_option('osmose_ads_simulator_page_slug', 'simulateur-devis');
$page_title = get_option('osmose_ads_simulator_title', 'Simulateur de Devis');
$email_notification = get_option('osmose_ads_simulator_email_notification', 1);
$email_recipient = get_option('osmose_ads_simulator_email_recipient', get_option('admin_email'));

$page = null;
$page_url = '';
if ($page_id) {
    $page = get_post($page_id);
    if ($page) {
        $page_url = get_permalink($page_id);
    }
}
?>

<div class="osmose-simulator-config-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: 20px;">
        <div>
            <h1 class="h3 mb-1"><?php _e('Configuration du Simulateur', 'osmose-ads'); ?></h1>
            <p class="text-muted mb-0"><?php _e('Configurez la page et les paramètres du simulateur de devis', 'osmose-ads'); ?></p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php _e('Paramètres de la Page', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <?php wp_nonce_field('osmose_simulator_config'); ?>
                        
                        <div class="mb-3">
                            <label for="simulator_page_title" class="form-label">
                                <?php _e('Titre de la page', 'osmose-ads'); ?>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="simulator_page_title" 
                                   name="simulator_page_title" 
                                   value="<?php echo esc_attr($page_title); ?>" 
                                   required>
                            <small class="form-text text-muted">
                                <?php _e('Le titre qui apparaîtra sur la page WordPress', 'osmose-ads'); ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="simulator_page_slug" class="form-label">
                                <?php _e('Slug de la page (URL)', 'osmose-ads'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo home_url('/'); ?></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="simulator_page_slug" 
                                       name="simulator_page_slug" 
                                       value="<?php echo esc_attr($page_slug); ?>" 
                                       pattern="[a-z0-9-]+"
                                       required>
                            </div>
                            <small class="form-text text-muted">
                                <?php _e('L\'URL de la page sera : ', 'osmose-ads'); ?>
                                <strong><?php echo home_url('/' . $page_slug . '/'); ?></strong>
                            </small>
                        </div>
                        
                        <?php if ($page_url): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong><?php _e('Page actuelle :', 'osmose-ads'); ?></strong>
                                <a href="<?php echo esc_url($page_url); ?>" target="_blank">
                                    <?php echo esc_html($page_url); ?>
                                </a>
                                <span class="badge bg-success ms-2">
                                    <?php _e('Publiée', 'osmose-ads'); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php _e('La page n\'existe pas encore. Elle sera créée lors de l\'enregistrement.', 'osmose-ads'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <h5 class="mb-3"><?php _e('Notifications Email', 'osmose-ads'); ?></h5>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="simulator_email_notification" 
                                       name="simulator_email_notification" 
                                       value="1" 
                                       <?php checked($email_notification, 1); ?>>
                                <label class="form-check-label" for="simulator_email_notification">
                                    <?php _e('Activer les notifications email', 'osmose-ads'); ?>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                <?php _e('Recevoir un email à chaque nouvelle demande de devis', 'osmose-ads'); ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="simulator_email_recipient" class="form-label">
                                <?php _e('Email de notification', 'osmose-ads'); ?>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="simulator_email_recipient" 
                                   name="simulator_email_recipient" 
                                   value="<?php echo esc_attr($email_recipient); ?>" 
                                   required>
                            <small class="form-text text-muted">
                                <?php _e('Adresse email qui recevra les notifications', 'osmose-ads'); ?>
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" 
                                    name="osmose_simulator_config_submit" 
                                    class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>
                                <?php _e('Enregistrer la configuration', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php _e('Informations', 'osmose-ads'); ?></h5>
                </div>
                <div class="card-body">
                    <h6><?php _e('Shortcode', 'osmose-ads'); ?></h6>
                    <p>
                        <code>[osmose_simulator]</code>
                    </p>
                    <p class="text-muted small">
                        <?php _e('Utilisez ce shortcode pour afficher le simulateur sur n\'importe quelle page ou article.', 'osmose-ads'); ?>
                    </p>
                    
                    <hr>
                    
                    <h6><?php _e('Lien dans le menu', 'osmose-ads'); ?></h6>
                    <p class="text-muted small">
                        <?php _e('Le lien "Devis Gratuit" est automatiquement ajouté dans le menu header de votre site.', 'osmose-ads'); ?>
                    </p>
                    
                    <hr>
                    
                    <h6><?php _e('Bouton flottant', 'osmose-ads'); ?></h6>
                    <p class="text-muted small">
                        <?php _e('Un bouton flottant apparaît en bas à droite de toutes les pages (sauf la page du simulateur).', 'osmose-ads'); ?>
                    </p>
                    
                    <hr>
                    
                    <h6><?php _e('Statistiques', 'osmose-ads'); ?></h6>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'osmose_ads_quote_requests';
                    $table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);
                    
                    if ($table_exists) {
                        $total_quotes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                        $pending_quotes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
                        ?>
                        <ul class="list-unstyled mb-0">
                            <li><strong><?php echo $total_quotes; ?></strong> <?php _e('demandes totales', 'osmose-ads'); ?></li>
                            <li><strong><?php echo $pending_quotes; ?></strong> <?php _e('en attente', 'osmose-ads'); ?></li>
                        </ul>
                        <a href="<?php echo admin_url('admin.php?page=osmose-ads-quotes'); ?>" class="btn btn-sm btn-outline-primary mt-2">
                            <?php _e('Voir toutes les demandes', 'osmose-ads'); ?>
                        </a>
                    <?php } else { ?>
                        <p class="text-muted small mb-0">
                            <?php _e('Aucune demande pour le moment', 'osmose-ads'); ?>
                        </p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

