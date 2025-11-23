<?php
/**
 * Footer global pour toutes les pages Osmose ADS
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

        </div>
    </div>
</div>

<footer class="osmose-footer bg-light border-top mt-5 py-4">
    <div class="container-xxl">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    &copy; <?php echo date('Y'); ?> Osmose ADS. <?php _e('Tous droits réservés.', 'osmose-ads'); ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">
                    <small>Version <?php echo OSMOSE_ADS_VERSION; ?></small>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Empêcher le scroll horizontal */
body.osmose-ads-page,
body[class*="osmose"] {
    overflow-x: hidden !important;
    max-width: 100% !important;
}

.wrap {
    max-width: 100% !important;
    overflow-x: hidden !important;
}

/* Navbar */
.osmose-navbar {
    width: 100%;
    padding: 15px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin: 0;
}

.osmose-navbar .container-fluid {
    padding-left: 20px;
    padding-right: 20px;
    max-width: 100%;
}

.osmose-navbar .navbar-brand {
    font-size: 1.25rem;
    color: #ffffff !important;
}

.osmose-logo-rounded {
    border-radius: 12px !important;
    object-fit: contain;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px;
    max-width: 100%;
    height: auto;
    display: block;
}

/* Assurer que le logo est visible */
.navbar-brand img.osmose-logo-rounded {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.osmose-navbar .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    padding: 8px 16px !important;
    border-radius: 6px;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.osmose-navbar .nav-link:hover,
.osmose-navbar .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff !important;
}

.osmose-navbar .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Page principale */
.osmose-ads-page {
    min-height: calc(100vh - 200px);
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    padding: 0 !important;
    margin: 0 !important;
}

.osmose-ads-page .container-fluid {
    padding-left: 20px;
    padding-right: 20px;
    max-width: 100%;
    overflow-x: hidden;
}

.osmose-ads-page .container-xxl {
    max-width: 100%;
    overflow-x: hidden;
}

/* Footer */
.osmose-footer {
    width: 100%;
    margin: 40px 0 0 0;
    padding: 20px;
}

.osmose-footer .container-xxl {
    max-width: 100%;
    padding-left: 20px;
    padding-right: 20px;
}

/* Tables responsive */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Assurer que les éléments ne dépassent pas */
.osmose-ads-page * {
    max-width: 100%;
    box-sizing: border-box;
}

/* Correction pour les formulaires */
.osmose-ads-page .form-table {
    width: 100%;
    table-layout: auto;
}

.osmose-ads-page .form-table input,
.osmose-ads-page .form-table textarea,
.osmose-ads-page .form-table select {
    max-width: 100%;
}

/* Cards responsive */
.osmose-ads-card {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .osmose-navbar .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .osmose-ads-page .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .osmose-navbar .nav-link {
        padding: 8px 12px !important;
        font-size: 0.9rem;
    }
    
    .osmose-logo-rounded {
        height: 40px !important;
        max-width: 150px !important;
    }
}
</style>

