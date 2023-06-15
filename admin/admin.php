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
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_wcsquared_tab', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_wcsquared_tab', __CLASS__ . '::update_settings' );
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

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wcsquared_tab'] = __( 'WC Squared', 'wcsquared-tab' );
        return $settings_tabs;
    }

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		woocommerce_admin_fields( self::get_settings() );

		$api_key = get_option('wc_squared_api_key');

		if (!self::isApiKeyValid($api_key)) {
			echo '<h5>Incorrect or empty key</h5>';
		}
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields().
	 */
	public static function get_settings() {
		$settings = array(
			'section_title' => array(
				'name'     => __( 'WC SQUARED SETTINGS', 'wcsquared-settings-tab' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcsquared_tab_section_title'
			),
			'title' => array(
				'name' => __( 'API KEY', 'wcsquared-settings-tab' ),
				'type' => 'password',
				'desc' => __( 'Enter your Square API key', 'wcsquared-settings-tab' ),
				'id'   => 'wc_squared_api_key',
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id'   => 'wc_squared_section_end'
			),
		);

		return apply_filters( 'wcsquared_tab_settings', $settings );
	}

	private static function isApiKeyValid($api_key) {
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
