<?php

/**
 * Products
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Products
{
	private $table_name_imported_products;

	public function __construct()
	{
		global $wpdb;
		$this->table_name_imported_products = $wpdb->prefix . 'wc_squared_imported_products';
	}

	public function importProducts()
	{
		try {
			$api_key = get_option('wc_squared_api_key');
			$client = new SquareClient([
				'accessToken' => $api_key,
				'environment' => Environment::SANDBOX,
			]);

			$api_response = $client->getCatalogApi()->listCatalog(null, 'ITEM');

			if ($api_response->isSuccess()) {
				$result = $api_response->getResult();

				foreach ($result->getObjects() as $object) {
					if (count($object->getItemData()->getVariations()) <= 1) {
						$this->createSimpleWooProduct($object->getItemData());
					} else {
						$this->createVariableWooProduct($object->getItemData());
					}
					// $this->insertOrUpdateProduct($object);
				}
			} else {
				$errors = $api_response->getErrors();
			}
		} catch(\Exception $e) {
			error_log('An error occurred during product import: ' . $e->getMessage());
		}
	}

	private function insertOrUpdateProduct($product)
	{
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

	private function createSimpleWooProduct($itemData)
	{
		try {
			$new_product = new WC_Product_Simple();
			$variationData = $itemData->getVariations()[0]->getItemVariationData();
			$new_product->set_sku($variationData->getSku());
			$new_product->set_regular_price($variationData->getPriceMoney()->getAmount() / 100); // Assuming the price is in cents

			// Set product data
			$new_product->set_name($itemData->getName());
			$new_product->set_description($itemData->getDescriptionHtml());
			$new_product->set_short_description($itemData->getDescriptionPlaintext());

			// Save the product
			$product_id = $new_product->save();
		} catch(\Exception $e) {
			error_log('An error occurred creating simple product: ' . $e->getMessage());
		}
	}

	private function createVariableWooProduct($itemData)
	{
		try {
			$variations = $itemData->getVariations();

			$new_product = new WC_Product_Variable();

			// Get all variation names
			$variation_names = array();
			foreach ($variations as $variation) {
				$variationData = $variation->getItemVariationData();
				$variation_names[] = $variationData->getName();
			}

			// one available for variation attribute
			$attribute = new WC_Product_Attribute();
			$attribute->set_name('Option');
			$attribute->set_options($variation_names);
			$attribute->set_position(0);
			$attribute->set_visible(true);
			$attribute->set_variation(true);

			$new_product->set_attributes(array($attribute));

			// Set product data
			$new_product->set_name($itemData->getName());
			$new_product->set_description($itemData->getDescriptionHtml());
			$new_product->set_short_description($itemData->getDescriptionPlaintext());

			// Save the product
			$product_id = $new_product->save();

			// If more than one variation exists, create them as separate products
			foreach ($variations as $variation) {
				$variationData = $variation->getItemVariationData();

				$new_variation = new WC_Product_Variation();
				$new_variation->set_name($itemData->getName() . " - " . $variationData->getName());
				$new_variation->set_parent_id($product_id);
				$new_variation->set_regular_price($variationData->getPriceMoney()->getAmount() / 100); // Assuming the price is in cents
				$new_variation->set_sku($variationData->getSku());
				$new_variation->set_attributes( array( 'option' => $variationData->getName() ) );

				// Save the variation
				$new_variation->save();
			}
		} catch(\Exception $e) {
			error_log('An error occurred creating variable product: ' . $e->getMessage());
		}
	}
}
