<?php
/**
 * AJAX Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Registers AJAX endpoints for various features, such as Generation/Trash/Delete Content.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.0.0
 */
class Page_Generator_Pro_AJAX {

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
	 * @since   1.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Generate: Authors.
		add_action( 'wp_ajax_page_generator_pro_search_authors', array( $this, 'search_authors' ) );

		// Generate: Content.
		add_action( 'wp_ajax_page_generator_pro_generate_content', array( $this, 'generate_content' ) );
		add_action( 'wp_ajax_page_generator_pro_generate_content_trash_generated_content', array( $this, 'trash_generated_content' ) );
		add_action( 'wp_ajax_page_generator_pro_generate_content_delete_generated_content', array( $this, 'delete_generated_content' ) );
		add_action( 'wp_ajax_page_generator_pro_generate_content_after', array( $this, 'after_generated_content' ) );

	}

	/**
	 * Returns the maximum number of generated items to delete in a single AJAX
	 * request, to prevent timeouts or server errors.
	 *
	 * @since   2.7.6
	 *
	 * @return  int     Limit
	 */
	public function get_trash_delete_per_request_item_limit() {

		$limit = 100;

		/**
		 * The maximum number of generated items to trash or delete in a single AJAX
		 * request, to prevent timeouts or server errors.
		 *
		 * If there are more items to delete than the limit specified, the Plugin
		 * will send synchronous requests until all items are deleted.
		 *
		 * @since   2.7.6
		 */
		$limit = apply_filters( 'page_generator_pro_ajax_delete_generated_count_number_of_items', $limit );

		// Return.
		return absint( $limit );

	}

	/**
	 * Searches for Authors for the given freeform text
	 *
	 * @since   1.8.3
	 */
	public function search_authors() {

		// Verify nonce.
		check_ajax_referer( 'search_authors', 'nonce' );

		// Get vars.
		$query = sanitize_text_field( $_REQUEST['query'] );

		// Get results.
		$users = new WP_User_Query(
			array(
				'search' => '*' . $query . '*',
			)
		);

		// If an error occured, bail.
		if ( is_wp_error( $users ) ) {
			return wp_send_json_error( $users->get_error_message() );
		}

		// Build array.
		$users_array = array();
		$results     = $users->get_results();
		if ( ! empty( $results ) ) {
			foreach ( $results as $user ) {
				$users_array[] = array(
					'id'         => $user->ID,
					'user_login' => $user->user_login,
				);
			}
		}

		// Done.
		wp_send_json_success( $users_array );

	}

	/**
	 * Generates a Page, Post or CPT
	 *
	 * @since   1.6.1
	 */
	public function generate_content() {

		// Validate.
		$group = $this->generate_validation( 'page-generator-pro-generate-browser' );

		// Run.
		$result = $this->base->get_class( 'generate' )->generate_content(
			$group['group_id'],
			$group['current_index'],
			$group['test_mode'],
			'browser',
			$group['last_generated_post_date_time']
		);

		// Return.
		$this->generate_return( $result );

	}

	/**
	 * Trashes Generated Content
	 *
	 * @since   1.9.1
	 */
	public function trash_generated_content() {

		// Validate.
		$group = $this->generate_validation( 'page-generator-pro-trash-generated-content' );

		// Run.
		$result = $this->base->get_class( 'generate' )->trash_content( $group['group_id'], $this->get_trash_delete_per_request_item_limit() );
		if ( is_wp_error( $result ) ) {
			$this->generate_return( $result );
			die();
		}

		// Determine if there are more Posts in this Content Group that need deleting.
		$remaining_posts = $this->base->get_class( 'generate' )->get_generated_content_post_ids( $group['group_id'] );
		if ( is_wp_error( $remaining_posts ) ) {
			// Error will say there are no more Generated Posts to delete for this Content Group.
			$result = array(
				'has_more' => false,
			);
		} else {
			$result = array(
				'has_more' => true,
			);
		}

		// Return.
		$this->generate_return( $result );

	}

	/**
	 * Deletes Generated Content
	 *
	 * @since   1.8.4
	 */
	public function delete_generated_content() {

		// Validate.
		$group = $this->generate_validation( 'page-generator-pro-delete-generated-content' );

		// Run.
		$result = $this->base->get_class( 'generate' )->delete_content( $group['group_id'], $this->get_trash_delete_per_request_item_limit() );
		if ( is_wp_error( $result ) ) {
			$this->generate_return( $result );
			die();
		}

		// Determine if there are more Posts in this Content Group that need deleting.
		$remaining_posts = $this->base->get_class( 'generate' )->get_generated_content_post_ids( $group['group_id'] );
		if ( is_wp_error( $remaining_posts ) ) {
			// Error will say there are no more Generated Posts to delete for this Content Group.
			$result = array(
				'has_more' => false,
			);

			// Reset the Last Index Generated.
			$this->base->get_class( 'groups' )->update_last_index_generated( $group['group_id'], 0 );
		} else {
			$result = array(
				'has_more' => true,
			);
		}

		// Return.
		$this->generate_return( $result );

	}

	/**
	 * Removes the generating flag on the Group, as Generation has finished.
	 *
	 * @since   1.9.9
	 */
	public function after_generated_content() {

		// Validate.
		$group = $this->generate_validation();

		/**
		 * Runs any actions after Generate Content has finished.
		 *
		 * @since   3.0.7
		 *
		 * @param   int     $group_id   Group ID.
		 * @param   bool    $test_mode  Test Mode.
		 * @param   string  $system     System.
		 */
		do_action( 'page_generator_pro_generate_content_after', $group['group_id'], false, 'browser' );

		// Run.
		$result = $this->base->get_class( 'groups' )->stop_generation( $group['group_id'] );

		// Return.
		$this->generate_return( $result );

	}

	/**
	 * Runs validation when AJAX calls are made to generate content or terms,
	 * returning the Group ID and Current Index.
	 *
	 * @since   1.6.1
	 *
	 * @param   mixed $action     Nonce Action.
	 * @return  array               Group ID and Current Index
	 */
	private function generate_validation( $action = false ) {

		// Validate nonce.
		if ( $action ) {
			check_ajax_referer( $action, 'nonce' );
		}

		// Sanitize inputs.
		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( __( 'No group ID was specified.', 'page-generator' ) );
			die();
		}

		return array(
			'group_id'                      => absint( $_POST['id'] ),
			'current_index'                 => ( isset( $_POST['current_index'] ) ? absint( $_POST['current_index'] ) : 0 ),
			'last_generated_post_date_time' => ( isset( $_POST['last_generated_post_date_time'] ) ? sanitize_text_field( $_POST['last_generated_post_date_time'] ) : false ),
			'test_mode'                     => ( isset( $_POST['test_mode'] ) ? true : false ),
		);

	}

	/**
	 * Returns the generation result as a JSON error or success
	 *
	 * @since   1.6.1
	 *
	 * @param   mixed $result     WP_Error | array.
	 */
	private function generate_return( $result ) {

		// Return error or success JSON.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_code() . ': ' . $result->get_error_message() );
		}

		// If here, run routine worked.
		wp_send_json_success( $result );

	}

}
