<?php

/**
 * Products
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Products
{

	// public function __construct(){}

	public static function importProducts() {
		try {
			$api_key = get_option('wc_squared_api_key');
			$client = new SquareClient([
				'accessToken' => $api_key,
				'environment' => false ? Environment::SANDBOX : Environment::PRODUCTION,
			]);
		} catch(\Exception $e) {
			error_log('An error occurred creating square client instance: ' . $e->getMessage());
		}

		self::syncCategories($client);

		$cursor = null;

		do {
			$api_response = $client->getCatalogApi()->listCatalog($cursor, 'ITEM');

			if ($api_response->isSuccess()) {

				// TODO: timeouts or something are happening here
				// $cursor = $api_response->getCursor();

				foreach ($api_response->getResult()->getObjects() as $object) {
					if (count($object->getItemData()->getVariations()) <= 1) {
						self::createSimpleWooProduct($object->getItemData());
					} else {
						self::createVariableWooProduct($object->getItemData());
					}
				}
			} else {
				error_log('An error occurred creating square client instance: ' . $api_response->getErrors());
			}

		} while ($cursor);
	}

	private static function syncCategories($client) {
		$api_response = $client->getCatalogApi()->listCatalog(null, 'CATEGORY');

		if ($api_response->isSuccess()) {
			foreach ($api_response->getResult()->getObjects() as $object) {
				$category = wp_insert_term( $object->getCategoryData()->getName(), 'product_cat' );		
				
				if (!is_wp_error($category)) {
					$category_id = $category['term_id'];

					// Save the Square category ID as term meta
					$old_square_category_id = $object->getId(); // Replace with the actual Square category ID
					update_term_meta($category_id, 'square_category_id', $old_square_category_id);
				} else {
					// Handle error if category creation fails
					$error_message = $category->get_error_message();
					error_log('Failed to create product category: ' . $error_message);
				}
			}
		} else {
			error_log('An error occurred during category sync: ' . $api_response->getErrors());
		}
	}

	private static function createSimpleWooProduct($itemData) {
		try {
			$new_product = new WC_Product_Simple();
			$variationData = $itemData->getVariations()[0]->getItemVariationData();
			$new_product->set_sku($variationData->getSku());

			if ($variationData->getSku() === null) {
				throw new \Exception('SKU is null.');
			}
			
			$new_product->set_regular_price($variationData->getPriceMoney()->getAmount() / 100); // Assuming the price is in cents
			
			// Set product data
			$new_product->set_name($itemData->getName());
			$new_product->set_description($itemData->getDescriptionHtml());
			$new_product->set_short_description($itemData->getDescriptionPlaintext());

			// Get the term ID based on the Square category ID meta value
			$terms = get_terms(array(
				'taxonomy' => 'product_cat',
				'fields' => 'ids',
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key' => 'square_category_id',
						'value' => $itemData->getCategoryId(),
						'compare' => '='
					)
				)
			));
			
			$new_product->set_category_ids($terms);
			
			// Save the product
			$product_id = $new_product->save();
		} catch(\Exception $e) {
			error_log('An error occurred creating simple product: ' . $e->getMessage());
		}
	}

	private static function createVariableWooProduct($itemData) {
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
		} catch(\Exception $e) {
			error_log("An error occurred creating variable product: " . $e->getMessage());
		}

		// If more than one variation exists, create them as separate products
		foreach ($variations as $variation) {
			try {
				$variationData = $variation->getItemVariationData();

				$new_variation = new WC_Product_Variation();
				$new_variation->set_sku($variationData->getSku());

				if ($variationData->getSku() === null) {
					throw new \Exception('SKU is null.');
				}

				$new_variation->set_name($itemData->getName() . " - " . $variationData->getName());
				$new_variation->set_parent_id($product_id);
				$new_variation->set_regular_price($variationData->getPriceMoney()->getAmount() / 100); // Assuming the price is in cents
				$new_variation->set_attributes( array( 'option' => $variationData->getName() ) );

				// Save the variation
				$new_variation->save();
			} catch(\Exception $e) {
				error_log("An error occurred creating product variation:\n " . $e->getMessage());

				// Rollback the creation of the variable product
				wp_delete_post($product_id, true);
			}
		}
	}
}
