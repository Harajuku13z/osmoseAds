<?php
if (!defined('ABSPATH')) {
    exit;
}
$f_trade = isset($_GET['trade']) ? sanitize_key($_GET['trade']) : '';
$f_city  = isset($_GET['city']) ? sanitize_text_field(wp_unslash($_GET['city'])) : '';
$f_score = isset($_GET['score']) ? sanitize_key($_GET['score']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
$paged   = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

$mq = array(array('key' => '_oa_generated', 'value' => '1'));
if ($f_trade) {
    $mq[] = array('key' => '_oa_trade', 'value' => $f_trade);
}
if ($f_city) {
    $mq[] = array('key' => '_oa_city_name', 'value' => $f_city, 'compare' => 'LIKE');
}
if ('low' === $f_score) {
    $mq['score'] = array('key' => '_oa_score', 'value' => (int) OA_Settings::get('min_score'), 'compare' => '<', 'type' => 'NUMERIC');
} else {
    $mq['score'] = array('key' => '_oa_score', 'compare' => 'EXISTS', 'type' => 'NUMERIC');
}
$args = array(
    'post_type'      => 'page',
    'post_status'    => array('publish', 'future', 'draft', 'pending', 'private'),
    'posts_per_page' => 40,
    'paged'          => $paged,
    'meta_query'     => $mq,
);
if ('score' === $orderby) {
    $args['orderby'] = array('score' => 'ASC');
} elseif ('city' === $orderby) {
    $args['meta_key'] = '_oa_city_name';
    $args['orderby']  = 'meta_value';
    $args['order']    = 'ASC';
}
$q = new WP_Query($args);
$base = admin_url('admin.php?page=oa-pages');
?>
<section class="oa-card">
    <form class="oa-card__head oa-filters-form" method="get">
        <input type="hidden" name="page" value="oa-pages">
        <div class="oa-row">
            <select name="trade"><option value="">Tous les métiers</option>
                <?php foreach (OA_Trades::all() as $t) : ?>
                    <option value="<?php echo esc_attr($t['id']); ?>" <?php selected($f_trade, $t['id']); ?>><?php echo esc_html($t['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="city" value="<?php echo esc_attr($f_city); ?>" placeholder="Ville">
            <select name="score">
                <option value="">Tous les scores</option>
                <option value="low" <?php selected($f_score, 'low'); ?>>Sous le seuil (&lt; <?php echo (int) OA_Settings::get('min_score'); ?>)</option>
            </select>
            <select name="orderby">
                <option value="date" <?php selected($orderby, 'date'); ?>>Plus récentes</option>
                <option value="score" <?php selected($orderby, 'score'); ?>>Score croissant</option>
                <option value="city" <?php selected($orderby, 'city'); ?>>Ville A→Z</option>
            </select>
            <button class="button">Filtrer</button>
        </div>
        <span class="oa-muted"><?php echo number_format_i18n($q->found_posts); ?> page(s)</span>
    </form>

    <table class="oa-table oa-table--pages">
        <thead><tr><th>Score</th><th>Page</th><th>Ville</th><th>Mots</th><th>Unicité</th><th>Statut</th><th class="oa-right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$q->have_posts()) : ?>
            <tr><td colspan="7" class="oa-muted">Aucune page. <a href="<?php echo esc_url(admin_url('admin.php?page=oa-generate')); ?>">Générer des pages</a></td></tr>
        <?php endif; ?>
        <?php foreach ($q->posts as $p) :
            $sc     = (int) get_post_meta($p->ID, '_oa_score', true);
            $sim    = (int) get_post_meta($p->ID, '_oa_similarity', true);
            $checks = (array) get_post_meta($p->ID, '_oa_checks', true);
            $hub    = get_post_meta($p->ID, '_oa_hub', true);
            ?>
            <tr data-post="<?php echo (int) $p->ID; ?>">
                <td><button type="button" class="oa-score oa-score--<?php echo $sc >= 80 ? 'good' : ($sc >= 60 ? 'ok' : 'bad'); ?>" data-oa-toggle title="Voir le détail"><?php echo $sc; ?></button></td>
                <td>
                    <strong><?php echo esc_html($p->post_title); ?></strong><?php echo $hub ? ' <span class="oa-tag">Page principale</span>' : ''; ?><br>
                    <small class="oa-muted">« <?php echo esc_html(get_post_meta($p->ID, '_oa_focus', true)); ?> » · /<?php echo esc_html($p->post_name); ?>/</small>
                </td>
                <td><?php echo esc_html(get_post_meta($p->ID, '_oa_city_name', true)); ?></td>
                <td><?php echo (int) get_post_meta($p->ID, '_oa_words', true); ?></td>
                <td><span class="oa-uniq oa-uniq--<?php echo $sim <= (int) OA_Settings::get('max_similarity') ? 'good' : 'bad'; ?>"><?php echo 100 - $sim; ?> %</span></td>
                <td><span class="oa-status oa-status--<?php echo esc_attr($p->post_status); ?>"><?php echo esc_html(get_post_status_object($p->post_status)->label); ?></span><?php echo 'future' === $p->post_status ? '<br><small class="oa-muted">' . esc_html(get_the_date('d/m H:i', $p)) . '</small>' : ''; ?></td>
                <td class="oa-right oa-nowrap">
                    <a class="button button-small" href="<?php echo esc_url(get_permalink($p)); ?>" target="_blank">Voir</a>
                    <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>">Éditer</a>
                    <button type="button" class="button button-small" data-oa-reanalyze>Ré-analyser</button>
                    <button type="button" class="button button-small" data-oa-regen>Régénérer</button>
                </td>
            </tr>
            <tr class="oa-checks-row" hidden>
                <td colspan="7">
                    <div class="oa-checks">
                        <?php foreach (array('seo' => 'SEO', 'read' => 'Lisibilité') as $g => $gl) : ?>
                            <div><h4><?php echo esc_html($gl); ?></h4><ul>
                            <?php foreach ($checks as $c) : if (!is_array($c) || $c['group'] !== $g) { continue; } ?>
                                <li class="<?php echo $c['ok'] ? 'ok' : 'ko'; ?>"><?php echo $c['ok'] ? '✓' : '✗'; ?> <?php echo esc_html($c['label']); ?> <small><?php echo esc_html($c['detail']); ?></small></li>
                            <?php endforeach; ?>
                            </ul></div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    if ($q->max_num_pages > 1) {
        echo '<div class="oa-pagination">' . paginate_links(array(
            'base'    => add_query_arg('paged', '%#%'),
            'format'  => '',
            'current' => $paged,
            'total'   => $q->max_num_pages,
        )) . '</div>';
    }
    ?>
</section>
