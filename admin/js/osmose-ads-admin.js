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
     * Initialiser les onglets sur la page des villes (Bootstrap compatible)
     */
    function initCityTabs() {
        // Gestion des onglets principaux avec Bootstrap
        $('.osmose-tab-btn').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            if (!tab) return;
            
            // Retirer active de tous les boutons
            $('.osmose-tab-btn').removeClass('active');
            // Ajouter active au bouton cliqué
            $(this).addClass('active');
            
            // Masquer tous les panneaux
            $('.tab-pane').removeClass('show active');
            // Afficher le panneau correspondant
            $('#tab-' + tab).addClass('show active');
        });
        
        // Gestion des sous-onglets (pour les méthodes d'import)
        $('.osmose-subtab-btn').on('click', function(e) {
            e.preventDefault();
            var subtab = $(this).data('subtab');
            
            if (!subtab) return;
            
            $('.osmose-subtab-btn').removeClass('active');
            $(this).addClass('active');
            
            $('.osmose-subtab-content').removeClass('active show').hide();
            $('#subtab-' + subtab).addClass('active show').show();
        });
    }
    
})(jQuery);



