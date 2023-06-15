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
		$('#pickup-location').show();
		getLocations();
	});
	
	function getLocations() {
		jQuery.ajax({
			url: my_ajax_object.ajax_url,
			type: 'POST',
			data: {
				action: 'get_pickup_locations'
			},
			success: function (response) {
				console.log(response);
				updateLocationDropdown(response);
			},
			error: function (xhr, status, error) {
				console.log('Error:', error);
			}
		});
	}
	
	function updateLocationDropdown(locations) {
		var dropdown = $('#pickup-location');
		dropdown.empty();
		dropdown.append('<option value="">Select a pickup location...</option>');
		$.each(locations, function (index, location) {
			dropdown.append('<option value="' + location.id + '">' + location.name + '</option>');
		});
	}
	
});