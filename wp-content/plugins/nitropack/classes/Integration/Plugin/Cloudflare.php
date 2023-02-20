<?php

namespace NitroPack\Integration\Plugin;

class Cloudflare {
    const STAGE = "very_early";

    public static function isApoActive() {
        if (defined('CLOUDFLARE_PLUGIN_DIR')) {
            $cfHelper = new CF_Helper();
            return $cfHelper->isApoEnabled();
        } else {
            return false;
        }
    }

    public static function isApoRequest() {
        return !empty($_SERVER["HTTP_CF_DEVICE_TYPE"]);
    }

    public function init($stage) {
        switch ($stage) {
        case "very_early":
            if (self::isApoRequest()) {
                $siteConfig = get_nitropack()->getSiteConfig();
                if ($siteConfig && !empty($siteConfig["isApoActive"])) {
                    add_action('nitropack_early_cache_headers', [$this, 'preventApoCache'], PHP_INT_MAX);
                    add_action('nitropack_cacheable_cache_headers', [$this, 'allowApoCache'], PHP_INT_MAX);
                    add_action('nitropack_cachehit_cache_headers', [$this, 'allowApoCache'], PHP_INT_MAX);
                }
            }
            \NitroPack\Integration::initSemAcquire();
            return true;
        case "late":
            \NitroPack\Integration::initSemRelease();
            if (self::isApoActive()) {
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            }
        default:
            return false;
        }
    }

    public function purgeUrl($url) {
        if (defined('CLOUDFLARE_PLUGIN_DIR')) {
            $cfHelper = new CF_Helper();
            return $cfHelper->purgeUrl($url);
        } else {
            return false;
        }
    }

    public function purgeAll() {
        if (defined('CLOUDFLARE_PLUGIN_DIR')) {
            $cfHelper = new CF_Helper();
            return $cfHelper->purgeCacheEverything();
        } else {
            return false;
        }
    }

    public function allowApoCache() {
        nitropack_header("cf-edge-cache: cache,platform=wordpress");
    }

    public function preventApoCache() {
        nitropack_header("cf-edge-cache: no-cache");
    }
}
