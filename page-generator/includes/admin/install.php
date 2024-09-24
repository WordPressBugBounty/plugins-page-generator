<?php
/**
 * Installation and Upgrade Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Runs installation routines when the Plugin is activated,
 * such as database table creation.
 *
 * Upgrade routines run depending on the existing and updated
 * Plugin version.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.1.8
 */
class Page_Generator_Pro_Install {

	/**
	 * Holds the base object.
	 *
	 * @since   1.3.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor.
	 *
	 * @since   1.9.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Runs installation routines for first time users
	 *
	 * @since   1.9.8
	 */
	public function install() {

		// Run activation routines on classes.
		$this->base->get_class( 'groups' )->activate();
		$this->base->get_class( 'keywords' )->activate();

	}

	/**
	 * Runs migrations for Pro to Pro version upgrades
	 *
	 * @since   1.1.7
	 */
	public function upgrade() {

		global $wpdb;

		// Get current installed version number.
		// false | 1.1.7.
		$installed_version = get_option( $this->base->plugin->name . '-version' );

		// If the version number matches the plugin version, bail.
		if ( $installed_version === $this->base->plugin->version ) {
			return;
		}

		/**
		 * 1.5.7: Upgrade Keywords Table
		 */
		if ( ! $installed_version || $installed_version < '1.5.7' ) {
			$this->base->get_class( 'keywords' )->upgrade();
		}

		/**
		 * 1.7.8: Install
		 */
		if ( ! $installed_version || $installed_version < '1.4.8' ) {
			// Get instance.
			$keywords = $this->base->get_class( 'keywords' );

			// Upgrade table.
			$keywords->upgrade();
		}

		// Update the version number.
		update_option( $this->base->plugin->name . '-version', $this->base->plugin->version );

	}

	/**
	 * Runs uninstallation routines
	 *
	 * @since   1.9.8
	 */
	public function uninstall() {

	}

}
