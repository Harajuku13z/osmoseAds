<?php
/**
 * Template pour le simulateur de devis multi-étapes (Version 2 - 5 étapes)
 * Design moderne et responsive
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

// Numéro de téléphone
$phone_raw     = get_option('osmose_ads_company_phone_raw', '');
$phone_display = get_option('osmose_ads_company_phone', $phone_raw);
?>

<style>
/* Reset et base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    min-height: 100vh;
}

/* Masquer les éléments du thème */
header, footer, .site-header, .site-footer, .elementor-location-header,
.elementor-location-footer, #masthead, #colophon, .main-header,
.main-footer, .page-header, .navbar, .footer, .header,
.container-header, .container-footer {
    display: none !important;
}

/* Container principal */
.osmose-simulator-fullpage {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.osmose-simulator-fullpage-inner {
    width: 100%;
    max-width: 800px;
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

/* Header interne */
.osmose-simulator-header-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 32px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.osmose-simulator-title-main {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.5px;
}

.osmose-simulator-call-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: 12px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-weight: 600;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.osmose-simulator-call-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

.osmose-simulator-call-btn i {
    font-size: 1.1rem;
}

/* Progress bar */
.osmose-simulator-progress {
    display: flex;
    justify-content: space-between;
    padding: 40px 40px 20px;
    position: relative;
}

.osmose-simulator-progress::before {
    content: '';
    position: absolute;
    top: 60px;
    left: 40px;
    right: 40px;
    height: 3px;
    background: #e5e7eb;
    z-index: 0;
}

.osmose-step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 1;
    flex: 1;
}

.step-number {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    border: 3px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.step-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-align: center;
    font-weight: 500;
    max-width: 90px;
}

.osmose-step-indicator.active .step-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    transform: scale(1.1);
}

.osmose-step-indicator.completed .step-number {
    background: #10b981;
    color: #ffffff;
}

.osmose-step-indicator.active .step-label,
.osmose-step-indicator.completed .step-label {
    color: #1f2937;
    font-weight: 600;
}

/* Form container */
.osmose-simulator-form {
    padding: 40px;
}

.osmose-step-content {
    display: none;
    animation: fadeIn 0.4s ease;
}

.osmose-step-content.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.3;
}

.step-description {
    color: #6b7280;
    font-size: 0.95rem;
    margin-bottom: 32px;
    line-height: 1.5;
}

/* Form fields */
.osmose-form-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.osmose-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.osmose-form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.required {
    color: #ef4444;
}

.osmose-form-group input,
.osmose-form-group textarea {
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.osmose-form-group input:focus,
.osmose-form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.osmose-form-group textarea {
    resize: vertical;
    min-height: 100px;
}

/* Option cards */
.osmose-option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.osmose-option-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #ffffff;
    min-height: 140px;
}

.osmose-option-card:hover {
    border-color: #667eea;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
}

.osmose-option-card input[type="radio"],
.osmose-option-card input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.osmose-option-card input:checked + .option-icon,
.osmose-option-card input:checked ~ .option-icon {
    transform: scale(1.1);
}

.osmose-option-card input:checked ~ .option-label {
    color: #667eea;
    font-weight: 700;
}

.osmose-option-card:has(input:checked) {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
}

.option-icon {
    font-size: 3rem;
    margin-bottom: 12px;
    transition: transform 0.3s ease;
}

.option-image {
    width: 80px;
    height: 80px;
    margin-bottom: 12px;
    border-radius: 12px;
    overflow: hidden;
}

.option-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.option-label {
    font-weight: 600;
    color: #374151;
    text-align: center;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

/* Buttons */
.osmose-step-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 32px;
}

.osmose-btn {
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.osmose-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
}

.osmose-btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
}

.osmose-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.osmose-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.osmose-btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

/* Success/Error messages */
.osmose-simulator-success,
.osmose-simulator-error {
    text-align: center;
    padding: 60px 40px;
}

.success-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #10b981;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    margin: 0 auto 24px;
}

.error-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ef4444;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    margin: 0 auto 24px;
}

