<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Slider' ) ) {

	/**
	* Class for reviews slider shortcode and block.
	*/
	final class CR_Reviews_Slider {

		private static $sort_order_by;
		private static $sort_order;
		private $min_chars;

		/**
		* Constructor.
		*/
		public function __construct() {
			$this->register_shortcode();
			add_action( 'init', array( $this, 'register_slider_script' ) );
			add_action( 'init', array( $this, 'register_block' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_scripts' ) );
		}

		public function register_shortcode() {
			add_shortcode( 'cusrev_reviews_slider', array( $this, 'render_reviews_slider_shortcode' ) );
		}

		/**
		* Register the Reviews Slider Block.
		*/
		public function register_block() {
			if ( function_exists( 'register_block_type' ) ) {
				register_block_type( 'ivole/cusrev-reviews-slider', array(
					'editor_script' => 'ivole-wc-components',

					'editor_style'  => 'ivole-wc-components',

					'attributes' => array(
						'count' => array(
							'type' => 'number',
							'default' => 3,
							'minimum' => 1,
							'maximum' => 6
						),
						'slides_to_show' => array(
							'type' => 'number',
							'default' => 3,
							'minimum' => 1,
							'maximum' => 10
						),
						'show_products' => array(
							'type' => 'boolean',
							'default' => true
						),
						'product_links' => array(
							'type' => 'boolean',
							'default' => true
						),
						'sort_by' => array(
							'type' => 'string',
							'enum' => array( 'date', 'rating' ),
							'default' => 'date'
						),
						'sort' => array(
							'type' => 'string',
							'enum' => array( 'ASC', 'DESC', 'RAND' ),
							'default' => 'DESC'
						),
						'categories' => array(
							'type' => 'array',
							'default' => array(),
							'items' => array(
								'type' => 'integer',
								'minimum' => 1
							)
						),
						'products' => array(
							'type' => 'array',
							'default' => array(),
							'items' => array(
								'type' => 'integer',
								'minimum' => 1
							)
						),
						'product_tags' => array(
							'type' => 'array',
							'default' => array(),
							'items' => array(
								'type' => 'string',
								'minimum' => 1
							)
						),
						'color_ex_brdr' => array(
							'type' => 'string',
							'default' => '#ebebeb'
						),
						'color_brdr' => array(
							'type' => 'string',
							'default' => '#ebebeb'
						),
						'color_bcrd' => array(
							'type' => 'string',
							'default' => '#fbfbfb'
						),
						'color_pr_bcrd' => array(
							'type' => 'string',
							'default' => '#f2f2f2'
						),
						'color_stars' => array(
							'type' => 'string',
							'default' => '#6bba70'
						),
						'shop_reviews' => array(
							'type' => 'boolean',
							'default' => false
						),
						'count_shop_reviews' => array(
							'type' => 'number',
							'default' => 1,
							'minimum' => 0,
							'maximum' => 3
						),
						'inactive_products' => array(
							'type' => 'boolean',
							'default' => false
						),
						'autoplay' => array(
							'type' => 'boolean',
							'default' => false
						),
						'avatars' => array(
							'type' => 'string',
							'enum' => array( 'initials', 'standard', 'false' ),
							'default' => 'initials'
						),
						'show_dots' => array(
							'type' => 'boolean',
							'default' => true
						),
						'max_chars' => array(
							'type' => 'number',
							'default' => 0,
							'minimum' => 0,
							'maximum' => 9999
						),
						'min_chars' => array(
							'type' => 'number',
							'default' => 0,
							'minimum' => 0,
							'maximum' => 9999
						),
					),

					'render_callback' => array( $this, 'render_reviews_slider' )
				));
			}
		}

		public function render_reviews_slider( $attributes ) {
			wp_enqueue_script( 'cr-reviews-slider' );
			$max_reviews = $attributes['count'];
			$order_by = $attributes['sort_by'] === 'date' ? 'comment_date_gmt' : 'rating';
			$order = $attributes['sort'];
			$inactive_products = $attributes['inactive_products'];
			$avatars = 'initials';
			if( isset( $attributes['avatars'] ) ) {
				if( 'false' === $attributes['avatars'] || false == $attributes['avatars'] ) {
					$avatars = false;
				} elseif( 'standard' === $attributes['avatars'] ) {
					$avatars = 'standard';
				}
			}

			$post_ids = $attributes['products'];

			// add products if product tags were provided
			if( ! empty( $attributes['product_tags'] ) ) {
				$products = CR_Reviews_Slider::cr_products_by_tags( $attributes['product_tags'] );
				$post_ids = array_merge($post_ids, $products);
			}

			// add products if product categories were provided
			if ( count( $attributes['categories'] ) > 0 ) {
				$post_ids = get_posts(
					array(
						'post_type' => 'product',
						'posts_per_page' => -1,
						'fields' => 'ids',
						'post__in' => $attributes['products'],
						'tax_query' => array(
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $attributes['categories']
							),
						)
					)
				);
			}

			if( 0 >= count( $post_ids ) && ( ! empty( $attributes['product_tags'] ) || count( $attributes['categories'] ) > 0 ) ) {
				$post_ids = array(-1);
			}

			$args = array(
				'status'      => 'approve',
				'post_type'   => 'product',
				'meta_key'    => 'rating',
				'orderby'     => $order_by,
				'post__in'    => $post_ids
			);

			if( !$inactive_products ) {
				$args['post_status'] = 'publish';
			}

			$reviews = [];
			// Query needs to be modified if min_chars constraints are set
			if ( ! empty( $attributes['min_chars'] ) ) {
				$this->min_chars = $attributes['min_chars'];
				add_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );
			}
			if( 'RAND' === $order ) {
				$all_product_reviews = get_comments( $args );
				$count_all_product_reviews = count( $all_product_reviews );
				if( 0 < $count_all_product_reviews ) {
					$max_reviews = ( $count_all_product_reviews < $max_reviews ) ? $count_all_product_reviews : $max_reviews;
					$random_keys = array_rand( $all_product_reviews, $max_reviews );
					if( is_array( $random_keys ) ) {
						for( $i = 0; $i < $max_reviews; $i++ ) {
							$reviews[] = $all_product_reviews[$random_keys[$i]];
						}
					} else {
						$reviews[] = $all_product_reviews[$random_keys];
					}
				}
			} else {
				if( 0 < $max_reviews ) {
					$args['order'] = $order;
					$args['number'] = $max_reviews;
					$reviews = get_comments( $args );
				}
			}

			$shop_page_id = wc_get_page_id( 'shop' );
			if( true === $attributes['shop_reviews'] ) {
				$max_shop_reviews = $attributes['count_shop_reviews'];
				if( $shop_page_id > 0 && $max_shop_reviews > 0 ) {
					$args_s = array(
						'status'      => 'approve',
						'post_status' => 'publish',
						'post_id'			=> $shop_page_id,
						'meta_key'    => 'rating',
						'orderby'     => $order_by
					);
					$shop_reviews = [];
					if( 'RAND' === $order ) {
						$all_shop_reviews = get_comments( $args_s );
						$count_all_shop_reviews = count( $all_shop_reviews );
						if( 0 < $count_all_shop_reviews ) {
							$max_shop_reviews = ( $count_all_shop_reviews < $max_shop_reviews ) ? $count_all_shop_reviews : $max_shop_reviews;
							$random_keys = array_rand( $all_shop_reviews, $max_shop_reviews );
							if( is_array( $random_keys ) ) {
								for( $i = 0; $i < $max_shop_reviews; $i++ ) {
									$shop_reviews[] = $all_shop_reviews[$random_keys[$i]];
								}
							} else {
								$shop_reviews[] = $all_shop_reviews[$random_keys];
							}
						}
					} else {
						if( 0 < $max_shop_reviews ) {
							$args_s['order'] = $order;
							$args_s['number'] = $max_shop_reviews;
							$shop_reviews = get_comments( $args_s );
						}
					}

					if( is_array( $reviews ) && is_array( $shop_reviews ) ) {
						$reviews = array_merge( $reviews, $shop_reviews );
						CR_Reviews_Slider::$sort_order_by = $order_by;
						CR_Reviews_Slider::$sort_order = $order;
						usort( $reviews, array( "CR_Reviews_Slider", "compare_dates_sort" ) );
					}
				}
			}
			remove_filter( 'comments_clauses', array( $this, 'min_chars_comments_clauses' ) );

			$num_reviews = count( $reviews );

			if ( $num_reviews < 1 ) {
				return __( 'No reviews to show', 'customer-reviews-woocommerce' );
			}

			$show_products = $attributes['show_products'];
			$product_links = $attributes['product_links'];

			$cr_verified_label = get_option( 'ivole_verified_owner', '' );
			if( $cr_verified_label ) {
				$verified_text = esc_html( $cr_verified_label );
			} else {
				$verified_text = esc_html__( 'verified owner', 'customer-reviews-woocommerce' );
			}

			$verified_reviews_enabled = false;
			if ( 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ) {
				$verified_reviews_enabled = true;
			}
			$country_enabled = ('yes' === get_option( 'ivole_form_geolocation', 'no' ) ? true : false);

			$badge_link = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/p/p-%s/r-%s';
			$badge = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', dirname( dirname( __FILE__ ) ) ) . '" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
			$badge .= '<span class="ivole-verified-badge-text">';
			$badge .= __( 'Verified review', 'customer-reviews-woocommerce' );
			$badge .= ' - <a href="' . $badge_link . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
			$badge .= '</span></p>';

			$badge_link_sr = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/s/r-%s';
			$badge_sr = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', dirname( dirname( __FILE__ ) ) ) . '" alt="' . __( 'Verified review', 'customer-reviews-woocommerce' ) . '" class="ivole-verified-badge-icon">';
			$badge_sr .= '<span class="ivole-verified-badge-text">';
			$badge_sr .= __( 'Verified review', 'customer-reviews-woocommerce' );
			$badge_sr .= ' - <a href="' . $badge_link_sr . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', 'customer-reviews-woocommerce' ) . '</a>';
			$badge_sr .= '</span></p>';

			$section_style = "border-color:" . $attributes['color_ex_brdr'] . ";";
			if ( ! empty( $attributes['color_ex_bcrd'] ) ) {
				$section_style .= "background-color:" . $attributes['color_ex_bcrd'] . ";";
			}
			$card_style = "border-color:" . $attributes['color_brdr'] . ";";
			$card_style .= "background-color:" . $attributes['color_bcrd'] . ";";
			$product_style = "background-color:" . $attributes['color_pr_bcrd'] . ";";
			$stars_style = "color:" . $attributes['color_stars'] . ";";
			$max_chars = $attributes['max_chars'];
			$responsive_slides_to_show = $attributes['slides_to_show'] > 1 ? 2 : 1;

			// slider settings for JS
			$slider_settings = array(
				'infinite'          => true,
				'dots'              => $attributes['show_dots'],
				'slidesToShow'      => $attributes['slides_to_show'],
				'slidesToScroll'    => 1,
				'adaptiveHeight'    => true,
				'autoplay'          => $attributes['autoplay'],
				'responsive'        => array(
					array(
						'breakpoint'    => 800,
						'settings'      => array(
							'slidesToShow'   => $responsive_slides_to_show
						)
					),
					array(
						'breakpoint'    => 650,
						'settings'      => array(
							'slidesToShow'   => 1
						)
					),
					array(
						'breakpoint'    => 450,
						'settings'      => array(
							'arrows'				 => false,
							'slidesToShow'   => 1
						)
					)
				)
			);

			$id = uniqid( 'cr-reviews-slider-' );

			$template = wc_locate_template(
				'reviews-slider.php',
				'customer-reviews-woocommerce',
				dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
			);

			if( 'initials' === $attributes['avatars'] ) {
				add_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ), 10, 5 );
			}

			ob_start();
			include( $template );

			if( 'initials' === $attributes['avatars'] ) {
				remove_filter( 'get_avatar', array( 'CR_Reviews_Grid', 'cr_get_avatar' ) );
			}

			return ob_get_clean();
		}

		public function render_reviews_slider_shortcode( $attributes ) {
			$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
			if( $shortcode_enabled === 'no' ) {
				return;
			} else {
				// Convert shortcode attributes
				$attributes = shortcode_atts( array(
					'slides_to_show' => 3,
					'count' => 5,
					'show_products' => true,
					'product_links' => true,
					'sort_by' => 'date',
					'sort' => 'DESC',
					'categories' => array(),
					'products' => array(),
					'color_ex_brdr' => '#ebebeb',
					'color_brdr' => '#ebebeb',
					'color_ex_bcrd' => '',
					'color_bcrd' => '#fbfbfb',
					'color_pr_bcrd' => '#f2f2f2',
					'color_stars' => '#6bba70',
					'shop_reviews' => 'false',
					'count_shop_reviews' => 1,
					'inactive_products' => false,
					'autoplay' => false,
					'avatars' => 'initials',
					'max_chars' => 0,
					'product_tags' => array(),
					'min_chars' => 0,
					'show_dots' => true,
				), $attributes, 'cusrev_reviews_slider' );

				$attributes['slides_to_shows'] = absint( $attributes['slides_to_show'] ) >= absint( $attributes['count'] ) ? absint( $attributes['count'] ) : absint( $attributes['slides_to_show'] );
				$attributes['count'] = absint( $attributes['count'] );
				$attributes['show_products'] = ( $attributes['show_products'] !== 'false' && boolval( $attributes['count'] ) );
				$attributes['product_links'] = ( $attributes['product_links'] !== 'false' );
				$attributes['shop_reviews'] = ( $attributes['shop_reviews'] !== 'false' && boolval( $attributes['count_shop_reviews'] ) );
				$attributes['count_shop_reviews'] = absint( $attributes['count_shop_reviews'] );
				$attributes['inactive_products'] = ( $attributes['inactive_products'] === 'true' );
				$attributes['autoplay'] = ( $attributes['autoplay'] === 'true' );
				$attributes['max_chars'] = absint( $attributes['max_chars'] );
				$attributes['min_chars'] = intval( $attributes['min_chars'] );
				$attributes['show_dots'] = ( $attributes['show_dots'] !== 'false' );
				if( $attributes['min_chars'] < 0 ) {
					$attributes['min_chars'] = 0;
				}

				if ( ! is_array( $attributes['categories'] ) ) {
					$attributes['categories'] = array_filter( array_map( 'trim', explode( ',', $attributes['categories'] ) ) );
				}

				if ( ! is_array( $attributes['products'] ) ) {
					$attributes['products'] = array_filter( array_map( 'trim', explode( ',', $attributes['products'] ) ) );
				}

				if( $attributes['slides_to_shows'] <= 0 ) {
					$attributes['slides_to_shows'] = 1;
				}

				if( ! empty( $attributes['product_tags'] ) ) {
					$attributes['product_tags'] = array_filter( array_map( 'trim', explode( ',', $attributes['product_tags'] ) ) );
				}

				return $this->render_reviews_slider( $attributes );
			}
		}

		public function register_slider_script() {
			wp_register_script(
				'cr-reviews-slider',
				plugins_url( 'js/slick.min.js', dirname( dirname( __FILE__ ) ) ),
				array( 'jquery' ),
				'3.119',
				true
			);
		}

		public function enqueue_block_editor_scripts(){
			wp_enqueue_script( 'cr-reviews-slider' );
		}

		private static function compare_dates_sort( $a, $b ) {
			if( 'rating' === CR_Reviews_Slider::$sort_order_by ) {
				$rating1 = intval( get_comment_meta( $a->comment_ID, 'rating', true ) );
				$rating2 = intval( get_comment_meta( $b->comment_ID, 'rating', true ) );
				if( 'ASC' === CR_Reviews_Slider::$sort_order ) {
					return $rating1 - $rating2;
				} elseif( 'RAND' === CR_Reviews_Slider::$sort_order ) {
					return rand( -1, 1 );
				} else {
					return $rating2 - $rating1;
				}
			} else {
				$datetime1 = strtotime( $a->comment_date );
				$datetime2 = strtotime( $b->comment_date );
				if( 'ASC' === CR_Reviews_Slider::$sort_order ) {
					return $datetime1 - $datetime2;
				}  elseif( 'RAND' === CR_Reviews_Slider::$sort_order ) {
					return rand( -1, 1 );
				} else {
					return $datetime2 - $datetime1;
				}
			}
		}

		public static function cr_products_by_tags( $tags ) {
			if( 0 < count( $tags ) ) {
				$args = array(
					'tag' => $tags,
					'status' => 'publish',
					'return' => 'ids',
				);
				$products = wc_get_products( $args );
				return $products;
			} else {
				return array();
			}
		}

		public function min_chars_comments_clauses( $clauses ) {
			global $wpdb;

			$clauses['where'] .= " AND CHAR_LENGTH({$wpdb->comments}.comment_content) >= " . $this->min_chars;

			return $clauses;
		}

	}

}
