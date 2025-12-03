<?php
/**
 * Classe pour gérer les emails HTML du simulateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Osmose_Ads_Email {
    
    /**
     * Envoyer un email en utilisant éventuellement la configuration SMTP du plugin
     */
    public static function send_mail($to, $subject, $message, $headers = array()) {
        $smtp_enabled = get_option('osmose_ads_smtp_enabled', 0);
        $callback = null;
        
        if ($smtp_enabled) {
            $callback = function($phpmailer) {
                // Forcer SMTP
                $phpmailer->isSMTP();
                
                $host = get_option('osmose_ads_smtp_host', '');
                $port = intval(get_option('osmose_ads_smtp_port', 587));
                $encryption = get_option('osmose_ads_smtp_encryption', 'tls');
                $username = get_option('osmose_ads_smtp_username', '');
                $password = get_option('osmose_ads_smtp_password', '');
                $from_email = get_option('osmose_ads_smtp_from_email', '');
                $from_name = get_option('osmose_ads_smtp_from_name', '');
                if (empty($from_name) && function_exists('get_bloginfo')) {
                    $from_name = get_bloginfo('name');
                }
                
                if (!empty($host)) {
                    $phpmailer->Host = $host;
                }
                if (!empty($port)) {
                    $phpmailer->Port = $port;
                }
                
                // Auth SMTP
                if (!empty($username) && !empty($password)) {
                    $phpmailer->SMTPAuth = true;
                    $phpmailer->Username = $username;
                    $phpmailer->Password = $password;
                } else {
                    $phpmailer->SMTPAuth = false;
                }
                
                // Chiffrement
                if ($encryption === 'ssl' || $encryption === 'tls') {
                    $phpmailer->SMTPSecure = $encryption;
                } else {
                    $phpmailer->SMTPSecure = '';
                }
                
                // Expéditeur
                if (!empty($from_email)) {
                    if (empty($from_name) && function_exists('get_bloginfo')) {
                        $from_name = get_bloginfo('name');
                    }
                    $phpmailer->setFrom($from_email, $from_name ?: 'WordPress');
                }
            };
            
            // Attacher la configuration SMTP juste pour cet envoi
            add_action('phpmailer_init', $callback);
        }
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        if ($callback) {
            remove_action('phpmailer_init', $callback);
        }
        
        return $result;
    }
    
    /**
     * Générer le template HTML de base pour les emails
     */
    public static function get_email_template($content, $title = '') {
        $company_name = get_bloginfo('name');
        $logo_url = self::get_logo_url();
        
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title ?: $company_name) . '</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .email-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        .email-company-name {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-title {
            color: #333333;
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 20px 0;
        }
        .email-content {
            color: #555555;
            font-size: 16px;
            line-height: 1.8;
        }
        .email-info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .email-info-row {
            margin: 10px 0;
            display: flex;
            align-items: flex-start;
        }
        .email-info-label {
            font-weight: 600;
            color: #333333;
            min-width: 120px;
        }
        .email-info-value {
            color: #555555;
            flex: 1;
        }
        .email-button {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer-text {
            color: #777777;
            font-size: 14px;
            margin: 5px 0;
        }
        .email-footer-link {
            color: #667eea;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            .email-info-row {
                flex-direction: column;
            }
            .email-info-label {
                min-width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">';
        
        if ($logo_url) {
            $html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($company_name) . '" class="email-logo">';
        }
        
        $html .= '<h1 class="email-company-name">' . esc_html($company_name) . '</h1>
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p class="email-footer-text">' . esc_html($company_name) . '</p>';
        
        $company_phone = get_option('osmose_ads_company_phone', '');
        $company_email = get_option('osmose_ads_company_email', '');
        $company_address = get_option('osmose_ads_company_address', '');
        
        if ($company_phone) {
            $html .= '<p class="email-footer-text">Téléphone: ' . esc_html($company_phone) . '</p>';
        }
        if ($company_email) {
            $html .= '<p class="email-footer-text">Email: <a href="mailto:' . esc_attr($company_email) . '" class="email-footer-link">' . esc_html($company_email) . '</a></p>';
        }
        if ($company_address) {
            $html .= '<p class="email-footer-text">' . esc_html($company_address) . '</p>';
        }
        
        $html .= '<p class="email-footer-text" style="margin-top: 20px; font-size: 12px; color: #999999;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.
            </p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Récupérer l'URL du logo
     */
    private static function get_logo_url() {
        $logo_paths = array(
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.png',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.jpg',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.JPG',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.PNG',
        );
        
        foreach ($logo_paths as $path) {
            if (file_exists($path)) {
                return OSMOSE_ADS_PLUGIN_URL . 'admin/img/' . basename($path);
            }
        }
        
        return '';
    }
    
    /**
     * Générer l'email de notification pour l'admin
     */
    public static function generate_admin_notification_email($quote_data) {
        $title = 'Nouvelle demande de devis';
        
        $content = '<h2 class="email-title">Nouvelle demande de devis reçue</h2>
        <p class="email-content">Vous avez reçu une nouvelle demande de devis via le simulateur.</p>
        
        <div class="email-info-box">
            <div class="email-info-row">
                <span class="email-info-label">Nom complet:</span>
                <span class="email-info-value">' . esc_html($quote_data['first_name'] . ' ' . $quote_data['last_name']) . '</span>
            </div>
            <div class="email-info-row">
                <span class="email-info-label">Email:</span>
                <span class="email-info-value"><a href="mailto:' . esc_attr($quote_data['email']) . '">' . esc_html($quote_data['email']) . '</a></span>
            </div>
            <div class="email-info-row">
                <span class="email-info-label">Téléphone:</span>
                <span class="email-info-value"><a href="tel:' . esc_attr($quote_data['phone']) . '">' . esc_html($quote_data['phone']) . '</a></span>
            </div>';
        
        if (!empty($quote_data['address'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Adresse:</span>
                <span class="email-info-value">' . esc_html($quote_data['address']) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['postal_code']) || !empty($quote_data['city'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Localisation:</span>
                <span class="email-info-value">' . esc_html(trim(($quote_data['postal_code'] ?? '') . ' ' . ($quote_data['city'] ?? ''))) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['property_type'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Type de logement:</span>
                <span class="email-info-value">' . esc_html(ucfirst($quote_data['property_type'])) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['surface'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Surface:</span>
                <span class="email-info-value">' . esc_html($quote_data['surface']) . ' m²</span>
            </div>';
        }
        
        if (!empty($quote_data['project_type'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Type de projet:</span>
                <span class="email-info-value">' . esc_html($quote_data['project_type']) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['project_details'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Détails du projet:</span>
                <span class="email-info-value">' . esc_html($quote_data['project_details']) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['message'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Message:</span>
                <span class="email-info-value">' . nl2br(esc_html($quote_data['message'])) . '</span>
            </div>';
        }
        
        $content .= '</div>
        
        <div style="text-align: center;">
            <a href="' . admin_url('admin.php?page=osmose-ads-quotes') . '" class="email-button">Voir la demande dans l\'admin</a>
        </div>';
        
        return self::get_email_template($content, $title);
    }
    
    /**
     * Générer l'email de confirmation pour l'utilisateur
     */
    public static function generate_user_confirmation_email($quote_data) {
        $company_name = get_bloginfo('name');
        $title = 'Confirmation de votre demande de devis';
        
        $content = '<h2 class="email-title">Merci pour votre demande de devis</h2>
        <p class="email-content">Bonjour ' . esc_html($quote_data['first_name']) . ',</p>
        <p class="email-content">Nous avons bien reçu votre demande de devis et nous vous remercions de votre confiance.</p>
        
        <div class="email-info-box">
            <p style="margin: 0; font-weight: 600; color: #333333;">Récapitulatif de votre demande:</p>
            <div class="email-info-row" style="margin-top: 15px;">
                <span class="email-info-label">Type de logement:</span>
                <span class="email-info-value">' . esc_html(ucfirst($quote_data['property_type'] ?? 'Non spécifié')) . '</span>
            </div>';
        
        if (!empty($quote_data['surface'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Surface:</span>
                <span class="email-info-value">' . esc_html($quote_data['surface']) . ' m²</span>
            </div>';
        }
        
        if (!empty($quote_data['project_type'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Type de projet:</span>
                <span class="email-info-value">' . esc_html($quote_data['project_type']) . '</span>
            </div>';
        }
        
        if (!empty($quote_data['project_details'])) {
            $content .= '<div class="email-info-row">
                <span class="email-info-label">Détails:</span>
                <span class="email-info-value">' . esc_html($quote_data['project_details']) . '</span>
            </div>';
        }
        
        $content .= '</div>
        
        <p class="email-content">Notre équipe va étudier votre demande et vous contactera dans les plus brefs délais.</p>
        <p class="email-content">En attendant, n\'hésitez pas à nous contacter si vous avez des questions.</p>';
        
        $company_phone = get_option('osmose_ads_company_phone', '');
        if ($company_phone) {
            $content .= '<p class="email-content" style="text-align: center; margin-top: 30px;">
                <strong>Besoin d\'aide ?</strong><br>
                Appelez-nous au <a href="tel:' . esc_attr(get_option('osmose_ads_company_phone_raw', $company_phone)) . '" style="color: #667eea;">' . esc_html($company_phone) . '</a>
            </p>';
        }
        
        return self::get_email_template($content, $title);
    }
}



