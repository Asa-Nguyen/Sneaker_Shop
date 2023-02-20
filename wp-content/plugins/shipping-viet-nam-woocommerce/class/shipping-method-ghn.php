<?php

if ( class_exists( 'WC_Shipping_Method' ) ) {
    class SVW_Shipping_Method_Ghn extends WC_Shipping_Method {

        public function __construct() {
            $this->id                 = 'svw_shipping_ghn';
            $this->method_title       = esc_html__( 'Giao Hàng Nhanh', 'svw' );
            $this->method_description = esc_html__( 'Kích hoạt tính năng ship hàng qua GHN', 'svw' );
            $this->enabled            = $this->get_option( 'enabled' );
            $this->title              = $this->get_option( 'title' );
            $this->sender_province    = $this->get_option( 'sender_province' );
            $this->sender_district    = $this->get_option( 'sender_district' );
            $this->sender_ward        = $this->get_option( 'sender_ward' );
            $this->sender_token       = $this->get_option( 'sender_token' );

            $this->init();
        }

        function init() {
            // Load the settings API
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
                    'default'     => esc_html__( 'GHN', 'svw' ),
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
                    'title'       => esc_html__( 'Token Giao Hàng Nhanh', 'svw' ),
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
            $from_district_id = $this->sender_district;
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
                $total_weight = $total_weight;
            } else {
                $total_weight = $total_weight*1000;
            }

            if ( ! $this->is_method_enabled() ) {
                return;
            }

            if ( $products && $token && $from_district_id && isset( $to_district_id ) && isset( $to_ward_id ) && $total_weight > 0 ) {
                $this->calculate_shipping_fee( $token, $products, $from_district_id, $to_district_id, $to_ward_id, $total_weight );
            }

        }

        public function calculate_shipping_fee( $token, $products, $from_district_id, $to_district_id, $to_ward_id, $total_weight ) {

            // SVW_GHN_API::insert_province();
            $shops = SVW_GHN_API::get_all_shop( $token );

            if ( isset( $to_district_id ) && $to_district_id ) {
                foreach( $shops as $shop_id => $shop_name ) {
                    $services = SVW_GHN_API::get_available_services( $token, $shop_id, $from_district_id, $to_district_id );
                    foreach( $services as $service_id => $service_name ) {
                        if ( $service_name ) {
                            $value = SVW_GHN_API::get_shipping_order_fee( $token, $shop_id, $service_id, $from_district_id, $to_district_id, $to_ward_id, $total_weight );
                            if ( $value && isset( $value->total ) ) {
                                $id = $this->id.'_'.$shop_id.'_'.$service_id;
                                $rate = array(
                                    'id'    => $id,
                                    'label' => $this->title.' - '.$shop_name.' - '.$service_name,
                                    'cost'  => $value->total,
                                    'meta_data' => array(
                                        'shop_id'          => $shop_id,
                                        'service_id'       => $service_id,
                                        'from_district_id' => $from_district_id,
                                        'to_district_id'   => $to_district_id,
                                        'to_ward_id'       => $to_ward_id,
                                        'total_weight'     => $total_weight
                                    ),
                                );
                                $this->add_rate( $rate );
                            }
                        }
                    }
                }
            }
        }
    }
}

