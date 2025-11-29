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
    
    // Gérer les types de projets
    if (isset($_POST['project_types']) && is_array($_POST['project_types'])) {
        $project_types = array();
        foreach ($_POST['project_types'] as $key => $project) {
            if (!empty($project['label'])) {
                $project_types[sanitize_key($key)] = array(
                    'label' => sanitize_text_field($project['label']),
                    'image' => isset($project['image']) ? esc_url_raw($project['image']) : '',
                    'options' => isset($project['options']) && is_array($project['options']) 
                        ? array_map('sanitize_text_field', array_filter($project['options'])) 
                        : array()
                );
            }
        }
        update_option('osmose_ads_simulator_project_types', $project_types);
    }
    
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
$project_types = get_option('osmose_ads_simulator_project_types', array(
    'toiture' => array(
        'label' => 'Toiture',
        'options' => array('hydrofuge', 'démoussage', 'réparation', 'remplacement', 'isolation')
    )
));

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
                            <div class="input-group">
                                <input type="email" 
                                       class="form-control" 
                                       id="simulator_email_recipient" 
                                       name="simulator_email_recipient" 
                                       value="<?php echo esc_attr($email_recipient); ?>" 
                                       required>
                                <button type="button" 
                                        class="btn btn-outline-primary" 
                                        id="test-email-btn"
                                        data-test-email="<?php echo esc_attr($email_recipient); ?>">
                                    <i class="bi bi-envelope-check"></i> <?php _e('Tester', 'osmose-ads'); ?>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <?php _e('Adresse email qui recevra les notifications', 'osmose-ads'); ?>
                            </small>
                            <div id="test-email-result" class="mt-2" style="display: none;"></div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3"><?php _e('Types de Projets', 'osmose-ads'); ?></h5>
                        <p class="text-muted"><?php _e('Configurez les types de projets disponibles dans le simulateur et leurs options', 'osmose-ads'); ?></p>
                        
                        <div id="project-types-list">
                            <?php 
                            $index = 0;
                            foreach ($project_types as $key => $project): 
                                $index++;
                            ?>
                                <div class="project-type-item mb-4 p-3 border rounded" data-index="<?php echo $index; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?php _e('Type de projet', 'osmose-ads'); ?> #<?php echo $index; ?></h6>
                                        <button type="button" class="btn btn-sm btn-danger remove-project-type" data-index="<?php echo $index; ?>">
                                            <i class="bi bi-trash"></i> <?php _e('Supprimer', 'osmose-ads'); ?>
                                        </button>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label"><?php _e('Nom du projet', 'osmose-ads'); ?></label>
                                        <input type="text" 
                                               class="form-control project-label" 
                                               name="project_types[<?php echo esc_attr($key); ?>][label]" 
                                               value="<?php echo esc_attr($project['label']); ?>" 
                                               placeholder="Ex: Toiture" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label"><?php _e('Image illustrative (optionnel)', 'osmose-ads'); ?></label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="url" 
                                                   class="form-control project-image-url" 
                                                   name="project_types[<?php echo esc_attr($key); ?>][image]" 
                                                   value="<?php echo esc_attr($project['image'] ?? ''); ?>" 
                                                   placeholder="URL de l'image">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary upload-project-image" 
                                                    data-project-key="<?php echo esc_attr($key); ?>">
                                                <i class="bi bi-upload"></i> <?php _e('Uploader', 'osmose-ads'); ?>
                                            </button>
                                        </div>
                                        <?php if (!empty($project['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc_url($project['image']); ?>" 
                                                     alt="<?php echo esc_attr($project['label']); ?>" 
                                                     style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                                            </div>
                                        <?php endif; ?>
                                        <small class="form-text text-muted">
                                            <?php _e('Si une image est fournie, elle remplacera l\'icône dans le simulateur', 'osmose-ads'); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <label class="form-label"><?php _e('Options disponibles', 'osmose-ads'); ?></label>
                                        <div class="project-options-list">
                                            <?php 
                                            if (!empty($project['options'])) {
                                                foreach ($project['options'] as $option): 
                                            ?>
                                                <div class="input-group mb-2 project-option-item">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="project_types[<?php echo esc_attr($key); ?>][options][]" 
                                                           value="<?php echo esc_attr($option); ?>" 
                                                           placeholder="Ex: Hydrofuge">
                                                    <button type="button" class="btn btn-outline-danger remove-option">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            <?php 
                                                endforeach;
                                            } else {
                                            ?>
                                                <div class="input-group mb-2 project-option-item">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="project_types[<?php echo esc_attr($key); ?>][options][]" 
                                                           placeholder="Ex: Hydrofuge">
                                                    <button type="button" class="btn btn-outline-danger remove-option">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary add-option" data-project-key="<?php echo esc_attr($key); ?>">
                                            <i class="bi bi-plus"></i> <?php _e('Ajouter une option', 'osmose-ads'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="btn btn-outline-success mb-3" id="add-project-type">
                            <i class="bi bi-plus-circle"></i> <?php _e('Ajouter un type de projet', 'osmose-ads'); ?>
                        </button>
                        
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

<script>
jQuery(document).ready(function($) {
    var projectTypeIndex = <?php echo count($project_types) + 1; ?>;
    var mediaUploader;
    
    // Upload d'image pour un projet
    $(document).on('click', '.upload-project-image', function(e) {
        e.preventDefault();
        var projectKey = $(this).data('project-key');
        var $input = $('input[name="project_types[' + projectKey + '][image]"]');
        var $container = $input.closest('.mb-2');
        
        // Créer une nouvelle instance pour chaque projet
        var customUploader = wp.media({
            title: 'Choisir une image',
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });
        
        customUploader.on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
            
            // Afficher ou mettre à jour l'aperçu
            var $preview = $container.find('.image-preview');
            if ($preview.length === 0) {
                $preview = $('<div class="mt-2 image-preview"></div>');
                $container.append($preview);
            }
            $preview.html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px;">');
        });
        
        customUploader.open();
    });
    
    // Ajouter un type de projet
    $('#add-project-type').on('click', function() {
        var newKey = 'project_' + Date.now();
        var html = '<div class="project-type-item mb-4 p-3 border rounded" data-index="' + projectTypeIndex + '">' +
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<h6 class="mb-0">Type de projet #' + projectTypeIndex + '</h6>' +
            '<button type="button" class="btn btn-sm btn-danger remove-project-type" data-index="' + projectTypeIndex + '">' +
            '<i class="bi bi-trash"></i> Supprimer' +
            '</button>' +
            '</div>' +
            '<div class="mb-2">' +
            '<label class="form-label">Nom du projet</label>' +
            '<input type="text" class="form-control project-label" name="project_types[' + newKey + '][label]" placeholder="Ex: Toiture" required>' +
            '</div>' +
            '<div class="mb-2">' +
            '<label class="form-label">Image illustrative (optionnel)</label>' +
            '<div class="d-flex align-items-center gap-2">' +
            '<input type="url" class="form-control project-image-url" name="project_types[' + newKey + '][image]" placeholder="URL de l\'image">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary upload-project-image" data-project-key="' + newKey + '">' +
            '<i class="bi bi-upload"></i> Uploader' +
            '</button>' +
            '</div>' +
            '<small class="form-text text-muted">Si une image est fournie, elle remplacera l\'icône dans le simulateur</small>' +
            '</div>' +
            '<div>' +
            '<label class="form-label">Options disponibles</label>' +
            '<div class="project-options-list">' +
            '<div class="input-group mb-2 project-option-item">' +
            '<input type="text" class="form-control" name="project_types[' + newKey + '][options][]" placeholder="Ex: Hydrofuge">' +
            '<button type="button" class="btn btn-outline-danger remove-option"><i class="bi bi-x"></i></button>' +
            '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-primary add-option" data-project-key="' + newKey + '">' +
            '<i class="bi bi-plus"></i> Ajouter une option' +
            '</button>' +
            '</div>' +
            '</div>';
        
        $('#project-types-list').append(html);
        projectTypeIndex++;
    });
    
    // Supprimer un type de projet
    $(document).on('click', '.remove-project-type', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce type de projet ?')) {
            $(this).closest('.project-type-item').remove();
        }
    });
    
    // Ajouter une option
    $(document).on('click', '.add-option', function() {
        var projectKey = $(this).data('project-key');
        var html = '<div class="input-group mb-2 project-option-item">' +
            '<input type="text" class="form-control" name="project_types[' + projectKey + '][options][]" placeholder="Ex: Hydrofuge">' +
            '<button type="button" class="btn btn-outline-danger remove-option"><i class="bi bi-x"></i></button>' +
            '</div>';
        $(this).siblings('.project-options-list').append(html);
    });
    
    // Supprimer une option
    $(document).on('click', '.remove-option', function() {
        $(this).closest('.project-option-item').remove();
    });
    
    // Tester l'envoi d'email
    $('#test-email-btn').on('click', function() {
        var $btn = $(this);
        var $result = $('#test-email-result');
        var email = $('#simulator_email_recipient').val() || $btn.data('test-email');
        
        if (!email) {
            $result.html('<div class="alert alert-warning">Veuillez d\'abord saisir une adresse email</div>').show();
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Envoi...');
        $result.hide();
        
        $.ajax({
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof osmoseAds !== 'undefined' ? osmoseAds.ajax_url : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'osmose_ads_test_email',
                nonce: '<?php echo wp_create_nonce('osmose_ads_nonce'); ?>',
                email: email
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + response.data.message + '</div>').show();
                } else {
                    $result.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + (response.data && response.data.message ? response.data.message : 'Erreur inconnue') + '</div>').show();
                }
            },
            error: function() {
                $result.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Erreur de connexion</div>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-envelope-check"></i> Tester');
            }
        });
    });
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

