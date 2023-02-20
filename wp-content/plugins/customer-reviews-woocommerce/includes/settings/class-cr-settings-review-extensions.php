<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Review_Extensions_Settings' ) ):

	class CR_Review_Extensions_Settings {

		/**
		* @var CR_Settings_Admin_Menu The instance of the settings admin menu
		*/
		protected $settings_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;

		public function __construct( $settings_menu ) {
			$this->settings_menu = $settings_menu;

			$this->tab = 'review_extensions';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Review Extensions', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();

			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();

			// make sure that there the maximum number of attached images is larger than zero
			if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_quantity'] ) ) {
				if( $_POST['ivole_attach_image_quantity'] <= 0 ) {
					$_POST['ivole_attach_image_quantity'] = 1;
				}
			}
			// make sure that there the maximum size of attached image is larger than zero
			if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_size'] ) ) {
				if( $_POST['ivole_attach_image_size'] <= 0 ) {
					$_POST['ivole_attach_image_size'] = 1;
				}
			}

			//removing spaces
			if( !empty( $_POST ) && isset( $_POST['ivole_verified_owner'] ) ) {
				$_POST['ivole_verified_owner'] = trim($_POST['ivole_verified_owner']);
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$review_reminder_settings_url = admin_url( 'admin.php?page=cr-reviews-settings&tab=review_reminder' );
			$this->settings = array(
				array(
					'title' => __( 'Extensions for Customer Reviews', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'The plugin is based on the standard WooCommerce reviews functionality. Here, you can configure various extensions for standard WooCommerce reviews.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options'
				),
				array(
					'title'   => __( 'Attach Images', 'customer-reviews-woocommerce' ),
					/* translators: please keep %1$s and %2$s in the translated version */
					'desc'    => sprintf( __( 'Enable attachment of images to reviews left on WooCommerce product pages. If you would like to enable attachment of images on aggregated review forms, this can be done %1$shere%2$s.', 'customer-reviews-woocommerce' ), '<a href="' . $review_reminder_settings_url . '">', '</a>' ),
					'id'      => 'ivole_attach_image',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'Quantity of Images', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the maximum number of images that can be uploaded for a single review. This setting applies only to reviews submitted on single product pages.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_attach_image_quantity',
					'default'  => 5,
					'type'     => 'number',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Maximum Size of Image', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the maximum size (in MB) of an image that can be uploaded with a review. This setting applies only to reviews submitted on single product pages.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_attach_image_size',
					'default'  => 25,
					'type'     => 'number',
					'desc_tip' => true
				),
				array(
					'title'         => __( 'Disable Lightbox', 'customer-reviews-woocommerce' ),
					'desc'          => __( 'Disable lightboxes for images attached to reviews (not recommended). Use this option only if your theme generates lightboxes for any picture on the website and this leads to two lightboxes shown after clicking on an image attached to a review.', 'customer-reviews-woocommerce' ),
					'id'            => 'ivole_disable_lightbox',
					'default'       => 'no',
					'type'          => 'checkbox'
				),
				array(
					'title'   => __( 'reCAPTCHA V2 for Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable reCAPTCHA to eliminate fake reviews. You must enter Site Key and Secret Key in the fields below if you want to use reCAPTCHA. You will receive Site Key and Secret Key after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_captcha',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'reCAPTCHA V2 Site Key', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Site Key that you will receive after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_captcha_site_key',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'reCAPTCHA V2 Secret Key', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Secret Key that you will receive after registration at reCAPTCHA website.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_captcha_secret_key',
					'desc_tip' => true
				),
				array(
					'title'   => __( 'Reviews Summary Bar', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable display of a histogram table with a summary of reviews on a product page.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_reviews_histogram',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'Vote for Reviews', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Enable people to upvote or downvote reviews. The plugin allows one vote per review per person. If the person is a guest, the plugin uses cookies and IP addresses to identify this visitor.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_reviews_voting',
					'default'  => 'no',
					'type'     => 'checkbox'
				),
				array(
					'title'   => __( 'Remove Plugin\'s Branding', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable this option to remove plugin\'s branding ("Powered by Customer Reviews plugin") from the reviews summary bar. If you like our plugin and would like to support us, please disable this checkbox.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_reviews_nobranding',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'Verified Owner', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Replace the standard ‘verified owner’ label that WooCommerce adds to customer reviews with a custom one. If this field is blank, the standard WooCommerce label will be used.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_verified_owner',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Customer Avatars', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Choose how customer avatars are displayed on WooCommerce single product pages. You can use either the standard WordPress avatars or avatars created based on initial letters of customer names.', 'customer-reviews-woocommerce' ),
					'default'  => 'standard',
					'id'       => 'ivole_avatars',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options'  => array(
						'standard'  => __( 'Standard', 'customer-reviews-woocommerce' ),
						'initials' => __( 'Initials', 'customer-reviews-woocommerce' )
					)
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options'
				),
				array(
					'title' => __( 'Lazy Load Reviews', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Settings to display reviews with \'Show more\' button instead of the standard WordPress pagination.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options_ajax'
				),
				array(
					'title'   => __( 'Lazy Load Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Display reviews on product pages with \'Show more\' button.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_ajax_reviews',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'Default Quantity of Reviews', 'customer-reviews-woocommerce' ),
					'desc'     => __( 'Specify the default number of reviews that will be shown during the initial product page load.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_ajax_reviews_per_page',
					'default'  => 5,
					'type'     => 'number',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'Default Sorting Order', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Define how reviews are sorted by default. The option to vote for reviews must be enabled to show the most helpful reviews first.', 'customer-reviews-woocommerce' ),
					'default'  => 'recent',
					'id'       => 'ivole_ajax_reviews_sort',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options'  => array(
						'recent'  => __( 'Recent reviews first', 'customer-reviews-woocommerce' ),
						'helpful' => __( 'Most helpful reviews first', 'customer-reviews-woocommerce' )
					)
				),
				array(
					'title'   => __( 'Review Form', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Display a review form on product pages. If you would like to protect your site from SPAM reviews and allow customers to submit reviews only via invitations (review reminders), this option should be disabled.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_ajax_reviews_form',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_ajax'
				),
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

	}

endif;
