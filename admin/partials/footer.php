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
.osmose-navbar {
    margin: -20px -20px 30px -2px;
    padding: 15px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.osmose-navbar .navbar-brand {
    font-size: 1.25rem;
    color: #ffffff !important;
}

.osmose-navbar .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    padding: 8px 16px !important;
    border-radius: 6px;
    transition: all 0.3s ease;
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

.osmose-ads-page {
    min-height: calc(100vh - 200px);
}

.osmose-footer {
    margin: 40px -20px -20px -2px;
}
</style>

