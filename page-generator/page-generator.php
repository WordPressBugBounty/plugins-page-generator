<?php
/**
 * Page Generator WordPress Plugin.
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 *
 * Plugin Name: Page Generator
 * Plugin URI: http://www.wpzinc.com/plugins/page-generator-pro
 * Version: 1.7.6
 * Author: WP Zinc
 * Author URI: http://www.wpzinc.com
 * Description: Generate multiple Pages using dynamic content.
 */

// Bail if Plugin is alread loaded.
if ( class_exists( 'Page_Generator' ) ) {
	return;
}

// Bail if the Pro version of the Plugin is active.
if ( class_exists( 'Page_Generator_Pro' ) ) {
	return;
}

// Define Plugin version and build date.
define( 'PAGE_GENERATOR_PLUGIN_VERSION', '1.7.6' );
define( 'PAGE_GENERATOR_PLUGIN_BUILD_DATE', '2025-02-27 18:00:00' );

// Define Plugin paths.
define( 'PAGE_GENERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAGE_GENERATOR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.6.5
 *
 * @param   string $class_name     The class to load.
 */
function page_generator_autoloader( $class_name ) {

	/**
	 * Load Plugin Class
	 */
	$class_start_name = array(
		'Page_Generator_Pro',
	);

	// Get the number of parts the class start name has.
	$class_parts_count = count( explode( '_', $class_start_name[0] ) );

	// Break the class name into an array.
	$class_path = explode( '_', $class_name );

	// Bail if it's not a minimum length.
	if ( count( $class_path ) < $class_parts_count ) {
		return;
	}

	// Build the base class path for this class.
	$base_class_path = '';
	for ( $i = 0; $i < $class_parts_count; $i++ ) {
		$base_class_path .= $class_path[ $i ] . '_';
	}
	$base_class_path = trim( $base_class_path, '_' );

	// Bail if the first parts don't match what we expect.
	if ( ! in_array( $base_class_path, $class_start_name, true ) ) {
		return;
	}

	// Define the file name we need to include.
	$file_name = strtolower( implode( '-', array_slice( $class_path, $class_parts_count ) ) ) . '.php';

	// Define the paths with file name we need to include.
	$include_paths = array(
		__DIR__ . '/includes/admin/' . $file_name,
		__DIR__ . '/includes/admin/keyword-sources/' . $file_name,
		__DIR__ . '/includes/global/' . $file_name,
	);

	// Iterate through the include paths to find the file.
	foreach ( $include_paths as $path_file ) {
		if ( file_exists( $path_file ) ) {
			require_once $path_file;
			return;
		}
	}

}
spl_autoload_register( 'page_generator_autoloader' );

// Load Activation and Deactivation functions.
require_once PAGE_GENERATOR_PLUGIN_PATH . 'includes/activation.php';
require_once PAGE_GENERATOR_PLUGIN_PATH . 'includes/deactivation.php';
register_activation_hook( __FILE__, 'page_generator_activate' );
if ( version_compare( get_bloginfo( 'version' ), '5.1', '>=' ) ) {
	add_action( 'wp_insert_site', 'page_generator_activate_new_site' );
} else {
	add_action( 'wpmu_new_blog', 'page_generator_activate_new_site' );
}
add_action( 'activate_blog', 'page_generator_activate_new_site' );
register_deactivation_hook( __FILE__, 'page_generator_deactivate' );

/**
 * Main function to return Plugin instance.
 *
 * @since   1.6.5
 */
function Page_Generator() { /* phpcs:ignore */

	return Page_Generator::get_instance();

}

// Finally, initialize the Plugin.
require_once PAGE_GENERATOR_PLUGIN_PATH . 'includes/class-page-generator.php';
$page_generator = Page_Generator();
