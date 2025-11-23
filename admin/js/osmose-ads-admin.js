/**
 * Scripts admin pour Osmose ADS
 */

(function($) {
    'use strict';
    
    // Initialisation
    $(document).ready(function() {
        // Scripts spécifiques seront ajoutés ici
        console.log('Osmose ADS Admin initialized');
        
        // Gestion des onglets pour la page des villes
        initCityTabs();
    });
    
    /**
     * Initialiser les onglets sur la page des villes
     */
    function initCityTabs() {
        // Gestion des onglets
        $('.osmose-tab-btn').on('click', function() {
            var tab = $(this).data('tab');
            
            $('.osmose-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.osmose-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });
        
        // Gestion des sous-onglets
        $('.osmose-subtab-btn').on('click', function() {
            var subtab = $(this).data('subtab');
            
            $('.osmose-subtab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.osmose-subtab-content').removeClass('active');
            $('#subtab-' + subtab).addClass('active');
        });
    }
    
})(jQuery);



