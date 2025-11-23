<?php
/**
 * Template public pour afficher une annonce
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

// Récupérer les données
$city = $ad->get_city();
$template = $ad->get_template();
$content = $ad->get_content();
$meta = $ad->get_meta();
$related_ads = $ad->get_related_ads(5);

// Récupérer les informations pour le tracking
$ad_id = $ad->post_id;
$ad_slug_for_tracking = $ad->post_name;
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

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

<!-- Bootstrap Icons pour les icônes -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">


<div class="osmose-ads-single-wrapper">
    <div class="osmose-ads-single">
        <article class="ad-content">
            <!-- Hero Section -->
            <header class="ad-hero">
                <div class="ad-hero-content">
                    <div class="ad-badge-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?php if ($city): ?>
                            <span><?php echo esc_html($city->post_title); ?></span>
                            <?php 
                            $department = get_post_meta($city->ID, 'department', true);
                            if ($department) {
                                echo ' <span class="ad-department">(' . esc_html($department) . ')</span>';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                    <h1 class="ad-title"><?php echo esc_html(get_the_title()); ?></h1>
                    
                    <div class="ad-hero-cta">
                        <?php if ($phone_raw): ?>
                            <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-call osmose-track-call" 
                               data-ad-id="<?php echo esc_attr($ad_id); ?>"
                               data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                               data-page-url="<?php echo esc_attr($current_url); ?>"
                               data-phone="<?php echo esc_attr($phone_raw); ?>">
                                <i class="bi bi-telephone-fill"></i>
                                <?php echo esc_html($phone ?: $phone_raw); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-devis">
                            <i class="bi bi-envelope-fill"></i>
                            <?php _e('Demander un Devis', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Contenu Principal -->
            <div class="ad-body">
                <?php 
                if ($content) {
                    echo wp_kses_post($content);
                } else {
                    echo '<p>' . __('Contenu non disponible', 'osmose-ads') . '</p>';
                }
                ?>
            </div>
            
            <!-- Section CTA Fixe -->
            <div class="ad-cta-floating">
                <div class="ad-cta-floating-content">
                    <div class="ad-cta-floating-text">
                        <strong><?php _e('Besoin de ce service ?', 'osmose-ads'); ?></strong>
                        <span><?php _e('Contactez-nous maintenant', 'osmose-ads'); ?></span>
                    </div>
                    <div class="ad-cta-floating-buttons">
                        <?php if ($phone_raw): ?>
                            <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-call-small osmose-track-call"
                               data-ad-id="<?php echo esc_attr($ad_id); ?>"
                               data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                               data-page-url="<?php echo esc_attr($current_url); ?>"
                               data-phone="<?php echo esc_attr($phone_raw); ?>">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-devis-small">
                            <i class="bi bi-envelope-fill"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Section CTA -->
            <div class="ad-cta-section">
                <div class="ad-cta-section-content">
                    <h2><?php _e('Contactez-nous pour votre projet', 'osmose-ads'); ?></h2>
                    <p><?php _e('Obtenez un devis personnalisé et gratuit pour votre projet', 'osmose-ads'); ?></p>
                    <div class="ad-cta-section-buttons">
                        <?php if ($phone_raw): ?>
                            <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-call osmose-track-call"
                               data-ad-id="<?php echo esc_attr($ad_id); ?>"
                               data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                               data-page-url="<?php echo esc_attr($current_url); ?>"
                               data-phone="<?php echo esc_attr($phone_raw); ?>">
                                <i class="bi bi-telephone-fill"></i>
                                <?php _e('Appeler', 'osmose-ads'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-devis">
                            <i class="bi bi-envelope-fill"></i>
                            <?php _e('Demander un Devis Gratuit', 'osmose-ads'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Annonces Similaires -->
            <?php if (!empty($related_ads)): ?>
                <div class="ad-related">
                    <h2><?php _e('Autres Services dans la Même Zone', 'osmose-ads'); ?></h2>
                    <div class="related-ads-grid">
                        <?php foreach ($related_ads as $related_ad): ?>
                            <div class="related-ad-item">
                                <h3><a href="<?php echo get_permalink($related_ad->ID); ?>"><?php echo esc_html($related_ad->post_title); ?></a></h3>
                                <a href="<?php echo get_permalink($related_ad->ID); ?>" class="related-ad-link">
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
