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
 * The plugin class.
 */
class WCSquared {
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
		$loader->addPsr4( 'WCSquared\\', __DIR__ . '/includes' );
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