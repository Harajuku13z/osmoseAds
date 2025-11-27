<?php
/**
 * Template public pour afficher une annonce - Design moderne inspiré de Tailwind
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fonction helper pour générer les URLs de tracking
if (!function_exists('osmose_get_call_tracking_url')) {
    function osmose_get_call_tracking_url($ad_id, $ad_slug, $phone, $source, $page_url) {
        return add_query_arg(array(
            'ad_id' => $ad_id,
            'ad_slug' => $ad_slug,
            'phone' => $phone,
            'source' => $source,
            'page_url' => urlencode($page_url)
        ), home_url('/osmose-call-track/'));
    }
}

// Charger les modèles si nécessaire
if (!class_exists('Ad')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad.php';
}
if (!class_exists('City')) {
    $city_file = OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-city.php';
    if (file_exists($city_file)) {
        require_once $city_file;
    }
}
if (!class_exists('Ad_Template')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'includes/models/class-ad-template.php';
}

// Charger la classe public pour les fonctions de tracking
if (!function_exists('osmose_ads_track_visit')) {
    require_once OSMOSE_ADS_PLUGIN_DIR . 'public/class-osmose-ads-public.php';
}

// Récupérer l'annonce ou l'article généré
global $wp_query, $post;
$ad_slug = get_query_var('ad_slug');
$is_article = false;
$ad = null;

if (!$ad_slug) {
    if ($post) {
        // Vérifier si c'est un article généré (post avec meta article_auto_generated)
        $is_article = ($post->post_type === 'post' && get_post_meta($post->ID, 'article_auto_generated', true) === '1');
        
        if ($post->post_type === 'ad') {
            $ad = new Ad($post->ID);
        } elseif ($is_article) {
            // Créer un objet Ad-like pour les articles générés
            $ad = new Ad($post->ID);
        } else {
            $ad = null;
        }
    } else {
        $ad = null;
    }
} else {
    $ad = Ad::get_by_slug($ad_slug);
}

if (!$ad) {
    get_header();
    echo '<div class="container"><p>' . __('Contenu non trouvé', 'osmose-ads') . '</p></div>';
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
    
    if (empty($featured_image_url) && $template) {
        $template_featured_id = get_post_thumbnail_id($template->post_id);
        if ($template_featured_id) {
            $featured_image_url = wp_get_attachment_image_url($template_featured_id, 'full');
        }
    }
    
    if (empty($featured_image_url)) {
        $featured_image_url = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1920&q=80';
    }
    
    $ad_id = $ad->post_id;
    $ad_slug_for_tracking = $ad->get_slug();
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $publication_date = $ad->get_formatted_publication_date('d F Y');
    
    $view_count = intval(get_post_meta($ad->post_id, 'view_count', true)) ?: 0;
    update_post_meta($ad->post_id, 'view_count', $view_count + 1);
    
    // Enregistrer la visite détaillée
    osmose_ads_track_visit($ad_id, $ad_slug_for_tracking, $current_url, $template ? $template->post_id : null, $city);
    
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
    if (isset($meta['og_title'])) {
        echo '<meta property="og:title" content="' . esc_attr($meta['og_title']) . '">' . "\n";
    }
    if (isset($meta['og_description'])) {
        echo '<meta property="og:description" content="' . esc_attr($meta['og_description']) . '">' . "\n";
    }
    if (isset($meta['twitter_title'])) {
        echo '<meta name="twitter:title" content="' . esc_attr($meta['twitter_title']) . '">' . "\n";
    }
    if (isset($meta['twitter_description'])) {
        echo '<meta name="twitter:description" content="' . esc_attr($meta['twitter_description']) . '">' . "\n";
    }
}, 1);

// Récupérer les informations
$phone = get_option('osmose_ads_company_phone', '');
$phone_raw = get_option('osmose_ads_company_phone_raw', '');
$company_name = get_bloginfo('name');
$company_email = get_option('osmose_ads_company_email', get_option('admin_email', ''));
$devis_url = get_option('osmose_ads_devis_url', '');
$company_address = get_option('osmose_ads_company_address', '');

// Nettoyer les données pour supprimer les éléments indésirables
if ($company_address) {
    $company_address = strip_tags($company_address);
    $company_address = preg_replace('/Icon-facebook|icon-facebook|Facebook/i', '', $company_address);
    $company_address = trim($company_address);
}
if ($company_name) {
    $company_name = strip_tags($company_name);
    $company_name = preg_replace('/Icon-facebook|icon-facebook|Facebook/i', '', $company_name);
    $company_name = trim($company_name);
}

get_header();
?>

<div class="osmose-ad-page-modern">
    
    <!-- Hero Section -->
    <section class="osmose-hero-modern">
        <div class="osmose-hero-bg" style="background-image: url('<?php echo esc_url($featured_image_url); ?>');"></div>
        <div class="osmose-hero-overlay-modern"></div>
        
        <div class="osmose-hero-container">
            <div class="osmose-hero-content-modern">
                <h1 class="osmose-hero-title-modern">
                    <i class="fas fa-tools"></i>
                    <?php echo esc_html(get_the_title()); ?>
                </h1>
                <p class="osmose-hero-description">
                    <?php 
                    $city_name = $city ? $city->post_title : '';
                    $dept = $city ? get_post_meta($city->ID, 'department', true) : '';
                    echo esc_html(sprintf(__('Service professionnel à %s - Devis gratuit et intervention rapide', 'osmose-ads'), $city_name ?: '')); 
                    ?>
                </p>
                <div class="osmose-hero-buttons">
                    <?php if ($devis_url): ?>
                        <a href="<?php echo esc_url($devis_url); ?>" class="osmose-btn-hero osmose-btn-accent">
                            <i class="fas fa-calculator"></i>
                            <?php _e('Devis Gratuit', 'osmose-ads'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($phone_raw): ?>
                        <a href="<?php echo esc_url(osmose_get_call_tracking_url($ad_id, $ad_slug_for_tracking, $phone_raw, 'hero', $current_url)); ?>" class="osmose-btn-hero osmose-btn-primary">
                            <i class="fas fa-phone"></i>
                            <?php echo esc_html($phone ?: $phone_raw); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contenu Principal -->
    <section class="osmose-content-section">
        <div class="osmose-container">
            <div class="osmose-content-wrapper">
                
                <!-- Contenu Principal (Gauche) -->
                <div class="osmose-content-main">
                    <div class="osmose-content-card">
                        <div class="osmose-content-grid">
                            
                            <!-- Colonne Gauche - Contenu -->
                            <div class="osmose-content-left">
                                <!-- Image mise en avant -->
                                <?php if ($featured_image_url): ?>
                                    <div class="osmose-featured-image-container">
                                        <img src="<?php echo esc_url($featured_image_url); ?>" 
                                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                                             class="osmose-featured-image-content">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="osmose-content-text">
                                    <?php 
                                    if ($content) {
                                        // Formater le contenu HTML
                                        $formatted_content = wp_kses_post($content);
                                        $formatted_content = preg_replace('/<h2>/', '<h2 class="osmose-h2">', $formatted_content);
                                        $formatted_content = preg_replace('/<h3>/', '<h3 class="osmose-h3">', $formatted_content);
                                        $formatted_content = preg_replace('/<h4>/', '<h4 class="osmose-h4">', $formatted_content);
                                        $formatted_content = preg_replace('/<p>/', '<p class="osmose-paragraph">', $formatted_content);
                                        $formatted_content = preg_replace('/<ul>/', '<ul class="osmose-list">', $formatted_content);
                                        $formatted_content = preg_replace('/<ol>/', '<ol class="osmose-list">', $formatted_content);
                                        echo $formatted_content;
                                    } else {
                                        echo '<p class="osmose-paragraph">' . __('Contenu non disponible', 'osmose-ads') . '</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Colonne Droite - Sidebar -->
                            <div class="osmose-sidebar-modern">
                                
                                <!-- Carte Contact -->
                                <div class="osmose-sidebar-card osmose-card-green">
                                    <h3 class="osmose-sidebar-title"><?php _e('Pourquoi choisir', 'osmose-ads'); ?> <?php echo esc_html($company_name); ?></h3>
                                    <p class="osmose-sidebar-text"><?php 
                                    $service_desc = sprintf(__('Choisir %s pour votre projet à %s, c\'est opter pour l\'expertise d\'une entreprise locale réputée. Nous garantissons des prestations de qualité, un suivi personnalisé, des délais respectés et des tarifs transparents.', 'osmose-ads'), $company_name, $city_name);
                                    echo esc_html($service_desc);
                                    ?></p>
                                </div>
                                
                                <!-- Carte Financement -->
                                <div class="osmose-sidebar-card osmose-card-yellow">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Financement et aides', 'osmose-ads'); ?></h4>
                                    <p><?php _e('Pour faciliter vos projets, vous pouvez bénéficier d\'aides financières telles que MaPrimeRénov, les CEE, l\'éco-PTZ ou une TVA réduite. Notre équipe est à votre disposition pour vous renseigner sur ces dispositifs.', 'osmose-ads'); ?></p>
                                </div>
                                
                                <!-- Carte Devis -->
                                <div class="osmose-sidebar-card osmose-card-gradient">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Besoin d\'un devis ?', 'osmose-ads'); ?></h4>
                                    <p class="osmose-sidebar-text"><?php _e('Contactez-nous pour un devis gratuit.', 'osmose-ads'); ?></p>
                                    <?php if ($devis_url): ?>
                                        <a href="<?php echo esc_url($devis_url); ?>" class="osmose-btn-devis-inline">
                                            <?php _e('Demande de devis', 'osmose-ads'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Carte Appel Direct -->
                                <?php if ($phone_raw): ?>
                                <div class="osmose-sidebar-card osmose-card-gradient-call" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                                    <h4 class="osmose-sidebar-subtitle" style="color: white;"><i class="fas fa-phone me-2"></i><?php _e('Appelez-nous', 'osmose-ads'); ?></h4>
                                    <p class="osmose-sidebar-text" style="color: rgba(255,255,255,0.9);"><?php _e('Intervention rapide et devis immédiat', 'osmose-ads'); ?></p>
                                    <a href="<?php echo esc_url(osmose_get_call_tracking_url($ad_id, $ad_slug_for_tracking, $phone_raw, 'sidebar', $current_url)); ?>" 
                                       class="osmose-btn-call-sidebar">
                                        <i class="fas fa-phone-alt"></i>
                                        <?php echo esc_html($phone ?: $phone_raw); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Informations Pratiques -->
                                <div class="osmose-sidebar-card osmose-card-gray">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Informations Pratiques', 'osmose-ads'); ?></h4>
                                    <ul class="osmose-info-list">
                                        <?php if ($company_address): ?>
                                            <li><i class="fas fa-map-marker-alt"></i> <strong><?php _e('Adresse :', 'osmose-ads'); ?></strong> <?php echo esc_html($company_address); ?></li>
                                        <?php endif; ?>
                                        <?php if ($phone_raw): ?>
                                            <li><i class="fas fa-phone"></i> <strong><?php _e('Téléphone :', 'osmose-ads'); ?></strong> <?php echo esc_html($phone ?: $phone_raw); ?></li>
                                        <?php endif; ?>
                                        <?php if ($company_email): ?>
                                            <li><i class="fas fa-envelope"></i> <strong><?php _e('Email :', 'osmose-ads'); ?></strong> <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a></li>
                                        <?php endif; ?>
                                        <?php if ($company_name): ?>
                                            <li><i class="fas fa-building"></i> <strong><?php _e('Société :', 'osmose-ads'); ?></strong> <?php echo esc_html($company_name); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                
                                <!-- Partage Social -->
                                <div class="osmose-sidebar-card osmose-card-border">
                                    <h4 class="osmose-sidebar-subtitle text-center"><?php _e('Partager ce service', 'osmose-ads'); ?></h4>
                                    <div class="osmose-social-share">
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" class="osmose-social-btn osmose-social-fb">
                                            <i class="fab fa-facebook-f"></i>
                                            <span>Facebook</span>
                                        </a>
                                        <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' - ' . $current_url); ?>" target="_blank" class="osmose-social-btn osmose-social-wa">
                                            <i class="fab fa-whatsapp"></i>
                                            <span>WhatsApp</span>
                                        </a>
                                        <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(__('Je vous partage ce service intéressant :', 'osmose-ads') . ' ' . $current_url); ?>" class="osmose-social-btn osmose-social-email">
                                            <i class="fas fa-envelope"></i>
                                            <span>Email</span>
                                        </a>
                                    </div>
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Section Avis Clients (Widgets for Google Reviews) -->
                    <div class="osmose-reviews-section">
                        <div class="osmose-reviews-widget">
                            <?php
                            /**
                             * Intégration Widgets for Google Reviews
                             * Par défaut, on utilise le shortcode principal du plugin.
                             * Vous pouvez filtrer ou surcharger ce shortcode via le hook
                             * "osmose_ads_google_reviews_shortcode" si besoin.
                             */
                            $default_reviews_shortcode = '[trustindex no-registration=google]';
                            $google_reviews_shortcode = apply_filters('osmose_ads_google_reviews_shortcode', $default_reviews_shortcode);
                            
                            if (!empty($google_reviews_shortcode)) {
                                echo do_shortcode($google_reviews_shortcode);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- CTA Section -->
                    <div class="osmose-cta-section">
                        <h3 class="osmose-cta-title"><?php _e('Prêt à Démarrer Votre Projet', 'osmose-ads'); ?> <?php if ($city_name): echo esc_html('à ' . $city_name); endif; ?> ?</h3>
                        <p class="osmose-cta-text"><?php _e('Contactez-nous dès aujourd\'hui pour un devis gratuit et personnalisé', 'osmose-ads'); ?></p>
                        <div class="osmose-cta-buttons">
                            <?php if ($devis_url): ?>
                                <a href="<?php echo esc_url($devis_url); ?>" class="osmose-btn-hero osmose-btn-accent">
                                    <i class="fas fa-calculator"></i>
                                    <?php _e('Demander un Devis Gratuit', 'osmose-ads'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($phone_raw): ?>
                                <a href="<?php echo esc_url(osmose_get_call_tracking_url($ad_id, $ad_slug_for_tracking, $phone_raw, 'footer-cta', $current_url)); ?>" class="osmose-btn-hero osmose-btn-primary">
                                    <i class="fas fa-phone"></i>
                                    <?php _e('Appeler Maintenant', 'osmose-ads'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    
    <!-- Annonces Similaires -->
    <?php if (!empty($related_ads)): ?>
        <section class="osmose-related-section">
            <div class="osmose-container">
                <div class="osmose-section-header">
                    <h2 class="osmose-section-title"><?php _e('Autres Services', 'osmose-ads'); ?> <?php if ($city_name): echo esc_html('à ' . $city_name); endif; ?></h2>
                    <p class="osmose-section-subtitle"><?php _e('Découvrez nos autres services disponibles dans votre ville', 'osmose-ads'); ?></p>
                </div>
                
                <div class="osmose-related-grid">
                    <?php foreach ($related_ads as $related_ad): 
                        $related_city_id = get_post_meta($related_ad->ID, 'city_id', true);
                        $related_city = $related_city_id ? get_post($related_city_id) : null;
                    ?>
                        <div class="osmose-related-card">
                            <div class="osmose-related-content">
                                <h3 class="osmose-related-title"><?php echo esc_html($related_ad->post_title); ?></h3>
                                <?php 
                                $excerpt = wp_trim_words($related_ad->post_content, 20);
                                if ($excerpt):
                                ?>
                                    <p class="osmose-related-excerpt"><?php echo esc_html($excerpt); ?>...</p>
                                <?php endif; ?>
                                <a href="<?php echo get_permalink($related_ad->ID); ?>" class="osmose-related-link">
                                    <?php _e('Voir le service', 'osmose-ads'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
</div>

<!-- Bouton d'appel flottant -->
<?php if ($phone_raw): ?>
    <a href="<?php echo esc_url(osmose_get_call_tracking_url($ad_id, $ad_slug_for_tracking, $phone_raw, 'floating', $current_url)); ?>" id="floatingCallBtn" class="osmose-floating-btn"
       aria-label="<?php printf(__('Appeler %s', 'osmose-ads'), esc_attr($phone ?: $phone_raw)); ?>"
       title="<?php printf(__('Appeler %s', 'osmose-ads'), esc_attr($phone ?: $phone_raw)); ?>">
        <i class="fas fa-phone"></i>
    </a>
<?php endif; ?>

<?php
get_footer();
