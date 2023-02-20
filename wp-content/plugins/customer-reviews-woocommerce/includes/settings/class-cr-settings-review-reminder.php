<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Review_Reminder_Settings' ) ):

	class CR_Review_Reminder_Settings {

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

			$this->tab = 'review_reminder';

			add_filter( 'cr_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );

			add_action( 'woocommerce_admin_field_email_from', array( $this, 'show_email_from' ) );
			add_action( 'woocommerce_admin_field_email_from_name', array( $this, 'show_email_from_name' ) );
			add_action( 'woocommerce_admin_field_footertext', array( $this, 'show_footertext' ) );
			add_action( 'woocommerce_admin_field_ratingbar', array( $this, 'show_ratingbar' ) );
			add_action( 'woocommerce_admin_field_geolocation', array( $this, 'show_geolocation' ) );
			add_action( 'woocommerce_admin_field_twocolsradio', array( $this, 'show_twocolsradio' ) );

			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_from', array( $this, 'save_email_from' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_footer', array( $this, 'save_footertext' ), 10, 3 );

			add_action( 'wp_ajax_ivole_check_license_email_ajax', array( $this, 'check_license_email_ajax' ) );
			add_action( 'wp_ajax_ivole_verify_email_ajax', array( $this, 'ivole_verify_email_ajax' ) );

			add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Review Reminder', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();

			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {

			$this->init_settings();

			if ( ! empty( $_POST ) && isset( $_POST['ivole_attach_image_quantity'] ) ) {
				if ( $_POST['ivole_attach_image_quantity'] <= 0 ) {
					$_POST['ivole_attach_image_quantity'] = 1;
				}
			}

			// make sure that there the maximum size of attached image is larger than zero
			if( ! empty( $_POST ) && isset( $_POST['ivole_attach_image_size'] ) ) {
				if ( $_POST['ivole_attach_image_size'] <= 0 ) {
					$_POST['ivole_attach_image_size'] = 1;
				}
			}

			// make sure that we do not save "Checking license..." in the settings
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_from'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_from'] ) {
					$_POST['ivole_email_from'] = get_option( 'ivole_email_from', '' );
				}
			}
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_from_name'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_from_name'] ) {
					$_POST['ivole_email_from_name'] = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
				}
			}
			if( ! empty( $_POST ) && isset( $_POST['ivole_email_footer'] ) ) {
				if ( __( 'Checking license...', 'customer-reviews-woocommerce' ) === $_POST['ivole_email_footer'] ) {
					$_POST['ivole_email_footer'] = get_option( 'ivole_email_footer', '' );
				}
			}

			// validate colors (users sometimes remove # or provide invalid hex color codes)
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_bg'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_bg'] ) ) {
					$_POST['ivole_email_color_bg'] = '#0f9d58';
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_email_color_text'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_email_color_text'] ) ) {
					$_POST['ivole_email_color_text'] = '#ffffff';
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_form_color_bg'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_form_color_bg'] ) ) {
					$_POST['ivole_form_color_bg'] = '#0f9d58';
				}
			}
			if ( ! empty( $_POST ) && isset( $_POST['ivole_form_color_text'] ) ) {
				if( ! preg_match_all( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['ivole_form_color_text'] ) ) {
					$_POST['ivole_form_color_text'] = '#ffffff';
				}
			}

			if( ! empty( $_POST ) && isset( $_POST['ivole_shop_name'] ) ) {
				if ( !$_POST['ivole_shop_name'] ) {
					$_POST['ivole_shop_name'] = Ivole_Email::get_blogname();
				}
			}

			if( ! empty( $_POST ) ) {
				if( isset( $_POST['ivole_form_geolocation'] ) ) {
					$_POST['ivole_form_geolocation'] = '1' === $_POST['ivole_form_geolocation'] || 'yes' === $_POST['ivole_form_geolocation'] ? 'yes' : 'no';
				} else {
					$_POST['ivole_form_geolocation'] = 'no';
				}
			}

			//validate that form header and description are not empty
			if( ! empty( $_POST ) && isset( $_POST['ivole_form_header'] ) ) {
				if( empty( $_POST['ivole_form_header'] ) ) {
					WC_Admin_Settings::add_error( __( '\'Form Header\' field cannot be empty', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_form_header'] = get_option( 'ivole_form_header' );
				}
			}

			if( ! empty( $_POST ) && isset( $_POST['ivole_form_body'] ) ) {
				if( empty( preg_replace( '#\s#isUu', '', html_entity_decode( $_POST['ivole_form_body'] ) ) ) ) {
					WC_Admin_Settings::add_error( __( '\'Form Body\' field cannot be empty', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_form_body'] = get_option( 'ivole_form_body' );
				}
			}

			//check that a license key is entered when CR scheduler is enabled
			if( ! empty( $_POST ) && isset( $_POST['ivole_scheduler_type'] ) ) {
				if( 'cr' === $_POST['ivole_scheduler_type'] ) {
					$licenseKey = trim( get_option( 'ivole_license_key', '' ) );
					if( 0 === strlen( $licenseKey ) ) {
						$_POST['ivole_scheduler_type'] = 'wp';
						add_action( 'admin_notices', array( $this, 'admin_notice_scheduler' ) );
					}
				}
			}

			//check that the 'shop' page is configured in WooCommerce
			if( ! empty( $_POST ) && isset( $_POST['ivole_form_shop_rating'] ) ) {
				if( 0 >= wc_get_page_id( 'shop' ) ){
					WC_Admin_Settings::add_error( __( 'It was not possible to enable \'Shop Rating\' option because no \'Shop page\' is set in WooCommerce settings (WooCommerce > Settings) on \'Products\' tab. Please configure a \'Shop page\' in WooCommerce settings first.', 'customer-reviews-woocommerce' ) );
					$_POST['ivole_form_shop_rating'] = 'no';
				}
			}

			// if Verified Reviews option was changed, check if Mailer and Scheduler options requires an update
			if( ! empty( $_POST ) && isset( $_POST['ivole_verified_reviews'] ) ) {
				if( 'yes' === $_POST['ivole_verified_reviews'] ) {
					update_option( 'ivole_mailer_review_reminder', 'cr', false );
				} else {
					update_option( 'ivole_mailer_review_reminder', 'wp', false );
					$_POST['ivole_scheduler_type'] = 'wp';
				}
			}

			WC_Admin_Settings::save_fields( $this->settings );
		}

		protected function init_settings() {
			$language_desc = __( 'Choose language that will be used for various elements of emails and review forms.', 'customer-reviews-woocommerce' );

			$available_languages = array(
				'AR'  => __( 'Arabic', 'customer-reviews-woocommerce' ),
				'BG'  => __( 'Bulgarian', 'customer-reviews-woocommerce' ),
				'ZHT'  => __( 'Chinese (Traditional)', 'customer-reviews-woocommerce' ),
				'ZHS'  => __( 'Chinese (Simplified)', 'customer-reviews-woocommerce' ),
				'HR'  => __( 'Croatian', 'customer-reviews-woocommerce' ),
				'CS'  => __( 'Czech', 'customer-reviews-woocommerce' ),
				'DA'  => __( 'Danish', 'customer-reviews-woocommerce' ),
				'NL'  => __( 'Dutch', 'customer-reviews-woocommerce' ),
				'EN'  => __( 'English', 'customer-reviews-woocommerce' ),
				'ET'  => __( 'Estonian', 'customer-reviews-woocommerce' ),
				'FA'  => __( 'Persian', 'customer-reviews-woocommerce' ),
				'FI'  => __( 'Finnish', 'customer-reviews-woocommerce' ),
				'FR'  => __( 'French', 'customer-reviews-woocommerce' ),
				'KA'  => __( 'Georgian', 'customer-reviews-woocommerce' ),
				'DE'  => __( 'German', 'customer-reviews-woocommerce' ),
				'DEF'  => __( 'German (Formal)', 'customer-reviews-woocommerce' ),
				'EL'  => __( 'Greek', 'customer-reviews-woocommerce' ),
				'HE'  => __( 'Hebrew', 'customer-reviews-woocommerce' ),
				'HU'  => __( 'Hungarian', 'customer-reviews-woocommerce' ),
				'IS'  => __( 'Icelandic', 'customer-reviews-woocommerce' ),
				'ID'  => __( 'Indonesian', 'customer-reviews-woocommerce' ),
				'IT'  => __( 'Italian', 'customer-reviews-woocommerce' ),
				'JA'  => __( 'Japanese', 'customer-reviews-woocommerce' ),
				'KO'  => __( 'Korean', 'customer-reviews-woocommerce' ),
				'LV'  => __( 'Latvian', 'customer-reviews-woocommerce' ),
				'LT'  => __( 'Lithuanian', 'customer-reviews-woocommerce' ),
				'MK'  => __( 'Macedonian', 'customer-reviews-woocommerce' ),
				'NO'  => __( 'Norwegian', 'customer-reviews-woocommerce' ),
				'PL'  => __( 'Polish', 'customer-reviews-woocommerce' ),
				'PT'  => __( 'Portuguese', 'customer-reviews-woocommerce' ),
				'BR'  => __( 'Portuguese (Brazil)', 'customer-reviews-woocommerce' ),
				'RO'  => __( 'Romanian', 'customer-reviews-woocommerce' ),
				'RU'  => __( 'Russian', 'customer-reviews-woocommerce' ),
				'SR'  => __( 'Serbian', 'customer-reviews-woocommerce' ),
				'SK'  => __( 'Slovak', 'customer-reviews-woocommerce' ),
				'SL'  => __( 'Slovenian', 'customer-reviews-woocommerce' ),
				'ES'  => __( 'Spanish', 'customer-reviews-woocommerce' ),
				'SV'  => __( 'Swedish', 'customer-reviews-woocommerce' ),
				'TH'  => __( 'Thai', 'customer-reviews-woocommerce' ),
				'TR'  => __( 'Turkish', 'customer-reviews-woocommerce' ),
				'UK'  => __( 'Ukrainian', 'customer-reviews-woocommerce' ),
				'VI'  => __( 'Vietnamese', 'customer-reviews-woocommerce' )
			);

			// qTranslate integration
			if ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
				$language_desc .= ' ' . __( 'It looks like you have qTranslate-X plugin activated. You might want to choose "qTranslate-X Automatic" option to enable automatic selection of language.', 'customer-reviews-woocommerce' );
				$available_languages = array( 'QQ' => __( 'qTranslate-X Automatic', 'customer-reviews-woocommerce' ) ) + $available_languages;
			}

			// WPML integration
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				$language_desc .= ' ' . __( 'It looks like you have WPML plugin activated. You might want to choose "WPML Automatic" option to enable automatic selection of language.', 'customer-reviews-woocommerce' );
				$available_languages = array( 'WPML' => __( 'WPML Automatic', 'customer-reviews-woocommerce' ) ) + $available_languages;
			}

			$order_statuses = wc_get_order_statuses();
			$paid_statuses = wc_get_is_paid_statuses();
			$default_status = 'wc-completed';
			foreach ($order_statuses as $status => $description) {
				$status2 = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
				if( !in_array( $status2, $paid_statuses, true ) ) {
					unset( $order_statuses[ $status ] );
				}
				if( 'completed' === $status2 ) {
					$default_status = $status;
				}
			}

			if( 'yes' === get_option( 'ivole_coupon_enable', 'no' ) ) {
				$def_consumer_consent_text = __( 'Check here to receive an invitation from CusRev (an independent third-party organization) to review your order. Once the review is published, you will receive a coupon to use for your next purchase.', 'customer-reviews-woocommerce' );
			} else {
				$def_consumer_consent_text = __( 'Check here to receive an invitation from CusRev (an independent third-party organization) to review your order', 'customer-reviews-woocommerce' );
			}

			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );

			if( 'yes' === $verified_reviews ) {
				$scheduler_options = array(
					'wp'  => __( 'WordPress Cron', 'customer-reviews-woocommerce' ),
					'cr' => __( 'CR Cron', 'customer-reviews-woocommerce' )
				);
			} else {
				$scheduler_options = array(
					'wp'  => __( 'WordPress Cron', 'customer-reviews-woocommerce' )
				);
			}

			$this->settings = array(
				array(
					'title' => __( 'Reminders for Customer Reviews', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Configure the plugin to send automatic or manual follow-up emails (reminders) that collect store and product reviews.', 'customer-reviews-woocommerce' ),
					'id'    => 'ivole_options'
				),
				array(
					'title'   => __( 'Enable Automatic Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable automatic follow-up emails with an invitation to submit a review. Before enabling this feature, you MUST update your terms and conditions and make sure that customers consent to receive invitations to review their orders. Depending on the location of your customers, it might also be necessary to receive an explicit consent to send review reminders. In this case, it is mandatory to enable the ‘Customer Consent’ option below.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable',
					'default' => 'no',
					'autoload' => false,
					'type'    => 'checkbox'
				),
				array(
					'title' => __( 'Verified Reviews', 'customer-reviews-woocommerce' ),
					'type' => 'twocolsradio',
					'id' => 'ivole_verified_reviews',
					'default' => 'no',
					'autoload' => false
				),
				array(
					'title'    => __( 'Sending Delay (Days)', 'customer-reviews-woocommerce' ),
					'type'     => 'number',
					'desc'     => __( 'Emails will be sent N days after order status is changed to the value specified in the field below. N is a sending delay that needs to be defined in this field.', 'customer-reviews-woocommerce' ),
					'default'  => 5,
					'id'       => 'ivole_delay',
					'desc_tip' => true
				),
				array(
					'title' => __( 'Order Status', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Review reminders will be sent N days after this order status. It is recommended to use \'Completed\' status.', 'customer-reviews-woocommerce' ),
					'default'  => $default_status,
					'id' => 'ivole_order_status',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => $order_statuses
				),
				array(
					'title'    => __( 'Enable for', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Define if reminders will be send for all or only specific categories of products.', 'customer-reviews-woocommerce' ),
					'default'  => 'all',
					'id'       => 'ivole_enable_for',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => array(
						'all'        => __( 'All Categories', 'customer-reviews-woocommerce' ),
						'categories' => __( 'Specific Categories', 'customer-reviews-woocommerce' )
					)
				),
				array(
					'title'    => __( 'Categories', 'customer-reviews-woocommerce' ),
					'type'     => 'cselect',
					'desc'     => __( 'If reminders are enabled only for specific categories of products, this field enables you to choose these categories.', 'customer-reviews-woocommerce' ),
					'id'       => 'ivole_enabled_categories',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;'
				),
				array(
					'title' => __( 'Enable for Roles', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Define if reminders will be send for all or only specific roles of users.', 'customer-reviews-woocommerce' ),
					'default'  => 'all',
					'id' => 'ivole_enable_for_role',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => array(
						'all'  => __( 'All Roles', 'customer-reviews-woocommerce' ),
						'roles' => __( 'Specific Roles', 'customer-reviews-woocommerce' )
					)
				),
				array(
					'title' => __( 'Roles', 'customer-reviews-woocommerce' ),
					'type' => 'cselect',
					'desc' => __( 'If reminders are enabled only for specific user roles, this field enables you to choose these roles.', 'customer-reviews-woocommerce' ),
					'id' => 'ivole_enabled_roles',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;'
				),
				array(
					'title'   => __( 'Enable for Guests', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable sending of review reminders to customers who place orders without an account (guest checkout). It is recommended to enable this checkbox, if you allow customers to place orders without creating an account on your site.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_for_guests',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title' => __( 'Reminders Scheduler', 'customer-reviews-woocommerce' ),
					'type' => 'select',
					'desc' => __( 'Define which scheduler the plugin will use to schedule automatic review reminders. The default option is to use WordPress Cron (WP-Cron) for scheduling automatic reminders. If your hosting limits WordPress Cron functionality and automatic reminders are not sent as expected, try CR Cron. CR Cron is an external service that requires a license key (free or pro).', 'customer-reviews-woocommerce' ),
					'default'  => 'wp',
					'id' => 'ivole_scheduler_type',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => $scheduler_options
				),
				array(
					'title'   => __( 'Enable Manual Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => sprintf( __( 'Enable manual sending of follow-up emails with a reminder to submit a review. Manual reminders can be sent for completed orders from %1$sOrders%2$s page after enabling this option.', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'edit.php?post_type=shop_order' ) . '">', '</a>' ),
					'id'      => 'ivole_enable_manual',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Limit Number of Reminders', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable this checkbox to make sure that no more than one review reminder is sent for each order.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_limit_reminders',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Customer Consent', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'If this option is enabled, customers will be asked to tick a checkbox on the checkout page to indicate that they would like to receive an invitation to review their order.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_customer_consent',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Customer Consent Text', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Text of the message shown to customers next to the consent checkbox on the checkout page.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_customer_consent_text',
					'type'     => 'textarea',
					'default' => $def_consumer_consent_text,
					'css'      => 'height:5em;',
					'class'    => 'cr-admin-settings-wide-text',
					'desc_tip' => true
				),
				array(
					'title'   => __( 'Registered Customers', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'By default, review reminders are sent to billing emails provided by customers during checkout. If you enable this option, the plugin will check if customers have accounts on your website, and review reminders will be sent to emails associated with their accounts. It is recommended to keep this option disabled.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_registered_customers',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Moderation of Reviews', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable manual moderation of reviews submitted by your verified customers. This setting applies only to reviews submitted in response to reminders sent by this plugin.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_enable_moderation',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Exclude Free Products', 'customer-reviews-woocommerce' ),
					'desc'    => __( 'Enable this checkbox to exclude free products from review invitations.', 'customer-reviews-woocommerce' ),
					'id'      => 'ivole_exclude_free_products',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'    => __( 'Shop Name', 'customer-reviews-woocommerce' ),
					'type'     => 'text',
					'desc'     => __( 'Specify your shop name that will be used in emails and review forms generated by this plugin.', 'customer-reviews-woocommerce' ),
					'default'  => Ivole_Email::get_blogname(),
					'id'       => 'ivole_shop_name',
					'css'      => 'min-width:300px;',
					'desc_tip' => true
				),
				array(
					'type' => 'sectionend',
					'id'   => 'ivole_options'
				)
			);

			// some features of review forms are not available for local forms
			if( 'yes' === $verified_reviews ) {
				$this->settings[] = array(
					'title' => __( 'Language', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => $language_desc,
					'id'    => 'ivole_options_language'
				);
				$this->settings[] = array(
					'title'    => __( 'Language', 'customer-reviews-woocommerce' ),
					'type'     => 'select',
					'desc'     => __( 'Choose one of the available languages.', 'customer-reviews-woocommerce' ),
					'default'  => 'EN',
					'id'       => 'ivole_language',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => $available_languages
				);
				$this->settings[] = array(
					'type' => 'sectionend',
					'id'   => 'ivole_options_language'
				);
			}

			$this->settings[] = array(
				'title' => __( 'Email Template', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc' => sprintf( __( 'The email template of review reminders can be configured on the <a href="%s">Emails</a> tab.', 'customer-reviews-woocommerce' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=emails' ) ),
				'id'    => 'ivole_options_email'
			);
			$this->settings[] = array(
				'type' => 'sectionend',
				'id'   => 'ivole_options_email'
			);
			$this->settings[] = array(
				'title' => __( 'Review Form Template', 'customer-reviews-woocommerce' ),
				'type'  => 'title',
				'desc'  => sprintf( __( 'Adjust template of the aggregated review forms that will be created and sent to customers by CusRev. Modifications will be applied to the next review form created after saving settings. If you enable <b>advanced</b> form templates in your account on %1$sCusRev website%2$s, they will <b>override</b> the settings below.', 'customer-reviews-woocommerce' ), '<a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">', '</a>' ),
				'id'    => 'ivole_options_form'
			);
			$this->settings[] = array(
				'title'    => __( 'Form Header', 'customer-reviews-woocommerce' ),
				'type'     => 'text',
				'desc'     => __( 'Header of the review form that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'default'  => 'How did we do?',
				'id'       => 'ivole_form_header',
				'class'    => 'cr-admin-settings-wide-text',
				'desc_tip' => true
			);
			$this->settings[] = array(
				'title'    => __( 'Form Body', 'customer-reviews-woocommerce' ),
				'type'     => 'textarea',
				'desc'     => __( 'Body of the review form that will be sent to customers.', 'customer-reviews-woocommerce' ),
				'default'  => 'Please review your experience with products and services that you purchased at {site_title}.',
				'id'       => 'ivole_form_body',
				'css'      => 'height:5em;',
				'class'    => 'cr-admin-settings-wide-text',
				'desc_tip' => true
			);
			$this->settings[] = array(
				'title'   => __( 'Shop Rating', 'customer-reviews-woocommerce' ),
				'type'    => 'checkbox',
				'id'      => 'ivole_form_shop_rating',
				'default' => 'no',
				'desc'    => __( 'Enable this option if you would like to include a separate question for a general shop review in addition to questions for product reviews.', 'customer-reviews-woocommerce' )
			);
			$this->settings[] = array(
				'title'   => __( 'Comment Required', 'customer-reviews-woocommerce' ),
				'type'    => 'checkbox',
				'id'      => 'ivole_form_comment_required',
				'default' => 'no',
				'desc'    => __( 'Enable this option if you would like to make it mandatory for your customers to write something in their review. This option applies only to aggregated review forms.', 'customer-reviews-woocommerce' )
			);
			if( 'yes' === $verified_reviews ) {
				$desc = sprintf( __( 'Enable attachment of pictures and videos on aggregated review forms. Uploaded media files are initially stored on Amazon S3 and automatically downloaded into WordPress Media Library later. This option applies only to aggregated review forms. If you would like to enable attachment of pictures to reviews submitted on WooCommerce product pages, this can be done %1$shere%2$s.', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=review_extensions' ) . '">', '</a>' );
			} else {
				$desc = sprintf( __( 'Enable attachment of pictures and videos on local aggregated review forms. This option applies only to aggregated review forms. If you would like to enable attachment of pictures to reviews submitted on WooCommerce product pages, this can be done %1$shere%2$s.', 'customer-reviews-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings&tab=review_extensions' ) . '">', '</a>' );
			}
			$this->settings[] = array(
				'title'   => __( 'Attach Media', 'customer-reviews-woocommerce' ),
				'type'    => 'checkbox',
				'id'      => 'ivole_form_attach_media',
				'default' => 'no',
				'desc'    => $desc
			);

			// some features of review forms are not available for local forms
			if( 'yes' === $verified_reviews ) {
				$this->settings[] = array(
					'title'   => __( 'Rating Bar', 'customer-reviews-woocommerce' ),
					'type'    => 'ratingbar',
					'id'      => 'ivole_form_rating_bar',
					'default' => 'smiley',
					'desc_tip'    => __( 'Visual style of rating bars on review forms.', 'customer-reviews-woocommerce' ),
					'options' => array(
						'smiley'  => __( 'Smiley and frowny faces', 'customer-reviews-woocommerce' ),
						'star'    => __( 'Stars', 'customer-reviews-woocommerce' ),
					),
					'css'     => 'display:none;'
				);
				$this->settings[] = array(
					'title'   => __( 'Geolocation', 'customer-reviews-woocommerce' ),
					'type'    => 'geolocation',
					'id'      => 'ivole_form_geolocation',
					'default' => 'no',
					'desc'    => __( 'Enable geolocation on aggregated review forms. Customers will have an option to indicate where they are from. For example, "England, United Kingdom".', 'customer-reviews-woocommerce' ),
					'desc_tip'    => __( 'Automatic geolocation on review forms.', 'customer-reviews-woocommerce' ),
					'css'     => 'display:none;'
				);
			}

			$this->settings[] = array(
				'title'    => __( 'Form Color 1', 'customer-reviews-woocommerce' ),
				'type'     => 'text',
				'id'       => 'ivole_form_color_bg',
				'default'  => '#2C5E66',
				'desc'     => __( 'Background color for heading of the form and product names.', 'customer-reviews-woocommerce' ),
				'desc_tip' => true
			);
			$this->settings[] = array(
				'title'    => __( 'Form Color 2', 'customer-reviews-woocommerce' ),
				'type'     => 'text',
				'id'       => 'ivole_form_color_text',
				'default'  => '#FFFFFF',
				'desc'     => __( 'Text color for product names.', 'customer-reviews-woocommerce' ),
				'desc_tip' => true
			);
			$this->settings[] = array(
				'title'    => __( 'Form Color 3', 'customer-reviews-woocommerce' ),
				'type'     => 'text',
				'id'       => 'ivole_form_color_el',
				'default'  => '#1AB394',
				'desc'     => __( 'Color of control elements (buttons, rating bars).', 'customer-reviews-woocommerce' ),
				'desc_tip' => true
			);
			$this->settings[] = array(
				'type' => 'sectionend',
				'id'   => 'ivole_options_form'
			);
		}

		public function is_this_tab() {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
		}

		public function is_other_tab( $tab ) {
			return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $tab );
		}

		/**
		* Custom field type for from email
		*/
		public function show_email_from( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<?php
							echo esc_html( $value['title'] );
							echo $tooltip_html;
						?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text" style="display:none; vertical-align:middle; margin: 0 10px 0 0;"
					class="<?php echo esc_attr( trim( 'cr-email-from-input ' . $value['class'] ) ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					/>
					<?php echo $description; ?>
					<span id="ivole_email_from_verify_status" style="display:none;padding:5px;vertical-align:middle;border-radius:3px;line-height:20px;margin:5px 10px 5px 0;"></span>
					<input
					type="button"
					id="ivole_email_from_verify_button"
					value="Verify"
					class="button-primary"
					style="display:none;vertical-align:middle;"
					/>
					<p id="ivole_email_from_status"></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for from  name
		*/
		public function show_email_from_name( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<?php
							echo esc_html( $value['title'] );
							echo $tooltip_html;
						?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text" style="display: none;" class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"/>
					<?php echo $description; ?>
					<p id="ivole_email_from_name_status"></p>
				</td>
			</tr>
			<?php
		}

		/*
		* Custom field type for email footer text
		*/
		public function show_footertext( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$default = $tmp['default'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php echo $description; ?>
					<textarea name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>"
						style="display: none;" class="<?php echo esc_attr( $value['class'] ); ?>" rows="3">
							<?php esc_html_e( get_option( $value['id'], $default ) ); ?>
					</textarea>
					<p id="ivole_email_footer_status"></p>
				</td>
			</tr>
			<?php
		}

		/*
		* Custom field type for rating bar style
		*/
		public function show_ratingbar( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-radio">
					<fieldset style="<?php echo esc_attr( $value['css'] ); ?>" id="ivole_form_rating_bar_fs">
						<?php echo $description; ?>
						<ul>
							<?php
							foreach ( $value['options'] as $key => $val ) {
								?>
								<li>
									<label><input
										name="<?php echo esc_attr( $value['id'] ); ?>"
										value="<?php echo esc_attr( $key ); ?>"
										type="radio"
										class="<?php echo esc_attr( $value['class'] ); ?>"
										<?php checked( $key, $option_value ); ?>
										/> <?php echo esc_html( $val ); ?>
									</label>
								</li>
								<?php
							}
							?>
						</ul>
					</fieldset>
					<p id="ivole_form_rating_bar_status"></p>
				</td>
			</tr>
			<?php
		}

		public function show_geolocation( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset style="<?php echo esc_attr( $value['css'] ); ?>" id="ivole_form_geolocation_fs">
						<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
							name="<?php echo esc_attr( $value['id'] ); ?>"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							type="checkbox"
							value="1"
							<?php checked( $option_value, 'yes' ); ?>
							/> <?php echo $description; ?>
						</label>
					</fieldset>
					<p id="ivole_form_geolocation_status"></p>
				</td>
			</tr>
			<?php
		}

		public function show_twocolsradio( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$option_value = get_option( $value['id'], $value['default'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-checkbox">
					<div class="cr-twocols-cont">
						<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">
						<div class="cr-twocols-left cr-twocols-cols<?php if( 'yes' !== $option_value ) echo ' cr-twocols-sel'; ?>">
							<svg width="68" height="63" viewBox="0 0 68 63" fill="none" xmlns="http://www.w3.org/2000/svg">
								<mask id="path-1-inside-1_6_5" fill="white">
									<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z"/>
								</mask>
								<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z" fill="#E1E1E1" stroke="#D1D1D1" stroke-width="2" mask="url(#path-1-inside-1_6_5)"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M24.4735 57.6588L38.1044 6.00509L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.487 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6282 60.8656C55.8645 62.1934 54.425 63.2062 53.1921 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L24.4735 57.6588Z" fill="#D1D1D1"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'No verification' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php esc_html_e( 'Collect reviews locally without third-party verification' ); echo wc_help_tip( 'The complete reviews collection solution hosted on your server' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Review invitations will be sent by the default mailer from your website' ); echo wc_help_tip( 'The plugin will use the standard \'wp_mail\' function for sending emails in WordPress' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Aggregated review forms will be hosted locally on your server' ); echo wc_help_tip( 'An aggregated review form is a review form that supports collection of reviews for multiple products at the same time.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'No restrictions on collection of reviews for prohibited product categories' ); echo wc_help_tip( 'Since CusRev does not have to display copies of unverified reviews, there are no restrictions on allowed categories of products' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'I understand that visitors of my website are likely to consider unverified reviews to be biased or fake' ); ?>
									</li>
								</ul>
							</div>
							<div class="cr-twocols-footer">
								<div class="cr-twocols-chkbox">
									<div class="cr-twocols-chkbox-inner">
									</div>
									<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 25.5C6.09625 25.5 0.5 19.9038 0.5 13C0.5 6.09625 6.09625 0.5 13 0.5C19.9038 0.5 25.5 6.09625 25.5 13C25.5 19.9038 19.9038 25.5 13 25.5ZM11.7538 18L20.5913 9.16125L18.8238 7.39375L11.7538 14.465L8.2175 10.9288L6.45 12.6963L11.7538 18Z" fill="#A46497"/>
									</svg>
								</div>
							</div>
						</div>
						<div class="cr-twocols-right cr-twocols-cols<?php if( 'yes' === $option_value ) echo ' cr-twocols-sel'; ?>">
							<svg width="68" height="63" viewBox="0 0 68 63" fill="none" xmlns="http://www.w3.org/2000/svg">
								<mask id="path-1-inside-1_2_5" fill="white">
									<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.4871 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z"/>
								</mask>
								<path d="M32.495 0.905848C33.1094 -0.301949 34.8903 -0.301949 35.5047 0.905848L44.7112 18.9641C44.9565 19.4442 45.4291 19.7767 45.9758 19.8531L66.5608 22.7499C67.9378 22.9438 68.4871 24.583 67.492 25.522L52.5944 39.579C52.1997 39.9518 52.0183 40.4906 52.1128 41.017L55.6283 60.8656C55.8646 62.1934 54.425 63.2062 53.1922 62.5785L34.7817 53.2088C34.2924 52.9599 33.7073 52.9599 33.218 53.2088L14.8062 62.5785C13.5747 63.2062 12.135 62.1934 12.3713 60.8656L15.8869 41.017C15.9801 40.4906 15.8 39.9518 15.404 39.579L0.508965 25.522C-0.487444 24.583 0.0618583 22.9438 1.43895 22.7499L22.025 19.8531C22.5706 19.7767 23.0443 19.4442 23.2885 18.9641L32.495 0.905848Z" fill="#F4DB6B" stroke="#F5CD5B" stroke-width="2" mask="url(#path-1-inside-1_2_5)"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M24.4734 57.6588L38.1043 6.005L44.7111 18.964C44.9564 19.4441 45.429 19.7766 45.9758 19.853L66.5607 22.7499C67.9377 22.9438 68.487 24.5829 67.492 25.5219L52.5944 39.579C52.1996 39.9517 52.0182 40.4905 52.1128 41.0169L55.6282 60.8655C55.8645 62.1933 54.4249 63.2061 53.1921 62.5784L34.7816 53.2087C34.2923 52.9598 33.7072 52.9598 33.2179 53.2087L24.4734 57.6588Z" fill="#F5CD5B"/>
							</svg>
							<div class="cr-twocols-title">
								<?php esc_html_e( 'Independently verified' ) ?>
							</div>
							<div class="cr-twocols-main">
								<ul>
									<li>
										<?php echo 'Use <a href="https://www.cusrev.com/business/" target="_blank" rel="noopener noreferrer">CusRev</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon"> for collection and verification of reviews' . wc_help_tip( 'CusRev (Customer Reviews) is a service for businesses that offers a voluntary scheme for verification of reviews submitted by customers.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Review invitations will be sent by CusRev on behalf of your store' ); echo wc_help_tip( 'CusRev uses AWS SES (Simple Email Service) for sending emails to ensure their excellent deliverability' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'Aggregated review forms will be hosted on AWS S3 by CusRev' ); echo wc_help_tip( 'An aggregated review form is a review form that supports collection of reviews for multiple products at the same time.' ); ?>
									</li>
									<li>
										<?php esc_html_e( 'CusRev is unable to collect and verify reviews for certain products' ); echo wc_help_tip( 'Due to regulatory restrictions, CusRev is unable to collect and verify reviews for prohibited categories of products (e.g., CBD or Kratom)' ); ?>
									</li>
									<li>
										<?php echo 'I confirm that I will send review invitations only with consent of customers and agree to CusRev’s <a href="https://www.cusrev.com/terms.html" target="_blank" rel="noopener noreferrer">terms and conditions</a><img src="' . untrailingslashit( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">'; ?>
									</li>
								</ul>
							</div>
							<div class="cr-twocols-footer">
								<div class="cr-twocols-chkbox">
									<div class="cr-twocols-chkbox-inner">
									</div>
									<span data-tip="<?php echo esc_attr__( 'Enabled', 'woocommerce' ); ?>"><svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M13 25.5C6.09625 25.5 0.5 19.9038 0.5 13C0.5 6.09625 6.09625 0.5 13 0.5C19.9038 0.5 25.5 6.09625 25.5 13C25.5 19.9038 19.9038 25.5 13 25.5ZM11.7538 18L20.5913 9.16125L18.8238 7.39375L11.7538 14.465L8.2175 10.9288L6.45 12.6963L11.7538 18Z" fill="#A46497"/>
									</svg></span>
								</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for body email save
		*/
		public function save_email_from( $value, $option, $raw_value ) {
			if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				return strtolower( $value );
			}
			return;
		}

		/**
		* Custom field type for email footer text save
		*/
		public function save_footertext( $value, $option, $raw_value ) {
			return $raw_value;
		}

		/**
		* Function to check status of the license and verification of email
		*/
		public function check_license_email_ajax() {
			$license = new CR_License();
			$lval = $license->check_license();

			if ( 1 === $lval['code'] ) {
				// the license is active, so check if current from email address is verified
				$verify = new Ivole_Email_Verify();
				$vval = $verify->is_verified();
				wp_send_json( array( 'license' => $lval['code'], 'email' => $vval ) );
			} else {
				wp_send_json( array( 'license' => $lval['code'], 'email' => 0 ) );
			}
		}

		/**
		* Function to verify an email
		*/
		public function ivole_verify_email_ajax() {
			$email = strval( $_POST['email'] );
			$verify = new Ivole_Email_Verify();
			$vval = $verify->verify_email( $email );
			wp_send_json( array( 'verification' => $vval['res'], 'email' => $email, 'message' => $vval['message'] ) );
		}

		public function output_page_javascript() {
			if ( $this->is_this_tab() || $this->is_other_tab( 'emails' ) ) {
				?>
				<script type="text/javascript">
				jQuery(function($) {
					// Load of Review Reminder page and check of From Email verification
					if( jQuery('#ivole_email_from.cr-email-from-input').length > 0 || jQuery('#ivole_form_rating_bar_status').length > 0 ) {
						var data = {
							'action': 'ivole_check_license_email_ajax',
							'email': '<?php echo get_option( 'ivole_email_from', '' ); ?>'
						};
						jQuery('#ivole_email_from_status').text( '<?php echo __( 'Checking license...', 'customer-reviews-woocommerce' ); ?>' );
						jQuery('#ivole_email_from_name_status').text( '<?php echo __( 'Checking license...', 'customer-reviews-woocommerce' ); ?>' );
						jQuery('#ivole_email_footer_status').text( '<?php echo __( 'Checking license...', 'customer-reviews-woocommerce' ); ?>' );
						jQuery('#ivole_form_rating_bar_status').text( '<?php echo __( 'Checking license...', 'customer-reviews-woocommerce' ); ?>' );
						jQuery('#ivole_form_geolocation_status').text( '<?php echo __( 'Checking license...', 'customer-reviews-woocommerce' ); ?>' );
						jQuery.post(ajaxurl, data, function(response) {
							jQuery('#ivole_email_footer_status').css('visibility', 'visible');

							if (1 === response.license) {
								jQuery('#ivole_email_from').val( '<?php echo get_option( 'ivole_email_from', '' ); ?>' );
								jQuery('#ivole_email_from').show();
								jQuery('#ivole_email_from_verify_status').show().css( 'display', 'inline-block' );
								jQuery('#ivole_email_from_name').show();
								jQuery('#ivole_email_from_name').val( <?php echo json_encode( get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() ), JSON_HEX_APOS|JSON_HEX_QUOT ); ?> );
								jQuery('#ivole_email_from_name_status').hide();
								jQuery('#ivole_email_footer').show();
								jQuery('#ivole_email_footer').val( <?php echo json_encode( get_option( 'ivole_email_footer', "" ), JSON_HEX_APOS|JSON_HEX_QUOT ); ?> );
								jQuery('#ivole_email_footer_status').text( '<?php echo esc_html__( 'While editing the footer text please make sure to keep the unsubscribe link markup:', 'customer-reviews-woocommerce' ); ?> <a href="{{unsubscribeLink}}" style="color:#555555; text-decoration: underline; line-height: 12px; font-size: 10px;">unsubscribe</a>.' );
								jQuery('#ivole_form_rating_bar_fs').show();
								jQuery('#ivole_form_rating_bar_status').hide();
								jQuery('#ivole_form_geolocation_fs').show();
								jQuery('#ivole_form_geolocation_status').hide();

								if (1 === response.email){
									jQuery('#ivole_email_from_verify_status').css('background', '#00FF00');
									jQuery('#ivole_email_from_verify_status').text( 'Verified' );
									jQuery('#ivole_email_from_status').text( '' );
									jQuery('#ivole_email_from_status').hide();
								} else {
									jQuery('#ivole_email_from_verify_status').css('background', '#FA8072');
									jQuery('#ivole_email_from_verify_status').text( 'Unverified' );
									jQuery('#ivole_email_from_verify_button').show();
									jQuery('#ivole_email_from_status').text( 'This email address is unverified. You must verify it to send emails.' );
								}
							} else {
								jQuery('#ivole_email_from').val( '' );
								jQuery('#ivole_email_from_status').html( 'Review reminders are sent by CusRev from \'feedback@cusrev.com\'. This indicates to customers that review process is independent and trustworthy. \'From Address\' can be modified with the <a href="<?php echo admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ); ?>">professional license</a> for CusRev.' );
								jQuery('#ivole_email_from_name_status').html( 'Since review invitations are sent via CusRev, \'From Name\' will be based on \'Shop Name\' (see above) with a reference to CusRev. This field can be modified with the <a href="<?php echo admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ); ?>">professional license</a> for CusRev.' );
								jQuery('#ivole_email_footer_status').html( 'To comply with the international laws about sending emails (CAN-SPAM act, CASL laws, etc), CusRev will automatically add a footer with address of the sender and an opt-out link. The footer can be modified with the <a href="<?php echo admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ); ?>">professional license</a> for CusRev.' );
								jQuery('#ivole_form_rating_bar_status').html( 'CusRev creates review forms that support two visual styles of rating bars: smiley/frowny faces and stars. The default style is smiley/frowny faces. This option can be modified with the <a href="<?php echo admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ); ?>">professional license</a> for CusRev.' );
								jQuery('#ivole_form_geolocation_status').html( 'CusRev supports automatic determination of geolocation and gives reviewers an option to indicate where they are from. For example, "England, United Kingdom". This feature requires the <a href="<?php echo admin_url( 'admin.php?page=cr-reviews-settings&tab=license-key' ); ?>">professional license</a> for CusRev.' );
							}
							// integration with qTranslate-X - add translation for elements that are loaded with a delay
							if (typeof qTranslateConfig !== 'undefined' && typeof qTranslateConfig.qtx !== 'undefined') {
								qTranslateConfig.qtx.addContentHook( document.getElementById( 'ivole_email_from_name' ), null, null );
								qTranslateConfig.qtx.addContentHook( document.getElementById( 'ivole_email_footer' ), null, null );
							}
						});
					}

					// Click on Verify From Email button
					jQuery('#ivole_email_from_verify_button').click(function(){
						var data = {
							'action': 'ivole_verify_email_ajax',
							'email': jQuery('#ivole_email_from').val()
						};
						jQuery('#ivole_email_from_verify_button').prop('disabled', true);
						jQuery('#ivole_email_from_status').text( 'Sending verification email...' );
						jQuery.post(ajaxurl, data, function(response) {
							if ( 1 === response.verification ) {
								jQuery('#ivole_email_from_status').text( 'A verification email from Amazon Web Services has been sent to \'' + response.email + '\'. Please open the email and click the verification URL to confirm that you are the owner of this email address. After verification, reload this page to see updated status of verification.' );
								jQuery('#ivole_email_from_verify_button').css('visibility', 'hidden');
							} else if ( 2 === response.verification ) {
								jQuery('#ivole_email_from_status').text( 'Verification error: ' + response.message + '.' );
								jQuery('#ivole_email_from_verify_button').prop('disabled', false);
							} else if ( 3 === response.verification ) {
								jQuery('#ivole_email_from_status').text( 'Verification error: ' + response.message + '. Please refresh the page to see the updated verification status.' );
								jQuery('#ivole_email_from_verify_button').prop('disabled', false);
							} else if ( 99 === response.verification ) {
								jQuery('#ivole_email_from_status').text( 'Verification error: please enter a valid email address.' );
								jQuery('#ivole_email_from_verify_button').prop('disabled', false);
							} else {
								jQuery('#ivole_email_from_status').text( 'Verification error.' );
								jQuery('#ivole_email_from_verify_button').prop('disabled', false);
							}
						});
					});
				});
				</script>
				<?php
			}
		}

		public function admin_notice_scheduler() {
			if ( current_user_can( 'manage_options' ) ) {
				$class = 'notice notice-error';
				$message = __( '<strong>CR Cron could not be enabled because no license key was entered. A license key (free or pro) is required to use CR Cron.</strong>', 'customer-reviews-woocommerce' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
			}
		}

	}

endif;
