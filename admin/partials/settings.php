<?php
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le header global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/header.php';

if (isset($_POST['submit'])) {
    update_option('osmose_ads_ai_personalization', isset($_POST['ai_personalization']) ? 1 : 0);
    update_option('osmose_ads_company_phone', sanitize_text_field($_POST['company_phone'] ?? ''));
    update_option('osmose_ads_company_phone_raw', sanitize_text_field($_POST['company_phone_raw'] ?? ''));
    update_option('osmose_ads_company_email', sanitize_email($_POST['company_email'] ?? ''));
    update_option('osmose_ads_company_address', sanitize_text_field($_POST['company_address'] ?? ''));
    update_option('osmose_ads_devis_url', esc_url_raw($_POST['devis_url'] ?? ''));
    update_option('osmose_ads_openai_api_key', sanitize_text_field($_POST['openai_api_key'] ?? ''));
    update_option('osmose_ads_ai_provider', sanitize_text_field($_POST['ai_provider'] ?? 'openai'));
    // SMTP custom pour les emails du simulateur
    update_option('osmose_ads_smtp_enabled', isset($_POST['smtp_enabled']) ? 1 : 0);
    update_option('osmose_ads_smtp_host', sanitize_text_field($_POST['smtp_host'] ?? ''));
    update_option('osmose_ads_smtp_port', intval($_POST['smtp_port'] ?? 587));
    update_option('osmose_ads_smtp_encryption', sanitize_text_field($_POST['smtp_encryption'] ?? 'tls'));
    update_option('osmose_ads_smtp_username', sanitize_text_field($_POST['smtp_username'] ?? ''));
    // Ne pas afficher le mot de passe en clair plus tard, mais l'enregistrer
    if (isset($_POST['smtp_password']) && $_POST['smtp_password'] !== '') {
        update_option('osmose_ads_smtp_password', sanitize_text_field($_POST['smtp_password']));
    }
    update_option('osmose_ads_smtp_from_email', sanitize_email($_POST['smtp_from_email'] ?? ''));
    update_option('osmose_ads_smtp_from_name', sanitize_text_field($_POST['smtp_from_name'] ?? ''));
    
    if (isset($_POST['services'])) {
        $services = array_map('sanitize_text_field', $_POST['services']);
        update_option('osmose_ads_services', $services);
    }
    
    echo '<div class="alert alert-success"><p>' . __('Réglages sauvegardés', 'osmose-ads') . '</p></div>';
}

// Gérer le flush des rewrite rules
if (isset($_POST['flush_rewrite_rules']) && isset($_POST['osmose_ads_nonce']) && wp_verify_nonce($_POST['osmose_ads_nonce'], 'osmose_ads_flush_rewrite_rules')) {
    flush_rewrite_rules(false);
    delete_option('osmose_ads_sitemap_flushed');
    echo '<div class="alert alert-success"><p>' . __('Rewrite rules régénérées avec succès. Le sitemap devrait maintenant être accessible.', 'osmose-ads') . '</p></div>';
}

