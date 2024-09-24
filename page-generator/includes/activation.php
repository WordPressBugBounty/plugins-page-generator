<?php
/**
 * Plugin activation functions.
 *
 * @package Page_Generator
 * @author WP Zinc
 */

/**
 * Runs the installation and update routines when the plugin is activated.
 *
 * @since   1.9.8
 *
 * @param   bool $network_wide   Is network wide activation.
 */
function page_generator_activate( $network_wide ) {

	// Initialise Plugin.
	$plugin = Page_Generator::get_instance();
	$plugin->initialize();

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		$plugin->get_class( 'install' )->install();
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$plugin->get_class( 'install' )->install();
			restore_current_blog();
		}
	}

}

/**
 * Runs the installation and update routines when the plugin is activated
 * on a WPMU site.
 *
 * @since   1.9.8
 *
 * @param   mixed $site_or_blog_id    WP_Site or Blog ID.
 */
function page_generator_activate_new_site( $site_or_blog_id ) {

	// Check if $site_or_blog_id is a WP_Site or a blog ID.
	if ( is_a( $site_or_blog_id, 'WP_Site' ) ) {
		$site_or_blog_id = $site_or_blog_id->blog_id;
	}

	// Initialise Plugin.
	$plugin = Page_Generator::get_instance();
	$plugin->initialize();

	// Run installation routine.
	switch_to_blog( $site_or_blog_id );
	$plugin->get_class( 'install' )->install();
	restore_current_blog();

}
