<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Email_Func' ) ) :

	/**
	* Reminder email for product reviews
	*/
	class CR_Email_Func {

		public const TEMPLATE_REVIEW_REMINDER = 'email-review-reminder.php';
		public const TEMPLATE_REVIEW_DISCOUNT = 'email-review-discount.php';

		public static function get_order_items2( $order, $currency = '' ) {
			// read options
			$enabled_for = get_option( 'ivole_enable_for', 'all' );
			$enabled_categories = get_option( 'ivole_enabled_categories', array() );
			$categories_mapping = get_option( 'ivole_product_feed_categories', array() );
			$identifiers = get_option( 'ivole_product_feed_identifiers', array(
				'pid'   => '',
				'gtin'  => '',
				'mpn'   => '',
				'brand' => ''
			) );
			$static_brand = trim( get_option( 'ivole_google_brand_static', '' ) );
			// get order items
			$items_return = array();
			$items = $order->get_items();
			// check if taxes should be included in line items prices
			$tax_display = get_option( 'woocommerce_tax_display_cart' );
			$inc_tax = false;
			if ( 'excl' == $tax_display ) {
				$inc_tax = false;
			} else {
				$inc_tax = true;
			}
			//error_log( 'items' );
			//error_log( print_r( $items, true) );
			foreach ( $items as $item_id => $item ) {
				$categories = get_the_terms( $item['product_id'], 'product_cat' );
				// check if an item needs to be skipped because none of categories it belongs to has been enabled for reminders
				if( $enabled_for === 'categories' ) {
					$skip = true;
					foreach ( $categories as $category_id => $category ) {
						if( in_array( $category->term_id, $enabled_categories ) ) {
							$skip = false;
							break;
						}
					}
					if( $skip ) {
						continue;
					}
				}
				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) && $item['product_id'] ) {
					// create WC_Product to use its function for getting name of the product
					$prod_main_temp = new WC_Product( $item['product_id'] );
					if( $item['variation_id'] ) {
						$prod_temp = new WC_Product_Variation( $item['variation_id'] );
					} else {
						$prod_temp = new WC_Product( $item['product_id'] );
					}
					$image = wp_get_attachment_image_url( $prod_main_temp->get_image_id(), 'full', false );
					if( !$image ) {
						$image = '';
					}
					$q_name = $prod_main_temp->get_title();
					$price_per_item = floatval( $prod_temp->get_price() );
					if( function_exists( 'wc_get_price_including_tax' ) ) {
						if( $inc_tax ) {
							$price_per_item = floatval( wc_get_price_including_tax( $prod_temp ) );
						} else {
							$price_per_item = floatval( wc_get_price_excluding_tax( $prod_temp ) );
						}
					}

					// qTranslate integration
					$ivole_language = get_option( 'ivole_language' );
					if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && $ivole_language === 'QQ' ) {
						$q_name = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $q_name );
					}

					// WPML integration
					if ( has_filter( 'translate_object_id' ) && $ivole_language === 'WPML' ) {
						$wpml_current_language = get_post_meta( $order->get_id(), 'wpml_language', true );
						$translated_product_id = apply_filters( 'translate_object_id', $item['product_id'], 'product', true, $wpml_current_language );
						$q_name = get_the_title( $translated_product_id );
						// WPML Multi-currency
						if ( $currency ) {
							$price_per_item_changed = false;
							if( get_post_meta( $item['product_id'], '_wcml_custom_prices_status', true ) ) {
								$price_per_item_currency = get_post_meta( $item['product_id'], '_price_' . strtoupper( $currency ), true );
								if( $price_per_item_currency ) {
									$price_per_item = floatval( $price_per_item_currency );
									$price_per_item_changed = true;
								}
							} else {
								if( has_filter( 'wcml_raw_price_amount' ) ) {
									$price_per_item = apply_filters( 'wcml_raw_price_amount', floatval( $prod_temp->get_price() ), $currency );
									$price_per_item_changed = true;
								}
							}
							if( $price_per_item_changed ) {
								if( $inc_tax ) {
									$price_per_item = floatval( wc_get_price_including_tax( $prod_temp, array( 'qty' => 1, 'price' => $price_per_item ) ) );
								} else {
									$price_per_item = floatval( wc_get_price_excluding_tax( $prod_temp, array( 'qty' => 1, 'price' => $price_per_item ) ) );
								}
							}
						}
					}

					// Polylang integration
					if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_default_language' ) && $ivole_language === 'WPML' ) {
						$polylang_default_language = pll_default_language();
						$default_product_id = pll_get_post( $item['product_id'], $polylang_default_language );
						if( $default_product_id ) {
							$item['product_id'] = $default_product_id;
						}
					}

					$q_name = strip_tags( $q_name );

					// check if name of the product is empty (this could happen if a product was deleted)
					if( strlen( $q_name ) === 0 ) {
						continue;
					}

					// check if we have several variations of the same product in our order
					// review requests should be sent only once per each product
					$same_product_exists = false;
					for($i = 0; $i < sizeof( $items_return ); $i++ ) {
						if( isset( $items_return[$i]['id'] ) && $item['product_id'] === $items_return[$i]['id'] ) {
							$same_product_exists = true;
							$items_return[$i]['price'] += $order->get_line_total( $item, $inc_tax );
						}
					}
					if( !$same_product_exists ) {
						$tags = array();
						$cats = array();
						$idens = array();
						// save native WooCommerce categories associated with the product as tags
						// save mapping of native WooCommerce categories to Google taxonomy as categories
						foreach ($categories as $category) {
							$tags[] = $category->name;
							if( isset( $categories_mapping[$category->term_id] ) && $categories_mapping[$category->term_id] > 0 ) {
								$cats[] = $categories_mapping[$category->term_id];
							}
						}
						$tags = array_values( array_unique( $tags ) );
						$cats = array_values( array_unique( $cats ) );
						// read product identifiers (gtin, mpn, brand)
						if( is_array( $identifiers ) ) {
							if( isset( $identifiers['gtin'] ) ) {
								$idens['gtin'] = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['gtin'], $prod_main_temp );
							}
							if( isset( $identifiers['mpn'] ) ) {
								$idens['mpn'] = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['mpn'], $prod_main_temp );
							}
							if( isset( $identifiers['brand'] ) ) {
								$idens['brand'] = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['brand'], $prod_main_temp );
								if( !$idens['brand'] ) {
									$idens['brand'] = strval( $static_brand );
								}
							}
						}
						$items_return[] = array( 'id' => $item['product_id'], 'name' => $q_name, 'price' => $order->get_line_total( $item, $inc_tax ),
						'pricePerItem' => $price_per_item, 'image' => $image, 'tags' => $tags, 'categories' => $cats, 'identifiers' => $idens );
					}
				}
			}
			// check if free products should be excluded
			if( 'yes' == get_option( 'ivole_exclude_free_products', 'no' ) ) {
				$items_return_excl_free = array();
				foreach ($items_return as $item_return) {
					if( $item_return['price'] > 0 ) {
						$items_return_excl_free[] = $item_return;
					}
				}
				return $items_return_excl_free;
			}
			//error_log( print_r( $items_return, true) );
			return $items_return;
		}

		// replacement of the standard WooCommerce wc_price function
		// it is necessary to prevent WooCommerce Multilingual plugin from adding its filters and crashing
		public static function cr_price( $price, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'ex_tax_label'       => false,
					'currency'           => '',
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimals'           => wc_get_price_decimals(),
					'price_format'       => get_woocommerce_price_format(),
				)
			);

			$unformatted_price = $price;
			$negative          = $price < 0;
			$price             = floatval( $negative ? $price * -1 : $price );
			$price             = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

			if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
				$price = wc_trim_zeros( $price );
			}

			$formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="cr-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
			$return          = '<span class="cr-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';

			/**
			 * Filters the string of price markup.
			 *
			 * @param string $return            Price HTML markup.
			 * @param string $price             Formatted price.
			 * @param array  $args              Pass on the args.
			 * @param float  $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
			 */
			return apply_filters( 'cr_price', $return, $price, $args, $unformatted_price );
		}

		public static function cr_map_language( $language ) {
			$language = strtoupper( $language );
			switch( $language ) {
				case 'PT-PT':
					$language = 'PT';
					break;
				case 'PT-BR':
					$language = 'BR';
					break;
				default:
					break;
			}
			return $language;
		}

		public static function get_local_email_template( $data, $is_test ) {
			// create a local form
			$form = self::create_local_form( $data, $is_test );
			$cr_email_form_link = '';
			if( 0 === $form['code'] ) {
				$cr_email_form_link = $form['text'];
			} else {
				return array(
					'code' => 1,
					'template' => $form['text']
				);
			}
			// fetch a local email template
			$template = wc_locate_template(
				self::TEMPLATE_REVIEW_REMINDER,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$email_template = '';
			ob_start();
			$cr_email_heading = $data['email']['header'];
			$cr_email_body = $data['email']['body'];
			$cr_email_review_btn = __( 'Review', 'customer-reviews-woocommerce' );
			$cr_email_footer = $data['email']['footer'];
			$cr_email_color_bg = get_option( 'ivole_email_color_bg', '#0f9d58' );
			$cr_email_color_text = get_option( 'ivole_email_color_text', '#ffffff' );
			include( $template );
			$email_template = ob_get_clean();
			return array(
				'code' => 0,
				'template' => $email_template
			);
		}

		public static function get_local_email_template_coupon( $data, $is_test ) {
			// fetch a local email template
			$template = wc_locate_template(
				self::TEMPLATE_REVIEW_DISCOUNT,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$email_template = '';
			ob_start();
			$cr_email_heading = $data['email']['header'];
			$cr_email_body = $data['email']['body'];
			$cr_email_footer = $data['email']['footer'];
			$cr_email_color_bg = get_option( 'ivole_email_coupon_color_bg', '#0f9d58' );
			$cr_email_color_text = get_option( 'ivole_email_coupon_color_text', '#ffffff' );
			include( $template );
			$email_template = ob_get_clean();
			return $email_template;
		}

		private static function create_local_form( $data, $is_test ) {
			return CR_Local_Forms::save_form(
				$data['order']['id'],
				$data['customer']['firstname'] . ' ' . $data['customer']['lastname'],
				$data['email']['to'],
				$data['form']['header'],
				$data['form']['description'],
				$data['order']['items'],
				$is_test
			);
		}

		public static function send_email( $data, $is_test ) {
			$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );
			if( 'wp' === $mailer ) {
				$headers = ['Content-Type: text/html; charset=UTF-8'];
				$headers[] = 'From: ' . $data['email']['fromText'] . ' <' . $data['email']['from'] . '>';
				if( $data['email']['replyTo'] ) {
					$headers[] = 'Reply-To: ' . $data['email']['replyTo'];
				}
				$message = self::get_local_email_template( $data, $is_test );
				if( 0 === $message['code'] ) {
					$wpmail_result = wp_mail( $data['email']['to'], $data['email']['subject'], $message['template'], $headers );
					$result = json_encode( array( 'status' => 'OK' ) );
				} else {
					$result = json_encode( array( 'status' => 'Error', 'details' => $message['template'] ) );
				}
			} else {
				$api_url = 'https://api.cusrev.com/v1/production/review-reminder';
				if( $is_test ) {
					$api_url = 'https://api.cusrev.com/v1/production/test-email';
				}
				$data_string = json_encode( $data );
				//error_log( $data_string );
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $data_string ) )
				);
				$result = curl_exec( $ch );
			}
			return $result;
		}

		public static function send_email_coupon( $data, $is_test ) {
			$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );
			if( 'wp' === $mailer ) {
				$headers = ['Content-Type: text/html; charset=UTF-8'];
				$headers[] = 'From: ' . $data['email']['fromText'] . ' <' . $data['email']['from'] . '>';
				if( $data['email']['replyTo'] ) {
					$headers[] = 'Reply-To: ' . $data['email']['replyTo'];
				}
				if( !$is_test && $data['email']['bcc'] ) {
					$headers[] = 'Bcc: ' . $data['email']['replyTo'];
				}
				$message = self::get_local_email_template_coupon( $data, $is_test );
				$wpmail_result = wp_mail( $data['email']['to'], $data['email']['subject'], $message, $headers );
				$result = json_encode( array( 'status' => 'OK' ) );
			} else {
				$api_url = 'https://api.cusrev.com/v1/production/review-discount';
				if( $is_test ) {
					$api_url = 'https://api.cusrev.com/v1/production/test-email';
				}
				$data_string = json_encode( $data );
				//error_log( $data_string );
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $api_url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $data_string ) )
				);
				$result = curl_exec( $ch );
			}
			return $result;
		}

	}

endif;
