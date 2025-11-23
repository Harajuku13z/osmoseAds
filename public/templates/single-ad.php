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

// Headers
get_header();
?>

<div class="osmose-ads-single">
    <article class="ad-content">
        <!-- Hero Section -->
        <header class="ad-header">
            <h1 class="ad-title"><?php echo esc_html(get_the_title()); ?></h1>
            <?php if ($city): ?>
                <p class="ad-location">
                    <?php echo esc_html($city->post_title); ?>
                    <?php 
                    $department = get_post_meta($city->ID, 'department', true);
                    if ($department) {
                        echo ' (' . esc_html($department) . ')';
                    }
                    ?>
                </p>
            <?php endif; ?>
            
            <div class="ad-cta">
                <a href="<?php echo esc_url(home_url('/devis')); ?>" class="button button-primary">
                    <?php _e('Demander un Devis', 'osmose-ads'); ?>
                </a>
                <?php 
                $phone = get_option('osmose_ads_company_phone_raw', '');
                if ($phone): 
                ?>
                    <a href="tel:<?php echo esc_attr($phone); ?>" class="button">
                        <?php _e('Appeler', 'osmose-ads'); ?>
                    </a>
                <?php endif; ?>
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
        
        <!-- Section CTA -->
        <div class="ad-cta-section">
            <h2><?php _e('Besoin de ce service ?', 'osmose-ads'); ?></h2>
            <p><?php _e('Contactez-nous pour obtenir un devis personnalisé', 'osmose-ads'); ?></p>
            <a href="<?php echo esc_url(home_url('/devis')); ?>" class="button button-primary">
                <?php _e('Demander un Devis Gratuit', 'osmose-ads'); ?>
            </a>
        </div>
        
        <!-- Annonces Similaires -->
        <?php if (!empty($related_ads)): ?>
            <div class="ad-related">
                <h2><?php _e('Autres Services', 'osmose-ads'); ?></h2>
                <div class="related-ads-grid">
                    <?php foreach ($related_ads as $related_ad): ?>
                        <div class="related-ad-item">
                            <h3><a href="<?php echo get_permalink($related_ad->ID); ?>"><?php echo esc_html($related_ad->post_title); ?></a></h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </article>
</div>

<?php
get_footer();

