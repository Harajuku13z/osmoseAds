<?php
if (!defined('ABSPATH')) {
    exit;
}
$oa_all  = OA_Trades::all();
$edit_id = isset($_GET['edit']) ? sanitize_key($_GET['edit']) : '';
$from    = isset($_GET['from']) ? sanitize_key($_GET['from']) : '';
$editing = null;
if ('new' === $edit_id) {
    $editing = array('id' => '', 'name' => '', 'schema_type' => 'HomeAndConstructionBusiness', 'lexicon' => '', 'links' => array(), 'keywords' => array());
    if ($from && isset($oa_all[$from])) {
        $editing         = $oa_all[$from];
        $editing['id']   = $from;
    }
} elseif ($edit_id && isset($oa_all[$edit_id])) {
    $editing = $oa_all[$edit_id];
}
$kw_lines = function ($t) {
    $out = array();
    foreach ($t['keywords'] as $k) {
        $out[] = $k['label'] . ' | ' . implode(', ', (array) $k['secondary']) . ' | ' . $k['angle'];
    }
    return implode("\n", $out);
};
$schema_types = array('HomeAndConstructionBusiness', 'RoofingContractor', 'Plumber', 'Electrician', 'HVACBusiness', 'HousePainter', 'GeneralContractor', 'Locksmith', 'LandscapingBusiness', 'MovingCompany', 'ProfessionalService');
?>
<?php if (isset($_GET['saved'])) : ?><div class="oa-alert oa-alert--ok">Métier enregistré.</div><?php endif; ?>

<div class="oa-grid oa-grid--2">
    <section class="oa-card">
        <div class="oa-card__head"><h2>Bibliothèque de métiers</h2><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=oa-trades&edit=new')); ?>">+ Nouveau métier</a></div>
        <p class="oa-muted">Les métiers intégrés peuvent être personnalisés : cliquez sur « Personnaliser », vos modifications remplacent la version d'origine.</p>
        <table class="oa-table">
            <tbody>
            <?php foreach ($oa_all as $t) : ?>
                <tr>
                    <td><strong><?php echo esc_html($t['name']); ?></strong> <?php echo !empty($t['custom']) ? '<span class="oa-tag">perso</span>' : ''; ?><br>
                        <small class="oa-muted"><?php echo esc_html(implode(' · ', array_slice(wp_list_pluck($t['keywords'], 'label'), 0, 6))); ?><?php echo count($t['keywords']) > 6 ? ' …' : ''; ?></small></td>
                    <td class="oa-right oa-nowrap">
                        <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=oa-trades&edit=' . $t['id'])); ?>">Personnaliser</a>
                        <?php if (!empty($t['custom'])) : ?>
                            <a class="button button-small button-link-delete" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oa_delete_trade&trade_id=' . $t['id']), 'oa_trade_delete')); ?>" onclick="return confirm('Supprimer ce métier personnalisé ?');">Réinitialiser</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="oa-card">
        <?php if (!$editing) : ?>
            <h2>Comment ça marche ?</h2>
            <p>Pour chaque ville, Osmose Ads crée <b>jusqu'à <?php echo (int) OA_Trades::MAX_PER_CITY; ?> pages</b>, une par mot-clé. Exemple pour un couvreur à Chalon-sur-Saône :</p>
            <ul class="oa-list-plain">
                <li><b>Couvreur à Chalon-sur-Saône</b> — page principale (hub)</li>
                <li>Rénovation de toiture à Chalon-sur-Saône</li>
                <li>Réfection de toiture à Chalon-sur-Saône</li>
                <li>Zinguerie à Chalon-sur-Saône</li>
                <li>Traitement hydrofuge de toiture à Chalon-sur-Saône</li>
                <li>…</li>
            </ul>
            <p class="oa-muted">Chaque mot-clé a ses propres mots-clés secondaires et un angle éditorial : les pages se complètent au lieu de se concurrencer.</p>
        <?php else : ?>
            <h2><?php echo $editing['id'] ? 'Personnaliser : ' . esc_html($editing['name']) : 'Nouveau métier'; ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="oa-form">
                <input type="hidden" name="action" value="oa_save_trade">
                <input type="hidden" name="trade_id" value="<?php echo esc_attr($editing['id']); ?>">
                <?php wp_nonce_field('oa_trade'); ?>
                <label class="oa-field">Nom du métier <input type="text" name="name" required value="<?php echo esc_attr($editing['name']); ?>" placeholder="ex. Pisciniste"></label>
                <label class="oa-field">Type schema.org
                    <select name="schema_type"><?php foreach ($schema_types as $st) : ?><option <?php selected($editing['schema_type'], $st); ?>><?php echo esc_html($st); ?></option><?php endforeach; ?></select>
                </label>
                <label class="oa-field">Mots-clés — un par ligne : <code>Libellé | secondaires, séparés, par virgules | angle éditorial</code>
                    <textarea name="keywords" rows="12" required placeholder="Pisciniste | construction piscine, artisan pisciniste | présentation de l'entreprise&#10;Construction de piscine | piscine béton, piscine coque | étapes et budget"><?php echo esc_textarea($kw_lines($editing)); ?></textarea>
                </label>
                <label class="oa-check"><input type="checkbox" name="first_is_hub" value="1" checked> La première ligne est la page principale (hub)</label>
                <label class="oa-field">Champ lexical technique (aide l'IA à écrire comme un expert)
                    <textarea name="lexicon" rows="3"><?php echo esc_textarea($editing['lexicon']); ?></textarea>
                </label>
                <label class="oa-field">Liens externes d'autorité — un par ligne : <code>https://… | libellé</code>
                    <textarea name="links" rows="2"><?php foreach ((array) $editing['links'] as $u => $l) { echo esc_textarea($u . ' | ' . $l) . "\n"; } ?></textarea>
                </label>
                <button class="button button-primary">Enregistrer</button>
            </form>
        <?php endif; ?>
    </section>
</div>