.osmose-simulator-success h3,
.osmose-simulator-error h3 {
    font-size: 1.75rem;
    color: #1f2937;
    margin-bottom: 12px;
}

.osmose-simulator-success p,
.osmose-simulator-error p {
    color: #6b7280;
    font-size: 1.1rem;
}

/* Responsive mobile */
@media (max-width: 768px) {
    .osmose-simulator-fullpage {
        padding: 20px 12px;
        align-items: flex-start;
    }

    .osmose-simulator-fullpage-inner {
        border-radius: 16px;
    }

    .osmose-simulator-header-bar {
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 20px;
    }

    .osmose-simulator-title-main {
        font-size: 1.5rem;
    }

    .osmose-simulator-call-btn {
        width: 100%;
        justify-content: center;
    }

    .osmose-simulator-progress {
        padding: 30px 20px 15px;
        overflow-x: auto;
    }

    .step-number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .step-label {
        font-size: 0.65rem;
        max-width: 70px;
    }

    .osmose-simulator-form {
        padding: 24px 20px;
    }

    .step-title {
        font-size: 1.25rem;
    }

    .step-description {
        font-size: 0.9rem;
        margin-bottom: 24px;
    }

    .osmose-form-fields {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .osmose-option-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .osmose-option-card {
        padding: 20px 12px;
        min-height: 120px;
    }

    .option-icon {
        font-size: 2.5rem;
    }

    .osmose-step-actions {
        flex-direction: column-reverse;
    }

    .osmose-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .osmose-option-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="osmose-simulator-fullpage">
    <div class="osmose-simulator-fullpage-inner">
        <div class="osmose-simulator-header-bar">
            <h1 class="osmose-simulator-title-main"><?php _e('Simulateur de Prix', 'osmose-ads'); ?></h1>
            <?php if (!empty($phone_raw)): ?>
                <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="osmose-simulator-call-btn">
                    <i class="fas fa-phone"></i>
                    <span><?php echo esc_html($phone_display); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <div class="osmose-simulator-container" id="osmose-simulator">
            <div class="osmose-simulator-wrapper">
                <!-- Progress Steps -->
                <div class="osmose-simulator-progress">
                    <div class="osmose-step-indicator active" data-step="0">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Informations', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="1">
                        <div class="step-number">2</div>
                        <div class="step-label"><?php _e('Logement', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="2">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php _e('Localisation', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="3">
                        <div class="step-number">4</div>
                        <div class="step-label"><?php _e('Projet', 'osmose-ads'); ?></div>
                    </div>
                    <div class="osmose-step-indicator" data-step="4">
                        <div class="step-number">5</div>
                        <div class="step-label"><?php _e('Détails', 'osmose-ads'); ?></div>
                    </div>
                </div>

                <!-- Form Steps -->
                <form id="osmose-simulator-form" class="osmose-simulator-form">
                    <!-- Step 0: Informations de contact -->
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
                                <?php _e('Continuer', 'osmose-ads'); ?> →
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
                                ← <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?> →
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
                                ← <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?> →
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Type de projet -->
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
                                ← <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                                <?php _e('Continuer', 'osmose-ads'); ?> →
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Détails du projet -->
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
                                ← <?php _e('Précédent', 'osmose-ads'); ?>
                            </button>
                            <button type="submit" class="osmose-btn osmose-btn-primary osmose-btn-submit" disabled>
                                <?php _e('Envoyer la demande', 'osmose-ads'); ?> ✓
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Success Message -->
                <div class="osmose-simulator-success" style="display: none;">
                    <div class="success-icon">✓ </div>
                    <h3><?php _e('Demande envoyée avec succès !', 'osmose-ads'); ?></h3>
                    <p><?php _e('Nous vous contacterons dans les plus brefs délais.', 'osmose-ads'); ?></p>
                </div>

                <!-- Error Message -->
                <div class="osmose-simulator-error" style="display: none;">
                    <div class="error-icon">✗</div>
                    <h3><?php _e('Erreur', 'osmose-ads'); ?></h3>
                    <p class="error-message"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Passer les types de projets au JavaScript
window.osmoseSimulatorProjects = <?php echo json_encode($project_types); ?>;
</script>