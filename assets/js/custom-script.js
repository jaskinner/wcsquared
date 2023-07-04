jQuery(document).ready(function ($) {
	$('#shipping-pickup-options input[type=radio]').change(function () {
		var data = {
			'action': 'my_action', // This should match the action hooked in your PHP function.
			'selected_option': $(this).val()
		};

		$.post(my_ajax_object.ajax_url, data, function (response) {
			console.log('test response')
		});
	});

	// show pickup location dropdown

	$('#shipping').click(function () {
		$('#pickup-location').hide();
	});

	$('#pickup').click(function () {
		if($('#option').val() == '') {
			$('#pickup-location').prop('disabled', true);
		} else {
			$('#pickup-location').prop('disabled', false);
		}
		$('#pickup-location').show();
	});

	$('#option').change(function () {
		if($('#option').val() == '') {
			$('#pickup-location').prop('disabled', true);
		} else {
			$('#pickup-location').prop('disabled', false);
		}
	})
	
	$( '.variations_form' ).on( 'found_variation', function( event, variation ) {
		resetDropdown(variation.sku);
	});
	
	function resetDropdown(sku) {
		jQuery.ajax({
			url: my_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'get_pickup_locations',
				sku: sku
			},
			success: function( options_html ) {
				jQuery('#pickup-location').html(options_html);
			},
			error: function (xhr, status, error) {
				console.log('Error:', error);
			}
		});
	}
});