<?php
/**
 * Template pour le simulateur de devis multi-étapes (Version 2 - 5 étapes)
 * Version "standalone" : page blanche sans hero, sidebar, avis ni CTA.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer la configuration des projets depuis les options
$project_types = get_option('osmose_ads_simulator_project_types', array(
    'toiture' => array(
        'label' => 'Toiture',
        'options' => array('hydrofuge', 'démoussage', 'réparation', 'remplacement', 'isolation')
    )
));
?>

<style>
/* Mode plein écran, fond blanc, sans header/footer visuels */
body {
    margin: 0;
    padding: 0;
    background: #ffffff !important;
}

/* Masquer un maximum de wrappers de thème pour obtenir une page vraiment blanche */
header,
footer,
.site-header,
.site-footer,
.elementor-location-header,
.elementor-location-footer,
#masthead,
#colophon,
.main-header,
.main-footer,
.page-header,
.navbar,
.footer,
.header,
.container-header,
.container-footer {
    display: none !important;
}

.osmose-simulator-fullpage {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    padding: 20px;
    box-sizing: border-box;
}

.osmose-simulator-fullpage-inner {
    width: 100%;
    max-width: 900px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
    padding: 30px 24px;
    box-sizing: border-box;
}

@media (max-width: 767px) {
    .osmose-simulator-fullpage-inner {
        padding: 20px 16px;
        border-radius: 0;
        box-shadow: none;
    }
}
</style>

