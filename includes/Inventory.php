<?php

/**
 * Inventory
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Inventory {
    private $table_name;

    // public function __construct() {}

    public static function importInventoryCount() {
        $api_key = get_option('wc_squared_api_key');
        $environment = get_option('wc_squared_environment', 'sandbox'); // Default to sandbox if the option is not set

        // Use the environment variable to set the SquareClient configuration
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => ($environment === 'sandbox') ? Environment::SANDBOX : Environment::PRODUCTION,
        ]);
        
        $body = new \Square\Models\BatchRetrieveInventoryCountsRequest();

        $api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($body);

        if ($api_response->isSuccess()) {
            $result = $api_response->getResult();

            foreach ($result->getCounts() as $count) {
                // self::insertOrUpdateCount($count);
            }

        } else {
            $errors = $api_response->getErrors();
        }
    }

    public static function getInventoryCountsByProductId($product_id) {
        $api_key = get_option('wc_squared_api_key');
        $environment = get_option('wc_squared_environment', 'sandbox'); // Default to sandbox if the option is not set

        // Use the environment variable to set the SquareClient configuration
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => ($environment === 'sandbox') ? Environment::SANDBOX : Environment::PRODUCTION,
        ]);
                
        $api_response = $client->getInventoryApi()->retrieveInventoryCount($product_id);

        if ($api_response->isSuccess()) {
            $result = $api_response->getResult();
            return $result;
        } else {
            $errors = $api_response->getErrors();
        }
    }

    public static function insertOrUpdateCount($count, $postId) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_squared_inventory';

        $locationId = $count->getLocationId();

        try {
            $wpdb->replace(
                $table_name,
                array(
                    'post_id' => $postId,
                    'location_id' => $locationId,
                    'quantity' => $count->getQuantity(),
                )
            );
        } catch(Error $e) {
            print_r($e);
            throw $e;
        }
    }

    function getPostIdByMeta($metaKey, $metaValue) {
        $args = array(
            'post_type' => 'product', // Adjust the post type if needed
            'meta_key' => $metaKey,
            'meta_value' => $metaValue,
            'fields' => 'ids',
            'posts_per_page' => 1,
        );
    
        $posts = get_posts($args);
    
        if ($posts) {
            return $posts[0]; // Return the first matching post ID
        }
    
        return 0; // Return 0 if no matching post is found
    }
    
}
