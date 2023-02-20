<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('firebase/src/JWT.php');
use \ivole\Firebase\JWT\JWT;

if ( ! class_exists( 'CR_Replies' ) ) :

	class CR_Replies {
		private $api_url = 'https://api.cusrev.com/v1/production/reviews/review-reply/seller';

	  public function __construct( $comment_id ) {
			if( 'yes' == get_option( 'ivole_reviews_verified', 'no' ) ) {
				$comment = get_comment( $comment_id );
	    	if ( $comment ) {
					// get parent comment (orignal review) and find order number related to this review
					//it is possible that we have a reply to reply
					//in this case, we will have to loop through previous replies to find the original review
					$max_iterations = 200;
					$parent_id = $comment->comment_parent;
					$i = 0;
					while( $parent_id ) {
						$i++;
						//just a safety measure to avoid infinite loop
						if( $i > $max_iterations ) {
							break;
						}
						$parent = get_comment( $parent_id );
						if( $parent ) {
							if( $parent->comment_parent ) {
								$parent_id = $parent->comment_parent;
								continue;
							} else {
								$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
								$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
								if( $ivole_order && $rating ) {
									$current_user = wp_get_current_user();

									if( $current_user->ID ) {
										$key = strtolower( get_option( 'ivole_license_key' ) );
										$payload = array(
											'iss' => Ivole_Email::get_blogurl(),
											'aud' => 'www.cusrev.com',
											'iat' => time()
										);
										$jwt = JWT::encode( $payload, $key, 'HS256' );
										//support for shop pages (product ID = -1)
										$product_id = $parent->comment_post_ID;
										$shop_page_id = wc_get_page_id( 'shop' );
										if( $shop_page_id == $product_id ) {
											$product_id = '-1';
										}
										//WPML integration
										$ivole_language = get_option( 'ivole_language' );
										if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
											$ivole_language = get_post_meta( $ivole_order, 'wpml_language', true );
											if( 0 < $product_id ) {
												$default_language = apply_filters( 'wpml_default_language', NULL );
												$maybe_translated_shop = apply_filters( 'translate_object_id', $product_id, 'page', true, $default_language );
												if( $shop_page_id === $maybe_translated_shop ) {
													$product_id = '-1';
												} else {
													$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $default_language );
												}
											}
										}
										if ( empty( $ivole_language ) ) {
											$ivole_language = 'EN';
										}
										$data = array(
											'shopDomain' => Ivole_Email::get_blogurl(),
											'orderId' => $ivole_order,
											'productId' => $product_id,
											'replyId' => strval( $comment_id ),
											'email' => $current_user->user_email,
											'language' => $ivole_language,
											'text' => $comment->comment_content,
											'token' => $jwt
										);
										$data_string = json_encode( $data );
										//error_log( print_r( $data_string, true ) );
										$ch = curl_init();
										curl_setopt( $ch, CURLOPT_URL, $this->api_url );
										curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
										curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
										curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
										curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
											'Content-Type: application/json',
											'Content-Length: ' . strlen( $data_string ) )
										);
										$result = curl_exec( $ch );
										//error_log( print_r( $result, true ) );
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										if( $httpcode ) {
											$meta = array();
											if( 201 === $httpcode ) {
												//success
												$meta[] = 201;
												$meta[] = __( 'A copy of the reply was published on CusRev portal', 'customer-reviews-woocommerce' );
											} else {
												//some error
												$resultd = json_decode( $result );
												if( $resultd && isset( $resultd->details ) ) {
													$meta[] = 995;
													$meta[] = $resultd->details;
												} else {
													$meta[] = 999;
													$meta[] = __( 'Unknown error', 'customer-reviews-woocommerce' );
												}
											}
											update_comment_meta( $comment_id, 'ivole_reply', $meta );
										} else {
											//error_log( print_r( $result, true ) );
											if( false === $result ) {
												$meta = array();
												$meta[] = 997;
												$meta[] = curl_error( $ch );
											} else {
												$meta = array();
												$meta[] = 998;
												$meta[] = __( 'Unknown error', 'customer-reviews-woocommerce' );
											}
											update_comment_meta( $comment_id, 'ivole_reply', $meta );
										}
									} else {
										$meta = array();
										$meta[] = 996;
										$meta[] = __( 'ID of the current user is not set', 'customer-reviews-woocommerce' );
										update_comment_meta( $comment_id, 'ivole_reply', $meta );
									}
								}
								break;
							}
						} else {
							break;
						}
					}
				};
			}
	  }

		public static function isReplyForCRReview( $comment ) {
			if( $comment && $comment->comment_parent ) {
				$parent_id = $comment->comment_parent;
				$max_iterations = 200;
				$i = 0;
				while( $parent_id ) {
					$i++;
					//just a safety measure to avoid infinite loop
					if( $i > $max_iterations ) {
						break;
					}
					$parent = get_comment( $parent_id );
					if( $parent ) {
						if( $parent->comment_parent ) {
							$parent_id = $parent->comment_parent;
							continue;
						} else {
							$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
							$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
							if( $ivole_order && $rating ) {
								$product_id = $parent->comment_post_ID;
								//WPML integration
								if ( has_filter( 'wpml_object_id' ) ) {
									$cr_language = get_option( 'ivole_language' );
									if( $cr_language === 'WPML' ) {
										$default_language = apply_filters( 'wpml_default_language', NULL );
										if( 'product' === get_post_type( $product_id ) ) {
											// it is a product
											$product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $default_language );
										} else {
											// it is shop page (shop review)
											$product_id = apply_filters( 'translate_object_id', $product_id, 'page', true, $default_language );
										}
									}
								}
								return array( $ivole_order, $product_id );
							} else {
								return false;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		}

	}

endif;

?>
