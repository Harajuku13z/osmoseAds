<?php
/**
 * Header global pour toutes les pages Osmose ADS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prévenir les inclusions multiples
if (defined('OSMOSE_ADS_HEADER_LOADED')) {
    return;
}
define('OSMOSE_ADS_HEADER_LOADED', true);

// Fonction helper pour trouver le logo (éviter les conflits si déjà définie)
if (!function_exists('osmose_ads_get_logo_url')) {
    function osmose_ads_get_logo_url() {
        if (!defined('OSMOSE_ADS_PLUGIN_DIR') || !defined('ABSPATH')) {
            return false;
        }
        
        $logo_paths = array(
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.jpg',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.png',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.JPG',
            OSMOSE_ADS_PLUGIN_DIR . 'admin/img/logo.PNG',
            OSMOSE_ADS_PLUGIN_DIR . '../logo.jpg',
            OSMOSE_ADS_PLUGIN_DIR . '../logo.png',
            ABSPATH . 'logo.jpg',
        );
        
        foreach ($logo_paths as $path) {
            // Normaliser le chemin
            $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
            $real_path = @realpath($path);
            
            if ($real_path && file_exists($real_path)) {
                // Convertir en URL
                if (strpos($real_path, ABSPATH) === 0) {
                    $url = str_replace(ABSPATH, home_url('/'), $real_path);
                    $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
                    return $url;
                }
                
                // Chemin relatif au plugin
                if (defined('OSMOSE_ADS_PLUGIN_DIR') && defined('OSMOSE_ADS_PLUGIN_URL')) {
                    $relative = str_replace(OSMOSE_ADS_PLUGIN_DIR, '', $real_path);
                    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
                    return OSMOSE_ADS_PLUGIN_URL . $relative;
                }
            }
        }
        return false;
    }
}

$logo_url = osmose_ads_get_logo_url();
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'osmose-ads';

// Navigation pages
$nav_items = array(
    'osmose-ads' => array(
        'title' => __('Tableau de bord', 'osmose-ads'),
        'icon' => 'dashicons-dashboard',
        'url' => admin_url('admin.php?page=osmose-ads'),
    ),
    'osmose-ads-templates' => array(
        'title' => __('Templates', 'osmose-ads'),
        'icon' => 'dashicons-edit',
        'url' => admin_url('admin.php?page=osmose-ads-templates'),
    ),
    'osmose-ads-ads' => array(
        'title' => __('Annonces', 'osmose-ads'),
        'icon' => 'dashicons-megaphone',
        'url' => admin_url('admin.php?page=osmose-ads-ads'),
    ),
    'osmose-ads-cities' => array(
        'title' => __('Villes', 'osmose-ads'),
        'icon' => 'dashicons-location-alt',
        'url' => admin_url('admin.php?page=osmose-ads-cities'),
    ),
    'osmose-ads-articles' => array(
        'title' => __('Articles', 'osmose-ads'),
        'icon' => 'dashicons-edit-page',
        'url' => admin_url('admin.php?page=osmose-ads-articles'),
    ),
    'osmose-ads-settings' => array(
        'title' => __('Réglages', 'osmose-ads'),
        'icon' => 'dashicons-admin-settings',
        'url' => admin_url('admin.php?page=osmose-ads-settings'),
    ),
    'osmose-ads-calls' => array(
        'title' => __('Statistiques d\'Appels', 'osmose-ads'),
        'icon' => 'dashicons-phone',
        'url' => admin_url('admin.php?page=osmose-ads-calls'),
    ),
    'osmose-ads-visits' => array(
        'title' => __('Statistiques de Visites', 'osmose-ads'),
        'icon' => 'dashicons-visibility',
        'url' => admin_url('admin.php?page=osmose-ads-visits'),
    ),
);
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary osmose-navbar" style="background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #3b82f6 100%) !important;">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo admin_url('admin.php?page=osmose-ads'); ?>">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Osmose" class="osmose-logo-rounded" style="height: 50px; width: auto; max-width: 200px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);">
            <?php else: ?>
                <!-- Fallback si logo non trouvé -->
                <div class="osmose-logo-placeholder" style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span class="dashicons dashicons-admin-site" style="color: white; font-size: 24px;"></span>
                </div>
            <?php endif; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#osmoseNavbar" aria-controls="osmoseNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="osmoseNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php foreach ($nav_items as $page_key => $nav_item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === $page_key ? 'active' : ''; ?>" href="<?php echo esc_url($nav_item['url']); ?>">
                            <span class="dashicons <?php echo esc_attr($nav_item['icon']); ?> me-1"></span>
                            <?php echo esc_html($nav_item['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="osmose-ads-page">
    <div class="container-fluid py-4">
        <div class="container-xxl">

