<?php
/**
 * Template pour le simulateur de devis multi-étapes (Version 2 - 5 étapes)
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

// Récupérer la configuration du hero
$hero_enabled = get_option('osmose_ads_simulator_hero_enabled', 0);
$hero_title = get_option('osmose_ads_simulator_hero_title', '');
$hero_description = get_option('osmose_ads_simulator_hero_description', '');
$hero_image = get_option('osmose_ads_simulator_hero_image', '');

// Informations entreprise (mêmes réglages que les pages d'annonces)
$phone        = get_option('osmose_ads_company_phone', '');
$phone_raw    = get_option('osmose_ads_company_phone_raw', '');
$company_name = get_bloginfo('name');
$company_email   = get_option('osmose_ads_company_email', get_option('admin_email', ''));
$company_address = get_option('osmose_ads_company_address', '');
$devis_url       = get_option('osmose_ads_devis_url', '');

// Nettoyer les données pour supprimer les éléments indésirables (même logique que single-ad)
if ($company_address) {
    $company_address = strip_tags($company_address);
    $company_address = preg_replace('/Icon-facebook|icon-facebook|Facebook/i', '', $company_address);
    $company_address = trim($company_address);
}
if ($company_name) {
    $company_name = strip_tags($company_name);
    $company_name = preg_replace('/Icon-facebook|icon-facebook|Facebook/i', '', $company_name);
    $company_name = trim($company_name);
}

// URL courante (pour partage)
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<div class="osmose-ad-page-modern">
    <?php if ($hero_enabled && (!empty($hero_title) || !empty($hero_image))): ?>
        <!-- Hero Section (même style que la page annonce) -->
        <section class="osmose-hero-modern">
            <?php if (!empty($hero_image)): ?>
                <div class="osmose-hero-bg" style="background-image: url('<?php echo esc_url($hero_image); ?>');"></div>
            <?php else: ?>
                <div class="osmose-hero-bg" style="background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1920&q=80');"></div>
            <?php endif; ?>
            <div class="osmose-hero-overlay-modern"></div>
            
            <div class="osmose-hero-container">
                <div class="osmose-hero-content-modern">
                    <?php if (!empty($hero_title)): ?>
                        <h1 class="osmose-hero-title-modern">
                            <i class="fas fa-calculator"></i>
                            <?php echo esc_html($hero_title); ?>
                        </h1>
                    <?php endif; ?>
                    <?php if (!empty($hero_description)): ?>
                        <p class="osmose-hero-description">
                            <?php echo esc_html($hero_description); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Section contenu avec la même mise en page que les pages d'annonces -->
    <section class="osmose-content-section">
        <div class="osmose-container">
            <div class="osmose-content-wrapper">
                <div class="osmose-content-main">
                    <div class="osmose-content-card">
                        <div class="osmose-content-grid">

                            <!-- Colonne gauche : simulateur -->
                            <div class="osmose-content-left">
                                <div class="osmose-simulator-container" id="osmose-simulator">
                                    <div class="osmose-simulator-wrapper">
                                        <!-- Header (affiché seulement si le hero n'est pas activé) -->
                                        <?php if (!$hero_enabled || empty($hero_title)): ?>
                                            <div class="osmose-simulator-header">
                                                <h2 class="osmose-simulator-title"><?php _e('Demandez un devis pour vos travaux', 'osmose-ads'); ?></h2>
                                                <p class="osmose-simulator-subtitle"><?php _e('Remplissez le formulaire en quelques étapes simples', 'osmose-ads'); ?></p>
                                            </div>
                                        <?php endif; ?>

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
                            </div> <!-- /.osmose-content-left -->

                            <!-- Colonne droite : sidebar (même style que les annonces) -->
                            <div class="osmose-sidebar-modern">

                                <!-- Carte Pourquoi nous choisir -->
                                <div class="osmose-sidebar-card osmose-card-green">
                                    <h3 class="osmose-sidebar-title"><?php _e('Pourquoi choisir', 'osmose-ads'); ?> <?php echo esc_html($company_name); ?></h3>
                                    <p class="osmose-sidebar-text">
                                        <?php
                                        $service_desc = sprintf(
                                            __('Choisir %s pour votre projet, c\'est opter pour l\'expertise d\'une entreprise locale réputée. Nous garantissons des prestations de qualité, un suivi personnalisé, des délais respectés et des tarifs transparents.', 'osmose-ads'),
                                            $company_name
                                        );
                                        echo esc_html($service_desc);
                                        ?>
                                    </p>
                                </div>

                                <!-- Carte Financement -->
                                <div class="osmose-sidebar-card osmose-card-yellow">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Financement et aides', 'osmose-ads'); ?></h4>
                                    <p><?php _e('Pour faciliter vos projets, vous pouvez bénéficier d\'aides financières telles que MaPrimeRénov, les CEE, l\'éco-PTZ ou une TVA réduite. Notre équipe est à votre disposition pour vous renseigner sur ces dispositifs.', 'osmose-ads'); ?></p>
                                </div>

                                <!-- Carte Devis -->
                                <div class="osmose-sidebar-card osmose-card-gradient">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Besoin d\'un devis ?', 'osmose-ads'); ?></h4>
                                    <p class="osmose-sidebar-text"><?php _e('Contactez-nous pour un devis gratuit.', 'osmose-ads'); ?></p>
                                    <?php if ($devis_url): ?>
                                        <a href="<?php echo esc_url($devis_url); ?>" class="osmose-btn-devis-inline">
                                            <?php _e('Demande de devis', 'osmose-ads'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Carte Appel Direct -->
                                <?php if ($phone_raw): ?>
                                    <div class="osmose-sidebar-card osmose-card-gradient-call" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                                        <h4 class="osmose-sidebar-subtitle" style="color: white;">
                                            <i class="fas fa-phone me-2"></i><?php _e('Appelez-nous', 'osmose-ads'); ?>
                                        </h4>
                                        <p class="osmose-sidebar-text" style="color: rgba(255,255,255,0.9);">
                                            <?php _e('Intervention rapide et devis immédiat', 'osmose-ads'); ?>
                                        </p>
                                        <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="osmose-btn-call-sidebar">
                                            <i class="fas fa-phone-alt"></i>
                                            <?php echo esc_html($phone ?: $phone_raw); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Informations Pratiques -->
                                <div class="osmose-sidebar-card osmose-card-gray">
                                    <h4 class="osmose-sidebar-subtitle"><?php _e('Informations Pratiques', 'osmose-ads'); ?></h4>
                                    <ul class="osmose-info-list">
                                        <?php if ($company_address): ?>
                                            <li><i class="fas fa-map-marker-alt"></i> <strong><?php _e('Adresse :', 'osmose-ads'); ?></strong> <?php echo esc_html($company_address); ?></li>
                                        <?php endif; ?>
                                        <?php if ($phone_raw): ?>
                                            <li><i class="fas fa-phone"></i> <strong><?php _e('Téléphone :', 'osmose-ads'); ?></strong> <?php echo esc_html($phone ?: $phone_raw); ?></li>
                                        <?php endif; ?>
                                        <?php if ($company_email): ?>
                                            <li><i class="fas fa-envelope"></i> <strong><?php _e('Email :', 'osmose-ads'); ?></strong> <a href="mailto:<?php echo esc_attr($company_email); ?>"><?php echo esc_html($company_email); ?></a></li>
                                        <?php endif; ?>
                                        <?php if ($company_name): ?>
                                            <li><i class="fas fa-building"></i> <strong><?php _e('Société :', 'osmose-ads'); ?></strong> <?php echo esc_html($company_name); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                            </div> <!-- /.osmose-sidebar-modern -->

                        </div> <!-- /.osmose-content-grid -->
                    </div> <!-- /.osmose-content-card -->

                    <!-- Section Avis Clients (Widgets for Google Reviews) -->
                    <div class="osmose-reviews-section">
                        <div class="osmose-reviews-widget">
                            <?php
                            $default_reviews_shortcode = '[trustindex no-registration=google]';
                            $google_reviews_shortcode = apply_filters('osmose_ads_google_reviews_shortcode', $default_reviews_shortcode);
                            
                            if (!empty($google_reviews_shortcode)) {
                                echo do_shortcode($google_reviews_shortcode);
                            }
                            ?>
                        </div>
                    </div>

                    <!-- CTA Section -->
                    <div class="osmose-cta-section">
                        <h3 class="osmose-cta-title">
                            <?php _e('Prêt à Démarrer Votre Projet ?', 'osmose-ads'); ?>
                        </h3>
                        <p class="osmose-cta-text">
                            <?php _e('Contactez-nous dès aujourd\'hui pour un devis gratuit et personnalisé', 'osmose-ads'); ?>
                        </p>
                        <div class="osmose-cta-buttons">
                            <?php if ($devis_url): ?>
                                <a href="<?php echo esc_url($devis_url); ?>" class="osmose-btn-hero osmose-btn-accent">
                                    <i class="fas fa-calculator"></i>
                                    <?php _e('Demander un Devis Gratuit', 'osmose-ads'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($phone_raw): ?>
                                <a href="tel:<?php echo esc_attr($phone_raw); ?>" class="osmose-btn-hero osmose-btn-primary">
                                    <i class="fas fa-phone"></i>
                                    <?php _e('Appeler Maintenant', 'osmose-ads'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div> <!-- /.osmose-content-main -->
            </div> <!-- /.osmose-content-wrapper -->
        </div> <!-- /.osmose-container -->
    </section>
</div>

<script>
// Passer les types de projets au JavaScript
window.osmoseSimulatorProjects = <?php echo json_encode($project_types); ?>;
</script>

