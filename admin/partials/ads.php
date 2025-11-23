<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

$ads = get_posts(array(
    'post_type' => 'ad',
    'posts_per_page' => 50,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC',
));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
</div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Titre', 'osmose-ads'); ?></th>
                <th><?php _e('Ville', 'osmose-ads'); ?></th>
                <th><?php _e('Template', 'osmose-ads'); ?></th>
                <th><?php _e('Statut', 'osmose-ads'); ?></th>
                <th><?php _e('Date', 'osmose-ads'); ?></th>
                <th><?php _e('Actions', 'osmose-ads'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ads)): ?>
                <?php foreach ($ads as $ad): 
                    $city_id = get_post_meta($ad->ID, 'city_id', true);
                    $template_id = get_post_meta($ad->ID, 'template_id', true);
                    $city = $city_id ? get_post($city_id) : null;
                    $template = $template_id ? get_post($template_id) : null;
                    $status = get_post_meta($ad->ID, 'status', true);
                    if (!$status) {
                        $status = $ad->post_status === 'publish' ? 'published' : 'draft';
                    }
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($ad->post_title); ?></strong></td>
                        <td><?php echo $city ? esc_html($city->post_title) : '—'; ?></td>
                        <td><?php echo $template ? esc_html($template->post_title) : '—'; ?></td>
                        <td>
                            <?php 
                            $status_labels = array(
                                'published' => __('Publié', 'osmose-ads'),
                                'draft' => __('Brouillon', 'osmose-ads'),
                                'archived' => __('Archivé', 'osmose-ads'),
                            );
                            echo esc_html($status_labels[$status] ?? $status);
                            ?>
                        </td>
                        <td><?php echo esc_html(get_the_date('d/m/Y', $ad->ID)); ?></td>
                        <td>
                            <a href="<?php echo get_permalink($ad->ID); ?>" target="_blank"><?php _e('Voir', 'osmose-ads'); ?></a> |
                            <a href="<?php echo get_edit_post_link($ad->ID); ?>"><?php _e('Modifier', 'osmose-ads'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6"><?php _e('Aucune annonce trouvée', 'osmose-ads'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

