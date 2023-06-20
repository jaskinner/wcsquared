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
			'post_content' => 'description',
			'post_status'  => 'publish',
			'post_type'    => 'product',
		);
	
		// Insert the product post
		$product_id = wp_insert_post($product_data);
	
		// Check the number of variations
		$variations = $product->getVariations();
		if (count($variations) > 1) {
			// Set the product type as variable
			wp_set_object_terms($product_id, 'variable', 'product_type');
	
			// Set product variations
			foreach ($variations as $variation) {
				$variationData = $variation->getItemVariationData();
	
				// Prepare variation data
				$variation_data = array(
					'post_title'   => $variationData->getName(),
					'post_status'  => 'publish',
					'post_parent'  => $product_id,
					'post_type'    => 'product_variation',
				);
	
				// Insert the variation post
				$variation_id = wp_insert_post($variation_data);
	
				// Set variation attributes and prices
				update_post_meta($variation_id, '_price', $variationData->getPriceMoney()->getAmount());
				update_post_meta($variation_id, '_regular_price', $variationData->getPriceMoney()->getAmount());
				// ...
	
				// Set the SKU (assuming SKU is available in the variation)
				update_post_meta($variation_id, '_sku', $variationData->getSku());
				
				// Link the variation to the parent product
				update_post_meta($variation_id, '_parent_id', $product_id);
				update_post_meta($variation_id, '_parent', 'product');
	
				// Link the variation to the parent product
				wp_set_object_terms($variation_id, 'simple', 'product_type');
			}
		} else {
			// Set the product type as simple
			wp_set_object_terms($product_id, 'simple', 'product_type');
	
			// Set product attributes and prices
			$variationData = $variations[0]->getItemVariationData();

			update_post_meta($product_id, '_price', $variationData->getPriceMoney()->getAmount());
			update_post_meta($product_id, '_regular_price', $variationData->getPriceMoney()->getAmount());
			// ...
	
			// Set the SKU (assuming SKU is available in the variation)
			update_post_meta($product_id, '_sku', $variationData->getSku());
		}
	
		// Update the product stock and other details
		update_post_meta($product_id, '_stock', 10);
		update_post_meta($product_id, '_stock_status', 'instock');
		// ...
	
		// Set the product thumbnail image (if applicable)
		// set_post_thumbnail($product_id, $image_id);
	}
}
