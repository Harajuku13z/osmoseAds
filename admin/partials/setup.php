<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

// Traitement du formulaire
if (isset($_POST['osmose_ads_setup_submit']) && check_admin_referer('osmose_ads_setup', 'osmose_ads_setup_nonce')) {
    // Sauvegarder les réglages
    update_option('osmose_ads_company_phone', sanitize_text_field($_POST['company_phone'] ?? ''));
    update_option('osmose_ads_company_phone_raw', sanitize_text_field($_POST['company_phone_raw'] ?? ''));
    update_option('osmose_ads_openai_api_key', sanitize_text_field($_POST['openai_api_key'] ?? ''));
    update_option('osmose_ads_ai_provider', sanitize_text_field($_POST['ai_provider'] ?? 'openai'));
    update_option('osmose_ads_ai_personalization', isset($_POST['ai_personalization']) ? 1 : 0);
    
    // Services
    if (isset($_POST['services']) && is_array($_POST['services'])) {
        $services = array_map('sanitize_text_field', $_POST['services']);
        $services = array_filter($services); // Enlever les vides
        update_option('osmose_ads_services', $services);
    }
    
    // Villes et départements
    if (isset($_POST['cities']) && is_array($_POST['cities'])) {
        $cities_added = 0;
        foreach ($_POST['cities'] as $city_data) {
            if (!empty($city_data['name'])) {
                $city_id = wp_insert_post(array(
                    'post_title' => sanitize_text_field($city_data['name']),
                    'post_type' => 'city',
                    'post_status' => 'publish',
                ));
                
                if ($city_id && !is_wp_error($city_id)) {
                    update_post_meta($city_id, 'name', sanitize_text_field($city_data['name']));
                    update_post_meta($city_id, 'postal_code', sanitize_text_field($city_data['postal_code'] ?? ''));
                    update_post_meta($city_id, 'department', sanitize_text_field($city_data['department'] ?? ''));
                    update_post_meta($city_id, 'region', sanitize_text_field($city_data['region'] ?? ''));
                    update_post_meta($city_id, 'population', intval($city_data['population'] ?? 0));
                    $cities_added++;
                }
            }
        }
    }
    
    // Flush permaliinks
    flush_rewrite_rules();
    
    // Marquer la configuration comme terminée
    update_option('osmose_ads_setup_completed', true);
    
    // Message de succès
    $success_message = __('Configuration enregistrée avec succès !', 'osmose-ads');
    if (isset($cities_added) && $cities_added > 0) {
        $success_message .= ' ' . sprintf(__('%d ville(s) ajoutée(s).', 'osmose-ads'), $cities_added);
    }
}

