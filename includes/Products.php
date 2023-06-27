<?php

/**
 * Products
 */

 set_time_limit(0);

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Products {

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
				$cursor = $api_response->getCursor();

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
			error_log('An error occurred during category sync: ' . print_r($api_response->getErrors()));
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

			// image import
			self::getCatalogObjectImageURL($variationData->getItemId(), $product_id);

			// inventory sync
			$counts = Inventory::getInventoryCountsByProductId($itemData->getVariations()[0]->getId());

			foreach ($counts->getCounts() as $count) {
				Inventory::insertOrUpdateCount($count, $product_id);
			}
		} catch(\Exception $e) {
			error_log('An error occurred creating simple product: ' . $e->getMessage());
			throw $e;
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

			// image import
			self::getCatalogObjectImageURL($variationData->getItemId(), $product_id);
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
				$variation_id = $new_variation->save();

				// inventory sync
				$counts = Inventory::getInventoryCountsByProductId($variation->getId());

				foreach ($counts->getCounts() as $count) {
					Inventory::insertOrUpdateCount($count, $variation_id);
				}
			} catch(\Exception $e) {
				error_log("An error occurred creating product variation:\n " . $e->getMessage());

				// Rollback the creation of the variable product
				wp_delete_post($product_id, true);
			}
		}
	}

	public static function getCatalogObjectImageURL($catalog_object_id, $post_id) {
		try {
			$api_key = get_option('wc_squared_api_key');
			$client = new SquareClient([
				'accessToken' => $api_key,
				'environment' => false ? Environment::SANDBOX : Environment::PRODUCTION,
			]);
		} catch(\Exception $e) {
			error_log('An error occurred creating square client instance: ' . $e->getMessage());
		}

		try {
			$api_response = $client->getCatalogApi()->retrieveCatalogObject($catalog_object_id);
	
			if ($api_response->isSuccess()) {
				$catalog_object = $api_response->getResult()->getObject();
				$image_ids = $catalog_object->getItemData()->getImageIds();

				foreach ($image_ids as $image_id) {
					if ($image_id) {
						$api_response = $client->getCatalogApi()->retrieveCatalogObject($image_id);
						
						if ($api_response->isSuccess()) {
							$image_data = $api_response->getResult()->getObject()->getImageData();
							self::downloadImage($image_data->getUrl(), $post_id, $image_ids[0] == $image_id);
						}
					}
				}
			} else {
				$errors = $api_response->getErrors();
				// Handle API errors
				// ...
			}
		} catch (\Exception $e) {
			// Handle exceptions
			// ...
		}
	
		return ''; // Return empty string if image URL is not found or there was an error
	}

	public static function downloadImage($url, $post_id, $feat) {
		$upload_dir = wp_upload_dir(); // Get the upload directory path
		$image_name = rand(100000, 999999) . basename($url); // Extract the image name from the URL
		$image_path = $upload_dir['path'] . '/' . $image_name; // Set the path to save the image
	
		// Download the image
		$response = wp_remote_get($url);
	
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$image_data = wp_remote_retrieve_body($response);
			$saved = file_put_contents($image_path, $image_data); // Save the image locally
	
			if ($saved !== false) {
				// Image saved successfully, now you can process it further if needed
				// For example, you can set the image as a featured image for a post
				$filetype = wp_check_filetype(basename($image_path), null);

				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid'           => $upload_dir['url'] . '/' . basename($image_path),
					'post_mime_type' => $filetype['type'],
					'post_title'     => preg_replace('/\.[^.]+$/', '', basename($image_path)),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
	
				// Insert the attachment.
				$attach_id = wp_insert_attachment($attachment, $image_path, $post_id);
	
				// Include the image handling library
				require_once(ABSPATH . 'wp-admin/includes/image.php');
	
				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
				$updated = wp_update_attachment_metadata($attach_id, $attach_data);

				// Finally, add the attachment to the product gallery
				$product = wc_get_product($post_id);
	
				if ($feat) {
					// Finally, set the attachment as the post thumbnail
					set_post_thumbnail($post_id, $attach_id);
				} else {
					if ($product) {
						
						// Get existing gallery
						$existing_gallery = $product->get_gallery_image_ids();
						
						// Add new image to gallery
						$existing_gallery[] = $attach_id;
						
						// Update gallery
						$product->set_gallery_image_ids($existing_gallery);
						$product->save();
					}
				}
			} else {
				error_log('Error saving image: ' . $image_name);
			}
		} else {
			error_log('Error downloading image: ' . $url);
		}
	}
}
