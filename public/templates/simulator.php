<?php
/**
 * Template pour le simulateur de devis multi-étapes
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="osmose-simulator-container" id="osmose-simulator">
    <div class="osmose-simulator-wrapper">
        <!-- Header -->
        <div class="osmose-simulator-header">
            <h2 class="osmose-simulator-title"><?php _e('Demandez un devis pour vos travaux', 'osmose-ads'); ?></h2>
            <p class="osmose-simulator-subtitle"><?php _e('Spécialiste en solution de rénovation électrique thermique et hygrométrique pour la maison', 'osmose-ads'); ?></p>
        </div>

        <!-- Progress Steps -->
        <div class="osmose-simulator-progress">
            <div class="osmose-step-indicator" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label"><?php _e('Votre logement', 'osmose-ads'); ?></div>
                <div class="step-description"><?php _e('Le lieu concerné par les travaux', 'osmose-ads'); ?></div>
            </div>
            <div class="osmose-step-indicator" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label"><?php _e('Votre projet', 'osmose-ads'); ?></div>
                <div class="step-description"><?php _e('Les travaux que vous souhaitez réaliser', 'osmose-ads'); ?></div>
            </div>
            <div class="osmose-step-indicator" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label"><?php _e('Vos informations', 'osmose-ads'); ?></div>
                <div class="step-description"><?php _e('Renseignements nécessaires pour vous contacter', 'osmose-ads'); ?></div>
            </div>
        </div>

        <!-- Form Steps -->
        <form id="osmose-simulator-form" class="osmose-simulator-form">
            <!-- Step 1: Type de logement -->
            <div class="osmose-step-content active" data-step="1">
                <h3 class="step-title"><?php _e('Vos travaux concernent-ils une maison ou un appartement ?', 'osmose-ads'); ?></h3>
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
                </div>
                <div class="osmose-step-actions">
                    <button type="button" class="osmose-btn osmose-btn-primary osmose-btn-next" disabled>
                        <?php _e('Continuer', 'osmose-ads'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: Type de travaux -->
            <div class="osmose-step-content" data-step="2">
                <h3 class="step-title"><?php _e('Quels travaux souhaitez-vous réaliser ?', 'osmose-ads'); ?></h3>
                <div class="osmose-option-grid osmose-option-grid-multiple">
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="renovation_electrique">
                        <div class="option-icon">⚡</div>
                        <div class="option-label"><?php _e('Rénovation électrique', 'osmose-ads'); ?></div>
                    </label>
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="renovation_thermique">
                        <div class="option-icon">🔥</div>
                        <div class="option-label"><?php _e('Rénovation thermique', 'osmose-ads'); ?></div>
                    </label>
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="isolation">
                        <div class="option-icon">🏡</div>
                        <div class="option-label"><?php _e('Isolation', 'osmose-ads'); ?></div>
                    </label>
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="chauffage">
                        <div class="option-icon">🌡️</div>
                        <div class="option-label"><?php _e('Chauffage', 'osmose-ads'); ?></div>
                    </label>
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="ventilation">
                        <div class="option-icon">💨</div>
                        <div class="option-label"><?php _e('Ventilation', 'osmose-ads'); ?></div>
                    </label>
                    <label class="osmose-option-card osmose-option-checkbox">
                        <input type="checkbox" name="work_type[]" value="autre">
                        <div class="option-icon">🔧</div>
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

            <!-- Step 3: Informations de contact -->
            <div class="osmose-step-content" data-step="3">
                <h3 class="step-title"><?php _e('Vos informations de contact', 'osmose-ads'); ?></h3>
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
                    <div class="osmose-form-group">
                        <label for="address"><?php _e('Adresse', 'osmose-ads'); ?></label>
                        <input type="text" id="address" name="address">
                    </div>
                    <div class="osmose-form-group">
                        <label for="city"><?php _e('Ville', 'osmose-ads'); ?></label>
                        <input type="text" id="city" name="city">
                    </div>
                    <div class="osmose-form-group">
                        <label for="postal_code"><?php _e('Code postal', 'osmose-ads'); ?></label>
                        <input type="text" id="postal_code" name="postal_code">
                    </div>
                    <div class="osmose-form-group">
                        <label for="message"><?php _e('Message (optionnel)', 'osmose-ads'); ?></label>
                        <textarea id="message" name="message" rows="4"></textarea>
                    </div>
                </div>
                <div class="osmose-step-actions">
                    <button type="button" class="osmose-btn osmose-btn-secondary osmose-btn-prev">
                        <?php _e('Précédent', 'osmose-ads'); ?>
                    </button>
                    <button type="submit" class="osmose-btn osmose-btn-primary osmose-btn-submit">
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
    </div>
</div>





