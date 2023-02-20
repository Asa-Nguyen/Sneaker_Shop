<?php

/**
 * Helper functions.
 *
 * @since 6.0.0
 *
 * @package ExactMetrics
 * @subpackage Helper
 * @author  Chris Christoff
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function exactmetrics_is_page_reload() {
	// Can't be a refresh without having a referrer
	if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
		return false;
	}

	// IF the referrer is identical to the current page request, then it's a refresh
	return ( $_SERVER['HTTP_REFERER'] === home_url( $_SERVER['REQUEST_URI'] ) );
}


function exactmetrics_track_user( $user_id = - 1 ) {
	if ( $user_id === - 1 ) {
		$user = wp_get_current_user();
	} else {
		$user = new WP_User( $user_id );
	}

	$track_user = true;
	$roles      = exactmetrics_get_option( 'ignore_users', array() );

	if ( ! empty( $roles ) && is_array( $roles ) ) {
		foreach ( $roles as $role ) {
			if ( is_string( $role ) ) {
				if ( user_can( $user, $role ) ) {
					$track_user = false;
					break;
				}
			}
		}
	}

	$track_super_admin = apply_filters( 'exactmetrics_track_super_admins', false );
	if ( $user_id === - 1 && $track_super_admin === false && is_multisite() && is_super_admin() ) {
		$track_user = false;
	}

	// or if tracking code is not entered
	$tracking_ids = exactmetrics_get_tracking_ids();
	if ( empty( $tracking_ids ) ) {
		$track_user = false;
	}

	return apply_filters( 'exactmetrics_track_user', $track_user, $user );
}

/**
 * Skip tracking status.
 *
 * @return bool
 */
function exactmetrics_skip_tracking() {
	return (bool) apply_filters( 'exactmetrics_skip_tracking', false );
}

function exactmetrics_get_client_id( $payment_id = false ) {
	if ( is_object( $payment_id ) ) {
		$payment_id = $payment_id->ID;
	}
	$user_cid  = exactmetrics_get_uuid();
	$saved_cid = ! empty( $payment_id ) ? get_post_meta( $payment_id, '_yoast_gau_uuid', true ) : false;

	if ( ! empty( $payment_id ) && ! empty( $saved_cid ) ) {
		return $saved_cid;
	} elseif ( ! empty( $user_cid ) ) {
		return $user_cid;
	} else {
		return exactmetrics_generate_uuid();
	}
}

/**
 * Returns the Google Analytics clientId to store for later use
 *
 * @return bool|string False if cookie isn't set, GA UUID otherwise
 * @link  https://developers.google.com/analytics/devguides/collection/analyticsjs/domains#getClientId
 *
 * @since 6.0.0
 */
function exactmetrics_get_uuid() {
	if ( empty( $_COOKIE['_ga'] ) ) {
		return false;
	}

	/**
	 * Example cookie formats:
	 *
	 * GA1.2.XXXXXXX.YYYYY
	 * _ga=1.2.XXXXXXX.YYYYYY -- We want the XXXXXXX.YYYYYY part
	 *
	 * for AMP pages the format is sometimes GA1.3.amp-XXXXXXXXXXXXX-XXXXXXXX
	 * if the first page visited is AMP, the cookie may be in the format amp-XXXXXXXXXXXXX-XXXXXXXX
	 */

	$ga_cookie    = $_COOKIE['_ga'];
	$cookie_parts = explode( '.', $ga_cookie );
	if ( is_array( $cookie_parts ) && ! empty( $cookie_parts[2] ) ) {
		$cookie_parts = array_slice( $cookie_parts, 2 );
		$uuid         = implode( '.', $cookie_parts );
		if ( is_string( $uuid ) ) {
			return $uuid;
		} else {
			return false;
		}
	} elseif ( 0 === strpos( $ga_cookie, 'amp-' ) ) {
		return $ga_cookie;
	} else {
		return false;
	}
}

/**
 * Gets GA Session Id (GA4 only) from cookies.
 *
 * @var string $measurement_id
 *   GA4 Measurement Id (Property Id). E.g., 'G-1YS1VWHG3V'.
 *
 * @return int
 *   Returns GA4 Session Id or NULL if cookie wasn't found.
 */
function exactmetrics_get_browser_session_id( $measurement_id ) {
	// Cookie name example: '_ga_1YS1VWHG3V'.
	$cookie_name = '_ga_' . str_replace( 'G-', '', $measurement_id );
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		// Cookie value example: 'GS1.1.1659710029.4.1.1659710504.0'.
		// Session Id:                  ^^^^^^^^^^.
		$parts = explode( '.', $_COOKIE[ $cookie_name ] );
		return $parts[2];
	}

	return null;
}

/**
 * Generate UUID v4 function - needed to generate a CID when one isn't available
 *
 * @link http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
 *
 * @since 6.1.8
 * @return string
 */
function exactmetrics_generate_uuid() {

	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),
		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,
		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,
		// 48 bits for "node"
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff )
	);
}

/**
 * Returns the Google Analytics clientId to store for later use
 *
 * @return GA UUID or error code.
 * @since 6.0.0
 */
function exactmetrics_get_cookie( $debug = false ) {
	if ( empty( $_COOKIE['_ga'] ) ) {
		return ( $debug ) ? 'FCE' : false;
	}

	$ga_cookie    = $_COOKIE['_ga'];
	$cookie_parts = explode( '.', $ga_cookie );
	if ( is_array( $cookie_parts ) && ! empty( $cookie_parts[2] ) ) {
		$cookie_parts = array_slice( $cookie_parts, 2 );
		$uuid         = implode( '.', $cookie_parts );
		if ( is_string( $uuid ) ) {
			return $ga_cookie;
		} else {
			return ( $debug ) ? 'FA' : false;
		}
	} elseif ( 0 === strpos( $ga_cookie, 'amp-' ) ) {
		return $ga_cookie;
	} else {
		return ( $debug ) ? 'FAE' : false;
	}
}


function exactmetrics_generate_ga_client_id() {
	return rand( 100000000, 999999999 ) . '.' . time();
}


/**
 * Hours between two timestamps.
 *
 * @access public
 *
 * @param string $start Timestamp of start time (in seconds since Unix).
 * @param string $stop Timestamp of stop time (in seconds since Unix). Optional. If not used, current_time (in UTC 0 / GMT ) is used.
 *
 * @return int Hours between the two timestamps, rounded.
 * @since 6.0.0
 */
function exactmetrics_hours_between( $start, $stop = false ) {
	if ( $stop === false ) {
		$stop = time();
	}

	$diff  = (int) abs( $stop - $start );
	$hours = round( $diff / HOUR_IN_SECONDS );

	return $hours;
}

/**
 * Is This ExactMetrics Pro?
 *
 * We use this function exactmetrics_to determine if the install is a pro version or a lite version install of ExactMetrics.
 * If the install is a lite version we disable the install from admin functionality[1] for addons as WordPress.org requires us to,
 * we change the links for where to get support (wp.org forum for free; our site for pro), we use this determine what class to load as
 * the base class in addons (to avoid fatal errors) and we use this on the system info page to know what constants to display values for
 * as the lite and pro versions of our plugin have different constants (and names for those constants) you can declare and use.
 *
 * [1] Note: This is not "feature-locking" under GPL guidelines but rather something WordPress.org requires us to do to stay
 * in compliance with their rules. We wish we didn't have to do this, as in our oppinion this diminishes the user experience
 * of users installing our free and premium addons, and we'd love to turn this on for non-Pro installs, but we're not allowed to.
 * If WordPress.org ever changes their mind on this subject, we'd totally turn on that feature for Lite installs in a heartbeat.
 *
 * @return bool True if pro version.
 * @since 6.0.0
 * @access public
 *
 * @todo  Are we allowed to turn on admin installing if the user has to manually declare a PHP constant (and thus would not be on
 * either by default or via any sort of user interface)? If so, we could add a constant for forcing Pro version so that users can see
 * for themselves that we're not feature locking anything inside the plugin + it would make it easier for our team to test stuff (both via
 * Travis-CI but also when installing addons to test with the Lite version). Also this would allow for a better user experience for users
 * who want that feature.
 */
function exactmetrics_is_pro_version() {
	if ( class_exists( 'ExactMetrics' ) ) {
		return true;
	} else {
		return false;
	}
}


/**
 * Get the user roles of this WordPress blog
 *
 * @return array
 */
function exactmetrics_get_roles() {
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$roles     = array();

	/**
	 * Filter: 'editable_roles' - Allows filtering of the roles shown within the plugin (and elsewhere in WP as it's a WP filter)
	 *
	 * @api array $all_roles
	 */
	$editable_roles = apply_filters( 'editable_roles', $all_roles );

	foreach ( $editable_roles as $id => $name ) {
		$roles[ $id ] = translate_user_role( $name['name'] );
	}

	return $roles;
}

/**
 * Get the user roles which can manage options. Used to prevent these roles from getting unselected in the settings.
 *
 * @return array
 */
function exactmetrics_get_manage_options_roles() {
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$roles     = array();

	/**
	 * Filter: 'editable_roles' - Allows filtering of the roles shown within the plugin (and elsewhere in WP as it's a WP filter)
	 *
	 * @api array $all_roles
	 */
	$editable_roles = apply_filters( 'editable_roles', $all_roles );

	foreach ( $editable_roles as $id => $role ) {
		if ( isset( $role['capabilities']['manage_options'] ) && $role['capabilities']['manage_options'] ) {
			$roles[ $id ] = translate_user_role( $role['name'] );
		}
	}

	return $roles;
}

/** Need to escape in advance of passing in $text. */
function exactmetrics_get_message( $type = 'error', $text = '' ) {
	$div = '';
	if ( $type === 'error' || $type === 'alert' || $type === 'success' || $type === 'info' ) {
		$base = ExactMetrics();

		return $base->notices->display_inline_notice( 'exactmetrics_standard_notice', '', $text, $type, false, array( 'skip_message_escape' => true ) );
	} else {
		return '';
	}
}

function exactmetrics_is_dev_url( $url = '' ) {

	if ( empty( $url ) ) {
		return false;
	}

	// Trim it up
	$url = strtolower( trim( $url ) );
	// Need to get the host...so let's add the scheme so we can use parse_url
	if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
		$url = 'http://' . $url;
	}
	$url_parts = parse_url( $url );
	$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;
	if ( ! empty( $url ) && ! empty( $host ) ) {
		if ( false !== ip2long( $host ) ) {
			if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
		} elseif ( 'localhost' === $host ) {
			return true;
		}

		$tlds_to_check = array( '.local', ':8888', ':8080', ':8081', '.invalid', '.example', '.test', '.dev' );
		foreach ( $tlds_to_check as $tld ) {
			if ( false !== strpos( $host, $tld ) ) {
				return true;
			}
		}
		if ( substr_count( $host, '.' ) > 1 ) {
			$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
			foreach ( $subdomains_to_check as $subdomain ) {
				$subdomain = str_replace( '.', '(.)', $subdomain );
				$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
				if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
					return true;
					break;
				}
			}
		}

		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env_type = wp_get_environment_type();

			if ( 'development' === $env_type || 'local' === $env_type ) {
				return true;
			}
		}

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
			if ( defined( 'WP_ACCESSIBLE_HOSTS' ) && WP_ACCESSIBLE_HOSTS ) {
				$allowed_hosts = preg_split( '|,\s*|', WP_ACCESSIBLE_HOSTS );

				if ( is_array( $allowed_hosts ) && ! empty( $allowed_hosts ) ) {
					if ( ! in_array( '*.exactmetrics.com', $allowed_hosts, true ) || ! in_array( 'api.exactmetrics.com', $allowed_hosts, true ) ) {
						return true;
					}
				}
			}

			return true;
		}
	}

	return false;
}

