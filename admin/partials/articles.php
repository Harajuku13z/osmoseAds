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
        // Stocker le message de succès dans un transient
        set_transient('osmose_article_generation_success', array(
            'message' => __('Article généré avec succès!', 'osmose-ads'),
            'article_id' => $result,
        ), 30);
        
        // Rediriger pour éviter la double soumission
        wp_redirect(add_query_arg('generated', '1', admin_url('admin.php?page=osmose-ads-articles')));
        exit;
    } else {
        // Stocker le message d'erreur dans un transient
        $error_msg = is_wp_error($result) ? $result->get_error_message() : __('Erreur lors de la génération de l\'article.', 'osmose-ads');
        set_transient('osmose_article_generation_error', $error_msg, 30);
        
        // Rediriger pour éviter la double soumission
        wp_redirect(add_query_arg('error', '1', admin_url('admin.php?page=osmose-ads-articles')));
        exit;
    }
}

// Traitement de la suppression des notifications d'échec
if (isset($_POST['dismiss_failure_notice']) && check_admin_referer('osmose_dismiss_failure', 'osmose_dismiss_failure_nonce')) {
    $failure_id = isset($_POST['failure_id']) ? intval($_POST['failure_id']) : -1;
    if ($failure_id >= 0) {
        $failures = get_option('osmose_articles_generation_failures', array());
        if (isset($failures[$failure_id])) {
            unset($failures[$failure_id]);
            $failures = array_values($failures); // Réindexer
            update_option('osmose_articles_generation_failures', $failures);
            echo '<div class="notice notice-success"><p>' . __('Notification d\'échec supprimée.', 'osmose-ads') . '</p></div>';
        }
    }
}

// Récupérer les échecs de génération récents
$failures = get_option('osmose_articles_generation_failures', array());
$recent_failures = array();

// Filtrer les échecs des dernières 24 heures
$last_24h = current_time('timestamp') - (24 * 60 * 60);
foreach ($failures as $index => $failure) {
    if (isset($failure['timestamp']) && $failure['timestamp'] >= $last_24h) {
        $recent_failures[] = array_merge($failure, array('index' => $index));
    }
}

