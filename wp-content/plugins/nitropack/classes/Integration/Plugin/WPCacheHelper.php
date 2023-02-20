<?php

namespace NitroPack\Integration\Plugin;

class WPCacheHelper {
    const STAGE = "late";

    public function init($stage) {
        if (class_exists("\WC_Cache_Helper")) {
            remove_action('template_redirect', array('WC_Cache_Helper', 'geolocation_ajax_redirect'));
        }
    }
}
