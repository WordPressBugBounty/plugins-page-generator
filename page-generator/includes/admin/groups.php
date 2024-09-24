<?php
/**
 * Content Groups Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Handles creating, editing, deleting and calling the generate routine
 * for the Generate Content section of the Plugin.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 2.0.2
 */
class Page_Generator_Pro_Groups {

	/**
	 * Holds the base class object.
	 *
	 * @since   2.0.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Stores the current Group the settings are defined for.
	 *
	 * @since   2.0.2
	 *
	 * @var     int
	 */
	public $group_id = 0;

	/**
	 * Stores a Group's settings
	 *
	 * @since   2.0.2
	 *
	 * @var     array
	 */
	public $settings = array();

	/**
	 * Holds query results from calling get_all_ids_names(),
	 * for performance
	 *
	 * @since   3.0.7
	 *
	 * @var     mixed
	 */
	private $ids_names = false;

	/**
	 * Constructor.
	 *
	 * @since   1.2.3
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Creates a single Group, if none exist, when the Plugin is activated.
	 *
	 * @since   1.3.8
	 *
	 * @global  $wpdb   WordPress DB Object.
	 */
	public function activate() {

		// Bail if we already have at least one Group.
		$number_of_groups = $this->get_count();
		if ( $number_of_groups > 0 ) {
			return;
		}

		// Create Group.
		wp_insert_post(
			array(
				'post_type'    => $this->base->get_class( 'post_type' )->post_type_name,
				'post_status'  => 'publish',
				'post_title'   => __( 'Title', 'page-generator' ),
				'post_content' => __( 'Edit this content, replacing it with the content you want to generate. You can use {keywords} here too.  Need help? Visit <a href="https://www.wpzinc.com/documentation/page-generator-pro/generate-content/" rel="nofollow noreferrer noopener" target="_blank">https://www.wpzinc.com/documentation/page-generator-pro/generate-content/</a>', 'page-generator' ),
			)
		);

	}

