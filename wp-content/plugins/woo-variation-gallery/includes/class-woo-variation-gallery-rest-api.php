<?php
defined( 'ABSPATH' ) or die( 'Keep Quit' );

if ( ! class_exists( 'Woo_Variation_Gallery_REST_API', false ) ):
	class Woo_Variation_Gallery_REST_API {

		protected static $_instance = null;

		protected function __construct() {
			$this->includes();
			$this->hooks();
			$this->init();
			do_action( 'woo_variation_gallery_rest_api_loaded', $this );
		}

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function includes() {

		}

		public function hooks() {
			add_filter( 'woocommerce_rest_prepare_product_variation_object', array(
				$this,
				'rest_api_response'
			), 10, 2 );
		}

		public function init() {

		}


		// Start

		public function rest_get_image_data( $attachment_id ) {

			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				return false;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				return false;
			}

			$attachment_data = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);

			$has_video = trim( get_post_meta( $attachment_id, 'woo_variation_gallery_media_video', true ) );

			if ( $has_video ) {
				$attachment_data['video_src'] = $has_video;
			}

			return apply_filters( 'woo_variation_gallery_images_rest_get_image', $attachment_data, $attachment_id );
		}

		// API GET Test
		// curl https://example.com/wp-json/wc/v3/products/<product_id>/variations/<id> -u consumer_key:consumer_secret
		// @TODO: "woocommerce_rest_pre_insert_{$this->post_type}_object"
		public function rest_api_response( $response, $object ) {

			if ( empty( $response->data ) ) {
				return $response;
			}

			$variation_id = $object->get_id();
			$product_id   = $object->get_parent_id();

			$variation_images                               = (array) get_post_meta( $variation_id, 'woo_variation_gallery_images', true );
			$response->data['woo_variation_gallery_images'] = array();
			foreach ( $variation_images as $attachment_id ) {

				$image_info = $this->rest_get_image_data( $attachment_id );
				if ( is_array( $image_info ) ) {
					array_push( $response->data['woo_variation_gallery_images'], $image_info );
				}
			}

			return $response;
		}
	}
endif;