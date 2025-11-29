/**
 * Scripts pour le simulateur de devis (Version 2 - 5 étapes)
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $simulator = $('#osmose-simulator');
        if (!$simulator.length) {
            return;
        }

        var currentStep = 0;
        var totalSteps = 5;
        var formData = {};
        var projectTypes = typeof window.osmoseSimulatorProjects !== 'undefined' ? window.osmoseSimulatorProjects : {};

        // Initialiser le simulateur
        initSimulator();

        function initSimulator() {
            updateProgress();
            setupEventListeners();
        }

        function setupEventListeners() {
            // Étape 0: Validation des champs obligatoires
            $simulator.find('#first_name, #last_name, #email, #phone').on('input', function() {
                validateStep0();
            });

            // Étape 1: Radio buttons pour type de logement
            $simulator.find('input[name="property_type"]').on('change', function() {
                var $nextBtn = $simulator.find('.osmose-step-content[data-step="1"] .osmose-btn-next');
                $nextBtn.prop('disabled', false);
            });

            // Étape 2: Validation des champs
            $simulator.find('#postal_code, #address, #surface').on('input', function() {
                validateStep2();
            });

            // Étape 3: Sélection multiple du type de projet (2-3 max)
            $simulator.on('change', 'input[name="project_type[]"]', function() {
                var checkedCount = $simulator.find('input[name="project_type[]"]:checked').length;
                var $currentStep = $simulator.find('.osmose-step-content[data-step="3"]');
                var $nextBtn = $currentStep.find('.osmose-btn-next');
                
                // Limiter à 3 projets maximum
                if (checkedCount > 3) {
                    $(this).prop('checked', false);
                    showError('Vous ne pouvez sélectionner que 3 projets maximum');
                    checkedCount--;
                }
                
                // Activer le bouton si au moins 1 projet est sélectionné
                if (checkedCount > 0 && checkedCount <= 3) {
                    $nextBtn.prop('disabled', false);
                    // Masquer les erreurs si tout est OK
                    $simulator.find('.osmose-simulator-error').hide();
                } else {
                    $nextBtn.prop('disabled', true);
                }
            });

            // Étape 4: Checkboxes pour détails du projet (dynamique)
            $simulator.on('change', 'input[name^="project_details_"]', function() {
                updateSubmitButton();
            });

            // Boutons Next
            $simulator.on('click', '.osmose-btn-next', function(e) {
                e.preventDefault();
                
                // Vérifier si le bouton est désactivé
                if ($(this).prop('disabled')) {
                    return false;
                }
                
                // Sauvegarder les données avant validation
                saveStepData();
                
                if (validateCurrentStep()) {
                    nextStep();
                }
            });

            // Boutons Previous
            $simulator.on('click', '.osmose-btn-prev', function(e) {
                e.preventDefault();
                saveStepData();
                previousStep();
            });

            // Soumission du formulaire
            $simulator.find('#osmose-simulator-form').on('submit', function(e) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    saveStepData();
                    submitForm();
                }
            });
        }

        function validateStep0() {
            var isValid = true;
            var requiredFields = ['first_name', 'last_name', 'email', 'phone'];
            
            requiredFields.forEach(function(field) {
                var $field = $simulator.find('#' + field);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                } else {
                    $field.removeClass('error');
                    
                    // Validation email
                    if (field === 'email' && !isValidEmail(value)) {
                        isValid = false;
                        $field.addClass('error');
                    }
                }
            });
            
            var $nextBtn = $simulator.find('.osmose-step-content[data-step="0"] .osmose-btn-next');
            $nextBtn.prop('disabled', !isValid);
            
            return isValid;
        }

        function validateStep2() {
            var isValid = true;
            var requiredFields = ['postal_code', 'surface']; // Adresse n'est plus obligatoire
            
            requiredFields.forEach(function(field) {
                var $field = $simulator.find('#' + field);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                } else {
                    $field.removeClass('error');
                    
                    // Validation code postal (5 chiffres)
                    if (field === 'postal_code' && !/^\d{5}$/.test(value)) {
                        isValid = false;
                        $field.addClass('error');
                    }
                    
                    // Validation surface (nombre positif)
                    if (field === 'surface' && (isNaN(value) || parseFloat(value) <= 0)) {
                        isValid = false;
                        $field.addClass('error');
                    }
                }
            });
            
            var $nextBtn = $simulator.find('.osmose-step-content[data-step="2"] .osmose-btn-next');
            $nextBtn.prop('disabled', !isValid);
            
            return isValid;
        }

        function validateCurrentStep() {
            var isValid = true;

            // Validation étape 0
            if (currentStep === 0) {
                isValid = validateStep0();
                if (!isValid) {
                    showError('Veuillez remplir tous les champs obligatoires');
                }
            }

            // Validation étape 1
            if (currentStep === 1) {
                var $propertyType = $simulator.find('input[name="property_type"]:checked');
                if (!$propertyType.length) {
                    isValid = false;
                    showError('Veuillez sélectionner un type de logement');
                }
            }

            // Validation étape 2
            if (currentStep === 2) {
                isValid = validateStep2();
                if (!isValid) {
                    showError('Veuillez remplir tous les champs obligatoires correctement');
                }
            }

            // Validation étape 3
            if (currentStep === 3) {
                var $currentStep = $simulator.find('.osmose-step-content[data-step="3"]');
                var $projectTypes = $currentStep.find('input[name="project_type[]"]:checked');
                var $nextBtn = $currentStep.find('.osmose-btn-next');
                
                if ($projectTypes.length === 0) {
                    isValid = false;
                    showError('Veuillez sélectionner au moins un type de projet');
                    $nextBtn.prop('disabled', true);
                } else if ($projectTypes.length > 3) {
                    isValid = false;
                    showError('Vous ne pouvez sélectionner que 3 projets maximum');
                    $nextBtn.prop('disabled', true);
                } else {
                    // Validation OK, s'assurer que le bouton est activé
                    $nextBtn.prop('disabled', false);
                }
            }

            // Validation étape 4
            if (currentStep === 4) {
                var $projectDetails = $simulator.find('input[name^="project_details_"]:checked');
                if ($projectDetails.length === 0) {
                    isValid = false;
                    showError('Veuillez sélectionner au moins un détail pour chaque projet');
                }
            }

            return isValid;
        }

        function isValidEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function loadProjectDetails() {
            var selectedProjects = [];
            $simulator.find('input[name="project_type[]"]:checked').each(function() {
                selectedProjects.push($(this).val());
            });
            
            if (selectedProjects.length === 0) {
                return;
            }
            
            var $container = $simulator.find('#project-details-container');
            $container.empty();
            
            selectedProjects.forEach(function(projectKey) {
                var project = projectTypes[projectKey];
                if (!project || !project.options) {
                    return;
                }
                
                // Créer une section pour chaque projet
                var $section = $('<div>', {
                    class: 'project-details-section mb-4'
                });
                
                var $sectionTitle = $('<h4>', {
                    class: 'project-details-title',
                    style: 'margin-bottom: 1rem; font-size: 1.2rem; color: #1f2937;'
                }).text('Détails pour : ' + project.label);
                
                var $optionsGrid = $('<div>', {
                    class: 'osmose-option-grid osmose-option-grid-multiple'
                });
                
                project.options.forEach(function(option) {
                    var optionLabel = option.label || option;
                    var optionValue = option.value || option;
                    
                    var $label = $('<label>', {
                        class: 'osmose-option-card osmose-option-checkbox'
                    });
                    
                    var $input = $('<input>', {
                        type: 'checkbox',
                        name: 'project_details_' + projectKey + '[]',
                        value: optionValue,
                        'data-project-key': projectKey
                    });
                    
                    var $icon = $('<div>', {
                        class: 'option-icon'
                    }).text('✓');
                    
                    var $optionLabel = $('<div>', {
                        class: 'option-label'
                    }).text(optionLabel);
                    
                    $label.append($input, $icon, $optionLabel);
                    $optionsGrid.append($label);
                });
                
                $section.append($sectionTitle, $optionsGrid);
                $container.append($section);
            });
            
            // Vérifier si au moins un détail est sélectionné
            updateSubmitButton();
        }
        
        function updateSubmitButton() {
            var $projectDetails = $simulator.find('input[name^="project_details_"]:checked');
            var $submitBtn = $simulator.find('.osmose-btn-submit');
            $submitBtn.prop('disabled', $projectDetails.length === 0);
        }

        function saveStepData() {
            var $currentStep = $simulator.find('.osmose-step-content.active');
            
            if (currentStep === 0) {
                formData.first_name = $currentStep.find('#first_name').val();
                formData.last_name = $currentStep.find('#last_name').val();
                formData.email = $currentStep.find('#email').val();
                formData.phone = $currentStep.find('#phone').val();
            } else if (currentStep === 1) {
                formData.property_type = $currentStep.find('input[name="property_type"]:checked').val();
            } else if (currentStep === 2) {
                formData.postal_code = $currentStep.find('#postal_code').val();
                formData.address = $currentStep.find('#address').val();
                formData.city = $currentStep.find('#city').val();
                formData.surface = $currentStep.find('#surface').val();
            } else if (currentStep === 3) {
                formData.project_type = [];
                $currentStep.find('input[name="project_type[]"]:checked').each(function() {
                    formData.project_type.push($(this).val());
                });
            } else if (currentStep === 4) {
                formData.project_details = {};
                $currentStep.find('input[name^="project_details_"]:checked').each(function() {
                    var projectKey = $(this).data('project-key');
                    if (!formData.project_details[projectKey]) {
                        formData.project_details[projectKey] = [];
                    }
                    formData.project_details[projectKey].push($(this).val());
                });
                formData.message = $currentStep.find('#message').val();
            }
        }

        function nextStep() {
            if (currentStep < totalSteps - 1) {
                currentStep++;
                updateStepDisplay();
                updateProgress();
                
                // Si on passe à l'étape 4, charger les détails des projets sélectionnés
                if (currentStep === 4) {
                    loadProjectDetails();
                }
            }
        }

        function previousStep() {
            if (currentStep > 0) {
                currentStep--;
                updateStepDisplay();
                updateProgress();
            }
        }

        function updateStepDisplay() {
            $simulator.find('.osmose-step-content').removeClass('active');
            $simulator.find('.osmose-step-content[data-step="' + currentStep + '"]').addClass('active');
            
            // Restaurer les données sauvegardées
            restoreStepData();
            
            // Si on arrive à l'étape 4, charger les détails des projets sélectionnés
            if (currentStep === 4) {
                loadProjectDetails();
                // Restaurer les sélections de détails
                if (formData.project_details) {
                    Object.keys(formData.project_details).forEach(function(projectKey) {
                        formData.project_details[projectKey].forEach(function(detail) {
                            $simulator.find('input[name="project_details_' + projectKey + '[]"][value="' + detail + '"]').prop('checked', true);
                        });
                    });
                    updateSubmitButton();
                }
            }
            
            // Si on revient à l'étape 3, restaurer les sélections
            if (currentStep === 3 && formData.project_type) {
                formData.project_type.forEach(function(projectKey) {
                    $simulator.find('input[name="project_type[]"][value="' + projectKey + '"]').prop('checked', true);
                });
                var checkedCount = $simulator.find('input[name="project_type[]"]:checked').length;
                $simulator.find('.osmose-step-content[data-step="3"] .osmose-btn-next').prop('disabled', checkedCount === 0);
            }
        }

        function restoreStepData() {
            if (currentStep === 0) {
                if (formData.first_name) $simulator.find('#first_name').val(formData.first_name);
                if (formData.last_name) $simulator.find('#last_name').val(formData.last_name);
                if (formData.email) $simulator.find('#email').val(formData.email);
                if (formData.phone) $simulator.find('#phone').val(formData.phone);
                validateStep0();
            } else if (currentStep === 1 && formData.property_type) {
                $simulator.find('input[name="property_type"][value="' + formData.property_type + '"]').prop('checked', true);
                $simulator.find('.osmose-step-content[data-step="1"] .osmose-btn-next').prop('disabled', false);
            } else if (currentStep === 2) {
                if (formData.postal_code) $simulator.find('#postal_code').val(formData.postal_code);
                if (formData.address) $simulator.find('#address').val(formData.address);
                if (formData.city) $simulator.find('#city').val(formData.city);
                if (formData.surface) $simulator.find('#surface').val(formData.surface);
                validateStep2();
            } else if (currentStep === 3 && formData.project_type) {
                $simulator.find('input[name="project_type"][value="' + formData.project_type + '"]').prop('checked', true);
                loadProjectDetails(formData.project_type);
                $simulator.find('.osmose-step-content[data-step="3"] .osmose-btn-next').prop('disabled', false);
            } else if (currentStep === 4) {
                if (formData.project_type) {
                    loadProjectDetails(formData.project_type);
                    if (formData.project_details) {
                        formData.project_details.forEach(function(value) {
                            $simulator.find('input[name="project_details[]"][value="' + value + '"]').prop('checked', true);
                        });
                        $simulator.find('.osmose-btn-submit').prop('disabled', false);
                    }
                }
                if (formData.message) $simulator.find('#message').val(formData.message);
            }
        }

        function updateProgress() {
            $simulator.find('.osmose-step-indicator').each(function() {
                var $indicator = $(this);
                var stepNum = parseInt($indicator.data('step'));
                
                $indicator.removeClass('active completed');
                
                if (stepNum < currentStep) {
                    $indicator.addClass('completed');
                } else if (stepNum === currentStep) {
                    $indicator.addClass('active');
                }
            });
        }

        function submitForm() {
            var $form = $simulator.find('#osmose-simulator-form');
            var $submitBtn = $form.find('.osmose-btn-submit');
            var originalText = $submitBtn.text();

            // Désactiver le bouton
            $submitBtn.prop('disabled', true).text('Envoi en cours...');

            // Préparer les données
            var submitData = {
                action: 'osmose_ads_submit_quote_request',
                nonce: typeof osmoseAdsSimulator !== 'undefined' ? osmoseAdsSimulator.nonce : '',
                data: formData
            };

            // Envoyer via AJAX
            $.ajax({
                url: typeof osmoseAdsSimulator !== 'undefined' ? osmoseAdsSimulator.ajax_url : ajaxurl,
                type: 'POST',
                data: submitData,
                success: function(response) {
                    if (response.success) {
                        showSuccess();
                        $form.hide();
                    } else {
                        showError(response.data && response.data.message ? response.data.message : 'Une erreur est survenue');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    showError('Erreur de connexion. Veuillez réessayer.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        function showSuccess() {
            $simulator.find('.osmose-simulator-success').fadeIn();
            $simulator.find('.osmose-simulator-progress, .osmose-simulator-form').hide();
        }

        function showError(message) {
            var $errorDiv = $simulator.find('.osmose-simulator-error');
            $errorDiv.find('.error-message').text(message);
            $errorDiv.fadeIn();
            
            setTimeout(function() {
                $errorDiv.fadeOut();
            }, 5000);
        }
    });

})(jQuery);

