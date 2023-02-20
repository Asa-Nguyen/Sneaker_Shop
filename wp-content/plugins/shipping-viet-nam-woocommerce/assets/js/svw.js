jQuery(document).ready(function(){
	jQuery(".svw-select-province, .svw-select-district, .svw-select-ward").select2();

	// Khi chọn province thì lưu province_id vào session
	if ( jQuery('.svw-select-province').length > 0 ) {
		jQuery('.svw-select-province').on( 'change', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw.ajax.url,
			  	data: {
			  		province_id : jQuery(this).val(),
			  		action: 'update_checkout_district'
			  	}
			}).done(function(result) {
				jQuery('.svw-select-district').html(result);
			});
		});
	}

	if ( jQuery('.svw-select-district').length > 0 ) {
		jQuery('.svw-select-district').on('change', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw.ajax.url,
			  	data: {
			  		district_id : jQuery(this).val(),
			  		action: 'update_checkout_ward'
			  	}
			}).done(function(result) {
				jQuery('.svw-select-ward').html(result);
			});
		});
	}

	if ( jQuery('.svw-select-ward').length > 0 ) {
		jQuery('.svw-select-ward').on('change', function(){
			jQuery.ajax({
				type: 'POST',
			  	url: svw.ajax.url,
			  	data: {
			  		ward_id : jQuery(this).val(),
			  		action: 'set_session_ward'
			  	}
			}).done(function(result) {
			});
		});
	}
});