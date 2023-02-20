<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Local_Forms' ) ) :

	class CR_Local_Forms {
		private $form_id;
		private $items;
		private $customer_email;
		private $customer_name;
		private $display_name;
		private $form_header;
		private $form_body;
		private $cr_form_color1;
		private $cr_form_color2;
		private $cr_form_color3;

		private const HEADER_TEMPLATE = 'form-header.php';
		private const ITEM_BLOCK_TEMPLATE = 'form-block-item.php';
		private const CUSTOMER_TEMPLATE = 'form-customer.php';
		private const FOOTER_TEMPLATE = 'form-footer.php';
		public const FORMS_TABLE = 'cr_local_forms';
		public const FORMS_SLUG = 'cusrev';
		public const TEST_FORM = 'test';

		public function __construct( $id ) {
			$this->form_id = $id;

			global $wpdb;
			$table_name = $wpdb->prefix . self::FORMS_TABLE;
			$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE `formId` = %s", $this->form_id ) );
			if( null !== $record ) {
				$this->customer_email = $record->customerEmail;
				$this->customer_name = trim( $record->customerName );
				$this->display_name = $record->displayName;
				$this->form_header = $record->formHeader;
				$this->form_body = $record->formBody;
				$this->items = json_decode( $record->items );
				$this->cr_form_color1 = get_option( 'ivole_form_color_bg', '#2C5E66' );
				$this->cr_form_color2 = get_option( 'ivole_form_color_text', '#FFFFFF' );
				$this->cr_form_color3 = get_option( 'ivole_form_color_el', '#1AB394' );
				// delete media files uploaded with test reviews
				if( self::TEST_FORM === $this->form_id ) {
					foreach( $this->items as $key => $item ) {
						if( property_exists( $item, 'media' ) ) {
							if( $item->media && is_array( $item->media ) ) {
								foreach( $item->media as $media ) {
									$attachmentId = intval( $media );
									if( 'attachment' === get_post_type( $attachmentId ) ) {
										wp_delete_attachment( $attachmentId, true );
									}
								}
							}
							$this->items[$key]->media = [];
						}
					}
					$db_items = json_encode( $this->items );
					$update_result = $wpdb->update( $wpdb->prefix . self::FORMS_TABLE, array(
						'items' => $db_items
					), array( 'formId' => $this->form_id ) );
				}
			} else {
				$this->form_id = 0;
			}
		}

		public function output() {
			if( is_array( $this->items ) && 0 < count( $this->items ) ) {
				$this->form_header();
				foreach( $this->items as $item ) {
					$this->form_block( $item );
				}
				$this->customer_block();
				$this->form_footer();
			}
		}

		private function form_header() {
			$template = wc_locate_template(
				self::HEADER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$cr_form_css = plugins_url( '/css/form.css', dirname( dirname( __FILE__ ) ) ) . '?ver=' . Ivole::CR_VERSION;
			$cr_form_js = plugins_url( '/js/form.js', dirname( dirname( __FILE__ ) ) ) . '?ver=' . Ivole::CR_VERSION;
			$cr_form_id = $this->form_id;
			$cr_form_header = $this->form_header;
			$cr_form_desc = $this->form_body;
			$cr_form_required = __( '* Required', 'customer-reviews-woocommerce' );
			$cr_form_ajax = admin_url( 'admin-ajax.php' );
			$cr_form_subm_header = __( 'Thank you for submitting a review!', 'customer-reviews-woocommerce' );
			$cr_form_subm_desc = __( 'Your response has been recorded.', 'customer-reviews-woocommerce' );
			$cr_form_edit_label = __( 'Edit your review', 'customer-reviews-woocommerce' );
			$cr_form_edit = $this->display_name ? true : false;
			$cr_form_color1 = $this->cr_form_color1;
			$cr_form_color2 = $this->cr_form_color2;
			$cr_form_color3 = $this->cr_form_color3;
			$cr_form_media_upload_limit = get_option( 'ivole_attach_image_quantity', 5 );
			$cr_form_media_upload_max_size = 1024 * 1024 * get_option( 'ivole_attach_image_size', 25 );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function form_block( $item ) {
			$template = wc_locate_template(
				self::ITEM_BLOCK_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';

			$cr_form_item_name = $item->name;
			$cr_form_item_comment_name = __( 'Comment', 'customer-reviews-woocommerce' );
			$cr_form_item_comment_placeholder = __( 'Your comment', 'customer-reviews-woocommerce' );
			$cr_form_item_id = $item->id;
			$cr_form_item_media_array = array();
			if( -1 === $item->id ) {
				// special case - store item
				$cr_form_item_rating_name = __( 'Rate website, customer service and delivery', 'customer-reviews-woocommerce' );
				$cr_form_item_image = '';
				$cr_form_item_price = '';
				$cr_form_media_enabled = false;
			} else {
				// otherwise product item
				$cr_form_item_rating_name = __( 'Rating', 'customer-reviews-woocommerce' );
				$cr_form_item_image = $item->image;
				$cr_form_item_price = CR_Email_Func::cr_price( $item->price );
				$cr_form_media_enabled = ( 'yes' === get_option( 'ivole_form_attach_media', 'no' ) ? true : false );
				if( property_exists( $item, 'media' ) ) {
					if( is_array(  $item->media ) ) {
						foreach( $item->media as $m_item ) {
							if( false !== get_post_type( $m_item ) ) {
								$cr_form_item_media_array[] = array(
									'url' => wp_get_attachment_url( $m_item ),
									'id' => (int) $m_item,
									'key' => get_post_meta( $m_item, 'cr-upload-temp-key', true )
								);
							}
						}
					}
				}
			}
			$cr_form_item_comment_req = 'yes' === get_option( 'ivole_form_comment_required', 'no' ) ? true : false;
			$cr_form_rating = ( isset( $item->rating ) && ( 1 <= $item->rating && 5 >= $item->rating ) ) ? intval( $item->rating ) : 0;
			$cr_form_comment = isset( $item->comment ) ? $item->comment : '';
			$cr_form_item_media_name = __( 'Upload Photos/Video', 'customer-reviews-woocommerce' );
			$cr_form_item_media_desc = __( 'Add photos or video to your review', 'customer-reviews-woocommerce' );
			$cr_form_media_upload_limit = get_option( 'ivole_attach_image_quantity', 5 );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function customer_block() {
			$template = wc_locate_template(
				self::CUSTOMER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$cr_form_cust_title = __( 'Display name', 'customer-reviews-woocommerce' );
			$cr_form_cust_name = $this->customer_name;
			$cr_form_cust_preview_name = $this->display_name ? $this->display_name : $this->customer_name;
			$cr_form_cust_name_w_dot = '';
			$cr_form_cust_f_name = '';
			if( strpos( $this->customer_name, ' ' ) !== false ) {
				$parts = explode( ' ', $this->customer_name );
				if( count( $parts ) > 1 ) {
					$lastname  = array_pop( $parts );
					$firstname = $parts[0];
					$cr_form_cust_name_w_dot = $firstname . ' ' . mb_substr( $lastname, 0, 1 ) . '.';
					$cr_form_cust_f_name = $firstname;
				}
			}
			$cr_form_cust_anonymous = __( 'Anonymous', 'customer-reviews-woocommerce' );
			$wc_terms_page = wc_get_page_id( 'terms' );
			if( $wc_terms_page ) {
				$wc_terms_page = get_permalink( $wc_terms_page );
			} else {
				$wc_terms_page = '';
			}
			$cr_form_terms = sprintf( __( 'By submitting your review, you agree to the <a href="%s" target="_blank" rel="noopener noreferrer">terms and conditions</a>.', 'customer-reviews-woocommerce' ), esc_url( $wc_terms_page ) );
			$cr_form_submit = __( 'Submit', 'customer-reviews-woocommerce' );
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		private function form_footer() {
			$template = wc_locate_template(
				self::FOOTER_TEMPLATE,
				'customer-reviews-woocommerce',
				__DIR__ . '/../../templates/'
			);
			$output = '';
			$home_url = '<a href="' . esc_url( get_home_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
			$cusrev_url = '<a href="https://wordpress.org/plugins/customer-reviews-woocommerce/" rel="noindex nofollow">CusRev</a>';
			$cr_form_footer = '';
			if( 'no' === get_option( 'ivole_reviews_nobranding', 'yes' ) ) {
				$cr_form_footer = sprintf( __( 'This form was created by %1$s using %2$s plugin.', 'customer-reviews-woocommerce' ), $home_url, $cusrev_url );
			} else {
				$cr_form_footer = sprintf( __( 'This form was created by %1$s.', 'customer-reviews-woocommerce' ), $home_url );
			}
			ob_start();
			include( $template );
			$output = ob_get_clean();
			echo $output;
		}

		public static function save_form( $orderId, $customerName, $customerEmail, $header, $body, $items, $is_test ) {
			// check if the table exists
			global $wpdb;
			$table_name = $wpdb->prefix . self::FORMS_TABLE;
			if( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
				if( true !== $wpdb->query(
						"CREATE TABLE `$table_name` (
							`formId` varchar(190),
							`orderId` varchar(190) DEFAULT NULL,
							`customerEmail` varchar(255) DEFAULT NULL,
							`customerName` varchar(255) DEFAULT NULL,
							`displayName` varchar(255) DEFAULT NULL,
							`formHeader` varchar(1024) DEFAULT NULL,
							`formBody` varchar(1024) DEFAULT NULL,
							`items` json DEFAULT NULL,
							PRIMARY KEY (`formId`),
							KEY `orderId_index` (`orderId`)
						);" ) ) {
					// it is possible that Maria DB is used that does not support JSON type
					if( true !== $wpdb->query(
							"CREATE TABLE `$table_name` (
								`formId` varchar(190),
								`orderId` varchar(190) DEFAULT NULL,
								`customerEmail` varchar(255) DEFAULT NULL,
								`customerName` varchar(255) DEFAULT NULL,
								`displayName` varchar(255) DEFAULT NULL,
								`formHeader` varchar(1024) DEFAULT NULL,
								`formBody` varchar(1024) DEFAULT NULL,
								`items` text DEFAULT NULL,
								PRIMARY KEY (`formId`),
								KEY `orderId_index` (`orderId`)
							);" ) ) {
						return array( 'code' => 1, 'text' => 'Table ' . $table_name . ' could not be created' );
					}
				}
			}

			// if store reviews are enabed, add a special item to the items array
			if( 'yes' === get_option( 'ivole_form_shop_rating', 'no' ) ) {
				$store_item = array( 'id' => -1, 'name' => Ivole_Email::get_blogname() );
				array_unshift( $items, $store_item );
			}

			if( $is_test ) {
				$formId = self::TEST_FORM;
			} else {
				// generate unique form id
				$formId = strtolower( uniqid() );
			}

			// insert data
			$res = $wpdb->replace( $table_name, array(
				'formId' => $formId,
				'orderId' => $orderId,
				'customerEmail' => $customerEmail,
				'customerName' => $customerName,
				'formHeader' => $header,
				'formBody' => $body,
				'items' => json_encode( $items )
			) );
			if( false !== $res ) {
				return array( 'code' => 0, 'text' => get_home_url() . '/' . self::FORMS_SLUG . '/' . $formId );
			} else {
				return array( 'code' => 2, 'text' => 'Form \'' . $formId . '\' could not be saved to the table \'' . $table_name . '\'' );
			}
		}

	}

endif;
