<?php
/**
 * Integrations Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Detects integrations and displays notices whether they're supported.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 */
class Page_Generator_Pro_Integrations {

	/**
	 * Holds the base object.
	 *
	 * @since   1.8.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the integrations that require the Pro version.
	 *
	 * @since   1.8.2
	 *
	 * @var     array
	 */
	public $integrations = array();

	/**
	 * Constructor
	 *
	 * @since   1.8.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Define the integrations that require the Pro version.
		$this->integrations = array(
			// These must be before Breakdance, otherwise the notice will be displayed for Breakdance
			// when it is Oxygen, because Oxygen uses Breakdance.
			'oxygen-6'        => array(
				'constant' => 'BREAKDANCE_MODE',
				'value'    => 'oxygen',
				'text'     => __( 'Oxygen', 'page-generator' ),
			),
			'oxygen'          => array(
				'constant' => 'CT_VERSION',
				'text'     => __( 'Oxygen', 'page-generator' ),
			),
			'beaver-builder'  => array(
				'class' => 'FLBuilder',
				'text'  => __( 'Beaver Builder', 'page-generator' ),
			),
			'betheme'         => array(
				'class' => 'Mfn_Post_Type',
				'text'  => __( 'BeTheme', 'page-generator' ),
			),
			'breakdance'      => array(
				'function' => '\Breakdance\Data\get_tree',
				'text'     => __( 'Breakdance', 'page-generator' ),
			),
			'bricks'          => array(
				'class' => '\Bricks\Elements',
				'text'  => __( 'Bricks', 'page-generator' ),
			),
			'brizy'           => array(
				'constant' => 'BRIZY_VERSION',
				'text'     => __( 'Brizy', 'page-generator' ),
			),
			'divi'            => array(
				'constant' => 'ET_CORE_VERSION',
				'text'     => __( 'Divi', 'page-generator' ),
			),
			'divi-theme'      => array(
				'function' => 'et_get_theme_version',
				'text'     => __( 'Divi', 'page-generator' ),
			),
			'elementor'       => array(
				'constant' => 'ELEMENTOR_VERSION',
				'text'     => __( 'Elementor', 'page-generator' ),
			),
			'live-composer'   => array(
				'constant' => 'DS_LIVE_COMPOSER_URL',
				'text'     => __( 'Live Composer', 'page-generator' ),
			),
			'visual-composer' => array(
				'function' => 'vchelper',
				'text'     => __( 'Visual Composer', 'page-generator' ),
			),
			'wpbakery'        => array(
				'function' => 'vc_disable_frontend',
				'text'     => __( 'WPBakery', 'page-generator' ),
			),
		);

		add_action( 'admin_notices', array( $this, 'content_group_notice_classic_editor' ) );

	}

	/**
	 * Displays a notice if an integration is detected in the Content Group
	 * when using the Classic Editor.
	 *
	 * @since   1.8.2
	 */
	public function content_group_notice_classic_editor() {

		// Determine the screen that we're on.
		$screen = $this->base->get_class( 'screen' )->get_current_screen();

		// If we're not on the Edit Content Group screen, bail.
		if ( 'content_groups' !== $screen['screen'] || 'edit' !== $screen['section'] ) {
			return;
		}

		// Iterate through the integrations and add a warning notice if the integration is detected.
		$notice = $this->get_integration_notice();
		if ( ! $notice ) {
			return;
		}

		// Set and output the notice.
		$this->base->get_class( 'notices' )->add_warning_notice(
			sprintf(
				'%s <br /><a href="%s" target="_blank">%s</a>',
				$notice['text'],
				$notice['url'],
				__( 'Upgrade', 'page-generator' ),
			)
		);
		$this->base->get_class( 'notices' )->output_notices();

	}

	/**
	 * Returns notice text if an integration is detected that requires the Pro version.
	 *
	 * @since   1.8.2
	 *
	 * @return  bool|array
	 */
	public function get_integration_notice() {

		// Iterate through the integrations and add a warning notice if the integration is detected.
		foreach ( $this->integrations as $integration_name => $integration ) {
			if ( isset( $integration['class'] ) && class_exists( $integration['class'] ) ) {
				return array(
					'text' => $this->get_notice_text( $integration['text'] ),
					'url'  => $this->base->dashboard->get_upgrade_url( $integration_name ),
				);
			}

			if ( isset( $integration['function'] ) && function_exists( $integration['function'] ) ) {
				return array(
					'text' => $this->get_notice_text( $integration['text'] ),
					'url'  => $this->base->dashboard->get_upgrade_url( $integration_name ),
				);
			}

			if ( isset( $integration['constant'] ) && defined( $integration['constant'] ) ) {
				if ( isset( $integration['value'] ) && constant( $integration['constant'] ) === $integration['value'] ) {
					return array(
						'text' => $this->get_notice_text( $integration['text'] ),
						'url'  => $this->base->dashboard->get_upgrade_url( $integration_name ),
					);
				}
				if ( ! isset( $integration['value'] ) ) {
					return array(
						'text' => $this->get_notice_text( $integration['text'] ),
						'url'  => $this->base->dashboard->get_upgrade_url( $integration_name ),
					);
				}
			}
		}

		return false;

	}

	/**
	 * Returns the notice text for the given integration.
	 *
	 * @since   1.8.2
	 *
	 * @param   string $integration_text    Integration text.
	 * @return  string
	 */
	private function get_notice_text( $integration_text ) {

		return sprintf(
			'%s %s',
			esc_html( $integration_text ),
			esc_html__( 'support is available in Page Generator Pro. Consider upgrading to Pro for 20% off with code NEWYEAR', 'page-generator' ),
		);

	}

}