// Vérifier si le cron devrait avoir généré des articles aujourd'hui
$auto_generate = get_option('osmose_articles_auto_generate', 0);
if ($auto_generate) {
    $publish_hours = get_option('osmose_articles_publish_hours', array());
    $articles_per_day = get_option('osmose_articles_per_day', 1);
    
    if (!empty($publish_hours) && $articles_per_day > 0) {
        $today = current_time('Y-m-d');
        $current_time = current_time('timestamp');
        
        // Vérifier chaque heure prévue aujourd'hui
        foreach ($publish_hours as $hour) {
            $time_parts = explode(':', $hour);
            $hour_int = intval($time_parts[0]);
            $minute_int = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
            
            $scheduled_time = mktime($hour_int, $minute_int, 0, date('n'), date('j'), date('Y'));
            
            // Si l'heure est passée aujourd'hui (avec une marge de 5 minutes)
            if ($scheduled_time < ($current_time - 300) && $scheduled_time >= strtotime('today')) {
                // Vérifier si des articles ont été créés à cette heure ou après
                $articles_after_time = get_posts(array(
                    'post_type' => 'osmose_article',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'date_query' => array(
                        array(
                            'after' => date('Y-m-d H:i:s', $scheduled_time),
                            'inclusive' => true,
                        ),
                    ),
                    'meta_query' => array(
                        array(
                            'key' => 'article_auto_generated',
                            'value' => '1',
                            'compare' => '=',
                        ),
                    ),
                ));
                
                // Si aucun article n'a été créé après cette heure, c'est un échec potentiel
                if (empty($articles_after_time)) {
                    // Vérifier si cet échec n'est pas déjà enregistré
                    $already_recorded = false;
                    foreach ($failures as $failure) {
                        if (isset($failure['timestamp']) && abs($failure['timestamp'] - $scheduled_time) < 600) {
                            $already_recorded = true;
                            break;
                        }
                    }
                    
                    if (!$already_recorded) {
                        $recent_failures[] = array(
                            'index' => -1, // Nouveau, pas encore enregistré
                            'date' => date('Y-m-d H:i:s', $scheduled_time),
                            'timestamp' => $scheduled_time,
                            'expected_count' => $articles_per_day,
                            'errors' => array(__('Aucun article généré à l\'heure prévue', 'osmose-ads')),
                            'partial' => false,
                            'detected' => true, // Détecté par vérification, pas enregistré par le cron
                        );
                    }
                }
            }
        }
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

// Afficher les messages de succès/erreur après le header
$success_message = get_transient('osmose_article_generation_success');
if ($success_message !== false) {
    delete_transient('osmose_article_generation_success');
    $article_id = isset($success_message['article_id']) ? $success_message['article_id'] : 0;
    $article_link = $article_id ? get_edit_post_link($article_id) : '';
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong><?php echo esc_html($success_message['message']); ?></strong>
            <?php if ($article_link): ?>
                <a href="<?php echo esc_url($article_link); ?>" class="button button-small" style="margin-left: 10px;">
                    <?php _e('Voir l\'article', 'osmose-ads'); ?>
                </a>
            <?php endif; ?>
        </p>
    </div>
    <?php
}

$error_message = get_transient('osmose_article_generation_error');
if ($error_message !== false) {
    delete_transient('osmose_article_generation_error');
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php echo esc_html($error_message); ?></strong></p>
    </div>
    <?php
}
?>

<div class="wrap osmose-ads-admin">
    <h1><?php _e('Articles Générés', 'osmose-ads'); ?></h1>
    
    <?php if (!empty($recent_failures)): ?>
        <?php foreach ($recent_failures as $failure): ?>
            <div class="notice notice-error is-dismissible" style="border-left-color: #dc3232; padding: 12px;">
                <p style="margin: 0; font-weight: 600;">
                    <span class="dashicons dashicons-warning" style="color: #dc3232; vertical-align: middle;"></span>
                    <?php _e('Échec de génération d\'article', 'osmose-ads'); ?>
                </p>
                <p style="margin: 8px 0 0 0;">
                    <?php 
                    $date_formatted = date_i18n('d/m/Y à H:i', $failure['timestamp']);
                    $expected = $failure['expected_count'];
                    $partial = isset($failure['partial']) && $failure['partial'];
                    
                    if ($partial) {
                        printf(
                            __('Le %s, %d article(s) sur %d attendu(s) n\'ont pas pu être généré(s).', 'osmose-ads'),
                            $date_formatted,
                            $expected,
                            $expected
                        );
                    } else {
                        printf(
                            __('Le %s, aucun article n\'a pu être généré (%d attendu(s)).', 'osmose-ads'),
                            $date_formatted,
                            $expected
                        );
                    }
                    
                    if (!empty($failure['errors'])) {
                        echo '<br><small style="color: #666;">';
                        echo __('Erreur(s): ', 'osmose-ads') . esc_html(implode(', ', array_slice($failure['errors'], 0, 3)));
                        if (count($failure['errors']) > 3) {
                            echo ' ' . sprintf(__('et %d autre(s)', 'osmose-ads'), count($failure['errors']) - 3);
                        }
                        echo '</small>';
                    }
                    ?>
                </p>
                <?php if (isset($failure['detected']) && $failure['detected']): ?>
                    <p style="margin: 8px 0 0 0; font-style: italic; color: #666;">
                        <small><?php _e('⚠️ Cet échec a été détecté automatiquement. Le cron WordPress peut ne pas s\'être exécuté.', 'osmose-ads'); ?></small>
                    </p>
                <?php else: ?>
                    <form method="post" action="" style="display: inline-block; margin-top: 8px;">
                        <?php wp_nonce_field('osmose_dismiss_failure', 'osmose_dismiss_failure_nonce'); ?>
                        <input type="hidden" name="failure_id" value="<?php echo esc_attr($failure['index']); ?>">
                        <input type="submit" name="dismiss_failure_notice" class="button button-small" value="<?php _e('Masquer cette notification', 'osmose-ads'); ?>">
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
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