// Set cookie to expire in 2 years
function exactmetrics_get_cookie_expiration_date( $time ) {
	return date( 'D, j F Y H:i:s', time() + $time );
}

function exactmetrics_string_ends_with( $string, $ending ) {
	$strlen    = strlen( $string );
	$endinglen = strlen( $ending );
	if ( $endinglen > $strlen ) {
		return false;
	}

	return substr_compare( $string, $ending, $strlen - $endinglen, $endinglen ) === 0;
}

function exactmetrics_string_starts_with( $string, $start ) {
	if ( ! is_string( $string ) || ! is_string( $start ) ) {
		return false;
	}

	return substr( $string, 0, strlen( $start ) ) === $start;
}

function exactmetrics_get_country_list( $translated = false ) {
	if ( $translated ) {
		$countries = array(
			''   => '',
			'US' => __( 'United States', 'google-analytics-dashboard-for-wp' ),
			'CA' => __( 'Canada', 'google-analytics-dashboard-for-wp' ),
			'GB' => __( 'United Kingdom', 'google-analytics-dashboard-for-wp' ),
			'AF' => __( 'Afghanistan', 'google-analytics-dashboard-for-wp' ),
			'AX' => __( '&#197;land Islands', 'google-analytics-dashboard-for-wp' ),
			'AL' => __( 'Albania', 'google-analytics-dashboard-for-wp' ),
			'DZ' => __( 'Algeria', 'google-analytics-dashboard-for-wp' ),
			'AS' => __( 'American Samoa', 'google-analytics-dashboard-for-wp' ),
			'AD' => __( 'Andorra', 'google-analytics-dashboard-for-wp' ),
			'AO' => __( 'Angola', 'google-analytics-dashboard-for-wp' ),
			'AI' => __( 'Anguilla', 'google-analytics-dashboard-for-wp' ),
			'AQ' => __( 'Antarctica', 'google-analytics-dashboard-for-wp' ),
			'AG' => __( 'Antigua and Barbuda', 'google-analytics-dashboard-for-wp' ),
			'AR' => __( 'Argentina', 'google-analytics-dashboard-for-wp' ),
			'AM' => __( 'Armenia', 'google-analytics-dashboard-for-wp' ),
			'AW' => __( 'Aruba', 'google-analytics-dashboard-for-wp' ),
			'AU' => __( 'Australia', 'google-analytics-dashboard-for-wp' ),
			'AT' => __( 'Austria', 'google-analytics-dashboard-for-wp' ),
			'AZ' => __( 'Azerbaijan', 'google-analytics-dashboard-for-wp' ),
			'BS' => __( 'Bahamas', 'google-analytics-dashboard-for-wp' ),
			'BH' => __( 'Bahrain', 'google-analytics-dashboard-for-wp' ),
			'BD' => __( 'Bangladesh', 'google-analytics-dashboard-for-wp' ),
			'BB' => __( 'Barbados', 'google-analytics-dashboard-for-wp' ),
			'BY' => __( 'Belarus', 'google-analytics-dashboard-for-wp' ),
			'BE' => __( 'Belgium', 'google-analytics-dashboard-for-wp' ),
			'BZ' => __( 'Belize', 'google-analytics-dashboard-for-wp' ),
			'BJ' => __( 'Benin', 'google-analytics-dashboard-for-wp' ),
			'BM' => __( 'Bermuda', 'google-analytics-dashboard-for-wp' ),
			'BT' => __( 'Bhutan', 'google-analytics-dashboard-for-wp' ),
			'BO' => __( 'Bolivia', 'google-analytics-dashboard-for-wp' ),
			'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'google-analytics-dashboard-for-wp' ),
			'BA' => __( 'Bosnia and Herzegovina', 'google-analytics-dashboard-for-wp' ),
			'BW' => __( 'Botswana', 'google-analytics-dashboard-for-wp' ),
			'BV' => __( 'Bouvet Island', 'google-analytics-dashboard-for-wp' ),
			'BR' => __( 'Brazil', 'google-analytics-dashboard-for-wp' ),
			'IO' => __( 'British Indian Ocean Territory', 'google-analytics-dashboard-for-wp' ),
			'BN' => __( 'Brunei Darrussalam', 'google-analytics-dashboard-for-wp' ),
			'BG' => __( 'Bulgaria', 'google-analytics-dashboard-for-wp' ),
			'BF' => __( 'Burkina Faso', 'google-analytics-dashboard-for-wp' ),
			'BI' => __( 'Burundi', 'google-analytics-dashboard-for-wp' ),
			'KH' => __( 'Cambodia', 'google-analytics-dashboard-for-wp' ),
			'CM' => __( 'Cameroon', 'google-analytics-dashboard-for-wp' ),
			'CV' => __( 'Cape Verde', 'google-analytics-dashboard-for-wp' ),
			'KY' => __( 'Cayman Islands', 'google-analytics-dashboard-for-wp' ),
			'CF' => __( 'Central African Republic', 'google-analytics-dashboard-for-wp' ),
			'TD' => __( 'Chad', 'google-analytics-dashboard-for-wp' ),
			'CL' => __( 'Chile', 'google-analytics-dashboard-for-wp' ),
			'CN' => __( 'China', 'google-analytics-dashboard-for-wp' ),
			'CX' => __( 'Christmas Island', 'google-analytics-dashboard-for-wp' ),
			'CC' => __( 'Cocos Islands', 'google-analytics-dashboard-for-wp' ),
			'CO' => __( 'Colombia', 'google-analytics-dashboard-for-wp' ),
			'KM' => __( 'Comoros', 'google-analytics-dashboard-for-wp' ),
			'CD' => __( 'Congo, Democratic People\'s Republic', 'google-analytics-dashboard-for-wp' ),
			'CG' => __( 'Congo, Republic of', 'google-analytics-dashboard-for-wp' ),
			'CK' => __( 'Cook Islands', 'google-analytics-dashboard-for-wp' ),
			'CR' => __( 'Costa Rica', 'google-analytics-dashboard-for-wp' ),
			'CI' => __( 'Cote d\'Ivoire', 'google-analytics-dashboard-for-wp' ),
			'HR' => __( 'Croatia/Hrvatska', 'google-analytics-dashboard-for-wp' ),
			'CU' => __( 'Cuba', 'google-analytics-dashboard-for-wp' ),
			'CW' => __( 'Cura&Ccedil;ao', 'google-analytics-dashboard-for-wp' ),
			'CY' => __( 'Cyprus', 'google-analytics-dashboard-for-wp' ),
			'CZ' => __( 'Czechia', 'google-analytics-dashboard-for-wp' ),
			'DK' => __( 'Denmark', 'google-analytics-dashboard-for-wp' ),
			'DJ' => __( 'Djibouti', 'google-analytics-dashboard-for-wp' ),
			'DM' => __( 'Dominica', 'google-analytics-dashboard-for-wp' ),
			'DO' => __( 'Dominican Republic', 'google-analytics-dashboard-for-wp' ),
			'TP' => __( 'East Timor', 'google-analytics-dashboard-for-wp' ),
			'EC' => __( 'Ecuador', 'google-analytics-dashboard-for-wp' ),
			'EG' => __( 'Egypt', 'google-analytics-dashboard-for-wp' ),
			'GQ' => __( 'Equatorial Guinea', 'google-analytics-dashboard-for-wp' ),
			'SV' => __( 'El Salvador', 'google-analytics-dashboard-for-wp' ),
			'ER' => __( 'Eritrea', 'google-analytics-dashboard-for-wp' ),
			'EE' => __( 'Estonia', 'google-analytics-dashboard-for-wp' ),
			'ET' => __( 'Ethiopia', 'google-analytics-dashboard-for-wp' ),
			'FK' => __( 'Falkland Islands', 'google-analytics-dashboard-for-wp' ),
			'FO' => __( 'Faroe Islands', 'google-analytics-dashboard-for-wp' ),
			'FJ' => __( 'Fiji', 'google-analytics-dashboard-for-wp' ),
			'FI' => __( 'Finland', 'google-analytics-dashboard-for-wp' ),
			'FR' => __( 'France', 'google-analytics-dashboard-for-wp' ),
			'GF' => __( 'French Guiana', 'google-analytics-dashboard-for-wp' ),
			'PF' => __( 'French Polynesia', 'google-analytics-dashboard-for-wp' ),
			'TF' => __( 'French Southern Territories', 'google-analytics-dashboard-for-wp' ),
			'GA' => __( 'Gabon', 'google-analytics-dashboard-for-wp' ),
			'GM' => __( 'Gambia', 'google-analytics-dashboard-for-wp' ),
			'GE' => __( 'Georgia', 'google-analytics-dashboard-for-wp' ),
			'DE' => __( 'Germany', 'google-analytics-dashboard-for-wp' ),
			'GR' => __( 'Greece', 'google-analytics-dashboard-for-wp' ),
			'GH' => __( 'Ghana', 'google-analytics-dashboard-for-wp' ),
			'GI' => __( 'Gibraltar', 'google-analytics-dashboard-for-wp' ),
			'GL' => __( 'Greenland', 'google-analytics-dashboard-for-wp' ),
			'GD' => __( 'Grenada', 'google-analytics-dashboard-for-wp' ),
			'GP' => __( 'Guadeloupe', 'google-analytics-dashboard-for-wp' ),
			'GU' => __( 'Guam', 'google-analytics-dashboard-for-wp' ),
			'GT' => __( 'Guatemala', 'google-analytics-dashboard-for-wp' ),
			'GG' => __( 'Guernsey', 'google-analytics-dashboard-for-wp' ),
			'GN' => __( 'Guinea', 'google-analytics-dashboard-for-wp' ),
			'GW' => __( 'Guinea-Bissau', 'google-analytics-dashboard-for-wp' ),
			'GY' => __( 'Guyana', 'google-analytics-dashboard-for-wp' ),
			'HT' => __( 'Haiti', 'google-analytics-dashboard-for-wp' ),
			'HM' => __( 'Heard and McDonald Islands', 'google-analytics-dashboard-for-wp' ),
			'VA' => __( 'Holy See (City Vatican State)', 'google-analytics-dashboard-for-wp' ),
			'HN' => __( 'Honduras', 'google-analytics-dashboard-for-wp' ),
			'HK' => __( 'Hong Kong', 'google-analytics-dashboard-for-wp' ),
			'HU' => __( 'Hungary', 'google-analytics-dashboard-for-wp' ),
			'IS' => __( 'Iceland', 'google-analytics-dashboard-for-wp' ),
			'IN' => __( 'India', 'google-analytics-dashboard-for-wp' ),
			'ID' => __( 'Indonesia', 'google-analytics-dashboard-for-wp' ),
			'IR' => __( 'Iran', 'google-analytics-dashboard-for-wp' ),
			'IQ' => __( 'Iraq', 'google-analytics-dashboard-for-wp' ),
			'IE' => __( 'Ireland', 'google-analytics-dashboard-for-wp' ),
			'IM' => __( 'Isle of Man', 'google-analytics-dashboard-for-wp' ),
			'IL' => __( 'Israel', 'google-analytics-dashboard-for-wp' ),
			'IT' => __( 'Italy', 'google-analytics-dashboard-for-wp' ),
			'JM' => __( 'Jamaica', 'google-analytics-dashboard-for-wp' ),
			'JP' => __( 'Japan', 'google-analytics-dashboard-for-wp' ),
			'JE' => __( 'Jersey', 'google-analytics-dashboard-for-wp' ),
			'JO' => __( 'Jordan', 'google-analytics-dashboard-for-wp' ),
			'KZ' => __( 'Kazakhstan', 'google-analytics-dashboard-for-wp' ),
			'KE' => __( 'Kenya', 'google-analytics-dashboard-for-wp' ),
			'KI' => __( 'Kiribati', 'google-analytics-dashboard-for-wp' ),
			'KW' => __( 'Kuwait', 'google-analytics-dashboard-for-wp' ),
			'KG' => __( 'Kyrgyzstan', 'google-analytics-dashboard-for-wp' ),
			'LA' => __( 'Lao People\'s Democratic Republic', 'google-analytics-dashboard-for-wp' ),
			'LV' => __( 'Latvia', 'google-analytics-dashboard-for-wp' ),
			'LB' => __( 'Lebanon', 'google-analytics-dashboard-for-wp' ),
			'LS' => __( 'Lesotho', 'google-analytics-dashboard-for-wp' ),
			'LR' => __( 'Liberia', 'google-analytics-dashboard-for-wp' ),
			'LY' => __( 'Libyan Arab Jamahiriya', 'google-analytics-dashboard-for-wp' ),
			'LI' => __( 'Liechtenstein', 'google-analytics-dashboard-for-wp' ),
			'LT' => __( 'Lithuania', 'google-analytics-dashboard-for-wp' ),
			'LU' => __( 'Luxembourg', 'google-analytics-dashboard-for-wp' ),
			'MO' => __( 'Macau', 'google-analytics-dashboard-for-wp' ),
			'MK' => __( 'Macedonia (FYROM)', 'google-analytics-dashboard-for-wp' ),
			'MG' => __( 'Madagascar', 'google-analytics-dashboard-for-wp' ),
			'MW' => __( 'Malawi', 'google-analytics-dashboard-for-wp' ),
			'MY' => __( 'Malaysia', 'google-analytics-dashboard-for-wp' ),
			'MV' => __( 'Maldives', 'google-analytics-dashboard-for-wp' ),
			'ML' => __( 'Mali', 'google-analytics-dashboard-for-wp' ),
			'MT' => __( 'Malta', 'google-analytics-dashboard-for-wp' ),
			'MH' => __( 'Marshall Islands', 'google-analytics-dashboard-for-wp' ),
			'MQ' => __( 'Martinique', 'google-analytics-dashboard-for-wp' ),
			'MR' => __( 'Mauritania', 'google-analytics-dashboard-for-wp' ),
			'MU' => __( 'Mauritius', 'google-analytics-dashboard-for-wp' ),
			'YT' => __( 'Mayotte', 'google-analytics-dashboard-for-wp' ),
			'MX' => __( 'Mexico', 'google-analytics-dashboard-for-wp' ),
			'FM' => __( 'Micronesia', 'google-analytics-dashboard-for-wp' ),
			'MD' => __( 'Moldova, Republic of', 'google-analytics-dashboard-for-wp' ),
			'MC' => __( 'Monaco', 'google-analytics-dashboard-for-wp' ),
			'MN' => __( 'Mongolia', 'google-analytics-dashboard-for-wp' ),
			'ME' => __( 'Montenegro', 'google-analytics-dashboard-for-wp' ),
			'MS' => __( 'Montserrat', 'google-analytics-dashboard-for-wp' ),
			'MA' => __( 'Morocco', 'google-analytics-dashboard-for-wp' ),
			'MZ' => __( 'Mozambique', 'google-analytics-dashboard-for-wp' ),
			'MM' => __( 'Myanmar', 'google-analytics-dashboard-for-wp' ),
			'NA' => __( 'Namibia', 'google-analytics-dashboard-for-wp' ),
			'NR' => __( 'Nauru', 'google-analytics-dashboard-for-wp' ),
			'NP' => __( 'Nepal', 'google-analytics-dashboard-for-wp' ),
			'NL' => __( 'Netherlands', 'google-analytics-dashboard-for-wp' ),
			'AN' => __( 'Netherlands Antilles', 'google-analytics-dashboard-for-wp' ),
			'NC' => __( 'New Caledonia', 'google-analytics-dashboard-for-wp' ),
			'NZ' => __( 'New Zealand', 'google-analytics-dashboard-for-wp' ),
			'NI' => __( 'Nicaragua', 'google-analytics-dashboard-for-wp' ),
			'NE' => __( 'Niger', 'google-analytics-dashboard-for-wp' ),
			'NG' => __( 'Nigeria', 'google-analytics-dashboard-for-wp' ),
			'NU' => __( 'Niue', 'google-analytics-dashboard-for-wp' ),
			'NF' => __( 'Norfolk Island', 'google-analytics-dashboard-for-wp' ),
			'KP' => __( 'North Korea', 'google-analytics-dashboard-for-wp' ),
			'MP' => __( 'Northern Mariana Islands', 'google-analytics-dashboard-for-wp' ),
			'NO' => __( 'Norway', 'google-analytics-dashboard-for-wp' ),
			'OM' => __( 'Oman', 'google-analytics-dashboard-for-wp' ),
			'PK' => __( 'Pakistan', 'google-analytics-dashboard-for-wp' ),
			'PW' => __( 'Palau', 'google-analytics-dashboard-for-wp' ),
			'PS' => __( 'Palestinian Territories', 'google-analytics-dashboard-for-wp' ),
			'PA' => __( 'Panama', 'google-analytics-dashboard-for-wp' ),
			'PG' => __( 'Papua New Guinea', 'google-analytics-dashboard-for-wp' ),
			'PY' => __( 'Paraguay', 'google-analytics-dashboard-for-wp' ),
			'PE' => __( 'Peru', 'google-analytics-dashboard-for-wp' ),
			'PH' => __( 'Philippines', 'google-analytics-dashboard-for-wp' ),
			'PN' => __( 'Pitcairn Island', 'google-analytics-dashboard-for-wp' ),
			'PL' => __( 'Poland', 'google-analytics-dashboard-for-wp' ),
			'PT' => __( 'Portugal', 'google-analytics-dashboard-for-wp' ),
			'PR' => __( 'Puerto Rico', 'google-analytics-dashboard-for-wp' ),
			'QA' => __( 'Qatar', 'google-analytics-dashboard-for-wp' ),
			'XK' => __( 'Republic of Kosovo', 'google-analytics-dashboard-for-wp' ),
			'RE' => __( 'Reunion Island', 'google-analytics-dashboard-for-wp' ),
			'RO' => __( 'Romania', 'google-analytics-dashboard-for-wp' ),
			'RU' => __( 'Russian Federation', 'google-analytics-dashboard-for-wp' ),
			'RW' => __( 'Rwanda', 'google-analytics-dashboard-for-wp' ),
			'BL' => __( 'Saint Barth&eacute;lemy', 'google-analytics-dashboard-for-wp' ),
			'SH' => __( 'Saint Helena', 'google-analytics-dashboard-for-wp' ),
			'KN' => __( 'Saint Kitts and Nevis', 'google-analytics-dashboard-for-wp' ),
			'LC' => __( 'Saint Lucia', 'google-analytics-dashboard-for-wp' ),
			'MF' => __( 'Saint Martin (French)', 'google-analytics-dashboard-for-wp' ),
			'SX' => __( 'Saint Martin (Dutch)', 'google-analytics-dashboard-for-wp' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'google-analytics-dashboard-for-wp' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'google-analytics-dashboard-for-wp' ),
			'SM' => __( 'San Marino', 'google-analytics-dashboard-for-wp' ),
			'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'google-analytics-dashboard-for-wp' ),
			'SA' => __( 'Saudi Arabia', 'google-analytics-dashboard-for-wp' ),
			'SN' => __( 'Senegal', 'google-analytics-dashboard-for-wp' ),
			'RS' => __( 'Serbia', 'google-analytics-dashboard-for-wp' ),
			'SC' => __( 'Seychelles', 'google-analytics-dashboard-for-wp' ),
			'SL' => __( 'Sierra Leone', 'google-analytics-dashboard-for-wp' ),
			'SG' => __( 'Singapore', 'google-analytics-dashboard-for-wp' ),
			'SK' => __( 'Slovak Republic', 'google-analytics-dashboard-for-wp' ),
			'SI' => __( 'Slovenia', 'google-analytics-dashboard-for-wp' ),
			'SB' => __( 'Solomon Islands', 'google-analytics-dashboard-for-wp' ),
			'SO' => __( 'Somalia', 'google-analytics-dashboard-for-wp' ),
			'ZA' => __( 'South Africa', 'google-analytics-dashboard-for-wp' ),
			'GS' => __( 'South Georgia', 'google-analytics-dashboard-for-wp' ),
			'KR' => __( 'South Korea', 'google-analytics-dashboard-for-wp' ),
			'SS' => __( 'South Sudan', 'google-analytics-dashboard-for-wp' ),
			'ES' => __( 'Spain', 'google-analytics-dashboard-for-wp' ),
			'LK' => __( 'Sri Lanka', 'google-analytics-dashboard-for-wp' ),
			'SD' => __( 'Sudan', 'google-analytics-dashboard-for-wp' ),
			'SR' => __( 'Suriname', 'google-analytics-dashboard-for-wp' ),
			'SJ' => __( 'Svalbard and Jan Mayen Islands', 'google-analytics-dashboard-for-wp' ),
			'SZ' => __( 'Swaziland', 'google-analytics-dashboard-for-wp' ),
			'SE' => __( 'Sweden', 'google-analytics-dashboard-for-wp' ),
			'CH' => __( 'Switzerland', 'google-analytics-dashboard-for-wp' ),
			'SY' => __( 'Syrian Arab Republic', 'google-analytics-dashboard-for-wp' ),
			'TW' => __( 'Taiwan', 'google-analytics-dashboard-for-wp' ),
			'TJ' => __( 'Tajikistan', 'google-analytics-dashboard-for-wp' ),
			'TZ' => __( 'Tanzania', 'google-analytics-dashboard-for-wp' ),
			'TH' => __( 'Thailand', 'google-analytics-dashboard-for-wp' ),
			'TL' => __( 'Timor-Leste', 'google-analytics-dashboard-for-wp' ),
			'TG' => __( 'Togo', 'google-analytics-dashboard-for-wp' ),
			'TK' => __( 'Tokelau', 'google-analytics-dashboard-for-wp' ),
			'TO' => __( 'Tonga', 'google-analytics-dashboard-for-wp' ),
			'TT' => __( 'Trinidad and Tobago', 'google-analytics-dashboard-for-wp' ),
			'TN' => __( 'Tunisia', 'google-analytics-dashboard-for-wp' ),
			'TR' => __( 'Turkey', 'google-analytics-dashboard-for-wp' ),
			'TM' => __( 'Turkmenistan', 'google-analytics-dashboard-for-wp' ),
			'TC' => __( 'Turks and Caicos Islands', 'google-analytics-dashboard-for-wp' ),
			'TV' => __( 'Tuvalu', 'google-analytics-dashboard-for-wp' ),
			'UG' => __( 'Uganda', 'google-analytics-dashboard-for-wp' ),
			'UA' => __( 'Ukraine', 'google-analytics-dashboard-for-wp' ),
			'AE' => __( 'United Arab Emirates', 'google-analytics-dashboard-for-wp' ),
			'UY' => __( 'Uruguay', 'google-analytics-dashboard-for-wp' ),
			'UM' => __( 'US Minor Outlying Islands', 'google-analytics-dashboard-for-wp' ),
			'UZ' => __( 'Uzbekistan', 'google-analytics-dashboard-for-wp' ),
			'VU' => __( 'Vanuatu', 'google-analytics-dashboard-for-wp' ),
			'VE' => __( 'Venezuela', 'google-analytics-dashboard-for-wp' ),
			'VN' => __( 'Vietnam', 'google-analytics-dashboard-for-wp' ),
			'VG' => __( 'Virgin Islands (British)', 'google-analytics-dashboard-for-wp' ),
			'VI' => __( 'Virgin Islands (USA)', 'google-analytics-dashboard-for-wp' ),
			'WF' => __( 'Wallis and Futuna Islands', 'google-analytics-dashboard-for-wp' ),
			'EH' => __( 'Western Sahara', 'google-analytics-dashboard-for-wp' ),
			'WS' => __( 'Western Samoa', 'google-analytics-dashboard-for-wp' ),
			'YE' => __( 'Yemen', 'google-analytics-dashboard-for-wp' ),
			'ZM' => __( 'Zambia', 'google-analytics-dashboard-for-wp' ),
			'ZW' => __( 'Zimbabwe', 'google-analytics-dashboard-for-wp' ),
			'ZZ' => __( 'Unknown Country', 'google-analytics-dashboard-for-wp' ),
		);
	} else {
		$countries = array(
			''   => '',
			'US' => 'United States',
			'CA' => 'Canada',
			'GB' => 'United Kingdom',
			'AF' => 'Afghanistan',
			'AX' => '&#197;land Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BQ' => 'Bonaire, Saint Eustatius and Saba',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darrussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CD' => 'Congo, Democratic People\'s Republic',
			'CG' => 'Congo, Republic of',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote d\'Ivoire',
			'HR' => 'Croatia/Hrvatska',
			'CU' => 'Cuba',
			'CW' => 'Cura&Ccedil;ao',
			'CY' => 'Cyprus',
			'CZ' => 'Czechia',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'TP' => 'East Timor',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'GQ' => 'Equatorial Guinea',
			'SV' => 'El Salvador',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard and McDonald Islands',
			'VA' => 'Holy See (City Vatican State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macau',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar (Burma)',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'KP' => 'North Korea',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territories',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn Island',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'XK' => 'Republic of Kosovo',
			'RE' => 'Reunion Island',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barth&eacute;lemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French)',
			'SX' => 'Saint Martin (Dutch)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'SM' => 'San Marino',
			'ST' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovak Republic',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia',
			'KR' => 'South Korea',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen Islands',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'UY' => 'Uruguay',
			'UM' => 'US Minor Outlying Islands',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Vietnam',
			'VG' => 'Virgin Islands (British)',
			'VI' => 'Virgin Islands (USA)',
			'WF' => 'Wallis and Futuna Islands',
			'EH' => 'Western Sahara',
			'WS' => 'Western Samoa',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
			'ZZ' => 'Unknown Country',
		);
	}

	return $countries;
}

