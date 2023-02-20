<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Attributes_Product_Feed' ) ):

	class CR_Attributes_Product_Feed {

		/**
		* @var CR_Product_Feed_Admin_Menu The instance of the admin menu
		*/
		protected $product_feed_menu;

		/**
		* @var string The slug of this tab
		*/
		protected $tab;

		/**
		* @var array The fields for this tab
		*/
		protected $settings;

		protected $alternate = false;

		public function __construct( $product_feed_menu ) {
			$this->product_feed_menu = $product_feed_menu;

			$this->tab = 'attributes';

			add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
			add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
			add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_product_feed_attributes', array( $this, 'display_product_feed_attributes' ) );
		}

		public function register_tab( $tabs ) {
			$tabs[$this->tab] = __( 'Product Attributes', 'customer-reviews-woocommerce' );
			return $tabs;
		}

		public function display() {
			$this->init_settings();
			WC_Admin_Settings::output_fields( $this->settings );
		}

		public function save() {
			$this->init_settings();
			$product_fields = array();
			if ( ! empty( $_POST ) ) {
				if ( isset( $_POST['cr_google_attribute_age_group'] ) ) {
					$product_fields['age_group'] = $_POST['cr_google_attribute_age_group'];
				}
				if( isset( $_POST['cr_google_attribute_color'] ) ) {
					$product_fields['color'] = $_POST['cr_google_attribute_color'];
				}
				if( isset( $_POST['cr_google_attribute_gender'] ) ) {
					$product_fields['gender'] = $_POST['cr_google_attribute_gender'];
				}
			}
			$_POST['ivole_product_feed_attributes'] = $product_fields;
			WC_Admin_Settings::save_fields( $this->settings );

			$feed = new CR_Google_Shopping_Prod_Feed();
			if ( $feed->is_enabled() ) {
				$feed->activate();
			} else {
				$feed->deactivate();
			}

			$feed_reviews = new CR_Google_Shopping_Feed();
			if ( $feed_reviews->is_enabled() ) {
				$feed_reviews->activate();
			} else {
				$feed_reviews->deactivate();
			}
		}

		protected function init_settings() {
			$this->settings = array(
				array(
					'title' => __( 'Product Attributes', 'customer-reviews-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Specify mapping of WooCommerce product fields to Google Shopping product attributes that might be required in certain countries for some product categories.', 'customer-reviews-woocommerce' ),
					'id'    => 'cr_product_attributes'
				),
				array(
					'id'       => 'ivole_product_feed_attributes',
					'type'     => 'product_feed_attributes'
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cr_product_attributes'
				)
			);
		}

		public function is_this_tab() {
			return $this->product_feed_menu->is_this_page() && ( $this->product_feed_menu->get_current_tab() === $this->tab );
		}

		public function display_product_feed_attributes( $option ) {
			if( !$option['value'] ) {
				$option['value'] = array(
					'age_group'		=> '',
					'color'			=> '',
					'gender'		=> '',
				);
			}
			$list_fields = $this->get_product_attributes();
			?>
			<tr valign="top">
				<td colspan="2" style="padding-left:0px;padding-right:0px;">
					<table class="cr-product-feed-categories widefat">
						<thead>
							<tr>
								<th class="cr-product-feed-categories-th">
									<?php
									esc_html_e( 'WooCommerce Product Field', 'customer-reviews-woocommerce' );
									echo Ivole_Admin::ivole_wc_help_tip( __( 'Select a product field that should be mapped', 'customer-reviews-woocommerce' ) );
									?>
								</th>
								<th class="cr-product-feed-categories-th">
									<?php
									esc_html_e( 'Google Shopping Attribute', 'customer-reviews-woocommerce' );
									echo Ivole_Admin::ivole_wc_help_tip( __( 'Product attributes required by Google Shopping in some countries', 'customer-reviews-woocommerce' ) );
									?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_google_attribute_age_group">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['age_group'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">age_group</td>
							</tr>
							<tr class="cr-alternate">
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_google_attribute_color">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['color'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">color</td>
							</tr>
							<tr>
								<td class="cr-product-feed-categories-td">
									<select class="cr-product-feed-identifiers-select" name="cr_google_attribute_gender">
										<option></option>
										<?php foreach ( $list_fields as $attribute_value => $attribute_name ): ?>
											<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $option['value']['gender'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="cr-product-feed-categories-td">gender</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		protected function get_product_attributes() {
			global $wpdb;

			$product_attributes = array(
				'product_id'   => __( 'Product ID', 'customer-reviews-woocommerce' ),
				'product_sku'  => __( 'Product SKU', 'customer-reviews-woocommerce' ),
				'product_name' => __( 'Product Name', 'customer-reviews-woocommerce' )
			);

			$product_attributes = array_reduce( wc_get_attribute_taxonomies(), function( $attributes, $taxonomy ) {
				$key = 'attribute_' . $taxonomy->attribute_name;
				$attributes[$key] = ucfirst( $taxonomy->attribute_label );

				return $attributes;
			}, $product_attributes );

			$meta_attributes = $wpdb->get_results(
				"SELECT meta.meta_id, meta.meta_key, meta.meta_value
				FROM {$wpdb->postmeta} AS meta, {$wpdb->posts} AS posts
				WHERE meta.post_id = posts.ID AND posts.post_type LIKE '%product%' AND (
					meta.meta_key NOT LIKE '\_%'
					OR meta.meta_key LIKE '\_woosea%'
					OR meta.meta_key LIKE '\_wpm%' OR meta.meta_key LIKE '\_cr_%' OR meta.meta_key LIKE '\_cpf_%'
					OR meta.meta_key LIKE '\_yoast%'
					OR meta.meta_key = '_product_attributes'
				)
				GROUP BY meta.post_id, meta.meta_key",
				ARRAY_A
			);

			if ( is_array( $meta_attributes ) ) {
				$product_attributes = array_reduce( $meta_attributes, function( $attributes, $meta_attribute ) {

					// If the meta entry is _product_attributes, then consider each attribute spearately
					if ( $meta_attribute['meta_key'] === '_product_attributes' ) {

						$attrs = maybe_unserialize( $meta_attribute['meta_value'] );
						if ( is_array( $attrs ) ) {

							foreach ( $attrs as $attr_key => $attr ) {
								$key = 'attribute_' . $attr_key;
								$attributes[$key] = ucfirst( $attr['name'] );
							}

						}

					} else {
						$key = 'meta_' . $meta_attribute['meta_key'];
						$attributes[$key] = ucfirst( str_replace( '_', ' ', $meta_attribute['meta_key'] ) );
					}
					return $attributes;
				}, $product_attributes );
			}
			$product_attributes['meta__cr_gtin'] = __( 'Product GTIN', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_mpn'] = __( 'Product MPN', 'customer-reviews-woocommerce' );
			$product_attributes['meta__cr_brand'] = __( 'Product Brand', 'customer-reviews-woocommerce' );

			$product_attributes['tags_tags'] = __( 'Product Tag', 'customer-reviews-woocommerce' );

			$taxonomies_3rd = array( 'pwb-brand' );
			foreach ($taxonomies_3rd as $taxonomy_3rd) {
				$product_terms = get_terms( array(
					'taxonomy' => $taxonomy_3rd
				) );
				if( $product_terms && !is_wp_error( $product_terms ) ) {
					$product_attributes['terms_' . $taxonomy_3rd] = $taxonomy_3rd;
				}
			}

			natcasesort( $product_attributes );

			return $product_attributes;
		}
	}

endif;
