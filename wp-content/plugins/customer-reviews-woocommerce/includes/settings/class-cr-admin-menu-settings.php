<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Settings_Admin_Menu' ) ):

	require_once dirname( dirname( __FILE__ ) ) . '/misc/class-cr-license.php';
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/class-ivole-email-verify.php';
	require_once 'class-cr-milestones.php';

	class CR_Settings_Admin_Menu {

		/**
		* @var string URL to admin diagnostics page
		*/
		protected $page_url;

		/**
		* @var string The slug identifying this menu
		*/
		protected $menu_slug;

		/**
		* @var string The slug of the currently displayed tab
		*/
		protected $current_tab = 'review_reminder';

		public function __construct() {
			$this->menu_slug = 'cr-reviews-settings';

			$this->page_url = add_query_arg( array(
				'page' => $this->menu_slug
			), admin_url( 'admin.php' ) );

			if ( isset( $_GET['tab'] ) ) {
				$this->current_tab = $_GET['tab'];
			}

			add_action( 'admin_init', array( $this, 'save_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_settings_menu' ), 11 );
			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ), 11 );

			add_action( 'woocommerce_admin_field_cselect', array( $this, 'show_cselect' ) );
			add_action( 'woocommerce_admin_field_htmltext', array( $this, 'show_htmltext' ) );
			add_action( 'woocommerce_admin_field_emailtest', array( $this, 'show_emailtest' ) );
			add_action( 'woocommerce_admin_field_license_status', array( $this, 'show_license_status' ) );

			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_enabled_categories', array( $this, 'save_cselect' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_enabled_roles', array( $this, 'save_cselect' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body', array( $this, 'save_htmltext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_coupon', array( $this, 'save_htmltext' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_qna_reply', array( $this, 'save_htmltext' ), 10, 3 );

			add_action( 'wp_ajax_ivole_send_test_email', array( $this, 'send_test_email' ) );
			add_action( 'wp_ajax_ivole_check_license_ajax', array( $this, 'check_license_ajax' ) );

			add_filter( 'woocommerce_screen_ids', array( $this, 'filter_woocommerce_screen_ids' ) );
		}

		public function register_settings_menu() {
			add_submenu_page(
				'cr-reviews',
				__( 'Settings', 'customer-reviews-woocommerce' ),
				__( 'Settings', 'customer-reviews-woocommerce' ),
				'manage_options',
				$this->menu_slug,
				array( $this, 'display_settings_admin_page' )
			);
		}

		public function display_settings_admin_page() {
			?>
			<div class="wrap ivole-new-settings woocommerce">
				<h1 class="wp-heading-inline" style="margin-bottom:8px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<hr class="wp-header-end">
				<?php
				$tabs = apply_filters( 'cr_settings_tabs', array() );

				if ( is_array( $tabs ) && sizeof( $tabs ) > 1 ) {
					echo '<ul class="subsubsub">';

					$array_keys = array_keys( $tabs );
					$last = end( $array_keys );

					foreach ( $tabs as $tab => $label ) {
						echo '<li><a href="' . $this->page_url . '&tab=' . $tab . '" class="' . ( $this->current_tab === $tab ? 'current' : '' ) . '">' . $label . '</a> ' . ( $last === $tab ? '' : '|' ) . ' </li>';
					}

					echo '</ul><br class="clear" />';
				}
				?>
				<form action="" method="post" id="mainform" enctype="multipart/form-data">
					<?php
					WC_Admin_Settings::show_messages();

					do_action( 'ivole_settings_display_' . $this->current_tab );
					?>
					<p class="submit">
						<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
							<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
						<?php endif; ?>
						<?php wp_nonce_field( 'ivole-settings' ); ?>
					</p>
				</div>
			</form>
			<?php
			update_option( 'ivole_activation_notice', 0 );
		}

		public function save_settings() {
			if ( $this->is_this_page() && ! empty( $_POST ) ) {
				check_admin_referer( 'ivole-settings' );

				do_action( 'ivole_save_settings_' . $this->current_tab );

				WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );

				//WPML integration
				if ( defined( 'ICL_LANGUAGE_CODE' ) && class_exists( 'CR_WPML' ) ) {
					CR_WPML::translate_admin( $_POST );
				}
			}
		}

		public function include_scripts() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'cr-reviews-settings' ) {
				wp_enqueue_script( 'cr-admin-settings', plugins_url('js/admin-settings.js', dirname( dirname( __FILE__ ) ) ), array(), Ivole::CR_VERSION, false );
			}

			if ( $this->is_this_page() ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'ivole-color-picker', plugins_url('js/admin-color-picker.js', dirname( dirname( __FILE__ ) ) ), array( 'wp-color-picker' ), false, true );
			}
		}

		public function filter_woocommerce_screen_ids( $screen_ids ) {
			$reviews_screen_id = sanitize_title( __( 'Reviews', 'customer-reviews-woocommerce' ) . Ivole_Reviews_Admin_Menu::$screen_id_bubble );
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-settings';
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-diagnostics';
			$screen_ids[] = $reviews_screen_id . '_page_cr-reviews-product-feed';
			return $screen_ids;
		}

		public function is_this_page() {
			return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
		}

		public function get_current_tab() {
			return $this->current_tab;
		}

		/**
		* Custom field type for categories
		*/
		public function show_cselect( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];

			$args = array(
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'id=>name'
			);

			if ( $value['id'] == 'ivole_enabled_categories' || $value['id'] == 'ivole_coupon__product_categories' || $value['id'] == 'ivole_coupon__excluded_product_categories' ) {
				$all_options = get_terms('product_cat', $args);
				// WPML filters product categories by the current language, the code below unfilters them
				if ( has_filter( 'wpml_current_language' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
					$current_lang = apply_filters( 'wpml_current_language', NULL );
					$languages = apply_filters( 'wpml_active_languages', NULL );
					if ( !empty( $languages ) ) {
						foreach( $languages as $l_key => $l_value ) {
							do_action( 'wpml_switch_language', $l_key );
							$all_options_wpml = get_terms('product_cat', $args);
							$all_options = $all_options + $all_options_wpml;
						}
					}
					do_action( 'wpml_switch_language', $current_lang );
				}
				$ph = 'categories';
				$label = 'Category';
			} elseif ($value['id'] == 'ivole_enabled_roles' || $value['id'] == 'ivole_coupon_enabled_roles') {
				global $wp_roles;
				$all_options = $wp_roles->get_names();
				$ph = 'user roles';
				$label = 'Role';
			}

			$selections = (array) WC_Admin_Settings::get_option( $value['id'] );
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp">
					<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="min-width:350px;"  data-placeholder="<?php esc_attr_e( 'Choose '.$ph.'&hellip;', 'customer-reviews-woocommerce' ); ?>" aria-label="<?php esc_attr_e( $label, 'customer-reviews-woocommerce' ) ?>" class="wc-enhanced-select">
						<option value="" selected="selected"></option>
						<?php
						if ( ! empty( $all_options ) ) {
							foreach ( $all_options as $key => $val ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ) . '>' . $val . '</option>';
							}
						}
						?>
					</select>
					<?php echo ( $description ) ? $description : ''; ?>
					<br />
					<a class="select_all button" href="#"><?php _e( 'Select all', 'customer-reviews-woocommerce' ); ?></a>
					<a class="select_none button" href="#"><?php _e( 'Select none', 'customer-reviews-woocommerce' ); ?></a>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for body email
		*/
		public function show_htmltext( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$variables = $tmp['variables'];
			$default_text = $tmp['default'];

			$body = wp_kses_post( WC_Admin_Settings::get_option( $value['id'], $default_text ) );
			$settings = array (
				'teeny' => true,
				'editor_css' => '<style>#wp-ivole_email_body-wrap {max-width: 700px !important;}</style>',
				'textarea_rows' => 20
			);
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<?php echo $description; ?>
					<?php wp_editor( $body, esc_attr( $value['id'] ), $settings );
					echo '<div">';
					echo '<p style="font-weight:bold;margin-top:1.5em;font-size=1em;">' . __( 'Variables', 'customer-reviews-woocommerce' ) . '</p>';
					echo '<p>' . __( 'You can use the following variables in the email template:', 'customer-reviews-woocommerce' ) . '</p>';
					if( 0 < count( $variables ) ) {
						foreach ($variables as $key => $value) {
							echo '<p>' . $value . '</p>';
						}
					}
					echo '</div>';
					?>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for email test
		*/
		public function show_emailtest( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			$coupon_class = '';

			if( false !== strpos( $value['class'], 'coupon_mail' )  ) {
				$coupon_class='coupon_mail';
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>" />
					<?php echo $description; ?>
					<input
					type="button"
					id="ivole_test_email_button"
					value="Send Test"
					class="cr-test-email-button button-primary <?php echo $coupon_class; ?>" />
					<p id="ivole_test_email_status" style="font-style:italic;visibility:hidden;"></p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for license status
		*/
		public function show_license_status( $value ) {
			$tmp = Ivole_Admin::cr_get_field_description( $value );
			$tooltip_html = $tmp['tooltip_html'];
			$description = $tmp['description'];
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tooltip_html; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="text"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					readonly />
					<?php echo $description; ?>
					<p id="ivole_test_email_status" style="font-style:italic;visibility:hidden;">A</p>
				</td>
			</tr>
			<?php
		}

		/**
		* Custom field type for saving body email
		*/
		public function save_htmltext( $value, $option, $raw_value ) {
			return wp_kses_post( $raw_value );
		}

		/**
		* Custom field type for categories
		*/
		public function save_cselect( $value, $option, $raw_value ) {
			if( is_array( $value ) ) {
				$value = array_filter( $value, function($v){ return $v != ""; } );
			} else {
				$value = array();
			}
			return $value;
		}

		/**
		* Function that sends testing email
		*/
		public function send_test_email() {
			global $q_config;

			$email = strval( $_POST['email'] );
			$q_language = $_POST['q_language'];
			//integration with qTranslate
			if ( $q_language >= 0 ) {
				$q_config['language'] = $q_language;
			}

			if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$shop_name = Ivole_Email::get_blogname();
				// check that shop name field (blog name) is not empty
				if ( strlen( $shop_name ) > 0 ) {
					if( isset( $_POST['type'] ) ) {

						switch( $_POST['type'] ) {
							case 'review_reminder':
								$e = new Ivole_Email();
								$result = $e->trigger2( null, $email );
								break;
							case 'qna_reply':
								$qe = new CR_Qna_Email( $_POST['type'] );
								$result = $qe->send_test( $email );
								break;
							default:
								break;
						}

					}

					if ( is_array( $result ) && count( $result)  > 1 && 2 === $result[0] ) {
						wp_send_json( array( 'code' => 2, 'message' => $result[1] ) );
					} elseif( is_array( $result ) && count( $result)  > 1 && 100 === $result[0] ) {
						wp_send_json( array( 'code' => 100, 'message' => $result[1] ) );
					} elseif( is_array( $result ) && count( $result)  > 1 ) {
						wp_send_json( array( 'code' => $result[0], 'message' => $result[1] ) );
					} elseif ( 0 === $result ) {
						wp_send_json( array( 'code' => 0, 'message' => '' ) );
					} elseif ( 1 === $result ) {
						wp_send_json( array( 'code' => 1, 'message' => '' ) );
					} elseif ( 13 === $result ) {
						wp_send_json( array( 'code' => 13, 'message' => '' ) );
					}
				} else {
					wp_send_json( array( 'code' => 97, 'message' => '' ) );
				}
			} else {
				wp_send_json( array( 'code' => 99, 'message' => '' ) );
			}

			wp_send_json( array( 'code' => 98, 'message' => '' ) );
		}

		/**
		* Function to check status of the license
		*/
		public function check_license_ajax() {
			$license = new CR_License();
			$lval = $license->check_license();

			wp_send_json( array( 'message' => $lval['info'] ) );
		}

	}

endif;
