<?php
/**
 * Classe Public
 */
class Osmose_Ads_Public {

    public function enqueue_styles() {
        // Charger uniquement sur les pages d'annonces
        global $wp_query, $post;
        $is_ad_page = is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
                      (isset($post) && $post->post_type === 'ad');
        
        if ($is_ad_page) {
            wp_enqueue_style(
                'osmose-ads-public',
                OSMOSE_ADS_PLUGIN_URL . 'public/css/osmose-ads-public.css',
                array(),
                OSMOSE_ADS_VERSION
            );
            
            // Ajouter Bootstrap Icons
            wp_enqueue_style(
                'bootstrap-icons',
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
                array(),
                '1.11.0'
            );
        }
    }

    public function enqueue_scripts() {
        // Charger sur toutes les pages où des annonces peuvent être affichées
        global $wp_query, $post;
        $is_ad_page = is_singular('ad') || get_query_var('ad_slug') || 
                      (isset($wp_query->query_vars['ad_slug']) && !empty($wp_query->query_vars['ad_slug'])) ||
                      (isset($post) && $post->post_type === 'ad');
        
        if ($is_ad_page) {
            wp_enqueue_script(
                'osmose-ads-public',
                OSMOSE_ADS_PLUGIN_URL . 'public/js/osmose-ads-public.js',
                array('jquery'),
                OSMOSE_ADS_VERSION,
                true
            );
            
            // Localiser le script avec les variables de tracking
            wp_localize_script('osmose-ads-public', 'osmoseAdsTracking', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('osmose_ads_track_call'),
            ));
        }
    }
    
    /**
     * Ajouter les variables de tracking dans le footer pour toutes les pages avec des annonces
     */
    public function add_tracking_variables() {
        global $post;
        
        // Vérifier si on est sur une page avec une annonce
        if (isset($post) && $post->post_type === 'ad') {
            $ad_id = $post->ID;
            $ad_slug = $post->post_name;
            $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $phone = get_option('osmose_ads_company_phone', '');
            
            ?>
            <script>
            // Variables pour le tracking des appels
            if (typeof window.osmoseAdsTracking === 'undefined') {
                window.osmoseAdsTracking = {
                    ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    nonce: '<?php echo wp_create_nonce('osmose_ads_track_call'); ?>'
                };
            }
            
            // Ajouter les attributs data aux liens tel: existants si nécessaire
            jQuery(document).ready(function($) {
                $('a[href^="tel:"]').each(function() {
                    var $link = $(this);
                    if (!$link.hasClass('osmose-track-call')) {
                        $link.addClass('osmose-track-call');
                        if (!$link.data('ad-id')) {
                            $link.attr('data-ad-id', '<?php echo esc_js($ad_id); ?>');
                            $link.attr('data-ad-slug', '<?php echo esc_js($ad_slug); ?>');
                            $link.attr('data-page-url', '<?php echo esc_js($page_url); ?>');
                            $link.attr('data-phone', '<?php echo esc_js($phone); ?>');
                        }
                    }
                });
            });
            </script>
            <?php
        }
    }
}



