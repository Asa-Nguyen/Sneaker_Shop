<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Checkout' ) ) :

	class CR_Checkout {
		public $consent_text;

		public function __construct() {
			if( 'yes' === get_option( 'ivole_customer_consent', 'no' ) ) {
				$def_consumer_consent_text = '';
				if( 'yes' === get_option( 'ivole_coupon_enable', 'no' ) ) {
					$def_consumer_consent_text = __( 'Check here to receive an invitation from CR (an independent third-party organization) to review your order. Once the review is published, you will receive a coupon to use for your next purchase.', 'customer-reviews-woocommerce' );
				} else {
					$def_consumer_consent_text = __( 'Check here to receive an invitation from CR (an independent third-party organization) to review your order', 'customer-reviews-woocommerce' );
				}
				$this->consent_text = get_option( 'ivole_customer_consent_text', $def_consumer_consent_text );
				// WPML integration
				if ( has_filter( 'wpml_translate_single_string' ) && has_filter( 'wpml_current_language' ) ) {
					$wpml_current_language = apply_filters( 'wpml_current_language', NULL );
					$this->consent_text = apply_filters( 'wpml_translate_single_string', $this->consent_text, 'ivole', 'ivole_customer_consent_text', $wpml_current_language );
				}
				add_action( 'woocommerce_checkout_terms_and_conditions', array( $this, 'display_cr_checkbox' ), 40 );
				add_action('woocommerce_checkout_update_order_meta', array( $this, 'cr_checkbox_meta' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'cr_checkout_style' ) );
			}
		}

		public function display_cr_checkbox() {
			$output = '<p class="cr-customer-consent">';
			$output .= '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">';
			$output .= '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox cr-customer-consent-checkbox" name="cr_customer_consent" id="cr_customer_consent" />';
			$output .= '<span class="woocommerce-terms-and-conditions-checkbox-text">' . $this->consent_text . '</span>';
			$output .= '</label>';
			$output .= '<input type="hidden" name="cr_customer_consent_field" value="1" />';
			$output .= '</p>';
			echo apply_filters( 'cr_consent_checkbox', $output );
		}

		public function cr_checkbox_meta( $order_id ) {
			if( isset( $_POST['cr_customer_consent_field'] ) && $_POST['cr_customer_consent_field'] ) {
				if( isset( $_POST['cr_customer_consent'] ) && $_POST['cr_customer_consent'] ) {
					update_post_meta( $order_id, '_ivole_cr_consent', 'yes' );
				} else {
					update_post_meta( $order_id, '_ivole_cr_consent', 'no' );
				}
			}
		}

		public function cr_checkout_style() {
			if( is_checkout() ) {
				$assets_version = Ivole::CR_VERSION;
				wp_register_style( 'ivole-frontend-css', plugins_url( '/css/frontend.css', dirname( dirname( __FILE__ ) ) ), array(), $assets_version, 'all' );
				wp_enqueue_style( 'ivole-frontend-css' );
			}
		}
	}

endif;
