<?php
/**
 * Template public pour afficher une annonce - Design Exceptionnel avec Hero et Sidebar
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
    
    // Récupérer le numéro de suivi
    $tracking_number = get_post_meta($ad->post_id, 'tracking_number', true);
    if (empty($tracking_number)) {
        $tracking_number = 'AD-' . str_pad($ad->post_id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($ad->post_id . time()), 0, 4));
        update_post_meta($ad->post_id, 'tracking_number', $tracking_number);
    }
    
    // Récupérer l'image mise en avant
    $featured_image_id = get_post_thumbnail_id($ad->post_id);
    $featured_image_url = '';
    if ($featured_image_id) {
        $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
    }
    
    // Si pas d'image mise en avant, essayer depuis le template
    if (empty($featured_image_url) && $template) {
        $template_featured_id = get_post_thumbnail_id($template->post_id);
        if ($template_featured_id) {
            $featured_image_url = wp_get_attachment_image_url($template_featured_id, 'full');
        }
    }
    
    // Image par défaut si aucune
    if (empty($featured_image_url)) {
        $featured_image_url = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1920&q=80';
    }
    
    // Récupérer les informations pour le tracking
    $ad_id = $ad->post_id;
    $ad_slug_for_tracking = $ad->get_slug();
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // Récupérer la date de publication
    $publication_date = $ad->get_formatted_publication_date('d F Y');
    
    // Récupérer les statistiques
    $view_count = intval(get_post_meta($ad->post_id, 'view_count', true)) ?: 0;
    update_post_meta($ad->post_id, 'view_count', $view_count + 1);
    
    // Récupérer le nombre d'appels
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
    echo '<div class="container"><div class="alert alert-danger"><p>' . __('Erreur lors du chargement de l\'annonce', 'osmose-ads') . '</p><p><small>' . esc_html($e->getMessage()) . '</small></p></div></div>';
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

// Informations de l'entreprise
$company_name = get_bloginfo('name');
$company_email = get_option('admin_email', '');
$company_address = get_option('osmose_ads_company_address', '');

// Headers
get_header();
?>

<div class="osmose-ad-page-wrapper">
    
    <!-- Hero Section avec Image Mise en Avant -->
    <section class="osmose-hero" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo esc_url($featured_image_url); ?>');">
        <div class="osmose-hero-overlay"></div>
        <div class="osmose-hero-content">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <?php if ($city): ?>
                            <div class="hero-badge">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?php echo esc_html($city->post_title); ?></span>
                                <?php 
                                $department = get_post_meta($city->ID, 'department', true);
                                if ($department) {
                                    echo '<span class="badge-separator">•</span><span>' . esc_html($department) . '</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        <h1 class="hero-title"><?php echo esc_html(get_the_title()); ?></h1>
                        <div class="hero-meta">
                            <span><i class="bi bi-calendar3"></i> <?php echo esc_html($publication_date); ?></span>
                            <span><i class="bi bi-eye"></i> <?php echo number_format_i18n($view_count + 1); ?> vues</span>
                            <span><i class="bi bi-telephone"></i> <?php echo number_format_i18n($call_count); ?> appels</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Content avec Sidebar -->
    <div class="osmose-main-content">
        <div class="container">
            <div class="row">
                
                <!-- Contenu Principal -->
                <div class="col-lg-8">
                    <article class="osmose-article-content">
                        <!-- Numéro de suivi -->
                        <div class="tracking-badge-top">
                            <i class="bi bi-hash"></i>
                            <strong><?php _e('Référence:', 'osmose-ads'); ?></strong>
                            <code><?php echo esc_html($tracking_number); ?></code>
                        </div>
                        
                        <!-- Contenu HTML Formaté -->
                        <div class="article-body">
                            <?php 
                            if ($content) {
                                // Formater et nettoyer le contenu HTML
                                $formatted_content = wp_kses_post($content);
                                // Ajouter des classes pour le style
                                $formatted_content = preg_replace('/<h2>/', '<h2 class="article-h2">', $formatted_content);
                                $formatted_content = preg_replace('/<h3>/', '<h3 class="article-h3">', $formatted_content);
                                $formatted_content = preg_replace('/<p>/', '<p class="article-paragraph">', $formatted_content);
                                $formatted_content = preg_replace('/<ul>/', '<ul class="article-list">', $formatted_content);
                                $formatted_content = preg_replace('/<ol>/', '<ol class="article-list">', $formatted_content);
                                echo $formatted_content;
                            } else {
                                echo '<p class="article-paragraph">' . __('Contenu non disponible', 'osmose-ads') . '</p>';
                            }
                            ?>
                        </div>
                        
                        <!-- Section Contact Bas -->
                        <div class="article-contact-bottom">
                            <h3><i class="bi bi-envelope-fill"></i> <?php _e('Besoin d\'un devis ?', 'osmose-ads'); ?></h3>
                            <p><?php _e('Contactez-nous pour une estimation gratuite de votre projet', 'osmose-ads'); ?></p>
                            <div class="contact-actions-bottom">
                                <?php if ($phone_raw): ?>
                                    <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="btn-contact osmose-track-call"
                                       data-ad-id="<?php echo esc_attr($ad_id); ?>"
                                       data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                                       data-page-url="<?php echo esc_attr($current_url); ?>"
                                       data-phone="<?php echo esc_attr($phone_raw); ?>">
                                        <i class="bi bi-telephone-fill"></i>
                                        <?php echo esc_html($phone ?: $phone_raw); ?>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/devis')); ?>" class="btn-contact btn-contact-secondary">
                                    <i class="bi bi-envelope-fill"></i>
                                    <?php _e('Demander un Devis', 'osmose-ads'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <aside class="osmose-sidebar">
                        
                        <!-- Carte Contact Principale -->
                        <div class="sidebar-card sidebar-card-contact">
                            <div class="sidebar-card-header">
                                <h3><i class="bi bi-telephone-fill"></i> <?php _e('Contact Rapide', 'osmose-ads'); ?></h3>
                            </div>
                            <div class="sidebar-card-body">
                                <?php if ($phone_raw): ?>
                                    <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="sidebar-call-button osmose-track-call"
                                       data-ad-id="<?php echo esc_attr($ad_id); ?>"
                                       data-ad-slug="<?php echo esc_attr($ad_slug_for_tracking); ?>"
                                       data-page-url="<?php echo esc_attr($current_url); ?>"
                                       data-phone="<?php echo esc_attr($phone_raw); ?>">
                                        <div class="call-button-icon">
                                            <i class="bi bi-telephone-fill"></i>
                                        </div>
                                        <div class="call-button-text">
                                            <strong><?php _e('Appeler Maintenant', 'osmose-ads'); ?></strong>
                                            <span><?php echo esc_html($phone ?: $phone_raw); ?></span>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo esc_url(home_url('/devis')); ?>" class="sidebar-devis-button">
                                    <i class="bi bi-envelope-fill"></i>
                                    <?php _e('Demander un Devis Gratuit', 'osmose-ads'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Informations Importantes -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <h3><i class="bi bi-info-circle-fill"></i> <?php _e('Informations', 'osmose-ads'); ?></h3>
                            </div>
                            <div class="sidebar-card-body">
                                <div class="info-item">
                                    <i class="bi bi-hash"></i>
                                    <div>
                                        <strong><?php _e('Référence', 'osmose-ads'); ?></strong>
                                        <span><?php echo esc_html($tracking_number); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($city): ?>
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <div>
                                            <strong><?php _e('Zone d\'intervention', 'osmose-ads'); ?></strong>
                                            <span><?php echo esc_html($city->post_title); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i class="bi bi-calendar3"></i>
                                    <div>
                                        <strong><?php _e('Publication', 'osmose-ads'); ?></strong>
                                        <span><?php echo esc_html($publication_date); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="bi bi-eye"></i>
                                    <div>
                                        <strong><?php _e('Vues', 'osmose-ads'); ?></strong>
                                        <span><?php echo number_format_i18n($view_count + 1); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="bi bi-telephone"></i>
                                    <div>
                                        <strong><?php _e('Appels', 'osmose-ads'); ?></strong>
                                        <span><?php echo number_format_i18n($call_count); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations Entreprise -->
                        <?php if ($company_name || $company_email || $company_address): ?>
                            <div class="sidebar-card">
                                <div class="sidebar-card-header">
                                    <h3><i class="bi bi-building"></i> <?php _e('Notre Entreprise', 'osmose-ads'); ?></h3>
                                </div>
                                <div class="sidebar-card-body">
                                    <?php if ($company_name): ?>
                                        <div class="info-item">
                                            <i class="bi bi-shop"></i>
                                            <div>
                                                <strong><?php echo esc_html($company_name); ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($company_email): ?>
                                        <div class="info-item">
                                            <i class="bi bi-envelope"></i>
                                            <div>
                                                <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($company_address): ?>
                                        <div class="info-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <div>
                                                <span><?php echo esc_html($company_address); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Annonces Similaires -->
                        <?php if (!empty($related_ads)): ?>
                            <div class="sidebar-card">
                                <div class="sidebar-card-header">
                                    <h3><i class="bi bi-grid-3x3-gap"></i> <?php _e('Autres Services', 'osmose-ads'); ?></h3>
                                </div>
                                <div class="sidebar-card-body">
                                    <div class="related-posts-list">
                                        <?php foreach ($related_ads as $related_ad): ?>
                                            <a href="<?php echo get_permalink($related_ad->ID); ?>" class="related-post-item">
                                                <span class="related-post-title"><?php echo esc_html($related_ad->post_title); ?></span>
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </aside>
                </div>
                
            </div>
        </div>
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