function exactmetrics_get_api_url() {
	return apply_filters( 'exactmetrics_get_api_url', 'api.exactmetrics.com/v2/' );
}

function exactmetrics_get_licensing_url() {
	return apply_filters( 'exactmetrics_get_licensing_url', 'https://www.exactmetrics.com' );
}

function exactmetrics_is_wp_seo_active() {
	$wp_seo_active = false; // @todo: improve this check. This is from old Yoast code.

	// Makes sure is_plugin_active is available when called from front end
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
		$wp_seo_active = true;
	}

	return $wp_seo_active;
}

function exactmetrics_get_asset_version() {
	if ( exactmetrics_is_debug_mode() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
		return time();
	} else {
		return EXACTMETRICS_VERSION;
	}
}

function exactmetrics_is_debug_mode() {
	$debug_mode = false;
	if ( defined( 'EXACTMETRICS_DEBUG_MODE' ) && EXACTMETRICS_DEBUG_MODE ) {
		$debug_mode = true;
	}

	return apply_filters( 'exactmetrics_is_debug_mode', $debug_mode );
}

function exactmetrics_is_network_active() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if ( is_multisite() && is_plugin_active_for_network( plugin_basename( EXACTMETRICS_PLUGIN_FILE ) ) ) {
		return true;
	} else {
		return false;
	}
}

