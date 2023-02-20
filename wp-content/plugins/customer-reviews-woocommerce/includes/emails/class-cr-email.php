<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Email' ) ) :

/**
 * Reminder email for product reviews
 */
class Ivole_Email {

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
	public static $default_body = "Hi {customer_first_name},\n\nThank you for shopping with us!\n\nWe would love if you could help us and other customers by reviewing products that you recently purchased in order #{order_id}. It only takes a minute and it would really help others. Click the button below and leave your review!\n\nBest wishes,\n{site_title}";
	public static $default_body_coupon = "Hi {customer_first_name},\n\nThank you for reviewing your order!\n\nAs a token of appreciation, we’d like to offer you a discount coupon for your next purchases in our shop. Please apply the following coupon code during checkout to receive {discount_amount} discount.\n\n<strong>{coupon_code}</strong>\n\nBest wishes,\n{site_title}";
	/**
	 * Constructor.
	 */
	public function __construct( $order_id = 0 ) {
		$this->id               = 'ivole_reminder';
		$this->heading          = strval( get_option( 'ivole_email_heading', __( 'How did we do?', 'customer-reviews-woocommerce' ) ) );
		$this->subject          = strval( get_option( 'ivole_email_subject', '[{site_title}] ' . __( 'Review Your Experience with Us', 'customer-reviews-woocommerce' ) ) );
		$this->form_header      = strval( get_option( 'ivole_form_header', __( 'How did we do?', 'customer-reviews-woocommerce' ) ) );
		$this->form_body        = strval( get_option( 'ivole_form_body', __( 'Please review your experience with products and services that you purchased at {site_title}.', 'customer-reviews-woocommerce' ) ) );
		$this->template_html    = Ivole_Email::plugin_path() . '/templates/email.php';
		$this->language					= get_option( 'ivole_language', 'EN' );
		$this->from							= get_option( 'ivole_email_from', '' );
		$this->from_name				= get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
		$this->replyto					= get_option( 'ivole_email_replyto', get_option( 'admin_email' ) );
		$this->footer						= get_option( 'ivole_email_footer', '' );

		$this->find['site-title'] = '{site_title}';
		$this->replace['site-title'] = Ivole_Email::get_blogname();

		//qTranslate integration
		if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$this->heading = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->heading );
			$this->subject = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->subject );
			$this->form_header = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_header );
			$this->form_body = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $this->form_body );
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
			$this->heading = apply_filters( 'wpml_translate_single_string', $this->heading, 'ivole', 'ivole_email_heading', $wpml_current_language );
			$this->subject = apply_filters( 'wpml_translate_single_string', $this->subject, 'ivole', 'ivole_email_subject', $wpml_current_language );
			$this->form_header = apply_filters( 'wpml_translate_single_string', $this->form_header, 'ivole', 'ivole_form_header', $wpml_current_language );
			$this->form_body = apply_filters( 'wpml_translate_single_string', $this->form_body, 'ivole', 'ivole_form_body', $wpml_current_language );
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
			$this->form_header = pll_translate_string( $this->form_header, $polylang_current_language );
			$this->form_body = pll_translate_string( $this->form_body, $polylang_current_language );
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

		// map language codes returned by translation plugins that include '-' like PT-PT
		$this->language = CR_Email_Func::cr_map_language( $this->language );

		$this->footer = strval( $this->footer );
	}

	/**
	 * Trigger version 2.
	 */
	public function trigger2( $order_id, $to = null, $schedule = false ) {
		if ( ! Ivole::is_curl_installed() ) {
			return array( 100, __( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ) );
		}
		$this->find['customer-first-name']  = '{customer_first_name}';
		$this->find['customer-last-name']  = '{customer_last_name}';
		$this->find['customer-name'] = '{customer_name}';
		$this->find['order-id'] = '{order_id}';
		$this->find['order-date'] = '{order_date}';
		$this->find['list-products'] = '{list_products}';

		// check if Reply-To address needs to be added to email
		if( filter_var( $this->replyto, FILTER_VALIDATE_EMAIL ) ) {
			$this->replyto = $this->replyto;
		} else {
			$this->replyto = get_option( 'admin_email' );
		}

		$comment_required = get_option( 'ivole_form_comment_required', 'no' );
		if( 'no' === $comment_required ) {
			$comment_required = 0;
		} else {
			$comment_required = 1;
		}

		$shop_rating = 'yes' === get_option( 'ivole_form_shop_rating', 'no' ) ? true : false;
		$allowMedia = 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ? true : false;
		$ratingBar = 'star' === get_option( 'ivole_form_rating_bar', 'smiley' ) ? 'star' : 'smiley';
		$geolocation = 'yes' === get_option( 'ivole_form_geolocation', 'no' ) ? true : false;

		if ( $order_id ) {
			//check if Limit Number of Reviews option is used
			if( 'yes' === get_option( 'ivole_limit_reminders', 'yes' ) ) {
				//check how many reminders have already been sent for this order (if any)
				$reviews = get_post_meta( $order_id, '_ivole_review_reminder', true );
				if( $reviews >= 1 ) {
					//if more than one, then we cannot send an email
					return 3;
				}
			}
			//check if registered customers option is used
			$registered_customers = false;
			if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
				$registered_customers = true;
			}
			$order = wc_get_order( $order_id );
			if( !$order  ) {
				//if no order exists with the provided $order_id, then we cannot send an email
				return array( 15, sprintf( __( 'Error: the order %s does not exist anymore.', 'customer-reviews-woocommerce' ), $order_id ) );
			}

			//check customer roles
			$for_role = get_option( 'ivole_enable_for_role', 'all' );
			$enabled_roles = get_option( 'ivole_enabled_roles', array() );
			$for_guests = 'no' === get_option( 'ivole_enable_for_guests', 'yes' ) ? false : true;

			// check if taxes should be included in list_products variable
			$tax_displ = get_option( 'woocommerce_tax_display_cart' );
			$incl_tax = false;
			if ( 'excl' === $tax_displ ) {
				$incl_tax = false;
			} else {
				$incl_tax = true;
			}

			//check if free products should be excluded from list_products variable
			$excl_free = false;
			if( 'yes' == get_option( 'ivole_exclude_free_products', 'no' ) ) {
				$excl_free = true;
			}

			// check if we are dealing with old WooCommerce version
			$customer_first_name = '';
			$customer_last_name = '';
			$order_date = '';
			$order_currency = '';
			$order_items = array();
			$user = NULL;
			$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
			$temp_shipping_country = '';
			if( method_exists( $order, 'get_billing_email' ) ) {
				// Woocommerce version 3.0 or later
				$user = $order->get_user();
				if( $registered_customers ) {
					if( $user ) {
						$this->to = $user->user_email;
					} else {
						$this->to = $order->get_billing_email();
					}
				} else {
					$this->to = $order->get_billing_email();
				}
				$customer_first_name = $order->get_billing_first_name();
				$customer_last_name = $order->get_billing_last_name();
				$this->replace['customer-first-name'] = $customer_first_name;
				$this->replace['customer-last-name'] = $customer_last_name;
				$this->replace['customer-name'] = $customer_first_name . ' ' . $customer_last_name;
				$this->replace['order-id'] = $order->get_order_number();
				$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) );
				$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
				$order_currency = $order->get_currency();
				$temp_shipping_country = $order->get_shipping_country();
				if( strlen( $temp_shipping_country ) > 0 ) {
					$shipping_country = $temp_shipping_country;
				}

				$price_args = array( 'currency' => $order_currency );
				$list_products = '';
				foreach ( $order->get_items() as $order_item ) {
					if( $excl_free && 0 >= $order->get_line_total( $order_item, $incl_tax ) ) {
						continue;
					}
					$list_products .= $order_item->get_name() . ' / ' . CR_Email_Func::cr_price( $order->get_line_total( $order_item, $incl_tax ), $price_args ) . '<br/>';
				}
				$this->replace['list-products'] = $list_products;
			} else {
				// Woocommerce before version 3.0
				$user_id = get_post_meta( $order_id, '_customer_user', true );
				if( $user_id ) {
					$user = get_user_by( 'id', $user_id );
				}
				if( $registered_customers ) {
					if( $user ) {
						$this->to = $user->user_email;
					} else {
						$this->to = get_post_meta( $order_id, '_billing_email', true );
					}
				} else {
					$this->to = get_post_meta( $order_id, '_billing_email', true );
				}
				$customer_first_name = get_post_meta( $order_id, '_billing_first_name', true );
				$customer_last_name = get_post_meta( $order_id, '_billing_last_name', true );
				$this->replace['customer-first-name'] = $customer_first_name;
				$this->replace['customer-last-name'] = $customer_last_name;
				$this->replace['customer-name'] = $customer_first_name . ' ' . $customer_last_name;
				//$this->replace['order-id'] = $order_id;
				$this->replace['order-id'] = $order->get_order_number();
				$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $order->order_date ) );
				$order_date = date_i18n( 'd.m.Y', strtotime( $order->order_date ) );
				$order_currency = $order->get_order_currency();
				$temp_shipping_country = get_post_meta( $order_id, '_shipping_country', true );
				if( strlen( $temp_shipping_country ) > 0 ) {
					$shipping_country = $temp_shipping_country;
				}

				$price_args = array( 'currency' => $order_currency );
				$list_products = '';
				foreach ( $order->get_items() as $order_item ) {
					if( $excl_free && 0 >= $order_item['line_total'] ) {
						continue;
					}
					$list_products .= $order_item['name'] . ' / ' . CR_Email_Func::cr_price( $order_item['line_total'], $price_args ) . '<br/>';
				}
				$this->replace['list-products'] = $list_products;
			}
			if( isset( $user ) && !empty( $user ) ) {
				// check customer roles if there is a restriction to which roles reminders should be sent
				if( 'roles' === $for_role ) {
					$roles = $user->roles;
					$intersection = array_intersect( $enabled_roles, $roles );
					if( count( $intersection ) < 1 ){
							//customer has no allowed roles
							return 5;
					}
				}
			} else {
				// check if sending of review reminders is enabled for guests
				if( ! $for_guests ) {
					return 11;
				}
			}
			// check if BCC address needs to be added to email
			$bcc_address = get_option( 'ivole_email_bcc', '' );
			if( filter_var( $bcc_address, FILTER_VALIDATE_EMAIL ) ) {
				$this->bcc = $bcc_address;
			} else {
				$this->bcc = '';
			}
			// check if customer email is valid
			if( !filter_var( $this->to, FILTER_VALIDATE_EMAIL ) ) {
				return 10;
			}
			// check if email subject is not empty
			$email_subject = $this->replace_variables( $this->subject );
			if( 0 >= strlen( $email_subject ) ) {
				return 13;
			}

			$message = $this->get_content();
			$message = $this->replace_variables( $message );

			$secret_key = get_post_meta( $order_id, 'ivole_secret_key', true );
			if( !$secret_key ) {
				//generate and save a secret key for callback to DB
				$secret_key = bin2hex(openssl_random_pseudo_bytes(16));
				if( false === update_post_meta( $order_id, 'ivole_secret_key', $secret_key ) ) {
					//could not save the secret key to DB, so a customer will not be able to submit the review form
					return 6;
				}
			}

			// WPML integration
			$ivole_language = get_option( 'ivole_language' );
			if ( has_filter( 'wpml_default_language' ) && $ivole_language === 'WPML' ) {
				$site_default_lang = apply_filters('wpml_default_language', NULL );
				$site_current_lang = apply_filters( 'wpml_current_language', NULL );
				do_action( 'wpml_switch_language', $site_default_lang );
				$callback_url = get_rest_url( null, '/ivole/v1/review' );
				do_action( 'wpml_switch_language', $site_current_lang );
			} else {
				$callback_url = get_rest_url( null, '/ivole/v1/review' );
			}

			$data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array( "name" => Ivole_Email::get_blogname(),
			 		'domain' => Ivole_Email::get_blogurl(),
				 	'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) ) ),
				'email' => array( 'to' => $this->to,
					'from' => strval( $this->from ),
					'fromText' => $this->from_name,
					'bcc' => $this->bcc,
					'replyTo' => $this->replyto,
			 		'subject' => $email_subject,
					'header' => $this->replace_variables( $this->heading ),
					'body' => $message,
				 	'footer' => $this->replace_variables( $this->footer )
				),
				'customer' => array( 'firstname' => $customer_first_name,
					'lastname' => $customer_last_name ),
				'order' => array( 'id' => strval( $order_id ),
			 		'date' => $order_date,
					'currency' => $order_currency,
					'country' => $shipping_country,
				 	'items' => CR_Email_Func::get_order_items2( $order, $order_currency ) ),
				'callback' => array( //'url' => get_option( 'home' ) . '/wp-json/ivole/v1/review',
					'url' => $callback_url,
					'key' => $secret_key ),
				'form' => array('header' => $this->replace_variables( $this->form_header ),
					'description' => $this->replace_variables( $this->form_body ),
				 	'commentRequired' => $comment_required,
				 	'allowMedia' => $allowMedia,
				 	'shopRating' => $shop_rating,
				 	'ratingBar' => $ratingBar,
				 	'geoLocation' => $geolocation ),
				'colors' => array(
					'form' => array(
						'bg' => get_option( 'ivole_form_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_form_color_text', '#ffffff' ),
						'el' => get_option( 'ivole_form_color_el', '#1AB394' )
					),
					'email' => array(
						'bg' => get_option( 'ivole_email_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_email_color_text', '#ffffff' )
					)
				),
				'language' => $this->language,
				'schedule' => $schedule
			);
			//check that array of items is not empty
			if( 1 > count( $data['order']['items'] ) ) {
				return array( 4, __( 'Error: the order does not contain any products for which review reminders are enabled in the settings.', 'customer-reviews-woocommerce' ) );
			}
			$is_test = false;
		} else {
			// no order number means this is a test and we should provide some dummy information
			$this->replace['customer-first-name'] = __( 'Jane', 'customer-reviews-woocommerce' );
			$this->replace['customer-last-name'] = __( 'Doe', 'customer-reviews-woocommerce' );
			$this->replace['customer-name'] = __( 'Jane Doe', 'customer-reviews-woocommerce' );
			$this->replace['order-id'] = 12345;
			$this->replace['order-date'] = date_i18n( wc_date_format(), time() );
			if( 0 >= strlen( $this->replace['order-date'] ) ) {
				$this->replace['order-date'] = date_i18n( 'F j, Y', time() );
			}
			$this->replace['list-products'] = sprintf(
				'%s / %s<br/>%s / %s<br/>',
				__( 'Item 1 Test', 'customer-reviews-woocommerce' ),
				CR_Email_Func::cr_price( 15 ),
				__( 'Item 2 Test', 'customer-reviews-woocommerce' ),
				CR_Email_Func::cr_price( 150 )
			);

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
					'footer' => $this->replace_variables( $this->footer )
				),
				'customer' => array( 'firstname' => __( 'Jane', 'customer-reviews-woocommerce' ),
					'lastname' => __( 'Doe', 'customer-reviews-woocommerce' ) ),
				'order' => array( 'id' => '12345',
					'date' => date_i18n( 'd.m.Y', time() ),
					'currency' => get_woocommerce_currency(),
					'items' => array( array( 'id' => 1,
							'name' => __( 'Item 1 Test', 'customer-reviews-woocommerce' ),
							'price' => 15,
							'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-1.jpeg'
						),
						array( 'id' => 2,
							'name' => __( 'Item 2 Test', 'customer-reviews-woocommerce' ),
							'price' => 150,
							'image' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'img/test-product-2.jpeg'
						)
					)
				),
				'form' => array( 'header' => $this->replace_variables( $this->form_header ),
					'description' => $this->replace_variables( $this->form_body ),
					'commentRequired' => $comment_required,
					'allowMedia' => $allowMedia,
				 	'shopRating' => $shop_rating,
				 	'ratingBar' => $ratingBar,
				 	'geoLocation' => $geolocation ),
				'colors' => array(
					'form' => array(
						'bg' => get_option( 'ivole_form_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_form_color_text', '#ffffff' ),
						'el' => get_option( 'ivole_form_color_el', '#1AB394' )
					),
					'email' => array(
						'bg' => get_option( 'ivole_email_color_bg', '#0f9d58' ),
						'text' => get_option( 'ivole_email_color_text', '#ffffff' )
					)
				),
				'language' => $this->language
			);
			$is_test = true;
		}
		$license = get_option( 'ivole_license_key', '' );
		if( strlen( $license ) > 0 ) {
			$data['licenseKey'] = $license;
		}
		$result = CR_Email_Func::send_email( $data, $is_test );
		if( false === $result ) {
			return array( 2, curl_error( $ch ) );
		}
		//error_log( $result );
		$result = json_decode( $result );
		if( isset( $result->status ) && $result->status === 'OK' ) {
			//update count of review reminders sent in order meta
			if( $order_id ) {
				if( $schedule ) {
					//CR Cron
					update_post_meta( $order_id, '_ivole_cr_cron', 1 );
				} else {
					//WP Cron
					$count = get_post_meta( $order_id, '_ivole_review_reminder', true );
					$new_count = 0;
					if( '' === $count ) {
						$new_count = 1;
					} else {
						$count = intval( $count );
						$new_count = $count + 1;
					}
					update_post_meta( $order_id, '_ivole_review_reminder', $new_count );
				}
			}
			return 0;
		} elseif( isset( $result->status ) && $result->status === 'Error' ) {
			if( isset( $result->details ) && 0 === strcmp( 'Too many review invitations for a single order', $result->details ) ) {
				//we shouldn't send one than more reminder per order because customers will be annoyed
				return array( 7, __( 'Error: only one review invitation per order is allowed.', 'customer-reviews-woocommerce' ) . ' <a href="https://cusrev.freshdesk.com/support/solutions/articles/43000511299-error-only-one-review-invitation-per-order-is-allowed" target="_blank" rel="noopener noreferrer">' . __( 'View additional information', 'customer-reviews-woocommerce' ) . '</a>.' );
			} elseif( isset( $result->details ) && 0 === strcmp( 'All products were reviewed by this customer', $result->details ) ) {
				return array( 9, __( 'Error: the customer has already reviewed all products from this order.', 'customer-reviews-woocommerce' ) );
			} elseif( isset( $result->details ) && ( 0 === strcmp( 'Unable to send reminder to specified email', $result->details ) || 0 === strcmp( 'Customer has unsubscribed from emails', $result->details ) ) ) {
				return array( 12, __( 'Error: the customer has unsubscribed from emails.', 'customer-reviews-woocommerce' ) );
			} elseif( isset( $result->details ) && 0 === strcmp( 'The customer already reviewed shop or products in the past', $result->details ) ) {
				return array( 14, __( 'Error: the customer has already left a review for a different order in the past.', 'customer-reviews-woocommerce' ) );
			} elseif( isset( $result->details ) ) {
				return array( 16, $result->details );
			} else {
				return 8;
			}
		} else {
			//error_log( print_r( $result, true) );
			return 1;
		}
	}

	public function get_content() {
		ob_start();
		$def_body = Ivole_Email::$default_body;
		$lang = $this->language;
		include( $this->template_html );
		return ob_get_clean();
	}

	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) );
	}

	public function replace_variables( $input ) {
		return str_replace( $this->find, $this->replace, $input );
	}

	public static function get_blogname() {
		$blog_name = get_option( 'ivole_shop_name', get_option( 'blogname' ) );
		if( !$blog_name ) {
			$blog_name = get_option( 'blogname' );
			if( !$blog_name ) {
				$blog_name = Ivole_Email::get_blogurl();
			}
		}
		return wp_specialchars_decode( $blog_name, ENT_QUOTES );
	}

	public static function get_blogurl() {
		$temp = get_option( 'home' );
		$disallowed = array('http://', 'https://');
		foreach($disallowed as $d) {
			if(strpos($temp, $d) === 0) {
				return str_replace($d, '', $temp);
			}
		}
		return $temp;
	}

	public static function get_blogdomain() {
		$temp = get_option( 'home' );
		$temp = parse_url( $temp, PHP_URL_HOST );
		if( !$temp ) {
			$temp = '';
		}
		return $temp;
	}

}

endif;
