<?php
/**
 * Base
 */

class Base {
	public function __construct() {

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('woocommerce_before_add_to_cart_button', array($this, 'add_content_before_addtocart'));
		add_action('wp_ajax_get_pickup_locations', array($this, 'add_content_before_addtocart'));
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
		$sku = $_POST['sku'];

		try {
			if ($sku) {
				$sku = $_POST['sku'];
				global $wpdb;
				$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );			
			} else {
				global $product;
				$product_id = $product->get_id();
			
				// Check if the product has variations
				if( $product->is_type( 'variable' ) ) {
					$variations = $product->get_available_variations();
					$variation_id = $variations[0]['variation_id']; // Use the first variation for this example
				} else {
					$variation_id = false;
				}
			}
			
			// You can now use $product_id or $variation_id to query the database
			global $wpdb;
			$inventory_table = $wpdb->prefix . 'wc_squared_inventory';
			$locations_table = $wpdb->prefix . 'wc_squared_locations';
			
			if($variation_id) {
				// Query using variation ID with INNER JOIN
				$result = $wpdb->get_results( $wpdb->prepare( 
					"SELECT * 
					FROM $inventory_table as inventory 
					INNER JOIN $locations_table as locations ON inventory.location_id = locations.id 
					WHERE inventory.post_id = %d", $variation_id ) );
			} else {
				// Query using product ID with INNER JOIN
				$result = $wpdb->get_results( $wpdb->prepare( 
					"SELECT * 
					FROM $inventory_table as inventory 
					INNER JOIN $locations_table as locations ON inventory.location_id = locations.id 
					WHERE inventory.post_id = %d", $product_id ) );
			}
			
			echo '<div id="shipping-pickup-options">
					<input type="radio" id="shipping" name="delivery" value="shipping">
					<label for="shipping">Shipping</label><br>
					<input type="radio" id="pickup" name="delivery" value="pickup">
					<label for="pickup">Pickup</label><br>
					<select id="pickup-location" style="display:none;">
						<option value="">Select a pickup location...</option>';
		
			// Loop through results and add as options to the select
			foreach($result as $row) {
				if ($row->quantity <= 0) {
					echo '<option value="' . esc_attr($row->id) . '" disabled="disabled">' . esc_html($row->name) . ' | OUT OF STOCK' . '</option>';
				} else {
					echo '<option value="' . esc_attr($row->id) . '">' . esc_html($row->name) . ' | stock: ' . esc_attr($row->quantity) . '</option>';
				}
			}
				
			echo '</select>
				<span id="delivery-required" style="color:red; display:none;">Required</span>
			</div>';
		} catch (Exception $e) {
			throw $e;
		}
	}
}
