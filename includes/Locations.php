<?php
/**
 * Locations
 */

use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;

class Locations {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wc_squared_locations';
    }

    public function syncLocations() {
        $api_key = get_option('wc_squared_api_key');
        $client = new SquareClient([
            'accessToken' => $api_key,
            'environment' => Environment::SANDBOX,
        ]);

        $api_response = $client->getLocationsApi()->listLocations();

        if ($api_response->isSuccess()) {
            $result = $api_response->getResult();

            foreach ($result->getLocations() as $location) {
                if ($location->getStatus() === "INACTIVE") {
                    continue;
                }

                $this->insertOrUpdateLocation($location);
            }
        } else {
            $errors = $api_response->getErrors();
            // Handle errors here...
        }
    }

    private function insertOrUpdateLocation($location) {
        global $wpdb;

        $locationId = $location->getId();
        $name = $location->getName();
        $address = $location->getAddress();

        $addressLine = $address->getAddressLine1();
        $locality = $address->getLocality();
        $administrativeDistrictLevel1 = $address->getAdministrativeDistrictLevel1();

        $wpdb->replace(
            $this->table_name,
            array(
                'id' => $locationId,
                'name' => $name,
                'address_line' => $addressLine,
                'locality' => $locality,
                'administrative_district' => $administrativeDistrictLevel1,
            )
        );
    }
}
