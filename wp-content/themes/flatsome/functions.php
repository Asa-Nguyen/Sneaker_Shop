<?php
/**
 * Flatsome functions and definitions
 *
 * @package flatsome
 */

require get_template_directory() . '/inc/init.php';

function flatsome_theme_update(){
    
}
add_action('init','flatsome_theme_update');



//Text
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
     $fields['billing']['billing_first_name']['placeholder'] = 'Full Name';
     $fields['billing']['billing_first_name']['label'] = 'Full Name';
     return $fields;
}
 



/**
 * Note: It's not recommended to add any custom code here. Please use a child theme so that your customizations aren't lost during updates.
 * Learn more here: http://codex.wordpress.org/Child_Themes
 */
