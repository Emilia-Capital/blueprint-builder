<?php
/**
 * Generates the admin page for the plugin.
 *
 * @package BlueprintBuilder
 */

namespace BlueprintBuilder;

class Admin {
	/**
	 * The builder.
	 *
	 * @var Builder
	 */
	private $builder;

	public function __construct( Builder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
	}

	/**
	 * Add the menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			'Blueprint Builder',
			'Blueprint Builder',
			'manage_options',
			'blueprint-builder',
			[ $this, 'render_page' ],
			'dashicons-welcome-widgets-menus'
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render_page() {
		$blueprint = $this->builder->generate();
		echo '<h1>Blueprint Builder</h1>';
		echo '<p>Here you can create a blueprint of your current environment.</p>';
		echo '<textarea rows="20" cols="100">', wp_json_encode( json_decode( $blueprint ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), '</textarea><br/>';

		$blueprint_url  = site_url( 'wp-json/blueprint-builder/v1/json-' . get_option( 'blueprint_builder_key' ) );
		$playground_url = 'https://playground.wordpress.net/?blueprint-url=' . $blueprint_url;

		echo '<p>If you website is live, you can <a href="', $playground_url, '">open the Playground with this blueprint</a></p>';
	}
}
