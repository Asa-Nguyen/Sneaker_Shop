<?php

namespace NitroPack\Integration\Plugin;

class NginxHelper {
    const STAGE = "very_early";

    public static function isActive() {
        return defined('NGINX_HELPER_BASEPATH');
    }

    public function init($stage) {
        switch ($stage) {
        case "very_early":
            \NitroPack\Integration::initSemAcquire();
            return true;
        case "late":
            \NitroPack\Integration::initSemRelease();
            if (self::isActive()) {
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            }
            break;
        }
    }

    public function purgeUrl($url) {
        global $nginx_purger;
        if ($nginx_purger) {
            $nginx_purger->purge_url($url);
        }
        return true;
    }

    public function purgeAll() {
        global $nginx_purger;
        if ($nginx_purger) {
            $nginx_purger->purge_all();
        }
        return true;
    }
}
