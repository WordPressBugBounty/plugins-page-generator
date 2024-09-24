<?php
/**
 * Local Keyword Source Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Registers a local Keyword source, enabling data stored in the Keyword's Term field
 * to be used for a Keyword.
 *
 * This is the default option available prior to 3.0.8.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 3.0.8
 */
class Page_Generator_Pro_Keywords_Source_Local {

	/**
	 * Holds the base object.
	 *
	 * @since   3.0.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.0.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Register this Keyword Source.
		add_filter( 'page_generator_pro_keywords_register_sources', array( $this, 'register' ) );

		// Define parameters for the Keyword before saving.
		add_filter( 'page_generator_pro_keywords_save_local', array( $this, 'save' ) );

		// Validate Keyword for this source before saving.
		add_filter( 'page_generator_pro_keywords_validate_local', array( $this, 'validate' ), 10, 3 );

	}

	/**
	 * Returns the programmatic name of the source
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */
	public function get_name() {

		return 'local';

	}

	/**
	 * Returns the label of the source
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */
	public function get_label() {

		return __( 'Local', 'page-generator' );

	}

	/**
	 * Registers this Source with the Keyword Sources system, so it's available
	 * to Keywords
	 *
	 * @since   3.0.8
	 *
	 * @param   array $sources    Sources.
	 * @return  array               Sources
	 */
	public function register( $sources ) {

		return array_merge(
			$sources,
			array(
				$this->get_name() => array(
					'name'    => $this->get_name(),
					'label'   => $this->get_label(),
					'options' => array(
						'data' => array(
							'type'        => 'textarea',
							'label'       => __( 'Terms', 'page-generator' ),
							'description' => array(
								__( 'Word(s) or phrase(s) which will be cycled through when generating content using the above keyword template tag.', 'page-generator' ),
								__( 'One word / phrase per line.', 'page-generator' ),
								__( 'If no Terms are entered, the plugin will try to automatically determine a list of similar terms based on the supplied keyword when you click Save.', 'page-generator' ),
							),
						),
					),
				),
			)
		);

	}

	/**
	 * Prepares Keyword Data for this Source, based on the supplied form data,
	 * immediately before it's saved to the Keywords table in the database
	 *
	 * @since   3.0.8
	 *
	 * @param   array $keyword    Keyword Parameters.
	 * @return  mixed               WP_Error | Keyword Parameters
	 */
	public function save( $keyword ) {

		// Merge options with Keyword.
		$keyword = array_merge(
			$keyword,
			array(
				'data' => $keyword['options']['data'],
			)
		);

		// Remove options.
		$keyword['options'] = '';

		// Return.
		return $keyword;

	}

	/**
	 * Runs validation tests specific to this source for a Keyword immediately before it's saved to the database.
	 *
	 * @since   3.0.9
	 *
	 * @param   mixed $result     Validation Result (WP_Error | bool).
	 * @param   array $keyword    Keyword.
	 * @param   array $form_data  Keyword Form Data.
	 * @return  mixed               WP_Error | bool
	 */
	public function validate( $result, $keyword, $form_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// If result is an error from e.g. another filter, bail.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Check that data was supplied.
		if ( empty( $keyword['data'] ) ) {
			return new WP_Error( 'page_generator_pro_keywords_save_validation_error', __( 'Please complete the keyword data field.', 'page-generator' ) );
		}

		// Validation passed.
		return true;

	}

}
