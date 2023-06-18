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
                $this->insertOrUpdateProduct($object);
            }
        } else {
            $errors = $api_response->getErrors();
        }
    }

    private function insertOrUpdateProduct($product) {
        global $wpdb;

        $productId = $product->getId();
        $locationId = 'LKGS66YAAZ6DM';

        $wpdb->insert(
            $this->table_name_imported_products,
            array(
                'product_id' => $productId,
                'location_id' => $locationId,
            )
        );
    }
}