	/**
	 * Defines a default settings structure when creating a new group
	 *
	 * @since   1.2.0
	 *
	 * @return  array   Group
	 */
	public function get_defaults() {

		// Get Defaults.
		$defaults = array(
			'group_type'                               => 'content',
			'title'                                    => '',
			'description'                              => '',
			'permalink'                                => '',
			'content'                                  => '',
			'excerpt'                                  => '',
			'latitude'                                 => '',
			'longitude'                                => '',
			'store_keywords'                           => 1,
			'meta'                                     => array(),
			'rotateAuthors'                            => 0,
			'author'                                   => ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 ),

			'comments'                                 => 0,
			'comments_generate'                        => array(
				'enabled'       => 0,
				'limit'         => 0,
				'date_option'   => 'now',
				'date_specific' => gmdate( 'Y-m-d' ),
				'date_min'      => gmdate( 'Y-m-d', strtotime( '-1 week' ) ),
				'date_max'      => gmdate( 'Y-m-d' ),
				'firstname'     => '',
				'surname'       => '',
				'comment'       => '',
			),

			'trackbacks'                               => 0,
			'type'                                     => 'page',
			'status'                                   => 'publish',
			'date_option'                              => 'now',
			'date_specific'                            => gmdate( 'Y-m-d' ),
			'date_min'                                 => gmdate( 'Y-m-d', strtotime( '-1 week' ) ),
			'date_max'                                 => gmdate( 'Y-m-d' ),
			'schedule'                                 => 1,
			'scheduleUnit'                             => 'hours',
			'method'                                   => 'all',
			'overwrite'                                => 'overwrite',
			'overwrite_sections'                       => array(
				'post_title'     => 1,
				'post_content'   => 1,
				'post_excerpt'   => 1,
				'post_status'    => 1,
				'post_author'    => 1,
				'post_date'      => 1,
				'comment_status' => 1,
				'comments'       => 1,
				'ping_status'    => 1,
				'custom_fields'  => 1,
				'featured_image' => 1,
				'attributes'     => 1,
				'taxonomies'     => 1,
				'menu'           => 1,
			),
			'numberOfPosts'                            => 0,
			'resumeIndex'                              => 0,
			'auto_regeneration'                        => 0,
			'auto_regeneration_interval'               => 30,
			'pageParent'                               => '',
			'pageTemplate'                             => '',
			'tax'                                      => '',
			'menu'                                     => 0,
			'menu_title'                               => '',
			'menu_parent'                              => '',

			// Featured Image: Source.
			'featured_image_source'                    => '', // id (media library), url, pexels, pixabay.

			// Featured Image: Search Parameters: Media Library.
			'featured_image_media_library_title'       => '',
			'featured_image_media_library_caption'     => '',
			'featured_image_media_library_description' => '',
			'featured_image_media_library_alt'         => '',
			'featured_image_media_library_operator'    => '',
			'featured_image_media_library_ids'         => '',
			'featured_image_media_library_min_id'      => '',
			'featured_image_media_library_max_id'      => '',

			// Featured Image: Search Parameters: URL, Pexels, Pixabay.
			'featured_image'                           => '', // URL or Term.

			// Featured Image: Search Parameters: Pexels, Pixabay.
			'featured_image_orientation'               => '',

			// Featured Image: Search Parameters: Pixabay.
			'featured_image_pixabay_language'          => '',
			'featured_image_pixabay_image_type'        => '',
			'featured_image_pixabay_image_category'    => '',
			'featured_image_pixabay_image_color'       => '',

			// Featured Image: Wikipedia.
			'featured_image_wikipedia_language'        => 'en',

			// Featured Image: Output.
			'featured_image_copy'                      => 0, // Create as Copy.
			'featured_image_title'                     => '',
			'featured_image_alt'                       => '',
			'featured_image_caption'                   => '',
			'featured_image_description'               => '',
			'featured_image_filename'                  => '',

			// Featured Image: EXIF.
			'featured_image_exif_latitude'             => '',
			'featured_image_exif_longitude'            => '',
			'featured_image_exif_comments'             => '',
			'featured_image_exif_description'          => '',
		);

		/**
		 * Defines the default settings structure when a new Content Group is created.
		 *
		 * @since   1.2.0
		 *
		 * @param   array   $defaults   Default Settings.
		 */
		$defaults = apply_filters( 'page_generator_pro_groups_get_defaults', $defaults );

