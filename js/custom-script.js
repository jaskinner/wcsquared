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

    // show required label if needed

    $('.single_add_to_cart_button').click(function (e) {
        if (!$('#shipping').is(':checked') && !$('#pickup').is(':checked')) {
            e.preventDefault();
            $('#delivery-required').show();
        } else {
            $('#delivery-required').hide();
        }
    });

    $('#sync-button').click(syncWithSquare);
    $('#save-key-button').click(saveApiKey);


    function syncWithSquare() {
        jQuery.ajax({
            url: my_ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'get_places'
            },
            success: function (response) {
                // Here you can handle the response, which contains the locations
                console.log(response);
            },
            error: function (error) {
                // Here you can handle any errors that occurred during the request
                console.log(error);
            }
        });
    }
    
    function saveApiKey() {
        jQuery.ajax({
            url: my_ajax_object.ajax_url,
            type: 'post',
            data: {
                action: 'save_api_key',
                api_key: $('#api-key').val() // Replace 'api-key-input' with the ID of your input field
            },
            success: function (response) {
                // Here you can handle the response, which contains the locations
                console.log(response);
            },
            error: function (error) {
                // Here you can handle any errors that occurred during the request
                console.log(error);
            }
        });
    }
    
    function getLocations() {
        jQuery.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_pickup_locations'
            },
            success: function (response) {
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