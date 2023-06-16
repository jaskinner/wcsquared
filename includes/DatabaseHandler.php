<?php
/**
 * Database Handler
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class DatabaseHandler {
    private $table_name_imported_products;
    private $table_name_inventory;
    private $table_name_locations;

    public function __construct() {
        global $wpdb;
        $this->table_name_locations = $wpdb->prefix . 'wc_squared_locations';
        $this->table_name_imported_products = $wpdb->prefix . 'wc_squared_imported_products';
        $this->table_name_inventory = $wpdb->prefix . 'wc_squared_inventory';
    }

    public function createLocationsTable() {

        // Create the wc_squared_locations table
        $sql = "CREATE TABLE $this->table_name_locations (
            id varchar(55) NOT NULL,
            name varchar(55) NOT NULL,
            address_line varchar(255) DEFAULT '' NOT NULL,
            locality varchar(55) DEFAULT '' NOT NULL,
            administrative_district varchar(55) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
        )";
        
        $this->executeQuery($sql);
    }

    public function createImportedProductsTable() {

        // Create the wc_squared_imported_products table
        $sql = "CREATE TABLE $this->table_name_imported_products (
            product_id bigint(20) NOT NULL,
            location_id varchar(55) NOT NULL,
            PRIMARY KEY  (product_id, location_id),
            FOREIGN KEY (location_id) REFERENCES $this->table_name_locations (id)
        )";
        
        $this->executeQuery($sql);
    }

    public function createInventoryTable() {

        // Create the wc_squared_inventory table
        $sql = "CREATE TABLE $this->table_name_inventory (
            product_id bigint(20) NOT NULL,
            location_id varchar(55) NOT NULL,
            quantity int(11) DEFAULT 0,
            PRIMARY KEY  (product_id, location_id),
            FOREIGN KEY (product_id) REFERENCES $this->table_name_imported_products (product_id),
            FOREIGN KEY (location_id) REFERENCES $this->table_name_locations (id)
        )";
        
        $this->executeQuery($sql);
    }

	public static function syncLocations() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_squared_locations';

        $api_key = get_option('wc_squared_api_key');
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => Environment::SANDBOX,
        ]);

        $api_response = $client->getLocationsApi()->listLocations();

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
    }

    private function executeQuery($sql_query) {
        global $wpdb;
    
        $charset_collate = $wpdb->get_charset_collate();
    
        // Append the charset collation to the SQL query
        $sql_query .= " $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_query);
    }    

}
