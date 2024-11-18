<?php
/**
 * Common Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Helper and generic functions that don't fit into a specific class.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.0.0
 */
class Page_Generator_Pro_Common {

	/**
	 * Holds the base object.
	 *
	 * @since   1.9.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
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
	 * Helper method to retrieve Generation Systems
	 *
	 * @since   2.6.1
	 *
	 * @return  array   Generation Systems
	 */
	public function get_generation_systems() {

		// Get systems.
		$systems = array(
			'browser' => __( 'Browser', 'page-generator' ),
		);

		/**
		 * Defines available Generation Systems
		 *
		 * @since   2.6.1
		 *
		 * @param   array   $systems    Generation Systems.
		 */
		$systems = apply_filters( 'page_generator_pro_common_get_generation_systems', $systems );

		// Return filtered results.
		return $systems;

	}

	/**
	 * Helper method to return all WordPress User IDs.
	 *
	 * @since   4.6.6
	 *
	 * @return  array   Authors
	 */
	public function get_all_user_ids() {

		// Get all user IDs.
		$user_ids = get_users(
			array(
				'fields'  => 'ID',
				'orderby' => 'ID',
			)
		);

		/**
		 * Defines available user IDs.
		 *
		 * @since   4.6.6
		 *
		 * @param   array   $user_ids    User IDs.
		 */
		$user_ids = apply_filters( 'page_generator_pro_common_get_all_user_ids', $user_ids );

		// Return filtered results.
		return $user_ids;

	}

