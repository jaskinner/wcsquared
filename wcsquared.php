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

/**
 * The plugin loader class.
 */
class WCSquared_Loader {
	/** the plugin name, for displaying notices */
	const PLUGIN_NAME = 'WC Squared';
	
	/** @var WCSquared_Loader single instance of this class */
	private static $instance;

	/**
	 * Constructs the class.
	 */	
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
	}

	public function init_plugin() {
		// autoload plugin and vendor files
		$loader = require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

		// register plugin namespace with autoloader
		$loader->addPsr4( 'WCSquared', __DIR__ . '/includes' );

		require_once plugin_dir_path( __FILE__ ) . 'includes/Functions.php';

		// fire it up!
		wc_squared();
	}
	
	// Activation hook
	// public static function activate() {
	//     $db_handler = new DatabaseHandler();
	//     $db_handler->createLocationsTable();
	//     $db_handler->createInventoryTable();

	//     // Create the API key option if it doesn't exist
	//     $api_key = get_option('wc_squared_api_key');
	//     if (empty($api_key)) {
	//         add_option('wc_squared_api_key', '');
	//     }
	// }

	/**
	 * Gets the main plugin loader instance.
	 *
	 * @return \WCSquared_Loader
	 */
	public static function instance() {
		if (null===self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WCSquared_Loader::instance();
