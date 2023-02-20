=== Shipping Viet Nam WooCommerce ===
Contributors: longbsvnu
Plugin URI: https://hoangquoclong.com/
Tags: woocommerce, shipping, vietnam, checkout, shop, ghn, ghtk, giao hang nhanh, giao hang tiet kiem
Requires at least: 4.0
Tested up to: 5.5
Stable tag: 3.0.1
License: MIT
License URI: http://opensource.org/licenses/MIT

== Description ==

* Plugin hỗ trợ toàn diện giao vận tại Việt Nam cho WooCommerce. Khách hàng chủ động chọn đơn vị giao vận và các gói giao vận ( Nhanh, Chuẩn, Tiết Kiệm ) tuỳ theo hầu bao của mình, việc này tạo sự tin tưởng cho người mua vì công khai chi phí ship giúp tăng tỉ lệ đặt hàng cho quản trị shop.
* Quản trị shop dễ dàng đăng vận đơn lên các đơn vị giao vận tuỳ theo lựa chọn của khách hàng khi đặt hàng chỉ với 1 Click, cùng với đó là tra cứu trạng thái vận đơn ngay từ trang quản trị. 
* Xem video demo chức năng : https://www.youtube.com/watch?v=vY2nfYgFfa0

=== HỖ TRỢ CÁC ĐƠN VỊ GIAO VẬN ===
* Giao Hàng Nhanh
* Giao Hàng Tiết Kiệm

=== CHỨC NĂNG CHÍNH ===
* Tính toán phí ship ngay khi đặt hàng.
* Khách hàng chủ động chọn gói ship phù hợp.
* Phí ship được tính toán trực tiếp từ GHN và GHTK, khách hàng biết rõ phí ship khi đặt hàng làm tăng độ tin tưởng của khách hàng đi đặt hàng. Shopper không cần tra phí mỗi khi khách hàng hỏi ship về A B C thì mất bao nhiêu phí.
* Đăng vận đơn lên GHN và GHTK chỉ với 1 click.
* Lựa chọn người chịu phí vận chuyển.
* Theo dõi trạng thái vận đơng trong chi tiết đơn hàng.
* Tối ưu hoá form checkout.

=== CHỨC NĂNG BẢN PRO ===
* Hủy vận đơn trực tiếp trong đơn hàng.
* Shortcode tra cứu vận đơn, khách hàng có thể chủ động tra được đơn hàng của mình đang như nào.
* Có thể thêm, thay đổi các tùy chọn khi đăng đơn lên đơn vị giao động.
* Bổ sung thêm các đơn vị giao vận trong tương lai.

=== Không hiện đầy đủ tỉnh thành sau khi active plugin ? ===
Sau khi active plugin, plugin sẽ tiến hành lưu tất cả các thông tin tỉnh/ thành phố, quận/ huyện, phường/ xã vào database của bạn, quá trình này có thể kéo dài 2 - 3 phút. Một số hosting đặt thời gian timeout thấp, dữ liệu tỉnh thành sẽ không được cập nhật đầy đủ, trường hợp đó các bạn sử dụng file sql đính kèm trong plugin import trực tiếp vào database, lưu ý đổi prefix trong file sql đúng với prefix bạn đang sử dụng trước khi import.

=== Lấy token ở đâu ? ===
* Đối với GHN xem hướng dẫn tại đây: https://api.ghn.vn/home/docs/detail
* Đói với GHTK xem hướng dẫn tại đây: https://giaohangtietkiem.vn/lay-api-token-key-nhu-nao/

=== Không hiện phí ship sau khi active plugin ? ===
Mặc tính chức năng này được disable sau khi active, các bạn cần vào WooCommerce -> Setting -> Shipping -> Giao Hàng Nhanh, Giao Hàng Tiết Kiệm để nhập thông tin người gửi và kích hoạt tính phí. Tối thiểu phải có thông tin người gửi, số điện thoại, tỉnh thành, quận huyện và token. đối với GHTK, Token chỉ có hiệu lực sau khi tài khoản đó được nhân viên gọi điện xác nhận tài khoản.

=== Cài đặt trọng lượng sản phẩm như thế nào ? ===
Bạn phải chọn đơn vị trọng lượng trong Woo là KG, đối với sản phẩm dưới 1KG, ví dụ 200 Gram thì nhập 0.2.

== Screenshots ==

1. Kích hoạt và cài đặt thông tin người gửi.
2. Tra cứu trạng thái vận đơn khi xem order.
3. Các field và tính toán chi phí GHN.
4. Đăng đơn lên GHN

== CHANGELOG ==
= 3.0.1 (10/11/2020) =
* Cập database dữ liệu tỉnh, thành, quận, huyện ...

= 3.0.0 (08/10/2020) =
* Cập nhật theo luồng API mới của GHN
* Cập nhật theo luồng API của GHTK
* Mở tùy chọn người trả phí khi đăng đơn
* Cập nhật giao diện

= 2.0.7 (3/6/2020) =
* Fix lỗi một số hosting không hiển thị được quận huyện.

= 2.0.6 (14/1/2019) =
* Fix lỗi GHTK đổi API, từ gram sang kg khi đăng đơn lên GHTK

= 2.0.5 (14/1/2019) =
* Fix lỗi bắt buộc phải nhập đơn vị là kg trong sản phẩm, hiện tại cách nhập sản phẩm có thể linh động Theo setting trong woo ( Cảm ơn Đi Outdoor ( http://dioutdoor.vn ) đã donate 300k để tiếp tục update plugin )

= 2.0.3 (24/5/2018) =
* Fix lỗi active plugin khi chưa kích hoạt WooCommerce 

= 2.0.2 (21/5/2018) =
* Fix lỗi khi đăng đơn lên GHN khi trọng lượng nhỏ hơn 1KG.
* Fix lỗi ko lấy được tên xã khi tạo vận đơn.
* Fix lỗi trắng trang khi ko ko nhận được id của quận/huyện, xã phường. 

= 2.0.1 (21/5/2018) =
* Fix lỗi và hiển thị thông báo đối với các order được tạo trước khi active plugin.

= 2.0.0 (20/5/2018) =
* Bổ sung Giao Hàng Tiết Kiệm.
* Fix các lỗi nhỏ.

= 1.0.0 (19/5/2018) =
* Fix lỗi js conflict với Master Slider

= 1.0.0 (18/5/2018) =
* Phiên bản đầu.

