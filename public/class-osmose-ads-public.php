<?php
/**
 * Classe Public
 */
class Osmose_Ads_Public {

    public function enqueue_styles() {
        wp_enqueue_style(
            'osmose-ads-public',
            OSMOSE_ADS_PLUGIN_URL . 'public/css/osmose-ads-public.css',
            array(),
            OSMOSE_ADS_VERSION
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'osmose-ads-public',
            OSMOSE_ADS_PLUGIN_URL . 'public/js/osmose-ads-public.js',
            array('jquery'),
            OSMOSE_ADS_VERSION,
            true
        );
    }
}



