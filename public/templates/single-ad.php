<?php
/**
 * Template public pour afficher une annonce - Design Blog Moderne
 */

if (!defined('ABSPATH')) {
    exit;
}

// Charger les modèles si nécessaire
if (!class_exists('Ad')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad.php';
}

// Récupérer l'annonce
global $wp_query;
$ad_slug = get_query_var('ad_slug');

if (!$ad_slug) {
    // Essayer de récupérer depuis le post actuel
    global $post;
    if ($post && $post->post_type === 'ad') {
        $ad = new Ad($post->ID);
    } else {
        $ad = null;
    }
} else {
    $ad = Ad::get_by_slug($ad_slug);
}

if (!$ad) {
    get_header();
    echo '<div class="container"><p>' . __('Annonce non trouvée', 'osmose-ads') . '</p></div>';
    get_footer();
    exit;
}

// Récupérer les données avec gestion d'erreurs
try {
    $city = $ad->get_city();
    $template = $ad->get_template();
    $content = $ad->get_content();
    $meta = $ad->get_meta();
    $related_ads = $ad->get_related_ads(5);
    
    // Récupérer le numéro de suivi (créer si n'existe pas)
    $tracking_number = get_post_meta($ad->post_id, 'tracking_number', true);
    if (empty($tracking_number)) {
        // Générer un numéro de suivi unique
        $tracking_number = 'AD-' . str_pad($ad->post_id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($ad->post_id . time()), 0, 4));
        update_post_meta($ad->post_id, 'tracking_number', $tracking_number);
    }
    
    // Récupérer les informations pour le tracking
    $ad_id = $ad->post_id;
    $ad_slug_for_tracking = $ad->get_slug();
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // Récupérer la date de publication
    $publication_date = $ad->get_formatted_publication_date('d F Y');
    
    // Récupérer les statistiques
    $view_count = intval(get_post_meta($ad->post_id, 'view_count', true)) ?: 0;
    
    // Incrémenter le compteur de vues
    update_post_meta($ad->post_id, 'view_count', $view_count + 1);
    
    // Récupérer le nombre d'appels pour cette annonce
    global $wpdb;
    $table_name = $wpdb->prefix . 'osmose_ads_call_tracking';
    $call_count = 0;
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $call_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE ad_id = %d",
            $ad_id
        ));
    }
    
} catch (Exception $e) {
    error_log('Osmose ADS: Error loading ad data: ' . $e->getMessage());
    get_header();
    echo '<div class="container"><div class="alert alert-danger"><p>' . __('Erreur lors du chargement de l\'annonce', 'osmose-ads') . '</p></div></div>';
    get_footer();
    exit;
}

// Métadonnées SEO
$page_title = $meta['meta_title'] ?? get_the_title();
$page_description = $meta['meta_description'] ?? '';
$meta_keywords = $meta['meta_keywords'] ?? '';

// Définir les métadonnées via les filtres WordPress
add_filter('wp_title', function($title) use ($page_title) {
    return $page_title ? $page_title : $title;
}, 10, 1);

add_filter('pre_get_document_title', function($title) use ($page_title) {
    return $page_title ? $page_title : $title;
}, 10, 1);

add_action('wp_head', function() use ($page_description, $meta_keywords, $meta) {
    if ($page_description) {
        echo '<meta name="description" content="' . esc_attr($page_description) . '">' . "\n";
    }
    if ($meta_keywords) {
        echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '">' . "\n";
    }
    // Open Graph
    if (isset($meta['og_title'])) {
        echo '<meta property="og:title" content="' . esc_attr($meta['og_title']) . '">' . "\n";
    }
    if (isset($meta['og_description'])) {
        echo '<meta property="og:description" content="' . esc_attr($meta['og_description']) . '">' . "\n";
    }
    // Twitter
    if (isset($meta['twitter_title'])) {
        echo '<meta name="twitter:title" content="' . esc_attr($meta['twitter_title']) . '">' . "\n";
    }
    if (isset($meta['twitter_description'])) {
        echo '<meta name="twitter:description" content="' . esc_attr($meta['twitter_description']) . '">' . "\n";
    }
}, 1);

// Récupérer le téléphone
$phone = get_option('osmose_ads_company_phone', '');
$phone_raw = get_option('osmose_ads_company_phone_raw', '');

// Headers
get_header();
?>

