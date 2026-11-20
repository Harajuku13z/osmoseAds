<?php
if (!defined('ABSPATH')) {
    exit;
}
$oa_manual = 'manual' === OA_Settings::get('ai_provider');
?>
<div id="oa-queue">
    <section class="oa-card">
        <div class="oa-card__head">
            <div>
                <h2>Progression</h2>
                <p class="oa-muted" id="oa-q-sub">
                    <?php if ($oa_manual) : ?>
                        Mode ChatGPT manuel : ouvrez chaque tâche « À coller », copiez le prompt dans ChatGPT puis collez la réponse.
                    <?php elseif (OA_Settings::get('background')) : ?>
                        La file tourne aussi en arrière-plan (WP-Cron) : vous pouvez fermer cette page.
                    <?php else : ?>
                        Gardez cette page ouverte pendant la génération.
                    <?php endif; ?>
                </p>
            </div>
            <div class="oa-actions-row">
                <?php if (!$oa_manual) : ?>
                    <button type="button" class="button button-primary" id="oa-q-start">▶ Démarrer</button>
                    <button type="button" class="button" id="oa-q-stop" hidden>⏸ Pause</button>
                <?php else : ?>
                    <button type="button" class="button button-primary" id="oa-q-prepare">Préparer les prompts</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="oa-progress"><i id="oa-q-bar" style="width:0"></i></div>
        <div class="oa-q-stats" id="oa-q-stats"></div>
    </section>

    <section class="oa-card">
        <div class="oa-card__head">
            <div class="oa-filters" id="oa-q-filter">
                <button type="button" class="is-active" data-status="">Toutes</button>
                <button type="button" data-status="pending">En attente</button>
                <button type="button" data-status="manual">À coller</button>
                <button type="button" data-status="done">Terminées</button>
                <button type="button" data-status="failed">Échecs</button>
            </div>
            <div class="oa-actions-row">
                <button type="button" class="button" data-bulk="retry_failed">Relancer les échecs</button>
                <button type="button" class="button" data-bulk="cancel_pending">Annuler l'attente</button>
                <button type="button" class="button" data-bulk="clear_done">Vider l'historique</button>
            </div>
        </div>
        <table class="oa-table oa-table--jobs">
            <thead><tr><th>Ville</th><th>Mot-clé</th><th>Statut</th><th>Score</th><th>Détail</th><th></th></tr></thead>
            <tbody id="oa-q-rows"><tr><td colspan="6" class="oa-muted">Chargement…</td></tr></tbody>
        </table>
    </section>
</div>

<div class="oa-modal" id="oa-manual" hidden>
    <div class="oa-modal__box">
        <button type="button" class="oa-modal__close" aria-label="Fermer">×</button>
        <h2>ChatGPT manuel</h2>
        <ol class="oa-steps-mini">
            <li>Copiez le prompt ci-dessous et collez-le dans une <b>nouvelle conversation</b> ChatGPT.</li>
            <li>Copiez <b>toute</b> la réponse de ChatGPT (le bloc JSON).</li>
            <li>Collez-la dans le second champ puis cliquez sur « Créer la page ».</li>
        </ol>
        <div class="oa-row"><button type="button" class="button" id="oa-manual-copy">Copier le prompt</button> <a class="button" href="https://chatgpt.com/" target="_blank" rel="noopener">Ouvrir ChatGPT ↗</a></div>
        <textarea id="oa-manual-prompt" rows="7" readonly></textarea>
        <textarea id="oa-manual-json" rows="7" placeholder="Collez ici la réponse JSON de ChatGPT"></textarea>
        <div class="oa-row"><button type="button" class="button button-primary" id="oa-manual-import">Créer la page</button> <span id="oa-manual-msg"></span></div>
    </div>
</div>
