<?php
/**
 * Content Groups UI Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Handles Content Groups Post Type's UI for creating
 * and editing Content Groups.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 2.0.2
 */
class Page_Generator_Pro_Groups_UI {

	/**
	 * Holds the base class object.
	 *
	 * @since   2.0.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds keywords for the Group we're editing
	 *
	 * @since   2.0.2
	 *
	 * @var     mixed (bool|array)
	 */
	public $keywords = false;

	/**
	 * Holds settings for the Group we're editing
	 *
	 * @since   2.0.2
	 *
	 * @var     array
	 */
	public $settings = array();

	/**
	 * Constructor.
	 *
	 * @since   2.0.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Modify Post Messages.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		// Before Title.
		add_action( 'edit_form_top', array( $this, 'output_keywords_dropdown_before_title' ) );

		// Meta Boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Save Group.
		add_action( 'save_post', array( $this, 'save_post' ) );

		// Page Generator.
		if ( class_exists( 'Page_Generator' ) ) {
			add_action( 'init', array( $this, 'limit_admin' ) );
			add_filter( 'wp_insert_post_empty_content', array( $this, 'limit_xml_rpc' ), 10, 2 );
		}

	}

	/**
	 * Defines admin notices for the Post Type.
	 *
	 * This also removes the 'View post' link on the message, which would result
	 * in an error on the frontend.
	 *
	 * @since   2.0.2
	 *
	 * @param   array $messages   Messages.
	 * @return  array               Messages
	 */
	public function post_updated_messages( $messages ) {

		$messages[ $this->base->get_class( 'post_type' )->post_type_name ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Group updated.', 'page-generator' ),
			2  => __( 'Custom field updated.', 'page-generator' ),
			3  => __( 'Custom field deleted.', 'page-generator' ),
			4  => __( 'Group updated.', 'page-generator' ),
			/* translators: Post revision title */
			5  => ( isset( $_GET['revision'] ) ? sprintf( __( 'Group restored to revision from %s.', 'page-generator' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false ), // phpcs:ignore WordPress.Security.NonceVerification
			6  => __( 'Group saved.', 'page-generator' ),
			7  => __( 'Group saved.', 'page-generator' ),
			8  => __( 'Group submitted.', 'page-generator' ),
			9  => __( 'Group scheduled.', 'page-generator' ),
			10 => __( 'Group draft updated.', 'page-generator' ),
		);

		return $messages;

	}

	/**
	 * Outputs the Keywords Dropdown before the Title field
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_keywords_dropdown_before_title( $post ) {

		// Don't do anything if we're not on this Plugin's CPT.
		if ( get_post_type( $post ) !== $this->base->get_class( 'post_type' )->post_type_name ) {
			return;
		}

		// Get all available keywords.
		if ( ! $this->keywords ) {
			$this->keywords = $this->base->get_class( 'keywords' )->get_keywords_and_columns();
		}

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-title-keywords.php';

	}

	/**
	 * Registers meta boxes for the Generate Custom Post Type
	 *
	 * @since   2.0.2
	 */
	public function add_meta_boxes() {

		// Remove some metaboxes that we don't need, to improve the UI.
		$this->remove_meta_boxes();

		// Determine whether we're using the Gutenberg Editor.
		// The use of $current_screen is in cases where is_gutenberg_page() sometimes wrongly returns false.
		global $current_screen;
		$is_gutenberg_page = ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ? true : false );
		if ( ! $is_gutenberg_page && method_exists( $current_screen, 'is_block_editor' ) ) {
			$is_gutenberg_page = $current_screen->is_block_editor();
		}

		// Permalink.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-permalink',
			__( 'Permalink', 'page-generator' ),
			array( $this, 'output_meta_box_permalink' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'normal'
		);

		// Author.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-author',
			__( 'Author', 'page-generator' ),
			array( $this, 'output_meta_box_author' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'normal'
		);

		// Discussion.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-discussion',
			__( 'Discussion', 'page-generator' ),
			array( $this, 'output_meta_box_discussion' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'normal'
		);

		// Upgrade.
		if ( class_exists( 'Page_Generator' ) ) {
			add_meta_box(
				$this->base->get_class( 'post_type' )->post_type_name . '-upgrade',
				__( 'Upgrade', 'page-generator' ),
				array( $this, 'output_meta_box_upgrade' ),
				$this->base->get_class( 'post_type' )->post_type_name,
				'normal'
			);
		}

		/**
		 * Sidebar
		 */

		// Actions Top.
		if ( ! $is_gutenberg_page ) {
			add_meta_box(
				$this->base->get_class( 'post_type' )->post_type_name . '-actions',
				__( 'Actions', 'page-generator' ),
				array( $this, 'output_meta_box_actions_top' ),
				$this->base->get_class( 'post_type' )->post_type_name,
				'side',
				'high'
			);
		}

		// Publish.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-publish',
			__( 'Publish', 'page-generator' ),
			array( $this, 'output_meta_box_publish' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'side'
		);

		// Generation.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-generation',
			__( 'Generation', 'page-generator' ),
			array( $this, 'output_meta_box_generation' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'side'
		);

		// Template.
		add_meta_box(
			$this->base->get_class( 'post_type' )->post_type_name . '-template',
			__( 'Template', 'page-generator' ),
			array( $this, 'output_meta_box_template' ),
			$this->base->get_class( 'post_type' )->post_type_name,
			'side'
		);

		// Actions Bottom.
		if ( ! $is_gutenberg_page ) {
			add_meta_box(
				$this->base->get_class( 'post_type' )->post_type_name . '-actions-bottom',
				__( 'Actions', 'page-generator' ),
				array( $this, 'output_meta_box_actions_bottom' ),
				$this->base->get_class( 'post_type' )->post_type_name,
				'side',
				'low'
			);
		} else {
			add_meta_box(
				$this->base->get_class( 'post_type' )->post_type_name . '-actions-gutenberg-bottom',
				__( 'Actions', 'page-generator' ),
				array( $this, 'output_meta_box_actions_gutenberg' ),
				$this->base->get_class( 'post_type' )->post_type_name,
				'side'
			);
		}

		/**
		 * Action hook after all meta boxes are added for the Content Group UI
		 *
		 * @since   1.0.0
		 *
		 * @param   Page_Generator_Pro_PostType     $post_type_instance     Post Type Instance.
		 * @param   bool                            $is_gutenberg_page      If Gutenberg Editor is used on this Content Group.
		 */
		do_action( 'page_generator_pro_groups_ui_add_meta_boxes', $this->base->get_class( 'post_type' ), $is_gutenberg_page );

	}

	/**
	 * Removes some metaboxes on the Groups Custom Post Type UI
	 *
	 * @since   2.1.1
	 *
	 * @global  array   $wp_meta_boxes  Array of registered metaboxes.
	 */
	public function remove_meta_boxes() {

		global $wp_meta_boxes;

		// Bail if no meta boxes for this CPT exist.
		if ( ! isset( $wp_meta_boxes['page-generator-pro'] ) ) {
			return;
		}

		// Define the metaboxes to remove.
		$remove_meta_boxes = array(
			// Main.
			'slugdiv',

			// Sidebar.
			'submitdiv',
			'tagsdiv-page-generator-tax',

			// Divi.
			'pageparentdiv',
			'postcustom',
		);

		/**
		 * Filters the metaboxes to remove from the Content Groups Screen.
		 *
		 * @since   2.1.1
		 *
		 * @param   array   $remove_meta_boxes   Meta Boxes to Remove.
		 */
		$remove_meta_boxes = apply_filters( 'page_generator_pro_groups_ui_remove_meta_boxes', $remove_meta_boxes );

		// Bail if no meta boxes are defined for removal.
		if ( ! is_array( $remove_meta_boxes ) ) {
			return;
		}
		if ( count( $remove_meta_boxes ) === 0 ) {
			return;
		}

		// Iterate through all registered meta boxes, removing those that aren't permitted.
		foreach ( $wp_meta_boxes['page-generator-pro'] as $position => $contexts ) {
			foreach ( $contexts as $context => $meta_boxes ) {
				foreach ( $meta_boxes as $meta_box_id => $meta_box ) {
					// If this meta box is in the array of meta boxes to remove, remove it now.
					if ( in_array( $meta_box_id, $remove_meta_boxes, true ) ) {
						unset( $wp_meta_boxes['page-generator-pro'][ $position ][ $context ][ $meta_box_id ] );
					}
				}
			}
		}

	}

	/**
	 * Outputs the Permalink Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_permalink( $post ) {

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get all available keywords, post types, taxonomies, authors and other settings that we might use on the admin screen.
		if ( ! $this->keywords ) {
			$this->keywords = $this->base->get_class( 'keywords' )->get_keywords_and_columns();
		}

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-permalink.php';

	}

	/**
	 * Outputs the Author Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_author( $post ) {

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// If an author is selected, fetch their details now for the select dropdown.
		if ( ! empty( $this->settings['author'] ) ) {
			$author = get_user_by( 'ID', $this->settings['author'] );
		}

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-author.php';

	}

	/**
	 * Outputs the Discussion Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_discussion( $post ) {

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-discussion.php';

	}

	/**
	 * Outputs the Upgrade Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_upgrade( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Load view.
		include $this->base->plugin->folder . '/_modules/dashboard/views/footer-upgrade.php';

	}

	/**
	 * Outputs the Actions Sidebar Top Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_actions_top( $post ) {

		// Define Group ID.
		$group_id = $post->ID;

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get limit.
		$limit = $this->base->get_class( 'ajax' )->get_trash_delete_per_request_item_limit();

		// Append to element IDs.
		$bottom = '';

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-actions.php';

	}

	/**
	 * Outputs the Actions Sidebar Bottom Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_actions_bottom( $post ) {

		// Define Group ID.
		$group_id = $post->ID;

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get limit.
		$limit = $this->base->get_class( 'ajax' )->get_trash_delete_per_request_item_limit();

		// Append to element IDs.
		$bottom = 'bottom';

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-actions.php';

	}

	/**
	 * Outputs the Actions Sidebar Meta Box for Gutenberg
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_actions_gutenberg( $post ) {

		// Define Group ID.
		$group_id = $post->ID;

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get limit.
		$limit = $this->base->get_class( 'ajax' )->get_trash_delete_per_request_item_limit();

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-actions-gutenberg.php';

	}

	/**
	 * Outputs the Publish Sidebar Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_publish( $post ) {

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get options.
		$statuses = $this->base->get_class( 'common' )->get_post_statuses();

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-publish.php';

	}

	/**
	 * Outputs the Generation Sidebar Meta Box
	 *
	 * @since   2.0.2
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_generation( $post ) {

		// Get settings.
		if ( count( $this->settings ) === 0 ) {
			$this->settings = $this->base->get_class( 'groups' )->get_settings( $post->ID );
		}

		// Get options.
		$methods = array(
			'all'        => __( 'All', 'page-generator' ),
			'sequential' => __( 'Sequential', 'page-generator' ),
		);

		// Define labels.
		$labels = array(
			'singular' => __( 'Page', 'page-generator' ),
			'plural'   => __( 'Pages', 'page-generator' ),
		);

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-generation.php';

	}

	/**
	 * Outputs the Template Sidebar Meta Box
	 *
	 * @since   3.3.9
	 *
	 * @param   WP_Post $post   Custom Post Type's Post.
	 */
	public function output_meta_box_template( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get options.
		$post_types_templates = $this->base->get_class( 'common' )->get_post_types_templates();

		// Load view.
		include $this->base->plugin->folder . 'views/admin/generate-meta-box-template.php';

	}

	/**
	 * Called when a Group is saved.
	 *
	 * @since   2.0.2
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function save_post( $post_id ) {

		// Bail if this isn't a Page Generator Pro Group that's being saved.
		if ( get_post_type( $post_id ) !== $this->base->get_class( 'post_type' )->post_type_name ) {
			return;
		}

		// Run security checks.
		// Missing nonce .
		if ( ! isset( $_POST[ $this->base->plugin->name . '_nonce' ] ) ) {
			return;
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( sanitize_key( $_POST[ $this->base->plugin->name . '_nonce' ] ), 'save_generate' ) ) {
			return;
		}

		// Save the Group's Settings.
		$result = $this->base->get_class( 'groups' )->save(
			$this->base->get_class( 'common' )->recursive_sanitize_text_field( $_POST[ $this->base->plugin->name ] ),
			$post_id
		);

		// Get action.
		$action = $this->get_action();

		// If an error occured, show it.
		if ( is_wp_error( $result ) ) {
			$this->base->get_class( 'notices' )->enable_store();
			$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );
			$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );

			// If this action isn't Trash or Delete, stop.
			if ( ! in_array(
				$action,
				array(
					'trash_generated_content',
					'delete_generated_content',
				),
				true
			) ) {
				return;
			}
		}

		// Maybe run an action on the Group now.
		$redirect = ( $action === 'generate_server' ? true : false );
		$this->base->get_class( 'groups' )->run_action( $action, $post_id, $redirect );

	}

	/**
	 * Returns the localized title
	 *
	 * @since   2.0.2
	 *
	 * @param   string $key    Key.
	 * @return  string          Message
	 */
	public function get_title( $key ) {

		// Get Titles and Messages.
		$titles_messages = $this->get_titles_and_messages();

		// Bail if no Titles exist.
		if ( ! isset( $titles_messages['titles'] ) ) {
			return '';
		}

		// Bail if the Title does not exist.
		if ( ! isset( $titles_messages['titles'][ $key ] ) ) {
			return '';
		}

		// Return the title.
		return $titles_messages['titles'][ $key ];

	}

	/**
	 * Returns the localized message
	 *
	 * @since   2.0.2
	 *
	 * @param   string $key    Key.
	 * @return  string          Message
	 */
	public function get_message( $key ) {

		// Get Titles and Messages.
		$titles_messages = $this->get_titles_and_messages();

		// Bail if no Messages exist.
		if ( ! isset( $titles_messages['messages'] ) ) {
			return '';
		}

		// Bail if the Message does not exist.
		if ( ! isset( $titles_messages['messages'][ $key ] ) ) {
			return '';
		}

		// Return the message.
		return $titles_messages['messages'][ $key ];

	}

	/**
	 * Returns Titles and Messages that are used for Content Generation,
	 * which are displayed in various notifications.
	 *
	 * @since   2.0.2
	 *
	 * @return  array   Titles and Messages
	 */
	public function get_titles_and_messages() {

		// Define localizations.
		$localization = array(
			'titles'   => array(
				'test'                     => __( 'Test', 'page-generator' ),
				'generate'                 => __( 'Generate', 'page-generator' ),
				'trash_generated_content'  => __( 'Trash Generated Content', 'page-generator' ),
				'delete_generated_content' => __( 'Delete Generated Content', 'page-generator' ),
				'cancel_generation'        => __( 'Cancel Generation', 'page-generator' ),
			),

			'messages' => array(
				// Generate.
				'generate_confirm'                 => __( 'This will generate all Pages/Posts. Proceed?', 'page-generator' ),

				// Cancel Generation.
				'cancel_generation_confirm'        => __( 'This will cancel Content Generation, allowing the Group to be edited.  Proceed?', 'page-generator' ),
				'cancel_generation_success'        => __( 'Generation Cancelled', 'page-generator' ),

				// Test.
				'test_confirm'                     => __( 'This will generate a single Page/Post in draft mode. Proceed?', 'page-generator' ),
				'test'                             => __( 'Generating Test in Draft Mode...', 'page-generator' ),
				/* Translators: URL to Term */
				'test_success'                     => __( 'Test Page/Post Generated at %s', 'page-generator' ),

				// Trash Generated Content.
				/* translators: Number of Pages/Posts to trash */
				'trash_generated_content_confirm'  => __( 'This will trash ALL %s content items generated by this group. Proceed?', 'page-generator' ),
				'trash_generated_content'          => __( 'Trashing Generated Content Items', 'page-generator' ),
				'trash_generated_content_success'  => __( 'Generated Content Trashed', 'page-generator' ),
				'trash_generated_content_error'    => __( 'An error occured. Please try again.', 'page-generator' ),

				// Delete Generated Content.
				/* translators: Number of Pages/Post to delete */
				'delete_generated_content_confirm' => __( 'This will PERMANENTLY DELETE ALL %s content items generated by this group. This action cannot be undone. Proceed?', 'page-generator' ),
				'delete_generated_content'         => __( 'Deleting Generated Content Items', 'page-generator' ),
				'delete_generated_content_success' => __( 'Generated Content Deleted', 'page-generator' ),
			),
		);

		/**
		 * Filters the localization title and message strings used for Generation.
		 *
		 * @since   2.0.2
		 *
		 * @param   array   $localization   Titles and Messages.
		 */
		$localization = apply_filters( 'page_generator_pro_groups_ui_get_titles_and_messages', $localization );

		// Return.
		return $localization;

	}

	/**
	 * Determines which submit button was pressed on the Groups add/edit screen
	 *
	 * @since   2.0.2
	 *
	 * @return  string  Action
	 */
	private function get_action() {

		if ( isset( $_POST['test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'test';
		}

		if ( isset( $_POST['generate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'generate';
		}

		if ( isset( $_POST['trash_generated_content'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'trash_generated_content';
		}

		if ( isset( $_POST['delete_generated_content'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'delete_generated_content';
		}

		if ( isset( $_POST['cancel_generation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'cancel_generation';
		}

		if ( isset( $_POST['save'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'save';
		}

		// No action given.
		return false;

	}

	/**
	 * Limit creating more than one Group via the WordPress Administration, by preventing
	 * the 'Add New' functionality, and ensuring the user is always taken to the edit
	 * screen of the single Group when they access the Post Type.
	 *
	 * @since   1.3.8
	 */
	public function limit_admin() {

		global $pagenow;

		switch ( $pagenow ) {
			/**
			 * Edit
			 * WP_List_Table
			 */
			case 'edit.php':
				// Bail if no Post Type is supplied.
				if ( ! isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					break;
				}

				// Bail if we're not on our Group Post Type.
				if ( $_REQUEST['post_type'] !== 'page-generator-pro' ) { // phpcs:ignore WordPress.Security.NonceVerification
					break;
				}

				// Fetch first group.
				$groups = new WP_Query(
					array(
						'post_type'      => 'page-generator-pro',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
					)
				);

				// Bail if no Groups exist, so the user can create one.
				if ( count( $groups->posts ) === 0 ) {
					break;
				}

				// Redirect to the Group's edit screen.
				wp_safe_redirect( 'post.php?post=' . $groups->posts[0]->ID . '&action=edit' );
				die;

			/**
			 * Add New
			 */
			case 'post-new.php':
			case 'press-this.php':
				// Bail if we don't know the Post Type.
				if ( ! isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					break;
				}

				// Bail if we're not on our Group Post Type.
				if ( $_REQUEST['post_type'] !== 'page-generator-pro' ) { // phpcs:ignore WordPress.Security.NonceVerification
					break;
				}

				// Fetch first group.
				$groups = new WP_Query(
					array(
						'post_type'      => 'page-generator-pro',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
					)
				);

				// Bail if no Groups exist, so the user can create one.
				if ( count( $groups->posts ) === 0 ) {
					break;
				}

				// Redirect to the Group's edit screen.
				wp_safe_redirect( 'post.php?post=' . $groups->posts[0]->ID . '&action=edit' );
				die;
		}

	}

	/**
	 * Limit creating more than one Group via XML-RPC
	 *
	 * @since   1.3.8
	 *
	 * @param   bool  $limit  Limit XML-RPC.
	 * @param   array $post   Post Data.
	 * @return                  Limit XML-RPC
	 */
	public function limit_xml_rpc( $limit, $post = array() ) {

		// Bail if we're not on an XMLRPC request.
		if ( ! defined( 'XMLRPC_REQUEST' ) || XMLRPC_REQUEST !== true ) {
			return $limit;
		}

		// Bail if no Post Type specified.
		if ( ! isset( $post['post_type'] ) ) {
			return $limit;
		}
		if ( $post['post_type'] !== 'page-generator-pro' ) {
			return $limit;
		}

		// If here, we're trying to create a Group. Don't let this happen.
		return true;

	}

}
