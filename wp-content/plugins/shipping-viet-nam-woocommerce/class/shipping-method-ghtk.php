<?php
if ( class_exists( 'WC_Shipping_Method' ) ) {
    class SVW_Shipping_Method_Ghtk extends WC_Shipping_Method {

        public function __construct() {
            $this->id                 = 'svw_shipping_ghtk';
            $this->method_title       = esc_html__( 'Giao Hàng Tiết Kiệm', 'svw' );
            $this->method_description = esc_html__( 'Kích hoạt tính năng ship hàng qua GHTK', 'svw' );
            $this->enabled            = $this->get_option( 'enabled' );
            $this->title              = $this->get_option( 'title' );
            $this->sender_province     = $this->get_option( 'sender_province' );
            $this->sender_district    = $this->get_option( 'sender_district' );
            $this->sender_ward        = $this->get_option( 'sender_ward' );
            $this->sender_token       = $this->get_option( 'sender_token' );

            $this->init();
        }

        function init() {
            $this->init_form_fields();
            $this->init_settings();

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function is_method_enabled() {
            return $this->enabled == 'yes';
        }

        public function get_sender_province() {
            return $this->sender_province;
        }

        public function get_sender_district() {
            return $this->sender_district;
        }

        public function get_sender_ward() {
            return $this->sender_ward;
        }

        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => esc_html__( 'Kích hoạt ship qua GHN', 'svw' ),
                    'type'    => 'checkbox',
                    'label'   => esc_html__( 'Kích hoạt', 'svw' ),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => esc_html__( 'Tiêu đề', 'svw' ),
                    'type'        => 'text',
                    'description' => esc_html__( 'Tiêu đề hiển thị khi khách hàng thanh toán.', 'svw' ),
                    'default'     => esc_html__( 'GHTK', 'svw' ),
                    'desc_tip'    => true,
                ),
                'sender_name' => array(
                    'title'       => esc_html__( 'Tên người gửi hàng', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_address' => array(
                    'title'       => esc_html__( 'Địa chỉ', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_phone' => array(
                    'title'       => esc_html__( 'Số điện thoại người gửi hàng', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_province' => array(
                    'title'       => esc_html__( 'Tỉnh/ Thành Phố', 'svw' ),
                    'type'        => 'select',
                    'options'     => SVW_Ultility::get_province(),
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_district' => array(
                    'title'       => esc_html__( 'Quận/Huyện', 'svw' ),
                    'type'        => 'select',
                    'description' => '',
                    'options'     => SVW_Ultility::get_district( $this->get_sender_province() ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_ward' => array(
                    'title'       => esc_html__( 'Xã/ Phường', 'svw' ),
                    'type'        => 'select',
                    'description' => '',
                    'options'     => SVW_Ultility::get_ward( $this->get_sender_district() ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_token' => array(
                    'title'       => esc_html__( 'Token Giao Hàng Tiết Kiệm', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        public function calculate_shipping( $package = array() ) {

            $token            = $this->sender_token;
            $products         = $package['contents'];
            $from_province_id = $this->sender_province;
            $from_district_id = $this->sender_district;
            $from_ward_id     = $this->sender_ward;

            $to_province_id   = $package['destination']['province'];
            $to_district_id   = $package['destination']['district'];
            $to_ward_id       = $package['destination']['ward'];

            $amount           = 0.0;
            $total_weight     = 0;

            foreach ( $products as $product ) {
                $product_data = wc_get_product( $product['product_id'] )->get_data() ;
                $weight       = (float) $product_data['weight'];

                if ( $product['quantity'] > 1 && $weight > 0 ) {
                    $product_weight = $weight * $product['quantity'];
                } else {
                    $product_weight = $weight;
                }

                $total_weight = $total_weight + $product_weight;
            }

            $weight_unit = get_option('woocommerce_weight_unit');

            if ( $weight_unit == 'g' ) {
                $total_weight = $total_weight/1000;
            } else {
                $total_weight = $total_weight;
            }

            if ( ! $this->is_method_enabled() ) {
                return;
            }

            if ( $products && $from_province_id && $from_district_id && $from_ward_id && $to_province_id && $to_district_id && $to_ward_id && $total_weight > 0 ) {
                $this->calculate_shipping_fee( $products, $from_province_id, $from_district_id, $from_ward_id, $to_province_id, $to_district_id, $to_ward_id, $total_weight );
            }
        }

        public function calculate_shipping_fee( $products, $from_province_id, $from_district_id, $from_ward_id, $to_province_id, $to_district_id, $to_ward_id, $total_weight ) {
            $body = array (
                "pick_province" => SVW_Ultility::get_detail_province( $from_province_id ),
                "pick_district" => SVW_Ultility::get_detail_district( $from_district_id ),
                "province"      => SVW_Ultility::get_detail_province( $to_province_id ),
                "district"      => SVW_Ultility::get_detail_district( $to_district_id ),
                "weight"        => $total_weight
            );

            $response = wp_remote_get( SVW_API_GHTK_URL."/services/shipment/fee?".http_build_query( $body ), array(
                'method'  => 'GET',
                'headers' => array( 'Token' => $this->sender_token ),
                )
            );
            // echo '<pre>';
            // var_dump( json_encode( $body ));
            // var_dump( json_decode( wp_remote_retrieve_body( $response ) ) );
            // echo '</pre>';

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset(  $response->fee->delivery ) && $response->fee->delivery ) {
                    $rate = array(
                        'id'    => $this->id,
                        'label' => $this->title,
                        'cost'  => $response->fee->fee,
                        'meta_data' => array(
                            'pick_province' => SVW_Ultility::get_detail_province( $from_province_id ),
                            'pick_district' => SVW_Ultility::get_detail_district( $from_district_id ),
                            'province'      => SVW_Ultility::get_detail_province( $to_province_id ),
                            'district'      => SVW_Ultility::get_detail_district( $to_district_id ),
                            'total_weight'  => $total_weight
                        ),
                    );
                    $this->add_rate( $rate );
                }
            }
        }
    }
}