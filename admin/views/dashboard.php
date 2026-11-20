<?php
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$pm      = $wpdb->postmeta;
$pages_n = (int) $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p JOIN $pm m ON m.post_id = p.ID AND m.meta_key = '_oa_generated' WHERE p.post_type = 'page' AND p.post_status IN ('publish','future','draft','pending')");
$cities  = (int) $wpdb->get_var("SELECT COUNT(DISTINCT m.meta_value) FROM $pm m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_oa_city_code' AND p.post_status IN ('publish','future','draft','pending')");
$avg     = (int) round((float) $wpdb->get_var("SELECT AVG(CAST(m.meta_value AS UNSIGNED)) FROM $pm m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_oa_score' AND p.post_status IN ('publish','future','draft','pending')"));
$buckets = $wpdb->get_results("SELECT CASE WHEN CAST(m.meta_value AS UNSIGNED) >= 80 THEN 'good' WHEN CAST(m.meta_value AS UNSIGNED) >= 60 THEN 'ok' ELSE 'bad' END b, COUNT(*) n FROM $pm m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_oa_score' AND p.post_status IN ('publish','future','draft','pending') GROUP BY b", OBJECT_K);
$calls_t = OA_Install::table('calls');
$since   = gmdate('Y-m-d H:i:s', current_time('timestamp') - 30 * DAY_IN_SECONDS);
$calls   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $calls_t WHERE kind = 'call' AND created_at >= %s", $since));
$quotes  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $calls_t WHERE kind = 'quote' AND created_at >= %s", $since));
$top     = $wpdb->get_results($wpdb->prepare("SELECT post_id, SUM(kind='call') calls, SUM(kind='quote') quotes FROM $calls_t WHERE created_at >= %s GROUP BY post_id ORDER BY calls DESC, quotes DESC LIMIT 8", $since));
$future  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN $pm m ON m.post_id = p.ID AND m.meta_key = '_oa_generated' WHERE p.post_status = 'future'");
$usage   = get_option('oa_usage', array());
$month   = isset($usage[gmdate('Y-m')]) ? $usage[gmdate('Y-m')] : array('tokens' => 0, 'calls' => 0);
$recent  = get_posts(array('post_type' => 'page', 'post_status' => array('publish', 'future', 'draft'), 'posts_per_page' => 8, 'meta_key' => '_oa_generated_at', 'orderby' => 'meta_value', 'order' => 'DESC'));
$seo     = OA_Seo::active_plugin();
$b       = function ($k) use ($buckets) {
    return isset($buckets[$k]) ? (int) $buckets[$k]->n : 0;
};
$total_b = max(1, $b('good') + $b('ok') + $b('bad'));
?>
<div class="oa-grid oa-grid--kpi">
    <div class="oa-kpi"><span>Pages locales</span><strong><?php echo number_format_i18n($pages_n); ?></strong><small><?php echo number_format_i18n($cities); ?> villes couvertes</small></div>
    <div class="oa-kpi"><span>Score SEO moyen</span><strong class="oa-score-text oa-score-text--<?php echo $avg >= 80 ? 'good' : ($avg >= 60 ? 'ok' : 'bad'); ?>"><?php echo $pages_n ? (int) $avg : '—'; ?><em>/100</em></strong><small>Analyse type TruSEO</small></div>
    <div class="oa-kpi"><span>Appels (30 j)</span><strong><?php echo number_format_i18n($calls); ?></strong><small><?php echo number_format_i18n($quotes); ?> demandes de devis</small></div>
    <div class="oa-kpi"><span>File d'attente</span><strong><?php echo (int) ($oa_stats['pending'] + $oa_stats['running']); ?></strong><small><?php echo $future ? number_format_i18n($future) . ' pages programmées' : 'Aucune publication programmée'; ?></small></div>
</div>

