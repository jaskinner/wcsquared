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
        
 use Square\SquareClient;
 use Square\Environment;
 use Square\Exceptions\ApiException;

class WC_Squared {

    private $client;

    // initial setup

    public function __construct() {
    
        $this->client = new SquareClient([
            'accessToken' => SQUARE_ACCESS_TOKEN,
            'environment' => Environment::SANDBOX,
        ]);
            
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts')); // Changed here
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_content_before_addtocart'));
        add_action('wp_ajax_nopriv_my_action', array($this, 'ajax_handler'));
        add_action('wp_ajax_my_action', array($this, 'ajax_handler'));
        add_action('woocommerce_add_to_cart_validation', array($this, 'validate_delivery_option'), 10, 3);
        add_action('wp_ajax_get_places', array($this, 'sync_locations_handler'));
        
        if (is_admin()) {
            add_action('admin_menu', array($this, 'my_plugin_menu'));
        }
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
          
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    public function enqueue_admin_scripts() { // Changed here
        wp_enqueue_script(
            'my-plugin-custom-script',
            plugins_url('js/custom-script.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
    
        // Localize the script with new data
        wp_localize_script('my-plugin-custom-script', 'my_ajax_object',
            array('ajax_url' => admin_url('admin-ajax.php')));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'my-plugin-custom-script',
            plugins_url('js/custom-script.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );

        // Localize the script with new data
        wp_localize_script('my-plugin-custom-script', 'my_ajax_object',
            array('ajax_url' => admin_url('admin-ajax.php')));
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
                    <!-- Populate this dropdown dynamically or statically with your pickup locations -->
                </select>
                <span id="delivery-required" style="color:red; display:none;">Required</span>
            </div>';
    }

    public function validate_delivery_option($passed) {
        if (isset($_POST['delivery']) && ($_POST['delivery'] === 'shipping' || $_POST['delivery'] === 'pickup')) {
            return $passed;
        } else {
            wc_add_notice('Please choose either Shipping or Pickup before proceeding.', 'error');
            return false;
        }
    }    

    // admin stuff

    public function my_plugin_menu() {
        add_menu_page('WC Squared Admin', 'WC Squared', 'manage_options', 'location-sync', array($this, 'location_sync_page'));
    }

    public function location_sync_page() {
        echo '<h1>Sync Locations</h1>';
        echo '<button id="sync-button">Sync with Square</button>';
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

// Instantiating the class.
$wc_squared = new WC_Squared();

// Register activation hook
register_activation_hook( __FILE__, array('WC_Squared', 'activate') );
