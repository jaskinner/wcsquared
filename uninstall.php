<?php

// uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

$table_name = $wpdb->prefix . 'wc_squared_locations';

$wpdb->query("DROP TABLE IF EXISTS $table_name");
