<?php

namespace NitroPack\Integration\Hosting;

class SiteGround extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        if (strpos(gethostname(), "siteground.eu") !== false) return true;
        $configFilePath = nitropack_get_wpconfig_path();
        if (!$configFilePath) return false;
        return strpos(file_get_contents($configFilePath), 'Added by SiteGround WordPress management system') !== false;
    }

    public function init($stage) {
        if ($this->getHosting() == "siteground") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'allowProxyCache']);
        }
    }

    public function purgeUrl($url) {
        $urlObj = new \NitroPack\Url($url);
        $purgeUrl = $urlObj->getHost() . $urlObj->getPath();
        if ($urlObj->getQuery()) {
            $purgeUrl .= "(.*)";
        }

        $purgeUrl = preg_replace("/^www\./", "", $purgeUrl);
        $purgeUrl = "http://" . $purgeUrl;

        try {
            $hosts = ['127.0.0.1'];
            $purger = new \NitroPack\SDK\Integrations\Varnish($hosts, 'PURGE');
            $purger->purge($purgeUrl);
        } catch (\Exception $e) {}

        return true;
    }

    public function purgeAll() {
        $siteConfig = nitropack_get_site_config();
        if ($siteConfig && !empty($siteConfig["home_url"])) {
            return $this->purgeUrl(nitropack_trailingslashit($siteConfig["home_url"]) . "/(.*)");
        }
        return false;
    }

    public function setCacheControl() {
        nitropack_header("Cache-Control: public, max-age=0, s-maxage=3600"); // needs to be like that instead of Cache-Control: no-cache in order to allow caching in the provided reverse proxy, but prevent the browsers from doing so
    }

    public function allowProxyCache() {
        $this->setCacheControl();
        nitropack_header('X-Cache-Enabled: True');
    }
}