$ai_personalization = get_option('osmose_ads_ai_personalization', false);
$company_phone = get_option('osmose_ads_company_phone', '');
$company_phone_raw = get_option('osmose_ads_company_phone_raw', '');
$company_email = get_option('osmose_ads_company_email', '');
$devis_url = get_option('osmose_ads_devis_url', '');
$openai_api_key = get_option('osmose_ads_openai_api_key', '');
$ai_provider = get_option('osmose_ads_ai_provider', 'openai');
$services = get_option('osmose_ads_services', array());
// SMTP
$smtp_enabled = get_option('osmose_ads_smtp_enabled', 0);
$smtp_host = get_option('osmose_ads_smtp_host', '');
$smtp_port = get_option('osmose_ads_smtp_port', 587);
$smtp_encryption = get_option('osmose_ads_smtp_encryption', 'tls');
$smtp_username = get_option('osmose_ads_smtp_username', '');
$smtp_from_email = get_option('osmose_ads_smtp_from_email', '');
$smtp_from_name = get_option('osmose_ads_smtp_from_name', get_bloginfo('name'));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
</div>
    
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Personnalisation IA', 'osmose-ads'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ai_personalization" value="1" <?php checked($ai_personalization, 1); ?>>
                        <?php _e('Activer la personnalisation IA du contenu par ville', 'osmose-ads'); ?>
                    </label>
                    <p class="description"><?php _e('Si activé, le contenu sera généré de manière unique pour chaque ville avec l\'IA.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Fournisseur IA', 'osmose-ads'); ?></th>
                <td>
                    <select name="ai_provider">
                        <option value="openai" <?php selected($ai_provider, 'openai'); ?>>OpenAI (ChatGPT)</option>
                        <option value="groq" <?php selected($ai_provider, 'groq'); ?>>Groq</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Clé API OpenAI/Groq', 'osmose-ads'); ?></th>
                <td>
                    <input type="password" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text">
                    <p class="description"><?php _e('Clé API pour l\'intégration IA', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Téléphone Entreprise', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text" placeholder="01 23 45 67 89">
                    <p class="description"><?php _e('Numéro de téléphone formaté (affiché)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Téléphone Entreprise (Brut)', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="company_phone_raw" value="<?php echo esc_attr($company_phone_raw); ?>" class="regular-text" placeholder="0123456789">
                    <p class="description"><?php _e('Numéro de téléphone sans formatage (pour liens tel:)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Email Entreprise', 'osmose-ads'); ?></th>
                <td>
                    <input type="email" name="company_email" value="<?php echo esc_attr($company_email); ?>" class="regular-text" placeholder="contact@example.com">
                    <p class="description"><?php _e('Adresse email affichée sur les pages d\'annonces', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Adresse Entreprise', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="company_address" value="<?php echo esc_attr(get_option('osmose_ads_company_address', '')); ?>" class="regular-text" placeholder="123 Rue Example, 75000 Paris">
                    <p class="description"><?php _e('Adresse complète de l\'entreprise (utilisée dans les contenus générés)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('URL "Demander un Devis"', 'osmose-ads'); ?></th>
                <td>
                    <input type="url" name="devis_url" value="<?php echo esc_attr($devis_url); ?>" class="regular-text" placeholder="https://example.com/devis">
                    <p class="description"><?php _e('URL de la page de demande de devis (utilisée pour le bouton "Devis Gratuit")', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Services', 'osmose-ads'); ?></th>
                <td>
                    <div id="services-list">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $index => $service): ?>
                                <div class="service-item">
                                    <input type="text" name="services[]" value="<?php echo esc_attr($service); ?>" class="regular-text">
                                    <button type="button" class="button remove-service"><?php _e('Supprimer', 'osmose-ads'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-service" class="button"><?php _e('Ajouter un Service', 'osmose-ads'); ?></button>
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e('Emails du simulateur - SMTP personnalisé', 'osmose-ads'); ?></h2></th>
            </tr>
            <tr>
                <th scope="row"><?php _e('Activer SMTP personnalisé', 'osmose-ads'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="smtp_enabled" value="1" <?php checked($smtp_enabled, 1); ?>>
                        <?php _e('Utiliser ces paramètres SMTP pour les emails du simulateur (au lieu de la configuration WordPress par défaut)', 'osmose-ads'); ?>
                    </label>
                    <p class="description"><?php _e('Recommandé si votre hébergeur bloque la fonction mail() de PHP ou si vous utilisez un service SMTP externe (Gmail, Outlook, Mailgun, etc.).', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Hôte SMTP', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text" placeholder="smtp.exemple.com">
                    <p class="description"><?php _e('Exemples : smtp.gmail.com, smtp.office365.com, smtp.mailgun.org…', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Port SMTP', 'osmose-ads'); ?></th>
                <td>
                    <input type="number" name="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text">
                    <p class="description"><?php _e('Ports courants : 587 (TLS), 465 (SSL), 25.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Chiffrement', 'osmose-ads'); ?></th>
                <td>
                    <select name="smtp_encryption">
                        <option value="none" <?php selected($smtp_encryption, 'none'); ?>><?php _e('Aucun', 'osmose-ads'); ?></option>
                        <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS</option>
                        <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Identifiant SMTP', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="smtp_username" value="<?php echo esc_attr($smtp_username); ?>" class="regular-text" placeholder="user@exemple.com">
                    <p class="description"><?php _e('Le login / email utilisé pour se connecter au serveur SMTP.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Mot de passe SMTP', 'osmose-ads'); ?></th>
                <td>
                    <input type="password" name="smtp_password" value="" class="regular-text" autocomplete="new-password">
                    <p class="description"><?php _e('Laisser vide pour ne pas modifier le mot de passe déjà enregistré.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Email d\'expéditeur', 'osmose-ads'); ?></th>
                <td>
                    <input type="email" name="smtp_from_email" value="<?php echo esc_attr($smtp_from_email); ?>" class="regular-text" placeholder="no-reply@exemple.com">
                    <p class="description"><?php _e('Adresse email qui apparaîtra comme expéditeur des emails du simulateur.', 'osmose-ads'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nom d\'expéditeur', 'osmose-ads'); ?></th>
                <td>
                    <input type="text" name="smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <p class="description"><?php _e('Nom affiché comme expéditeur (ex : Nom de votre entreprise).', 'osmose-ads'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <!-- Section Sitemap -->
    <div class="card" style="max-width: 800px; margin-top: 30px;">
        <h2 style="margin-top: 0;"><?php _e('Sitemap XML', 'osmose-ads'); ?></h2>
        <p><?php _e('Le sitemap XML est divisé en plusieurs fichiers pour optimiser les performances. Chaque fichier contient maximum 4000 liens.', 'osmose-ads'); ?></p>
        
        <?php
        $sitemap_index_url = home_url('/sitemap-ads.xml');
        $ads_count = wp_count_posts('ad');
        $published_ads = isset($ads_count->publish) ? $ads_count->publish : 0;
        $max_links_per_sitemap = 4000;
        $num_sitemaps = $published_ads > 0 ? ceil($published_ads / $max_links_per_sitemap) : 1;
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Sitemap Index (Principal)', 'osmose-ads'); ?></th>
                <td>
                    <code style="background: #f0f0f0; padding: 5px 10px; border-radius: 3px;"><?php echo esc_url($sitemap_index_url); ?></code>
                    <a href="<?php echo esc_url($sitemap_index_url); ?>" target="_blank" class="button" style="margin-left: 10px;"><?php _e('Voir le Sitemap Index', 'osmose-ads'); ?></a>
                    <p class="description"><?php _e('URL du sitemap index qui liste tous les sitemaps individuels', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Annonces totales', 'osmose-ads'); ?></th>
                <td>
                    <strong><?php echo number_format($published_ads, 0, ',', ' '); ?></strong> <?php _e('annonce(s) publiée(s)', 'osmose-ads'); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Fichiers Sitemap', 'osmose-ads'); ?></th>
                <td>
                    <strong><?php echo intval($num_sitemaps); ?></strong> <?php _e('fichier(s) sitemap', 'osmose-ads'); ?>
                    <p class="description"><?php _e('Nombre de fichiers sitemap générés (max 4000 liens par fichier)', 'osmose-ads'); ?></p>
                </td>
            </tr>
            
            <?php if ($num_sitemaps > 0): ?>
            <tr>
                <th scope="row"><?php _e('Liste des Sitemaps', 'osmose-ads'); ?></th>
                <td>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 3px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <th style="text-align: left; padding: 8px;"><?php _e('Fichier', 'osmose-ads'); ?></th>
                                    <th style="text-align: left; padding: 8px;"><?php _e('URL', 'osmose-ads'); ?></th>
                                    <th style="text-align: center; padding: 8px;"><?php _e('Liens', 'osmose-ads'); ?></th>
                                    <th style="text-align: center; padding: 8px;"><?php _e('Action', 'osmose-ads'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Sitemap 0 = page d'accueil
                                $home_url = home_url('/sitemap-ads-0.xml');
                                ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px;"><strong>sitemap-ads-0.xml</strong></td>
                                    <td style="padding: 8px;"><code style="font-size: 11px;"><?php echo esc_url($home_url); ?></code></td>
                                    <td style="text-align: center; padding: 8px;">1</td>
                                    <td style="text-align: center; padding: 8px;">
                                        <a href="<?php echo esc_url($home_url); ?>" target="_blank" class="button button-small"><?php _e('Voir', 'osmose-ads'); ?></a>
                                    </td>
                                </tr>
                                <?php
                                // Sitemaps des annonces
                                for ($i = 1; $i <= $num_sitemaps; $i++) {
                                    $sitemap_url = home_url('/sitemap-ads-' . $i . '.xml');
                                    $start_index = ($i - 1) * $max_links_per_sitemap;
                                    $end_index = min($start_index + $max_links_per_sitemap, $published_ads);
                                    $count = $end_index - $start_index;
                                    ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 8px;"><strong>sitemap-ads-<?php echo intval($i); ?>.xml</strong></td>
                                        <td style="padding: 8px;"><code style="font-size: 11px;"><?php echo esc_url($sitemap_url); ?></code></td>
                                        <td style="text-align: center; padding: 8px;"><?php echo number_format($count, 0, ',', ' '); ?></td>
                                        <td style="text-align: center; padding: 8px;">
                                            <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" class="button button-small"><?php _e('Voir', 'osmose-ads'); ?></a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <th scope="row"><?php _e('Actions', 'osmose-ads'); ?></th>
                <td>
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field('osmose_ads_flush_rewrite_rules', 'osmose_ads_nonce'); ?>
                        <input type="hidden" name="action" value="flush_rewrite_rules">
                        <button type="submit" class="button button-secondary" name="flush_rewrite_rules"><?php _e('Régénérer les Rewrite Rules', 'osmose-ads'); ?></button>
                    </form>
                    <p class="description" style="margin-top: 10px;"><?php _e('Si le sitemap retourne une erreur 404, cliquez sur ce bouton pour régénérer les règles de réécriture.', 'osmose-ads'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="notice notice-info" style="margin-top: 20px;">
            <p><strong><?php _e('Comment utiliser le Sitemap :', 'osmose-ads'); ?></strong></p>
            <ul style="margin-left: 20px;">
                <li><?php _e('Soumettez uniquement l\'URL du sitemap index à Google Search Console :', 'osmose-ads'); ?> <code><?php echo esc_url($sitemap_index_url); ?></code></li>
                <li><?php _e('Ajoutez l\'URL dans votre fichier robots.txt :', 'osmose-ads'); ?> <code>Sitemap: <?php echo esc_url($sitemap_index_url); ?></code></li>
                <li><?php _e('Le sitemap index référence automatiquement tous les sitemaps individuels', 'osmose-ads'); ?></li>
                <li><?php _e('Chaque fichier sitemap contient maximum 4000 liens pour optimiser les performances', 'osmose-ads'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-service').on('click', function() {
        var html = '<div class="service-item"><input type="text" name="services[]" class="regular-text" placeholder="Nom du service"><button type="button" class="button remove-service"><?php _e('Supprimer', 'osmose-ads'); ?></button></div>';
        $('#services-list').append(html);
    });
    
    $(document).on('click', '.remove-service', function() {
        $(this).parent().remove();
    });
});
</script>

<?php
// Inclure le footer global
require_once OSMOSE_ADS_PLUGIN_DIR . 'admin/partials/footer.php';
?>

