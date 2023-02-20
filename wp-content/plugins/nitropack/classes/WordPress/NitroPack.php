<?php
namespace NitroPack\WordPress;

class NitroPack {
    private static $instance = NULL;
    public static $preUpdatePosts = array();
    public static $preUpdateTaxonomies = array();
    public static $ignoreUpdatePostIDs = array();
    public static $optionsToCache = [
        'cache_handler_cache_handler',
        'woocommerce_default_customer_address', 
        [ "wc_aelia_currency_switcher" => "ipgeolocation_enabled"]
    ];

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new NitroPack();
        }

        return self::$instance;
    }

    private $sdkObjects;

    public $Config;
    public $Notification;

    public function __construct() {
        $this->Config = new Config($this);
        $this->Notifications = new Notifications($this);
        $this->sdkObjects = array();
    }

    public function getSiteConfig() {
        $siteConfig = null;
        $npConfig = $this->Config->get();
        $host = !empty($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";
        $uri = !empty($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
        $currentUrl = $host . $uri;
        $matchLength = 0;

        if (stripos($currentUrl, "www.") === 0) {
            $currentUrl = substr($currentUrl, 4);
        }

        foreach ($npConfig as $siteUrl => $config) {
            if (stripos($siteUrl, "www.") === 0) {
                $siteUrl = substr($siteUrl, 4);
            }

            if (stripos($currentUrl, $siteUrl) === 0 && strlen($siteUrl) > $matchLength) {
                $siteConfig = $config;
                $matchLength = strlen($siteUrl);
            }
        }
        return $siteConfig;
    }

    public function getSiteId() {
        $siteConfig = $this->getSiteConfig();
        return $siteConfig ? $siteConfig["siteId"] : NULL;
    }

    public function getSiteSecret() {
        $siteConfig = $this->getSiteConfig();
        return $siteConfig ? $siteConfig["siteSecret"] : NULL;
    }

    /**
     * Bear in mind that get_home_url() is not defined in the context of advanced_cache.php
     * so this will throw a fatal error if you call it at that point!
     */
    public static function getConfigKey() {
        return preg_replace("/^https?:\/\/(.*)/", "$1", get_home_url());
    }

    public function isConnected() {
        return !empty($this->getSiteId()) && !empty($this->getSiteSecret());
    }

    public function updateCurrentBlogConfig($siteId, $siteSecret, $blogId, $enableCompression = null) {
        if ($enableCompression === null) {
            $enableCompression = (get_option('nitropack-enableCompression') == 1);
        }

        $webhookToken = get_option('nitropack-webhookToken');
        $hosting = nitropack_detect_hosting();

        $home_url = get_home_url();
        $admin_url = admin_url();
        $alwaysBuffer = defined("NITROPACK_ALWAYS_BUFFER") ? NITROPACK_ALWAYS_BUFFER : true;
        $configKey = self::getConfigKey();
        $staticConfig = $this->Config->get();
        $staticConfig[$configKey] = array(
            "siteId" => $siteId,
            "siteSecret" => $siteSecret,
            "blogId" => $blogId,
            "compression" => $enableCompression,
            "webhookToken" => $webhookToken,
            "home_url" => $home_url,
            "admin_url" => $admin_url,
            "hosting" => $hosting,
            "alwaysBuffer" => $alwaysBuffer,
            "isEzoicActive" => \NitroPack\Integration\Plugin\Ezoic::isActive(),
            "isApoActive" => \NitroPack\Integration\Plugin\Cloudflare::isApoActive(),
            "isLateIntegrationInitRequired" => nitropack_is_late_integration_init_required(),
            "isDlmActive" => \NitroPack\Integration\Plugin\DownloadManager::isActive(),
            "isWoocommerceCacheHandlerActive" => \NitroPack\Integration\Plugin\WoocommerceCacheHandler::isActive(),
            "isWoocommerceActive" => \NitroPack\Integration\Plugin\Woocommerce::isActive(),
            "isAeliaCurrencySwitcherActive" => \NitroPack\Integration\Plugin\AeliaCurrencySwitcher::isActive(),
            "dlm_downloading_url" => \NitroPack\Integration\Plugin\DownloadManager::isActive() ? \NitroPack\Integration\Plugin\DownloadManager::downloadingUrl() : NULL,
            "dlm_download_endpoint" => \NitroPack\Integration\Plugin\DownloadManager::isActive() ? \NitroPack\Integration\Plugin\DownloadManager::downloadEndpoint() : NULL,
            "pluginVersion" => NITROPACK_VERSION,
            "options_cache" => [],
        );
        foreach (self::$optionsToCache as $opt) {
            if (is_array($opt)) {
                foreach($opt as $option => $suboption) {
                    if (empty($staticConfig[$configKey]["options_cache"][$option])) {
                        $staticConfig[$configKey]["options_cache"][$option] = [];
                    }
                    $optionValue = get_option($option);
                    if (!empty($optionValue)) {
                        $staticConfig[$configKey]["options_cache"][$option][$suboption] = $optionValue[$suboption];
                    } else {
                        $staticConfig[$configKey]["options_cache"][$option][$suboption] = null;
                    }
                }
            } else {
                $staticConfig[$configKey]["options_cache"][$opt] = get_option($opt);
            }
        }
        $configSetResult = $this->Config->set($staticConfig);

        if (\NitroPack\Integration\Plugin\AeliaCurrencySwitcher::isActive()) {
            try {
                \NitroPack\Integration\Plugin\AeliaCurrencySwitcher::configureVariationCookies();
            } catch (\Exception $e) {
                // TODO: Log this error
            }
        }

        return $configSetResult;
    }

    public function unsetCurrentBlogConfig() {
        $configKey = self::getConfigKey();
        $staticConfig = $this->Config->get();
        if (!empty($staticConfig[$configKey])) {
            unset($staticConfig[$configKey]);
            return $this->Config->set($staticConfig);
        }

        return true;
    }

    public function resetSdkInstances() {
        $this->sdkObjects = [];
    }

    public function getSdk($siteId = null, $siteSecret = null, $urlOverride = NULL, $forwardExceptions = false) {
        $siteConfig = $this->getSiteConfig();

        $siteId = $siteId ? $siteId : ($siteConfig ? $siteConfig['siteId'] : get_option('nitropack-siteId'));
        $siteSecret = $siteSecret ? $siteSecret : ($siteConfig ? $siteConfig['siteSecret'] : get_option('nitropack-siteSecret'));

        if ($siteId && $siteSecret) {
            try {
                $userAgent = NULL; // It will be automatically detected by the SDK
                $dataDir = nitropack_trailingslashit(NITROPACK_DATA_DIR) . $siteId; // dir without a trailing slash, because this is how the SDK expects it
                $cacheKey = "{$siteId}:{$siteSecret}:{$dataDir}";

                if ($urlOverride) {
                    $cacheKey .= ":{$urlOverride}";
                }

                if (!empty($this->sdkObjects[$cacheKey])) {
                    $nitro = $this->sdkObjects[$cacheKey];
                } else {
                    if (!defined("NP_COOKIE_FILTER")) {
                        \NitroPack\SDK\NitroPack::addCookieFilter("nitropack_filter_non_original_cookies");
                        define("NP_COOKIE_FILTER", true);
                    }
                    if (!defined("NP_STORAGE_CONFIGURED")) {
                        if (defined("NITROPACK_USE_REDIS") && NITROPACK_USE_REDIS) {
                            \NitroPack\SDK\Filesystem::setStorageDriver(new \NitroPack\SDK\StorageDriver\Redis(
                                NITROPACK_REDIS_HOST,
                                NITROPACK_REDIS_PORT,
                                NITROPACK_REDIS_PASS,
                                NITROPACK_REDIS_DB
                            ));
                        }
                        define("NP_STORAGE_CONFIGURED", true);
                    }

                    if (!defined('NP_GEOLOCATION_PREFIX_DEFINED')) {
                        do_action('set_nitropack_geo_cache_prefix');
                        define('NP_GEOLOCATION_PREFIX_DEFINED', true);
                    }

                    if (defined("NITROPACK_CUSTOM_CACHE_PREFIX") && !defined('NP_CUSTOM_CACHE_PREFIX_SET')) {
                        \NitroPack\SDK\NitroPack::addCustomCachePrefix(NITROPACK_CUSTOM_CACHE_PREFIX);
                        define('NP_CUSTOM_CACHE_PREFIX_SET', true);
                    }

                    $nitro = new \NitroPack\SDK\NitroPack($siteId, $siteSecret, $userAgent, $urlOverride, $dataDir);
                    $this->sdkObjects[$cacheKey] = $nitro;
                }
            } catch (\Exception $e) {
                if ($forwardExceptions) {
                    throw $e;
                }
                return NULL;
            }

            return $nitro;
        }

        return NULL;
    }

    public function dataDirExists() {
        return defined("NITROPACK_DATA_DIR") && is_dir(NITROPACK_DATA_DIR); // TODO: Convert this to use the Filesystem abstraction for better Redis support
    }

    public function initDataDir() {
        return $this->dataDirExists() || @mkdir(NITROPACK_DATA_DIR, 0755, true); // TODO: Convert this to use the Filesystem abstraction for better Redis support
    }
}
