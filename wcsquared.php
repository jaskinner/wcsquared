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

	// Initial setup
	public function __construct() {
        $this->admin = new Admin();
        $this->ui = new Base();
    }
	
    // Activation hook
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name_locations = $wpdb->prefix . 'wc_squared_locations';
        $table_name_imported_products = $wpdb->prefix . 'wc_squared_imported_products';
        $table_name_inventory = $wpdb->prefix . 'wc_squared_inventory';

        // Create the wc_squared_locations table
        $sql_locations = "CREATE TABLE $table_name_locations (
            id varchar(55) NOT NULL,
            name varchar(55) NOT NULL,
            address_line varchar(255) DEFAULT '' NOT NULL,
            locality varchar(55) DEFAULT '' NOT NULL,
            administrative_district varchar(55) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Create the wc_squared_imported_products table
        $sql_imported_products = "CREATE TABLE $table_name_imported_products (
            product_id bigint(20) NOT NULL,
            location_id varchar(55) NOT NULL,
            PRIMARY KEY  (product_id, location_id),
            FOREIGN KEY (location_id) REFERENCES $table_name_locations (id)
        ) $charset_collate;";

        // Create the wc_squared_inventory table
        $sql_inventory = "CREATE TABLE $table_name_inventory (
            product_id bigint(20) NOT NULL,
            location_id varchar(55) NOT NULL,
            quantity int(11) DEFAULT 0,
            PRIMARY KEY  (product_id, location_id),
            FOREIGN KEY (product_id) REFERENCES $table_name_imported_products (product_id),
            FOREIGN KEY (location_id) REFERENCES $table_name_locations (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_locations);
        dbDelta($sql_imported_products);
        dbDelta($sql_inventory);

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
