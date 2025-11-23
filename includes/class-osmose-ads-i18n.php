<?php
/**
 * Gestion de l'internationalisation
 */
class Osmose_Ads_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'osmose-ads',
            false,
            dirname(OSMOSE_ADS_PLUGIN_BASENAME) . '/languages/'
        );
    }
}



