<?php
/**
 * Inventory
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Inventory {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wc_squared_inventory';
    }

    public function importInventoryCount() {
        $api_key = get_option('wc_squared_api_key');
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => false ? Environment::SANDBOX : Environment::PRODUCTION,
        ]);

        $body = new \Square\Models\BatchRetrieveInventoryCountsRequest();

        $api_response = $client->getInventoryApi()->batchRetrieveInventoryCounts($body);

        if ($api_response->isSuccess()) {
            $result = $api_response->getResult();

            foreach ($result->getCounts() as $count) {
                $this->insertOrUpdateCount($count);
            }

        } else {
            $errors = $api_response->getErrors();
        }
    }

    private function insertOrUpdateCount($count) {
        global $wpdb;

        $postId = $this->getPostIdByMeta('square_catalog_object_id', $count->getCatalogObjectId());
        $locationId = $count->getLocationId();

        try {
            $wpdb->replace(
                $this->table_name,
                array(
                    'post_id' => $postId,
                    'location_id' => $locationId,
                    'quantity' => $count->getQuantity(),
                )
            );
        } catch(Error $e) {
            print_r($e);
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
