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
        <h2>ChatGPT (abonnement) <small id="oa-manual-left" class="oa-muted"></small></h2>
        <div class="oa-flow">
            <div class="oa-flow__step">
                <b>1</b>
                <div>
                    <button type="button" class="button button-primary button-hero" id="oa-manual-send" disabled>Envoyer à ChatGPT ↗</button>
                    <small>ChatGPT s'ouvre dans un nouvel onglet avec le prompt déjà rempli (il est aussi copié : si le champ est vide, faites <kbd>⌘V</kbd> / <kbd>Ctrl+V</kbd> puis Entrée).</small>
                </div>
            </div>
            <div class="oa-flow__step">
                <b>2</b>
                <div>
                    <button type="button" class="button button-primary button-hero" id="oa-manual-paste">Récupérer la réponse et créer la page</button>
                    <small>Dans ChatGPT, cliquez sur l'icône « Copier » du bloc de code, puis revenez ici : la réponse est détectée et la page créée automatiquement.</small>
                </div>
            </div>
            <label class="oa-check"><input type="checkbox" id="oa-manual-chain" checked> Enchaîner automatiquement avec la page suivante</label>
        </div>
        <p id="oa-manual-msg" class="oa-manual-msg"></p>
        <details class="oa-manual-raw">
            <summary>Afficher le prompt / coller la réponse à la main</summary>
            <textarea id="oa-manual-prompt" rows="6" readonly></textarea>
            <textarea id="oa-manual-json" rows="6" placeholder="Collez ici la réponse JSON de ChatGPT"></textarea>
            <div class="oa-row"><button type="button" class="button" id="oa-manual-copy">Copier le prompt</button> <button type="button" class="button" id="oa-manual-import">Créer la page</button></div>
        </details>
    </div>
</div>
