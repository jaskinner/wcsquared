<?php
/**
 * Plugin Name: WC Squared
 * Plugin URI: https://github.com
 * Description: Extra special integrations for Square and Woocommerce.
 * Version: 1.0.0
 * Author: Jon Skinner
 * Author URI: https://skinnerconsulting.tech
 **/

defined( 'ABSPATH' ) || exit;

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

/**
 * The plugin class.
 */
class WCSquared {
	/** the plugin name, for displaying notices */
	const PLUGIN_NAME = 'WC Squared';

	/** the main plugin file */
	const PLUGIN_FILE = __FILE__;
	
	/** @var WCSquared_Loader single instance of this class */
	private static $instance;

	/**
	 * Constructs the class.
	 */	
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
	}

	public function register_hooks() {
		register_activation_hook(self::PLUGIN_FILE, array($this, 'fetch_square_locations'));
		add_action('admin_menu', array($this, 'register_settings_page'));
	}

	public static function init() {
		$instance = self::instance();
		$instance->register_hooks();
	}

	public function init_plugin() {
		// autoload plugin and vendor files
		$loader = require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

		// register plugin namespace with autoloader
		$loader->addPsr4( 'WCSquared\\', __DIR__ . '/includes' );
	}

	public function register_settings_page() {
		add_options_page(
			'WC Squared Settings', // page_title
			'WC Squared', // menu_title
			'manage_options', // capability
			'wcsquared', // menu_slug
			array($this, 'settings_page') // function
		);
	}

	public function fetch_square_locations() {
		
        $api_key = get_option('wc_squared_api_key', null);
        $environment = get_option('wc_squared_environment', 'sandbox'); // Default to sandbox if the option is not set

        // Use the environment variable to set the SquareClient configuration
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => ($environment === 'sandbox') ? Environment::SANDBOX : Environment::PRODUCTION,
        ]);
        
        $api_response = $client->getLocationsApi()->listLocations();

		try {

			if ($api_response->isSuccess()) {
				$result = $api_response->getResult();
				$locations = [];

				foreach ($result->getLocations() as $location) {
					if ($location->getStatus() === "INACTIVE") {
						continue;
					}
	
					$locations[] = $location;
				}
		
				// Store the locations in a WP option
				update_option('square_locations', $locations);
			} else {
				$errors = $api_response->getErrors();
				error_log('Error: ' . $errors);
			}
		} catch (ApiException $e) {
			error_log('Error: ' . $e);
		}
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
	
			<?php
			$locations = get_option('square_locations');
	
			if ($locations) {
				echo '<h2>Locations</h2>';
				echo '<ol>';
	
				// Print the location names
				foreach ($locations as $location) {
					echo '<li>' . $location->getName() . '</li>';
				}
	
				echo '</ol>';
			} else {
				echo '<p>No locations found.</p>';
			}
			?>
		</div>
		<?php
	}
				
	/**
	 * Gets the main plugin instance.
	 *
	 * @return \WCSquared
	 */
	public static function instance() {
		if (null===self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Returns Instance of WC Squared.
 *
 * @return \WCSquared
 */
function wc_squared() {
	return \WCSquared::instance();
}

WCSquared::init();
