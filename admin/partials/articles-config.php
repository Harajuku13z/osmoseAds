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
    
    // Sauvegarder les images pour articles
    $article_images = array();
    if (isset($_POST['article_images']) && is_array($_POST['article_images'])) {
        // Réindexer le tableau pour éviter les problèmes d'indices
        $article_images_raw = array_values($_POST['article_images']);
        foreach ($article_images_raw as $img_data) {
            // Vérifier que c'est bien un tableau
            if (!is_array($img_data)) {
                continue;
            }
            
            $img_id = isset($img_data['image_id']) ? intval($img_data['image_id']) : 0;
            $keywords = isset($img_data['keywords']) ? sanitize_text_field($img_data['keywords']) : '';
            
            // Ne sauvegarder que si on a au moins un image_id OU des keywords (pour permettre de sauvegarder les mots-clés avant de sélectionner l'image)
            if ($img_id > 0 || !empty($keywords)) {
                $article_images[] = array(
                    'image_id' => $img_id,
                    'keywords' => $keywords,
                );
            }
        }
    }
    
    // Debug: logger pour vérifier ce qui est reçu
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Osmose ADS: Article images POST data: ' . print_r($_POST['article_images'] ?? array(), true));
        error_log('Osmose ADS: Article images processed: ' . print_r($article_images, true));
    }
    
    update_option('osmose_articles_images', $article_images);
    
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
        
        <!-- Section Images pour Articles -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2 style="margin-top: 0;"><?php _e('Images pour les Articles', 'osmose-ads'); ?></h2>
            <p><?php _e('Sélectionnez des images qui seront insérées automatiquement dans les articles générés. Les images seront associées à des mots-clés pour être insérées dans les articles correspondants.', 'osmose-ads'); ?></p>
            
            <?php
            $article_images = get_option('osmose_articles_images', array());
            if (empty($article_images) || !is_array($article_images)) {
                $article_images = array();
            }
            ?>
            
            <div id="article-images-container">
                <?php 
                // Réindexer pour avoir des indices séquentiels (0, 1, 2...)
                $article_images = array_values($article_images);
                foreach ($article_images as $index => $img_data): 
                    $img_id = isset($img_data['image_id']) ? intval($img_data['image_id']) : 0;
                    $keywords = isset($img_data['keywords']) ? esc_attr($img_data['keywords']) : '';
                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
                ?>
                    <div class="article-image-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <div style="flex-shrink: 0;">
                                <?php if ($img_url): ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;" class="article-image-preview">
                                <?php else: ?>
                                    <div class="article-image-placeholder" style="width: 100px; height: 100px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <span style="color: #999;"><?php _e('Aucune image', 'osmose-ads'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <button type="button" class="button select-article-image" data-index="<?php echo $index; ?>">
                                    <?php _e('Sélectionner une image', 'osmose-ads'); ?>
                                </button>
                                <input type="hidden" name="article_images[<?php echo $index; ?>][image_id]" value="<?php echo $img_id; ?>" class="article-image-id" data-index="<?php echo $index; ?>">
                                <div style="margin-top: 10px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Mots-clés associés (séparés par des virgules)', 'osmose-ads'); ?>
                                    </label>
                                    <input type="text" name="article_images[<?php echo $index; ?>][keywords]" value="<?php echo $keywords; ?>" class="regular-text article-image-keywords" style="width: 100%;" placeholder="<?php _e('Ex: couvreur, toiture, isolation, hydrofuger...', 'osmose-ads'); ?>">
                                    <p class="description"><?php _e('Ces mots-clés seront utilisés pour insérer cette image dans les articles dont le titre contient ces mots.', 'osmose-ads'); ?></p>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="button remove-article-image"><?php _e('Supprimer', 'osmose-ads'); ?></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add-article-image" class="button" style="margin-top: 10px;">
                <?php _e('Ajouter une image', 'osmose-ads'); ?>
            </button>
            <p class="description"><?php _e('Au moins une image doit être configurée. Elle sera insérée automatiquement dans les articles générés.', 'osmose-ads'); ?></p>
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
    var articleImageFrames = {};
    var articleImageIndex = <?php echo count($article_images); ?>;
    
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
    
    // Ajouter une image pour articles
    $('#add-article-image').on('click', function() {
        var index = articleImageIndex++;
        var html = '<div class="article-image-item" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">' +
                   '<div style="display: flex; gap: 15px; align-items: flex-start;">' +
                   '<div style="flex-shrink: 0;">' +
                   '<div style="width: 100px; height: 100px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">' +
                   '<span style="color: #999;"><?php echo esc_js(__('Aucune image', 'osmose-ads')); ?></span>' +
                   '</div></div>' +
                   '<div style="flex-grow: 1;">' +
                   '<button type="button" class="button select-article-image" data-index="' + index + '"><?php echo esc_js(__('Sélectionner une image', 'osmose-ads')); ?></button>' +
                   '<input type="hidden" name="article_images[' + index + '][image_id]" value="0" class="article-image-id">' +
                   '<div style="margin-top: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_js(__('Mots-clés associés (séparés par des virgules)', 'osmose-ads')); ?></label>' +
                   '<input type="text" name="article_images[' + index + '][keywords]" value="" class="regular-text" style="width: 100%;" placeholder="<?php echo esc_js(__('Ex: couvreur, toiture, isolation, hydrofuger...', 'osmose-ads')); ?>">' +
                   '<p class="description"><?php echo esc_js(__('Ces mots-clés seront utilisés pour insérer cette image dans les articles dont le titre contient ces mots.', 'osmose-ads')); ?></p>' +
                   '</div></div>' +
                   '<div><button type="button" class="button remove-article-image"><?php echo esc_js(__('Supprimer', 'osmose-ads')); ?></button></div>' +
                   '</div></div>';
        $('#article-images-container').append(html);
    });
    
    // Sélectionner une image pour articles
    $(document).on('click', '.select-article-image', function() {
        var index = $(this).data('index');
        var $item = $(this).closest('.article-image-item');
        var $imgContainer = $item.find('div:first-child');
        var $input = $item.find('.article-image-id[data-index="' + index + '"]');
        
        // Créer une clé unique pour ce frame
        var frameKey = 'frame_' + index;
        
        if (articleImageFrames[frameKey]) {
            articleImageFrames[frameKey].open();
            return;
        }
        
        var frame = wp.media({
            title: '<?php echo esc_js(__('Sélectionner une image pour les articles', 'osmose-ads')); ?>',
            button: {
                text: '<?php echo esc_js(__('Utiliser cette image', 'osmose-ads')); ?>'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            
            // Mettre à jour l'aperçu de l'image
            if ($imgContainer.find('.article-image-placeholder').length > 0) {
                $imgContainer.find('.article-image-placeholder').replaceWith('<img src="' + attachment.url + '" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;" class="article-image-preview">');
            } else if ($imgContainer.find('.article-image-preview').length > 0) {
                $imgContainer.find('.article-image-preview').attr('src', attachment.url);
            } else {
                $imgContainer.html('<img src="' + attachment.url + '" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;" class="article-image-preview">');
            }
        });
        
        frame.open();
        articleImageFrames[frameKey] = frame;
    });
    
    // Supprimer une image pour articles
    $(document).on('click', '.remove-article-image', function() {
        $(this).closest('.article-image-item').remove();
        
        // Réindexer les indices après suppression
        reindexArticleImages();
    });
    
    // Fonction pour réindexer les images après suppression
    function reindexArticleImages() {
        $('#article-images-container .article-image-item').each(function(newIndex) {
            var $item = $(this);
            
            // Mettre à jour l'index du bouton
            $item.find('.select-article-image').attr('data-index', newIndex);
            
            // Mettre à jour les noms des champs avec les bons indices
            $item.find('.article-image-id').attr('name', 'article_images[' + newIndex + '][image_id]').attr('data-index', newIndex);
            $item.find('.article-image-keywords').attr('name', 'article_images[' + newIndex + '][keywords]');
        });
        
        // Mettre à jour le compteur pour les nouvelles images (utiliser le max des indices existants + 1)
        var maxIndex = -1;
        $('#article-images-container .article-image-item').each(function() {
            var currentIndex = parseInt($(this).find('.select-article-image').attr('data-index')) || 0;
            if (currentIndex > maxIndex) {
                maxIndex = currentIndex;
            }
        });
        articleImageIndex = maxIndex + 1;
    }
    
    // Réindexer au chargement de la page pour s'assurer que les indices sont corrects
    reindexArticleImages();
});
</script>

<?php
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

