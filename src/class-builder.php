<?php
/**
 * Builds a blueprint for the WordPress Playground.
 *
 * @package BlueprintBuilder
 */

namespace BlueprintBuilder;

/**
 * Builds a blueprint for the WordPress Playground.
 */
class Builder {

	/**
	 * The blueprint.
	 *
	 * @var array
	 */
	public $blueprint = [
		'$schema'             => 'https://playground.wordpress.net/blueprint-schema.json',
		'preferredVersions'   => [
			'php' => '',
			'wp'  => '',
		],
		'features'            => [
			'networking' => true,
		],
		'phpExtensionBundles' => [ 'kitchen-sink' ],
		'landingPage'         => '/wp-admin/',
		'steps'               => [],
	];

	/**
	 * WordPress.org API URL for plugin information.
	 */
	const WP_ORG_PLUGIN_API_URL = 'https://api.wordpress.org/plugins/info/1.0/';

	/**
	 * Generates a blueprint file.
	 *
	 * @return string The blueprint, JSON encoded.
	 */
	public function generate() {
		$php_version                                 = explode( '.', phpversion() );
		$this->blueprint['preferredVersions']['php'] = $php_version[0] . '.' . $php_version[1];

		$wp_version                                 = explode( '.', get_bloginfo( 'version' ) );
		$this->blueprint['preferredVersions']['wp'] = $wp_version[0] . '.' . $wp_version[1];
		if ( ! is_numeric( $wp_version[1] ) ) {
			$this->blueprint['preferredVersions']['wp'] = 'nightly';
		}

		$this->add_login_step();
		$this->add_theme_installations_steps();
		$this->add_plugins_installations_steps();
		$this->add_wxr_step();
		$this->add_option_steps();

		$this->write();

		return wp_json_encode( $this->blueprint );
	}

	/**
	 * Writes the blueprint to a file.
	 *
	 * @return void
	 */
	public function write() {
		$filename = 'blueprint-' . \get_option( 'blueprint_builder_key' ) . '.json';

		global $wp_filesystem;

		require_once ( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
		$wp_filesystem->put_contents( trailingslashit( WP_CONTENT_DIR ) . $filename, wp_json_encode( $this->blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Adds the login step to the blueprint.
	 *
	 * @return void
	 */
	protected function add_login_step() {
		$this->blueprint['steps'][] = [
			'step'     => 'login',
			'username' => 'admin',
			'password' => 'password',
		];
	}

	/**
	 * Adds the WXR import step to the blueprint.
	 *
	 * @return void 
	 */
	protected function add_wxr_step() {
		$wxr_url = site_url( 'wp-json/blueprint-builder/v1/wxr-' . get_option( 'blueprint_builder_key' ) . '.xml' );

		$this->blueprint['steps'][] = [
			'step' => 'importFile',
			'file' => [
				'resource' => 'url',
				'url'      => $wxr_url,
			],
		];
	}

	/**
	 * Adds the theme installation steps to the blueprint.
	 *
	 * @return void
	 */
	protected function add_theme_installations_steps() {
		$active_theme = $this->get_active_theme();

		// Workaround for bug in Playground.
		// @link https://github.com/WordPress/wordpress-playground/issues/999
		if ( $active_theme === 'twentytwentyfour' ) {
			return;
		}

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo add support for child & parent themes.
		$this->blueprint['steps'][] = [
			'step'         => 'installTheme',
			'themeZipFile' => [
				'resource' => 'wordpress.org/themes',
				'slug'     => $this->get_active_theme(),
			],
			'options'      => [
				'activate' => true,
			],
		];
	}

	/**
	 * Fetches the active theme.
	 *
	 * @return string The active theme.
	 */
	private function get_active_theme() {
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo Add support for child themes.
		// Currently returns the parent theme on purpose until we have a way to download the child theme from the site itself.
		return get_template();
	}

	/**
	 * Adds the plugin installation steps to the blueprint.
	 *
	 * @return void
	 */
	protected function add_plugins_installations_steps() {
		foreach ( $this->get_active_plugins() as $plugin ) {
			if ( $plugin['wordpress_org'] ) {
				$this->blueprint['steps'][] = [
					'step'          => 'installPlugin',
					'pluginZipFile' => [
						'resource' => 'wordpress.org/plugins',
						'slug'     => $plugin['slug'],
					],
					'options'       => [
						'activate' => true,
					],
				];
			}
		}
	}

	/**
	 * Fetches the active plugins that are available on WordPress.org.
	 *
	 * @return array List of active plugins.
	 */
	protected function get_active_plugins() {
		$plugins        = get_option( 'active_plugins' );
		$return_plugins = [];

		foreach ( $plugins as $plugin ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$slug        = pathinfo( basename( $plugin ), PATHINFO_FILENAME );

			/** 
			 * Prevent some special cases:
			 * - akismet breaks,
			 * - blueprint-builder is this plugin and not needed,
			 * - Plausible breaks - https://github.com/plausible/wordpress/issues/174
			 */
			if ( in_array( $slug, [ 'akismet', 'blueprint-builder', 'plausible-analytics' ] ) ) {
				continue;
			}
			$wp_org_data   = wp_remote_post(
				self::WP_ORG_PLUGIN_API_URL,
				[
					'body' => [
						'action'   => 'plugin_information',
						'request'  => serialize(
							(object) [
								'slug'   => $slug,
								'fields' => [ 'sections' => false ],
							]
						),
						'per_page' => 1,
					],
				]
			);
			$wordpress_org = false;
			if ( wp_remote_retrieve_response_code( $wp_org_data ) === 200 ) {
				$wordpress_org = true;
			}
			$return_plugins[] = [
				'slug'          => $slug,
				'version'       => $plugin_data['Version'],
				'wordpress_org' => $wordpress_org,
			];
		}

		return $return_plugins;
	}

	/**
	 * Adds the option steps to the blueprint.
	 *
	 * @return void
	 */
	protected function add_option_steps() {
		$options = wp_load_alloptions();

		// Prevent some special cases.
		foreach ( [
			'active_plugins',
			'auth_key',
			'auth_salt',
			'cron',
			'home',
			'https_detection_errors',
			'initial_db_version',
			'logged_in_key',
			'logged_in_salt',
			'mailserver_url',
			'mailserver_login',
			'mailserver_pass',
			'mailserver_port',
			'new_admin_email',
			'recently_activated',
			'recovery_keys',
			'rewrite_rules',
			'siteurl',
			'site_icon',
			'site_logo',
			'theme_switched',
		] as $key
		) {
			unset( $options[ $key ] );
		}

		foreach ( $options as $key => $option ) {
			if ( strpos( $key, '_transient' ) === 0 || strpos( $key, '_site_transient' ) === 0 ) {
				unset( $options[ $key ] );
				continue;
			}

			if ( $option === '' || $option === [] ) {
				unset( $options[ $key ] );
			}
		}

		$i = 1;
		$j = 1;
		foreach ( $options as $key => $option ) {
			$options_chunks[ $j ][ $key ] = $option;
			++$i;
			if ( $i > 10 ) {
				++$j;
				$i = 1;
			}
		}

		foreach ( $options_chunks as $chunk ) {
			$this->blueprint['steps'][] = [
				'step'    => 'setSiteOptions',
				'options' => $chunk,
			];
		}
	}
}
