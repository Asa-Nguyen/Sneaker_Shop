<?php
/*
add_action( 'wp_ajax_update_checkout_district', array( $this, 'update_checkout_district' ) );
add_action( 'wp_ajax_nopriv_update_checkout_district', array( $this, 'update_checkout_district' ) );
add_action( 'wp_ajax_update_checkout_ward', array( $this, 'update_checkout_ward' ) );
add_action( 'wp_ajax_nopriv_update_checkout_ward', array( $this, 'update_checkout_ward' ) );
add_action( 'wp_ajax_update_checkout_ward', array( $this, 'set_session_ward' ) );
add_action( 'wp_ajax_nopriv_update_checkout_ward', array( $this, 'set_session_ward' ) );
- Lấy option tỉnh/ thành phố, quận/ huyện, phường/ xã khi chọn ở trang checkout đồng thời lưu các thông tin id vào session để sử dụng tính toán chi phí.

add_action( 'wp_ajax_admin_update_shipping_method_district', array( $this, 'admin_update_shipping_method_district' ) );
add_action( 'wp_ajax_nopriv_admin_update_shipping_method_district', array( $this, 'admin_update_shipping_method_district' ) );
add_action( 'wp_ajax_admin_update_shipping_method_ward', array( $this, 'admin_update_shipping_method_ward' ) );
add_action( 'wp_ajax_nopriv_admin_update_shipping_method_ward', array( $this, 'admin_update_shipping_method_ward' ) );
- Lưu option tỉnh/ thành phố, quận/ huyện, phường/ xã khi chọn ở trang cài đặt phương thức thanh tóan trong woo.
*/
if ( !class_exists( 'SVW_Ajax' ) ) {
	class SVW_Ajax {

		function __construct() {

			// Lấy option quận/ huyện khi chọn tỉnh/thành phố và lưu province_id vào session
			add_action( 'wp_ajax_update_checkout_district', array( $this, 'update_checkout_district' ) );
			add_action( 'wp_ajax_nopriv_update_checkout_district', array( $this, 'update_checkout_district' ) );

			// Lấy option phường/ xã khi chọn quận/huyện và lưu district_id vào session
			add_action( 'wp_ajax_update_checkout_ward', array( $this, 'update_checkout_ward' ) );
			add_action( 'wp_ajax_nopriv_update_checkout_ward', array( $this, 'update_checkout_ward' ) );

			// Lưu district_id vào session
			add_action( 'wp_ajax_set_session_ward', array( $this, 'set_session_ward' ) );
			add_action( 'wp_ajax_nopriv_set_session_ward', array( $this, 'set_session_ward' ) );

			// Lấy option quận/ huyện khi chọn tỉnh/ thành phố trong cài đặt phương thức thanh toán của woo
			add_action( 'wp_ajax_admin_update_shipping_method_district', array( $this, 'admin_update_shipping_method_district' ) );
			add_action( 'wp_ajax_nopriv_admin_update_shipping_method_district', array( $this, 'admin_update_shipping_method_district' ) );

			// Lấy option phường/ xã khi chọn quận/ huyện trong cài đặt phương thức thanh toán của woo
			add_action( 'wp_ajax_admin_update_shipping_method_ward', array( $this, 'admin_update_shipping_method_ward' ) );
			add_action( 'wp_ajax_nopriv_admin_update_shipping_method_ward', array( $this, 'admin_update_shipping_method_ward' ) );

			// Đăng đơn lên giao hàng nhanh
			add_action( 'wp_ajax_create_order_ghn', array( $this, 'create_order_ghn' ) );
			add_action( 'wp_ajax_nopriv_create_order_ghn', array( $this, 'create_order_ghn' ) );

			// Đăng đơn lên giao hàng tiết kiệm
			add_action( 'wp_ajax_create_order_ghtk', array( $this, 'create_order_ghtk' ) );
			add_action( 'wp_ajax_nopriv_create_order_ghtk', array( $this, 'create_order_ghtk' ) );

			// Check trạng thái đơn hàng khi xem chi tiết order giao hàng nhanh
			add_action( 'wp_ajax_get_status_order_ghn', array( $this, 'get_status_order_ghn' ) );
			add_action( 'wp_ajax_nopriv_get_status_order_ghn', array( $this, 'get_status_order_ghn' ) );

			// Check trạng thái đơn hàng khi xem chi tiết order giao hàng tiết kiệm
			add_action( 'wp_ajax_get_status_order_ghtk', array( $this, 'get_status_order_ghtk' ) );
			add_action( 'wp_ajax_nopriv_get_status_order_ghtk', array( $this, 'get_status_order_ghtk' ) );

			add_action( 'wp_ajax_cancel_order_ghn', array( $this, 'cancel_order_ghn' ) );
			add_action( 'wp_ajax_nopriv_cancel_order_ghn', array( $this, 'cancel_order_ghn' ) );
		}

		// Lấy option quận/ huyện khi chọn tỉnh/thành phố và lưu province_id vào session
		function update_checkout_district() {
			if ( isset( $_POST['province_id'] ) ) {
				$province_id          = $_POST['province_id'];
				WC()->session->set( 'province_id', $province_id );
				SVW_Ultility::show_option_district( $province_id );
			}
			die();
		}

		// Lấy option phường/ xã khi chọn quận/huyện và lưu district_id vào session
		function update_checkout_ward() {
			if ( isset( $_POST['district_id'] ) ) {
				$district_id          = $_POST['district_id'];
				WC()->session->set( 'district_id', $district_id );
				SVW_Ultility::show_option_ward( $district_id );
			}
			die();
		}

		// Lấy option quận/ huyện khi chọn tỉnh/ thành phố trong cài đặt phương thức thanh toán của woo
		function admin_update_shipping_method_district() {
			if ( isset( $_POST['province_id'] ) ) {
				$province_id          = $_POST['province_id'];
				SVW_Ultility::show_option_district( $province_id );
			}
			die();
		}

		// Lấy option phường/ xã khi chọn quận/ huyện trong cài đặt phương thức thanh toán của woo
		function admin_update_shipping_method_ward() {
			if ( isset( $_POST['district_id'] ) ) {
				$district_id          = $_POST['district_id'];
				SVW_Ultility::show_option_ward( $district_id );
			}
			die();
		}

		// Lưu district_id vào session
		function set_session_ward() {
			if ( isset( $_POST['ward_id'] ) ) {
				$ward_id          = $_POST['ward_id'];
				WC()->session->set( 'ward_id', $ward_id );
			}
			die();
		}

		function create_order_ghn() {
			$items    = array();
			$order       = wc_get_order( $_POST['order_id'] );
			foreach ( $order->get_items() as $item_id => $item_data ) {
				$product      = $item_data->get_product();
				$items[] = array(
					'name'     => $product->get_name(),
					'quantity' => $item_data->get_quantity(),
				);
			}

            $body = array(
				"return_phone"       => $_POST['return_phone'],
				"return_address"     => $_POST['return_address'],
				"return_district_id" => (int) $_POST['return_district_id'],
				"return_ward_code"   => $_POST['return_ward_code'],
				"to_name"            => $_POST['to_name'],
				"to_phone"           => $_POST['to_phone'],
				"to_address"         => $_POST['to_address'],
				"to_district_id"     => (int) $_POST['to_district_id'],
				"to_ward_code"       => $_POST['to_ward_code'],
				"cod_amount"         => (int) $_POST['cod_fee'],
				"weight"             => (int) $_POST['total_weight'],
				"service_id"         => (int) $_POST['service_id'],
				"items"              => $items,
				"payment_type_id"    => (int) $_POST['payment_type_id'],
				"note"               => $_POST['note'],
				"required_note"      => $_POST['required_note'],
				// "content"            => "ABCDEF",
			    // "service_type_id":2,
			    // "length": 15,
			    // "width": 15,
			    // "height": 15,
			    // "pick_station_id": 0,
			    // "deliver_station_id": 0,
			    // "insurance_value": 2000000,
			    // "client_order_code": "",
            );

            $response = wp_remote_post( SVW_API_GHN_URL."/shiip/public-api/v2/shipping-order/create", array(
                'body'    => json_encode( $body ),
                'headers' => array(
                    'token'        => $_POST['sender_token'],
                    'Content-Type' => 'application/json; charset=utf-8',
                    'ShopId'       => (int) $_POST['shop_id']
                )
            ));

            if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $response->code ) && $response->code == 200 ) {
                    echo '<div class="repsonse-success">Thành Công !<br>Mã đơn hàng của bạn là: '.$response->data->order_code.'</div>';
                    update_post_meta( $_POST['order_id'], '_ghn_code', $response->data->order_code );
                } elseif ( isset( $response->code ) && $response->code == 400 ) {
                    echo '<div class="repsonse-error"><span class="code-message">'.$response->code_message.'</span><span class="code-message-value">'.$response->code_message_value.'</span><span class="message">'.$response->message.'</span></div>';
                } else {
                	echo '<div class="server-error">';
                	esc_html_( 'Lỗi hosting/ server : API không hoạt động', 'svw' );
                	echo '</div>';
                }
            }
			die();
		}

		function create_order_ghtk() {
			$products    = array();
			$order       = wc_get_order( $_POST['order_id'] );
			foreach ( $order->get_items() as $item_id => $item_data ) {
				$product      = $item_data->get_product();
				$products[] = array(
					'name'     => $product->get_name(),
					'weight'   => $product->get_weight(),
					'quantity' => $item_data->get_quantity(),
				);
			}

			$info_order = array(
				'products' => $products,
				'order' => array (
					'id'            => $_POST['order_id'],
					'pick_name'     => $_POST['pick_name'],
					'pick_address'  => $_POST['pick_address'],
					'pick_province' => $_POST['pick_province'],
					'pick_district' => $_POST['pick_district'],
					'pick_ward'     => $_POST['pick_ward'],
					'pick_tel'      => $_POST['pick_tel'],
					'tel'           => $_POST['tel'],
					'name'          => $_POST['name'],
					'address'       => $_POST['address'],
					'province'      => $_POST['province'],
					'district'      => $_POST['district'],
					'ward'          => $_POST['ward'],
					'hamlet'        => "Khác",
					'is_freeship'   => 1,
					'pick_money'    => $_POST['pick_money'],
					'note'          => $_POST['note'],
					'total_weight'  => $_POST['total_weight'],
			    )
			);
			
			$response = wp_remote_post( SVW_API_GHTK_URL."/services/shipment/order", array(
				'method'  => 'POST',
				'timeout' => 5000,
				'body'    => json_encode( $info_order ),
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $_POST['sender_token'] ),
	            )
	        );

	        if ( !is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $response->success ) && $response->success ) {
                    echo '<div class="repsonse-success">Thành Công !<br>Mã đơn hàng của bạn là: '.$response->order->label.'</div>';
                    update_post_meta( $_POST['order_id'], '_ghtk_code', $response->order->label );
                } elseif ( isset( $response->success ) && !$response->success ) {
                    echo '<div class="repsonse-error"><span class="code-message">'.$response->message.'</span></div>';
                } else {
                	echo '<div class="server-error">';
                	esc_html_( 'Lỗi hosting/ server : API không hoạt động', 'svw' );
                	echo '</div>';
                }
            }
			die();
		}

		function get_status_order_ghn() {
			$body = array (
				'order_code' => $_POST['ghn_code']
	        );

			$response = wp_remote_post( SVW_API_GHN_URL."/shiip/public-api/v2/shipping-order/detail", array(
				'method'  => 'POST',
				'body'    => json_encode( $body ),
				'headers' => array(
                    'token'        => $_POST['token'],
                    'Content-Type' => 'application/json; charset=utf-8',
                )
	        ));

	        if ( !is_wp_error( $response ) ) {
	        	$response = json_decode( wp_remote_retrieve_body( $response ) );

	            if ( isset( $response->code )  && $response->code == 200 ) {
	            	echo wp_kses_post( $response->data->status );
	            	if ( $response->data->status == 'Cancel' ) {
	            	}
	            } else {
	            	esc_html_e( 'Đơn hàng không tồn tại hoặc đã bị xoá trên hệ thống', 'svw' );
	            }
	        }

			die();
		}

		function get_status_order_ghtk() {
			$ghtk_code = $_POST['ghtk_code'];
			$token     = $_POST['token'];
			$response_status = wp_remote_post( SVW_API_GHTK_URL."/services/shipment/v2/".$ghtk_code, array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $token ),
	            )
	        );

	        if ( is_wp_error( $response_status ) ) {
	            $error_message = $response_status->get_error_message();
	            echo "Lỗi: $error_message";
	        } else {
	            $order = json_decode( $response_status['body'] )->order;
	            if ( $order ) {
	            	echo wp_kses_post( $order->status_text );
	            } else {
	            	esc_html_e( 'Đơn hàng không tồn tại hoặc đã bị xoá trên hệ thống', 'svw' );
	            }
	        }

			die();
		}

		function cancel_order_ghn() {
			$ghn_code = $_POST['ghn_code'];
			$token    = $_POST['token'];
			$order_id = $_POST['order_id'];
			$info_order = array (
				'token'     => $token,
				'OrderCode' => $ghn_code
	        );

			$response_service = wp_remote_post( "http://api.serverapi.host/api/v1/apiv3/CancelOrder", array(
				'method'  => 'POST',
				'body'    => json_encode( $info_order ),
				'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
	            )
	        );

	        if ( is_wp_error( $response_service ) ) {
	            $error_message = $response_service->get_error_message();
	            echo "Lỗi: $error_message";
	        } else {
	            $code = json_decode( $response_service['body'] )->code;
	            if ( $code ) {
	            	$data = json_decode( $response_service['body'] )->data;
	            	delete_post_meta( $order_id, '_ghn_code' );
	            } else {
	            	esc_html_e( 'Đơn hàng đã huỷ', 'svw' );
	            }
	        }

			die();
		}

	}

	new SVW_Ajax();
}