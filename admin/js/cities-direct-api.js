/**
 * Script pour l'import de villes via l'API officielle geo.api.gouv.fr
 * Appels directs à l'API depuis JavaScript
 */

(function($) {
    'use strict';
    
    const API_BASE_URL = 'https://geo.api.gouv.fr';
    
    // Fonction pour s'assurer que osmoseAds est disponible
    function ensureOsmoseAds() {
        // Vérifier d'abord dans window.osmoseAds
        if (typeof window.osmoseAds !== 'undefined' && window.osmoseAds.ajax_url && window.osmoseAds.nonce) {
            return window.osmoseAds;
        }
        
        // Essayer de récupérer depuis window.ajaxurl (WordPress standard)
        var ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
        var nonce = '';
        
        console.warn('Osmose ADS: osmoseAds object not properly initialized. Using fallback with ajaxurl.');
        
        window.osmoseAds = {
            ajax_url: ajaxUrl,
            nonce: nonce,
            needs_nonce: true
        };
        
        return window.osmoseAds;
    }
    
    // Attendre que le DOM soit prêt
    $(document).ready(function() {
        console.log('Osmose ADS: Initializing direct API integration...');
        console.log('Osmose ADS: Checking osmoseAds availability...');
        
        // Vérifier immédiatement
        var osmoseAds = ensureOsmoseAds();
        console.log('Osmose ADS: osmoseAds object:', osmoseAds);
        
        if (!osmoseAds || !osmoseAds.ajax_url) {
            console.error('Osmose ADS: CRITICAL - ajax_url is missing!', osmoseAds);
            alert('Erreur: Configuration AJAX manquante. Vérifiez la console pour plus de détails.');
            return;
        }
        
        if (!osmoseAds.nonce) {
            console.warn('Osmose ADS: WARNING - nonce is missing. Some features may not work.');
        }
        
        // Charger les départements et régions immédiatement
        loadDepartmentsDirect();
        loadRegionsDirect();
        
        // Recherche de ville avec autocomplétion
        initCitySearch();
        
        // Gestionnaires de formulaires d'import
        initImportForms();
    });
    
    /**
     * Charger les départements directement depuis l'API
     */
    function loadDepartmentsDirect() {
        var select = $('#department_code');
        if (!select.length) {
            console.error('Department select not found');
            return;
        }
        
        select.prop('disabled', true);
        select.html('<option value="">Chargement...</option>');
        
        $.ajax({
            url: API_BASE_URL + '/departements',
            type: 'GET',
            dataType: 'json',
            timeout: 30000,
            success: function(data) {
                console.log('Departments loaded:', data.length);
                select.prop('disabled', false);
                select.empty();
                select.append('<option value="">-- Sélectionner un département --</option>');
                
                if (Array.isArray(data) && data.length > 0) {
                    // Trier par code
                    data.sort(function(a, b) {
                        return a.code.localeCompare(b.code);
                    });
                    
                    $.each(data, function(i, dept) {
                        if (dept && dept.code && dept.nom) {
                            var option = $('<option>')
                                .attr('value', dept.code)
                                .text(dept.nom + ' (' + dept.code + ')');
                            select.append(option);
                        }
                    });
                } else {
                    select.html('<option value="">Erreur: Aucun département trouvé</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading departments:', error);
                select.prop('disabled', false);
                select.html('<option value="">Erreur de connexion à l\'API</option>');
            }
        });
    }
    
    /**
     * Charger les régions directement depuis l'API
     */
    function loadRegionsDirect() {
        var select = $('#region_code');
        if (!select.length) {
            console.error('Region select not found');
            return;
        }
        
        select.prop('disabled', true);
        select.html('<option value="">Chargement...</option>');
        
        $.ajax({
            url: API_BASE_URL + '/regions',
            type: 'GET',
            dataType: 'json',
            timeout: 30000,
            success: function(data) {
                console.log('Regions loaded:', data.length);
                select.prop('disabled', false);
                select.empty();
                select.append('<option value="">-- Sélectionner une région --</option>');
                
                if (Array.isArray(data) && data.length > 0) {
                    // Trier par nom
                    data.sort(function(a, b) {
                        return a.nom.localeCompare(b.nom);
                    });
                    
                    $.each(data, function(i, region) {
                        if (region && region.code && region.nom) {
                            var option = $('<option>')
                                .attr('value', region.code)
                                .text(region.nom);
                            select.append(option);
                        }
                    });
                } else {
                    select.html('<option value="">Erreur: Aucune région trouvée</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading regions:', error);
                select.prop('disabled', false);
                select.html('<option value="">Erreur de connexion à l\'API</option>');
            }
        });
    }
    
    /**
     * Initialiser la recherche de ville
     */
    function initCitySearch() {
        var searchTimeout;
        var searchInput = $('#city_search');
        var resultsDiv = $('#city-search-results');
        var cityCodeInput = $('#city_code');
        
        if (!searchInput.length) return;
        
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            var search = $(this).val().trim();
            
            if (search.length < 3) {
                resultsDiv.hide().empty();
                cityCodeInput.val('');
                return;
            }
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: API_BASE_URL + '/communes',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        nom: search,
                        fields: 'nom,code,codeDepartement,codeRegion,population,codesPostaux',
                        boost: 'population',
                        limit: 5
                    },
                    success: function(data) {
                        resultsDiv.empty();
                        
                        if (Array.isArray(data) && data.length > 0) {
                            $.each(data, function(i, city) {
                                var postalCode = city.codesPostaux && city.codesPostaux.length > 0 ? city.codesPostaux[0] : '';
                                var dept = city.codeDepartement || '';
                                var item = $('<div>')
                                    .addClass('city-search-item')
                                    .attr('data-code', city.code)
                                    .html('<strong>' + city.nom + '</strong><br><small>' + postalCode + ' - ' + dept + '</small>');
                                resultsDiv.append(item);
                            });
                            resultsDiv.show();
                        } else {
                            resultsDiv.hide();
                        }
                    },
                    error: function() {
                        resultsDiv.hide().empty();
                    }
                });
            }, 500);
        });
        
        // Sélection d'une ville
        $(document).on('click', '.city-search-item', function() {
            var code = $(this).data('code');
            var name = $(this).find('strong').text();
            cityCodeInput.val(code);
            searchInput.val(name);
            resultsDiv.hide();
        });
    }
    
    /**
     * Initialiser les formulaires d'import
     */
    function initImportForms() {
        // Import par département
        $('#import-department-form').on('submit', function(e) {
            e.preventDefault();
            var deptCode = $('#department_code').val();
            if (!deptCode) {
                alert('Veuillez sélectionner un département');
                return;
            }
            importByDepartment(deptCode);
        });
        
        // Import par région
        $('#import-region-form').on('submit', function(e) {
            e.preventDefault();
            var regionCode = $('#region_code').val();
            if (!regionCode) {
                alert('Veuillez sélectionner une région');
                return;
            }
            importByRegion(regionCode);
        });
        
        // Import par rayon
        $('#import-distance-form').on('submit', function(e) {
            e.preventDefault();
            var cityCode = $('#city_code').val();
            var distance = parseFloat($('#distance_km').val()) || 10;
            
            if (!cityCode) {
                alert('Veuillez sélectionner une ville de référence');
                return;
            }
            
            if (!distance || distance < 1) {
                alert('Veuillez entrer un rayon valide (minimum 1 km)');
                return;
            }
            
            importByDistance(cityCode, distance);
        });
    }
    
    /**
     * Importer par département
     */
    function importByDepartment(departmentCode) {
        var resultDiv = $('#import-result');
        resultDiv.html('<div class="alert alert-info">Récupération des communes du département...</div>');
        $('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: API_BASE_URL + '/departements/' + departmentCode + '/communes',
            type: 'GET',
            dataType: 'json',
            data: {
                fields: 'nom,code,codeDepartement,codeRegion,centre,population,codesPostaux,surface'
            },
            timeout: 60000,
            success: function(communes) {
                console.log('Communes récupérées:', communes.length);
                sendToWordPressForImport(communes, 'department');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching communes:', error);
                resultDiv.html('<div class="alert alert-danger">Erreur lors de la récupération des communes: ' + error + '</div>');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    }
    
    /**
     * Importer par région
     */
    function importByRegion(regionCode) {
        var resultDiv = $('#import-result');
        resultDiv.html('<div class="alert alert-info">Récupération des départements de la région...</div>');
        $('button[type="submit"]').prop('disabled', true);
        
        // D'abord récupérer les départements
        $.ajax({
            url: API_BASE_URL + '/regions/' + regionCode + '/departements',
            type: 'GET',
            dataType: 'json',
            timeout: 30000,
            success: function(departments) {
                if (!Array.isArray(departments) || departments.length === 0) {
                    resultDiv.html('<div class="alert alert-danger">Aucun département trouvé pour cette région</div>');
                    $('button[type="submit"]').prop('disabled', false);
                    return;
                }
                
                resultDiv.html('<div class="alert alert-info">Récupération des communes depuis ' + departments.length + ' département(s)...</div>');
                
                var allCommunes = [];
                var loadedCount = 0;
                var totalDepts = departments.length;
                
                $.each(departments, function(i, dept) {
                    $.ajax({
                        url: API_BASE_URL + '/departements/' + dept.code + '/communes',
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            fields: 'nom,code,codeDepartement,codeRegion,centre,population,codesPostaux,surface'
                        },
                        timeout: 60000,
                        success: function(communes) {
                            if (Array.isArray(communes)) {
                                allCommunes = allCommunes.concat(communes);
                            }
                            loadedCount++;
                            
                            if (loadedCount === totalDepts) {
                                console.log('Toutes les communes récupérées:', allCommunes.length);
                                sendToWordPressForImport(allCommunes, 'region');
                            }
                        },
                        error: function() {
                            loadedCount++;
                            if (loadedCount === totalDepts) {
                                sendToWordPressForImport(allCommunes, 'region');
                            }
                        }
                    });
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching departments:', error);
                resultDiv.html('<div class="alert alert-danger">Erreur lors de la récupération des départements: ' + error + '</div>');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    }
    
    /**
     * Importer par rayon
     */
    function importByDistance(cityCode, distanceKm) {
        var resultDiv = $('#import-result');
        resultDiv.html('<div class="alert alert-info">Récupération des informations de la ville...</div>');
        $('button[type="submit"]').prop('disabled', true);
        
        // D'abord récupérer la ville de référence
        $.ajax({
            url: API_BASE_URL + '/communes/' + cityCode,
            type: 'GET',
            dataType: 'json',
            data: {
                fields: 'nom,code,codeDepartement,codeRegion,centre,population,codesPostaux'
            },
            timeout: 30000,
            success: function(cityRef) {
                var deptCode = cityRef.codeDepartement;
                var lat = cityRef.centre && cityRef.centre.coordinates ? cityRef.centre.coordinates[1] : null;
                var lon = cityRef.centre && cityRef.centre.coordinates ? cityRef.centre.coordinates[0] : null;
                
                if (!deptCode || !lat || !lon) {
                    resultDiv.html('<div class="alert alert-danger">Impossible de récupérer les coordonnées de la ville</div>');
                    $('button[type="submit"]').prop('disabled', false);
                    return;
                }
                
                resultDiv.html('<div class="alert alert-info">Récupération des communes du département et calcul des distances...</div>');
                
                // Récupérer toutes les communes du département
                $.ajax({
                    url: API_BASE_URL + '/departements/' + deptCode + '/communes',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        fields: 'nom,code,codeDepartement,codeRegion,centre,population,codesPostaux,surface'
                    },
                    timeout: 60000,
                    success: function(allCommunes) {
                        if (!Array.isArray(allCommunes)) {
                            resultDiv.html('<div class="alert alert-danger">Erreur lors de la récupération des communes</div>');
                            $('button[type="submit"]').prop('disabled', false);
                            return;
                        }
                        
                        // Filtrer par distance
                        var filteredCommunes = allCommunes.filter(function(commune) {
                            if (!commune.centre || !commune.centre.coordinates) return false;
                            var communeLon = commune.centre.coordinates[0];
                            var communeLat = commune.centre.coordinates[1];
                            var distance = calculateDistance(lat, lon, communeLat, communeLon);
                            return distance <= distanceKm;
                        });
                        
                        // Ajouter la distance calculée
                        filteredCommunes.forEach(function(commune) {
                            var communeLon = commune.centre.coordinates[0];
                            var communeLat = commune.centre.coordinates[1];
                            commune._distance = calculateDistance(lat, lon, communeLat, communeLon);
                        });
                        
                        // Trier par distance
                        filteredCommunes.sort(function(a, b) {
                            return (a._distance || 0) - (b._distance || 0);
                        });
                        
                        console.log('Communes dans le rayon:', filteredCommunes.length);
                        sendToWordPressForImport(filteredCommunes, 'distance');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching communes:', error);
                        resultDiv.html('<div class="alert alert-danger">Erreur lors de la récupération des communes: ' + error + '</div>');
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching city:', error);
                resultDiv.html('<div class="alert alert-danger">Erreur lors de la récupération de la ville: ' + error + '</div>');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    }
    
    /**
     * Calculer la distance entre deux points (formule de Haversine)
     */
    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371; // Rayon de la Terre en km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    /**
     * Envoyer les communes à WordPress pour l'import (SANS AJAX - Formulaire POST)
     */
    function sendToWordPressForImport(communes, importType) {
        var resultDiv = $('#import-result');
        
        if (!communes || communes.length === 0) {
            resultDiv.html('<div class="alert alert-warning">Aucune commune à importer</div>');
            $('button[type="submit"]').prop('disabled', false);
            return;
        }
        
        console.log('Osmose ADS: Preparing to import ' + communes.length + ' communes via form POST');
        
        // Créer un formulaire POST pour envoyer les données
        var form = $('<form>')
            .attr('method', 'POST')
            .attr('action', window.location.href)
            .css('display', 'none');
        
        // Champ pour les communes (JSON)
        form.append($('<input>')
            .attr('type', 'hidden')
            .attr('name', 'communes_json')
            .val(JSON.stringify(communes))
        );
        
        // Champ pour le type d'import
        form.append($('<input>')
            .attr('type', 'hidden')
            .attr('name', 'import_type')
            .val(importType)
        );
        
        // Nonce de sécurité - essayer plusieurs emplacements
        var nonce = $('#import_nonce').val() || 
                    $('input[name="import_nonce"]').val() || 
                    '';
        
        if (!nonce) {
            console.error('Osmose ADS: Import nonce not found in page!');
            resultDiv.html('<div class="alert alert-danger">Erreur de sécurité : nonce manquant. Rechargez la page.</div>');
            $('button[type="submit"]').prop('disabled', false);
            return;
        }
        
        form.append($('<input>')
            .attr('type', 'hidden')
            .attr('name', 'import_nonce')
            .val(nonce)
        );
        
        // Champ pour déclencher l'import
        form.append($('<input>')
            .attr('type', 'hidden')
            .attr('name', 'import_communes')
            .val('1')
        );
        
        // Ajouter le formulaire au body et le soumettre
        $('body').append(form);
        
        // Afficher un message de chargement
        resultDiv.html(
            '<div class="alert alert-info">' +
            '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
            'Import de ' + communes.length + ' commune(s) en cours, veuillez patienter...' +
            '</div>'
        );
        
        // Soumettre le formulaire
        form.submit();
    }
    
})(jQuery);

