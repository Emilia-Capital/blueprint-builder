<?php
/**
 * Plugin Name: Blueprint Builder
 * Plugin URI: -
 * Description: A simple plugin to create a WordPress Playground blueprint from your current environment.
 * Version: 1.0
 * Author: Joost de Valk
 * Author URI: https://joost.blog
 * License: GPL3+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: blueprint-builder
 */

namespace BlueprintBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLUEPRINT_BUILDER_FILE', __FILE__ );
define( 'BLUEPRINT_BUILDER_DIR', dirname( BLUEPRINT_BUILDER_FILE ) );

require __DIR__ . '/src/autoload.php';

$plugin = new Plugin();
add_action( 'init', [ $plugin, 'register_hooks' ] );
