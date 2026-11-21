<?php
if (!defined('ABSPATH')) {
    exit;
}
$s    = OA_Settings::all();
$name = function ($k) {
    return 'oa[' . $k . ']';
};
$logo = $s['logo_id'] ? wp_get_attachment_image_url($s['logo_id'], 'thumbnail') : '';
$pages_dd = wp_dropdown_pages(array(
    'name'              => 'oa[parent_page]',
    'selected'          => (int) $s['parent_page'],
    'show_option_none'  => '— Aucune (racine du site) —',
    'option_none_value' => 0,
    'echo'              => 0,
));
?>
<?php if (isset($_GET['saved'])) : ?><div class="oa-alert oa-alert--ok">Réglages enregistrés.</div><?php endif; ?>
<?php $oa_cg_notice = OA_ChatGPT::pull_notice(); if ($oa_cg_notice) : ?>
    <div class="oa-alert oa-alert--<?php echo 'success' === $oa_cg_notice['type'] ? 'ok' : ('warning' === $oa_cg_notice['type'] ? 'warn' : 'error'); ?>"><?php echo esc_html($oa_cg_notice['message']); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="oa-settings" id="oa-settings">
    <input type="hidden" name="action" value="oa_save_settings">
    <input type="hidden" name="tab" value="" id="oa-tab-input">
    <?php wp_nonce_field('oa_settings'); ?>

    <nav class="oa-tabs" role="tablist">
        <a href="#entreprise" class="is-active">Entreprise</a>
        <a href="#ia">Intelligence artificielle</a>
        <a href="#contenu">Contenu</a>
        <a href="#publication">Publication</a>
        <a href="#seo">SEO & design</a>
    </nav>

    <section class="oa-card oa-tab" id="tab-entreprise">
        <h2>Votre entreprise</h2>
        <p class="oa-muted">Ces informations alimentent les textes, les boutons d'appel, la barre mobile et le schema LocalBusiness. Elles se mettent à jour sur toutes les pages sans régénération.</p>
        <div class="oa-form-grid">
            <label class="oa-field">Nom commercial <input type="text" name="<?php echo $name('company_name'); ?>" value="<?php echo esc_attr($s['company_name']); ?>"></label>
            <label class="oa-field">Téléphone <input type="tel" name="<?php echo $name('phone'); ?>" value="<?php echo esc_attr($s['phone']); ?>" placeholder="03 85 00 00 00"></label>
            <label class="oa-field">E-mail <input type="email" name="<?php echo $name('email'); ?>" value="<?php echo esc_attr($s['email']); ?>"></label>
            <label class="oa-field">Page de devis / contact <input type="url" name="<?php echo $name('quote_url'); ?>" value="<?php echo esc_attr($s['quote_url']); ?>" placeholder="<?php echo esc_attr(home_url('/contact/')); ?>"></label>
            <label class="oa-field">Adresse <input type="text" name="<?php echo $name('address'); ?>" value="<?php echo esc_attr($s['address']); ?>"></label>
            <label class="oa-field">Ville du siège <input type="text" name="<?php echo $name('base_city'); ?>" value="<?php echo esc_attr($s['base_city']); ?>"></label>
            <label class="oa-field">Code postal <input type="text" name="<?php echo $name('postal_code'); ?>" value="<?php echo esc_attr($s['postal_code']); ?>"></label>
            <label class="oa-field">Années d'expérience <input type="number" name="<?php echo $name('years'); ?>" value="<?php echo esc_attr($s['years']); ?>" min="0"></label>
        </div>
        <label class="oa-field">Garanties & certifications réelles (une par ligne) <small>— seules celles-ci seront citées</small>
            <textarea name="<?php echo $name('certifications'); ?>" rows="3"><?php echo esc_textarea($s['certifications']); ?></textarea>
        </label>
        <label class="oa-field">Points forts réels (un par ligne)
            <textarea name="<?php echo $name('usps'); ?>" rows="3" placeholder="Intervention sous 48 h&#10;Équipe salariée, pas de sous-traitance&#10;Nettoyage du chantier inclus"><?php echo esc_textarea($s['usps']); ?></textarea>
        </label>
        <div class="oa-form-grid">
            <div class="oa-field">Logo
                <div class="oa-logo-pick">
                    <img id="oa-logo-img" src="<?php echo esc_url($logo); ?>" alt="" <?php echo $logo ? '' : 'hidden'; ?>>
                    <input type="hidden" name="<?php echo $name('logo_id'); ?>" id="oa-logo-id" value="<?php echo (int) $s['logo_id']; ?>">
                    <button type="button" class="button" id="oa-logo-btn">Choisir</button>
                </div>
            </div>
            <label class="oa-field">Note moyenne réelle (avis Google) <input type="text" name="<?php echo $name('rating_value'); ?>" value="<?php echo esc_attr($s['rating_value']); ?>" placeholder="4.8"></label>
            <label class="oa-field">Nombre d'avis <input type="number" name="<?php echo $name('rating_count'); ?>" value="<?php echo esc_attr($s['rating_count']); ?>"></label>
        </div>
    </section>

    <section class="oa-card oa-tab" id="tab-ia" hidden>
        <h2>Intelligence artificielle</h2>
        <div class="oa-providers">
            <?php
            $providers = array(
                'chatgpt'    => array('ChatGPT — mon abonnement', 'Connectez votre compte ChatGPT (Go, Plus, Pro ou Business) : les pages sont générées automatiquement sur votre forfait, sans clé API.'),
                'openai'     => array('OpenAI (ChatGPT) — API', 'Recommandé pour la génération en masse. Facturation à l\'usage sur platform.openai.com (quelques centimes par page avec un modèle « mini »).'),
                'manual'     => array('ChatGPT manuel (abonnement)', 'Utilise votre abonnement ChatGPT Plus/Pro : un clic ouvre ChatGPT avec le prompt prêt, un clic récupère la réponse et crée la page, puis enchaîne la suivante. Sans clé API ni coût supplémentaire.'),
                'compatible' => array('API compatible OpenAI', 'OpenRouter, Mistral, Groq, serveur local… Indiquez l\'URL de base et la clé.'),
            );
            foreach ($providers as $k => $p) :
                ?>
                <label class="oa-provider">
                    <input type="radio" name="<?php echo $name('ai_provider'); ?>" value="<?php echo esc_attr($k); ?>" <?php checked($s['ai_provider'], $k); ?>>
                    <span><strong><?php echo esc_html($p[0]); ?></strong><small><?php echo esc_html($p[1]); ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
        $oa_cg_ready = OA_ChatGPT::is_configured();
        $oa_cg_acc   = OA_ChatGPT::account();
        $oa_cg_on    = OA_ChatGPT::is_connected();
        ?>
        <div class="oa-connect oa-siwc" data-oa-show="chatgpt">
            <div class="oa-connect__head">
                <strong>Continuer avec ChatGPT</strong>
                <span class="oa-pill <?php echo ($oa_cg_on && $oa_cg_acc['plan']) ? 'is-ok' : 'is-off'; ?>"><?php echo $oa_cg_on ? ($oa_cg_acc['plan'] ? '● Forfait connecté' : '◐ Forfait non autorisé') : '○ Non connecté'; ?></span>
            </div>
            <?php if (!$oa_cg_ready) : ?>
                <p class="oa-alert oa-alert--warn">OpenAI réserve « Se connecter avec ChatGPT » aux partenaires approuvés. Dès que vous recevez votre identifiant client (<code>oaiapp_…</code>), collez-le ci-dessous et enregistrez : le bouton de connexion apparaîtra.</p>
                <p><a class="button" href="<?php echo esc_url(OA_ChatGPT::FORM_URL); ?>" target="_blank" rel="noopener noreferrer">Demander l'accès à OpenAI ↗</a></p>
            <?php elseif (!$oa_cg_on) : ?>
                <p><a class="oa-siwc-btn" data-oai-siwc-button data-oai-siwc-button-client="<?php echo esc_attr(OA_ChatGPT::client_id()); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oa_chatgpt_start'), 'oa_chatgpt_start')); ?>">Continuer avec ChatGPT</a></p>
                <p class="oa-muted">Vous serez redirigé vers ChatGPT pour vous connecter, puis choisir d'autoriser Osmose Ads à utiliser votre forfait. Aucune clé API n'est créée ni partagée.</p>
            <?php else : ?>
                <p>Connecté en tant que <strong><?php echo esc_html($oa_cg_acc['name'] ?: $oa_cg_acc['email']); ?></strong><?php echo $oa_cg_acc['email'] && $oa_cg_acc['name'] ? ' (' . esc_html($oa_cg_acc['email']) . ')' : ''; ?><?php echo $oa_cg_acc['connected_at'] ? ' depuis le ' . esc_html(wp_date('j F Y', $oa_cg_acc['connected_at'])) : ''; ?>.</p>
                <?php if (!$oa_cg_acc['plan']) : ?>
                    <p class="oa-alert oa-alert--warn">L'utilisation de votre forfait n'a pas été autorisée : aucune page ne peut être générée par ce biais.</p>
                    <p><a class="oa-siwc-btn" data-oai-siwc-button href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oa_chatgpt_start&reconsent=1'), 'oa_chatgpt_start')); ?>">Autoriser mon forfait ChatGPT</a></p>
                <?php endif; ?>
                <label class="oa-field">Modèle
                    <span class="oa-inline"><input type="text" name="<?php echo $name('chatgpt_model'); ?>" id="oa-cg-model" value="<?php echo esc_attr($s['chatgpt_model']); ?>" list="oa-cg-models"><button type="button" class="button" id="oa-cg-models-btn">Lister</button></span>
                    <datalist id="oa-cg-models"></datalist>
                </label>
                <p class="oa-row">
                    <button type="button" class="button" id="oa-cg-test">Tester</button>
                    <a class="button" href="<?php echo esc_url(OA_ChatGPT::USAGE_URL); ?>" target="_blank" rel="noopener noreferrer">Voir ma consommation et fixer une limite ↗</a>
                    <a class="button-link oa-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oa_chatgpt_disconnect'), 'oa_chatgpt_disconnect')); ?>" onclick="return confirm('Déconnecter ChatGPT ?');">Déconnecter</a>
                </p>
                <p id="oa-cg-msg" class="oa-muted">Avec Plus, la limite de 5 heures est partagée avec votre usage de ChatGPT ; préférez un rythme de publication modéré.</p>
            <?php endif; ?>
            <?php if (!OA_ChatGPT::from_config()) : ?>
                <details class="oa-siwc-config" <?php echo $oa_cg_ready ? '' : 'open'; ?>>
                    <summary>Identifiant client OpenAI</summary>
                    <div class="oa-form-grid">
                        <label class="oa-field">Client ID <input type="text" name="<?php echo $name('chatgpt_client_id'); ?>" value="<?php echo esc_attr($s['chatgpt_client_id']); ?>" placeholder="oaiapp_…" autocomplete="off"></label>
                        <label class="oa-field">Client secret (si client confidentiel) <input type="password" name="<?php echo $name('chatgpt_client_secret'); ?>" value="<?php echo esc_attr(OA_Settings::masked_key('chatgpt_client_secret')); ?>" autocomplete="off"></label>
                    </div>
                    <p class="oa-muted">URL de retour à déclarer chez OpenAI, à l'identique : <code><?php echo esc_html(OA_ChatGPT::redirect_uri()); ?></code></p>
                </details>
            <?php endif; ?>
        </div>
        <?php $oa_connected = ('openai' === $s['ai_provider'] && '' !== (string) $s['ai_key']); ?>
        <div class="oa-connect" data-oa-show="openai">
            <div class="oa-connect__head">
                <strong>Connecter mon compte OpenAI</strong>
                <span id="oa-connect-status" class="oa-pill <?php echo $oa_connected ? 'is-ok' : 'is-off'; ?>"><?php echo $oa_connected ? '● Connecté' : '○ Non connecté'; ?></span>
            </div>
            <ol class="oa-connect__steps">
                <li>
                    <a class="button button-primary" href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">1. Ouvrir OpenAI et créer ma clé ↗</a>
                    <small>Connectez-vous avec votre compte ChatGPT habituel, cliquez « Create new secret key », copiez la clé (sk-…). Ajoutez quelques euros de crédit dans <a href="https://platform.openai.com/settings/organization/billing/overview" target="_blank" rel="noopener noreferrer">Billing</a> : l'API est facturée à part de l'abonnement ChatGPT.</small>
                </li>
                <li>
                    <span class="oa-inline"><input type="password" id="oa-connect-key" placeholder="2. Collez la clé ici : sk-…" autocomplete="off"><button type="button" class="button button-primary" id="oa-connect-btn">Connecter et activer</button></span>
                    <small id="oa-connect-msg" class="oa-muted">La clé est vérifiée, enregistrée et le mode OpenAI est activé automatiquement.</small>
                </li>
            </ol>
        </div>
        <div class="oa-form-grid" data-oa-show="openai,compatible">
            <label class="oa-field">Clé API <input type="password" name="<?php echo $name('ai_key'); ?>" value="<?php echo esc_attr(OA_Settings::masked_key()); ?>" autocomplete="off" placeholder="sk-…"></label>
            <label class="oa-field">Modèle
                <span class="oa-inline"><input type="text" name="<?php echo $name('ai_model'); ?>" id="oa-model" value="<?php echo esc_attr($s['ai_model']); ?>" list="oa-models"><button type="button" class="button" id="oa-models-btn">Lister</button></span>
                <datalist id="oa-models"></datalist>
            </label>
            <label class="oa-field" data-oa-show="compatible">URL de base <input type="url" name="<?php echo $name('ai_base_url'); ?>" value="<?php echo esc_attr($s['ai_base_url']); ?>"></label>
            <label class="oa-field">Effort de raisonnement (modèles GPT-5 / o)
                <select name="<?php echo $name('ai_reasoning'); ?>">
                    <?php foreach (array('minimal' => 'Minimal (le plus rapide)', 'low' => 'Faible (recommandé)', 'medium' => 'Moyen', 'high' => 'Élevé (lent)') as $v => $l) : ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($s['ai_reasoning'], $v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="oa-field">Délai max par requête (s) <input type="number" name="<?php echo $name('ai_timeout'); ?>" value="<?php echo (int) $s['ai_timeout']; ?>" min="30" max="600"></label>
        </div>
        <p data-oa-show="openai,compatible"><button type="button" class="button" id="oa-test-ai">Tester la connexion</button> <span id="oa-test-msg" class="oa-muted">Enregistrez avant de tester.</span></p>
    </section>

    <section class="oa-card oa-tab" id="tab-contenu" hidden>
        <h2>Rédaction</h2>
        <div class="oa-form-grid">
            <label class="oa-field">Longueur visée (mots) <input type="number" name="<?php echo $name('word_target'); ?>" value="<?php echo (int) $s['word_target']; ?>" min="600" max="3000" step="100"></label>
            <label class="oa-field">Ton <input type="text" name="<?php echo $name('tone'); ?>" value="<?php echo esc_attr($s['tone']); ?>"></label>
            <label class="oa-field">Narration
                <select name="<?php echo $name('person'); ?>"><option value="nous" <?php selected($s['person'], 'nous'); ?>>« Nous » (entreprise)</option><option value="je" <?php selected($s['person'], 'je'); ?>>« Je » (artisan indépendant)</option></select>
            </label>
        </div>
        <label class="oa-field">Consignes supplémentaires pour l'IA
            <textarea name="<?php echo $name('extra_rules'); ?>" rows="4" placeholder="ex. Toujours mentionner la visite technique gratuite. Ne jamais parler de prix."><?php echo esc_textarea($s['extra_rules']); ?></textarea>
        </label>
        <h3>Contrôle qualité automatique</h3>
        <div class="oa-form-grid">
            <label class="oa-field">Score SEO minimal <input type="number" name="<?php echo $name('min_score'); ?>" value="<?php echo (int) $s['min_score']; ?>" min="0" max="100"></label>
            <label class="oa-field">Similarité max. entre villes (%) <input type="number" name="<?php echo $name('max_similarity'); ?>" value="<?php echo (int) $s['max_similarity']; ?>" min="5" max="90"></label>
        </div>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('auto_retry'); ?>" value="1" <?php checked($s['auto_retry']); ?>> Relancer l'IA avec les corrections ciblées si le score ou l'unicité est insuffisant (1 relance max)</label>
    </section>

    <section class="oa-card oa-tab" id="tab-publication" hidden>
        <h2>Publication</h2>
        <div class="oa-form-grid">
            <label class="oa-field">Statut par défaut
                <select name="<?php echo $name('post_status'); ?>">
                    <option value="publish" <?php selected($s['post_status'], 'publish'); ?>>Publier</option>
                    <option value="draft" <?php selected($s['post_status'], 'draft'); ?>>Brouillon</option>
                    <option value="pending" <?php selected($s['post_status'], 'pending'); ?>>En attente de relecture</option>
                </select>
            </label>
            <label class="oa-field">Structure des URL
                <select name="<?php echo $name('url_mode'); ?>">
                    <option value="flat" <?php selected($s['url_mode'], 'flat'); ?>>À plat : /renovation-de-toiture-a-chalon-sur-saone/</option>
                    <option value="silo" <?php selected($s['url_mode'], 'silo'); ?>>Silo : /couvreur-a-chalon-sur-saone/renovation-de-toiture-a-…/</option>
                </select>
            </label>
            <label class="oa-field">Page parente <?php echo $pages_dd; // phpcs:ignore ?></label>
            <label class="oa-field">Publication progressive (pages / jour, 0 = immédiat) <input type="number" name="<?php echo $name('drip_per_day'); ?>" value="<?php echo (int) $s['drip_per_day']; ?>" min="0" max="500"></label>
            <label class="oa-field">Générations en parallèle <input type="number" name="<?php echo $name('workers'); ?>" value="<?php echo (int) $s['workers']; ?>" min="1" max="8"></label>
        </div>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('background'); ?>" value="1" <?php checked($s['background']); ?>> Continuer la génération en arrière-plan (WP-Cron), même page fermée</label>
        <p class="oa-muted">Conseil : publier des centaines de pages d'un coup peut paraître artificiel. Une publication progressive (ex. 20/jour) est plus naturelle pour Google.</p>
    </section>

    <section class="oa-card oa-tab" id="tab-seo" hidden>
        <h2>SEO</h2>
        <p class="oa-muted">Plugin détecté : <strong><?php echo esc_html(OA_Seo::active_plugin() ?: 'aucun'); ?></strong>. Les titres, méta-descriptions et expressions-clés sont écrits directement dans All in One SEO (et Yoast / Rank Math si présents).</p>
        <div class="oa-form-grid">
            <label class="oa-field">Suffixe du titre SEO (ajouté s'il tient dans 60 caractères) <input type="text" name="<?php echo $name('title_suffix'); ?>" value="<?php echo esc_attr($s['title_suffix']); ?>" placeholder="| <?php echo esc_attr($s['company_name']); ?>"></label>
        </div>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('schema'); ?>" value="1" <?php checked($s['schema']); ?>> Données structurées LocalBusiness + Service + FAQPage</label>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('external_links'); ?>" value="1" <?php checked($s['external_links']); ?>> Ajouter un lien externe d'autorité (France Rénov', Service-Public…)</label>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('nearby_links'); ?>" value="1" <?php checked($s['nearby_links']); ?>> Maillage vers le même service dans les communes voisines</label>

        <h2>Design</h2>
        <div class="oa-form-grid">
            <label class="oa-field">Couleur principale (bouton devis) <input type="color" name="<?php echo $name('brand_color'); ?>" value="<?php echo esc_attr($s['brand_color']); ?>"></label>
            <label class="oa-field">Couleur principale foncée (liens) <input type="color" name="<?php echo $name('brand_color2'); ?>" value="<?php echo esc_attr($s['brand_color2']); ?>"></label>
            <label class="oa-field">Texte du bouton devis <input type="color" name="<?php echo $name('button_ink'); ?>" value="<?php echo esc_attr($s['button_ink']); ?>"></label>
            <label class="oa-field">Couleur du bouton d'appel <input type="color" name="<?php echo $name('accent_color'); ?>" value="<?php echo esc_attr($s['accent_color']); ?>"></label>
            <label class="oa-field">Texte du bouton d'appel <input type="color" name="<?php echo $name('accent_ink'); ?>" value="<?php echo esc_attr($s['accent_ink']); ?>"></label>
            <label class="oa-field">Couleur des titres et textes <input type="color" name="<?php echo $name('ink_color'); ?>" value="<?php echo esc_attr($s['ink_color']); ?>"></label>
            <label class="oa-field">Police des titres (Google Fonts) <input type="text" name="<?php echo $name('heading_font'); ?>" value="<?php echo esc_attr($s['heading_font']); ?>" placeholder="ex. Anton — vide = police du thème"></label>
            <label class="oa-field">Police du texte (Google Fonts) <input type="text" name="<?php echo $name('body_font'); ?>" value="<?php echo esc_attr($s['body_font']); ?>" placeholder="ex. DM Sans — vide = police du thème"></label>
            <label class="oa-field">Arrondi des boutons et cartes (px) <input type="number" min="0" max="32" name="<?php echo $name('radius'); ?>" value="<?php echo esc_attr($s['radius']); ?>"></label>
            <label class="oa-field">Libellé du bouton devis <input type="text" name="<?php echo $name('quote_label'); ?>" value="<?php echo esc_attr($s['quote_label']); ?>"></label>
        </div>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('heading_upper'); ?>" value="1" <?php checked($s['heading_upper']); ?>> Titres en majuscules (style affiche)</label>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('sticky_cta'); ?>" value="1" <?php checked($s['sticky_cta']); ?>> Barre « Appeler / Devis » fixe sur mobile</label>
        <h3>Blocs « landing page »</h3>
        <p class="description">Une entrée par ligne : <code>Titre | Lien | URL de l'image | Texte court</code>. Ces blocs s'affichent sur toutes les pages locales et les articles, et se mettent à jour partout dès que vous modifiez la liste.</p>
        <label class="oa-field">Titre du bloc services <input type="text" name="<?php echo $name('services_title'); ?>" value="<?php echo esc_attr($s['services_title']); ?>"></label>
        <label class="oa-field">Services <textarea name="<?php echo $name('services_list'); ?>" rows="6"><?php echo esc_textarea($s['services_list']); ?></textarea></label>
        <label class="oa-field">Titre du bloc réalisations <input type="text" name="<?php echo $name('realisations_title'); ?>" value="<?php echo esc_attr($s['realisations_title']); ?>"></label>
        <label class="oa-field">Réalisations <textarea name="<?php echo $name('realisations_list'); ?>" rows="8"><?php echo esc_textarea($s['realisations_list']); ?></textarea></label>
        <label class="oa-field">Lien « Voir toutes nos réalisations » <input type="url" name="<?php echo $name('realisations_url'); ?>" value="<?php echo esc_attr($s['realisations_url']); ?>"></label>
        <label class="oa-check"><input type="checkbox" name="<?php echo $name('style_posts'); ?>" value="1" <?php checked($s['style_posts']); ?>> Appliquer le design des pages locales aux articles du blog (hero, boutons, badges, bloc devis)</label>
        <label class="oa-field">Accroche du hero des articles <input type="text" name="<?php echo $name('post_tagline'); ?>" value="<?php echo esc_attr($s['post_tagline']); ?>"></label>
    </section>

    <div class="oa-savebar"><button class="button button-primary button-hero">Enregistrer les réglages</button></div>
</form>
