<?php
/**
 * Page de configuration pour la génération automatique d'articles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Traitement du formulaire
if (isset($_POST['osmose_articles_config_save']) && check_admin_referer('osmose_articles_config', 'osmose_articles_config_nonce')) {
    // Sauvegarder les mots-clés
    $keywords = isset($_POST['article_keywords']) ? sanitize_textarea_field($_POST['article_keywords']) : '';
    update_option('osmose_articles_keywords', $keywords);
    
    // Sauvegarder les villes favorites
    $favorite_cities = isset($_POST['favorite_cities']) ? array_map('intval', $_POST['favorite_cities']) : array();
    update_option('osmose_articles_favorite_cities', $favorite_cities);
    
    // Sauvegarder les départements
    $favorite_departments = isset($_POST['favorite_departments']) ? array_map('sanitize_text_field', $_POST['favorite_departments']) : array();
    update_option('osmose_articles_favorite_departments', $favorite_departments);
    
    // Sauvegarder le planning
    $articles_per_day = isset($_POST['articles_per_day']) ? intval($_POST['articles_per_day']) : 1;
    update_option('osmose_articles_per_day', $articles_per_day);
    
    $publish_hours = isset($_POST['publish_hours']) ? array_map('sanitize_text_field', $_POST['publish_hours']) : array();
    update_option('osmose_articles_publish_hours', $publish_hours);
    
    // Activer/désactiver la génération automatique
    $auto_generate = isset($_POST['auto_generate_enabled']) ? 1 : 0;
    update_option('osmose_articles_auto_generate', $auto_generate);
    
    echo '<div class="notice notice-success"><p>' . __('Configuration sauvegardée avec succès!', 'osmose-ads') . '</p></div>';
}

// Récupérer les valeurs actuelles
$keywords = get_option('osmose_articles_keywords', '');
$favorite_cities = get_option('osmose_articles_favorite_cities', array());
$favorite_departments = get_option('osmose_articles_favorite_departments', array());
$articles_per_day = get_option('osmose_articles_per_day', 1);
$publish_hours = get_option('osmose_articles_publish_hours', array('09:00', '14:00', '18:00'));
$auto_generate = get_option('osmose_articles_auto_generate', 0);

// Récupérer toutes les villes pour la sélection
$all_cities = get_posts(array(
    'post_type' => 'city',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// Récupérer les départements uniques
global $wpdb;
$departments = $wpdb->get_results(
    "SELECT DISTINCT pm.meta_value as department_code, 
            (SELECT pm2.meta_value FROM {$wpdb->postmeta} pm2 
             WHERE pm2.post_id = pm.post_id AND pm2.meta_key = 'department_name' LIMIT 1) as department_name
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'city' 
     AND p.post_status = 'publish'
     AND pm.meta_key = 'department'
     AND pm.meta_value != ''
     ORDER BY department_code ASC",
    ARRAY_A
);

require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';
?>

<div class="wrap osmose-ads-admin">
    <h1><?php _e('Configuration de la Génération d\'Articles', 'osmose-ads'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('osmose_articles_config', 'osmose_articles_config_nonce'); ?>
        
        <!-- Section Mots-clés -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Mots-clés pour la Génération', 'osmose-ads'); ?></h2>
            <p><?php _e('Entrez les mots-clés principaux (un par ligne) qui seront utilisés pour générer les titres et contenus d\'articles.', 'osmose-ads'); ?></p>
            <p class="description"><?php _e('Exemples : hydrofuger, couvreur, toiture, isolation, etc.', 'osmose-ads'); ?></p>
            <textarea name="article_keywords" rows="10" class="large-text" style="width: 100%;"><?php echo esc_textarea($keywords); ?></textarea>
        </div>
        
        <!-- Section Villes Favorites -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Villes Favorites', 'osmose-ads'); ?></h2>
            <p><?php _e('Sélectionnez les villes qui seront utilisées en priorité pour la génération d\'articles.', 'osmose-ads'); ?></p>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <?php if (!empty($all_cities)): ?>
                    <?php foreach ($all_cities as $city): 
                        $city_name = get_post_meta($city->ID, 'name', true) ?: $city->post_title;
                        $department = get_post_meta($city->ID, 'department', true);
                        $checked = in_array($city->ID, $favorite_cities) ? 'checked' : '';
                    ?>
                        <label class="d-block mb-1" style="cursor: pointer;">
                            <input type="checkbox" name="favorite_cities[]" value="<?php echo esc_attr($city->ID); ?>" <?php echo $checked; ?> class="me-2">
                            <span><?php echo esc_html($city_name . ($department ? ' (' . $department . ')' : '')); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('Aucune ville disponible. Importez d\'abord des villes depuis la page Villes.', 'osmose-ads'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Section Départements -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Départements Favorites', 'osmose-ads'); ?></h2>
            <p><?php _e('Sélectionnez les départements qui seront utilisés pour la génération d\'articles.', 'osmose-ads'); ?></p>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <?php if (!empty($departments)): ?>
                    <?php foreach ($departments as $dept): 
                        $dept_code = $dept['department_code'];
                        $dept_name = $dept['department_name'] ?: $dept_code;
                        $dept_label = $dept_code . ($dept_name != $dept_code ? ' - ' . $dept_name : '');
                        $checked = in_array($dept_code, $favorite_departments) ? 'checked' : '';
                    ?>
                        <label class="d-block mb-1" style="cursor: pointer;">
                            <input type="checkbox" name="favorite_departments[]" value="<?php echo esc_attr($dept_code); ?>" <?php echo $checked; ?> class="me-2">
                            <span><?php echo esc_html($dept_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('Aucun département disponible.', 'osmose-ads'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Section Planning -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Planning de Publication', 'osmose-ads'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="articles_per_day"><?php _e('Nombre d\'articles par jour', 'osmose-ads'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="articles_per_day" id="articles_per_day" value="<?php echo esc_attr($articles_per_day); ?>" min="1" max="50" class="small-text">
                        <p class="description"><?php _e('Nombre d\'articles à générer chaque jour automatiquement.', 'osmose-ads'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Heures de publication', 'osmose-ads'); ?></label>
                    </th>
                    <td>
                        <div id="publish-hours-container">
                            <?php if (!empty($publish_hours)): ?>
                                <?php foreach ($publish_hours as $hour): ?>
                                    <div class="publish-hour-item" style="margin-bottom: 5px;">
                                        <input type="time" name="publish_hours[]" value="<?php echo esc_attr($hour); ?>" class="regular-text">
                                        <button type="button" class="button remove-hour" style="margin-left: 5px;"><?php _e('Supprimer', 'osmose-ads'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="publish-hour-item" style="margin-bottom: 5px;">
                                    <input type="time" name="publish_hours[]" value="09:00" class="regular-text">
                                    <button type="button" class="button remove-hour" style="margin-left: 5px;"><?php _e('Supprimer', 'osmose-ads'); ?></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-publish-hour" class="button"><?php _e('Ajouter une heure', 'osmose-ads'); ?></button>
                        <p class="description"><?php _e('Heures auxquelles les articles seront publiés chaque jour.', 'osmose-ads'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="auto_generate_enabled"><?php _e('Génération automatique', 'osmose-ads'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_generate_enabled" id="auto_generate_enabled" value="1" <?php checked($auto_generate, 1); ?>>
                            <?php _e('Activer la génération automatique quotidienne via cron', 'osmose-ads'); ?>
                        </label>
                        <p class="description"><?php _e('Si activé, les articles seront générés automatiquement selon le planning défini.', 'osmose-ads'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="osmose_articles_config_save" class="button button-primary" value="<?php _e('Enregistrer la configuration', 'osmose-ads'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Ajouter une heure de publication
    $('#add-publish-hour').on('click', function() {
        var html = '<div class="publish-hour-item" style="margin-bottom: 5px;">' +
                   '<input type="time" name="publish_hours[]" value="09:00" class="regular-text">' +
                   '<button type="button" class="button remove-hour" style="margin-left: 5px;"><?php echo esc_js(__('Supprimer', 'osmose-ads')); ?></button>' +
                   '</div>';
        $('#publish-hours-container').append(html);
    });
    
    // Supprimer une heure de publication
    $(document).on('click', '.remove-hour', function() {
        if ($('.publish-hour-item').length > 1) {
            $(this).closest('.publish-hour-item').remove();
        } else {
            alert('<?php echo esc_js(__('Vous devez avoir au moins une heure de publication.', 'osmose-ads')); ?>');
        }
    });
});
</script>

<?php
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

