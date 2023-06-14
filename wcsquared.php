<?php
/**
 * Plugin Name: WC Squared
 * Plugin URI: https://github.com
 * Description: Extra special integrations for Square and Woocommerce.
 * Version: 1.0.0
 * Author: Jon Skinner
 * Author URI: https://skinnerconsulting.tech
 **/

require 'vendor/autoload.php';
require 'admin/admin.php';
require 'includes/wcsquared-base.php';

use Square\SquareClient;
use Square\Environment;

class WC_Squared {
	private $client;
	private $api_key;
    private $ui;
    private $admin;

	// Initial setup
	public function __construct() {
		$this->api_key = get_option('wc_squared_api_key');
		
		if (empty($this->api_key)) {
			add_action('admin_notices', array($this, 'display_api_key_notice'));
		} else {
			$this->client = new SquareClient([
				'accessToken' => $this->api_key,
				'environment' => Environment::SANDBOX,
			]);
		}

        $this->admin = new WC_Squared_Admin($this->api_key, $this->client);
        $this->ui = new WC_Squared_Base($this->api_key, $this->client);
	}
	
	public function display_api_key_notice() {
		echo '<div class="notice notice-error"><p>Please enter your Square API Key in the plugin settings.</p></div>';
	}
	
	// Activation hook
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'wc_squared_locations';

		$sql = "CREATE TABLE $table_name (
			id varchar(55) NOT NULL,
			name varchar(55) NOT NULL,
			address_line varchar(255) DEFAULT '' NOT NULL,
			locality varchar(55) DEFAULT '' NOT NULL,
			administrative_district varchar(55) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
		  ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// Create the API key option if it doesn't exist
		$api_key = get_option('wc_squared_api_key');
		if (empty($api_key)) {
			add_option('wc_squared_api_key', '');
		}
	}
}

// Instantiating the class.
$wc_squared = new WC_Squared();

// Register activation hook
register_activation_hook(__FILE__, array('WC_Squared', 'activate'));
