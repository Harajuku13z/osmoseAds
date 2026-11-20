<?php
if (!defined('ABSPATH')) {
    exit;
}
$oa_current = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'oa-dashboard';
$oa_nav     = array(
    'oa-dashboard' => 'Tableau de bord',
    'oa-generate'  => 'Générer',
    'oa-queue'     => 'File d\'attente',
    'oa-pages'     => 'Pages & SEO',
    'oa-trades'    => 'Métiers',
    'oa-settings'  => 'Réglages',
);
$oa_stats = OA_Queue::stats();
$oa_fatal = get_option('oa_last_fatal');
?>
<header class="oa-top">
    <div class="oa-brand">
        <span class="oa-logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
        <div>
            <strong>Osmose Ads</strong>
            <span>Pages locales SEO · v<?php echo esc_html(OA_VERSION); ?></span>
        </div>
    </div>
    <nav class="oa-nav">
        <?php foreach ($oa_nav as $slug => $label) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" class="<?php echo $slug === $oa_current ? 'is-active' : ''; ?>">
                <?php echo esc_html($label); ?>
                <?php if ('oa-queue' === $slug && ($oa_stats['pending'] + $oa_stats['running'] + $oa_stats['manual']) > 0) : ?>
                    <em class="oa-pill"><?php echo (int) ($oa_stats['pending'] + $oa_stats['running'] + $oa_stats['manual']); ?></em>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
<?php if ($oa_fatal) : ?>
    <div class="oa-alert oa-alert--error"><strong>Génération interrompue :</strong> <?php echo esc_html($oa_fatal['message']); ?> — <a href="<?php echo esc_url(admin_url('admin.php?page=oa-settings#ia')); ?>">vérifier les réglages IA</a></div>
<?php endif; ?>
<?php if (!(new OA_AI())->is_ready()) : ?>
    <div class="oa-alert"><strong>IA non configurée.</strong> Ajoutez une clé API ou activez le mode « ChatGPT manuel » dans <a href="<?php echo esc_url(admin_url('admin.php?page=oa-settings#ia')); ?>">Réglages → IA</a>.</div>
<?php endif; ?>