if ( !class_exists( 'SVW_GHN_API' ) ) {
    class SVW_GHN_API {

        // Insert thông tin tỉnh/ thành phố
        public static function insert_province() {
            $province = array();
            $response = wp_remote_post( SVW_API_GHN_DEV_URL."/shiip/public-api/master-data/province", array(
                'headers' => array(
                    'token'        => '11ce5c2c-078f-11eb-84a9-aef8461f938e',
                    'Content-Type' => 'application/json; charset=utf-8'
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );

                global $wpdb;
                if ( isset( $response->data ) && is_array( $response->data ) && $response->code == 200 ) {
                    $table  = $wpdb->prefix . 'svw_province_district_ward';
                    $wpdb->query("TRUNCATE TABLE $table ");
                    foreach ( $response->data as $item ) {
                        if ( isset( $item->ProvinceID ) && $item->ProvinceID && isset( $item->ProvinceName ) && $item->ProvinceName ) {
                            $wpdb->insert( $wpdb->prefix . "svw_province_district_ward", array(
                                'code'        => $item->ProvinceID,
                                'name'        => $item->ProvinceName,
                                'is_province' => true,
                            ));
                            self::insert_district( $item->ProvinceID );
                        }
                    }
                }
            }
        }

        // Insert thông tin quận/ huyện
        public static function insert_district( $province_id ) {
            $response = wp_remote_post( SVW_API_GHN_DEV_URL."/shiip/public-api/master-data/district", array(
                'body'    => json_encode( array( 'province_id' => $province_id ) ),
                'headers' => array(
                    'token'        => '11ce5c2c-078f-11eb-84a9-aef8461f938e',
                    'Content-Type' => 'application/json; charset=utf-8'
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                global $wpdb;
                if ( isset( $response->data ) && is_array( $response->data ) && $response->code == 200 ) {
                    foreach ( $response->data as $item ) {
                        if ( isset( $item->DistrictID ) && $item->DistrictID && isset( $item->DistrictName ) && $item->DistrictName ) {
                            $wpdb->insert( $wpdb->prefix . "svw_province_district_ward", array(
                                'code'        => $item->DistrictID,
                                'name'        => $item->DistrictName,
                                'parent'      => $province_id,
                                'is_district' => true
                            ));
                            self::insert_ward( $item->DistrictID );
                        }
                    }
                }
            }
        }

        // Insert thông tin xã/ phường
        public static function insert_ward( $district_id ) {
            $response = wp_remote_post( SVW_API_GHN_DEV_URL."/shiip/public-api/master-data/ward", array(
                'body'    => json_encode( array( 'district_id' => $district_id ) ),
                'headers' => array(
                    'token'        => '11ce5c2c-078f-11eb-84a9-aef8461f938e',
                    'Content-Type' => 'application/json; charset=utf-8'
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                global $wpdb;
                if ( isset( $response->data ) && is_array( $response->data ) && $response->code == 200 ) {
                    foreach ( $response->data as $item ) {
                        if ( isset( $item->WardCode ) && $item->WardCode && isset( $item->WardName ) && $item->WardName ) {
                            $wpdb->insert( $wpdb->prefix . "svw_province_district_ward", array(
                                'code'    => $item->WardCode,
                                'name'    => $item->WardName,
                                'parent'  => $district_id,
                                'is_ward' => true
                            ));
                        }
                    }
                }
            }
        }

        // Lấy thông tin tất cả các shop theo token, output array key và tên shop
        public static function get_all_shop( $token ) {
            $shops = array();
            $response = wp_remote_post( SVW_API_GHN_URL."/shiip/public-api/v2/shop/all", array(
                'headers' => array(
                    'token'        => $token,
                    'Content-Type' => 'application/json; charset=utf-8'
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $response->data->shops ) && is_array( $response->data->shops ) ) {
                    foreach ( $response->data->shops as $shop ) {
                        $shops[$shop->_id] = $shop->name;
                    }
                    return $shops;
                }
            }
        }

        // Lấy thông tin các gói dịch vụ theo id cửa hàng.
        public static function get_available_services( $token, $shop_id, $from_district, $to_district ) {
            $body = array(
                'shop_id'       => (int) $shop_id,
                'from_district' => (int) $from_district,
                'to_district'   => (int) $to_district
            );

            $response = wp_remote_post( SVW_API_GHN_URL."/shiip/public-api/v2/shipping-order/available-services", array(
                'body'    => json_encode( $body ),
                'headers' => array(
                    'token'        => $token,
                    'Content-Type' => 'application/json; charset=utf-8'
                )
            ));
            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $response->data ) && is_array( $response->data ) ) {
                    foreach ( $response->data as $service ) {
                        $services[$service->service_id] = $service->short_name;
                    }
                    return $services;
                }
            }
        }

        // Tính phí vận chuyển
        public static function get_shipping_order_fee( $token, $shop_id, $service_id, $from_district_id, $to_district_id, $to_ward_code, $weight, $height = null, $length = null,  $width = null ) {
            $body = array(
                'service_id'       => (int) $service_id,
                'from_district_id' => (int) $from_district_id,
                'to_district_id'   => (int) $to_district_id,
                'to_ward_code'     => $to_ward_code,
                'weight'           => (int) $weight,
                'height'           => (int) $height,
                'length'           => (int) $length,
                'width'            => (int) $width
            );

            $response = wp_remote_post( SVW_API_GHN_URL."/shiip/public-api/v2/shipping-order/fee", array(
                'body'    => json_encode( $body ),
                'headers' => array(
                    'token'        => $token,
                    'Content-Type' => 'application/json; charset=utf-8',
                    'ShopId'       => (int) $shop_id
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $response->code ) && $response->code = 200 ) {
                    return $response->data;
                }
            }
        }
    }
    new SVW_GHN_API();
}