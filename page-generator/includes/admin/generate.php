<?php
/**
 * Content Group Generation Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Handles generating content from Content Groups (Pages, Posts
 * and Custom Post Types)
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.0.0
 */
class Page_Generator_Pro_Generate {

	/**
	 * Holds the base object.
	 *
	 * @since   1.9.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds an array comprising of every keyword detected in the Group.
	 * Each Keyword holds an array comprising of every single Term for that Keyword.
	 *
	 * @since   1.9.8
	 *
	 * @var     array
	 */
	public $keywords = array();

	/**
	 * Holds an array comprising of every keyword detected in the Group.
	 * Each Keyword holds the nth Term that will be used to replace the Keyword.
	 *
	 * @since   1.9.8
	 *
	 * @var     array
	 */
	public $keywords_terms = array();

	/**
	 * Holds an array comprising of every required keyword detected in the Group.
	 *
	 * @since   4.0.4
	 *
	 * @var     array
	 */
	public $required_keywords = array();

	/**
	 * Holds an array comprising of every required keyword detected in the Group,
	 * including columns and modifiers.
	 *
	 * @since   4.0.4
	 *
	 * @var     array
	 */
	public $required_keywords_full = array();

	/**
	 * Holds the array of keywords to replace e.g. {city}
	 *
	 * @since   1.3.1
	 *
	 * @var     array
	 */
	public $searches = array();

	/**
	 * Holds the array of keyword values to replace e.g. Birmingham
	 *
	 * @since   1.3.1
	 *
	 * @var     array
	 */
	public $replacements = array();

	/**
	 * Holds a flag to denote if one or more $replacements are an array
	 * If they're an array, it's because the :random_different transformation
	 * is used, and so we have to perform a slower search/replace method.
	 *
	 * @since   2.7.2
	 *
	 * @var     bool
	 */
	public $replacements_contain_array = false;

	/**
	 * Holds a flag to denote whether Page Generator Pro shortcodes
	 * should be processed on the main Post Content
	 *
	 * @since   1.9.5
	 *
	 * @var     bool
	 */
	public $process_shortcodes_on_post_content = false;

	/**
	 * Constructor.
	 *
	 * @since   1.9.3
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Calculates the maximum number of items that will be generated based
	 * on the settings.
	 *
	 * @since   1.1.5
	 *
	 * @param   array $settings   Group Settings (either a Content or Term Group).
	 * @return  mixed               WP_Error | integer
	 */
	public function get_max_number_of_pages( $settings ) {

		// Remove some settings that we don't want to be spun/have keywords replaced on,
		// as they're for a subsection i.e. Comment Generation.
		unset( $settings['comments_generate'] );

		// Build a class array of required keywords that need replacing with data.
		$required_keywords = $this->find_keywords_in_settings( $settings );

		// Bail if no keywords were found.
		if ( count( $required_keywords['required_keywords'] ) === 0 ) {
			return 0;
		}

		// Update Keywords that don't use a local source now.
		$result = $this->base->get_class( 'keywords' )->refresh_terms( $required_keywords['required_keywords'] );

		// If an error occured refreshing Keywords, bail.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get the terms for each required keyword.
		$this->keywords = $this->get_keywords_terms_columns_delimiters( $required_keywords['required_keywords'] );

		// Bail if no keywords were found.
		if ( empty( $this->keywords['terms'] ) ) {
			return 0;
		}

		// Depending on the generation method chosen, for each keyword, define the term
		// that will replace it.
		switch ( $settings['method'] ) {

			/**
			 * All
			 * Random
			 * - Generates all possible term combinations across keywords
			 */
			case 'all':
			case 'random':
				$total = 1;
				foreach ( $this->keywords['terms'] as $keyword => $terms ) {
					$total = ( $total * count( $terms ) );
				}

				return $total;

			/**
			 * Sequential
			 * - Generates term combinations across keywords matched by index
			 */
			case 'sequential':
				$total = 0;
				foreach ( $this->keywords['terms'] as $keyword => $terms ) {
					if ( count( $terms ) > 0 && ( count( $terms ) < $total || $total === 0 ) ) {
						$total = count( $terms );
					}
				}

				return $total;

		}

		return 0;

	}

