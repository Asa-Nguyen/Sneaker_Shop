jQuery(document).ready(function(){

	// https://codepen.io/adventuresinmissions/pen/nrhHF/
	//open popup
	jQuery('.cd-popup-trigger').on('click', function(event){
		event.preventDefault();
		jQuery('.cd-popup').addClass('is-visible');
	});

	//close popup
	jQuery('.cd-popup').on('click', function(event){
		if( jQuery(event.target).is('.cd-popup-close') || jQuery(event.target).is('.cd-popup') ) {
			event.preventDefault();
			jQuery(this).removeClass('is-visible');
		}
	});

	//close popup when clicking the esc keyboard button
	jQuery(document).keyup(function(event){
    	if(event.which=='27'){
    		jQuery('.cd-popup').removeClass('is-visible');
	    }
    });


	jQuery("#woocommerce_svw_shipping_ghn_sender_province,#woocommerce_svw_shipping_ghn_sender_district,#woocommerce_svw_shipping_ghn_sender_ward, #woocommerce_svw_shipping_ghtk_sender_province, #woocommerce_svw_shipping_ghtk_sender_district, #woocommerce_svw_shipping_ghtk_sender_ward").select2();

	// Update Quận/ Huyện Khi Chọn Thành Phố GHN
	if ( jQuery('#woocommerce_svw_shipping_ghn_sender_province, #woocommerce_svw_shipping_ghtk_sender_province').length > 0 ) {
		jQuery('#woocommerce_svw_shipping_ghn_sender_province, #woocommerce_svw_shipping_ghtk_sender_province').on('change', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw_admin_params.ajax.url,
			  	data: {
			  		province_id : jQuery(this).val(),
			  		action: 'admin_update_shipping_method_district'
			  	}
			}).done(function(result) {
				jQuery('#woocommerce_svw_shipping_ghn_sender_district, #woocommerce_svw_shipping_ghtk_sender_district').html(result);
			});
		});
	}

	// Update Xã / Phường Khi Chọn Thành Phố GHN
	if ( jQuery('#woocommerce_svw_shipping_ghn_sender_district, #woocommerce_svw_shipping_ghtk_sender_district').length > 0 ) {
		jQuery('#woocommerce_svw_shipping_ghn_sender_district, #woocommerce_svw_shipping_ghtk_sender_district').on('change', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw_admin_params.ajax.url,
			  	data: {
			  		district_id : jQuery(this).val(),
			  		action: 'admin_update_shipping_method_ward'
			  	}
			}).done(function(result) {
				jQuery('#woocommerce_svw_shipping_ghn_sender_ward, #woocommerce_svw_shipping_ghtk_sender_ward').html(result);
			});
		});
	}

	// Tạo vận đơn trên GHN
	if ( jQuery('.svw-submit-order-shipping-ghn').length > 0 ) {
		jQuery('.svw-submit-order-shipping-ghn').on('click', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw_admin_params.ajax.url,
			  	data: {
					action                : 'create_order_ghn',
					to_name               : jQuery(this).data('to_name'),
					to_address            : jQuery(this).data('to_address'),
					to_phone              : jQuery(this).data('to_phone'),
					recipient_province_id : jQuery(this).data('recipient_province_id'),
					to_district_id        : jQuery(this).data('to_district_id'),
					to_ward_code          : jQuery(this).data('to_ward_code'),
					return_address        : jQuery(this).data('return_address'),
					return_phone          : jQuery(this).data('return_phone'),
					return_district_id    : jQuery(this).data('return_district_id'),
					return_ward_code      : jQuery(this).data('return_ward_code'),
					sender_token          : jQuery(this).data('sender_token'),
					cod_fee               : jQuery(this).data('cod_fee'),
					shop_id               : jQuery(this).data('shop_id'),
					service_id            : jQuery(this).data('service_id'),
					total_weight          : jQuery(this).data('total_weight'),
					order_id              : jQuery(this).data('order_id'),
					required_note         : jQuery('select[name="required_note"]').val(),
					payment_type_id       : jQuery('select[name="payment_type_id"]').val(),
					coupon                : jQuery('input[name="coupon"]').val(),
					note                  : jQuery('textarea[name="note"]').val(),
			  	},
			  	beforeSend: function( xhr ) {
				    jQuery('.svw-submit-order-shipping-ghn').html('ĐANG XỬ LÝ ...');
				    jQuery('.svw-submit-order-shipping-ghn').attr('disabled','disabled');
				}
			}).done(function(result) {
				jQuery('.svw-submit-order-shipping-ghn').remove();
				jQuery('.svw-response').html(result);
			});
		});
	}

	if ( jQuery('.svw-submit-order-shipping-ghtk').length > 0 ) {
		jQuery('.svw-submit-order-shipping-ghtk').on('click', function() {
			jQuery.ajax({
				type: 'POST',
			  	url: svw_admin_params.ajax.url,
			  	data: {
					action        : 'create_order_ghtk',
					name          : jQuery(this).data('name'),
					address       : jQuery(this).data('address'),
					tel           : jQuery(this).data('tel'),
					province      : jQuery(this).data('province'),
					district      : jQuery(this).data('district'),
					ward          : jQuery(this).data('ward'),
					hamlet        : 'Khac',
					pick_name     : jQuery(this).data('pick_name'),
					pick_address  : jQuery(this).data('pick_address'),
					pick_tel      : jQuery(this).data('pick_tel'),
					pick_province : jQuery(this).data('pick_province'),
					pick_ward 	  : jQuery(this).data('pick_ward'),
					pick_district : jQuery(this).data('pick_district'),
					sender_token  : jQuery(this).data('sender_token'),
					cod_fee       : jQuery(this).data('cod_fee'),
					total_weight  : jQuery(this).data('total_weight'),
					order_id      : jQuery(this).data('order_id'),
					pick_money    : jQuery(this).data('pick_money'),
					note          : jQuery('textarea[name="note"]').val(),
			  	},
			  	beforeSend: function( xhr ) {
				    jQuery('.svw-submit-order-shipping-ghtk').html('ĐANG XỬ LÝ ...');
				    jQuery('.svw-submit-order-shipping-ghtk').attr('disabled','disabled');
				}
			}).done(function(result) {
				jQuery('.svw-submit-order-shipping-ghtk').remove();
				jQuery('.svw-response').html(result);
			});
		});
	}

	// Lấy trạng thái vận đơn
	if ( jQuery('.svw-ghn-status').length > 0 ) {
		jQuery.ajax({
			type: 'POST',
		  	url: svw_admin_params.ajax.url,
		  	data: {
				ghn_code: jQuery('.svw-ghn-status').data('ghn_code'),
				action  : 'get_status_order_ghn',
				token   : jQuery('.svw-ghn-status').data('token')
		  	}
		}).done(function(result) {
			if ( result == 'Cancel' ) {
				jQuery('.svw-ghn-cancel-order').remove();
			}
			jQuery('.svw-ghn-status span').html(result);
		});
	}

	if ( jQuery('.svw-ghtk-status').length > 0 ) {
		jQuery.ajax({
			type: 'POST',
		  	url: svw_admin_params.ajax.url,
		  	data: {
				ghtk_code: jQuery('.svw-ghtk-status').data('ghtk_code'),
				action  : 'get_status_order_ghtk',
				token   : jQuery('.svw-ghtk-status').data('token')
		  	}
		}).done(function(result) {
			jQuery('.svw-ghtk-status span').html(result);
		});
	}

	if ( jQuery('.svw-ghn-cancel-order').length > 0 ) {
		jQuery('.svw-ghn-cancel-order').click(function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw_admin_params.ajax.url,
			  	data: {
					ghn_code: jQuery('.svw-ghn-cancel-order').data('ghn_code'),
					action  : 'cancel_order_ghn',
					token   : jQuery('.svw-ghn-cancel-order').data('token'),
					order_id: jQuery('.svw-ghn-cancel-order').data('order_id')
			  	}
			}).done(function(result) {
				alert( result );
			});
		});
	}
});