	/**
	 * Helper method to retrieve public Post Types
	 *
	 * @since   1.1.3
	 *
	 * @return  array   Public Post Types
	 */
	public function get_post_types() {

		// Get public Post Types.
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Remove excluded Post Types from $types.
		$excluded_types = $this->get_excluded_post_types();
		if ( is_array( $excluded_types ) ) {
			foreach ( $excluded_types as $excluded_type ) {
				unset( $types[ $excluded_type ] );
			}
		}

		/**
		 * Defines the available public Post Type Objects that content can be generated for.
		 *
		 * @since   1.1.3
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( 'page_generator_pro_common_get_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Returns an array of Post Types supporting the given feature
	 *
	 * @since   3.3.9
	 *
	 * @param   string $feature    post_type_supports() compatible $feature argument.
	 * @return  array               Post Types supporting feature
	 */
	public function get_post_types_supporting( $feature ) {

		// Get public Post Types.
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Remove excluded Post Types from $types.
		$excluded_types = $this->get_excluded_post_types();
		if ( is_array( $excluded_types ) ) {
			foreach ( $excluded_types as $excluded_type ) {
				unset( $types[ $excluded_type ] );
			}
		}

		// Get some settings we might check.
		$post_types_templates = $this->base->get_class( 'common' )->get_post_types_templates();

		foreach ( $types as $post_type => $type ) {
			// Some features aren't returned by post_type_supports().
			switch ( $feature ) {
				case 'hierarchical':
					// Remove this Post Type if it doesn't support this feature.
					if ( ! $type->hierarchical ) {
						unset( $types[ $post_type ] );
					}
					break;

				case 'templates':
					// Remove this Post Type if it doesn't have any Templates.
					if ( ! $post_types_templates ) {
						unset( $types[ $post_type ] );
						break;
					}
					if ( ! isset( $post_types_templates[ $post_type ] ) ) {
						unset( $types[ $post_type ] );
						break;
					}
					break;

				case 'taxonomies':
					// Remove this Post Type if it doesn't have any Taxonomies.
					if ( ! count( get_object_taxonomies( $post_type ) ) ) {
						unset( $types[ $post_type ] );
					}
					break;

				default:
					// Remove this Post Type if it doesn't support the feature.
					if ( ! post_type_supports( $post_type, $feature ) ) {
						unset( $types[ $post_type ] );
					}
					break;
			}
		}

		// Just get Post Type names.
		return array_values( array_keys( $types ) );

	}

	/**
	 * Improved version of post_type_supports() that can detect whether the Post Type supports:
	 * - Hierarchical structure
	 * - Templates
	 * - Taxonomies
	 *
	 * @since   3.3.9
	 *
	 * @param   string $post_type  Post Type.
	 * @param   string $feature    Feature.
	 * @return  bool                Feature supported by Post Type
	 */
	public function post_type_supports( $post_type, $feature ) {

		return in_array( $post_type, $this->get_post_types_supporting( $feature ), true );

	}

	/**
	 * Helper method to retrieve excluded Post Types
	 *
	 * @since   1.1.3
	 *
	 * @return  array                       Excluded Post Types
	 */
	public function get_excluded_post_types() {

		// Get excluded Post Types.
		$types = array(
			$this->base->get_class( 'post_type' )->post_type_name,
			'attachment',
			'revision',
			'nav_menu_item',
		);

		/**
		 * Defines the Post Type Objects that content cannot be generated for.
		 *
		 * @since   1.1.3
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( 'page_generator_pro_common_get_excluded_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Returns any available Templates for each Post Type
	 *
	 * @since   1.5.8
	 *
	 * @return  array   Post Types and Templates
	 */
	public function get_post_types_templates() {

		// Get Post Types.
		$post_types = $this->get_post_types();

		// Bail if no Post Types.
		if ( empty( $post_types ) ) {
			return false;
		}

		// Load necessary library if get_page_templates() isn't available.
		if ( ! function_exists( 'get_page_templates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		// Bail if get_page_templates() still isn't available.
		if ( ! function_exists( 'get_page_templates' ) ) {
			return false;
		}

		// Build templates.
		$templates = array();
		foreach ( $post_types as $post_type ) {
			// Skip if this Post Type doesn't have any templates.
			$post_type_templates = get_page_templates( null, $post_type->name );
			if ( empty( $post_type_templates ) ) {
				continue;
			}

			$templates[ $post_type->name ] = $post_type_templates;
		}

		/**
		 * Defines available Theme Templates for each Post Type that can have content
		 * generated for it.
		 *
		 * @since   1.5.8
		 *
		 * @param   array   $templates  Templates by Post Type.
		 */
		$templates = apply_filters( 'page_generator_pro_common_get_post_type_templates', $templates );

		// Return filtered results.
		return $templates;

	}

	/**
	 * Helper method to retrieve post statuses
	 *
	 * @since   1.1.3
	 *
	 * @return  array   Post Statuses
	 */
	public function get_post_statuses() {

		// Get statuses.
		$statuses = array(
			'draft'   => __( 'Draft', 'page-generator' ),
			'private' => __( 'Private', 'page-generator' ),
			'publish' => __( 'Publish', 'page-generator' ),
		);

		/**
		 * Defines available Post Statuses for generated content.
		 *
		 * @since   1.1.3
		 *
		 * @param   array   $statuses   Statuses.
		 */
		$statuses = apply_filters( 'page_generator_pro_common_get_post_statuses', $statuses );

		// Return filtered results.
		return $statuses;

	}

	/**
	 * Returns configuration for autocomplete instances across tribute.js, TinyMCE and Gutenberg
	 * for keyword autocomplete functionality.
	 *
	 * @since   3.2.2
	 *
	 * @param   bool $is_group           If true, autocomplete fields are for a Content or Term Group.
	 *                                   If false, autocomplete fields are for Related Links shortcode on a Page or Post.
	 * @return  mixed                       false | Javascript DOM Selectors
	 */
	public function get_autocomplete_configuration( $is_group ) {

		// Get values, casting to an autocomplete compatible array as necessary.
		$values = $this->base->get_class( 'keywords' )->get_keywords_and_columns( true );

		// If no Keywords exist, don't initialize autocompleters.
		if ( ! $values ) {
			return false;
		}

		foreach ( $values as $index => $value ) {
			$values[ $index ] = array(
				'key'   => $value,
				'value' => $value,
			);
		}

		// Define autocomplete configuration.
		$autocomplete_configuration = array(
			array(
				'fields'   => $this->get_autocomplete_enabled_fields( $is_group ),
				'triggers' => array(
					array(
						'name'                    => 'keywords',
						'trigger'                 => '{',
						'values'                  => $values,
						'allowSpaces'             => false,
						'menuItemLimit'           => 20,

						// TinyMCE specific.
						'triggerKeyCode'          => 219, // Left square/curly bracket.
						'triggerKeyShiftRequired' => true, // Require shift key to also be pressed.
						'tinyMCEName'             => 'page_generator_pro_autocomplete_keywords',
					),
				),
			),
		);

		/**
		 * Define autocompleters to use across Content Groups, Term Group and TinyMCE
		 *
		 * @since   3.2.2
		 *
		 * @param   array   $autocomplete_configuration     Autocomplete Configuration.
		 * @param   bool    $is_group   If true, autocomplete fields are for a Content or Term Group.
		 *                              If false, autocomplete fields are for Related Links shortcode on a Page or Post.
		 */
		$autocomplete_configuration = apply_filters( 'page_generator_pro_common_get_autocomplete_configuration', $autocomplete_configuration, $is_group );

		// Return filtered results.
		return $autocomplete_configuration;

	}

	/**
	 * Returns an array of Javascript DOM selectors to enable the keyword
	 * autocomplete functionality on.
	 *
	 * @since   2.0.2
	 *
	 * @param   bool $is_group   If true, autocomplete fields are for a Content or Term Group.
	 *                           If false, autocomplete fields are for Related Links shortcode on a Page or Post.
	 * @return  array   Javascript DOM Selectors
	 */
	public function get_autocomplete_enabled_fields( $is_group = true ) {

		// Get fields.
		if ( $is_group ) {
			// Register autocomplete selectors across Group fields.
			$fields = array(
				// Classic Editor.
				'input[type=text]:not(#term-selectized)', // type=text prevents autocomplete greedily running on selectize inputs.
				'textarea',
				'div[contenteditable=true]',

				// Gutenberg.
				'h1[contenteditable=true]',

				// TinyMCE Plugins.
				'.wpzinc-autocomplete',
			);
		} else {
			// Register autocomplete selectors for Plugin-specific fields only
			// i.e. Related Links Shortcode.
			$fields = array(
				// Gutenberg
				// Is now handled using Dashboard Submodule's WPZincAutocompleterControl in autocomplete-gutenberg.js.

				// TinyMCE Plugins.
				'.wpzinc-autocomplete',
			);
		}

		/**
		 * Defines an array of Javascript DOM selectors to enable the keyword
		 * autocomplete functionality on.
		 *
		 * @since   2.0.2
		 *
		 * @param   array   $fields     Supported Fields.
		 * @param   bool    $is_group   If true, autocomplete fields are for a Content or Term Group.
		 *                              If false, autocomplete fields are for Related Links shortcode on a Page or Post.
		 */
		$fields = apply_filters( 'page_generator_pro_common_get_autocomplete_enabled_fields', $fields, $is_group );

		// Return filtered results.
		return $fields;

	}

	/**
	 * Returns an array of Javascript DOM selectors to enable the
	 * selectize functionality on.
	 *
	 * @since   2.5.4
	 *
	 * @return  array   Javascript DOM Selectors
	 */
	public function get_selectize_enabled_fields() {

		// Get fields.
		$fields = array(
			'freeform'  => array(
				'input.wpzinc-selectize-freeform',
				'.wpzinc-selectize-freeform input',
			),

			'drag_drop' => array(
				'select.wpzinc-selectize-drag-drop',
				'.wpzinc-selectize-drag-drop select',
			),

			'search'    => array(
				'select.wpzinc-selectize-search',
				'.wpzinc-selectize-search select',
			),

			'api'       => array(
				'select.wpzinc-selectize-api',
				'.wpzinc-selectize-api select',
			),

			'standard'  => array(
				'select.wpzinc-selectize',
				'.wpzinc-selectize select',
			),
		);

		/**
		 * Defines an array of Javascript DOM selectors to enable the
		 * selectize functionality on.
		 *
		 * @since   2.5.4
		 *
		 * @param   array   $fields  Supported Fields
		 */
		$fields = apply_filters( 'page_generator_pro_common_get_selectize_enabled_fields', $fields );

		// Return filtered results.
		return $fields;

	}

	/**
	 * Returns an array of events to reinitialize selectize instances
	 * on within Appearance > Customize
	 *
	 * @since   2.7.7
	 *
	 * @return  array   Events and Selectors
	 */
	public function get_selectize_reinit_events() {

		return array(
			'click'  => array(
				'li.accordion-section h3.accordion-section-title', // Top level Panels.
			),
			'change' => array(
				'input[name="_customize-radio-show_on_front"]', // Homepage Settings > Your homepage displays.
			),
		);

	}

	/**
	 * Helper method to return an array of WordPress Role Capabilities that should be disabled
	 * when a Content Group is Generating Content
	 *
	 * @since   1.9.9
	 *
	 * @return  array   Capabilities
	 */
	public function get_capabilities_to_disable_on_group_content_generation() {

		// Get capabilities.
		$capabilities = array(
			'delete_post',
			'edit_post',
		);

		/**
		 * Defines Role Capabilities that should be disabled when a Content Group is Generating Content.
		 *
		 * @since   1.9.9
		 *
		 * @param   array   $capabilities   Capabilities.
		 */
		$capabilities = apply_filters( 'page_generator_pro_common_get_capabilities_to_disable_on_group_content_generation', $capabilities );

		// Return filtered results.
		return $capabilities;

	}

	/**
	 * Recursively sanitizes a given multidimensional array.
	 *
	 * @since   1.6.5
	 *
	 * @param   array $arr  Values.
	 * @return  array       Sanitized values
	 */
	public function recursive_sanitize_text_field( $arr ) {

		foreach ( $arr as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->recursive_sanitize_text_field( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}

		return $arr;

	}

}
