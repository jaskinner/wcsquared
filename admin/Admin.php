<?php
/**
 * Admin
 */
 
class Admin {

	public function __construct() {

		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_wcsquared_tab', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_wcsquared_tab', __CLASS__ . '::update_settings' );
	}

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wcsquared_tab'] = __( 'WC Squared', 'wcsquared-tab' );
        return $settings_tabs;
    }

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings() {
		$settings = self::get_settings();
	
		woocommerce_update_options( $settings );
	
		$sync_checkbox = isset( $_POST['wc_squared_sync_checkbox'] ) ? 'yes' : 'no';
	
		// Call the sync_handler function if the checkbox is checked.
		if ( 'yes' === $sync_checkbox ) {
			// Call the sync_handler function if the checkbox is checked.
			if ( 'yes' === $sync_checkbox ) {
				$locationSync = new Locations();
				$locationSync->syncLocations();

				$productSync = new Products();
				$productSync->importProducts();
			}
		}
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields().
	 */
	public static function get_settings() {
		$settings = array(
			'section_title' => array(
				'name'     => __( 'WC SQUARED SETTINGS', 'wcsquared-settings-tab' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcsquared_tab_section_title'
			),
			'title' => array(
				'name' => __( 'API KEY', 'wcsquared-settings-tab' ),
				'type' => 'password',
				'desc' => __( 'Enter your Square API key', 'wcsquared-settings-tab' ),
				'id'   => 'wc_squared_api_key',
			),
			'sync_checkbox' => array(
				'name'    => __( 'Sync Locations', 'wc-squared' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Check this box to sync locations.', 'wc-squared' ),
				'id'      => 'wc_squared_sync_checkbox',
				'default' => 'no',
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id'   => 'wc_squared_section_end'
			),
		);

		return apply_filters( 'wcsquared_tab_settings', $settings );
	}
}