	/**
	 * Generates a Page, Post or Custom Post Type for the given Group and Index
	 *
	 * @since   1.6.1
	 *
	 * @param   int    $group_id                       Group ID.
	 * @param   int    $index                          Keyword Index.
	 * @param   bool   $test_mode                      Test Mode.
	 * @param   string $system                         System (browser|cron|cli).
	 * @param   mixed  $last_generated_post_date_time  Last Generated Post's Date and Time.
	 * @return  mixed                                   WP_Error | array
	 */
	public function generate_content( $group_id, $index = 0, $test_mode = false, $system = 'browser', $last_generated_post_date_time = false ) {

		// Performance debugging.
		$start = ( function_exists( 'hrtime' ) ? hrtime( true ) : microtime( true ) );

		// Define the Group ID and Index as globals, so it can be picked up by our shortcodes when they're processed.
		global $page_generator_pro_group_id, $page_generator_pro_index;
		$page_generator_pro_group_id = $group_id;
		$page_generator_pro_index    = $index;

		// If test mode is enabled, set the debug constant.
		if ( $test_mode && ! defined( 'PAGE_GENERATOR_PRO_DEBUG' ) ) {
			define( 'PAGE_GENERATOR_PRO_DEBUG', true );
		}

		// Get group settings.
		$settings = $this->base->get_class( 'groups' )->get_settings( $group_id, false, true );

		// Remove some settings that we don't want to be spun/have keywords replaced on,
		// as they're for a subsection i.e. Comment Generation.
		$original_settings = $settings;
		unset( $settings['comments_generate'] );

		// If this Group has a request to cancel generation, exit.
		if ( ! $test_mode ) {
			if ( $this->base->get_class( 'groups' )->cancel_generation_requested( $group_id ) ) {
				// Stop Generation.
				$this->base->get_class( 'groups' )->stop_generation( $group_id );

				// Return error.
				return $this->generate_error_return(
					new WP_Error( 'generation_error', __( 'A request to cancel generation was made by the User. Exiting...', 'page-generator' ) ),
					$group_id,
					0,
					$settings['type'],
					$test_mode,
					$system,
					false
				);
			}
		}

		// If the Group is not published, generation might fail in Gutenberg stating that no keywords could be found
		// in the Content. Change its status to published.
		if ( ! in_array( get_post_status( $group_id ), array_keys( $this->base->get_class( 'groups' )->get_group_statuses() ), true ) ) {
			$result = wp_update_post(
				array(
					'ID'          => $group_id,
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				// Return error.
				return $this->generate_error_return(
					$result,
					$group_id,
					0,
					$settings['type'],
					$test_mode,
					$system,
					false
				);
			}
		}

		// Validate group.
		$validated = $this->base->get_class( 'groups' )->validate( $group_id );
		if ( is_wp_error( $validated ) ) {
			return $this->generate_error_return(
				$validated,
				$group_id,
				0,
				$settings['type'],
				$test_mode,
				$system,
				false
			);
		}

		/**
		 * Run any actions before an individual Page, Post or Custom Post Type is generated
		 *
		 * @since   2.4.1
		 *
		 * @param   int     $group_id       Group ID.
		 * @param   array   $settings       Group Settings.
		 * @param   int     $index          Keyword Index.
		 * @param   bool    $test_mode      Test Mode
		 */
		do_action( 'page_generator_pro_generate_content_started', $group_id, $settings, $index, $test_mode );

		// Build a class array of required keywords that need replacing with data.
		$required_keywords = $this->find_keywords_in_settings( $settings );
		if ( count( $required_keywords['required_keywords'] ) === 0 ) {
			return $this->generate_error_return(
				new WP_Error( 'keyword_error', __( 'No keywords were specified in the Group.', 'page-generator' ) ),
				$group_id,
				0,
				$settings['type'],
				$test_mode,
				$system,
				false
			);
		}

		// Build a keywords array comprising of terms, columns and delimiters for each of the required keywords.
		$this->keywords = $this->get_keywords_terms_columns_delimiters( $required_keywords['required_keywords'] );
		if ( count( $this->keywords['terms'] ) === 0 ) {
			return $this->generate_error_return(
				new WP_Error( 'keyword_error', __( 'Keywords were specified in the Group, but no keywords exist in either the Keywords section of the Plugin or as a Taxonomy.', 'page-generator' ) ),
				$group_id,
				0,
				$settings['type'],
				$test_mode,
				$system,
				false
			);
		}

		// Build array of keyword --> term key/value pairs to use for this generation.
		$keywords_terms = $this->get_keywords_terms( $settings['method'], (int) $index );
		if ( is_wp_error( $keywords_terms ) ) {
			return $this->generate_error_return(
				$keywords_terms,
				$group_id,
				0,
				$settings['type'],
				$test_mode,
				$system,
				false
			);
		}

		// Rotate Author.
		if ( isset( $settings['rotateAuthors'] ) ) {
			$author_ids = $this->base->get_class( 'common' )->get_all_user_ids();
			$user_index = wp_rand( 0, ( count( $author_ids ) - 1 ) );
		}

		// Remove all shortcode processors, so we don't process any shortcodes. This ensures page builders, galleries etc
		// will work as their shortcodes will be processed when the generated page is viewed.
		remove_all_shortcodes();

		// Iterate through each detected Keyword to build a full $this->searches and $this->replacements arrays.
		$this->build_search_replace_arrays( $required_keywords['required_keywords_full'], $keywords_terms );

		// Iterate through each keyword and term key/value pair.
		$settings = $this->replace_keywords( $settings );

		// Define Post Name / Slug.
		// If no Permalink exists, use the Post Title.
		if ( ! empty( $settings['permalink'] ) ) {
			$post_name = sanitize_title( $settings['permalink'] );
		} else {
			$post_name = sanitize_title( $settings['title'] );
		}

		/**
		 * Modify the Group's settings prior to parsing shortcodes and building the Post Arguments
		 * to use for generating a single Page, Post or Custom Post Type.
		 *
		 * Changes made only affect this item in the generation set, and are not persistent or saved.
		 *
		 * For Gutenberg and Page Builders with Blocks / Elements registered by this Plugin, this
		 * is a good time to convert them to a Shortcode Block / Element / Text
		 *
		 * @since   2.6.0
		 *
		 * @param   array   $settings       Group Settings.
		 * @param   int     $group_id       Group ID.
		 * @param   int     $index          Keyword Index.
		 * @param   bool    $test_mode      Test Mode.
		 */
		$settings = apply_filters( 'page_generator_pro_generate_content_settings', $settings, $group_id, $index, $test_mode );

		// Build Post args.
		$post_args = array(
			'post_type'      => $settings['type'],
			'post_title'     => $settings['title'],
			'post_content'   => $settings['content'],
			'post_status'    => ( $test_mode ? 'draft' : $settings['status'] ),
			'post_author'    => ( ( isset( $settings['rotateAuthors'] ) && $settings['rotateAuthors'] == 1 && isset( $author_ids ) && isset( $user_index ) ) ? $author_ids[ $user_index ] : $settings['author'] ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			'comment_status' => ( ( isset( $settings['comments'] ) && $settings['comments'] == 1 ) ? 'open' : 'closed' ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			'ping_status'    => ( ( isset( $settings['trackbacks'] ) && $settings['trackbacks'] == 1 ) ? 'open' : 'closed' ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			'post_name'      => $post_name,
			'post_date'      => $this->post_date( $settings, $last_generated_post_date_time ),
		);

		// Define Post Excerpt, if the Post Type supports it.
		if ( post_type_supports( $settings['type'], 'excerpt' ) ) {
			$post_args['post_excerpt'] = $settings['excerpt'];
		}

		/**
		 * Filters arguments used for creating or updating a Post when running
		 * content generation.
		 *
		 * @since   1.6.1
		 *
		 * @param   array   $post_args  wp_insert_post() / wp_update_post() compatible arguments.
		 * @param   array   $settings   Content Group Settings.
		 */
		$post_args = apply_filters( 'page_generator_pro_generate_post_args', $post_args, $settings );

		/**
		 * Run any actions immediately before an individual Page, Post or Custom Post Type is generated.
		 *
		 * @since   2.4.1
		 *
		 * @param   int     $group_id       Group ID.
		 * @param   array   $settings       Group Settings.
		 * @param   int     $index          Keyword Index.
		 * @param   bool    $test_mode      Test Mode.
		 */
		do_action( 'page_generator_pro_generate_content_before_insert_update_post', $group_id, $settings, $index, $test_mode );

		// Create Page, Post or CPT.
		$post_id = wp_insert_post( $post_args, true );

		// Define return message.
		$log = sprintf(
			/* translators: Post Type Name */
			__( 'Created, as %s with Permalink has not yet been generated by this Group', 'page-generator' ),
			$settings['type']
		);

		// Check Post creation / update worked.
		if ( is_wp_error( $post_id ) ) {
			// Fetch error codes when trying to insert / update the Post.
			$error_codes = $post_id->get_error_codes();

			// Ignore invalid_page_template errors.  wp_update_post() adds the existing page_template
			// parameter to $post_args before passing onto wp_insert_post(); however the template
			// might belong to a Page Builder Template that has / will not register the template with
			// the active Theme.
			// We manually assign _wp_page_template later on in this process, so we can safely ignore
			// this error.
			if ( count( $error_codes ) === 1 && $error_codes[0] === 'invalid_page_template' ) {
				// The Post ID will be the existing Post ID we just updated.
				$post_id = $existing_post_id;
			} else {
				// UTF-8 encode the Title, Excerpt and Content.
				$post_args['post_title']   = mb_convert_encoding( $post_args['post_title'], 'UTF-8', mb_list_encodings() );
				$post_args['post_content'] = mb_convert_encoding( $post_args['post_content'], 'UTF-8', mb_list_encodings() );
				if ( post_type_supports( $settings['type'], 'excerpt' ) ) {
					$post_args['post_excerpt'] = mb_convert_encoding( $post_args['post_excerpt'], 'UTF-8', mb_list_encodings() );
				}

				// Try again.
				$post_id = wp_insert_post( $post_args, true );

				// If Post creation / update still didn't work, bail.
				if ( is_wp_error( $post_id ) ) {
					$post_id->add_data( $post_args, $post_id->get_error_code() );

					return $this->generate_error_return(
						$post_id,
						$group_id,
						0,
						$settings['type'],
						$test_mode,
						$system,
						$keywords_terms
					);
				}
			}
		}

		/**
		 * Run any actions immediately after an individual Page, Post or Custom Post Type is generated, but before
		 * its Page Template, Featured Image, Custom Fields, Post Meta, Geodata or Taxonomy Terms have been assigned.
		 *
		 * @since   2.4.1
		 *
		 * @param   int     $post_id        Post ID.
		 * @param   int     $group_id       Group ID.
		 * @param   array   $settings       Group Settings.
		 * @param   int     $index          Keyword Index.
		 * @param   bool    $test_mode      Test Mode.
		 */
		do_action( 'page_generator_pro_generate_content_after_insert_update_post', $post_id, $group_id, $settings, $index, $test_mode );

		// Store this Group ID and Index in the Post's meta, so we can edit/delete the generated Post(s) in the future.
		update_post_meta( $post_id, '_page_generator_pro_group', $group_id );
		update_post_meta( $post_id, '_page_generator_pro_index', $index );

		// Store Page Template.
		$this->set_page_template( $post_id, $group_id, $settings, $post_args );

		// Store Post Meta (ACF, Yoast, Page Builder data etc) on the Generated Post.
		$this->set_post_meta( $post_id, $group_id, $settings, $post_args );

		// Request that the user review the Plugin, if we're not in Test Mode. Notification displayed later,
		// can be called multiple times and won't re-display the notification if dismissed.
		if ( ! $test_mode ) {
			$this->base->dashboard->request_review();
		}

		// Store current index as the last index generated for this Group, if we're not in test mode.
		if ( ! $test_mode ) {
			$this->base->get_class( 'groups' )->update_last_index_generated( $group_id, $index );
		}

		/**
		 * Run any actions after an individual Page, Post or Custom Post Type is generated
		 * successfully.
		 *
		 * @since   2.4.1
		 *
		 * @param   int     $post_id        Generated Post ID.
		 * @param   int     $group_id       Group ID.
		 * @param   array   $settings       Group Settings.
		 * @param   int     $index          Keyword Index.
		 * @param   bool    $test_mode      Test Mode.
		 */
		do_action( 'page_generator_pro_generate_content_finished', $post_id, $group_id, $settings, $index, $test_mode );

		// Get Generated Post's Date.
		// We don't use $post_args['post_date'] as it might not be set if overwriting dates are disabled.
		$post_date = get_the_date( 'Y-m-d H:i:s', $post_id );

		// Return success data.
		return $this->generate_return( $group_id, $post_id, $settings['type'], true, $log, $start, $test_mode, $system, $keywords_terms, $post_date );

	}

	/**
	 * Resets the Search and Replacement class arrays
	 *
	 * @since   3.0.4
	 */
	private function reset_search_replace_arrays() {

		// Reset search and replacement arrays.
		$this->searches     = array();
		$this->replacements = array();

	}

	/**
	 * For all Keyword tags found in the Group, builds search and replacement class arrays for later use
	 * when recursively iterating through a Group's settings to replace the Keyword tags with their Term counterparts
	 *
	 * @since   2.6.1
	 *
	 * @param   array $required_keywords_full     Required Keywords, Full.
	 * @param   array $keywords_terms             Keywords / Terms Key/Value Pairs.
	 */
	private function build_search_replace_arrays( $required_keywords_full, $keywords_terms ) {

		// Reset search and replacement arrays.
		$this->reset_search_replace_arrays();

		foreach ( $required_keywords_full as $keyword => $keywords_with_modifiers ) {
			// Build search and replacement arrays for this Keyword.
			foreach ( $keywords_with_modifiers as $keyword_with_modifiers ) {
				// If the Keyword isn't truly a Keyword in the database, don't do anything.
				if ( ! isset( $keywords_terms[ $keyword ] ) ) {
					continue;
				}

				// Cast keyword as a string so numeric keywords don't break search/replace.
				$this->build_search_replace_arrays_for_keyword( $keyword_with_modifiers, (string) $keyword, $keywords_terms[ $keyword ] );
			}
		}

	}


	/**
	 * Appends the search and replace arrays for the given Keyword (column name, nth term, transformations) and its applicable Term.
	 *
	 * @since   2.6.1
	 *
	 * @param   string $keyword_with_modifiers     Keyword with Modifiers (search, e.g. keyword(column):3:uppercase_all:url.
	 * @param   string $keyword                    Keyword without Modifiers (e.g. keyword).
	 * @param   string $term                       Term (replacement).
	 */
	private function build_search_replace_arrays_for_keyword( $keyword_with_modifiers, $keyword, $term ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Add Keyword and Term to Search and Replace arrays.
		$this->searches[]     = '{' . $keyword . '}';
		$this->replacements[] = $term;

	}

	/**
	 * Helper method to iterate through each keyword's tags, including any modifiers,
	 * building search and replacement arrays before recursively iterating through the supplied settings,
	 * replacing the keywords and their transformations with the terms.
	 *
	 * @since   1.9.8
	 *
	 * @param   array $settings   Group Settings.
	 * @return  array               Group Settings
	 */
	public function replace_keywords( $settings ) {

		// Iterate through Group Settings, replacing $this->searches (Keywords) with $this->replacements (Terms).
		array_walk_recursive( $settings, array( $this, 'replace_keywords_in_array' ) );

		// Return.
		return $settings;

	}

	/**
	 * Returns an array comprising of all keywords and their term replacements,
	 * including keywords with column names in the format keyword_column.
	 *
	 * Does not include transformations or nth terms
	 *
	 * Used to store basic keyword/term data in the generated Page's Post Meta
	 * if Store Keywords is enabled
	 *
	 * @since   2.2.8
	 *
	 * @param   array $keywords_terms     Keyword / Term Key/Value Pairs.
	 * @return  array                       Keyword / Term Key/Value Pairs
	 */
	private function get_keywords_terms_array_with_columns( $keywords_terms ) {

		$store_keywords = array();

		foreach ( $keywords_terms as $keyword => $term ) {
			// Add keyword/term pair.
			$store_keywords[ $keyword ] = $term;
		}

		// Bail if no keywords.
		if ( count( $store_keywords ) === 0 ) {
			return false;
		}

		return $store_keywords;

	}

	/**
	 * A faster method for fetching all keyword combinations for PHP 5.5+
	 *
	 * @since   1.5.1
	 *
	 * @param   array $input  Multidimensional array of Keyword Names (keys) => Terms (values).
	 * @return  \Generator      Generator
	 */
	private function generate_all_combinations( $input ) {

		// Load class.
		require_once $this->base->plugin->folder . '/includes/admin/cartesian-product.php';

		// Return.
		return new Page_Generator_Pro_Cartesian_Product( $input );

	}

	/**
	 * Recursively goes through the settings array, finding any {keywords}
	 * specified, to build up an array of keywords we need to fetch.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $settings   Settings.
	 * @return  array               Required Keywords
	 */
	public function find_keywords_in_settings( $settings ) {

		// Reset required keywords array.
		$this->required_keywords      = array();
		$this->required_keywords_full = array();

		// Recursively walk through all settings to find all keywords.
		array_walk_recursive( $settings, array( $this, 'find_keywords_in_string' ) );

		// Build results.
		$results = array(
			'required_keywords'      => $this->required_keywords, // Keywords only.
			'required_keywords_full' => $this->required_keywords_full, // Includes columns and modifiers.
		);

		// Reset required keywords array.
		$this->required_keywords      = array();
		$this->required_keywords_full = array();

		return $results;

	}

	/**
	 * For the given array of keywords, only returns keywords with terms, column names and delimiters
	 * where each keywords have terms.
	 *
	 * @since   1.6.5
	 *
	 * @param   array $required_keywords  Required Keywords.
	 * @return  array                       Keywords with Terms, Columns and Delimiters
	 */
	private function get_keywords_terms_columns_delimiters( $required_keywords ) {

		// Define blank array for keywords with terms and keywords with columns.
		$results = array(
			'terms'      => array(),
			'columns'    => array(),
			'delimiters' => array(),
		);

		foreach ( $required_keywords as $key => $keyword ) {
			$result = $this->base->get_class( 'keywords' )->get_by( 'keyword', $keyword );

			// Skip if no results.
			if ( ! is_array( $result ) ) {
				continue;
			}
			if ( count( $result ) === 0 ) {
				continue;
			}

			$results['terms'][ $keyword ] = $result['dataArr'];
		}

		// Return results.
		return $results;

	}

	/**
	 * Returns an array of keyword and term key / value pairs.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $method     Generation Method.
	 * @param   int    $index      Generation Index.
	 * @return  mixed               WP_Error | array
	 */
	private function get_keywords_terms( $method, $index ) {

		switch ( $method ) {

			/**
			 * All
			 * - Generates all possible term combinations across keywords
			 */
			case 'all':
				// Use our Cartesian Product class, which implements a Generator
				// to allow iteration of data without needing to build an array in memory.
				// See: http://php.net/manual/en/language.generators.overview.php.
				$combinations = $this->generate_all_combinations( $this->keywords['terms'] );

				// If the current index exceeds the total number of combinations, we've exhausted all
				// options and don't want to generate any more Pages (otherwise we end up with duplicates).
				if ( $index > ( $combinations->count() - 1 ) ) {
					// If the combinations count is a negative number, we exceeded the floating point for an integer
					// Tell the user to upgrade PHP and/or reduce the number of keyword terms.
					if ( $combinations->count() < 0 ) {
						$message = __( 'The total possible number of unique keyword term combinations exceeds the maximum number value that can be stored by your version of PHP.  Please consider upgrading to a 64 bit PHP 7.0+ build and/or reducing the number of keyword terms that you are using.', 'page-generator' );
					} else {
						$message = __( 'All possible keyword term combinations have been generated. Generating more Pages/Posts would result in duplicate content.', 'page-generator' );
					}

					return new WP_Error( 'page_generator_pro_generate_content_keywords_exhausted', $message );
				}

				// Iterate through the combinations until we reach the one matching the index.
				foreach ( $combinations as $c_index => $combination ) {
					// Skip if not the index we want.
					if ( $c_index !== $index ) {
						continue;
					}

					// Define the keyword => term key/value pairs to use based on the current index.
					$keywords_terms = $combination;
					break;
				}
				break;

			/**
			 * Sequential
			 * - Generates term combinations across keywords matched by index
			 */
			case 'sequential':
				$keywords_terms = array();
				foreach ( $this->keywords['terms'] as $keyword => $terms ) {
					// Use modulo to get the term index for this keyword.
					$term_index = ( $index % count( $terms ) );

					// Build the keyword => term key/value pairs.
					$keywords_terms[ $keyword ] = $terms[ $term_index ];
				}
				break;

			/**
			 * Random
			 * - Gets a random term for each keyword
			 */
			case 'random':
				$keywords_terms = array();
				foreach ( $this->keywords['terms'] as $keyword => $terms ) {
					// If only one term exists, use that.
					if ( count( $terms ) === 1 ) {
						$term_index = 0;
					} else {
						$term_index = wp_rand( 0, ( count( $terms ) - 1 ) );
					}

					// Build the keyword => term key/value pairs.
					$keywords_terms[ $keyword ] = $terms[ $term_index ];
				}
				break;

			/**
			 * Invalid method
			 */
			default:
				return new WP_Error( 'page_generator_pro_generate_get_keywords_terms_invalid_method', __( 'The method given is invalid.', 'page-generator' ) );
		}

		// Cleanup the terms.
		foreach ( $keywords_terms as $key => $term ) {
			$keywords_terms[ $key ] = trim( html_entity_decode( $term ) );
		}

		/**
		 * Returns an array of keyword and term key / value pairs, before any
		 * search or replacement arrays are built.
		 *
		 * @since   2.7.5
		 *
		 * @param   array   $keywords_terms     Keywords and Terms for this Page Generation.
		 * @param   string  $method             Generation Method.
		 * @param   int     $index              Generation Index.
		 */
		$keywords_terms = apply_filters( 'page_generator_pro_generate_get_keywords_terms', $keywords_terms, $method, $index );

		// Return.
		return $keywords_terms;

	}

	/**
	 * Performs a search on the given string to find any {keywords}
	 *
	 * @since   1.2.0
	 *
	 * @param   string $content    Array Value (string to search).
	 * @param   string $key        Array Key.
	 */
	private function find_keywords_in_string( $content, $key ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// If $content is an object, iterate this call.
		if ( is_object( $content ) ) {
			return array_walk_recursive( $content, array( $this, 'find_keywords_in_string' ) );
		}

		// Bail if content is null.
		if ( is_null( $content ) ) { // @phpstan-ignore-line
			return;
		}

		/**
		 * Get Keywords in this string.  Covers:
		 * - Alphanumeric and accented keyword names, with hyphens and underscores
		 * - Alphanumeric and accented keyword column names, with hyphens and underscores
		 * - Keyword modifiers
		 * - Keyword modifier arguments
		 *
		 * For example:
		 * {keyword}
		 * {keyword_ü}
		 * {keyword-keyword}
		 * {keyword_keyword}
		 * {keyword:modifier}
		 * {keyword(column)}, {keyword(column_name)}, {keyword(column-name)}
		 * {keyword(column_name):modifier}, {keyword(column-name):modifier...:modifier}
		 * {keyword(column_name):modifier[args]}
		 * {keyword(column_name):modifier[arg1,argN]}
		 *
		 * Previous method "|{(.+?)}|" would include spintax and fail to extract keywords
		 * within JSON e.g. Gutenberg Block JSON strings that contain a Keyword.
		 */
		preg_match_all( '/{([\p{L}0-9_\-:,()\\[\\]]+?)}/u', $content, $matches );

		// Bail if no matches are found.
		if ( ! is_array( $matches ) ) {
			return;
		}
		if ( count( $matches[1] ) === 0 ) {
			return;
		}

		// Iterate through matches, adding them to the required keywords array.
		foreach ( $matches[1] as $m_key => $keyword ) {
			$this->add_keyword_to_required_keywords( $keyword );
		}

	}

	/**
	 * Adds the given keyword to the required keywords array, if it doesn't already exist
	 *
	 * @since   2.8.8
	 *
	 * @param   string $keyword    Possible Keyword.
	 */
	private function add_keyword_to_required_keywords( $keyword ) {

		// Remove additional leading/trailing curly braces.
		$keyword = str_replace( '{', '', $keyword );
		$keyword = str_replace( '}', '', $keyword );

		// Lowercase keyword, to avoid duplicates e.g. {City} and {city}.
		$keyword = strtolower( $keyword );

		// Fetch just the Keyword Name.
		$keyword_name = $this->extract_keyword_name_from_keyword( $keyword );

		// If the Keyword Name is a spin that's just text (i.e it's not actually a Keyword), skip it.
		if ( ! in_array( $keyword_name, $this->base->get_class( 'keywords' )->get_keywords_names(), true ) ) {
			return;
		}

		// If the Keyword Name is not in our required_keywords array, add it now.
		if ( ! in_array( $keyword_name, $this->required_keywords, true ) ) {
			$this->required_keywords[ $keyword_name ] = $keyword_name;
		}

		// If the Keyword (Full) is not in our required_keywords_full array, add it now.
		if ( ! isset( $this->required_keywords_full[ $keyword_name ] ) ) {
			$this->required_keywords_full[ $keyword_name ] = array();
		}
		if ( ! in_array( $keyword, $this->required_keywords_full[ $keyword_name ], true ) ) {
			$this->required_keywords_full[ $keyword_name ][] = $keyword;
		}

	}

	/**
	 * Returns just the keyword name, excluding any columns, nth terms and transformations
	 *
	 * @since   2.6.1
	 *
	 * @param   string $keyword    Keyword.
	 * @return  string              Keyword Name excluding any columns, nth terms and transformations
	 */
	private function extract_keyword_name_from_keyword( $keyword ) {

		if ( strpos( $keyword, ':' ) !== false ) {
			$keyword_parts = explode( ':', $keyword );
			$keyword       = trim( $keyword_parts[0] );
		}

		$keyword = preg_replace( '/\(.*?\)/', '', $keyword );

		return $keyword;

	}

	/**
	 * Callback for array_walk_recursive, which finds $this->searches, replacing with
	 * $this->replacements in $item.
	 *
	 * @since   1.3.1
	 *
	 * @param   mixed  $item   Item (array, object, string).
	 * @param   string $key    Key.
	 */
	private function replace_keywords_in_array( &$item, $key ) {

		// If the settings key's value is an array, walk through it recursively to search/replace
		// Otherwise do a standard search/replace on the string.
		if ( is_array( $item ) ) {
			// Array.
			array_walk_recursive( $item, array( $this, 'replace_keywords_in_array' ) );
		} elseif ( is_object( $item ) ) {
			// Object.
			array_walk_recursive( $item, array( $this, 'replace_keywords_in_array' ) );
		} elseif ( is_string( $item ) ) {
			// If here, we have a string.
			// Perform keyword replacement now.

			// If replacements contain an array, we're using the :random_different Keyword Transformation
			// and therefore need to perform a slower search/replace to iterate through every occurance of
			// the same transformation.
			if ( $this->replacements_contain_array ) {
				foreach ( $this->searches as $index => $search ) {
					// Standard search/replace.
					if ( ! is_array( $this->replacements[ $index ] ) ) {
						$item = str_ireplace( $search, $this->replacements[ $index ], $item );
						continue;
					}

					// Pluck a value at random from the array of replacement Terms for the given search, doing this
					// every time we find the Keyword, so we get truly random Terms each time in a single string.
					$pos = stripos( $item, $search );
					while ( $pos !== false ) {
						$item = substr_replace( $item, $this->replacements[ $index ][ wp_rand( 0, ( count( $this->replacements[ $index ] ) - 1 ) ) ], $pos, strlen( $search ) );

						// Search for next occurrence of this Keyword  .
						$pos = stripos( $item, $search, $pos + 1 );
					}
				}
			} else {
				// Replace all searches with all replacements.
				$item = str_ireplace( $this->searches, $this->replacements, $item );
			}

			/**
			 * Perform any other keyword replacements or string processing.
			 *
			 * @since   1.9.8
			 *
			 * @param   string  $item   Group Setting String (this can be Post Meta, Custom Fields, Permalink, Title, Content etc).
			 * @param   string  $key    Group Setting Key.
			 */
			$item = apply_filters( 'page_generator_pro_generate_replace_keywords_in_array', $item, $key );
		}

	}

	/**
	 * Assigns any Attachments to the given Post ID that have the specified Group ID and Index
	 *
	 * @since   2.4.1
	 *
	 * @param   int $post_id    Generated Post ID.
	 * @param   int $group_id   Group ID.
	 * @param   int $index      Generation Index.
	 * @return  mixed               WP_Error | bool
	 */
	private function assign_attachments_to_post_id( $post_id, $group_id, $index ) {

		// Build query.
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_page_generator_pro_group',
					'value' => absint( $group_id ),
				),
				array(
					'key'   => '_page_generator_pro_index',
					'value' => absint( $index ),
				),
			),
			'fields'         => 'ids',
		);

		// Get all Attachments belonging to the given Group ID and Index.
		$attachments = new WP_Query( $args );

		// If no Attachments found, return false, as there's nothing to assign.
		if ( count( $attachments->posts ) === 0 ) {
			return false;
		}

		// For each Attachment, assign it to the Post.
		foreach ( $attachments->posts as $attachment_id ) {
			$result = wp_update_post(
				array(
					'ID'          => $attachment_id,
					'post_parent' => $post_id,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Done.
		return true;

	}

	/**
	 * Returns a wp_insert_post() and wp_update_post() compatible date and time to publish/schedule the generated post,
	 * based on the Group's settings.
	 *
	 * @since   3.1.8
	 *
	 * @param   array $settings                       Group Settings.
	 * @param   mixed $last_generated_post_date_time  Last Generated Post's Date and Time.
	 */
	public function post_date( $settings, $last_generated_post_date_time = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		return date_format( date_create( date_i18n( 'Y-m-d H:i:s' ) ), 'Y-m-d H:i:s' );

	}

	/**
	 * Copies Attributes > Template to the Generated Post ID, honoring
	 * the Overwrite setting.
	 *
	 * @since   2.9.0
	 *
	 * @param   int   $post_id            Generated Post ID.
	 * @param   int   $group_id           Group ID.
	 * @param   array $settings           Group Settings.
	 * @param   array $post_args          wp_insert_post() / wp_update_post() arguments.
	 * @return  bool                        Updated Page Template on Generated Post ID
	 */
	private function set_page_template( $post_id, $group_id, $settings, $post_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if the target Post Type doesn't support templates.
		if ( ! $this->base->get_class( 'common' )->post_type_supports( $settings['type'], 'templates' ) ) {
			return false;
		}

		// Bail if we're overwriting an existing Post and don't want to overwrite the Template.
		if ( isset( $post_args['ID'] ) && ! array_key_exists( 'attributes', $settings['overwrite_sections'] ) ) {
			return false;
		}

		// Backward compat for Free.
		if ( ! empty( $settings['pageTemplate'] ) && ! is_array( $settings['pageTemplate'] ) ) {
			update_post_meta( $post_id, '_wp_page_template', $settings['pageTemplate'] );
		}
		if ( ! empty( $settings['pageTemplate'][ $settings['type'] ] ) ) {
			update_post_meta( $post_id, '_wp_page_template', $settings['pageTemplate'][ $settings['type'] ] );
		}

		/**
		 * Action to perform any further steps with the Content Group's Page Template
		 * after the Page Template has been copied from the Content Group to the Generated Content.
		 *
		 * @since   2.9.7
		 *
		 * @param   int     $post_id        Generated Page ID.
		 * @param   int     $group_id       Group ID.
		 * @param   array   $settings       Group Settings.
		 * @param   array   $post_args      wp_insert_post() / wp_update_post() arguments.
		 */
		do_action( 'page_generator_pro_generate_set_page_template', $post_id, $settings, $post_args );

		return true;

	}

	/**
	 * Copies the Content Group's Post Meta to the Generated Post ID,
	 * including Page Builder / ACF data.
	 *
	 * @since   2.3.5
	 *
	 * @param   int   $post_id    Generated Post ID.
	 * @param   int   $group_id   Group ID.
	 * @param   array $settings   Group Settings.
	 * @param   array $post_args  wp_insert_post() / wp_update_post() arguments.
	 * @return  bool                Updated Post Meta on Generated Post ID
	 */
	private function set_post_meta( $post_id, $group_id, $settings, $post_args ) {

		// Bail if no Post Meta to copy to the generated Post.
		if ( ! isset( $settings['post_meta'] ) ) {
			return false;
		}

		// Define the metadata to ignore.
		$ignored_keys = array(
			'_wp_page_template',
		);

		/**
		 * Defines Post Meta Keys in a Content Group to ignore and not copy to generated Posts / Groups.
		 *
		 * @since   2.6.1
		 *
		 * @param   array   $ignored_keys   Ignored Keys (preg_match() compatible regex expressions are supported).
		 * @param   int     $post_id        Generated Post ID.
		 * @param   array   $settings       Group Settings.
		 * @param   array   $post_args      wp_insert_post() / wp_update_post() arguments.
		 */
		$ignored_keys = apply_filters( 'page_generator_pro_generate_set_post_meta_ignored_keys', $ignored_keys, $post_id, $settings, $post_args );

		// Iterate through Post Meta.
		foreach ( $settings['post_meta'] as $meta_key => $meta_value ) {

			// Skip ignored keys.
			if ( in_array( $meta_key, $ignored_keys, true ) ) {
				continue;
			}

			// Iterate through the ignored keys using preg_match(), so we can support
			// regular expressions.
			foreach ( $ignored_keys as $ignored_key ) {
				// Don't evaluate if not a regular expression.
				if ( strpos( $ignored_key, '/' ) === false ) {
					continue;
				}

				// Don't copy this Meta Key/Value if it's set to be ignored.
				if ( preg_match( $ignored_key, $meta_key ) ) {
					continue 2;
				}
			}

			/**
			 * Filters the Group Metadata for the given Key and Value, immediately before it's
			 * saved to the Generated Page.
			 *
			 * @since   2.6.1
			 *
			 * @param   mixed   $meta_value Meta Value.
			 * @param   int     $post_id    Generated Post ID.
			 * @param   int     $group_id   Group ID.
			 * @param   array   $settings   Group Settings.
			 * @param   array   $post_args  wp_insert_post() / wp_update_post() arguments.
			 */
			$meta_value = apply_filters( 'page_generator_pro_generate_set_post_meta_' . $meta_key, $meta_value, $post_id, $group_id, $settings, $post_args );

			// Update Generated Page's Meta Value.
			update_post_meta( $post_id, $meta_key, $meta_value );

		}

		/**
		 * Action to perform any further steps with the Content Group's Post Meta,
		 * after all Post Meta has been copied  from the Content Group to the Generated Content.
		 *
		 * @since   2.9.7
		 *
		 * @param   int     $post_id        Generated Page ID.
		 * @param   int     $group_id       Group ID.
		 * @param   array   $post_meta      Group Post Meta.
		 * @param   array   $settings       Group Settings.
		 * @param   array   $post_args      wp_insert_post() / wp_update_post() arguments.
		 */
		do_action( 'page_generator_pro_generate_set_post_meta', $post_id, $group_id, $settings['post_meta'], $settings, $post_args );

		return true;

	}

	/**
	 * Main function to trash previously generated Contents
	 * for the given Group ID
	 *
	 * @since   1.2.3
	 *
	 * @param   int   $group_id           Group ID.
	 * @param   int   $limit              Number of Generated Posts to delete (-1 = all).
	 * @param   mixed $exclude_post_ids   Exclude Post IDs from deletion.
	 * @return  mixed                       WP_Error | Success
	 */
	public function trash_content( $group_id, $limit = -1, $exclude_post_ids = false ) {

		// Get all Post IDs generated by this Group.
		$post_ids = $this->get_generated_content_post_ids( $group_id, $limit, $exclude_post_ids );

		// Bail if an error occured.
		if ( is_wp_error( $post_ids ) ) {
			return $post_ids;
		}

		// Delete Posts by their IDs.
		foreach ( $post_ids as $post_id ) {
			$result = wp_trash_post( $post_id );
			if ( ! $result ) {
				return new WP_Error(
					'page_generator_pro_generate_trash_content',
					sprintf(
						/* translators: Post ID */
						__( 'Unable to trash generated content with ID = %s', 'page-generator' ),
						$post_id
					)
				);
			}
		}

		/**
		 * Run any actions after all generated content for a given Content Group has been trashd.
		 *
		 * @since   3.4.2
		 *
		 * @param   int     $group_id       Group ID.
		 * @param   int     $post_ids       Generated Post IDs that were deleted.
		 */
		do_action( 'page_generator_pro_generate_trash_content_finished', $group_id, $post_ids );

		// Done.
		return true;

	}

	/**
	 * Main function to delete previously generated Contents
	 * for the given Group ID
	 *
	 * @since   1.2.3
	 *
	 * @param   int   $group_id           Group ID.
	 * @param   int   $limit              Number of Generated Posts to delete (-1 = all).
	 * @param   mixed $exclude_post_ids   Exclude Post IDs from deletion.
	 * @return  mixed                       WP_Error | Success
	 */
	public function delete_content( $group_id, $limit = -1, $exclude_post_ids = false ) {

		// Get all Post IDs generated by this Group.
		$post_ids = $this->get_generated_content_post_ids( $group_id, $limit, $exclude_post_ids );

		// Bail if an error occured.
		if ( is_wp_error( $post_ids ) ) {
			return $post_ids;
		}

		// Delete Posts.
		foreach ( $post_ids as $post_id ) {
			$result = wp_delete_post( $post_id, true );
			if ( ! $result ) {
				return new WP_Error(
					'page_generator_pro_generate_delete_content',
					sprintf(
						/* translators: Post ID */
						__( 'Unable to delete generated content with ID = %s', 'page-generator' ),
						$post_id
					)
				);
			}
		}

		/**
		 * Run any actions after all generated content for a given Content Group has been deleted.
		 *
		 * @since   3.4.2
		 *
		 * @param   int     $group_id       Group ID.
		 * @param   int     $post_ids       Generated Post IDs that were deleted.
		 */
		do_action( 'page_generator_pro_generate_delete_content_finished', $group_id, $post_ids );

		return true;

	}

	/**
	 * Returns all Post IDs generated by the given Group ID
	 *
	 * @since   1.9.1
	 *
	 * @param   int   $group_id           Group ID.
	 * @param   int   $limit              Number of Post IDs to return (-1 = no limit).
	 * @param   mixed $exclude_post_ids   Exclude Post IDs.
	 * @return  mixed                       WP_Error | array
	 */
	public function get_generated_content_post_ids( $group_id, $limit = -1, $exclude_post_ids = false ) {

		// Fetch valid Post Statuses that can be used when generating content.
		$statuses = array_keys( $this->base->get_class( 'common' )->get_post_statuses() );

		$params = array(
			'post_type'              => 'any',
			'post_status'            => $statuses,
			'posts_per_page'         => $limit,
			'meta_query'             => array(
				array(
					'key'   => '_page_generator_pro_group',
					'value' => absint( $group_id ),
				),
			),
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		// Add excluded Post IDs, if defined.
		if ( is_array( $exclude_post_ids ) ) {
			// Cast to integers.
			foreach ( $exclude_post_ids as $index => $post_id ) {
				$exclude_post_ids[ $index ] = absint( $post_id );
			}

			// Add to query parameters.
			$params['post__not_in'] = $exclude_post_ids;
		}

		// Get all Posts.
		$posts = new WP_Query( $params );

		// If no Posts found, return an error.
		if ( ! $posts->posts || count( $posts->posts ) === 0 ) {
			return new WP_Error( 'page_generator_pro_generate_get_generated_content_post_ids', __( 'No content has been generated by this group.', 'page-generator' ) );
		}

		// Return Post IDs.
		return $posts->posts;

	}

	/**
	 * Returns an array of data relating to the successfully generated Post or Term,
	 * logging the result if logging is enabled.
	 *
	 * @since   2.1.8
	 *
	 * @param   int    $group_id                       Group ID.
	 * @param   int    $post_or_term_id                Post or Term ID.
	 * @param   string $post_type_or_taxonomy          Post Type or Taxonomy.
	 * @param   bool   $generated                      Post Generated (false = skipped).
	 * @param   string $message                        Message to return (created, updated, skipped etc).
	 * @param   int    $start                          Start Time.
	 * @param   bool   $test_mode                      Test Mode.
	 * @param   string $system                         System (browser|cron|cli).
	 * @param   array  $keywords_terms                 Keywords / Terms Key / Value array used.
	 * @param   mixed  $last_generated_post_date_time  Last Generated Post's Date and Time.
	 * @return                                          Success Data
	 */
	private function generate_return( $group_id, $post_or_term_id, $post_type_or_taxonomy, $generated, $message, $start, $test_mode, $system, $keywords_terms, $last_generated_post_date_time = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$url = get_permalink( $post_or_term_id );
		if ( $test_mode ) {
			$url = add_query_arg(
				array(
					'preview' => 'true',
				),
				get_permalink( $post_or_term_id )
			);
		} else {
			$url = get_permalink( $post_or_term_id );
		}

		// Performance debugging.
		$end = ( function_exists( 'hrtime' ) ? hrtime( true ) : microtime( true ) );

		// Strip HTML from Keywords Terms, to avoid issues with Generate via Browser log output.
		foreach ( $keywords_terms as $keyword => $term ) {
			$keywords_terms[ $keyword ] = wp_strip_all_tags( $term );
		}

		// Build result array.
		$result = array(
			// Item.
			'post_id'                       => $post_or_term_id,
			'url'                           => $url,
			'type'                          => ( taxonomy_exists( $post_type_or_taxonomy ) ? 'term' : 'content' ),
			'system'                        => $system,
			'test_mode'                     => $test_mode,
			'generated'                     => $generated,
			'result'                        => 'success',
			'keywords_terms'                => $keywords_terms,
			'last_generated_post_date_time' => $last_generated_post_date_time,
			'message'                       => $message,

			// Performance data.
			'start'                         => $start,
			'end'                           => $end,
			'duration'                      => ( function_exists( 'hrtime' ) ? round( ( ( $end - $start ) / 1e+9 ), 3 ) : round( ( $end - $start ), 2 ) ),
			'memory_usage'                  => round( memory_get_usage() / 1024 / 1024 ),
			'memory_peak_usage'             => round( memory_get_peak_usage() / 1024 / 1024 ),
		);

		// Return.
		return $result;

	}

	/**
	 * Returns the supplied WP_Error, logging the result if logging is enabled
	 *
	 * @since   2.8.0
	 *
	 * @param   WP_Error $error                  WP_Error.
	 * @param   int      $group_id               Group ID.
	 * @param   int      $post_or_term_id        Post or Term ID.
	 * @param   string   $post_type_or_taxonomy  Post Type or Taxonomy.
	 * @param   bool     $test_mode              Test Mode.
	 * @param   string   $system                 System (browser|cron|cli).
	 * @param   array    $keywords_terms         Keywords and Terms.
	 * @return  WP_Error                            Error
	 */
	private function generate_error_return( $error, $group_id, $post_or_term_id, $post_type_or_taxonomy, $test_mode, $system, $keywords_terms ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$url = '';
		if ( $post_or_term_id ) {
			if ( $test_mode ) {
				$url = get_bloginfo( 'url' ) . '?p=' . $post_or_term_id . '&preview=true';
			} else {
				$url = get_permalink( $post_or_term_id );
			}
		}

		// Build result array.
		$result = array(
			// Item.
			'post_id'           => $post_or_term_id,
			'url'               => $url,
			'type'              => ( taxonomy_exists( $post_type_or_taxonomy ) ? 'term' : 'content' ),
			'system'            => $system,
			'test_mode'         => $test_mode,
			'generated'         => 0,
			'result'            => 'error',
			'keywords_terms'    => $keywords_terms,
			'message'           => $error->get_error_message(),

			// Performance data.
			'start'             => 0,
			'end'               => 0,
			'duration'          => 0,
			'memory_usage'      => round( memory_get_usage() / 1024 / 1024 ),
			'memory_peak_usage' => round( memory_get_peak_usage() / 1024 / 1024 ),
		);

		// Return original WP_Error.
		return $error;

	}

}
