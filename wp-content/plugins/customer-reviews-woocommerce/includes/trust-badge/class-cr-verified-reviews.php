<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Verified_Reviews' ) ) :

	class CR_Verified_Reviews {
		public function __construct() {
		}

		public function check_status() {
			if ( ! Ivole::is_curl_installed() ) {
				update_option( 'ivole_reviews_verified', 'no' );
				return 1;
			}
			$data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array(
					'domain' => Ivole_Email::get_blogurl(),
					'name' => Ivole_Email::get_blogname()
				),
				'action' => 'status'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
			$data_string = json_encode($data);
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $data_string ) )
			);
			$result = curl_exec( $ch );
			if( false === $result ) {
				return 1;
			}
			$result = json_decode( $result );
			if( isset( $result->ageRestriction ) && $result->ageRestriction ) {
				update_option( 'ivole_age_restriction', 'yes' );
			} else {
				update_option( 'ivole_age_restriction', 'no' );
			}
			if( isset( $result->status ) && 'enabled' === $result->status ) {
				return 0;
			} else {
				update_option( 'ivole_reviews_verified', 'no' );
				return 1;
			}
		}

		public function enable( $reviewsUrl, $ageRestriction ) {
			if( strlen( $reviewsUrl ) === 0 ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error: \'Verified Reviews Page\' cannot be empty.', 'customer-reviews-woocommerce' ) );
				return 1;
			}
			if( ! Ivole::is_curl_installed() ) {
				WC_Admin_Settings::add_error( __( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ) );
				return 1;
			}
			$data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array(
					'domain' => Ivole_Email::get_blogurl(),
					'name' => Ivole_Email::get_blogname(),
					'reviewsUrl' => $reviewsUrl,
					'ageRestriction' => $ageRestriction
				),
				'action' => 'enable'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
			$data_string = json_encode($data);
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $data_string ) )
			);
			$result = curl_exec( $ch );
			if( false === $result ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error #98. ' . curl_error( $ch ), 'customer-reviews-woocommerce' ) );
				return 1;
			}
			$result = json_decode( $result );
			if( isset( $result->status ) && 'enabled' === $result->status ) {
				WC_Admin_Settings::add_message( __( 'Trust badges have been successfully activated.', 'customer-reviews-woocommerce' ) );
				return 0;
			} elseif( isset( $result->error ) && 'Duplicate reviews url' === $result->error ) {
				WC_Admin_Settings::add_error( sprintf( __( 'Trust badges activation error: \'%s\' is already in use. Please enter a different page name.', 'customer-reviews-woocommerce' ), $reviewsUrl ) );
				return 1;
			} elseif( isset( $result->error ) && 'Wrong reviews url' === $result->error ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error: page name contains unsupported symbols. Only latin characters (a-z), numbers (0-9), and . symbol are allowed.', 'customer-reviews-woocommerce' ) );
				return 1;
			} elseif( isset( $result->details ) && 'The store page is blocked' === $result->details ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error: the page is blocked, please contact CusRev support for assistance.', 'customer-reviews-woocommerce' ) );
				return 1;
			} else {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error #99.', 'customer-reviews-woocommerce' ) );
				return 1;
			}
		}

		public function disable( $ageRestriction ) {
			if( ! Ivole::is_curl_installed() ) {
				if( class_exists( 'WC_Admin_Settings' ) ) {
					WC_Admin_Settings::add_error( __( 'Error: cURL library is missing on the server.', 'customer-reviews-woocommerce' ) );
				}
				return 1;
			}
			$data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array(
					'domain' => Ivole_Email::get_blogurl(),
					'name' => Ivole_Email::get_blogname(),
					'ageRestriction' => $ageRestriction
				),
				'action' => 'disable'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
			$data_string = json_encode($data);
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $data_string ) )
			);
			$result = curl_exec( $ch );
			if( false === $result ) {
				if( class_exists( 'WC_Admin_Settings' ) ) {
					WC_Admin_Settings::add_error( __( 'Trust badges deactivation error #98. Please try again.', 'customer-reviews-woocommerce' ) );
				}
				return 1;
			}
			$result = json_decode( $result );
			if( isset( $result->status ) && 'disabled' === $result->status ) {
				if( class_exists( 'WC_Admin_Settings' ) ) {
					WC_Admin_Settings::add_message( __( 'Trust badges have been successfully deactivated.', 'customer-reviews-woocommerce' ) );
				}
				return 0;
			} elseif( isset( $result->details ) && 'The store page is blocked' === $result->details ) {
				WC_Admin_Settings::add_error( __( 'Trust badges deactivation error: the page is blocked, please contact CusRev support for assistance.', 'customer-reviews-woocommerce' ) );
				return 1;
			} else {
				if( class_exists( 'WC_Admin_Settings' ) ) {
					WC_Admin_Settings::add_error( __( 'Trust badges deactivation error #99.', 'customer-reviews-woocommerce' ) );
				}
				return 1;
			}
		}

	}

endif;
