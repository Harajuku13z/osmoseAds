<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

if (isset($_POST['submit'])) {
    update_option('osmose_ads_ai_personalization', isset($_POST['ai_personalization']) ? 1 : 0);
    update_option('osmose_ads_company_phone', sanitize_text_field($_POST['company_phone'] ?? ''));
    update_option('osmose_ads_company_phone_raw', sanitize_text_field($_POST['company_phone_raw'] ?? ''));
    update_option('osmose_ads_openai_api_key', sanitize_text_field($_POST['openai_api_key'] ?? ''));
    update_option('osmose_ads_ai_provider', sanitize_text_field($_POST['ai_provider'] ?? 'openai'));
    
    if (isset($_POST['services'])) {
        $services = array_map('sanitize_text_field', $_POST['services']);
        update_option('osmose_ads_services', $services);
    }
    
    echo '<div class="alert alert-success"><p>' . __('Réglages sauvegardés', 'osmose-ads') . '</p></div>';
}

$ai_personalization = get_option('osmose_ads_ai_personalization', false);
$company_phone = get_option('osmose_ads_company_phone', '');
$company_phone_raw = get_option('osmose_ads_company_phone_raw', '');
$openai_api_key = get_option('osmose_ads_openai_api_key', '');
$ai_provider = get_option('osmose_ads_ai_provider', 'openai');
$services = get_option('osmose_ads_services', array());
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
</div>
    
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Personnalisation IA', 'osmose-ads'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ai_personalization" value="1" <?php checked($ai_personalization, 1); ?>>
                        <?php _e('Activer la personnalisation IA du contenu par ville', 'osmose-ads'); ?>
                    </label>
                    <p class="description"><?php _e('Si activé, le contenu sera généré de manière unique pour chaque ville avec l\'IA.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Fournisseur IA', 'osmose-ads'); ?></th>
                <td>
                    <select name="ai_provider">
                        <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI (ChatGPT)</option>
                        <option value="groq" <?php selected($ai_provider, 'groq'); ?>>Groq</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Clé API OpenAI/Groq', 'osmose-ads'); ?></th>
                <td>
                    <input type="password" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text">
                    <p class="description"><?php _e('Clé API pour l\'intégration IA', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Téléphone Entreprise', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text" placeholder="01 23 45 67 89">
                    <p class="description"><?php _e('Numéro de téléphone formaté (affiché)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Téléphone Entreprise (Brut)', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="company_phone_raw" value="<?php echo esc_attr($company_phone_raw); ?>" class="regular-text" placeholder="0123456789">
                    <p class="description"><?php _e('Numéro de téléphone sans formatage (pour liens tel:)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Services', 'osmose-ads'); ?></th>
                <td>
                    <div id="services-list">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $index => $service): ?>
                                <div class="service-item">
                                    <input type="text" name="services[]" value="<?php echo esc_attr($service); ?>" class="regular-text">
                                    <button type="button" class="button remove-service"><?php _e('Supprimer', 'osmose-ads'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-service" class="button"><?php _e('Ajouter un Service', 'osmose-ads'); ?></button>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-service').on('click', function() {
        var html = '<div class="service-item"><input type="text" name="services[]" class="regular-text" placeholder="Nom du service"><button type="button" class="button remove-service"><?php _e('Supprimer', 'osmose-ads'); ?></button></div>';
        $('#services-list').append(html);
    });
    
    $(document).on('click', '.remove-service', function() {
        $(this).parent().remove();
    });
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

