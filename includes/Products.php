<?php
/**
 * Products
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Products {
    private $table_name_imported_products;

    public function __construct() {
        global $wpdb;
        $this->table_name_imported_products = $wpdb->prefix . 'wc_squared_imported_products';
    }

    public function importProducts() {
        $api_key = get_option('wc_squared_api_key');
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => Environment::SANDBOX,
        ]);

        $api_response = $client->getCatalogApi()->listCatalog(null, 'ITEM');

        if ($api_response->isSuccess()) {
            $result = $api_response->getResult();

            foreach ($result->getObjects() as $object) {
                $this->createWooProduct($object->getItemData());
                // $this->insertOrUpdateProduct($object);
            }
        } else {
            $errors = $api_response->getErrors();
        }
    }

    private function insertOrUpdateProduct($product) {
        global $wpdb;

        $productId = $product->getId();
        $locationId = 'LKGS66YAAZ6DM';

        $wpdb->replace(
            $this->table_name_imported_products,
            array(
                'post_id' => $productId,
                'location_id' => $locationId,
            )
        );
    }

    private function createWooProduct($product) {
        // Prepare the product data
        $product_data = array(
            'post_title'   => $product->getName(),
            'post_content' => $product->getDescription(),
            'post_status'  => 'publish',
            'post_type'    => 'product',
        );

        // Insert the product post
        $product_id = wp_insert_post($product_data);

        // Set the product type and attributes
        wp_set_object_terms($product_id, 'simple', 'product_type');
        update_post_meta($product_id, '_price', 19.99);
        update_post_meta($product_id, '_regular_price', 19.99);

        // Set product categories
        wp_set_object_terms($product_id, array('category1', 'category2'), 'product_cat');

        // Set product attributes and variations (if applicable)
        // ...

        // Update the product stock and other details
        update_post_meta($product_id, '_stock', 10);
        update_post_meta($product_id, '_stock_status', 'instock');
        // ...

        // Set the product thumbnail image (if applicable)
        // set_post_thumbnail($product_id, $image_id);
    }
}
