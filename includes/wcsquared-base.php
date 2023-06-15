<?php
/**
 * WC Squared Base
 */

 class WC_Squared_Base {
	private $api_key;

	public function __construct($api_key) {

		$this->api_key = $api_key;

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('woocommerce_before_add_to_cart_button', array($this, 'add_content_before_addtocart'));
		add_action('wp_ajax_nopriv_my_action', array($this, 'ajax_handler'));
		add_action('wp_ajax_my_action', array($this, 'ajax_handler'));
		add_action('woocommerce_add_to_cart_validation', array($this, 'validate_delivery_option'), 10, 3);
		add_action('wp_ajax_get_pickup_locations', array($this, 'get_pickup_locations_handler'));
		add_action('wp_ajax_nopriv_get_pickup_locations', array($this, 'get_pickup_locations_handler'));    
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'my-plugin-custom-script',
			plugins_url('../assets/js/custom-script.js', __FILE__),
			array('jquery'),
			'1.0',
			true
		);

		// Localize the script with new data
		wp_localize_script('my-plugin-custom-script', 'my_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	public function add_content_before_addtocart() {
		echo
			'<div id="shipping-pickup-options">
				<input type="radio" id="shipping" name="delivery" value="shipping">
				<label for="shipping">Shipping</label><br>
				<input type="radio" id="pickup" name="delivery" value="pickup">
				<label for="pickup">Pickup</label><br>
				<select id="pickup-location" style="display:none;">
					<option value="">Select a pickup location...</option>
				</select>
				<span id="delivery-required" style="color:red; display:none;">Required</span>
			</div>';
	}

	public function validate_delivery_option($passed, $product_id, $quantity) {
		if (isset($_POST['delivery']) && ($_POST['delivery'] === 'shipping' || $_POST['delivery'] === 'pickup')) {
			return $passed;
		} else {
			wc_add_notice('Please choose either Shipping or Pickup before proceeding.', 'error');
			return false;
		}
	}
}