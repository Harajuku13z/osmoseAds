/**
 * Scripts pour le simulateur de devis
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $simulator = $('#osmose-simulator');
        if (!$simulator.length) {
            return;
        }

        var currentStep = 1;
        var totalSteps = 3;
        var formData = {};

        // Initialiser le simulateur
        initSimulator();

        function initSimulator() {
            updateProgress();
            setupEventListeners();
        }

        function setupEventListeners() {
            // Radio buttons pour l'étape 1
            $simulator.find('input[name="property_type"]').on('change', function() {
                var $nextBtn = $simulator.find('.osmose-step-content[data-step="1"] .osmose-btn-next');
                $nextBtn.prop('disabled', false);
            });

            // Checkboxes pour l'étape 2
            $simulator.find('input[name="work_type[]"]').on('change', function() {
                var checkedCount = $simulator.find('input[name="work_type[]"]:checked').length;
                var $nextBtn = $simulator.find('.osmose-step-content[data-step="2"] .osmose-btn-next');
                $nextBtn.prop('disabled', checkedCount === 0);
            });

            // Boutons Next
            $simulator.on('click', '.osmose-btn-next', function(e) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    saveStepData();
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

        function validateCurrentStep() {
            var $currentStep = $simulator.find('.osmose-step-content.active');
            var isValid = true;

            // Validation étape 1
            if (currentStep === 1) {
                var $propertyType = $currentStep.find('input[name="property_type"]:checked');
                if (!$propertyType.length) {
                    isValid = false;
                    showError('Veuillez sélectionner un type de logement');
                }
            }

            // Validation étape 2
            if (currentStep === 2) {
                var $workTypes = $currentStep.find('input[name="work_type[]"]:checked');
                if ($workTypes.length === 0) {
                    isValid = false;
                    showError('Veuillez sélectionner au moins un type de travaux');
                }
            }

            // Validation étape 3
            if (currentStep === 3) {
                var requiredFields = ['first_name', 'last_name', 'email', 'phone'];
                requiredFields.forEach(function(field) {
                    var $field = $currentStep.find('#' + field);
                    if (!$field.val().trim()) {
                        isValid = false;
                        $field.addClass('error');
                    } else {
                        $field.removeClass('error');
                    }
                });

                // Validation email
                var $email = $currentStep.find('#email');
                if ($email.val() && !isValidEmail($email.val())) {
                    isValid = false;
                    $email.addClass('error');
                    showError('Veuillez entrer une adresse email valide');
                }
            }

            return isValid;
        }

        function isValidEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function saveStepData() {
            var $currentStep = $simulator.find('.osmose-step-content.active');
            
            if (currentStep === 1) {
                formData.property_type = $currentStep.find('input[name="property_type"]:checked').val();
            } else if (currentStep === 2) {
                formData.work_type = [];
                $currentStep.find('input[name="work_type[]"]:checked').each(function() {
                    formData.work_type.push($(this).val());
                });
            } else if (currentStep === 3) {
                $currentStep.find('input, textarea').each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    if (name) {
                        formData[name] = $field.val();
                    }
                });
            }
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
                updateProgress();
            }
        }

        function previousStep() {
            if (currentStep > 1) {
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
        }

        function restoreStepData() {
            if (currentStep === 1 && formData.property_type) {
                $simulator.find('input[name="property_type"][value="' + formData.property_type + '"]').prop('checked', true);
                $simulator.find('.osmose-step-content[data-step="1"] .osmose-btn-next').prop('disabled', false);
            } else if (currentStep === 2 && formData.work_type) {
                formData.work_type.forEach(function(value) {
                    $simulator.find('input[name="work_type[]"][value="' + value + '"]').prop('checked', true);
                });
                $simulator.find('.osmose-step-content[data-step="2"] .osmose-btn-next').prop('disabled', false);
            } else if (currentStep === 3) {
                Object.keys(formData).forEach(function(key) {
                    var $field = $simulator.find('#' + key + ', [name="' + key + '"]');
                    if ($field.length && formData[key]) {
                        $field.val(formData[key]);
                    }
                });
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

