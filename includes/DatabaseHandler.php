<?php
/**
 * Database Handler
 */

class DatabaseHandler {
    private $table_name_imported_products;
    private $table_name_inventory;
    private $table_name_locations;

    public function __construct() {
        global $wpdb;
        $this->table_name_locations = $wpdb->prefix . 'wc_squared_locations';
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

    public function createInventoryTable() {
        global $wpdb;

        // Create the wc_squared_inventory table
        $sql = "CREATE TABLE $this->table_name_inventory (
            post_id bigint(20) unsigned NOT NULL,
            location_id varchar(55) NOT NULL,
            quantity int(11) DEFAULT 0,
            PRIMARY KEY  (post_id, location_id),
            FOREIGN KEY (post_id) REFERENCES {$wpdb->posts} (ID),
            FOREIGN KEY (location_id) REFERENCES $this->table_name_locations (id)
        )";
        
        $this->executeQuery($sql);
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
