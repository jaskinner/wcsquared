<?php

namespace WCSquared;

defined( 'ABSPATH' ) || exit;

class Plugin {
    
	/** @var Plugin plugin instance */
	protected static $instance;

	/**
	 * Gets the singleton instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
