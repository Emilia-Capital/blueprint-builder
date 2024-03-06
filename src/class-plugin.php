<?php
/**
 * Loads the classes for the plugin and determines the key, if needed.
 *
 * @package BlueprintBuilder
 */

namespace BlueprintBuilder;

class Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$blueprint_builder_key = \get_option( 'blueprint_builder_key', false );
		if ( ! $blueprint_builder_key ) {
			$blueprint_builder_key = \wp_generate_password( 12, false );
			\update_option( 'blueprint_builder_key', $blueprint_builder_key );
		}
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		$rest_route = new Endpoint();
		$rest_route->register_hooks();

		if ( is_admin() ) {
			$blueprint_builder       = new Builder();
			$blueprint_builder_admin = new Admin( $blueprint_builder );
			$blueprint_builder_admin->register_hooks();
		}
	}
}
