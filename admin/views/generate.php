<?php
if (!defined('ABSPATH')) {
    exit;
}
$oa_trades = OA_Trades::all();
?>
<div class="oa-gen" id="oa-gen">
    <div class="oa-gen__main">

        <section class="oa-card oa-step" data-step="1">
            <div class="oa-step__num">1</div>
            <div class="oa-step__body">
                <h2>Métier</h2>
                <p class="oa-muted">Le métier détermine les mots-clés proposés, le vocabulaire technique et le type schema.org.</p>
                <div class="oa-trade-grid">
                    <?php foreach ($oa_trades as $t) : ?>
                        <label class="oa-trade">
                            <input type="radio" name="oa_trade" value="<?php echo esc_attr($t['id']); ?>">
                            <span><strong><?php echo esc_html($t['name']); ?></strong><small><?php echo count($t['keywords']); ?> mots-clés<?php echo !empty($t['custom']) ? ' · perso' : ''; ?></small></span>
                        </label>
                    <?php endforeach; ?>
                    <a class="oa-trade oa-trade--add" href="<?php echo esc_url(admin_url('admin.php?page=oa-trades&edit=new')); ?>"><span><strong>+ Métier perso</strong><small>Créer vos mots-clés</small></span></a>
                </div>
            </div>
        </section>

        <section class="oa-card oa-step" data-step="2">
            <div class="oa-step__num">2</div>
            <div class="oa-step__body">
                <h2>Villes</h2>
                <p class="oa-muted">Données officielles (geo.api.gouv.fr) : code postal, département, population et communes voisines servent à personnaliser chaque page.</p>
                <div class="oa-search">
                    <input type="search" id="oa-city-q" placeholder="Nom de ville ou code postal (ex. Chalon-sur-Saône, 71100)" autocomplete="off">
                    <ul class="oa-search__results" id="oa-city-results" hidden></ul>
                </div>
                <div class="oa-chips" id="oa-cities"></div>

                <details class="oa-tools">
                    <summary>Ajouter les communes autour d'une ville</summary>
                    <div class="oa-row">
                        <label>Ville centre <select id="oa-near-center"><option value="">— ajoutez d'abord une ville —</option></select></label>
                        <label>Rayon <input type="number" id="oa-near-radius" value="20" min="1" max="100" step="1"> km</label>
                        <label>Population min. <input type="number" id="oa-near-pop" value="1000" min="0" step="500"></label>
                        <label>Départements voisins <input type="text" id="oa-near-depts" placeholder="ex. 21, 39" size="8"></label>
                        <button type="button" class="button" id="oa-near-go">Chercher</button>
                    </div>
                    <div id="oa-near-list" class="oa-near"></div>
                </details>

                <details class="oa-tools">
                    <summary>Coller une liste de villes</summary>
                    <textarea id="oa-bulk" rows="5" placeholder="Une ville ou un code postal par ligne"></textarea>
                    <button type="button" class="button" id="oa-bulk-go">Ajouter la liste</button>
                    <span id="oa-bulk-msg" class="oa-muted"></span>
                </details>
            </div>
        </section>

        <section class="oa-card oa-step" data-step="3">
            <div class="oa-step__num">3</div>
            <div class="oa-step__body">
                <div class="oa-card__head">
                    <h2>Mots-clés <span class="oa-counter" id="oa-kw-count">0 / <?php echo (int) OA_Trades::MAX_PER_CITY; ?></span></h2>
                    <button type="button" class="button" id="oa-kw-suggest" disabled>✨ Suggérer avec l'IA</button>
                </div>
                <p class="oa-muted">Une page par mot-clé et par ville, <?php echo (int) OA_Trades::MAX_PER_CITY; ?> maximum. Chaque page cible une intention différente : pas de cannibalisation. La <b>page principale</b> (ex. « Couvreur à … ») sert de hub et relie les autres.</p>
                <div class="oa-kw-list" id="oa-kw-list"><p class="oa-muted">Choisissez d'abord un métier.</p></div>
                <div class="oa-row oa-kw-add">
                    <input type="text" id="oa-kw-new" placeholder="Ajouter un mot-clé (ex. Pose de gouttières)">
                    <input type="text" id="oa-kw-new-sec" placeholder="Mots-clés secondaires, séparés par des virgules">
                    <button type="button" class="button" id="oa-kw-add">Ajouter</button>
                </div>
            </div>
        </section>

        <section class="oa-card oa-step" data-step="4">
            <div class="oa-step__num">4</div>
            <div class="oa-step__body">
                <h2>Photos</h2>
                <p class="oa-muted">Ajoutez vos photos de chantiers. Chaque page reçoit ses photos (image à la une, illustrations, galerie) avec des textes alternatifs optimisés ; l'ordre change d'une ville à l'autre. Les photos par mot-clé sont prioritaires sur la banque commune.</p>
                <div class="oa-photo-pool">
                    <div class="oa-photo-pool__head"><strong>Banque commune</strong> <button type="button" class="button" data-oa-photos="_pool">Choisir des photos</button></div>
                    <div class="oa-thumbs" data-oa-thumbs="_pool"></div>
                </div>
                <div id="oa-photo-kw"></div>
            </div>
        </section>
    </div>

    <aside class="oa-gen__side">
        <div class="oa-card oa-summary">
            <h2>Récapitulatif</h2>
            <dl>
                <dt>Métier</dt><dd id="oa-sum-trade">—</dd>
                <dt>Villes</dt><dd id="oa-sum-cities">0</dd>
                <dt>Mots-clés</dt><dd id="oa-sum-kw">0</dd>
                <dt>Photos</dt><dd id="oa-sum-photos">0</dd>
            </dl>
            <div class="oa-summary__total"><strong id="oa-sum-total">0</strong> pages à générer</div>
            <label class="oa-field">Statut
                <select id="oa-status">
                    <option value="">Selon réglages (<?php echo esc_html(OA_Settings::get('post_status')); ?>)</option>
                    <option value="publish">Publier</option>
                    <option value="draft">Brouillon</option>
                    <option value="pending">En attente de relecture</option>
                </select>
            </label>
            <label class="oa-check"><input type="checkbox" id="oa-skip" checked> Ignorer les pages déjà existantes</label>
            <?php if ((int) OA_Settings::get('drip_per_day') > 0) : ?>
                <p class="oa-muted">Publication progressive : <?php echo (int) OA_Settings::get('drip_per_day'); ?> pages / jour.</p>
            <?php endif; ?>
            <button type="button" class="button button-primary button-hero oa-wide" id="oa-launch" disabled>Lancer la génération</button>
            <p class="oa-muted oa-small" id="oa-estimate"></p>
        </div>
    </aside>
</div>