// Récupérer les valeurs existantes
$company_phone = get_option('osmose_ads_company_phone', '');
$company_phone_raw = get_option('osmose_ads_company_phone_raw', '');
$openai_api_key = get_option('osmose_ads_openai_api_key', '');
$ai_provider = get_option('osmose_ads_ai_provider', 'openai');
$ai_personalization = get_option('osmose_ads_ai_personalization', false);
$services = get_option('osmose_ads_services', array());
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
                <h1>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Configuration Initiale', 'osmose-ads'); ?>
                </h1>
                <p class="description"><?php _e('Configurez votre extension en quelques étapes', 'osmose-ads'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="osmose-ads-container">
        <div class="osmose-ads-setup">
    
    <?php if (isset($success_message)): ?>
        <div class="osmose-ads-card" style="border-left: 4px solid #10b981; margin-bottom: 25px;">
            <p style="color: #10b981; font-weight: 600; margin: 0;">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html($success_message); ?>
            </p>
            <p style="margin: 15px 0 0 0;">
                <a href="<?php echo admin_url('admin.php?page=osmose-ads'); ?>" class="osmose-btn osmose-btn-primary">
                    <?php _e('Aller au Tableau de bord', 'osmose-ads'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="osmose-ads-card" style="margin-bottom: 25px;">
        <p class="description" style="margin: 0;">
            <?php _e('Bienvenue ! Configurez votre extension Osmose ADS en quelques étapes. Vous pourrez modifier ces réglages plus tard dans la section Réglages.', 'osmose-ads'); ?>
        </p>
    </div>
    
    <form method="post" action="" class="osmose-ads-setup-form">
        <?php wp_nonce_field('osmose_ads_setup', 'osmose_ads_setup_nonce'); ?>
        
        <div class="osmose-ads-setup-sections">
            
            <!-- Section 1: Informations Entreprise -->
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-building"></span>
                    <?php _e('1. Informations de l\'Entreprise', 'osmose-ads'); ?>
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="company_phone"><?php _e('Téléphone Entreprise', 'osmose-ads'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="company_phone" 
                                   name="company_phone" 
                                   value="<?php echo esc_attr($company_phone); ?>" 
                                   class="regular-text" 
                                   placeholder="01 23 45 67 89">
                            <p class="description"><?php _e('Numéro de téléphone formaté (affiché sur les pages)', 'osmose-ads'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_phone_raw"><?php _e('Téléphone (Brut)', 'osmose-ads'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="company_phone_raw" 
                                   name="company_phone_raw" 
                                   value="<?php echo esc_attr($company_phone_raw); ?>" 
                                   class="regular-text" 
                                   placeholder="0123456789">
                            <p class="description"><?php _e('Numéro sans formatage (pour liens tel:)', 'osmose-ads'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Section 2: Services -->
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('2. Services Proposés', 'osmose-ads'); ?>
                </h2>
                
                <p class="description">
                    <?php _e('Listez les services pour lesquels vous souhaitez créer des pages géolocalisées.', 'osmose-ads'); ?>
                </p>
                
                <div id="services-list-setup">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $index => $service): ?>
                            <div class="service-item-setup">
                                <input type="text" 
                                       name="services[]" 
                                       value="<?php echo esc_attr($service); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('Ex: Dépannage plomberie', 'osmose-ads'); ?>">
                                <button type="button" class="button remove-service-setup">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="service-item-setup">
                            <input type="text" 
                                   name="services[]" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Ex: Dépannage plomberie', 'osmose-ads'); ?>">
                            <button type="button" class="button remove-service-setup">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-service-setup" class="osmose-btn osmose-btn-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Ajouter un Service', 'osmose-ads'); ?>
                </button>
            </div>
            
            <!-- Section 3: Configuration IA -->
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('3. Configuration IA (Optionnel)', 'osmose-ads'); ?>
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Personnalisation IA', 'osmose-ads'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="ai_personalization" 
                                       value="1" 
                                       <?php checked($ai_personalization, 1); ?>>
                                <?php _e('Activer la personnalisation IA du contenu par ville', 'osmose-ads'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Si activé, le contenu sera généré de manière unique pour chaque ville avec l\'IA. Nécessite une clé API.', 'osmose-ads'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ai_provider"><?php _e('Fournisseur IA', 'osmose-ads'); ?></label>
                        </th>
                        <td>
                            <select id="ai_provider" name="ai_provider">
                                <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI (ChatGPT)</option>
                                <option value="groq" <?php selected($ai_provider, 'groq'); ?>>Groq</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php _e('Clé API', 'osmose-ads'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="openai_api_key" 
                                   name="openai_api_key" 
                                   value="<?php echo esc_attr($openai_api_key); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Clé API pour l\'intégration IA. Vous pouvez la configurer plus tard si vous n\'en avez pas encore.', 'osmose-ads'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Section 4: Villes et Départements -->
            <div class="osmose-ads-card">
                <h2>
                    <span class="dashicons dashicons-location"></span>
                    <?php _e('4. Villes et Départements', 'osmose-ads'); ?>
                </h2>
                
                <p class="description">
                    <?php _e('Ajoutez les villes pour lesquelles vous souhaitez créer des pages. Vous pourrez en ajouter d\'autres plus tard.', 'osmose-ads'); ?>
                </p>
                
                <div id="cities-list-setup">
                    <div class="city-item-setup">
                        <table class="form-table city-fields">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Nom de la Ville', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="cities[0][name]" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Ex: Paris', 'osmose-ads'); ?>" 
                                           required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Code Postal', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="cities[0][postal_code]" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Ex: 75001', 'osmose-ads'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Département', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="cities[0][department]" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Ex: Paris', 'osmose-ads'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Région', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="cities[0][region]" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Ex: Île-de-France', 'osmose-ads'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Population', 'osmose-ads'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           name="cities[0][population]" 
                                           class="regular-text" 
                                           placeholder="<?php _e('Optionnel', 'osmose-ads'); ?>">
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="osmose-btn osmose-btn-outline remove-city-setup">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Supprimer cette ville', 'osmose-ads'); ?>
                        </button>
                    </div>
                </div>
                
                        <button type="button" id="add-city-setup" class="osmose-btn osmose-btn-secondary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Ajouter une Autre Ville', 'osmose-ads'); ?>
                        </button>
            </div>
            
        </div>
        
        <div class="osmose-ads-card" style="text-align: center; margin-top: 30px;">
            <p class="submit">
                <button type="submit" 
                        name="osmose_ads_setup_submit" 
                        class="osmose-btn osmose-btn-primary osmose-btn-large">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Enregistrer la Configuration', 'osmose-ads'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=osmose-ads'); ?>" class="osmose-btn osmose-btn-secondary">
                    <?php _e('Passer cette étape', 'osmose-ads'); ?>
                </a>
            </p>
        </div>
    </form>
    </div>
</div>

<style>
.osmose-ads-setup {
    max-width: 100%;
}

.osmose-ads-setup-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.osmose-ads-card h2 .dashicons {
    color: #3b82f6;
}

.service-item-setup,
.city-item-setup {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.service-item-setup {
    display: flex;
    gap: 10px;
    align-items: center;
}

.service-item-setup input {
    flex: 1;
}

.city-item-setup {
    position: relative;
}

.city-item-setup .city-fields {
    margin-bottom: 10px;
}

.city-item-setup .remove-city-setup {
    margin-top: 10px;
}

</style>

<script>
jQuery(document).ready(function($) {
    // Ajouter un service
    $('#add-service-setup').on('click', function() {
        var html = '<div class="service-item-setup">' +
            '<input type="text" name="services[]" class="regular-text" placeholder="<?php _e('Ex: Dépannage plomberie', 'osmose-ads'); ?>">' +
            '<button type="button" class="button remove-service-setup"><span class="dashicons dashicons-trash"></span></button>' +
            '</div>';
        $('#services-list-setup').append(html);
    });
    
    // Supprimer un service
    $(document).on('click', '.remove-service-setup', function() {
        if ($('.service-item-setup').length > 1) {
            $(this).closest('.service-item-setup').remove();
        } else {
            alert('<?php _e('Vous devez avoir au moins un service', 'osmose-ads'); ?>');
        }
    });
    
    // Ajouter une ville
    var cityIndex = 1;
    $('#add-city-setup').on('click', function() {
        var html = '<div class="city-item-setup">' +
            '<table class="form-table city-fields">' +
            '<tr><th scope="row"><label><?php _e('Nom de la Ville', 'osmose-ads'); ?></label></th>' +
            '<td><input type="text" name="cities[' + cityIndex + '][name]" class="regular-text" placeholder="<?php _e('Ex: Paris', 'osmose-ads'); ?>"></td></tr>' +
            '<tr><th scope="row"><label><?php _e('Code Postal', 'osmose-ads'); ?></label></th>' +
            '<td><input type="text" name="cities[' + cityIndex + '][postal_code]" class="regular-text" placeholder="<?php _e('Ex: 75001', 'osmose-ads'); ?>"></td></tr>' +
            '<tr><th scope="row"><label><?php _e('Département', 'osmose-ads'); ?></label></th>' +
            '<td><input type="text" name="cities[' + cityIndex + '][department]" class="regular-text" placeholder="<?php _e('Ex: Paris', 'osmose-ads'); ?>"></td></tr>' +
            '<tr><th scope="row"><label><?php _e('Région', 'osmose-ads'); ?></label></th>' +
            '<td><input type="text" name="cities[' + cityIndex + '][region]" class="regular-text" placeholder="<?php _e('Ex: Île-de-France', 'osmose-ads'); ?>"></td></tr>' +
            '<tr><th scope="row"><label><?php _e('Population', 'osmose-ads'); ?></label></th>' +
            '<td><input type="number" name="cities[' + cityIndex + '][population]" class="regular-text" placeholder="<?php _e('Optionnel', 'osmose-ads'); ?>"></td></tr>' +
            '</table>' +
            '<button type="button" class="button remove-city-setup"><span class="dashicons dashicons-trash"></span> <?php _e('Supprimer cette ville', 'osmose-ads'); ?></button>' +
            '</div>';
        $('#cities-list-setup').append(html);
        cityIndex++;
    });
    
    // Supprimer une ville
    $(document).on('click', '.remove-city-setup', function() {
        if ($('.city-item-setup').length > 1) {
            $(this).closest('.city-item-setup').remove();
        } else {
            alert('<?php _e('Vous devez avoir au moins une ville', 'osmose-ads'); ?>');
        }
    });
});
</script>

