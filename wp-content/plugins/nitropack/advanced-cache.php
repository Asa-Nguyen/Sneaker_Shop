<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (strpos($_SERVER["REQUEST_URI"], "nitroHealthcheck") !== false) {
    // This healthcheck is used to quickly check test whether the PHP application is able to handle the requests
    // Mainly used to check for errors after .htaccess has been modified
    echo "Healthy";
    exit;
}

$nitropack_functions_file = '/*NITROPACK_FUNCTIONS_FILE*/';
$nitropack_abspath = '/*NITROPACK_ABSPATH*/';

// We need the ABSPATH check in order to verify that the functions file which we are about to load belongs to the expected WP installation.
// Otherwise issues may occur when a site is being duplicated in a subdir on the same server.
if (file_exists($nitropack_functions_file) && ABSPATH == $nitropack_abspath) {
    define( 'NITROPACK_ADVANCED_CACHE', true);
    define( 'NITROPACK_ADVANCED_CACHE_VERSION', '/*NP_VERSION*/');
    define( 'NITROPACK_LOGGED_IN_COOKIE', '/*LOGIN_COOKIES*/' );
    require_once $nitropack_functions_file;
}

if (defined("NITROPACK_VERSION") && defined("NITROPACK_ADVANCED_CACHE_VERSION") && NITROPACK_VERSION == NITROPACK_ADVANCED_CACHE_VERSION && nitropack_is_dropin_cache_allowed()) {
    nitropack_handle_request("drop-in");
    $nitro = get_nitropack_sdk();

    if (null !== $nitro) {
        $np_siteConfig = nitropack_get_site_config();
        if ( !empty($np_siteConfig["alwaysBuffer"]) || ($nitro->isAJAXRequest() && $nitro->isAllowedAJAX()) ) {
            define( 'NITROPACK_IS_BUFFERING', true );
            ob_start(function($buffer) use (&$nitro) {
                if (!defined("NITROPACK_BEACON_PRINTED")) {
                    $respHeaders = headers_list();
                    $contentType = NULL;
                    foreach ($respHeaders as $respHeader) {
                        if (stripos(trim($respHeader), 'Content-Type:') === 0) {
                            $contentType = $respHeader;
                        }
                    }

                    if ( stripos($contentType, 'text/html') !== false && nitropack_passes_cookie_requirements() && nitropack_passes_page_requirements(false)) {
                        define("NITROPACK_BEACON_PRINTED", true);
                        $buffer = str_replace("</body", nitropack_get_beacon_script() . "</body", $buffer);
                    }
                }

                if ($nitro->isAJAXRequest() && $nitro->isAllowedAJAX()) {
                    $nitro->pageCache->setContent($buffer, []);
                }
                return $buffer;
            }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
        } else {
            define( 'NITROPACK_IS_BUFFERING', false );
        }
    }
}
