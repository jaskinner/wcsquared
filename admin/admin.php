<?php
/**
 * WC Squared Admin
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;
 
class WC_Squared_Admin {
	private $api_key;
	private $client;

	public function __construct($api_key, $client) {
		$this->api_key = $api_key;
		$this->client = $client;

		add_action('wp_ajax_get_places', array($this, 'sync_locations_handler'));
		add_action('wp_ajax_save_api_key', array($this, 'save_api_key_handler'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'my_plugin_menu'));
	}

	public function enqueue_admin_scripts() {
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

	public function my_plugin_menu() {
		add_menu_page('WC Squared Admin', 'WC Squared', 'manage_options', 'location-sync', array($this, 'wc_admin_page'));
	}

	public function wc_admin_page() {
		$api_key = get_option('wc_squared_api_key');

		echo '<h1>WC Squared</h1>';
		if (!$this->isApiKeyValid($api_key)) {
			echo '<h5>Incorrect or empty key</h5>';
		}
		// Show API key input and save button if API key is not set or incorrect
		if (empty($api_key) || !$this->isApiKeyValid($api_key)) {
			echo '<label for="api-key">Square API Key:</label>';
			echo '<input type="text" id="api-key" name="api-key" value="">';
			echo '<button id="save-key-button">Save API Key</button><br><br>';
		} else {
			// API key is set and valid, show other content
			echo '<button id="sync-button">Sync Locations</button>';
			echo '<p>Note: Enter your Square API key above and click "Save API Key" to link your account.</p>';
		}
	}

	private function isApiKeyValid($api_key) {
		$client = new SquareClient([
			'accessToken' => $api_key,
			'environment' => Environment::SANDBOX,
		]);

		try {
			$api_response = $client->getLocationsApi()->listLocations();
			return $api_response->isSuccess();
		} catch (ApiException $e) {
			return false;
		}
	}

	public function save_api_key_handler() {
		// Retrieve the API key from the AJAX request data
		$api_key = $_POST['api_key'];

		// Save the API key using the WordPress Options API
		update_option('wc_squared_api_key', $api_key);

		// Return a response (optional)
		$response = array('message' => 'API key saved successfully');
		wp_send_json_success($response);
	}

	public function sync_locations_handler() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_squared_locations';

		$api_response = $this->client->getLocationsApi()->listLocations();

		if ($api_response->isSuccess()) {
			$result = $api_response->getResult();

			// Loop over each location.
			foreach ($result->getLocations() as $location) {

				if ($location->getStatus() === "INACTIVE") {
					continue;
				}

				// Extract properties from the location.
				$locationId = $location->getId();
				$name = $location->getName();
				$address = $location->getAddress();

				$addressLine = $address->getAddressLine1();
				$locality = $address->getLocality();
				$administrativeDistrictLevel1 = $address->getAdministrativeDistrictLevel1();

				// Insert or update the data in the database
				$wpdb->replace(
					$table_name,
					array(
						'id' => $locationId,
						'name' => $name,
						'address_line' => $addressLine,
						'locality' => $locality,
						'administrative_district' => $administrativeDistrictLevel1,
					)
				);
			}
		} else {
			$errors = $api_response->getErrors();
			// Handle errors here...
		}
		wp_die();
	}
}
