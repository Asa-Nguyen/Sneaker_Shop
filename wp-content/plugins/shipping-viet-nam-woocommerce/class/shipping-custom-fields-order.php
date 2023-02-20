<?php

if ( !class_exists( 'SVW_Custom_Fields_Order' ) ) {
	class SVW_Custom_Fields_Order {

		function __construct() {

			// Thêm các fields quận/huyện và xã/phường và truyền id để sử dụng trong format.
			add_filter( 'woocommerce_get_order_address', array( $this, 'svw_woocommerce_get_order_address' ), 3, 999 );

			// Tuỳ chỉnh format thông tin thanh toán và thông tin giao hàng.
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'svw_woocommerce_localisation_address_formats' ), 999 );

			// Khai cách replace mảng và xử lý dữ liệu các fields mới vào, thay id bằng tên.
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'svw_woocommerce_formatted_address_replacements' ), 2, 999 );

			// Khai báo các fields nào được hiển thị, fields nào không và label của chúng khi xem chi tiết 1 order trong admin thông qua giá trị show
			add_filter( 'woocommerce_admin_billing_fields', array( $this, 'svw_woocommerce_admin_billing_fields' ), 999 );
			add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'svw_woocommerce_admin_shipping_fields' ), 999 );

			// Gõ bỏ các fields không sử dụng.
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'svw_woocommerce_order_formatted_billing_address' ), 999 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'svw_woocommerce_order_formatted_shipping_address' ), 999 );

			// Thêm box tạo vận đơn vào sidebar trong admin
			add_action( 'add_meta_boxes', array( $this, 'svw_add_meta_boxes' ), 30 );
		}

		function svw_woocommerce_get_order_address( $array, $type, $order ) {
			$shipping_province_id = get_post_meta( $order->get_id(), '_shipping_svw_province', true );
			$shipping_district_id = get_post_meta( $order->get_id(), '_shipping_svw_district', true );
			$shipping_ward_id     = get_post_meta( $order->get_id(), '_shipping_svw_ward', true );

			$billing_province_id = get_post_meta( $order->get_id(), '_billing_svw_province', true );
			$billing_district_id = get_post_meta( $order->get_id(), '_billing_svw_district', true );
			$billing_ward_id     = get_post_meta( $order->get_id(), '_billing_svw_ward', true );

			if ( $type === 'billing' ) {
				$array['svw_province'] = $billing_province_id;
				$array['svw_district'] = $billing_district_id;
				$array['svw_ward']     = $billing_ward_id;
			} elseif ( $type === 'shipping' ) {
				$array['svw_province'] = $shipping_province_id;
				$array['svw_district'] = $shipping_district_id;
				$array['svw_ward']     = $shipping_ward_id;
			}

			return $array;
		}

		function svw_woocommerce_localisation_address_formats( $array ) {
			$array['default'] = "Họ Tên : {name}\nCông Ty : {company}\nĐịa Chỉ : {address_1}\nPhường/ Xã : {svw_ward}\nQuận/ Huyện : {svw_district}\nTỉnh/ Thành Phố: {svw_province}";
			$array['VN'] = "Họ Tên : {name}\nCông Ty : {company}\nĐịa Chỉ : {address_1}\nPhường/ Xã : {svw_ward}\nQuận/ Huyện : {svw_district}\nTỉnh/ Thành Phố : {svw_province}";

			return $array;
		}

		function svw_woocommerce_formatted_address_replacements( $array, $args ) {
			if ( isset( $args['svw_province'] ) && $args['svw_province'] ) {
				$province_id = $args['svw_province'];
			}

			if ( isset( $args['svw_district'] ) && $args['svw_district'] ) {
				$district_id = $args['svw_district'];
			}

			if ( isset( $args['svw_ward'] ) && $args['svw_ward'] ) {
				$ward_id     = $args['svw_ward'];
			}

			if ( isset( $province_id ) && $province_id ) {
				$array['{svw_province}'] = SVW_Ultility::get_detail_province( $province_id );
			}

			if ( isset( $district_id ) && $district_id ) {
				$array['{svw_district}'] = SVW_Ultility::get_detail_district( $district_id );
			}

			if ( isset( $ward_id ) && $ward_id ) {
				$array['{svw_ward}'] = SVW_Ultility::get_detail_ward( $ward_id );
			}

			return $array;
		}

		function svw_woocommerce_admin_billing_fields( $array ) {
			$array['company']['show'] = false;

			return $array;
		}

		function svw_woocommerce_admin_shipping_fields( $array ) {
			$array['company']['show'] = false;
			$array['phone']['label']  = esc_html__( 'Điện Thoại', 'svw' );
			$array['phone']['show']   = true;

			return $array;
		}

		function svw_woocommerce_order_formatted_billing_address( $array ) {
			unset($array['address_2']);
			unset($array['state']);
			unset($array['postcode']);
			unset($array['country']);

			return $array;
		}

		function svw_woocommerce_order_formatted_shipping_address( $array ) {
			unset($array['address_2']);
			unset($array['state']);
			unset($array['postcode']);
			unset($array['country']);

			return $array;
		}

		// Thêm box tạo vận đơn vào sidebar trong admin
		public function svw_add_meta_boxes() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Orders.
			foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
				$order_type_object = get_post_type_object( $type );
				add_meta_box( 'woocommerce-shipping-actions', esc_html__( 'Vận Đơn', 'svw' ), 'SVW_Custom_Fields_Order::output', $type, 'side', 'high' );
			}
		}

		public static function output( $post ) {
			?>
			<ul class="shipping_actions submitbox">

				<?php do_action( 'svw_woocommerce_shipping_actions_start', $post->ID ); ?>

				<li class="wide" id="actions">
					<?php

						$order                = wc_get_order( $post->ID );
						$order_data           = $order->get_data();
						$recipient_name          = get_post_meta( $post->ID, '_shipping_first_name', true ).' '.get_post_meta( $post->ID, '_shipping_last_name', true );
						$recipient_address       = get_post_meta( $post->ID, '_shipping_address_1', true );
						$recipient_phone         = get_post_meta( $post->ID, '_shipping_phone', true );
						$recipient_phone         = get_post_meta( $post->ID, '_shipping_phone', true );
						$recipient_province_id   = (int) get_post_meta( $post->ID, '_shipping_svw_province', true );
						$recipient_province_name = SVW_Ultility::get_detail_province( $recipient_province_id );
						$recipient_district_id   = (int) get_post_meta( $post->ID, '_shipping_svw_district', true );
						$recipient_district_name = SVW_Ultility::get_detail_district( $recipient_district_id );
						$recipient_ward_id       = get_post_meta( $post->ID, '_shipping_svw_ward', true );
						$recipient_ward_name     = SVW_Ultility::get_detail_ward( $recipient_ward_id );

						foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
							// Data dùng cho giao hàng nhanh
							$shop_id          =  $shipping_item_obj['shop_id'];
							$service_id       =  $shipping_item_obj['service_id'];
							$from_district_id =  $shipping_item_obj['from_district_id'];
							$to_district_id   =  $shipping_item_obj['to_district_id'];
							$to_ward_id       =  $shipping_item_obj['to_ward_id'];

							// Data dùng cho giao hàng tiết kiệm
							$pick_province =  $shipping_item_obj['pick_province'];
							$pick_district =  $shipping_item_obj['pick_district'];
							$province      =  $shipping_item_obj['province'];
							$district      =  $shipping_item_obj['district'];

							$total_weight     =  $shipping_item_obj['total_weight'];

							$shipping_method_title     = $shipping_item_obj->get_method_title();
						    $shipping_method_id        = $shipping_item_obj->get_method_id(); // The method ID
						    $shipping_method_total     = $shipping_item_obj->get_total();
						}

						if ( isset( $from_district_id ) && isset( $to_district_id ) && isset( $to_ward_id ) && isset( $total_weight )
							|| isset( $pick_province ) && isset( $pick_district ) && isset( $province ) && isset( $district ) ) :
						// Nếu sử dụng giao hàng nhanh
						if ( $shipping_method_id == 'svw_shipping_ghn' ) :
							$sender_data_ghn = new SVW_Shipping_Method_Ghn();
							// Néu chưa cấu hình giao hàng nhanh trong setting woo
							if ( isset( $sender_data_ghn->settings['sender_name'] ) &&
								isset( $sender_data_ghn->settings['sender_address'] ) &&
								isset( $sender_data_ghn->settings['sender_phone'] ) &&
								isset( $sender_data_ghn->settings['sender_province'] ) &&
								isset( $sender_data_ghn->settings['sender_district'] ) &&
								isset( $sender_data_ghn->settings['sender_ward'] ) &&
								isset( $sender_data_ghn->settings['sender_token'] ) &&
								$sender_data_ghn->settings['sender_name'] &&
								$sender_data_ghn->settings['sender_address'] &&
								$sender_data_ghn->settings['sender_phone'] &&
								$sender_data_ghn->settings['sender_province'] &&
								$sender_data_ghn->settings['sender_district'] &&
								$sender_data_ghn->settings['sender_ward'] &&
								$sender_data_ghn->settings['sender_token']
							) :

								$sender_name          = $sender_data_ghn->settings['sender_name'];
								$sender_address       = $sender_data_ghn->settings['sender_address'];
								$sender_phone         = $sender_data_ghn->settings['sender_phone'];
								$sender_token         = $sender_data_ghn->settings['sender_token'];
								$sender_province_id   = (int) $sender_data_ghn->settings['sender_province'];
								$sender_district_id   = (int) $sender_data_ghn->settings['sender_district'];
								$sender_ward_id       = $sender_data_ghn->settings['sender_ward'];
								$sender_province_name = SVW_Ultility::get_detail_province( $sender_province_id );
								$sender_district_name = SVW_Ultility::get_detail_district( $sender_district_id );
								$sender_ward_name     = SVW_Ultility::get_detail_ward( $sender_ward_id );

								$cod_fee = $order_data['total'] - $shipping_method_total;

								// Nếu đã đăng đơn rồi
								$ghn_code_exist = get_post_meta( $post->ID, '_ghn_code', true );
								if ( !$ghn_code_exist ) :
					?>
									<div class="cd-popup-trigger svw-create-order"><?php esc_html_e( 'TẠO VẬN ĐƠN GHN', 'svw' ); ?></div>

									<div class="cd-popup" role="alert">
										<div class="cd-popup-container">
											<div id="svw-modal-create-order-shipping-ghn">
											    <div id="create_order">
											    	<div class="svw-row">
										                <div class="svw-col-6 sender">
										                    <div class="svw-col-12">
										                        <div class="title"><?php esc_html_e( 'Người gửi', 'svw' ); ?></div>
										                    </div>
										                    <div class="sub-content">
																<div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Họ tên:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $sender_name ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Số điện thoại:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $sender_phone ); ?>
										                            </div>
										                        </div>

										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Địa chỉ:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $sender_address ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Xã/ Phường:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
																		<?php echo wp_kses_post( $sender_ward_name ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Quận/ Huyện:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $sender_district_name ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Tỉnh/Thành Phố:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $sender_province_name ); ?>
										                            </div>
										                        </div>
										                    </div>
										                </div>
										                <div class="svw-col-6 recipient">
										                    <div class="svw-col-12">
										                        <div class="title"><?php esc_html_e( 'Người nhận', 'svw' ); ?></div>
										                        <i class="fa fa-angle-up"></i>
										                    </div>
										                    <div class="sub-content">
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Họ tên:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_name ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Số điện thoại:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_phone ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Địa chỉ:', 'svw' ); ?></label>
										                            </div>
										                           	<div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_address ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Xã/ Phường:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_ward_name ); ?>
										                            </div>
										                        </div>
										                        <div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Quận/ Huyện:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_district_name ); ?>
										                            </div>
										                        </div>
										                       	<div class="svw-row item">
										                            <div class="svw-col-5">
										                                <label><?php esc_html_e( 'Tỉnh/ Thành Phố:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-7">
										                                <?php echo wp_kses_post( $recipient_province_name ); ?>
										                            </div>
										                        </div>
										                    </div>
										                </div>
										            </div>
										            <hr>
										            <div class="svw-row">
										                <div class="svw-col-6 parcel">
										                    <div class="svw-col-12">
										                        <div class="title"><?php esc_html_e( 'Gói Hàng', 'svw' ); ?></div>
										                        <i class="fa fa-angle-up"></i>
										                    </div>
										                    <div class="sub-content">
										                        <div class="svw-row">
										                            <div class="svw-col-4">
										                                <label><?php esc_html_e( 'Mã đơn hàng:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-8">
										                                <?php echo wp_kses_post( '#'.$post->ID ); ?>
										                            </div>
										                        </div>
										                       	<div class="svw-row">
										                            <div class="svw-col-4">
										                                <label><?php esc_html_e( 'Khối lượng', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-8">
										                                <?php
											                                echo wp_kses_post( $total_weight );
										                                ?> gram
										                            </div>
										                        </div>
										                        <div class="svw-row">
										                            <div class="svw-col-4">
										                                <label>Chú ý*:</label>
										                            </div>
										                            <div class="svw-col-8">
										                                <select name="required_note">
										                                	<option value="KHONGCHOXEMHANG"><?php esc_html_e( 'Không cho xem hàng', 'svw' ); ?></option>
										                                	<option value="CHOXEMHANGKHONGTHU"><?php esc_html_e( 'Cho xem hàng, không thử', 'svw' ); ?></option>
										                                	<option value="CHOTHUHANG"><?php esc_html_e( 'Cho thử hàng', 'svw' ); ?></option>
										                                </select>
										                            </div>
										                        </div>
										                    </div>
										                </div>
										                <div class="svw-col-6 package">
										                    <div class="svw-col-12">
										                        <div class="title"><?php esc_html_e( 'Gói Cước', 'svw' ); ?></div>
										                        <i class="fa fa-angle-up"></i>
										                    </div>
										                    <div class="sub-content">
									                            <div class="svw-row">
									                                <div class="svw-col-7">
									                                    <label for="#expenses"><?php esc_html_e( 'Tiền thu hộ (COD):', 'svw' ); ?></label>
									                                </div>
									                                <div class="svw-col-5">
									                                    <?php echo wp_kses_post( $cod_fee ); ?> VNĐ
									                                </div>
									                            </div>
									                            <div class="svw-row">
									                                <div class="svw-col-7">
									                                    <label><?php esc_html_e( 'Mã khuyến mãi:', 'svw' ); ?></label>
									                                </div>
									                                <div class="svw-col-5">
									                                    <input type="text" name="coupon" value"">
									                                </div>
									                            </div>
									                            <div class="svw-row">
									                                <div class="svw-col-7">
									                                    <label><?php esc_html_e( 'Người trả phí vận chuyển:', 'svw' ); ?></label>
									                                </div>
									                                <div class="svw-col-5">
									                                    <select name="payment_type_id">
										                                	<option value="1"><?php esc_html_e( 'Cửa hàng / Người bán', 'svw' ); ?></option>
										                                	<option value="2"><?php esc_html_e( 'Người mua / Người nhận hàng', 'svw' ); ?></option>
										                                </select>
									                                </div>
									                            </div>
										                    </div>
										                </div>
										                <div class="svw-col-12">
								                            <div class="svw-col-12">
								                                <label><?php esc_html_e( 'Ghi chú:', 'svw' ); ?></label>
								                            </div>
								                            <div class="svw-col-12">
								                                <textarea name="note"></textarea>
								                            </div>
								                        </div>
										            </div>
										            <hr>
									                <div class="svw-row">
									                    <div class="svw-col-12">
									                        <div class="title"><?php esc_html_e( 'Cước Phí', 'svw' ); ?></div>
									                        <div class="desc"><?php esc_html_e( 'Thời gian và chi phí giao hàng được tính tại thời điểm khách hàng đặt hàng. Chi phí và thời gian giao hàng dự kiến có thể sẽ thay đổi nếu GHN thay đổi biểu phí tại thời điểm tạo vận đơn', 'svw' ); ?></div>
									                    </div>
									                    <div class="sub-content">
									                        <div class="svw-row">
									                            <div class="svw-col-4">
									                                <label><?php esc_html_e( 'Tổng:', 'svw' ); ?></label>
									                            </div>
									                            <div class="svw-col-8">
									                                <?php echo wp_kses_post( $order_data['total'] ); ?> VNĐ
									                            </div>
									                        </div>
									                        <div class="svw-row">
									                            <div class="svw-col-4">
									                                <label><?php esc_html_e( 'Phí vận chuyển:', 'svw' ); ?></label>
									                            </div>
									                            <div class="svw-col-8">
									                                <?php echo wp_kses_post( $shipping_method_total ) ?> - <?php echo wp_kses_post( $shipping_method_title ) ?>
									                            </div>
									                        </div>
									                    </div>
									                </div>
									                <div class="svw-row">
									                	<div class="svw-col-12">
									                		<div class="svw-response"></div>
									                	</div>
									                </div>
									            </div>
											</div>
											<ul class="cd-buttons">
												<li>
													<div class="svw-submit-order-shipping-ghn"
									                data-to_name="<?php echo esc_attr( $recipient_name ); ?>"
									                data-to_address="<?php echo esc_attr( $recipient_address ); ?>"
									                data-to_phone="<?php echo esc_attr( $recipient_phone ); ?>"
									                data-to_district_id="<?php echo esc_attr( $recipient_district_id); ?>"
									                data-to_ward_code="<?php echo esc_attr( $recipient_ward_id ); ?>"
									                data-return_address="<?php echo esc_attr( $sender_address ); ?>"
									                data-return_phone="<?php echo esc_attr( $sender_phone ); ?>"
									                data-return_district_id="<?php echo esc_attr( $sender_district_id ); ?>"
									                data-return_ward_code="<?php echo esc_attr( $sender_ward_id ); ?>"
									                data-sender_token="<?php echo esc_attr( $sender_token ); ?>"
									                data-cod_fee="<?php echo esc_attr( $cod_fee ); ?>"
									                data-shop_id="<?php echo esc_attr( $shop_id ); ?>"
									                data-service_id="<?php echo esc_attr( $service_id ); ?>"
									                data-total_weight="<?php echo esc_attr( $total_weight ); ?>"
									                data-order_id="<?php echo esc_attr( $post->ID ); ?>"
									                ><?php esc_html_e( 'Đăng Đơn', 'svw' ); ?></div>
												</li>
											</ul>
											<a href="#0" class="cd-popup-close img-replace">Close</a>
										</div> <!-- cd-popup-container -->
									</div> <!-- cd-popup -->
								<?php else: // Nếu đã tạo vận đơn rồi ?>
									<div class="svw-exits">
										<div class="ghn-code">
											<p><?php esc_html_e( 'MÃ ĐƠN GHN', 'svw' ); ?></p>
											<p><?php echo $ghn_code_exist; ?></p>
										</div>
										<div class="svw-ghn-status ghn-status" data-ghn_code=<?php echo esc_attr( $ghn_code_exist ); ?> data-token="<?php echo esc_attr( $sender_token ); ?>">
											<p><?php esc_html_e( 'TRẠNG THÁI ĐƠN', 'svw' ); ?></p>
											<span></span>
										</div>
									</div>
								<?php endif;
							else : // Néu chưa cấu hình giao hàng nhanh trong setting woo
								esc_html_e( 'Bạn chưa nhập đầy đủ thông tin người gửi hàng hoặc chưa kích hoạt phương thức này trong cài đặt Giao Hàng Nhanh', 'svw' );
							endif;
						elseif ( $shipping_method_id == 'svw_shipping_ghtk' ) : // nếu sử dụng giao hàng tiết kiệm
							$sender_data_ghtk = new SVW_Shipping_Method_Ghtk();
							if ( isset( $sender_data_ghtk->settings['sender_name'] ) &&
								isset( $sender_data_ghtk->settings['sender_address'] ) &&
								isset( $sender_data_ghtk->settings['sender_phone'] ) &&
								isset( $sender_data_ghtk->settings['sender_province'] ) &&
								isset( $sender_data_ghtk->settings['sender_district'] ) &&
								isset( $sender_data_ghtk->settings['sender_ward'] ) &&
								isset( $sender_data_ghtk->settings['sender_token'] )
							) :
								$sender_province_id = (int) $sender_data_ghtk->settings['sender_province'];
								$sender_district_id = $sender_data_ghtk->settings['sender_district'];
								$sender_ward_id     = $sender_data_ghtk->settings['sender_ward'];


								$sender_name     = $sender_data_ghtk->settings['sender_name'];
								$sender_address  = $sender_data_ghtk->settings['sender_address'];
								$sender_phone    = $sender_data_ghtk->settings['sender_phone'];
								$sender_token    = $sender_data_ghtk->settings['sender_token'];
								$sender_province_name = SVW_Ultility::get_detail_province( $sender_province_id );
								$sender_district_name = SVW_Ultility::get_detail_district( $sender_district_id );
								$sender_ward_name     = SVW_Ultility::get_detail_ward( $sender_ward_id );

								$cod_fee = $order_data['total'] - $shipping_method_total;
								$ghtk_code_exist = get_post_meta( $post->ID, '_ghtk_code', true );
								if ( !$ghtk_code_exist ) :
					?>
										<div class="cd-popup-trigger svw-create-order"><?php esc_html_e( 'TẠO VẬN ĐƠN GHTK', 'svw' ); ?></div>

											<div class="cd-popup">
												<div class="cd-popup-container">
													<div id="svw-modal-create-order-shipping-ghtk">
													    <div id="create_order">
													    	<div class="svw-row">
												                <div class="svw-col-6 sender">
												                    <div class="svw-col-12">
												                        <div class="title"><?php esc_html_e( 'Người gửi', 'svw' ); ?></div>
												                    </div>
												                    <div class="sub-content">
																		<div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Họ Tên:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $sender_name ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Số Điện Thoại:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $sender_phone ); ?>
												                            </div>
												                        </div>

												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Địa Chỉ:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $sender_address ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Xã/Phường:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
																				<?php echo wp_kses_post( $sender_ward_name ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Quận/ Huyện:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $sender_district_name ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Tỉnh/Thành Phố:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $sender_province_name ); ?>
												                            </div>
												                        </div>
												                    </div>
												                </div>
												                <div class="svw-col-6 recipient">
												                    <div class="svw-col-12">
												                        <div class="title"><?php esc_html_e( 'Người nhận', 'svw' ); ?></div>
												                        <i class="fa fa-angle-up"></i>
												                    </div>
												                    <div class="sub-content">
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Họ Tên:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_name ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Số Điện Thoại:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_phone ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Địa Chỉ:', 'svw' ); ?></label>
												                            </div>
												                           	<div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_address ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Xã/ Phường:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_ward_name ); ?>
												                            </div>
												                        </div>
												                        <div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Quận/ Huyện:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_district_name ); ?>
												                            </div>
												                        </div>
												                       	<div class="svw-row item">
												                            <div class="svw-col-5">
												                                <label><?php esc_html_e( 'Tỉnh/ Thành Phố:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-7">
												                                <?php echo wp_kses_post( $recipient_province_name ); ?>
												                            </div>
												                        </div>
												                    </div>
												                </div>
												            </div>
												            <div class="svw-row">
												                <div class="svw-col-6 parcel">
												                    <div class="svw-col-12">
												                        <div class="title"><?php esc_html_e( 'Gói Hàng', 'svw' ); ?></div>
												                        <i class="fa fa-angle-up"></i>
												                    </div>
												                    <div class="sub-content">
												                        <div class="svw-row">
												                            <div class="svw-col-4">
												                                <label><?php esc_html_e( 'Mã Đơn Hàng:', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-8">
												                                <?php echo wp_kses_post( '#'.$post->ID ); ?>
												                            </div>
												                        </div>
												                       	<div class="svw-row">
												                            <div class="svw-col-4">
												                                <label><?php esc_html_e( 'Khối Lượng', 'svw' ); ?></label>
												                            </div>
												                            <div class="svw-col-8">
												                                <?php
													                                echo wp_kses_post( $total_weight );
												                                ?> gram
												                            </div>
												                        </div>
												                    </div>
												                </div>
												                <div class="svw-col-6 package">
												                    <div class="svw-col-12">
												                        <div class="title"><?php esc_html_e( 'Gói Cước', 'svw' ); ?></div>
												                        <i class="fa fa-angle-up"></i>
												                    </div>
												                    <div class="sub-content">
											                            <div class="svw-row">
											                                <div class="svw-col-7">
											                                    <label for="#expenses"><?php esc_html_e( 'Tiền Thu Hộ (COD):', 'svw' ); ?></label>
											                                </div>
											                                <div class="svw-col-5">
											                                    <?php echo wp_kses_post( $cod_fee ); ?> VNĐ
											                                </div>
											                            </div>
												                    </div>
												                </div>
												                <div class="svw-col-12">
										                            <div class="svw-col-12">
										                                <label><?php esc_html_e( 'Ghi Chú:', 'svw' ); ?></label>
										                            </div>
										                            <div class="svw-col-12">
										                                <textarea name="note"></textarea>
										                            </div>
										                        </div>
												            </div>
											                <div class="svw-row">
											                    <div class="svw-col-12">
											                        <div class="title"><?php esc_html_e( 'Cước Phí', 'svw' ); ?></div>
											                        <div class="desc"><?php esc_html_e( 'Thời gian và chi phí giao hàng được tính tại thời điểm khách hàng đặt hàng. Chi phí và thời gian giao hàng dự kiến có thể sẽ thay đổi nếu GHTK thay đổi biểu phí tại thời điểm tạo vận đơn', 'svw' ); ?></div>
											                    </div>
											                    <div class="sub-content">
											                        <div class="svw-row">
											                            <div class="svw-col-4">
											                                <label><?php esc_html_e( 'Tổng:', 'svw' ); ?></label>
											                            </div>
											                            <div class="svw-col-8">
											                                <?php echo wp_kses_post( $order_data['total'] ); ?> VNĐ
											                            </div>
											                        </div>
											                        <div class="svw-row">
											                            <div class="svw-col-4">
											                                <label><?php esc_html_e( 'Phí Vận Chuyển:', 'svw' ); ?></label>
											                            </div>
											                            <div class="svw-col-8">
											                                <?php echo wp_kses_post( $shipping_method_total ) ?> - <?php echo wp_kses_post( $shipping_method_title ) ?>
											                            </div>
											                        </div>
											                    </div>
											                </div>

											                <div class="svw-row">
											                	<div class="svw-col-12">
											                		<div class="svw-response"></div>
											                	</div>
											                </div>
											            </div>
													</div>
													<ul class="cd-buttons">
														<li>
															<div class="svw-submit-order-shipping-ghtk"
																data-name="<?php echo esc_attr( $recipient_name ); ?>"
																data-address="<?php echo esc_attr( $recipient_address ); ?>"
																data-tel="<?php echo esc_attr( $recipient_phone ); ?>"
																data-province="<?php echo esc_attr( $recipient_province_name ); ?>"
																data-district="<?php echo esc_attr( $recipient_district_name ); ?>"
																data-ward="<?php echo esc_attr( $recipient_ward_name ); ?>"
																data-pick_name="<?php echo esc_attr( $sender_name ); ?>"
																data-pick_address="<?php echo esc_attr( $sender_address ); ?>"
																data-pick_tel="<?php echo esc_attr( $sender_phone ); ?>"
																data-pick_province="<?php echo esc_attr( $sender_province_name ); ?>"
																data-pick_district="<?php echo esc_attr( $sender_district_name ); ?>"
																data-pick_ward="<?php echo esc_attr( $sender_ward_name ); ?>"
																data-sender_token="<?php echo esc_attr( $sender_token ); ?>"
																data-pick_money="<?php echo esc_attr( $cod_fee ); ?>"
																data-total_weight="<?php echo esc_attr( $total_weight ); ?>"
																data-order_id="<?php echo esc_attr( $post->ID ); ?>"
															>
																<?php esc_html_e( 'Đăng Đơn', 'svw' ); ?>
															</div>
														</li>
													</ul>
													<a href="#0" class="cd-popup-close img-replace">Close</a>
												</div> <!-- cd-popup-container -->
											</div> <!-- cd-popup -->

								<?php else : ?>
									<div class="svw-exits">
										<div class="ghtk-code">
											<p><?php esc_html_e( 'MÃ ĐƠN GHTK', 'svw' ); ?></p>
											<p><?php echo $ghtk_code_exist; ?></p>
										</div>
										<div class="svw-ghtk-status ghtk-status" data-ghtk_code=<?php echo esc_attr( $ghtk_code_exist ); ?> data-token="<?php echo esc_attr( $sender_token ); ?>">
											<p><?php esc_html_e( 'TRẠNG THÁI ĐƠN', 'svw' ); ?></p>
											<span></span>
										</div>
									</div>
								<?php endif; ?>
							<?php
							else :
								esc_html_e( 'Bạn chưa nhập đầy đủ thông tin người gửi hàng hoặc chưa kích hoạt phương thức này trong cài đặt Giao Hàng Tiết Kiệm.', 'svw' );
							endif;
							?>
					<?php endif; ?>
					<?php else:
						esc_html_e( 'Bạn không thể tạo vận đơn vì đơn hàng này được tạo trước khi active Shipping Viet Nam WooCommerce.', 'svw' );
					endif; ?>
				</li>

				<?php do_action( 'svw_woocommerce_shipping_actions_end', $post->ID ); ?>

			</ul>
			<?php
		}
	}

	new SVW_Custom_Fields_Order();
}