<?php
/*
add_filter( 'woocommerce_checkout_fields', array( $this, 'svw_woocommerce_checkout_fields' ), 99 );
Tuỳ chỉnh trang checkout:
- Thêm select chọn tỉnh/ thành phố, quận/ huyện, xã/ phường
- Bỏ các field ở trang checkout: city, postcode, last_name

add_filter( 'woocommerce_customer_meta_fields', array( $this, 'svw_woocommerce_customer_meta_fields' ) );
Tuỳ chỉnh thông tin user:
- Thêm các thông tin tỉnh/ thành phố, quận/ huyện, xã/ phường vào thông tin cùa user
*/

if ( !class_exists( 'SVW_Custom_Fields' ) ) {
	class SVW_Custom_Fields {

		function __construct() {
			// Add thêm field khi checkout, sắp xếp lại thứ tự fields và ẩn một số field không sử dụng.
			add_filter( 'woocommerce_checkout_fields', array( $this, 'svw_woocommerce_checkout_fields' ), 99 );

			// Đổi label của field nếu cần.
			// add_filter( 'woocommerce_default_address_fields' ,  array( $this, 'svw_woocommerce_default_address_fields' ) );

			// Add thêm field lưu tỉnh/ thành phố, quận/huyện, phường/ xã vào thông tin của user .
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'svw_woocommerce_customer_meta_fields' ) );
		}

		// Add thêm field khi checkout, sắp xếp lại thứ tự fields và ẩn một số field không sử dụng.
		function svw_woocommerce_checkout_fields( $fields ) {

			$province_args = array(
				'label'   => esc_html__( 'Province', 'svw' ),
				'type'    => 'select',
				'required' => true,
				'options' => SVW_Ultility::get_province(),
				'input_class' => array(
					'wc-enhanced-select svw-select-province',
				),
				'priority' => 90,
				'default'  => '',
			);
			$fields['shipping']['shipping_svw_province'] = $province_args;
			$fields['billing']['billing_svw_province']   = $province_args;

			$district_args = array(
				'label'    => esc_html__( 'District', 'svw' ),
				'type'     => 'select',
				'required' => true,
				'options'  => SVW_Ultility::get_district( get_user_meta( get_current_user_id(), 'billing_svw_province', true ) ),
				'input_class' => array(
					'wc-enhanced-select svw-select-district',
				),
				'class' => array (
					0 => 'form-row-wide',
				),
				'priority' => 90
			);
			$fields['shipping']['shipping_svw_district'] = $district_args;
			$fields['billing']['billing_svw_district']   = $district_args;

			$ward_args = array(
				'label'    => esc_html__( 'Ward', 'svw' ),
				'type'     => 'select',
				'required' => true,
				'options'  => SVW_Ultility::get_ward( get_user_meta( get_current_user_id(), 'billing_svw_district', true ) ),
				'input_class' => array(
					'wc-enhanced-select svw-select-ward',
				),
				'class' => array (
					0 => 'form-row-wide',
					2 => 'update_totals_on_change',
				),
				'priority' => 100
			);
			$fields['shipping']['shipping_svw_ward'] = $ward_args;
			$fields['billing']['billing_svw_ward']   = $ward_args;

			unset($fields['shipping']['shipping_city']);
			unset($fields['billing']['billing_city']);

			unset($fields['shipping']['shipping_postcode']);
			unset($fields['billing']['billing_postcode']);

			unset($fields['shipping']['shipping_last_name']);
			unset($fields['billing']['billing_last_name']);

			$fields['shipping']['shipping_phone']['priority'] = 30;
			$fields['billing']['billing_phone']['priority']   = 30;

			$fields['shipping']['shipping_email']['priority'] = 40;
			$fields['billing']['billing_email']['priority']   = 40;

			$fields['shipping']['shipping_country']['priority'] = 50;
			$fields['billing']['billing_country']['priority']   = 50;

			$fields['shipping']['shipping_address_1']['priority'] = 1000;
			$fields['billing']['billing_address_1']['priority']   = 1000;

			return $fields;
		}

		// Đổi label của field nếu cần.
		function svw_woocommerce_default_address_fields( $fields ) {
		    $fields['city']['label'] = esc_html__( 'Province', 'svw' );

			return $fields;
		}

		function svw_woocommerce_customer_meta_fields( $show_fields ) {
			$show_fields['billing']['fields']['billing_svw_province'] = array(
				'label'       => esc_html__( 'Tình/ Thảnh Phố', 'svw' ),
				'description' => '1234',
			);
		    $show_fields['billing']['fields']['billing_svw_district'] = array(
				'label'       => esc_html__( 'Quận/ Huyện', 'svw' ),
				'description' => '',
			);
			$show_fields['billing']['fields']['billing_svw_ward'] = array(
				'label'       => esc_html__( 'Xã/ Phường', 'svw' ),
				'description' => '',
			);

			$show_fields['shipping']['fields']['shipping_svw_province'] = array(
				'label'       => esc_html__( 'Tỉnh/ Thành Phố', 'svw' ),
				'description' => '',
			);
			$show_fields['shipping']['fields']['shipping_svw_district'] = array(
				'label'       => esc_html__( 'Quận/ Huyện', 'svw' ),
				'description' => '',
			);
			$show_fields['shipping']['fields']['shipping_svw_ward'] = array(
				'label'       => esc_html__( 'Xã/ Phường', 'svw' ),
				'description' => '',
			);

			return $show_fields;
		}

	}

	new SVW_Custom_Fields();
}