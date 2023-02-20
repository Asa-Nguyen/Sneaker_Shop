<?php
	defined( 'ABSPATH' ) or die( 'Keep Quit' );
	
	
	/**
	 * @var $gallery_images
	 * @var $variation_id
	 */
	//print_r( $gallery_images);
	
	foreach ( $gallery_images as $image_id ):
		
		$image = wp_get_attachment_image_src( $image_id );
		
		?>
        <li class="image">
            <input class="wvg_variation_id_input" type="hidden" name="woo_variation_gallery[<?php echo esc_attr( $variation_id ) ?>][]" value="<?php echo $image_id ?>">
            <img src="<?php echo esc_url( $image[ 0 ] ) ?>">
            <a href="#" class="delete remove-woo-variation-gallery-image"><span class="dashicons dashicons-dismiss"></span></a>
        </li>
	
	<?php endforeach; ?>
