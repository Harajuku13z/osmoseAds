/**
 * Scripts publics pour Osmose ADS
 * Inclut le tracking des appels téléphoniques
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Osmose ADS Public initialized');
        
        // Tracking des appels téléphoniques
        function trackPhoneCall(event) {
            event.preventDefault(); // Empêcher l'action par défaut temporairement
            
            var $link = $(event.currentTarget);
            var adId = $link.data('ad-id') || $link.attr('data-ad-id') || '';
            var adSlug = $link.data('ad-slug') || $link.attr('data-ad-slug') || '';
            var pageUrl = $link.data('page-url') || $link.attr('data-page-url') || window.location.href;
            var phone = $link.data('phone') || $link.attr('data-phone') || '';
            
            // Si les données ne sont pas dans les attributs data, essayer de les récupérer depuis le href
            if (!phone && $link.attr('href')) {
                var href = $link.attr('href');
                var match = href.match(/tel:([^"]+)/);
                if (match) {
                    phone = match[1];
                }
            }
            
            // Vérifier que les variables de tracking sont disponibles
            if (typeof window.osmoseAdsTracking === 'undefined') {
                console.warn('Osmose ADS: Tracking variables not available, redirecting anyway');
                // Rediriger quand même vers le lien tel:
                window.location.href = $link.attr('href');
                return;
            }
            
            // Envoyer le tracking via AJAX (non-bloquant)
            $.ajax({
                url: window.osmoseAdsTracking.ajax_url,
                type: 'POST',
                data: {
                    action: 'osmose_ads_track_call',
                    nonce: window.osmoseAdsTracking.nonce,
                    ad_id: adId,
                    ad_slug: adSlug,
                    page_url: pageUrl,
                    phone: phone
                },
                success: function(response) {
                    console.log('Osmose ADS: Call tracked successfully', response);
                },
                error: function(xhr, status, error) {
                    console.warn('Osmose ADS: Failed to track call', error, xhr.responseText);
                },
                complete: function() {
                    // Après le tracking (succès ou échec), rediriger vers le lien tel:
                    window.location.href = $link.attr('href');
                }
            });
        }
        
        // Attacher le tracking à tous les liens tel: avec la classe osmose-track-call
        $(document).on('click', 'a.osmose-track-call', trackPhoneCall);
        
        // Afficher le CTA flottant après scroll
        var $floatingCta = $('.ad-cta-floating');
        if ($floatingCta.length) {
            var scrollThreshold = 300; // Afficher après 300px de scroll
            var hasScrolled = false;
            
            $(window).on('scroll', function() {
                if (!hasScrolled && $(window).scrollTop() > scrollThreshold) {
                    $floatingCta.addClass('show');
                    hasScrolled = true;
                } else if (hasScrolled && $(window).scrollTop() <= scrollThreshold) {
                    $floatingCta.removeClass('show');
                    hasScrolled = false;
                }
            });
        }
    });
    
})(jQuery);
