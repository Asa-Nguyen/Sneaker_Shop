<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( __DIR__ . '/includes/emails/class-cr-email-func.php' );
require_once( __DIR__ . '/includes/emails/class-cr-sender.php' );
require_once( __DIR__ . '/includes/emails/class-cr-email.php' );
require_once( __DIR__ . '/includes/emails/class-cr-email-coupon.php' );
require_once('class-ivole-admin.php');
require_once('class-cr-reviews.php');
require_once('class-ivole-endpoint.php');
require_once('class-ivole-endpoint-replies.php');
require_once('class-cr-referrals.php');
require_once( __DIR__ . '/includes/reminders/class-cr-manual.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-admin-menu-reminders.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-reminders-list-table.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-local-forms.php' );
require_once( __DIR__ . '/includes/reminders/class-cr-local-forms-ajax.php' );
require_once('class-ivole-replies.php');
require_once( __DIR__ . '/includes/blocks/class-cr-all-reviews.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-reviews-grid.php' );
require_once( __DIR__ . '/includes/blocks/class-cr-reviews-slider.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-admin-menu-reviews.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-ajax-reviews.php');
require_once( __DIR__ . '/includes/reviews/class-cr-reviews-list-table.php' );
require_once( __DIR__ . '/includes/reviews/class-cr-reviews-media-download.php' );
require_once( __DIR__ . '/includes/settings/class-cr-admin-menu-settings.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-reminder.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-extensions.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-discount-tiers.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-review-discount.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-license.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-trust-badges.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-referrals.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-shortcodes.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-email-template.php' );
require_once( __DIR__ . '/includes/settings/class-cr-settings-emails.php' );
require_once( __DIR__ . '/includes/google/class-cr-structured-data.php' );
require_once( __DIR__ . '/includes/google/class-cr-xml-feeds.php' );
require_once( __DIR__ . '/includes/google/class-cr-google-shopping-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-google-shopping-prod-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-admin-menu-product-feed.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-status.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-categories.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-identifiers.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-attributes.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-feed-reviews.php' );
require_once( __DIR__ . '/includes/google/class-cr-product-fields.php' );
require_once( __DIR__ . '/includes/misc/class-cr-checkout.php' );
require_once( __DIR__ . '/includes/misc/class-cr-admin-menu-diagnostics.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-admin-import.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-admin-menu-import.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-export-reviews.php' );
require_once( __DIR__ . '/includes/import-export/class-cr-reviews-exporter.php' );
require_once( __DIR__ . '/includes/tags/class-cr-admin-menu-tags.php' );
require_once( __DIR__ . '/includes/tags/class-cr-tags.php' );
require_once( __DIR__ . '/includes/trust-badge/class-cr-trust-badge.php' );
require_once( __DIR__ . '/includes/trust-badge/class-cr-floating-trust-badge.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-list-table.php' );
require_once( __DIR__ . '/includes/qna/class-cr-admin-menu-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-settings-qna.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-shortcode.php' );
require_once( __DIR__ . '/includes/qna/class-cr-qna-email.php' );

class Ivole {
	public const CR_VERSION = '5.3.4';

	public function __construct() {
		if( function_exists( 'wc' ) ) {
			$ivole_admin = new Ivole_Admin();
			$cr_sender = new CR_Sender();
			$cr_reviews = new CR_Reviews();
			$ivole_endpoint = new Ivole_Endpoint();
			$ivole_endpoint_replies = new Ivole_Endpoint_Replies();
			$cr_referrals = new CR_Referrals();
			$cr_structured_data = new CR_StructuredData();
			$cr_checkout = new CR_Checkout();
			$cr_product_fields = new CR_Product_Fields();
			$cr_ajax_reviews = new CR_Ajax_Reviews();
			$cr_tags = new CR_Tags();
			$cr_qna = new CR_Qna();
			$cr_trust_badge = new CR_Trust_Badge();
			new CR_Reviews_Media_Download();
			new CR_XML_Feeds();
			new CR_Local_Forms_Ajax();

			if( 'yes' === get_option( 'ivole_reviews_shortcode', 'no' ) ) {
				$cr_all_reviews = new CR_All_Reviews();
				$cr_reviews_grid = new CR_Reviews_Grid();
				$cr_reviews_slider = new CR_Reviews_Slider();
				$cr_qna_shortcode = new CR_Qna_Shortcode( $cr_qna );
			}

			if ( is_admin() ) {
				$reviews_admin_menu = new Ivole_Reviews_Admin_Menu();
				$reminders_admin_menu = new CR_Reminders_Admin_Menu();
				$tags_admin_menu = new CR_Tags_Admin_Menu();
				$qna_admin_menu = new CR_Qna_Admin_Menu();
				$product_feed_admin_menu = new CR_Product_Feed_Admin_Menu();
				$settings_admin_menu = new CR_Settings_Admin_Menu();
				$diagnostics_admin_menu = new CR_Diagnostics_Admin_Menu();
				$cr_manual = new CR_Manual();
				$cr_admin_import = new CR_Admin_Import();
				$import_admin_menu = new CR_Import_Admin_Menu();
				$reviews_exporter = new CR_Reviews_Exporter();

				new CR_Review_Reminder_Settings( $settings_admin_menu );
				new CR_Review_Extensions_Settings( $settings_admin_menu );
				new CR_Review_Discount_Settings( $settings_admin_menu );
				new CR_License_Settings( $settings_admin_menu );
				new Ivole_Trust_Badges( $settings_admin_menu );
				new CR_Referrals_Settings( $settings_admin_menu );
				new CR_Qna_Settings( $settings_admin_menu );
				new CR_Shortcodes_Settings( $settings_admin_menu );
				new CR_Emails_Settings( $settings_admin_menu );
				new CR_Status_Product_Feed( $product_feed_admin_menu );
				new CR_Categories_Product_Feed( $product_feed_admin_menu );
				new CR_Identifiers_Product_Feed( $product_feed_admin_menu );
				new CR_Attributes_Product_Feed( $product_feed_admin_menu );
				new CR_Reviews_Product_Feed( $product_feed_admin_menu );
				new CR_Export_Reviews( $import_admin_menu );

				$this->add_plugin_row_meta();
			}
		}
	}

	/**
	* Check installation cURL php extension
	* @return bool
	*/
	public static function is_curl_installed()
	{
		return in_array  ('curl', get_loaded_extensions() );
	}

	public function add_plugin_row_meta() {
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	public function plugin_row_meta( $links, $file ) {
		if ( 'customer-reviews-woocommerce/ivole.php' !== $file ) {
			return $links;
		}
		$row_meta = array(
			'docs'    => '<a href="' . esc_url( 'https://help.cusrev.com/' ) . '" aria-label="' . esc_attr__( 'View CusRev documentation', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Docs', 'customer-reviews-woocommerce' ) . '</a>',
			'support' => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/customer-reviews-woocommerce/' ) . '" aria-label="' . esc_attr__( 'Visit community forums', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Community support', 'customer-reviews-woocommerce' ) . '</a>',
		);
		return array_merge( $links, $row_meta );
	}

	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( 'customer-reviews-woocommerce/ivole.php' !== $plugin_file ) {
			return $actions;
		}
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings' ) . '" aria-label="' . esc_attr__( 'View Customer Reviews settings', 'customer-reviews-woocommerce' ) . '">' . esc_html__( 'Settings', 'customer-reviews-woocommerce' ) . '</a>',
		);
		return array_merge( $action_links, $actions );
	}
}
