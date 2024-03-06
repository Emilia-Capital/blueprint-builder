<?php
/**
 * Autoload PHP classes for the plugin.
 *
 * @package BlueprintBuilder
 */

spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'BlueprintBuilder\\';

		if ( 0 !== \strpos( $class_name, $prefix ) ) {
			return;
		}

		$class_name = \str_replace( $prefix, '', $class_name );

		$file = BLUEPRINT_BUILDER_DIR . '/src/class-' . strtolower( $class_name ) . '.php';

		if ( \file_exists( $file ) ) {
			require_once $file;
		}
	}
);
