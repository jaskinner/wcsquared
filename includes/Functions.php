<?php

defined( 'ABSPATH' ) || exit;

/**
 * Gets the singleton instance.
 *
 * @return \WCSquared\Plugin
 */
function wc_squared() {

	return \WCSquared\Plugin::instance();
}

