<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Email_Coupon' ) ) :

/**
 * Reminder email for product reviews
 */
class CR_Email_Coupon {

	public $id;
	public $to;
	public $heading;
	public $subject;
	public $template_html;
	public $from;
	public $from_name;
	public $bcc;
	public $replyto;
	public $language;
	public $footer;
	public $find = array();
	public $replace = array();
	/**
	 * Constructor.
	 */
	public function __construct( $order_id = 0 ) {
		$this->id               = 'ivole_review_coupon';
		$this->heading          = strval( get_option( 'ivole_email_heading_coupon', __( 'Thank You for Leaving a Review', 'customer-reviews-woocommerce' ) ) );
		$this->subject          = strval( get_option( 'ivole_email_subject_coupon', '[{site_title}] ' . __( 'Discount Coupon for You', 'customer-reviews-woocommerce' ) ) );
		$this->template_html    = Ivole_Email::plugin_path() . '/templates/email_coupon.php';
		$this->language					= get_option( 'ivole_language', 'EN' );
		$this->from							= get_option( 'ivole_email_from', '' );
		$this->from_name				= get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
		$this->replyto					= get_option( 'ivole_coupon_email_replyto', get_option( 'admin_email' ) );
		$this->footer						= get_option( 'ivole_email_footer', '' );

		$this->find['site-title'] = '{site_title}';
		$this->find['customer-first-name']  = '{customer_first_name}';
		$this->find['customer-last-name']  = '{customer_last_name}';
		$this->find['customer-name'] = '{customer_name}';
		$this->find['coupon-code'] = '{coupon_code}';
		$this->find['discount-amount'] = '{discount_amount}';
		$this->replace['site-title'] = Ivole_Email::get_blogname();

		//qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$this->heading = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->heading );
			$this->subject = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->subject );
			$this->from_name = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->from_name );
			$this->footer = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->footer );
			if( 'QQ' === $this->language ) {
				global $q_config;
				$this->language = strtoupper( $q_config['language'] );
			}
		}

		//WPML integration
		if ( has_filter( 'wpml_translate_single_string' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
			$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
			if ( $order_id ) {
				$wpml_current_language = get_post_meta( $order_id, 'wpml_language', true );
			}
			$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'ivole', 'ivole_email_heading_coupon', $wpml_current_language );
			$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'ivole', 'ivole_email_subject_coupon', $wpml_current_language );
			$this->from = apply_filters( 'wpml_translate_single_string', $this->from, 'ivole', 'ivole_email_from', $wpml_current_language );
			$this->from_name = apply_filters( 'wpml_translate_single_string', $this->from_name, 'ivole', 'ivole_email_from_name', $wpml_current_language );
			$this->replyto = apply_filters( 'wpml_translate_single_string', $this->replyto, 'ivole', 'ivole_email_replyto', $wpml_current_language );
			$this->footer = apply_filters( 'wpml_translate_single_string', $this->footer, 'ivole', 'ivole_email_footer', $wpml_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = Ivole_Email::get_blogname();
			}
			if ( 'WPML' === $this->language && $wpml_current_language ) {
				$this->language = strtoupper( $wpml_current_language );
			}
		}

		//Polylang integration
		if( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_language' ) && function_exists( 'pll_translate_string' ) ) {
			$polylang_current_language = pll_current_language();
			if( $order_id ) {
				$polylang_current_language = pll_get_post_language( $order_id );
			}
			$this->heading = pll_translate_string( $this->heading, $polylang_current_language );
			$this->subject = pll_translate_string( $this->subject, $polylang_current_language );
			$this->from = pll_translate_string( $this->from, $polylang_current_language );
			$this->from_name = pll_translate_string( $this->from_name, $polylang_current_language );
			$this->replyto = pll_translate_string( $this->replyto, $polylang_current_language );
			$this->footer = pll_translate_string( $this->footer, $polylang_current_language );
			if ( empty( $this->from_name ) ) {
				$this->from_name = Ivole_Email::get_blogname();
			}
			if ( 'WPML' === $this->language ) {
				$this->language = strtoupper( $polylang_current_language );
			}
		}

		//a safety check if some translation plugin removed language
		if ( empty( $this->language ) || 'WPML' === $this->language ) {
			$this->language = 'EN';
		}

		$this->footer = strval( $this->footer );
	}

	/**
	 * Trigger 2 - used for sending test emails only
	 */
	public function triggerTest( $order_id, $to = null, $coupon_code = "", $discount_amount = "" ) {
		if ( ! Ivole::is_curl_installed() ) {
			return array( 100, __( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ) );
		}
		$api_url = '';

		// check if Reply-To address needs to be added to email
		if( filter_var( $this->replyto, FILTER_VALIDATE_EMAIL ) ) {
			$this->replyto = $this->replyto;
		} else {
			$this->replyto = get_option( 'admin_email' );
		}

		// some dummy information for tests
		$this->to = $to;
		$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
		$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
		$this->replace['customer-name'] = __( 'Jane Doe', 'customer-reviews-woocommerce' );
		$this->replace['coupon-code'] = $coupon_code;
		$this->replace['discount-amount'] = ( $discount_amount == "" ) ? '10%' : $discount_amount;

		// check if email subject is not empty
		$email_subject = $this->replace_variables( $this->subject );
		if( 0 >= strlen( $email_subject ) ) {
			return 13;
		}

		$message = $this->get_content();
		$message = $this->replace_variables( $message );

		$data = array(
			'token' => '164592f60fbf658711d47b2f55a1bbba',
			'shop' => array( "name" => Ivole_Email::get_blogname(),
				'domain' => Ivole_Email::get_blogurl() ),
			'email' => array( 'to' => $to,
				'from' => strval( $this->from ),
				'fromText' => $this->from_name,
				'replyTo' => $this->replyto,
				'subject' => $email_subject,
				'header' => $this->replace_variables( $this->heading ),
				'body' => $message,
				'footer' => $this->replace_variables( $this->footer ) ),
			'customer' => array( 'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
				'lastname' => __( 'Doe', 'customer-reviews-woocommerce' ) ),
			'order' => array( 'id' => '12345',
				'date' => date_i18n( 'd.m.Y', time() ),
				'currency' => get_woocommerce_currency(),
				'items' => array( array( 'id' => 1,
						'name' => __( 'Item 1 Test', 'customer-reviews-woocommerce' ),
						'price' => 15,
						'image' => ''),
					array( 'id' => 2,
						'name' => __( 'Item 2 Test', 'customer-reviews-woocommerce' ),
						'price' => 150,
						'image' => '') ) ),
				'colors' => array(
						'email' => array(
							"bg" => get_option( 'ivole_email_coupon_color_bg', '#0f9d58' ),
							'text' => get_option( 'ivole_email_coupon_color_text', '#ffffff' )
						)
					),
			'language' => $this->language
		);
		$api_url = 'https://api.cusrev.com/v1/production/test-email';
		$license = get_option( 'ivole_license_key', '' );
		if( strlen( $license ) > 0 ) {
			$data['licenseKey'] = $license;
		}
		$result = CR_Email_Func::send_email_coupon( $data, true );
		if( false === $result ) {
			return array( 2, curl_error( $ch ) );
		}
		//error_log( $result );
		$result = json_decode( $result );
		if( isset( $result->status ) && $result->status === 'OK' ) {
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 * Generate a coupon for the given email
	 *
	 * @access public
	 * @return int|false id of generated coupon
	 */
	public function generate_coupon( $to, $review_id, $coupon ) {
		$prefix = $coupon['cr_coupon_prefix'];
		$unique_code = ( !empty( $to ) ) ? strtoupper( uniqid( substr( preg_replace( '/[^a-z0-9]/i', '', sanitize_title( $to ) ), 0, 5 ) ) ) : strtoupper( uniqid() );
		$unique_code = strtoupper( $prefix ) . $unique_code;
		$coupon_args = array(
			'post_title' 	=> $unique_code,
			'post_content' 	=> '',
			'post_status' 	=> 'publish',
			'post_author' 	=> 1,
			'post_type'     => 'shop_coupon'
		);
		$coupon_id = wp_insert_post( $coupon_args );
		if( $coupon_id > 0 ){
			$type = $coupon['cr_coupon__discount_type'];
			update_post_meta( $coupon_id, 'discount_type', $type );
			$amount = floatval( $coupon['cr_coupon__coupon_amount'] );
			update_post_meta( $coupon_id, 'coupon_amount', $amount );
			$individual_use = 'yes' === $coupon['cr_coupon__individual_use'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'individual_use', $individual_use );
			$product_ids = is_array( $coupon['cr_coupon__product_ids'] ) ? $coupon['cr_coupon__product_ids'] : array();
			$product_ids = implode( ",", $product_ids );
			update_post_meta( $coupon_id, 'product_ids', $product_ids );
			$exclude_product_ids = is_array( $coupon['cr_coupon__exclude_product_ids'] ) ? $coupon['cr_coupon__exclude_product_ids'] : array();
			$exclude_product_ids = implode( ",", $exclude_product_ids );
			update_post_meta( $coupon_id, 'exclude_product_ids', $exclude_product_ids );
			$usage_limit = 0 < intval( $coupon['cr_coupon__usage_limit'] ) ? intval( $coupon['cr_coupon__usage_limit'] ) : 0;
			update_post_meta( $coupon_id, 'usage_limit', $usage_limit );
			update_post_meta( $coupon_id, 'usage_limit_per_user', $usage_limit );
			$days = 0 < intval( $coupon['cr_coupon__expires_days'] ) ? intval( $coupon['cr_coupon__expires_days'] ) : 0;
			if( $days > 0 ) {
				$today = time();
				$expiry_date = date( 'Y-m-d', $today + 24 * 60 * 60 * $days );
				update_post_meta( $coupon_id, 'expiry_date', $expiry_date );
				$date_expires = strtotime( $expiry_date );
				update_post_meta( $coupon_id, 'date_expires', $date_expires );
			} else {
				update_post_meta( $coupon_id, 'expiry_date', NULL );
				update_post_meta( $coupon_id, 'date_expires', '' );
			}
			$coupon_sharing = 'yes' === $coupon['cr_coupon__sharing'] ? 'yes' : 'no';
			if( 'no' === $coupon_sharing ) {
				update_post_meta( $coupon_id, 'customer_email', array( $to ) );
			}
			$free_shipping = 'yes' === $coupon['cr_coupon__free_shipping'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'free_shipping', $free_shipping );

			$exclude_sale_items = 'yes' === $coupon['cr_coupon__exclude_sale_items'] ? 'yes' : 'no';
			update_post_meta( $coupon_id, 'exclude_sale_items', $exclude_sale_items );

			$product_categories = is_array( $coupon['cr_coupon__product_categories'] ) ? $coupon['cr_coupon__product_categories'] : array();
			update_post_meta( $coupon_id, 'product_categories', $product_categories );

			$exclude_product_categories = is_array( $coupon['cr_coupon__excluded_product_categories'] ) ? $coupon['cr_coupon__excluded_product_categories'] : array();
			update_post_meta( $coupon_id, 'exclude_product_categories', $exclude_product_categories );

			$minimum_amount =  0 < floatval( $coupon['cr_coupon__minimum_amount'] ) ? floatval( $coupon['cr_coupon__minimum_amount'] ) : 0;
			if( $minimum_amount > 0 ) {
				update_post_meta( $coupon_id, 'minimum_amount', $minimum_amount );
			} else {
				update_post_meta( $coupon_id, 'minimum_amount', '' );
			}

			$maximum_amount = 0 < floatval( $coupon['cr_coupon__maximum_amount'] ) ? floatval( $coupon['cr_coupon__maximum_amount'] ) : 0;
			if( $maximum_amount > 0 ) {
				update_post_meta( $coupon_id, 'maximum_amount', $maximum_amount );
			} else {
				update_post_meta( $coupon_id, 'maximum_amount', '' );
			}
			update_post_meta( $coupon_id, 'usage_count', 0 );
			update_post_meta( $coupon_id, 'generated_from_review_id', $review_id );
			update_post_meta( $coupon_id, '_ivole_auto_generated', 1 );
		}
		return $coupon_id;
	}
	/**
	 * Get content
	 *
	 * @access public
	 * @return string
	 */
	public function get_content() {
		ob_start();
		//$email_heading = $this->heading;
		$def_body = Ivole_Email::$default_body_coupon;
		$lang = $this->language;
		include( $this->template_html );
		return ob_get_clean();
	}

	public function replace_variables( $input ) {
		return str_replace( $this->find, $this->replace, __( $input ) );
	}

	public function get_last_order_with_product( $product_id, $email ) {
		global $wpdb;
		$statuses = array( 'processing', 'completed' );
		$result = $wpdb->get_col( "
				SELECT p.ID FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
				INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
				WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
				AND pm.meta_key IN ( '_billing_email' )
				AND im.meta_key IN ( '_product_id', '_variation_id' )
				AND im.meta_value IN ('" . $product_id . "')
				AND pm.meta_value IN ( '" . $email . "' )
		" );
		$result = array_map( 'absint', $result );
		if( count( $result ) > 0 ) {
			//error_log( 'ORDER ID: ' . print_r( max( $result ), true ) );
			return max( $result );
		} else {
			return -1;
		}
	}

}

endif;
