<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_License_Settings' ) ):

	class CR_License_Settings {

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

			$this->tab = 'license-key';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );
			add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = '&#9733; ' . __( 'License Key', 'customer-reviews-woocommerce' ) . ' &#9733;';
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();

			$field_id = 'ivole_license_key';
			if( !empty( $_POST ) && isset( $_POST[$field_id] ) ) {
				$license = new CR_License();
				$license->register_license( $_POST[$field_id] );
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$description_website = '<a href="https://www.cusrev.com/business/" target="_blank">';
			$description_website_register = '<a href="https://www.cusrev.com/register.html" target="_blank">';
			$description_website_pricing = '<a href="https://www.cusrev.com/business/pricing.html" target="_blank">Free vs Pro</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png' .'" class="cr-product-feed-categories-ext-icon"></p>';
			$license_keys_description = '<p>' . __( 'Customer Reviews (CusRev) service works with two types of license keys: (1) professional and (2) free.', 'customer-reviews-woocommerce' ) . '</p>';
			$license_keys_description .= '<p>' . sprintf( __( '(1) You can unlock <b>all</b> features for managing customer reviews by purchasing a professional license key => %sProfessional License Key', 'customer-reviews-woocommerce' ), $description_website );
			$license_keys_description .= '</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon"></p>';
			$license_keys_description .= '<p>' . sprintf( __( '(2) Basic features of CusRev service (e.g., social media integration, analytics, replies to reviews) are available for free but require a (free) license key. If you would like to request a free license key (no pro features), create an account here => %sFree License Key', 'customer-reviews-woocommerce' ), $description_website_register );
			$license_keys_description .= '</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon"></p>';
			$license_keys_description .= '<p>' . sprintf( __( 'An overview of features available in the Free and Pro versions of Customer Reviews: %s', 'customer-reviews-woocommerce' ), $description_website_pricing );
			$this->settings = array(
				array(
					'title' => __( 'Types of License Keys', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $license_keys_description,
					'id'    => 'ivole_options_premium'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_premium'
				),
				array(
					'title' => __( 'License Key', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Please enter your license key (free or pro) in the field below. The plugin will automatically determine type of your license key.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options_license'
				),
				array(
					'title'    => __( 'License Status', 'customer-reviews-woocommerce' ),
					'type'     => 'license_status',
					'desc'     => __( 'Information about license status.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_license_status',
					'desc_tip' => true
				),
				array(
					'title'    => __( 'License Key', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Enter your license key here.', 'customer-reviews-woocommerce' ),
					'default'  => '',
					'id'       => 'ivole_license_key',
					'desc_tip' => true
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_license'
				)
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function output_page_javascript() {
			if ( $this->is_this_tab() ) {
				?>
				<script type="text/javascript">
				jQuery(function($) {
					if ( jQuery('#ivole_license_status').length > 0 ) {
						var data = {
							'action': 'ivole_check_license_ajax'
						};

						jQuery('#ivole_license_status').val( '<?php echo __('Checking...', 'customer-reviews-woocommerce');?>' );

						jQuery.post(ajaxurl, data, function(response) {
							jQuery('#ivole_license_status').val( response.message );
						});
					}
				});
				</script>
				<?php
			}
		}
	}

endif;
