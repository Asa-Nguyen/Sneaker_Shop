<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('CR_Trust_Badge')) :

	class CR_Trust_Badge
	{

		/**
		* @var array holds the current shorcode attributes
		*/
		public $shortcode_atts;
		protected $lang;

		public function __construct() {
			if ( 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ) {
				$this->register_shortcode();
				$this->lang = CR_Trust_Badge::get_badge_language();
				add_action( 'init', array( 'CR_Reviews_Grid', 'cr_register_blocks_script' ) );
				add_action( 'enqueue_block_assets', array( 'CR_Reviews_Grid', 'cr_enqueue_block_scripts' ) );
				add_action( 'init', array( $this, 'register_block' ) );
			}
		}

		public function register_shortcode() {
			add_shortcode( 'cusrev_trustbadge', array( $this, 'render_trustbadge_shortcode' ) );
		}

		public function render_trustbadge_shortcode( $attributes ) {
			$defaults = array(
				'type' => 'sl',
				'border' => 'yes',
				'color' => ''
			);
			if ( isset( $attributes['type'] ) ) {
				$type = str_replace( ' ', '', $attributes['type'] );
				$type = strtolower( $type );
				$allowed_types = array( 'sl', 'slp', 'sd', 'sdp', 'wl', 'wlp', 'wd', 'wdp', 'vsl', 'vsd' );
				if( in_array( $type, $allowed_types ) ) {
					$attributes['type'] = $type;
				} else {
					$attributes['type'] = null;
				}
			}
			if ( isset( $attributes['border'] ) ) {
				$border = str_replace( ' ', '', $attributes['border'] );
				$border = strtolower( $border );
				$allowed_borders = array( 'yes', 'no' );
				if( in_array( $border, $allowed_borders ) ) {
					$attributes['border'] = $border;
				} else {
					$attributes['border'] = 'yes';
				}
			}
			if ( isset( $attributes['color'] ) ) {
				$color = str_replace( ' ', '', $attributes['color'] );
				$color = strtolower( $color );
				if( preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $color ) ) {
					$attributes['color'] = $color;
				} else {
					$attributes['color'] = '';
				}
			}
			$this->shortcode_atts = shortcode_atts( $defaults, $attributes );
			return $this->show_trust_badge();
		}

		public function show_trust_badge() {
			$storeStats = self::get_store_stats( Ivole_Email::get_blogurl(), false );
			$site_lang = '';
			if( 'en' !== $this->lang ) {
				$site_lang = $this->lang . '/';
			}
			$verified_page = 'https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
			$color = '';
			if( 0 < strlen( $this->shortcode_atts['color'] ) ) {
				$color = '" style="background-color:' . $this->shortcode_atts['color'] . ';';
			}
			$return = '<div class="cr-trustbadgef">';
			$return .= self::show_html_trust_badge( $this->shortcode_atts['type'], $storeStats, $this->shortcode_atts['color'], $this->shortcode_atts['border'], $verified_page );
			$return .= '</div>';
			return $return;
		}

		/**
		* Registers the trustbadge block
		*
		* @since 3.53
		*/
		public static function register_block() {
			// Only register the block if the WP is at least 5.0, or gutenberg is installed.
			if ( function_exists( 'register_block_type' ) ) {
				register_block_type( 'ivole/cusrev-trustbadge', array(
					'attributes' => array(
						'badge_size' => array(
							'type' => 'string',
							'enum' => array( 'small', 'wide', 'compact' ),
							'default' => 'small'
						),
						'badge_style' => array(
							'type' => 'string',
							'enum' => array( 'light', 'dark' ),
							'default' => 'light'
						),
						'store_rating' => array(
							'type' => 'boolean',
							'default' => false
						),
						'badge_border' => array(
							'type' => 'boolean',
							'default' => true
						),
						'badge_color' => array(
							'type' => 'string',
							'default' => '#ffffff'
						)
					),
					'render_callback' => array( self::class, 'render_block' )
				) );
			}
		}

		/**
		* Render the trust_badges block
		*
		* @since 3.53
		*
		* @param array $block_attributes An array of block attributes
		*
		* @return string
		*/
		public static function render_block( $block_attributes ) {
			// If trust badges are not enabled, display nothing.
			if ( get_option( 'ivole_reviews_verified', 'no' ) === 'no' ) {
				return '';
			}

			switch( $block_attributes['badge_size'] ) {
				case 'small':
				$badge_type = 's';
				break;
				case 'wide':
				$badge_type = 'w';
				break;
				case 'compact':
				$badge_type = 'vs';
				$block_attributes['store_rating']  = '';
				break;
				default:
				$badge_type = 's';
				break;
			}
			$badge_type .= $block_attributes['badge_style'] === 'light' ? 'l' : 'd';
			$badge_type .= $block_attributes['store_rating'] ? 'p' : '';

			$badge_border = $block_attributes['badge_border'] ? 'yes': 'no';

			$badge_color = $block_attributes['badge_color'];
			$color = str_replace( ' ', '', $badge_color );
			$color = strtolower( $badge_color );
			if( !preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $color ) ) {
				$color = '';
			}
			$storeStats = self::get_store_stats( Ivole_Email::get_blogurl(), false );
			$site_lang = '';
			$lng = CR_Trust_Badge::get_badge_language();
			if( 'en' !== $lng ) {
				$l_suffix = '-' . $lng;
				$site_lang = $lng . '/';
			}
			$verified_page = 'https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
			$return = '<div class="cr-trustbadgef">';
			$return .= self::show_html_trust_badge( $badge_type, $storeStats, $color, $badge_border, $verified_page );
			$return .= '</div>';
			return $return;
		}

		public static function get_badge_language() {
			$language = 'en';
			$blog_language = get_bloginfo( 'language', 'display' );
			if( is_string( $blog_language ) ) {
				$blog_language = substr( $blog_language, 0, 2 );
				if( 2 === strlen( $blog_language ) ) {
					$language = strtolower( $blog_language );
				}
				// special case for Norwegian language
				if( 'nb' === $language || 'nn' === $language ) {
					$language = 'no';
				}
			}
			return $language;
		}

		public static function show_html_trust_badge( $type, $storeStats, $backgroundColor, $borderStyle, $verifiedPage, $display = true ) {
			$badgeColorClass = '';
			$badgeClass = '';
			$badgeStyle = '';
			$badgeVerified = __( 'Independently verified', 'customer-reviews-woocommerce' );
			$badgeVerifiedW = __( 'independently verified', 'customer-reviews-woocommerce' );
			$separateRatings = false;
			$strAvRating = sprintf( __( '%s rating', 'customer-reviews-woocommerce' ), strval( $storeStats['averageRating'] ) );
			$strAvRatingW = sprintf( __( 'rating %s / 5', 'customer-reviews-woocommerce' ), '<b>' . strval( $storeStats['averageRating'] ) . '</b>' );
			$strStoreRating = sprintf( __( '%s store rating', 'customer-reviews-woocommerce' ), strval( $storeStats['storeRating'] ) );
			$strStoreRatingW = sprintf( __( 'Store rating %s / 5', 'customer-reviews-woocommerce' ), '<b>' . strval( $storeStats['storeRating'] ) . '</b>' );
			$strStoreRatingVS = __( 'store rating', 'customer-reviews-woocommerce' );
			$strProdRating = sprintf( __( '%s product rating', 'customer-reviews-woocommerce' ), strval( $storeStats['productRating'] ) );
			$strProdRatingW = sprintf( __( 'Product rating %s / 5', 'customer-reviews-woocommerce' ), '<b>' . strval( $storeStats['productRating'] ) . '</b>' );
			$strProdRatingVS = __( 'product rating', 'customer-reviews-woocommerce' );
			$strCount = sprintf( _n( '%s review', '%s reviews', $storeStats['totalReviews'], 'customer-reviews-woocommerce' ), $storeStats['totalReviews'] );
			$strCountW = sprintf( _n( '%s review', '%s reviews', $storeStats['totalReviews'], 'customer-reviews-woocommerce' ), '<b>' . $storeStats['totalReviews'] . '</b>' );
			$avRating = self::starsPercentage( $storeStats['averageRating'] );
			$stRating = self::starsPercentage( $storeStats['storeRating'] );
			$prRating = self::starsPercentage( $storeStats['productRating'] );
			$templateFile = '';

			switch( $type ) {
				case 'sl':
					$separateRatings = false;
					$templateFile = 'badge-small.php';
					if( !$backgroundColor ) {
						$backgroundColor = '#FFFFFF';
					}
					if( 'no' !== $borderStyle ) {
						$badgeClass = ' cr-trustbadge-border';
					}
					break;
				case 'slp':
					$separateRatings = true;
					$templateFile = 'badge-small.php';
					if( !$backgroundColor ) {
						$backgroundColor = '#FFFFFF';
					}
					if( 'no' !== $borderStyle ) {
						$badgeClass = ' cr-trustbadge-border';
					}
					break;
				case 'sd':
					$separateRatings = false;
					$templateFile = 'badge-small.php';
					$badgeClass = ' badge_color_dark';
					if( !$backgroundColor ) {
						$backgroundColor = '#3D3D3D';
					}
					if( 'no' !== $borderStyle ) {
						$badgeClass .= ' cr-trustbadge-border';
					}
					break;
				case 'sdp':
					$separateRatings = true;
					$templateFile = 'badge-small.php';
					$badgeClass = ' badge_color_dark';
					if( !$backgroundColor ) {
						$backgroundColor = '#3D3D3D';
					}
					if( 'no' !== $borderStyle ) {
						$badgeClass .= ' cr-trustbadge-border';
					}
					break;
				case 'wl':
					$separateRatings = false;
					$templateFile = 'badge-wide.php';
					if( !$backgroundColor ) {
						$backgroundColor = '#FFFFFF';
					}
					break;
				case 'wlp':
					$separateRatings = true;
					$templateFile = 'badge-wide.php';
					if( !$backgroundColor ) {
						$backgroundColor = '#FFFFFF';
					}
					break;
				case 'wd':
					$separateRatings = false;
					$templateFile = 'badge-wide.php';
					$badgeClass = ' badge_color_dark';
					if( !$backgroundColor ) {
						$backgroundColor = '#003640';
					}
					break;
				case 'wdp':
					$separateRatings = true;
					$templateFile = 'badge-wide.php';
					$badgeClass = ' badge_color_dark';
					if( !$backgroundColor ) {
						$backgroundColor = '#003640';
					}
					break;
				case 'vsl':
					$templateFile = 'badge-wide-vs.php';
					if( !$backgroundColor ) {
						$backgroundColor = '#FFFFFF';
					}
					break;
				case 'vsd':
					$templateFile = 'badge-wide-vs.php';
					$badgeClass = ' badge_color_dark';
					if( !$backgroundColor ) {
						$backgroundColor = '#373737';
					}
					break;
				case 'cl':
					$templateFile = 'compact.php';
					break;
				case 'cwl':
					$templateFile = 'compact-wide.php';
					break;
				case 'cd':
					$templateFile = 'compact.php';
					$badgeClass = ' badge_color_dark';
					break;
				case 'cwd':
					$templateFile = 'compact-wide.php';
					$badgeClass = ' badge_color_dark';
					break;
				default:
					break;
			}
			if( $backgroundColor ) {
				$badgeStyle = 'background-color:' . $backgroundColor . ';';
			}
			if( !$display ) {
				$badgeStyle .= 'display:none;';
			}

			if( $templateFile ) {
				$template = wc_locate_template(
					$templateFile,
					'customer-reviews-woocommerce',
					dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/'
				);
				ob_start();
				include( $template );
				return ob_get_clean();
			} else {
				return '';
			}
		}

		private static function starsPercentage( $rating ) {
			$starsRating = array();
			for( $i=1; $i<6; $i++ ) {
				if( $rating > $i ) {
					$starsRating[] = '100%';
				} elseif ( $rating < $i + 1 ) {
					$starsRating[] = round( max( 100 - ($i - $rating) * 100, 0 ) ) . '%';
				}
			}
			return $starsRating;
		}

		public static function get_store_stats( $domain, $refresh ) {
			$stats = null;
			$cached_stats = get_option( 'ivole_store_stats', null );
			if( $refresh || !$cached_stats ) {
				$response = wp_remote_get( 'https://api.cusrev.com/v1/production/store-stats?domain=' . $domain );
				if( !is_wp_error( $response ) ) {
					$response_body = wp_remote_retrieve_body( $response );
					if( $response_body ) {
						$stats = json_decode( $response_body, true );
						if( $stats ) {
							if( isset( $stats['status'] ) && 'Error' === $stats['status'] ) {
								$stats = null;
								$cached_stats = null;
							} else {
								update_option( 'ivole_store_stats', $stats );
							}
						}
					}
				}
			}
			if( !$stats ) {
				$stats = $cached_stats;
			}
			if( !$stats ) {
				$stats = array(
					'storeName' => '',
					'averageRating' => '0.0',
					'storeRating' => '0.0',
					'productRating' => '0.0',
					'totalReviews' => 0
				);
			}
			return $stats;
		}

	}

endif;