<div class="osmose-ads-blog-wrapper">
    <div class="osmose-ads-blog-container">
        <article class="osmose-ads-blog-post">
            
            <!-- En-tête de l'article (style blog) -->
            <header class="blog-post-header">
                <div class="blog-post-meta">
                    <div class="blog-meta-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo esc_html($publication_date); ?></span>
                    </div>
                    <?php if ($city): ?>
                        <div class="blog-meta-item">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>
                                <?php echo esc_html($city->post_title); ?>
                                <?php 
                                $department = get_post_meta($city->ID, 'department', true);
                                if ($department) {
                                    echo ' (' . esc_html($department) . ')';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="blog-meta-item">
                        <i class="bi bi-eye"></i>
                        <span><?php echo number_format_i18n($view_count + 1); ?> <?php _e('vues', 'osmose-ads'); ?></span>
                    </div>
                    <div class="blog-meta-item">
                        <i class="bi bi-telephone"></i>
                        <span><?php echo number_format_i18n($call_count); ?> <?php _e('appels', 'osmose-ads'); ?></span>
                    </div>
                </div>
                
                <h1 class="blog-post-title"><?php echo esc_html(get_the_title()); ?></h1>
                
                <div class="blog-post-tracking">
                    <div class="tracking-badge">
                        <i class="bi bi-hash"></i>
                        <strong><?php _e('N° de suivi:', 'osmose-ads'); ?></strong>
                        <code><?php echo esc_html($tracking_number); ?></code>
                    </div>
                </div>
            </header>
            
            <!-- Barre d'action fixe -->
            <div class="blog-action-bar">
                <div class="blog-action-content">
                    <div class="blog-action-info">
                        <div class="blog-action-phone">
                            <?php if ($phone_raw): ?>
                                <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-action-call osmose-track-call"
                                   data-ad-id="<?php echo esc_attr($ad_id); ?>"
                                   data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                                   data-page-url="<?php echo esc_attr($current_url); ?>"
                                   data-phone="<?php echo esc_attr($phone_raw); ?>">
                                    <i class="bi bi-telephone-fill"></i>
                                    <span><?php echo esc_html($phone ?: $phone_raw); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="blog-action-devis">
                            <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-action-devis">
                                <i class="bi bi-envelope-fill"></i>
                                <span><?php _e('Devis Gratuit', 'osmose-ads'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenu Principal -->
            <div class="blog-post-content">
                <?php 
                if ($content) {
                    echo wp_kses_post($content);
                } else {
                    echo '<p>' . __('Contenu non disponible', 'osmose-ads') . '</p>';
                }
                ?>
            </div>
            
            <!-- Section Contact -->
            <div class="blog-contact-section">
                <div class="blog-contact-card">
                    <div class="blog-contact-header">
                        <h3><i class="bi bi-telephone-fill"></i> <?php _e('Contactez-nous', 'osmose-ads'); ?></h3>
                        <p><?php _e('Pour toute demande de devis ou information', 'osmose-ads'); ?></p>
                    </div>
                    <div class="blog-contact-actions">
                        <?php if ($phone_raw): ?>
                            <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-contact-call osmose-track-call"
                               data-ad-id="<?php echo esc_attr($ad_id); ?>"
                               data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                               data-page-url="<?php echo esc_attr($current_url); ?>"
                               data-phone="<?php echo esc_attr($phone_raw); ?>">
                                <i class="bi bi-telephone-fill"></i>
                                <div>
                                    <strong><?php _e('Appeler maintenant', 'osmose-ads'); ?></strong>
                                    <span><?php echo esc_html($phone ?: $phone_raw); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-contact-devis">
                            <i class="bi bi-envelope-fill"></i>
                            <div>
                                <strong><?php _e('Demander un devis', 'osmose-ads'); ?></strong>
                                <span><?php _e('Réponse sous 24h', 'osmose-ads'); ?></span>
                            </div>
                        </a>
                    </div>
                    <div class="blog-contact-tracking">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            <?php _e('Référence:', 'osmose-ads'); ?> <strong><?php echo esc_html($tracking_number); ?></strong>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Annonces Similaires -->
            <?php if (!empty($related_ads)): ?>
                <div class="blog-related-posts">
                    <h2 class="blog-section-title">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <?php _e('Autres Services dans la Même Zone', 'osmose-ads'); ?>
                    </h2>
                    <div class="blog-related-grid">
                        <?php foreach ($related_ads as $related_ad): 
                            $related_city = get_post_meta($related_ad->ID, 'city_id', true);
                            $related_city_post = $related_city ? get_post($related_city) : null;
                        ?>
                            <div class="blog-related-card">
                                <h3><a href="<?php echo get_permalink($related_ad->ID); ?>"><?php echo esc_html($related_ad->post_title); ?></a></h3>
                                <?php if ($related_city_post): ?>
                                    <div class="blog-related-location">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo esc_html($related_city_post->post_title); ?>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo get_permalink($related_ad->ID); ?>" class="blog-related-link">
                                    <?php _e('Voir le service', 'osmose-ads'); ?> <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </article>
    </div>
</div>

<script>
// Variables pour le tracking
window.osmoseAdsTracking = {
    ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo wp_create_nonce('osmose_ads_track_call'); ?>'
};
</script>

<?php
get_footer();
