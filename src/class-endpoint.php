<?php
/**
 * This file contains the Endpoint used to feed the blueprint to the playground.
 *
 * @package BlueprintBuilder
 */

namespace BlueprintBuilder;

/**
 * Class Endpoint.
 */
class Endpoint {

	/**
	 * The key used for the JSON file and REST route.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->key = get_option( 'blueprint_builder_key' );
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the /json-blueprint/ endpoint.
	 */
	public function register_routes() {
		register_rest_route(
			'blueprint-builder/v1',
			'/json-' . $this->key . '/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_blueprint_json' ],
				'_pretty_json'        => true, // This is a custom parameter, see 'pretty_json' in the 'register_rest_route' function in the 'wp-includes/rest-api.php' file.
				'permission_callback' => '__return_true', // Allows public access.
			]
		);

		register_rest_route(
			'blueprint-builder/v1',
			'/wxr-' . $this->key . '.xml',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_wxr' ],
				'permission_callback' => '__return_true', // Allows public access.
			]
		);
	}

	/**
	 * Callback to output the blueprint.json content.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_blueprint_json() {
		$file_path = WP_CONTENT_DIR . '/blueprint-' . $this->key . '.json';

		if ( ! file_exists( $file_path ) ) {
			$builder = new Builder();
			$builder->generate();
		}

		if ( file_exists( $file_path ) ) {
			$response = new \WP_REST_Response(
				json_decode( file_get_contents( $file_path ) ),
				200,
				[
					'Content-Type'                => 'application/json',
					'Access-Control-Allow-Origin' => '*',
				]
			);
			return $response;
		}

		return new \WP_Error( 'json_blueprint_not_found', 'Blueprint JSON file not found.', [ 'status' => 404 ] );
	}

	public function get_wxr() {
		require_once ABSPATH . 'wp-admin/includes/export.php';

		$args['content'] = 'all';
		header( 'Access-Control-Allow-Origin: *' );
		export_wp( $args );
		header_remove( 'Content-Description' );
		header_remove( 'Content-Disposition' );
		header( 'Content-Type: application/xml', true );
	}
}
