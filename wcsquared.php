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
require 'autoload.php';

class WC_Squared {
    private $ui;
    private $admin;
    private $db_handler;

	// Initial setup
    public function __construct(Admin $admin, Base $ui, DatabaseHandler $db_handler) {
        $this->admin = $admin;
        $this->ui = $ui;
        $this->db_handler = $db_handler;
    }
	
    // Activation hook
    public static function activate() {
        $db_handler = new DatabaseHandler();
        $db_handler->createLocationsTable();
        $db_handler->createInventoryTable();

        // Create the API key option if it doesn't exist
        $api_key = get_option('wc_squared_api_key');
        if (empty($api_key)) {
            add_option('wc_squared_api_key', '');
        }
    }
}

$admin = new Admin();
$ui = new Base();
$db_handler = new DatabaseHandler();

// Instantiating the class.
$wc_squared = new WC_Squared($admin, $ui, $db_handler);

// Register activation hook
register_activation_hook(__FILE__, array('WC_Squared', 'activate'));