<div class="osmose-simulator-fullpage">
    <div class="osmose-simulator-fullpage-inner">
        <div class="osmose-simulator-container" id="osmose-simulator">
            <div class="osmose-simulator-wrapper">
                <!-- Progress Steps -->
                <div class="osmose-simulator-progress">
                    <div class="osmose-step-indicator" data-step="0">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Vos informations', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="1">
                        <div class="step-number">2</div>
                        <div class="step-label"><?php _e('Type de logement', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="2">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php _e('Localisation', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="3">
                        <div class="step-number">4</div>
                        <div class="step-label"><?php _e('Type de projet', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="4">
                        <div class="step-number">5</div>
                        <div class="step-label"><?php _e('Détails', 'osmose-ads'); ?></div>
                    </div>
                </div>

                <!-- Form Steps -->
                <form id="osmose-simulator-form" class="osmose-simulator-form">
                    <!-- Step 0: Informations de contact (obligatoires) -->
                    <div class="osmose-step-content active" data-step="0">
                        <h3 class="step-title"><?php _e('Vos informations de contact', 'osmose-ads'); ?></h3>
                        <p class="step-description"><?php _e('Ces informations sont nécessaires pour vous contacter', 'osmose-ads'); ?></p>
                        <div class="osmose-form-fields">
                            <div class="osmose-form-group">
                                <label for="first_name"><?php _e('Prénom', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="osmose-form-group">
                                <label for="last_name"><?php _e('Nom', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                            <div class="osmose-form-group">
                                <label for="email"><?php _e('Email', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="osmose-form-group">
                                <label for="phone"><?php _e('Téléphone', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="osmose-step-actions">
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 1: Type de logement -->
                    <div class="osmose-step-content" data-step="1">
                        <h3 class="step-title"><?php _e('Vos travaux concernent-ils une maison, un appartement, un local commercial ou autre ?', 'osmose-ads'); ?></h3>
                        <div class="osmose-option-grid">
                            <label class="osmose-option-card">
                                <input type="radio" name="property_type" value="maison" required>
                                <div class="option-icon">🏠</div>
                                <div class="option-label"><?php _e('Maison', 'osmose-ads'); ?></div>
                            </label>
                            <label class="osmose-option-card">
                                <input type="radio" name="property_type" value="appartement" required>
                                <div class="option-icon">🏢</div>
                                <div class="option-label"><?php _e('Appartement', 'osmose-ads'); ?></div>
                            </label>
                            <label class="osmose-option-card">
                                <input type="radio" name="property_type" value="local_commercial" required>
                                <div class="option-icon">🏪</div>
                                <div class="option-label"><?php _e('Local commercial', 'osmose-ads'); ?></div>
                            </label>
                            <label class="osmose-option-card">
                                <input type="radio" name="property_type" value="autre" required>
                                <div class="option-icon">🏗️</div>
                                <div class="option-label"><?php _e('Autre', 'osmose-ads'); ?></div>
                            </label>
                        </div>
                        <div class="osmose-step-actions">
                            <button type="button" class="osmose-btn osmose-btn-secondary osmose-btn-prev">
                                <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Code postal, adresse et surface -->
                    <div class="osmose-step-content" data-step="2">
                        <h3 class="step-title"><?php _e('Localisation et surface de votre logement', 'osmose-ads'); ?></h3>
                        <div class="osmose-form-fields">
                            <div class="osmose-form-group">
                                <label for="postal_code"><?php _e('Code postal', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="text" id="postal_code" name="postal_code" pattern="[0-9]{5}" maxlength="5" required>
                            </div>
                            <div class="osmose-form-group">
                                <label for="address"><?php _e('Adresse', 'osmose-ads'); ?></label>
                                <input type="text" id="address" name="address">
                            </div>
                            <div class="osmose-form-group">
                                <label for="city"><?php _e('Ville', 'osmose-ads'); ?></label>
                                <input type="text" id="city" name="city">
                            </div>
                            <div class="osmose-form-group">
                                <label for="surface"><?php _e('Surface (m²)', 'osmose-ads'); ?> <span class="required">*</span></label>
                                <input type="number" id="surface" name="surface" min="1" step="1" required>
                            </div>
                        </div>
                        <div class="osmose-step-actions">
                            <button type="button" class="osmose-btn osmose-btn-secondary osmose-btn-prev">
                                <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Type de projet (sélection multiple) -->
                    <div class="osmose-step-content" data-step="3">
                        <h3 class="step-title"><?php _e('Quels types de projets souhaitez-vous réaliser ?', 'osmose-ads'); ?></h3>
                        <p class="step-description"><?php _e('Vous pouvez sélectionner plusieurs projets (2 à 3 maximum)', 'osmose-ads'); ?></p>
                        <div class="osmose-option-grid osmose-option-grid-multiple osmose-project-types" id="project-types-container">
                            <?php foreach ($project_types as $key => $project): 
                                $image_url = !empty($project['image']) ? esc_url($project['image']) : '';
                            ?>
                                <label class="osmose-option-card osmose-option-checkbox">
                                    <input type="checkbox" name="project_type[]" value="<?php echo esc_attr($key); ?>" data-project-key="<?php echo esc_attr($key); ?>">
                                    <?php if ($image_url): ?>
                                        <div class="option-image">
                                            <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr($project['label']); ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="option-icon">🔧</div>
                                    <?php endif; ?>
                                    <div class="option-label"><?php echo esc_html($project['label']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="osmose-step-actions">
                            <button type="button" class="osmose-btn osmose-btn-secondary osmose-btn-prev">
                                <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Détails du projet (dynamique selon les types sélectionnés) -->
                    <div class="osmose-step-content" data-step="4">
                        <h3 class="step-title"><?php _e('Quels détails concernent vos projets ?', 'osmose-ads'); ?></h3>
                        <p class="step-description"><?php _e('Sélectionnez les détails pour chaque projet choisi', 'osmose-ads'); ?></p>
                        <div id="project-details-container">
                            <!-- Les sections de détails seront chargées dynamiquement via JavaScript -->
                        </div>
                        <div class="osmose-form-group" style="margin-top: 20px;">
                            <label for="message"><?php _e('Message complémentaire (optionnel)', 'osmose-ads'); ?></label>
                            <textarea id="message" name="message" rows="4"></textarea>
                        </div>
                        <div class="osmose-step-actions">
                            <button type="button" class="osmose-btn osmose-btn-secondary osmose-btn-prev">
                                <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="submit" class="osmose-btn osmose-btn-primary osmose-btn-submit" disabled>
                                <?php _e('Envoyer la demande', 'osmose-ads'); ?>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Success Message -->
                <div class="osmose-simulator-success" style="display: none;">
                    <div class="success-icon">✓</div>
                    <h3><?php _e('Demande envoyée avec succès !', 'osmose-ads'); ?></h3>
                    <p><?php _e('Nous vous contacterons dans les plus brefs délais.', 'osmose-ads'); ?></p>
                </div>

                <!-- Error Message -->
                <div class="osmose-simulator-error" style="display: none;">
                    <div class="error-icon">✗</div>
                    <h3><?php _e('Erreur', 'osmose-ads'); ?></h3>
                    <p class="error-message"></p>
                </div>
            </div> <!-- /.osmose-simulator-wrapper -->
        </div> <!-- /.osmose-simulator-container -->
    </div> <!-- /.osmose-simulator-fullpage-inner -->
</div> <!-- /.osmose-simulator-fullpage -->

<script>
// Passer les types de projets au JavaScript
window.osmoseSimulatorProjects = <?php echo json_encode($project_types); ?>;
</script>