if ( ! function_exists( 'remove_class_filter' ) ) {
	/**
	 * Remove Class Filter Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_filter() on a filter added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove filters with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2 - 4.7+
	 *
	 * @param string $tag Filter to remove
	 * @param string $class_name Class name for the filter's callback
	 * @param string $method_name Method name for the filter's callback
	 * @param int    $priority Priority of the filter (default 10)
	 *
	 * @return bool Whether the function is removed.
	 */
	function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		global $wp_filter;
		// Check that filter actually exists first
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return false;
		}
		/**
		 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
		 * a simple array, rather it is an object that implements the ArrayAccess interface.
		 *
		 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
		 *
		 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
		 */
		if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
			$callbacks = &$wp_filter[ $tag ]->callbacks;
		} else {
			$callbacks = &$wp_filter[ $tag ];
		}
		// Exit if there aren't any callbacks for specified priority
		if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
			return false;
		}
		// Loop through each filter for the specified priority, looking for our class & method
		foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {
			// Filter should always be an array - array( $this, 'method' ), if not goto next
			if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
				continue;
			}
			// If first value in array is not an object, it can't be a class
			if ( ! is_object( $filter['function'][0] ) ) {
				continue;
			}
			// Method doesn't match the one we're looking for, goto next
			if ( $filter['function'][1] !== $method_name ) {
				continue;
			}
			// Method matched, now let's check the Class
			if ( get_class( $filter['function'][0] ) === $class_name ) {
				// Now let's remove it from the array
				unset( $callbacks[ $priority ][ $filter_id ] );
				// and if it was the only filter in that priority, unset that priority
				if ( empty( $callbacks[ $priority ] ) ) {
					unset( $callbacks[ $priority ] );
				}
				// and if the only filter for that tag, set the tag to an empty array
				if ( empty( $callbacks ) ) {
					$callbacks = array();
				}
				// If using WordPress older than 4.7
				if ( ! is_object( $wp_filter[ $tag ] ) ) {
					// Remove this filter from merged_filters, which specifies if filters have been sorted
					unset( $GLOBALS['merged_filters'][ $tag ] );
				}

				return true;
			}
		}

		return false;
	}
} // End function exists

if ( ! function_exists( 'remove_class_action' ) ) {
	/**
	 * Remove Class Action Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_action() on an action added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove actions with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2 - 4.7+
	 *
	 * @param string $tag Action to remove
	 * @param string $class_name Class name for the action's callback
	 * @param string $method_name Method name for the action's callback
	 * @param int    $priority Priority of the action (default 10)
	 *
	 * @return bool               Whether the function is removed.
	 */
	function remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		remove_class_filter( $tag, $class_name, $method_name, $priority );
	}
} // End function exists

/**
 * Format a big number, instead of 1000000 you get 1.0M, works with billions also.
 *
 * @param int $number
 * @param int $precision
 *
 * @return string
 */
function exactmetrics_round_number( $number, $precision = 2 ) {

	if ( $number < 1000000 ) {
		// Anything less than a million
		$number = number_format_i18n( $number );
	} elseif ( $number < 1000000000 ) {
		// Anything less than a billion
		$number = number_format_i18n( $number / 1000000, $precision ) . 'M';
	} else {
		// At least a billion
		$number = number_format_i18n( $number / 1000000000, $precision ) . 'B';
	}

	return $number;
}

if ( ! function_exists( 'wp_get_jed_locale_data' ) ) {
	/**
	 * Returns Jed-formatted localization data. Added for backwards-compatibility.
	 *
	 * @param string $domain Translation domain.
	 *
	 * @return array
	 */
	function wp_get_jed_locale_data( $domain ) {
		$translations = get_translations_for_domain( $domain );

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}
}

