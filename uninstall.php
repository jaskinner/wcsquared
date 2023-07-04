<?php

// uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

$inventory_table = $wpdb->prefix . 'wc_squared_inventory';
$locations_table = $wpdb->prefix . 'wc_squared_locations';

$wpdb->query("DROP TABLE IF EXISTS $table_name");

$option_name_1 = 'wc_squared_api_key';
$option_name_2 = 'wc_squared_sync_checkbox';

delete_option($option_name_1);
delete_option($option_name_2);
