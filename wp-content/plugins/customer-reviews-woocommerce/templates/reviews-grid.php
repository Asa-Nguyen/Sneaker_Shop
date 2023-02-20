<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ivole-reviews-grid" id="<?php echo $id; ?>" style="<?php echo $section_style; ?>" data-attributes="<?php echo wc_esc_json(wp_json_encode($attributes));?>">
	<?php echo $summary_bar;?>
	<div class="cr-reviews-grid-inner">
		<div class="cr-reviews-grid-col cr-reviews-grid-col1"></div>
		<div class="cr-reviews-grid-col cr-reviews-grid-col2"></div>
		<div class="cr-reviews-grid-col cr-reviews-grid-col3"></div>
		<?php foreach ( $reviews as $i => $review ):
			$rating = intval( get_comment_meta( $review->comment_ID, 'rating', true ) );
			$order_id = intval( get_comment_meta( $review->comment_ID, 'ivole_order', true ) );
			$country = get_comment_meta( $review->comment_ID, 'ivole_country', true );
			$country_code = null;
			if( is_array( $country ) && isset( $country['code'] ) ) {
				$country_code = $country['code'];
			}
			$product = wc_get_product( $review->comment_post_ID );
			if( $product ) {
				$card_class = 'cr-review-card cr-card-product';
			} else {
				$card_class = 'cr-review-card cr-card-shop';
			}
			$pics = get_comment_meta( $review->comment_ID, 'ivole_review_image' );
			$pics_local = get_comment_meta( $review->comment_ID, 'ivole_review_image2' );
			$customer_images_html = '';
			$customer_images = array();
			foreach( $pics as $pic ) {
				if( $pic['url'] ) {
					$customer_images[] = $pic['url'];
				}
			}
			foreach( $pics_local as $pic ) {
				$attachmentUrl = wp_get_attachment_image_url( $pic, apply_filters( 'cr_reviews_grid_image_size', 'large' ) );
				if( $attachmentUrl ) {
					$customer_images[] = $attachmentUrl;
				}
			}
			$count_customer_images = count( $customer_images );
			if( 0 < $count_customer_images ) {
				$customer_images_html = '<div class="image-row">';
				$customer_images_html .= '<img class="image-row-img" src="' . $customer_images[0] . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), 1 ) . $review->comment_author . '" loading="lazy">';
				for( $j=1; $j < $count_customer_images; $j++ ) {
					$customer_images_html .= '<img class="image-row-img image-row-img-none" src="' . $customer_images[$j] . '" alt="' . sprintf( __( 'Image #%1$d from ', 'customer-reviews-woocommerce' ), $j+1 ) . $review->comment_author . '">';
				}
				$customer_images_html .= '<div class="image-row-count">';
				$customer_images_html .= '<img class="image-row-camera" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/camera.svg" loading="lazy" width="40" height="40">';
				$customer_images_html .= $count_customer_images . '</div></div>';
			}
			$author = get_comment_author( $review );
			?>
			<div class="<?php echo $card_class; ?>" style="<?php echo $card_style; ?>" data-reviewid="<?php echo esc_attr( $review->comment_ID );?>">
				<div class="cr-review-card-content">
					<?php echo $customer_images_html; ?>
					<div class="top-row">
						<?php
						$avtr = get_avatar( $review, 56, '', esc_attr( $author ) );
						if( $avatars && $avtr ): ?>
							<div class="review-thumbnail">
								<?php echo $avtr; ?>
							</div>
						<?php endif; ?>
						<div class="reviewer">
							<div class="reviewer-name">
								<?php
								echo esc_html( $author );
								if( $country_code ) {
									echo '<img src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/flags/' . $country_code . '.svg" class="ivole-grid-country-icon" width="20" height="15" alt="' . $country_code . '">';
								}
								?>
							</div>
							<?php
							if( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && wc_review_is_from_verified_owner( $review->comment_ID ) ) {
								echo '<div class="reviewer-verified">';
								echo '<img class="cr-reviewer-verified" src="' . plugin_dir_url( dirname( __FILE__ ) ) . 'img/verified.svg' . '" alt="' . $verified_text . '" width="22" height="22" loading="lazy" />';
								echo $verified_text;
								echo '</div>';
							}
							?>
						</div>
					</div>
					<div class="rating-row">
						<div class="rating">
							<div class="crstar-rating" style="<?php echo $stars_style; ?>"><span style="width:<?php echo ($rating / 5) * 100; ?>%;"></span></div>
						</div>
						<div class="rating-label">
							<?php echo $rating . '/5'; ?>
						</div>
					</div>
					<div class="middle-row">
						<div class="review-content">
							<?php echo wpautop( wp_kses_post( $review->comment_content ) ); ?>
						</div>
						<?php if ( $verified_reviews_enabled && $order_id && intval( $review->comment_post_ID ) !== intval( $shop_page_id ) ): ?>
							<div class="verified-review-row">
								<div class="verified-badge"><?php printf( $badge, $review->comment_post_ID, $order_id ); ?></div>
							</div>
						<?php elseif ( $verified_reviews_enabled && $order_id && intval( $review->comment_post_ID ) === intval( $shop_page_id ) ): ?>
							<div class="verified-review-row">
								<div class="verified-badge"><?php printf( $badge_sr, $order_id ); ?></div>
							</div>
						<?php endif; ?>
						<div class="datetime">
							<?php printf( _x( '%s ago', '%s = human-readable time difference', 'customer-reviews-woocommerce' ), human_time_diff( mysql2date( 'U', $review->comment_date, true ), current_time( 'timestamp' ) ) ); ?>
						</div>
					</div>
					<?php if ( $show_products && $product ):
						if( 'publish' === $product->get_status() ):
							?>
							<div class="review-product" style="<?php echo $product_style; ?>">
								<div class="product-thumbnail">
									<?php echo $product->get_image( 'woocommerce_gallery_thumbnail' ); ?>
								</div>
								<div class="product-title">
									<?php if ( $product_links ): ?>
										<?php echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . $product->get_title() . '</a>'; ?>
									<?php else: ?>
										<?php echo '<span>' . $product->get_title() . '</span>'; ?>
									<?php endif; ?>
								</div>
							</div>
							<?php
						endif;
					endif;
					?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $show_more ): ?>
		<div class="cr-show-more">
			<button id="cr-show-more-reviews-grid" class="ivole-show-more-button" type="button"><?php echo __( 'Show more', 'customer-reviews-woocommerce' ); ?></button>
			<span class="ivole-show-more-spinner" style="display:none;"></span>
		</div>
	<?php else: ?>
		<div class="cr-show-more">
			<span class="ivole-show-more-spinner" style="display:none;"></span>
		</div>
	<?php endif; ?>
</div>