function exactmetrics_get_inline_menu_icon() {
	$scheme          = get_user_option( 'admin_color', get_current_user_id() );
	$use_dark_scheme = $scheme === 'light';
	if ( $use_dark_scheme ) {
		return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iIzAwMCIgZmlsbC1ydWxlPSJub256ZXJvIj48cGF0aCBkPSJNMTQuNjI0IDguODY2YTIuOTM1IDIuOTM1IDAgMCAwLTMuMTQxLTIuOTI3QTIuOTM4IDIuOTM4IDAgMCAwIDguNzcgOC42NTNhMi45MzUgMi45MzUgMCAwIDAgMi45MjcgMy4xNCAyLjkyMyAyLjkyMyAwIDAgMCAyLjkyNy0yLjkyN1ptLTQuMjc2IDBjMC0uNTkyLjM3LTEuMTM0LjkzOC0xLjMwNy45MzctLjI4OCAxLjguNDAzIDEuOCAxLjMgMCAuNzU2LS42MDggMS4zNjQtMS4zNjUgMS4zNjRhMS4zNjEgMS4zNjEgMCAwIDEtMS4zNzMtMS4zNTdaTTguNCA4LjE0M2EzLjM1IDMuMzUgMCAwIDEgLjQyNy0xLjA3SDQuNTExVjUuNTk1SDEuMzA0YS43MzcuNzM3IDAgMSAwIDAgMS40NzJsLjAyNS4wMDhINC42djEuMDY5SDIuNzkyYS43MDcuNzA3IDAgMCAwLS43NC43MTVjMCAuNDExLjMzLjc0Ljc0Ljc2NWgxLjgxdi0xLjQ4SDguNFoiLz48cGF0aCBkPSJNOC44NjggMTAuNjkyYTMuNTUyIDMuNTUyIDAgMCAxLS40MjctMS4wN0g0LjU5M3YuOTg3aC0uMjhhLjc2Ljc2IDAgMCAwLS43NTYuNjM0LjczNy43MzcgMCAwIDAgLjczMi44NDZoLjMwNHYtMS4zOTdoNC4yNzVaIi8+PHBhdGggZD0ibTE4LjI1IDQuMzY5LTUuODQ2LTMuMzQ2YTEuMzI0IDEuMzI0IDAgMCAwLTEuMzA4IDBMNS4yNTEgNC4zNjljLS40MDMuMjMtLjY1LjY1OC0uNjUgMS4xMjZ2LjEwN2g2LjAzNWEzLjQ2MSAzLjQ2MSAwIDAgMSA0LjM5IDIuMTM4Yy42MTcgMS43OTItLjM0NSAzLjc0LTIuMTU0IDQuMzU4YTMuNDcyIDMuNDcyIDAgMCAxLTIuMjM2IDBINC41OTN2LjEwNmMwIC40Ni4yNDcuODg4LjY1IDEuMTI3bDUuODQ1IDMuMzQ2Yy40MDMuMjMuOTA1LjIzIDEuMzA4IDBsNS44NDUtMy4zNDZjLjQwMy0uMjMuNjUtLjY1OC42NS0xLjEyN1Y1LjQ5NWExLjI1MSAxLjI1MSAwIDAgMC0uNjQxLTEuMTI2WiIvPjwvZz48L3N2Zz4=';
		// return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACYAAAAlCAMAAAAZd2syAAAAAXNSR0IB2cksfwAAAAlwSFlzAAAWJQAAFiUBSVIk8AAAAapQTFRFAAAA////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////3hTzAgAAAI50Uk5TACVrdjwKgvj/pCBP4Px5AzXA84Fg31oUl/6+HA6D97wiAWTq2kewmB8+yO8HWWFGbV46BnoeTHN7U72OyZqTuvucEWno20DYFm/X3BWU7QlWHdBXiKLlLWK7uFvsC4DdE9lBNGyMwagwEBsZPycMzYT2fUj0zFw5NhhV3ku/qhKzpcNO4+TxDQ+FlqHueGDblssAAAHSSURBVHicY2AYPICRiZmFoCJWNnYODg5OLvyquHk4wICXjx+3IgFBoAohYRFRICUqhkORuIQkUFpKmoFBRlYOyJJXwKJIUUkZKKWiCuFxqYGsVtdAV6WpBRTW5tOBC+iCbdZDVaUvDAQGrChihmCFRri9AgXGJtpAG0AsUy4MYGZuYSkNVchlxcEBpKw5MIGNrZ29g6MTRJ0zWJkLhiJXN5Cshq27B5IyTy9vFCDp6gO1ztceSRka4PIDxqU/d0AgkAwKRlGmiOSLkFBg3HtxcISFMzBERKIoi0J2GTA0o0G0FdB5McjKYiWRlVkyMMSB6HgGhgQHFNPYEkXhQJuJgSEJpAxoeTIPTi+kAC1jSOXgSAP6wTMdXZkizAsZ2qAYzMwCEtmiOejKpBCOc8mFCOXlF6CHmwyyJwqLxBkYdIq9rBnQlaH4QlRU0su9pLSMAVMZKpApr6iEObrKBacyhM/C5YGuqEYWqrGqra2tQ8md9akOHBySDdIoWrl8gTolG+EKneKAKVfSShPDDq4mUDZOBaczxeYWIKdVF6tjINm4jEFRoBbIaItrx+Hojk5QASLYBUwOkulZeHzH1Q1OMZLumI5CBUbVkpI9vToEVJENAOVRStPJFiL7AAAAAElFTkSuQmCC';
	} else {
		return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI0ZGRiIgZmlsbC1ydWxlPSJub256ZXJvIj48cGF0aCBkPSJNMTQuNjI0IDguMjE2YTIuOTM1IDIuOTM1IDAgMCAwLTMuMTQtMi45MjdBMi45MzggMi45MzggMCAwIDAgOC43NyA4LjAwM2EyLjkzNSAyLjkzNSAwIDAgMCAyLjkyNyAzLjE0IDIuOTIzIDIuOTIzIDAgMCAwIDIuOTI3LTIuOTI3Wm0tNC4yNzYgMGMwLS41OTIuMzctMS4xMzQuOTM4LTEuMzA3LjkzNy0uMjg4IDEuOC40MDMgMS44IDEuMyAwIC43NTYtLjYwOCAxLjM2NC0xLjM2NSAxLjM2NGExLjM2MSAxLjM2MSAwIDAgMS0xLjM3My0xLjM1N1pNOC40IDcuNDkzYTMuMzUgMy4zNSAwIDAgMSAuNDI3LTEuMDdINC41MTFWNC45NDVIMS4zMDRhLjczNy43MzcgMCAxIDAgMCAxLjQ3MmwuMDI1LjAwOEg0LjZ2MS4wNjlIMi43OTJhLjcwNy43MDcgMCAwIDAtLjc0LjcxNWMwIC40MTEuMzMuNzQuNzQuNzY1aDEuODF2LTEuNDhIOC40WiIvPjxwYXRoIGQ9Ik04Ljg2OCAxMC4wNDJhMy41NTIgMy41NTIgMCAwIDEtLjQyNy0xLjA3SDQuNTkzdi45ODdoLS4yOGEuNzYuNzYgMCAwIDAtLjc1Ni42MzQuNzM3LjczNyAwIDAgMCAuNzMyLjg0NmguMzA0di0xLjM5N2g0LjI3NVoiLz48cGF0aCBkPSJNMTguMjUgMy43MTkgMTIuNDA0LjM3M2ExLjMyNCAxLjMyNCAwIDAgMC0xLjMwNyAwTDUuMjUgMy43MTljLS40MDMuMjMtLjY1LjY1OC0uNjUgMS4xMjZ2LjEwN2g2LjAzNWEzLjQ2MSAzLjQ2MSAwIDAgMSA0LjM5IDIuMTM4Yy42MTcgMS43OTItLjM0NSAzLjc0LTIuMTU0IDQuMzU4YTMuNDcyIDMuNDcyIDAgMCAxLTIuMjM2IDBINC41OTN2LjEwNmMwIC40Ni4yNDcuODg4LjY1IDEuMTI3bDUuODQ1IDMuMzQ2Yy40MDMuMjMuOTA1LjIzIDEuMzA4IDBsNS44NDUtMy4zNDZjLjQwMy0uMjMuNjUtLjY1OC42NS0xLjEyN1Y0Ljg0NWExLjI1MSAxLjI1MSAwIDAgMC0uNjQxLTEuMTI2WiIvPjwvZz48L3N2Zz4=';
		// return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACYAAAAlCAMAAAAZd2syAAAAAXNSR0IB2cksfwAAAAlwSFlzAAAWJQAAFiUBSVIk8AAAAapQTFRFAAAA////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////3hTzAgAAAI50Uk5TACVrdjwKgvj/pCBP4Px5AzXA84Fg31oUl/6+HA6D97wiAWTq2kewmB8+yO8HWWFGbV46BnoeTHN7U72OyZqTuvucEWno20DYFm/X3BWU7QlWHdBXiKLlLWK7uFvsC4DdE9lBNGyMwagwEBsZPycMzYT2fUj0zFw5NhhV3ku/qhKzpcNO4+TxDQ+FlqHueGDblssAAAHSSURBVHicY2AYPICRiZmFoCJWNnYODg5OLvyquHk4wICXjx+3IgFBoAohYRFRICUqhkORuIQkUFpKmoFBRlYOyJJXwKJIUUkZKKWiCuFxqYGsVtdAV6WpBRTW5tOBC+iCbdZDVaUvDAQGrChihmCFRri9AgXGJtpAG0AsUy4MYGZuYSkNVchlxcEBpKw5MIGNrZ29g6MTRJ0zWJkLhiJXN5Cshq27B5IyTy9vFCDp6gO1ztceSRka4PIDxqU/d0AgkAwKRlGmiOSLkFBg3HtxcISFMzBERKIoi0J2GTA0o0G0FdB5McjKYiWRlVkyMMSB6HgGhgQHFNPYEkXhQJuJgSEJpAxoeTIPTi+kAC1jSOXgSAP6wTMdXZkizAsZ2qAYzMwCEtmiOejKpBCOc8mFCOXlF6CHmwyyJwqLxBkYdIq9rBnQlaH4QlRU0su9pLSMAVMZKpApr6iEObrKBacyhM/C5YGuqEYWqrGqra2tQ8md9akOHBySDdIoWrl8gTolG+EKneKAKVfSShPDDq4mUDZOBaczxeYWIKdVF6tjINm4jEFRoBbIaItrx+Hojk5QASLYBUwOkulZeHzH1Q1OMZLumI5CBUbVkpI9vToEVJENAOVRStPJFiL7AAAAAElFTkSuQmCC';
	}
}


function exactmetrics_get_shareasale_id() {
	// Check if there's a constant.
	$shareasale_id = '';
	if ( defined( 'EXACTMETRICS_SHAREASALE_ID' ) ) {
		$shareasale_id = EXACTMETRICS_SHAREASALE_ID;
	}

	// If there's no constant, check if there's an option.
	if ( empty( $shareasale_id ) ) {
		$shareasale_id = get_option( 'exactmetrics_shareasale_id', '' );
	}

	// Whether we have an ID or not, filter the ID.
	$shareasale_id = apply_filters( 'exactmetrics_shareasale_id', $shareasale_id );

	// Ensure it's a number
	$shareasale_id = absint( $shareasale_id );

	return $shareasale_id;
}

// Passed in with mandatory default redirect and shareasaleid from exactmetrics_get_upgrade_link
function exactmetrics_get_shareasale_url( $shareasale_id, $shareasale_redirect ) {
	// Check if there's a constant.
	$custom = false;
	if ( defined( 'EXACTMETRICS_SHAREASALE_REDIRECT_URL' ) ) {
		$shareasale_redirect = EXACTMETRICS_SHAREASALE_REDIRECT_URL;
		$custom              = true;
	}

	// If there's no constant, check if there's an option.
	if ( empty( $custom ) ) {
		$shareasale_redirect = get_option( 'exactmetrics_shareasale_redirect_url', '' );
		$custom              = true;
	}

	// Whether we have an ID or not, filter the ID.
	$shareasale_redirect = apply_filters( 'exactmetrics_shareasale_redirect_url', $shareasale_redirect, $custom );
	$shareasale_url      = sprintf( 'https://www.shareasale.com/r.cfm?B=1494714&U=%s&M=94980&urllink=%s', $shareasale_id, $shareasale_redirect );
	$shareasale_url      = apply_filters( 'exactmetrics_shareasale_redirect_entire_url', $shareasale_url, $shareasale_id, $shareasale_redirect );

	return $shareasale_url;
}

/**
 * Get a clean page title for archives.
 */
