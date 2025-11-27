<?php
/**
 * Page de liste des articles générés
 */

if (!defined('ABSPATH')) {
    exit;
}

// Traitement de la génération manuelle
if (isset($_POST['generate_article_manual']) && check_admin_referer('osmose_generate_article', 'osmose_generate_article_nonce')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/services/class-article-generator.php';
    $generator = new Osmose_Article_Generator();
    $result = $generator->generate_article();
    
    if ($result && !is_wp_error($result)) {
        echo '<div class="notice notice-success"><p>' . __('Article généré avec succès!', 'osmose-ads') . '</p></div>';
    } else {
        $error_msg = is_wp_error($result) ? $result->get_error_message() : __('Erreur lors de la génération de l\'article.', 'osmose-ads');
        echo '<div class="notice notice-error"><p>' . esc_html($error_msg) . '</p></div>';
    }
}

// Pagination
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;

// Requête des articles
$articles_query = new WP_Query(array(
    'post_type' => 'osmose_article',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
));

require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';
?>

<div class="wrap osmose-ads-admin">
    <h1><?php _e('Articles Générés', 'osmose-ads'); ?></h1>
    
    <div style="margin: 20px 0;">
        <form method="post" action="" style="display: inline-block;">
            <?php wp_nonce_field('osmose_generate_article', 'osmose_generate_article_nonce'); ?>
            <input type="submit" name="generate_article_manual" class="button button-primary" value="<?php _e('Générer un Article Maintenant', 'osmose-ads'); ?>">
        </form>
        <a href="<?php echo admin_url('admin.php?page=osmose-ads-articles-config'); ?>" class="button"><?php _e('Configuration', 'osmose-ads'); ?></a>
    </div>
    
    <?php if ($articles_query->have_posts()): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Titre', 'osmose-ads'); ?></th>
                    <th><?php _e('Date de publication', 'osmose-ads'); ?></th>
                    <th><?php _e('Statut', 'osmose-ads'); ?></th>
                    <th><?php _e('Actions', 'osmose-ads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($articles_query->have_posts()): $articles_query->the_post(); 
                    $post_id = get_the_ID();
                    $department = get_post_meta($post_id, 'article_department', true);
                    $city = get_post_meta($post_id, 'article_city', true);
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo get_edit_post_link($post_id); ?>"><?php the_title(); ?></a></strong>
                            <?php if ($department || $city): ?>
                                <br><small style="color: #666;">
                                    <?php if ($city): ?>
                                        <?php echo esc_html($city); ?>
                                    <?php endif; ?>
                                    <?php if ($department): ?>
                                        <?php echo ($city ? ' - ' : '') . esc_html($department); ?>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo get_the_date('d/m/Y à H:i'); ?></td>
                        <td>
                            <?php 
                            $status = get_post_status();
                            $status_labels = array(
                                'publish' => __('Publié', 'osmose-ads'),
                                'draft' => __('Brouillon', 'osmose-ads'),
                                'pending' => __('En attente', 'osmose-ads'),
                            );
                            echo isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small"><?php _e('Modifier', 'osmose-ads'); ?></a>
                            <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="button button-small"><?php _e('Voir', 'osmose-ads'); ?></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php
        $total_pages = $articles_query->max_num_pages;
        if ($total_pages > 1):
        ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $paged,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
    <?php else: ?>
        <p><?php _e('Aucun article généré pour le moment.', 'osmose-ads'); ?></p>
    <?php endif; ?>
</div>

<?php
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

