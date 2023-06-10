jQuery(document).ready(function($){
    $('#shipping-pickup-options input[type=radio]').change(function() {
        var data = {
            'action': 'my_action', // This should match the action hooked in your PHP function.
            'selected_option': $(this).val()
        };
        
        $.post(my_ajax_object.ajax_url, data, function(response) {
            console.log('test response')
        });
    });

    // show pickup location dropdown

    $('#shipping').click(function() {
        $('#pickup-location').hide();
    });

    $('#pickup').click(function() {
        $('#pickup-location').show();
    });

    // show required label if needed

    $('.single_add_to_cart_button').click(function(e) {
        if (!$('#shipping').is(':checked') && !$('#pickup').is(':checked')) {
            e.preventDefault();
            $('#delivery-required').show();
        } else {
            $('#delivery-required').hide();
        }
    });

    $('#sync-button').click(syncWithSquare);
});

function syncWithSquare() {
    jQuery.ajax({
        url: my_ajax_object.ajax_url,
        type: 'post',
        data: {
            action: 'get_places'
        },
        success: function(response) {
            // Here you can handle the response, which contains the locations
            console.log(response);
        },
        error: function(error) {
            // Here you can handle any errors that occurred during the request
            console.log(error);
        }
    });
}