		// Return.
		return $defaults;

	}

	/**
	 * Returns a Group's Settings by the given Group ID
	 *
	 * @since   1.2.1
	 *
	 * @param   int  $id                         ID.
	 * @param   bool $include_stats              Include Generated Count and Last Index Generated.
	 * @param   bool $remove_orphaned_metadata   If enabled, remove any orphaned data that might remain due to changing Page Builder, SEO or Schema Plugin.
	 * @return  mixed                               false | array
	 */
	public function get_settings( $id, $include_stats = true, $remove_orphaned_metadata = false ) {

		// Bail if the ID isn't for a Content Group.
		if ( get_post_type( $id ) !== 'page-generator-pro' ) {
			return new WP_Error(
				'page_generator_pro_groups_get_settings_error',
				sprintf(
					/* translators: Group ID */
					esc_html__( 'ID %s is not a Content Group.  Did you enter the correct Content Group ID?', 'page-generator' ),
					$id
				)
			);
		}

		// Get settings.
		$settings = get_post_meta( $id, '_page_generator_pro_settings', true );

		// If the result isn't an array, we're getting settings for a new Group, so just use the defaults.
		if ( ! is_array( $settings ) ) {
			$settings = $this->get_defaults();
		} else {
			// Store the Post's Title and Content in the settings, for backward compat.
			$post                = get_post( $id );
			$settings['title']   = $post->post_title;
			$settings['content'] = $post->post_content;

			// Merge with defaults, so keys are always set.
			$settings = array_merge( $this->get_defaults(), $settings );

			// For PHP 8 compat, ensure some fields have numerical values.
			if ( empty( $settings['numberOfPosts'] ) ) {
				$settings['numberOfPosts'] = 0;
			}
			if ( empty( $settings['resumeIndex'] ) ) {
				$settings['resumeIndex'] = 0;
			}
		}

		// Fetch all Metadata stored against the Group ID, and add that to the settings array.
		$settings['post_meta'] = $this->get_post_meta( $id );

		// Format date_specific to include time if necessary, so it works with 3.1.6+'s datetime-local input.
		if ( ! empty( $settings['date_specific'] ) ) {
			if ( strpos( $settings['date_specific'], ':' ) === false ) {
				$settings['date_specific'] .= 'T00:00:00';
			} else {
				$settings['date_specific'] = str_replace( ' ', 'T', $settings['date_specific'] );
			}
		}

		// Add the generated pages count and last index that was generated.
		if ( $include_stats ) {
			$settings['generated_pages_count'] = $this->get_generated_count_by_id( $id );
			$settings['last_index_generated']  = $this->get_last_index_generated( $id );
		}

		// Remove any orphaned data, such as Page Builder, SEO or Schema metadata, from the Group before generation is run,
		// that might remain due to changing Page Builder, SEO or Schema Plugin.
		if ( $remove_orphaned_metadata ) {
			/**
			 * Remove any orphaned data, such as Page Builder, SEO or Schema metadata, from the Group before generation is run,
			 * that might remain due to changing Page Builder, SEO or Schema Plugin.
			 *
			 * @since   3.3.7
			 *
			 * @param   array   $group   Group Settings.
			 */
			$settings = apply_filters( 'page_generator_pro_groups_get_settings_remove_orphaned_settings', $settings );
		}

		// Return settings.
		return $settings;

	}

	/**
	 * Returns all Post Metadata for the given Group ID, excluding some specific keys.
	 *
	 * This ensures that Page Builder data, ACF data etc. is included in the Group
	 * settings and subsequently copied to the generated Page.
	 *
	 * @since   1.4.4
	 *
	 * @param   int $id             Group ID.
	 * @return  array                   Metadata
	 */
	private function get_post_meta( $id ) {

		// Fetch all metadata.
		$meta = get_post_meta( $id );

		// Bail if no metadata was returned.
		if ( empty( $meta ) ) {
			return false;
		}

		// Define the metadata to ignore.
		$ignored_keys = array(
			'_edit_lock',
			'_edit_last',
			'_page_generator_pro_last_index_generated',
			'_page_generator_pro_settings',
			'_page_generator_pro_status',
			'_page_generator_pro_system',
			'_yoast_wpseo_content_score',
		);

		/**
		 * Defines Post Meta Keys in a Content Group to ignore and not copy to generated Posts / Groups.
		 *
		 * @since   1.4.4
		 *
		 * @param   array   $ignored_keys   Ignored Keys.
		 * @param   int     $id             Group ID.
		 */
		$ignored_keys = apply_filters( 'page_generator_pro_groups_get_post_meta_ignored_keys', $ignored_keys, $id );

		// Iterate through the metadata, removing items we don't want.
		foreach ( $meta as $meta_key => $meta_value ) {
			// Remove ignored keys.
			if ( in_array( $meta_key, $ignored_keys, true ) ) {
				unset( $meta[ $meta_key ] );
				continue;
			}

			// Fetch the single value.
			$value = get_post_meta( $id, $meta_key, true );

			/**
			 * Filters the Group Metadata for the given Key and Value
			 *
			 * @since   2.6.1
			 *
			 * @param   mixed   $value  Meta Value.
			 * @return  mixed           Meta Value
			 */
			$value = apply_filters( 'page_generator_pro_groups_get_post_meta_' . $meta_key, $value );

			// Assign value to the metadata array.
			$meta[ $meta_key ] = $value;
		}

		/**
		 * Filters the Group Metadata to return.
		 *
		 * @since   1.4.4
		 *
		 * @param   array   $meta   Metadata.
		 * @param   int     $id     Group ID
		 */
		$meta = apply_filters( 'page_generator_pro_groups_get_post_meta', $meta, $id );

		// Return filtered metadata.
		return $meta;

	}

	/**
	 * Get the number of Groups
	 *
	 * @since   1.3.8
	 *
	 * @return  int             Number of Generated Pages / Posts / CPTs.
	 */
	public function get_count() {

		// Fetch valid Post Statuses that can be used when generating content.
		$statuses = array_keys( $this->base->get_class( 'common' )->get_post_statuses() );

		$posts = new WP_Query(
			array(
				'post_type'              => $this->base->get_class( 'post_type' )->post_type_name,
				'post_status'            => $statuses,
				'posts_per_page'         => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'fields'                 => 'ids',
			)
		);

		return count( $posts->posts );

	}

	/**
	 * Get the number of Pages / Posts / CPTs generated by the given Group ID
	 *
	 * @since   1.2.3
	 *
	 * @param   int $id     Group ID.
	 * @return  int             Number of Generated Pages / Posts / CPTs
	 */
	private function get_generated_count_by_id( $id ) {

		return count( $this->get_generated_post_ids_by_id( $id ) );

	}

	/**
	 * Get the Page / Posts / CPT IDs generated by the given Group ID
	 *
	 * @since   3.1.7
	 *
	 * @param   int $id     Group ID.
	 * @return  mixed           array
	 */
	public function get_generated_post_ids_by_id( $id ) {

		// Fetch valid Post Statuses that can be used when generating content.
		$statuses = array_keys( $this->base->get_class( 'common' )->get_post_statuses() );

		$posts = new WP_Query(
			array(
				'post_type'              => 'any',
				'post_status'            => $statuses,
				'posts_per_page'         => -1,
				'meta_query'             => array(
					array(
						'key'   => '_page_generator_pro_group',
						'value' => absint( $id ),
					),
				),
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'fields'                 => 'ids',
			)
		);

		return $posts->posts;

	}

	/**
	 * Runs an action on a Group
	 *
	 * Called by both row actions and edit actions
	 *
	 * @since   1.9.5
	 *
	 * @param   string $action     Action.
	 * @param   int    $id         Group ID.
	 * @param   bool   $redirect   Redirct on success / error.
	 */
	public function run_action( $action, $id, $redirect = false ) {

		switch ( $action ) {

			/**
			 * Generate
			 */
			case 'generate':
				// Validate group before passing this request through.
				$result = $this->validate( $id );
				if ( $result ) {
					wp_safe_redirect( 'admin.php?page=' . $this->base->plugin->name . '-generate&id=' . $id . '&type=content' );
					die;
				}
				break;

			/**
			 * Test
			 */
			case 'test':
				$result = $this->test( $id );
				break;

			/**
			 * Trash Generated Content
			 */
			case 'trash_generated_content':
				$result = $this->trash_generated_content( $id );
				break;

			/**
			 * Delete Generated Content
			 */
			case 'delete_generated_content':
				$result = $this->delete_generated_content( $id );
				break;

			/**
			 * Cancel Generation
			 */
			case 'cancel_generation':
				$result = $this->cancel_generation( $id );
				break;

			default:
				/**
				 * Run a custom row action on a Group.
				 *
				 * @since   1.9.5
				 *
				 * @param   mixed   $result     Result (WP_Error | bool | string).
				 * @param   string  $action     Action.
				 * @param   int     $id         Group ID.
				 */
				$result = false;
				$result = apply_filters( 'page_generator_pro_groups_run_row_actions', $result, $action, $id );
				break;

		}

		// If there is no result from the action, nothing happened.
		if ( ! isset( $result ) || $result === false ) {
			return;
		}

		// Setup notices class, enabling persistent storage.
		$this->base->get_class( 'notices' )->enable_store();
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

		// Depending on the result of the action, store a notification and redirect.
		if ( is_wp_error( $result ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );

			if ( $redirect ) {
				// Redirect to the Generate Content WP_List_Table.
				wp_safe_redirect( $this->base->get_class( 'groups_table' )->get_action_url() );
				die();
			}
		}

		// Build success notice, if an error didn't occur.
		$message = false;
		if ( ! is_wp_error( $result ) ) {
			switch ( $action ) {

				/**
				 * Test
				 */
				case 'test':
					$message = sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						sprintf(
							/* translators: Number of seconds */
							__( 'Test Page Generated in %s seconds at ', 'page-generator' ),
							$result['duration']
						),
						$result['url'],
						$result['url']
					);

					foreach ( $result['keywords_terms'] as $keyword => $term ) {
						$message .= "\n";
						$message .= '{' . $keyword . '}: ' . ( strlen( $term ) > 50 ? substr( $term, 0, 50 ) . '...' : $term );
					}
					break;

				default:
					// Get message.
					$message = $this->base->get_class( 'groups_ui' )->get_message( $action . '_success' );

					/**
					 * Define an optional success message based on the result of a custom row action on a Group.
					 *
					 * @since   1.9.5
					 *
					 * @param   mixed   $message    Success Message (false | string).
					 * @param   mixed   $result     Result (WP_Error | bool | string).
					 * @param   string  $action     Action.
					 * @param   int     $id         Group ID.
					 */
					$message = apply_filters( 'page_generator_pro_groups_run_row_actions_success_message', $message, $result, $action, $id );
					break;

			}
		}

		// Store success notice.
		if ( $message !== false ) {
			$this->base->get_class( 'notices' )->add_success_notice( $message );
		}

		// Redirect to the Generate Content WP_List_Table.
		if ( $redirect ) {
			wp_safe_redirect( $this->base->get_class( 'groups_table' )->get_action_url() );
			die();
		}

	}

	/**
	 * Adds or edits a record, based on the given settings array.
	 *
	 * @since   1.2.1
	 *
	 * @param   array $settings   Settings to save.
	 * @param   int   $group_id   Group ID.
	 * @return  mixed               WP_Error | bool
	 */
	public function save( $settings, $group_id ) {

		// Ensure some keys have a value, in case the user blanked out the values or it's a checkbox that wasn't selected
		// This prevents errors later on when trying to generate content from a Group.
		if ( ! isset( $settings['store_keywords'] ) ) {
			$settings['store_keywords'] = 0;
		}

		if ( ! isset( $settings['comments_generate']['enabled'] ) ) {
			$settings['comments_generate']['enabled'] = 0;
		}

		if ( empty( $settings['resumeIndex'] ) ) {
			$settings['resumeIndex'] = 0;
		}

		// Sanitize the Permalink setting.
		if ( ! empty( $settings['permalink'] ) ) {
			$settings['permalink'] = preg_replace( '/[^a-z0-9-_{}\(\):]+/i', '', str_replace( ' ', '-', trim( $settings['permalink'] ) ) );
		}

		// Clear out blank meta.
		if ( isset( $settings['meta'] ) && is_array( $settings['meta'] ) && count( $settings['meta'] ) > 0 ) {
			foreach ( $settings['meta']['key'] as $index => $value ) {
				if ( empty( $value ) ) {
					unset( $settings['meta']['key'][ $index ] );
					unset( $settings['meta']['value'][ $index ] );
				}
			}
		}

		// Merge with defaults, so any missing keys are always set.
		$settings = array_merge( $this->get_defaults(), $settings );

		// Trim top level settings.
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}

			$settings[ $key ] = trim( $value );
		}
		if ( is_array( $settings['pageParent'] ) ) {
			$settings['pageParent'] = array_map( 'trim', $settings['pageParent'] );
		}

		// Update Post Meta.
		update_post_meta( $group_id, '_page_generator_pro_settings', $settings );

		/**
		 * Save data to Group Post's Meta
		 *
		 * @since   2.8.6
		 *
		 * @param   int     $group_id   Group ID.
		 * @param   array   $settings   Group Settings.
		 * @param   array   $_REQUEST   Request data.
		 */
		do_action( 'page_generator_pro_groups_save', $group_id, $settings, $_REQUEST ); // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter, WordPress.Security.NonceVerification

		// Validate the Group, adding error notices as necessary.
		return $this->validate( $group_id );

	}

	/**
	 * Performs several validations on the given Group Settings, to ensure that
	 * content generation will function successfully.
	 *
	 * These validations should be corrected in save() prior to calling this function,
	 * but this serves as a fallback catch in case they're not.
	 *
	 * @since   2.0.1
	 *
	 * @param   int $id     Group ID.
	 * @return  mixed           WP_Error | bool
	 */
	public function validate( $id ) {

		// Fetch group settings.
		$settings = $this->get_settings( $id, false );

		// Bail if an error occured.
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		// If the Permalink isn't empty, check it has at least one keyword specified
		// so that unique permalinks are produced.
		if ( ! empty( $settings['permalink'] ) ) {
			preg_match_all( '|{(.+?)}|', $settings['permalink'], $matches );

			if ( ! is_array( $matches ) || count( $matches[1] ) === 0 ) {
				return new WP_Error(
					'page_generator_pro_groups_validate_permalink',
					__( 'The Permalink must either be blank or contain one or more keywords, so that a unique Permalink is produced for each generated page.  Defining a "static" Permalink will result in a single generated page, regardless of any other settings.', 'page-generator' )
				);
			}

			// Check that the number of opening and closing braces match, to ensure there isn't a typo that would
			// result in the same generated Page being overwritten.
			if ( substr_count( $settings['permalink'], '{' ) !== substr_count( $settings['permalink'], '}' ) ) {
				return new WP_Error(
					'page_generator_pro_groups_validate_permalink',
					__( 'One or more Keywords in the Permalink are missing opening and/or closing braces. This must be fixed for generation to work correctly.', 'page-generator' )
				);
			}
		}

		// If no Author has been specified, return an error.
		if ( empty( $settings['author'] ) && ! $settings['rotateAuthors'] ) {
			return new WP_Error(
				'page_generator_pro_groups_validate_author',
				__( 'The Author must be specified, or the Random Author option selected.', 'page-generator' )
			);
		}

		// If the Group is not published, generation might fail in Gutenberg stating that no keywords could be found
		// in the Content.
		$post_status             = get_post_status( $id );
		$required_group_statuses = $this->get_group_statuses();
		if ( ! in_array( $post_status, array_keys( $required_group_statuses ), true ) ) {
			return new WP_Error(
				'page_generator_pro_groups_validate_save',
				sprintf(
					/* translators: %1$s: Comma separated list of required Group Statuses (e.g. publish,future), %2$s: Current Group Status (e.g. draft), %3$s: URL to Edit Content Group screen */
					__( 'The Group\'s Status must be set to one of <strong>%1$s</strong> for Generation to function correctly. Right now, it\'s set as <strong>%2$s</strong>. Please <a href="%3$s">edit the Group</a> as necessary.', 'page-generator' ),
					implode( ', ', $required_group_statuses ),
					get_post_status( $id ),
					admin_url( 'post.php?post=' . $id . '&action=edit' )
				)
			);
		}

		$result = true;

		/**
		 * Performs several validations on the given Group Settings, to ensure that
		 * content generation will function successfully.
		 *
		 * @since   2.0.1
		 *
		 * @param   mixed   $rest       Validation Result (WP_Error | bool).
		 * @param   array   $settings   Group Settings.
		 * @param   int     $id         Group ID.
		 */
		$result = apply_filters( 'page_generator_pro_groups_validate', $result, $settings, $id );

		// Return result.
		return $result;

	}

	/**
	 * Returns an array of Post Statuses that can be used when saving a Content Group.
	 *
	 * If a Group's Status does not match a status defined here, generation might fail in Gutenberg
	 * stating that no keywords could be found.
	 *
	 * @since   2.6.5
	 *
	 * @return  array   Supported Group Statuses
	 */
	public function get_group_statuses() {

		// Get statuses.
		$statuses = array(
			'private' => __( 'Private', 'page-generator' ),
			'publish' => __( 'Publish', 'page-generator' ),
		);

		/**
		 * Defines available Post Statuses for generated content.
		 *
		 * @since   2.6.5
		 *
		 * @param   array   $statuses   Statuses.
		 */
		$statuses = apply_filters( 'page_generator_pro_groups_get_group_statuses', $statuses );

		// Return filtered results.
		return $statuses;

	}

	/**
	 * Fetches the last index generated for the given Group.
	 *
	 * @since   2.2.6
	 *
	 * @param   int $id     Group ID.
	 */
	public function get_last_index_generated( $id ) {

		return absint( get_post_meta( $id, '_page_generator_pro_last_index_generated', true ) );

	}

	/**
	 * Stores the given index as the last generated index for the given
	 * Group.
	 *
	 * @since   2.2.6
	 *
	 * @param   int $id     Group ID.
	 * @param   int $index  Last Index Generated.
	 */
	public function update_last_index_generated( $id, $index ) {

		update_post_meta( $id, '_page_generator_pro_last_index_generated', $index );

	}

	/**
	 * Tests content for the given Group ID
	 *
	 * @since   1.8.0
	 *
	 * @param   int $id     Group ID.
	 * @return  mixed           WP_Error | array
	 */
	public function test( $id ) {

		// Fetch group.
		$post = get_post( $id );
		if ( ! $post ) {
			return new WP_Error(
				'page_generator_pro_groups_test',
				sprintf(
					/* translators: Group ID */
					__( 'Group ID %s does not exist!', 'page-generator' ),
					$id
				)
			);
		}

		// Fetch group settings.
		$settings = $this->get_settings( $id, false );
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		// Validate group.
		$validated = $this->validate( $id );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Run test.
		$result = $this->base->get_class( 'generate' )->generate_content( $id, $settings['resumeIndex'], true );

		// Define success / error notice based on the test result.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Runs any actions once Generate Content has finished.
		 *
		 * @since   1.9.3
		 *
		 * @param   int     $group_id   Group ID.
		 * @param   bool    $test_mode  Test Mode.
		 * @param   string  $system     System.
		 */
		do_action( 'page_generator_pro_generate_content_after', $id, true, 'browser' );

		// Return result.
		return $result;

	}

	/**
	 * Trashes Generated Content for the given Group ID
	 *
	 * @since   1.9.1
	 *
	 * @param   int $id     Group ID.
	 * @return  mixed           WP_Error | array
	 */
	public function trash_generated_content( $id ) {

		// Trash Generated Content now.
		return $this->base->get_class( 'generate' )->trash_content( $id );

	}

	/**
	 * Deletes Generated Content for the given Group ID
	 *
	 * @since   1.8.0
	 *
	 * @param   int $id     Group ID.
	 * @return  mixed           WP_Error | array
	 */
	public function delete_generated_content( $id ) {

		// Delete Generated Content now.
		return $this->base->get_class( 'generate' )->delete_content( $id );

	}

	/**
	 * Returns a flag denoting whether the given Group ID has generated content
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool            Has Generated Content
	 */
	public function has_generated_content( $id ) {

		// Get number of generated pages.
		$generated_pages_count = $this->get_generated_count_by_id( $id );

		if ( $generated_pages_count > 0 ) {
			return true;
		}

		return false;

	}

	/**
	 * Determines if the given Content Group is eligible to generate content
	 *
	 * @since   3.3.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool            Can Generate Content
	 */
	public function generates_content( $id ) {

		$can_generate_content = true;

		/**
		 * Determine if the given Content Group is eligible to generate content
		 *
		 * @since   3.3.9
		 *
		 * @param   bool    $can_generate_content   Can Generate Content.
		 * @param   int     $id                     Group ID.
		 */
		$can_generate_content = apply_filters( 'page_generator_pro_groups_generates_content', $can_generate_content, $id );

		// Return filtered result.
		return $can_generate_content;

	}

	/**
	 * Returns a flag denoting whether the given Group ID is idle i.e. not generating
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool            Is Idle (not generating)
	 */
	public function is_idle( $id ) {

		$status = $this->get_status( $id );

		if ( $status === 'idle' || empty( $status ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Returns a flag denoting whether the given Group ID is generating
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool            Is Generating
	 */
	public function is_generating( $id ) {

		$status = $this->get_status( $id );

		if ( $status === 'generating' ) {
			return true;
		}

		return false;

	}

	/**
	 * Gets the status of the given Group ID (idle, scheduled, generating)
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  string          Status
	 */
	public function get_status( $id ) {

		return get_post_meta( $id, '_page_generator_pro_status', true );

	}

	/**
	 * Gets the given Group ID's system being used for generation
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 */
	public function get_system( $id ) {

		return get_post_meta( $id, '_page_generator_pro_system', true );

	}

	/**
	 * Starts generation for the given Group ID by:
	 * - Defining the status flag
	 * - Defining the system flag
	 * - Deleting the cancel flag
	 *
	 * @since   1.9.9
	 *
	 * @param   int    $id             Group ID.
	 * @param   string $status     Status.
	 * @param   string $system     Generation System.
	 * @return  bool
	 */
	public function start_generation( $id, $status, $system ) {

		update_post_meta( $id, '_page_generator_pro_status', $status );
		update_post_meta( $id, '_page_generator_pro_system', $system );
		delete_post_meta( $id, '_page_generator_pro_cancel' );

		return true;

	}

	/**
	 * Cancels generation for the given Group ID by:
	 * - Deleting the status flag
	 * - Deleting the system flag
	 * - Adding a cancel flag, so that if the generation process is running async, it'll stop
	 * on the next iteration.
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool
	 */
	public function cancel_generation( $id ) {

		delete_post_meta( $id, '_page_generator_pro_status' );
		delete_post_meta( $id, '_page_generator_pro_system' );
		update_post_meta( $id, '_page_generator_pro_cancel', 1 );

		return true;

	}

	/**
	 * Returns a flag denoting whether the given Group ID has a request to cancel generation.
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool        Stop Generation
	 */
	public function cancel_generation_requested( $id ) {

		global $wpdb;

		// Read value directly from the DB, so that a cached meta value is not returned
		// This ensures that cron, cli will perform a fresh read for each generated
		// item to ensure generation is cancelled if the flag has been set through the browser
		// through the cancel command.
		$result = $wpdb->get_var(
			' SELECT meta_value FROM ' . $wpdb->postmeta . '
                                    WHERE post_id = ' . absint( $id ) . "
                                    AND meta_key = '_page_generator_pro_cancel'
                                    LIMIT 1"
		);

		return (bool) $result;

	}

	/**
	 * Stops generation for the given Group ID by:
	 * - Deleting the status flag
	 * - Deleting the system flag
	 * - Deleting the cancellation flag
	 *
	 * @since   1.9.9
	 *
	 * @param   int $id     Group ID.
	 * @return  bool
	 */
	public function stop_generation( $id ) {

		delete_post_meta( $id, '_page_generator_pro_status' );
		delete_post_meta( $id, '_page_generator_pro_system' );
		delete_post_meta( $id, '_page_generator_pro_cancel' );

		return true;

	}

	/**
	 * Determines if the given Page ID was generated by the given Group ID
	 *
	 * @since   3.0.9
	 *
	 * @param   int $post_id    Post ID.
	 * @param   int $group_id   Group ID.
	 * @return  bool                Post ID was generated by Group ID
	 */
	public function is_generated_by_group( $post_id, $group_id ) {

		// Get Group ID that generated the given Post ID.
		$post_generated_group_id = get_post_meta( $post_id, '_page_generator_pro_group', true );

		if ( ! $post_generated_group_id ) {
			return false;
		}
		if ( ! is_numeric( $post_generated_group_id ) ) {
			return false;
		}

		// Return false if the Post ID doesn't match.
		return ( ( absint( $post_generated_group_id ) !== absint( $group_id ) ) ? false : true );

	}

}