function exactmetrics_get_page_title() {

	$title = __( 'Archives' );

	if ( is_category() ) {
		/* translators: Category archive title. %s: Category name */
		$title = sprintf( __( 'Category: %s' ), single_cat_title( '', false ) );
	} elseif ( is_tag() ) {
		/* translators: Tag archive title. %s: Tag name */
		$title = sprintf( __( 'Tag: %s' ), single_tag_title( '', false ) );
	} elseif ( is_author() ) {
		/* translators: Author archive title. %s: Author name */
		$title = sprintf( __( 'Author: %s' ), '<span class="vcard">' . get_the_author() . '</span>' );
	} elseif ( is_year() ) {
		/* translators: Yearly archive title. %s: Year */
		$title = sprintf( __( 'Year: %s' ), get_the_date( _x( 'Y', 'yearly archives date format' ) ) );
	} elseif ( is_month() ) {
		/* translators: Monthly archive title. %s: Month name and year */
		$title = sprintf( __( 'Month: %s' ), get_the_date( _x( 'F Y', 'monthly archives date format' ) ) );
	} elseif ( is_day() ) {
		/* translators: Daily archive title. %s: Date */
		$title = sprintf( __( 'Day: %s' ), get_the_date( _x( 'F j, Y', 'daily archives date format' ) ) );
	} elseif ( is_tax( 'post_format' ) ) {
		if ( is_tax( 'post_format', 'post-format-aside' ) ) {
			$title = _x( 'Asides', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
			$title = _x( 'Galleries', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
			$title = _x( 'Images', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
			$title = _x( 'Videos', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
			$title = _x( 'Quotes', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
			$title = _x( 'Links', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
			$title = _x( 'Statuses', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
			$title = _x( 'Audio', 'post format archive title' );
		} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
			$title = _x( 'Chats', 'post format archive title' );
		}
	} elseif ( is_post_type_archive() ) {
		/* translators: Post type archive title. %s: Post type name */
		$title = sprintf( __( 'Archives: %s' ), post_type_archive_title( '', false ) );
	} elseif ( is_tax() ) {
		$tax = get_taxonomy( get_queried_object()->taxonomy );
		/* translators: Taxonomy term archive title. 1: Taxonomy singular name, 2: Current taxonomy term */
		$title = sprintf( '%1$s: %2$s', $tax->labels->singular_name, single_term_title( '', false ) );
	}

	return $title;
}

/**
 * Count the number of occurrences of UA tags inserted by third-party plugins.
 *
 * @param string $body
 * @param string $type
 *
 * @return int
 */
function exactmetrics_count_third_party_ua_codes( $body, $type = 'ua' ) {
	$count = 0;

	// Count all potential google site verification tags
	if ( $type === 'ua' ) {
		$pattern = '/content="UA-[0-9-]+"/';

		if ( preg_match_all( $pattern, $body, $matches ) ) {
			$count += count( $matches[0] );
		}
	}

	// Advanced Ads plugin (https://wpadvancedads.com)
	// When `Ad blocker counter` setting is populated with an UA ID
	if ( class_exists( 'Advanced_Ads' ) ) {
		$options = Advanced_Ads::get_instance()->options();

		$pattern = '/UA-[0-9-]+/';
		if ( $type === 'ua' && isset( $options['ga-UID'] ) && preg_match( $pattern, $options['ga-UID'] ) ) {
			++ $count;
		}
	}

	// WP Popups plugin (https://wppopups.com/)
	// When `Google UA Code` setting is populated with an UA Id
	if ( function_exists( 'wppopups_setting' ) ) {
		$code = wppopups_setting( 'ua-code' );

		$pattern = '/UA-[0-9-]+/';
		if ( $type === 'ua' && ! empty( $code ) && preg_match( $pattern, $code ) ) {
			++ $count;
		}
	}

	return $count;
}

/**
 * Count the number of times the same tracking ID is used for Ads and Performance
 *
 * @param $current_code
 *
 * @return int
 */
function exactmetrics_count_addon_codes( $current_code ) {
	$count = 0;

	// If the ads addon is installed and its conversion ID is the same as the current code, then increase the count
	if ( class_exists( 'ExactMetrics_Ads' ) ) {
		$ads_id = esc_attr( exactmetrics_get_option( 'gtag_ads_conversion_id' ) );

		if ( $ads_id === $current_code ) {
			$count ++;
		}
	}

	// If the performance addon is installed and its Google Optimize ID is the same as the current code, then increase the count
	if ( class_exists( 'ExactMetrics_Performance' ) ) {
		$container_id = exactmetrics_get_option( 'goptimize_container', '' );

		if ( $container_id === $current_code ) {
			$count ++;
		}
	}

	return $count;
}

/**
 * Detect tracking code error depending on the type of tracking code
 *
 * @param string $body
 * @param string $type
 *
 * @return array
 */
function exactmetrics_detect_tracking_code_error( $body, $type = 'ua' ) {
	$errors = array();

	$current_code = $type === 'ua'
		? exactmetrics_get_ua_to_output()
		: exactmetrics_get_v4_id_to_output();

	$url = exactmetrics_get_url( 'notice', 'using-cache', 'https://www.wpbeginner.com/beginners-guide/how-to-clear-your-cache-in-wordpress/' );
	// Translators: The placeholders are for making the "We noticed you're using a caching plugin" text bold.
	$cache_error = sprintf(
		esc_html__( '%1$sWe noticed you\'re using a caching plugin or caching from your hosting provider.%2$s Be sure to clear the cache to ensure the tracking appears on all pages and posts. %3$s(See this guide on how to clear cache)%4$s.', 'google-analytics-dashboard-for-wp' ),
		'<b>',
		'</b>',
		'<a target="_blank" href="' . esc_url( $url ) . '" target="_blank">',
		'</a>'
	);

	// Check if the current UA code is actually present.
	if ( $current_code && false === strpos( $body, $current_code ) ) {
		// We have the tracking code but using another UA, so it's cached.
		$errors[] = $cache_error;

		return $errors;
	}

	if ( empty( $current_code ) ) {
		return $errors;
	}

	if ( $type === 'v4' && false === strpos( $body, '__gtagTracker' ) ) {
		$errors[] = $cache_error;

		return $errors;
	}

	$limit = 3;

	$limit += exactmetrics_count_addon_codes( $current_code );

	// TODO: Need to re-evaluate this regularly when third party plugins start supporting v4
	$limit += exactmetrics_count_third_party_ua_codes( $body, $type );

	// Count all the codes from the page.
	$total_count = substr_count( $body, $current_code );

	// Count the `send_to` instances which are valid
	$pattern = '/send_to[\'"]*?:\s*[\'"]' . $current_code . '/m';
	if ( preg_match_all( $pattern, $body, $matches ) ) {
		$total_count -= count( $matches[0] );
	}

	// Main property always has a ?id=(UA|G|GT)-XXXXXXXX script
	$connected_type = ExactMetrics()->auth->get_connected_type();
	if ( $type === $connected_type && strpos( $body, 'googletagmanager.com/gtag/js?id=' . $current_code ) !== false ) {
		// In that case, we can safely deduct one from the total count
		-- $total_count;
	}

	if ( $total_count > $limit ) {
		// Translators: The placeholders are for making the "We have detected multiple tracking codes" text bold & adding a link to support.
		$message           = esc_html__( '%1$sWe have detected multiple tracking codes%2$s! You should remove non-ExactMetrics ones. If you need help finding them please %3$sread this article%4$s.', 'google-analytics-dashboard-for-wp' );
		$url               = exactmetrics_get_url( 'site-health', 'comingsoon', 'https://www.exactmetrics.com/docs/how-to-find-duplicate-google-analytics-tracking-codes-in-wordpress/' );
		$multiple_ua_error = sprintf(
			$message,
			'<b>',
			'</b>',
			'<a target="_blank" href="' . $url . '" target="_blank">',
			'</a>'
		);

		$errors[] = $multiple_ua_error;
	}

	return $errors;
}

/**
 * Make a request to the front page and check if the tracking code is present. Moved here from onboarding wizard
 * to be used in the site health check.
 *
 * @return array
 */
function exactmetrics_is_code_installed_frontend() {
	// Grab the front page html.
	$request = wp_remote_request(
		home_url(),
		array(
			'sslverify' => false,
		)
	);
	$errors  = array();

	$accepted_http_codes = array(
		200,
		503,
	);

	$response_code = wp_remote_retrieve_response_code( $request );

	if ( in_array( $response_code, $accepted_http_codes, true ) ) {
		$body = wp_remote_retrieve_body( $request );

		$errors = array_merge(
			exactmetrics_detect_tracking_code_error( $body ),
			exactmetrics_detect_tracking_code_error( $body, 'v4' )
		);
	}

	return $errors;
}

/**
 * Returns a HEX color to highlight menu items based on the admin color scheme.
 */
function exactmetrics_menu_highlight_color() {

	$color_scheme = get_user_option( 'admin_color' );
	$color        = '#1da867';
	if ( 'light' === $color_scheme || 'blue' === $color_scheme ) {
		$color = '#5f3ea7';
	}

	return $color;
}

/**
 * Track Pretty Links redirects with ExactMetrics.
 *
 * @param string $url The url to which users get redirected.
 */
function exactmetrics_custom_track_pretty_links_redirect( $url ) {
	if ( ! function_exists( 'exactmetrics_mp_track_event_call' ) && ! function_exists( 'exactmetrics_mp_collect_v4' ) ) {
		return;
	}

	// Track if it is a file.
	exactmetrics_track_pretty_links_file_download_redirect( $url );

	// Try to determine if click originated on the same site.
	$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
	if ( ! empty( $referer ) ) {
		$current_site_url    = get_bloginfo( 'url' );
		$current_site_parsed = wp_parse_url( $current_site_url );
		$parsed_referer      = wp_parse_url( $referer );
		if ( ! empty( $parsed_referer['host'] ) && ! empty( $current_site_parsed['host'] ) && $current_site_parsed['host'] === $parsed_referer['host'] ) {
			// Don't track clicks originating from same site as those are tracked with JS.
			return;
		}
	}
	// Check if this is an affiliate link and use the appropriate category.
	$ec            = 'outbound-link';
	$inbound_paths = exactmetrics_get_option( 'affiliate_links', array() );
	$path          = empty( $_SERVER['REQUEST_URI'] ) ? '' : $_SERVER['REQUEST_URI'];
	if ( ! empty( $inbound_paths ) && is_array( $inbound_paths ) && ! empty( $path ) ) {
		$found = false;
		foreach ( $inbound_paths as $inbound_path ) {
			if ( empty( $inbound_path['path'] ) ) {
				continue;
			}
			if ( 0 === strpos( $path, trim( $inbound_path['path'] ) ) ) {
				$label = ! empty( $inbound_path['label'] ) ? trim( $inbound_path['label'] ) : 'aff';
				$ec   .= '-' . $label;
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return;
		}
	} else {
		// no paths setup in ExactMetrics settings
		return;
	}

	if ( exactmetrics_get_ua_to_output() ) {
		$track_args = array(
			't'  => 'event',
			'ec' => $ec,
			'ea' => $url,
			'el' => 'external-redirect',
		);
		exactmetrics_mp_track_event_call( $track_args );
	}

	if ( exactmetrics_get_v4_id_to_output() ) {
		$url_components = parse_url( $url );
		$args           = array(
			'events' => array(
				array(
					'link_text'   => 'external-redirect',
					'link_url'    => $url,
					'link_domain' => $url_components['host'],
					'outbound'    => true,
				),
			),
		);

		if ( ! empty( $label ) ) {
			$args['events'][0]['affiliate_label']   = $label;
			$args['events'][0]['is_affiliate_link'] = true;
		}

		exactmetrics_mp_collect_v4( $args );
	}
}

add_action( 'prli_before_redirect', 'exactmetrics_custom_track_pretty_links_redirect' );

/**
 * Track Pretty Links file download redirects with ExactMetrics.
 *
 * @param string $url The url to which users get redirected.
 */
function exactmetrics_track_pretty_links_file_download_redirect( $url ) {
	$file_info = pathinfo( $url );

	// If no extension in URL.
	if ( ! isset( $file_info['extension'] ) ) {
		return;
	}

	if ( ! $file_info['extension'] ) {
		return;
	}

	// Get download extensions to track.
	$download_extensions = exactmetrics_get_option( 'extensions_of_files', '' );

	if ( ! $download_extensions ) {
		return;
	}

	$download_extensions = explode( ',', str_replace( '.', '', $download_extensions ) );

	if ( ! is_array( $download_extensions ) ) {
		$download_extensions = array( $download_extensions );
	}

	// If current URL extension is not in settings.
	if ( ! in_array( $file_info['extension'], $download_extensions ) ) {
		return;
	}

	$url_components = parse_url( $url );

	global $prli_link;
	$pretty_link = $prli_link->get_one_by( 'url', $url );

	$args = array(
		'events' => array(
			array(
				'name'   => 'file_download',
				'params' => array(
					'link_text'      => $pretty_link->name,
					'link_url'       => $url,
					'link_domain'    => $url_components['host'],
					'file_extension' => $file_info['extension'],
					'file_name'      => $file_info['basename'],
				)
			)
		),
	);

	exactmetrics_mp_collect_v4( $args );
}

/**
 * Get post type in admin side
 */
function exactmetrics_get_current_post_type() {
	global $post, $typenow, $current_screen;

	if ( $post && $post->post_type ) {
		return $post->post_type;
	} elseif ( $typenow ) {
		return $typenow;
	} elseif ( $current_screen && $current_screen->post_type ) {
		return $current_screen->post_type;
	} elseif ( isset( $_REQUEST['post_type'] ) ) {
		return sanitize_key( $_REQUEST['post_type'] );
	}

	return null;
}

/** Decode special characters, both alpha- (<) and numeric-based (').
 *
 * @param string $string Raw string to decode.
 *
 * @return string
 * @since 7.10.5
 */
function exactmetrics_decode_string( $string ) {

	if ( ! is_string( $string ) ) {
		return $string;
	}

	return wp_kses_decode_entities( html_entity_decode( $string, ENT_QUOTES ) );
}

add_filter( 'exactmetrics_email_message', 'exactmetrics_decode_string' );

/**
 * Sanitize a string, that can be a multiline.
 * If WP core `sanitize_textarea_field()` exists (after 4.7.0) - use it.
 * Otherwise - split onto separate lines, sanitize each one, merge again.
 *
 * @param string $string
 *
 * @return string If empty var is passed, or not a string - return unmodified. Otherwise - sanitize.
 * @since 7.10.5
 */
function exactmetrics_sanitize_textarea_field( $string ) {

	if ( empty( $string ) || ! is_string( $string ) ) {
		return $string;
	}

	if ( function_exists( 'sanitize_textarea_field' ) ) {
		$string = sanitize_textarea_field( $string );
	} else {
		$string = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $string ) ) );
	}

	return $string;
}

/**
 * Trim a sentence
 *
 * @param string $string
 * @param int    $count
 *
 * @return trimed sentence
 * @since 7.10.5
 */
function exactmetrics_trim_text( $text, $count ) {
	$text   = str_replace( '  ', ' ', $text );
	$string = explode( ' ', $text );
	$trimed = '';

	for ( $wordCounter = 0; $wordCounter <= $count; $wordCounter ++ ) {
		$trimed .= isset( $string[ $wordCounter ] ) ? $string[ $wordCounter ] : '';

		if ( $wordCounter < $count ) {
			$trimed .= ' ';
		} else {
			$trimed .= '...';
		}
	}

	$trimed = trim( $trimed );

	return $trimed;
}

/**
 * Add newly generated builder URL to PrettyLinks &
 * Clear localStorage key(ExactMetricsURL) after saving PrettyLink
 */
function exactmetrics_tools_copy_url_to_prettylinks() {
	global $pagenow;

	$post_type                 = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
	$exactmetrics_reference = isset( $_GET['exactmetrics_reference'] ) ? $_GET['exactmetrics_reference'] : '';

	if ( 'post-new.php' === $pagenow && 'pretty-link' === $post_type && 'url_builder' === $exactmetrics_reference ) { ?>
<script>
let targetTitleField = document.querySelector("input[name='post_title']");
let targetUrlField = document.querySelector("textarea[name='prli_url']");
let ExactMetricsUrl = JSON.parse(localStorage.getItem('ExactMetricsURL'));
if ('undefined' !== typeof targetUrlField && 'undefined' !== typeof ExactMetricsUrl) {
    let url = ExactMetricsUrl.value;
    let postTitle = '';
    let pathArray = url.split('?');
    if (pathArray.length <= 1) {
        pathArray = url.split('#');
    }
    let urlParams = new URLSearchParams(pathArray[1]);
    if (urlParams.has('utm_campaign')) {
        let campaign_name = urlParams.get('utm_campaign');
        postTitle += campaign_name;
    }
    if (urlParams.has('utm_medium')) {
        let campaign_medium = urlParams.get('utm_medium');
        postTitle += ` ${campaign_medium}`;
    }
    if (urlParams.has('utm_source')) {
        let campaign_source = urlParams.get('utm_source');
        postTitle += ` on ${campaign_source}`;
    }
    if (urlParams.has('utm_term')) {
        let campaign_term = urlParams.get('utm_term');
        postTitle += ` for ${campaign_term}`;
    }
    if (urlParams.has('utm_content')) {
        let campaign_content = urlParams.get('utm_content');
        postTitle += ` - ${campaign_content}`;
    }
    if ('undefined' !== typeof targetTitleField && postTitle) {
        targetTitleField.value = postTitle;
    }
    if (url) {
        targetUrlField.value = url;
    }
}
let form = document.getElementById('post');
form.addEventListener('submit', function() {
    localStorage.removeItem('ExactMetricsURL');
});
</script>
<?php
	}
}

add_action( 'admin_footer', 'exactmetrics_tools_copy_url_to_prettylinks' );

/**
 * When click on 'Create New Pretty Link" button(on tools/prettylinks-flow page) after installing & activating prettylinks plugin
 * it redirects to PrettyLinks welcome scree page instead of prettylinks add new page.
 * This function will skip that welcome screen
 */
function exactmetrics_skip_prettylinks_welcome_screen() {
	global $pagenow;

	$post_type                 = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
	$exactmetrics_reference = isset( $_GET['exactmetrics_reference'] ) ? $_GET['exactmetrics_reference'] : '';

	if ( 'post-new.php' === $pagenow && 'pretty-link' === $post_type && 'url_builder' === $exactmetrics_reference ) {
		$onboard = get_option( 'prli_onboard' );

		if ( $onboard == 'welcome' || $onboard == 'update' ) {
			update_option( 'exactmetrics_backup_prli_onboard_value', $onboard );
			delete_option( 'prli_onboard' );
		}
	}
}

add_action( 'wp_loaded', 'exactmetrics_skip_prettylinks_welcome_screen', 9 );

/**
 * Restore the `prli_onboard` value after creating a prettylinks with exactmetrics prettylinks flow
 * users will see the prettylinks welcome screen after fresh installation & creating prettylinks with exactmetrics prettylinks flow
 */
function exactmetrics_restore_prettylinks_onboard_value() {
	global $pagenow;

	$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

	if ( 'edit.php' === $pagenow && 'pretty-link' === $post_type ) {
		$onboard = get_option( 'exactmetrics_backup_prli_onboard_value' );

		if ( class_exists( 'PrliBaseController' ) && ( $onboard == 'welcome' || $onboard == 'update' ) ) {
			update_option( 'prli_onboard', $onboard );
			delete_option( 'exactmetrics_backup_prli_onboard_value' );
		}
	}
}

add_action( 'wp_loaded', 'exactmetrics_restore_prettylinks_onboard_value', 15 );

/**
 * Check WP version and include the compatible upgrader skin.
 *
 * @param bool $custom_upgrader If true it will include our custom upgrader, otherwise it will use the default WP one.
 */
function exactmetrics_require_upgrader( $custom_upgrader = true ) {

	global $wp_version;

	$base = ExactMetrics();

	if ( ! $custom_upgrader ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	// WP 5.3 changes the upgrader skin.
	if ( version_compare( $wp_version, '5.3', '<' ) ) {
		if ( $custom_upgrader ) {
			require_once plugin_dir_path( $base->file ) . 'includes/admin/licensing/plugin-upgrader.php';
		}
		require_once plugin_dir_path( $base->file ) . '/includes/admin/licensing/skin-legacy.php';
	} else {
		if ( $custom_upgrader ) {
			require_once plugin_dir_path( $base->file ) . 'includes/admin/licensing/plugin-upgrader.php';
		}
		require_once plugin_dir_path( $base->file ) . '/includes/admin/licensing/skin.php';
	}
}

/**
 * Load headline analyzer if wp version is higher than/equal to 5.4
 *
 * @return boolean
 * @since 7.12.3
 */
function exactmetrics_load_gutenberg_app() {
	global $wp_version;

	if ( version_compare( $wp_version, '5.4', '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Helper function for frontend script attributes
 *
 * @return string
 * @since 7.12.3
 */
function exactmetrics_get_frontend_analytics_script_atts() {
	$attr_string = '';

	$default_attributes = array(
		'data-cfasync'     => 'false',
		'data-wpfc-render' => 'false',
	);
	if ( ! current_theme_supports( 'html5', 'script' ) ) {
		$default_attributes['type'] = 'text/javascript';
	}

	$attributes = apply_filters( 'exactmetrics_tracking_analytics_script_attributes', $default_attributes );

	if ( ! empty( $attributes ) ) {
		foreach ( $attributes as $attr_name => $attr_value ) {
			if ( ! empty( $attr_name ) ) {
				$attr_string .= ' ' . sanitize_key( $attr_name ) . '="' . esc_attr( $attr_value ) . '"';
			} else {
				$attr_string .= ' ' . esc_attr( $attr_value );
			}
		}
	}

	return $attr_string;
}

/**
 * Helper function instead of wp_localize_script with our script tag attributes.
 *
 * @return string
 * @since 8.5.0
 */
function exactmetrics_localize_script( $handle, $object_name, $data, $priority = 100 ) {
	$theme_supports_html5 = current_theme_supports( 'html5', 'script' );
	$script_js            = ! $theme_supports_html5 ? "/* <![CDATA[ */\n" : '';
	$script_js           .= "var $object_name = " . wp_json_encode( $data ) . ';';
	$script_js           .= ! $theme_supports_html5 ? "/* ]]> */\n" : '';

	$script = sprintf(
		"<script%s id='%s-js-extra'>%s</script>\n",
		exactmetrics_get_frontend_analytics_script_atts(),
		esc_attr( $handle ),
		$script_js
	);

	add_filter(
		'script_loader_tag',
		function ( $tag, $current_handle ) use ( $handle, $script ) {
			if ( $current_handle !== $handle ) {
				return $tag;
			}

			return $tag . $script;
		},
		$priority,
		2
	);
}

/**
 * Get native english speaking countries
 *
 * @return array
 *
 * @since 7.12.3
 */
function exactmetrics_get_english_speaking_countries() {
	return array(
		'AG' => __( 'Antigua and Barbuda', 'google-analytics-dashboard-for-wp' ),
		'AU' => __( 'Australia', 'google-analytics-dashboard-for-wp' ),
		'BB' => __( 'Barbados', 'google-analytics-dashboard-for-wp' ),
		'BZ' => __( 'Belize', 'google-analytics-dashboard-for-wp' ),
		'BW' => __( 'Botswana', 'google-analytics-dashboard-for-wp' ),
		'BI' => __( 'Burundi', 'google-analytics-dashboard-for-wp' ),
		'CM' => __( 'Cameroon', 'google-analytics-dashboard-for-wp' ),
		'CA' => __( 'Canada', 'google-analytics-dashboard-for-wp' ),
		'DM' => __( 'Dominica', 'google-analytics-dashboard-for-wp' ),
		'FJ' => __( 'Fiji', 'google-analytics-dashboard-for-wp' ),
		'GD' => __( 'Grenada', 'google-analytics-dashboard-for-wp' ),
		'GY' => __( 'Guyana', 'google-analytics-dashboard-for-wp' ),
		'GM' => __( 'Gambia', 'google-analytics-dashboard-for-wp' ),
		'GH' => __( 'Ghana', 'google-analytics-dashboard-for-wp' ),
		'IE' => __( 'Ireland', 'google-analytics-dashboard-for-wp' ),
		'IN' => __( 'India', 'google-analytics-dashboard-for-wp' ),
		'JM' => __( 'Jamaica', 'google-analytics-dashboard-for-wp' ),
		'KE' => __( 'Kenya', 'google-analytics-dashboard-for-wp' ),
		'KI' => __( 'Kiribati', 'google-analytics-dashboard-for-wp' ),
		'LS' => __( 'Lesotho', 'google-analytics-dashboard-for-wp' ),
		'LR' => __( 'Liberia', 'google-analytics-dashboard-for-wp' ),
		'MW' => __( 'Malawi', 'google-analytics-dashboard-for-wp' ),
		'MT' => __( 'Malta', 'google-analytics-dashboard-for-wp' ),
		'MH' => __( 'Marshall Islands', 'google-analytics-dashboard-for-wp' ),
		'MU' => __( 'Mauritius', 'google-analytics-dashboard-for-wp' ),
		'FM' => __( 'Micronesia', 'google-analytics-dashboard-for-wp' ),
		'NZ' => __( 'New Zealand', 'google-analytics-dashboard-for-wp' ),
		'NA' => __( 'Namibia', 'google-analytics-dashboard-for-wp' ),
		'NR' => __( 'Nauru', 'google-analytics-dashboard-for-wp' ),
		'NG' => __( 'Nigeria', 'google-analytics-dashboard-for-wp' ),
		'PK' => __( 'Pakistan', 'google-analytics-dashboard-for-wp' ),
		'PW' => __( 'Palau', 'google-analytics-dashboard-for-wp' ),
		'PG' => __( 'Papua New Guinea', 'google-analytics-dashboard-for-wp' ),
		'PH' => __( 'Philippines', 'google-analytics-dashboard-for-wp' ),
		'RW' => __( 'Rwanda', 'google-analytics-dashboard-for-wp' ),
		'SG' => __( 'Singapore', 'google-analytics-dashboard-for-wp' ),
		'KN' => __( 'St Kitts and Nevis', 'google-analytics-dashboard-for-wp' ),
		'LC' => __( 'St Lucia', 'google-analytics-dashboard-for-wp' ),
		'VC' => __( 'St Vincent and the Grenadines', 'google-analytics-dashboard-for-wp' ),
		'SZ' => __( 'Swaziland', 'google-analytics-dashboard-for-wp' ),
		'WS' => __( 'Samoa', 'google-analytics-dashboard-for-wp' ),
		'SC' => __( 'Seychelles', 'google-analytics-dashboard-for-wp' ),
		'SL' => __( 'Sierra Leone', 'google-analytics-dashboard-for-wp' ),
		'SB' => __( 'Solomon Islands', 'google-analytics-dashboard-for-wp' ),
		'ZA' => __( 'South Africa', 'google-analytics-dashboard-for-wp' ),
		'SS' => __( 'South Sudan', 'google-analytics-dashboard-for-wp' ),
		'SD' => __( 'Sudan', 'google-analytics-dashboard-for-wp' ),
		'TT' => __( 'Trinidad and Tobago', 'google-analytics-dashboard-for-wp' ),
		'BS' => __( 'The Bahamas', 'google-analytics-dashboard-for-wp' ),
		'TZ' => __( 'Tanzania', 'google-analytics-dashboard-for-wp' ),
		'TO' => __( 'Tonga', 'google-analytics-dashboard-for-wp' ),
		'TV' => __( 'Tuvalu', 'google-analytics-dashboard-for-wp' ),
		'GB' => __( 'United Kingdom', 'google-analytics-dashboard-for-wp' ),
		'US' => __( 'United States of America', 'google-analytics-dashboard-for-wp' ),
		'UG' => __( 'Uganda', 'google-analytics-dashboard-for-wp' ),
		'VU' => __( 'Vanuatu', 'google-analytics-dashboard-for-wp' ),
		'ZM' => __( 'Zambia', 'google-analytics-dashboard-for-wp' ),
		'ZW' => __( 'Zimbabwe', 'google-analytics-dashboard-for-wp' ),
	);
}

/**
 * Helper function to check if the current user can install a plugin.
 *
 * @return bool
 */
function exactmetrics_can_install_plugins() {

	if ( ! current_user_can( 'install_plugins' ) ) {
		return false;
	}

	// Determine whether file modifications are allowed.
	if ( function_exists( 'wp_is_file_mod_allowed' ) && ! wp_is_file_mod_allowed( 'exactmetrics_can_install' ) ) {
		return false;
	}

	return true;
}

/**
 * Check if current date is between given dates. Date format: Y-m-d.
 *
 * @param string $start_date Start Date. Eg: 2021-01-01.
 * @param string $end_date End Date. Eg: 2021-01-14.
 *
 * @return bool
 * @since 7.13.2
 */
function exactmetrics_date_is_between( $start_date, $end_date ) {

	$current_date = current_time( 'Y-m-d' );

	$start_date = date( 'Y-m-d', strtotime( $start_date ) );
	$end_date   = date( 'Y-m-d', strtotime( $end_date ) );

	if ( ( $current_date >= $start_date ) && ( $current_date <= $end_date ) ) {
		return true;
	}

	return false;
}

/**
 * Check is All-In-One-Seo plugin is active or not.
 *
 * @return bool
 * @since 7.17.0
 */
function exactmetrics_is_aioseo_active() {

	if ( function_exists( 'aioseo' ) ) {
		return true;
	}

	return false;
}

/**
 * Return AIOSEO Dashboard URL if plugin is active.
 *
 * @return string
 * @since 7.17.0
 */
function exactmetrics_aioseo_dashboard_url() {
	$url = '';

	if ( function_exists( 'aioseo' ) ) {
		$url = is_multisite() ? network_admin_url( 'admin.php?page=aioseo' ) : admin_url( 'admin.php?page=aioseo' );
	}

	return $url;
}

/**
 * Check if AIOSEO Pro version is installed or not.
 *
 * @return bool
 * @since 7.17.10
 */
function exactmetrics_is_installed_aioseo_pro() {
	$installed_plugins = get_plugins();

	if ( array_key_exists( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php', $installed_plugins ) ) {
		return true;
	}

	return false;
}

/**
 * Check if Cookiebot plugin functionality active.
 *
 * @since 8.9.0
 *
 * @return bool
 */
function exactmetrics_is_cookiebot_active() {

	if ( function_exists( '\cybot\cookiebot\lib\cookiebot_active' ) ) {
		return \cybot\cookiebot\lib\cookiebot_active();
	}

	if ( function_exists( 'cookiebot_active' ) ) {
		return cookiebot_active();
	}

	return false;
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Retrieves the date, in localized format.
	 *
	 * This is a newer function, intended to replace `date_i18n()` without legacy quirks in it.
	 *
	 * Note that, unlike `date_i18n()`, this function accepts a true Unix timestamp, not summed
	 * with timezone offset.
	 *
	 * @since WP 5.3.0
	 *
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 *
	 * @param string       $format    PHP date format.
	 * @param int          $timestamp Optional. Unix timestamp. Defaults to current time.
	 * @param DateTimeZone $timezone  Optional. Timezone to output result in. Defaults to timezone
	 *                                from site settings.
	 * @return string|false The date, translated if locale specifies it. False on invalid timestamp input.
	 */
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		global $wp_locale;

		if ( null === $timestamp ) {
			$timestamp = time();
		} elseif ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		if ( ! $timezone ) {
			$timezone = wp_timezone();
		}

		$datetime = date_create( '@' . $timestamp );
		$datetime->setTimezone( $timezone );

		if ( empty( $wp_locale->month ) || empty( $wp_locale->weekday ) ) {
			$date = $datetime->format( $format );
		} else {
			// We need to unpack shorthand `r` format because it has parts that might be localized.
			$format = preg_replace( '/(?<!\\\\)r/', DATE_RFC2822, $format );

			$new_format    = '';
			$format_length = strlen( $format );
			$month         = $wp_locale->get_month( $datetime->format( 'm' ) );
			$weekday       = $wp_locale->get_weekday( $datetime->format( 'w' ) );

			for ( $i = 0; $i < $format_length; $i ++ ) {
				switch ( $format[ $i ] ) {
					case 'D':
						$new_format .= addcslashes( $wp_locale->get_weekday_abbrev( $weekday ), '\\A..Za..z' );
						break;
					case 'F':
						$new_format .= addcslashes( $month, '\\A..Za..z' );
						break;
					case 'l':
						$new_format .= addcslashes( $weekday, '\\A..Za..z' );
						break;
					case 'M':
						$new_format .= addcslashes( $wp_locale->get_month_abbrev( $month ), '\\A..Za..z' );
						break;
					case 'a':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'a' ) ), '\\A..Za..z' );
						break;
					case 'A':
						$new_format .= addcslashes( $wp_locale->get_meridiem( $datetime->format( 'A' ) ), '\\A..Za..z' );
						break;
					case '\\':
						$new_format .= $format[ $i ];

						// If character follows a slash, we add it without translating.
						if ( $i < $format_length ) {
							$new_format .= $format[ ++$i ];
						}
						break;
					default:
						$new_format .= $format[ $i ];
						break;
				}
			}

			$date = $datetime->format( $new_format );
			$date = wp_maybe_decline_date( $date, $format );
		}

		/**
		 * Filters the date formatted based on the locale.
		 *
		 * @since WP 5.3.0
		 *
		 * @param string       $date      Formatted date string.
		 * @param string       $format    Format to display the date.
		 * @param int          $timestamp Unix timestamp.
		 * @param DateTimeZone $timezone  Timezone.
		 */
		$date = apply_filters( 'wp_date', $date, $format, $timestamp, $timezone );

		return $date;
	}
}

if ( ! function_exists( 'wp_timezone_string' ) ) {
	/**
	 * Retrieves the timezone of the site as a string.
	 *
	 * Uses the `timezone_string` option to get a proper timezone name if available,
	 * otherwise falls back to a manual UTC ± offset.
	 *
	 * Example return values:
	 *
	 *  - 'Europe/Rome'
	 *  - 'America/North_Dakota/New_Salem'
	 *  - 'UTC'
	 *  - '-06:30'
	 *  - '+00:00'
	 *  - '+08:45'
	 *
	 * @since WP 5.3.0
	 *
	 * @return string PHP timezone name or a ±HH:MM offset.
	 */
	function wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Retrieves the timezone of the site as a `DateTimeZone` object.
	 *
	 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
	 *
	 * @since WP 5.3.0
	 *
	 * @return DateTimeZone Timezone object.
	 */
	function wp_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
}

if ( ! function_exists( 'current_datetime' ) ) {
	/**
	 * Retrieves the current time as an object using the site's timezone.
	 *
	 * @since WP 5.3.0
	 *
	 * @return DateTimeImmutable Date and time object.
	 */
	function current_datetime() {
		return new DateTimeImmutable( 'now', wp_timezone() );
	}
}