<div class="oa-grid oa-grid--2">
    <section class="oa-card">
        <div class="oa-card__head"><h2>Dernières pages générées</h2><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=oa-pages')); ?>">Toutes les pages</a></div>
        <?php if (!$recent) : ?>
            <div class="oa-empty">
                <p><strong>Aucune page pour l'instant.</strong></p>
                <p>Choisissez un métier, des villes et jusqu'à 8 mots-clés : Osmose Ads rédige une page unique par combinaison.</p>
                <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=oa-generate')); ?>">Générer mes premières pages</a>
            </div>
        <?php else : ?>
            <table class="oa-table">
                <tbody>
                <?php foreach ($recent as $p) : $sc = (int) get_post_meta($p->ID, '_oa_score', true); ?>
                    <tr>
                        <td><span class="oa-score oa-score--<?php echo $sc >= 80 ? 'good' : ($sc >= 60 ? 'ok' : 'bad'); ?>"><?php echo $sc; ?></span></td>
                        <td><a href="<?php echo esc_url(get_permalink($p)); ?>" target="_blank"><?php echo esc_html($p->post_title); ?></a><br><small class="oa-muted"><?php echo esc_html(get_post_meta($p->ID, '_oa_city_name', true)); ?> · <?php echo (int) get_post_meta($p->ID, '_oa_words', true); ?> mots</small></td>
                        <td class="oa-right"><span class="oa-status oa-status--<?php echo esc_attr($p->post_status); ?>"><?php echo esc_html(get_post_status_object($p->post_status)->label); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <div class="oa-stack">
        <section class="oa-card">
            <h2>Qualité SEO</h2>
            <div class="oa-bars">
                <div class="oa-bar"><span>Excellent (≥ 80)</span><div><i class="good" style="width:<?php echo round($b('good') / $total_b * 100); ?>%"></i></div><b><?php echo $b('good'); ?></b></div>
                <div class="oa-bar"><span>Correct (60–79)</span><div><i class="ok" style="width:<?php echo round($b('ok') / $total_b * 100); ?>%"></i></div><b><?php echo $b('ok'); ?></b></div>
                <div class="oa-bar"><span>À améliorer (&lt; 60)</span><div><i class="bad" style="width:<?php echo round($b('bad') / $total_b * 100); ?>%"></i></div><b><?php echo $b('bad'); ?></b></div>
            </div>
            <p class="oa-muted">Plugin SEO détecté : <strong><?php echo esc_html($seo ? array('aioseo' => 'All in One SEO', 'yoast' => 'Yoast SEO', 'rankmath' => 'Rank Math')[$seo] : 'aucun (métadonnées gérées par Osmose Ads)'); ?></strong></p>
        </section>

        <section class="oa-card">
            <h2>Pages qui génèrent des contacts</h2>
            <?php if (!$top) : ?>
                <p class="oa-muted">Les clics sur « Appeler » et « Devis » de vos pages apparaîtront ici.</p>
            <?php else : ?>
                <table class="oa-table"><tbody>
                <?php foreach ($top as $t) : ?>
                    <tr><td><a href="<?php echo esc_url(get_permalink($t->post_id)); ?>" target="_blank"><?php echo esc_html(get_the_title($t->post_id)); ?></a></td><td class="oa-right"><b><?php echo (int) $t->calls; ?></b> ✆ · <?php echo (int) $t->quotes; ?> devis</td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </section>

        <section class="oa-card">
            <h2>IA ce mois-ci</h2>
            <p><strong><?php echo number_format_i18n($month['calls']); ?></strong> appels · <strong><?php echo number_format_i18n($month['tokens']); ?></strong> tokens · fournisseur <strong><?php echo esc_html(array('openai' => 'OpenAI', 'compatible' => 'API compatible', 'manual' => 'ChatGPT manuel', 'chatgpt' => 'Forfait ChatGPT')[OA_Settings::get('ai_provider')]); ?></strong> (<?php echo esc_html('chatgpt' === OA_Settings::get('ai_provider') ? OA_Settings::get('chatgpt_model') : OA_Settings::get('ai_model')); ?>)</p>
        </section>
    </div>
</div>
