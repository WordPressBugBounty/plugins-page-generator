<?php
/**
 * Administration Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Registers the Plugin's menus and screens, saving Plugin wide settings.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.0.0
 */
class Page_Generator_Pro_Admin {

	/**
	 * Holds the base object.
	 *
	 * @since   1.2.1
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

		// Admin Notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Admin CSS, JS and Menu.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 8 );
		add_action( 'parent_file', array( $this, 'admin_menu_hierarchy_correction' ), 999 );

		// Keywords: Bulk and Row Actions.
		add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );
		add_action( 'init', array( $this, 'run_keyword_save_actions' ) );
		add_action( 'current_screen', array( $this, 'run_keyword_table_bulk_actions' ) );
		add_action( 'current_screen', array( $this, 'run_keyword_table_row_actions' ) );

	}

	/**
	 * Checks the transient to see if any admin notices need to be output now.
	 *
	 * @since   1.2.3
	 */
	public function admin_notices() {

		// Determine the screen that we're on.
		$screen = $this->base->get_class( 'screen' )->get_current_screen();

		// If we're not on a plugin screen, exit.
		if ( ! $screen['screen'] ) {
			return;
		}

		// Output notices.
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );
		$this->base->get_class( 'notices' )->output_notices();
		$this->base->get_class( 'notices' )->delete_notices();

	}

	/**
	 * Enqueues CSS and JS
	 *
	 * @since   1.0.0
	 */
	public function admin_scripts_css() {

		global $post;

		// CSS - always load, admin / frontend editor wide.
		if ( $this->base->is_admin_or_frontend_editor() ) {
			wp_enqueue_style( $this->base->plugin->name . '-admin', $this->base->plugin->url . 'assets/css/admin.css', array(), $this->base->plugin->version );
		}

		// Define CSS variables for design.
		wp_register_style( $this->base->plugin->name . '-vars', false, array(), $this->base->plugin->version );
		wp_enqueue_style( $this->base->plugin->name . '-vars' );
		wp_add_inline_style(
			$this->base->plugin->name . '-vars',
			trim(
				':root {
			--wpzinc-logo: url(\'' . esc_attr( $this->base->plugin->logo ) . '\');
			--wpzinc-plugin-display-name: "' . esc_attr( $this->base->plugin->displayName ) . ' ";
		}'
			)
		);

		// Determine the screen that we're on.
		$screen = $this->base->get_class( 'screen' )->get_current_screen();

		// If we're not on a plugin screen, exit.
		if ( ! $screen['screen'] ) {
			return;
		}

		// (Re)register dashboard scripts and enqueue CSS for frontend editors, which won't have registered these yet.
		$this->base->dashboard->admin_scripts_css();

		// Determine whether to load minified versions of JS.
		$minified = $this->base->dashboard->should_load_minified_js();

		// JS - register scripts we might use.
		wp_register_script( $this->base->plugin->name . '-conditional-fields', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'conditional-fields' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-generate-browser', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'generate-browser' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-generate-content', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'generate-content' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-gutenberg', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'gutenberg' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery', $this->base->plugin->name . '-conditional-fields' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-keywords', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'keywords' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-selectize', $this->base->plugin->url . 'assets/js/' . ( $minified ? 'min/' : '' ) . 'selectize' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );

		// If here, we're on a plugin screen.
		// Conditionally load scripts and styles depending on which section of the Plugin we're loading.
		switch ( $screen['screen'] ) {
			/**
			 * Keywords
			 */
			case 'keywords':
				switch ( $screen['section'] ) {
					/**
					 * Keywords: Add / Edit
					 */
					case 'edit':
						wp_enqueue_script( $this->base->plugin->name . '-conditional-fields' );
						wp_enqueue_script( $this->base->plugin->name . '-keywords' );

						// Localize Keywords with CodeMirror Code Editor instance.
						wp_localize_script(
							$this->base->plugin->name . '-keywords',
							'page_generator_pro_keywords',
							array(
								'codeEditor' => wp_enqueue_code_editor(
									array(
										'type' => 'text',
									)
								),
							)
						);
						break;
				}
				break;

			/**
			 * Content: Groups
			 */
			case 'content_groups':
				// JS: WP Zinc.
				wp_enqueue_script( 'wpzinc-admin-modal' );

				// JS: Plugin.
				wp_enqueue_script( $this->base->plugin->name . '-generate-content' );

				// Get localization strings.
				$localization = array_merge(
					$this->base->get_class( 'groups_ui' )->get_titles_and_messages(),
					array(
						'nonces' => array(
							'generate_content'         => wp_create_nonce( 'page-generator-pro-generate-browser' ),
							'trash_generated_content'  => wp_create_nonce( 'page-generator-pro-trash-generated-content' ),
							'delete_generated_content' => wp_create_nonce( 'page-generator-pro-delete-generated-content' ),
						),
					)
				);

				// Add data to localization depending on the screen we're viewing.
				switch ( $screen['section'] ) {
					/**
					 * Content: Groups: Edit
					 */
					case 'edit':
						// Prevents errors with meta boxes and Yoast in the WordPress Admin.
						if ( is_admin() ) {
							wp_enqueue_media();
						}

						// CSS: WP Zinc.
						wp_enqueue_style( 'wpzinc-admin-selectize' );

						// JS: WordPress.
						wp_enqueue_script( 'jquery-ui-sortable' );

						// JS: WP Zinc.
						wp_enqueue_script( 'wpzinc-admin-conditional' );
						wp_enqueue_script( 'wpzinc-admin-inline-search' );
						wp_enqueue_script( 'wpzinc-admin-selectize' );
						wp_enqueue_script( 'wpzinc-admin-tags' );
						wp_enqueue_script( 'wpzinc-admin' );

						// JS: Plugin.
						wp_enqueue_script( $this->base->plugin->name . '-conditional-fields' );
						wp_enqueue_script( $this->base->plugin->name . '-gutenberg' );
						wp_enqueue_script( $this->base->plugin->name . '-selectize' );

						// Enqueue and localize Autocomplete, if a configuration exists.
						$autocomplete = $this->base->get_class( 'common' )->get_autocomplete_configuration( true );
						if ( $autocomplete ) {
							wp_enqueue_script( 'wpzinc-admin-autocomplete' );
							wp_enqueue_script( 'wpzinc-admin-autocomplete-gutenberg' );

							wp_localize_script( 'wpzinc-admin-autocomplete', 'wpzinc_autocomplete', $autocomplete );
							wp_localize_script( 'wpzinc-admin-autocomplete-gutenberg', 'wpzinc_autocomplete_gutenberg', $autocomplete );
						}

						// Localize Gutenberg.
						wp_localize_script(
							$this->base->plugin->name . '-gutenberg',
							'page_generator_pro_gutenberg',
							array(
								'keywords'  => $this->base->get_class( 'keywords' )->get_keywords_and_columns( true ),
								'post_type' => ( isset( $post->post_type ) ? $post->post_type : false ),
							)
						);

						// Localize Selectize.
						wp_localize_script(
							$this->base->plugin->name . '-selectize',
							'page_generator_pro_selectize',
							array(
								'ajaxurl' => admin_url( 'admin-ajax.php' ),
								'fields'  => $this->base->get_class( 'common' )->get_selectize_enabled_fields(),
							)
						);

						// Get localization strings.
						$localization['post_id'] = ( isset( $post->ID ) ? $post->ID : false );
						break;
				}

				// Apply Localization.
				wp_localize_script( $this->base->plugin->name . '-generate-content', 'page_generator_pro_generate_content', $localization );
				break;

			/**
			 * Generate
			 */
			case 'generate':
				wp_enqueue_script( 'jquery-ui-progressbar' );
				wp_enqueue_script( 'wpzinc-admin-synchronous-ajax' );
				break;

		}

		// Add footer action to output overlay modal markup.
		add_action( 'admin_footer', array( $this, 'output_modal' ) );

		/**
		 * Enqueues CSS and JS
		 *
		 * @since   2.6.2
		 *
		 * @param   array       $screen     Screen (screen, section).
		 * @param   WP_Post     $post       WordPress Post.
		 * @param   bool        $minified   Whether to load minified JS.
		 */
		do_action( 'page_generator_pro_admin_admin_scripts_css', $screen, $post, $minified );

		// CSS.
		if ( class_exists( 'Page_Generator' ) ) {
			// Hide 'Add New' if a Group exists.
			$number_of_groups = $this->base->get_class( 'groups' )->get_count();
			if ( $number_of_groups > 0 ) {
				?>
				<style type="text/css">body.post-type-page-generator-pro a.page-title-action { display: none; }</style>
				<?php
			}
		}

	}

	/**
	 * Add the Plugin to the WordPress Administration Menu
	 *
	 * @since   1.0.0
	 */
	public function admin_menu() {

		// Define the minimum capability required to access settings.
		$minimum_capability = 'manage_options';

		/**
		 * Defines the minimum capability required to access the Plugin's
		 * Menu and Sub Menus
		 *
		 * @since   2.8.9
		 *
		 * @param   string  $capability     Minimum Required Capability.
		 * @return  string                  Minimum Required Capability
		 */
		$minimum_capability = apply_filters( 'page_generator_pro_admin_admin_menu_minimum_capability', $minimum_capability );

		/**
		 * Add settings menus and sub menus for the Plugin's settings.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $minimum_capability     Minimum capability required.
		 */
		do_action( 'page_generator_pro_admin_admin_menu', $minimum_capability );

	}

	/**
	 * Ensures this Plugin's top level Admin menu remains open when the user clicks on:
	 * - Generate Content
	 * - Generate Terms
	 *
	 * This prevents the 'wrong' admin menu being open (e.g. Posts)
	 *
	 * @since   1.2.3
	 *
	 * @param   string $parent_file    Parent Admin Menu File Name.
	 * @return  string                  Parent Admin Menu File Name
	 */
	public function admin_menu_hierarchy_correction( $parent_file ) {

		global $current_screen;

		// If we're creating or editing a Content Group, set the $parent_file to this Plugin's registered menu name.
		if ( $current_screen->base === 'post' && $current_screen->post_type === $this->base->get_class( 'post_type' )->post_type_name ) {
			// The free version uses a different top level filename.
			if ( class_exists( 'Page_Generator' ) ) {
				return $this->base->plugin->name . '-keywords';
			}

			return $this->base->plugin->name;
		}

		return $parent_file;

	}

	/**
	 * Defines options to display in the Screen Options dropdown on the Keywords
	 * WP_List_Table, and performs any save actions for Keywords.
	 *
	 * @since   2.6.5
	 */
	public function add_keyword_screen_options() {

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Keywords', 'page-generator' ),
				'default' => 20,
				'option'  => 'page_generator_pro_keywords_per_page',
			)
		);

		// Initialize Keywords WP_List_Table, as this will trigger WP_List_Table to add column options.
		$keywords_table = new Page_Generator_Pro_Keywords_Table( $this->base );

	}

	/**
	 * Sets values for options displayed in the Screen Options dropdown on the Keywords and Logs
	 * WP_List_Table
	 *
	 * @since   2.6.5
	 *
	 * @param   mixed  $screen_option  The value to save instead of the option value. Default false (to skip saving the current option).
	 * @param   string $option         The option name.
	 * @param   string $value          The option value.
	 * @return  string                  The option value
	 */
	public function set_screen_options( $screen_option, $option, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		return $value;

	}

	/**
	 * Run any bulk actions on the Log WP_List_Table
	 *
	 * @since   2.6.5
	 */
	public function run_keyword_save_actions() {

		// Setup notices class.
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

		// Get command.
		$cmd = ( ( isset( $_GET['cmd'] ) ) ? sanitize_text_field( $_GET['cmd'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
		switch ( $cmd ) {
			/**
			 * Add / Edit Keyword
			 */
			case 'form':
				// Save keyword.
				$keyword_id = $this->save_keyword();

				if ( is_wp_error( $keyword_id ) ) {
					$this->base->get_class( 'notices' )->add_error_notice( $keyword_id->get_error_message() );
				} elseif ( is_numeric( $keyword_id ) ) {
					// Redirect.
					$this->base->get_class( 'notices' )->enable_store();
					$this->base->get_class( 'notices' )->add_success_notice( __( 'Keyword saved successfully', 'page-generator' ) );
					wp_safe_redirect( 'admin.php?page=page-generator-keywords&cmd=form&id=' . $keyword_id );
					die;
				}
				break;

		}

	}

	/**
	 * Run any bulk actions on the Keyword WP_List_Table
	 *
	 * @since   2.6.5
	 */
	public function run_keyword_table_bulk_actions() {

		// Get screen.
		$screen = $this->base->get_class( 'screen' )->get_current_screen();

		// Bail if we're not on the Keywords Screen.
		if ( $screen['screen'] !== 'keywords' ) {
			return;
		}
		if ( $screen['section'] !== 'wp_list_table' ) {
			return;
		}

		// Setup notices class, enabling persistent storage.
		$this->base->get_class( 'notices' )->enable_store();
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

		// Bail if no nonce exists or fails verification.
		if ( ! array_key_exists( '_wpnonce', $_REQUEST ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'bulk-keywords' ) ) {
			$this->base->get_class( 'notices' )->add_error_notice(
				__( 'Nonce invalid. Bulk action not performed.', 'page-generator' )
			);
			return;
		}

		// Get bulk action from the fields that might contain it.
		$bulk_action = array_values(
			array_filter(
				array(
					( isset( $_REQUEST['action'] ) && $_REQUEST['action'] != '-1' ? sanitize_text_field( $_REQUEST['action'] ) : '' ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] != '-1' ? sanitize_text_field( $_REQUEST['action2'] ) : '' ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					( isset( $_REQUEST['action3'] ) && ! empty( $_REQUEST['action3'] ) ? sanitize_text_field( $_REQUEST['action3'] ) : '' ),
				)
			)
		);

		// Bail if no bulk action.
		if ( ! is_array( $bulk_action ) ) {
			return;
		}
		if ( ! count( $bulk_action ) ) {
			return;
		}

		// Perform Bulk Action.
		switch ( $bulk_action[0] ) {

			/**
			 * Delete Keywords
			 */
			case 'delete':
				// Setup notices class, enabling persistent storage.
				$this->base->get_class( 'notices' )->enable_store();
				$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

				// Get Keyword IDs.
				if ( ! isset( $_REQUEST['ids'] ) ) {
					$this->base->get_class( 'notices' )->add_error_notice(
						__( 'No Keywords were selected for deletion.', 'page-generator' )
					);
					break;
				}

				// Sanitize IDs.
				$ids = array();
				foreach ( $_REQUEST['ids'] as $id ) {
					$id         = absint( sanitize_text_field( $id ) );
					$ids[ $id ] = $id;
				}

				// Delete Keywords.
				$result = $this->base->get_class( 'keywords' )->delete( $ids );

				// Output success or error messages.
				if ( is_wp_error( $result ) ) {
					// Add error message and redirect back to the keyword table.
					$this->base->get_class( 'notices' )->add_error_notice( $result );
				} else {
					$this->base->get_class( 'notices' )->add_success_notice(
						sprintf(
							/* translators: Number of Keywords deleted */
							__( '%s Keywords deleted.', 'page-generator' ),
							count( $ids )
						)
					);
				}

				// Redirect.
				$this->redirect_after_keyword_action();
				break;

		}

	}

	/**
	 * Run any row actions on the Keywords WP_List_Table now
	 *
	 * @since   1.2.3
	 */
	public function run_keyword_table_row_actions() {

		// Bail if no page specified.
		$page = ( ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : false ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $page ) {
			return;
		}
		if ( $page !== $this->base->plugin->name . '-keywords' ) {
			return;
		}

		// Setup notices class, enabling persistent storage.
		$this->base->get_class( 'notices' )->enable_store();
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

		// Bail if no nonce exists.
		if ( ! array_key_exists( '_wpnonce', $_REQUEST ) ) {
			return;
		}

		// Bail if nonce fails verification, as it might be for a different request.
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'action-keywords' ) ) {
			return;
		}

		// Bail if no row action specified.
		$cmd = ( ( isset( $_GET['cmd'] ) ) ? sanitize_text_field( $_GET['cmd'] ) : false );
		if ( ! $cmd ) {
			return;
		}

		switch ( $cmd ) {
			/**
			 * Delete Keyword
			 */
			case 'delete':
				// Bail if no ID set.
				if ( ! isset( $_GET['id'] ) ) {
					$this->base->get_class( 'notices' )->add_error_notice( __( 'No Group was selected for duplication.', 'page-generator' ) );
					break;
				}

				// Delete keyword.
				$result = $this->base->get_class( 'keywords' )->delete( absint( $_GET['id'] ) );

				// Output success or error messages.
				if ( is_string( $result ) ) {
					// Add error message and redirect back to the keyword table.
					$this->base->get_class( 'notices' )->add_error_notice( $result );
				} elseif ( $result === true ) {
					// Success.
					$this->base->get_class( 'notices' )->add_success_notice( __( 'Keyword deleted successfully.', 'page-generator' ) );
				}

				// Redirect.
				$this->redirect_after_keyword_action();
				break;

		}

	}

	/**
	 * Reloads the Keywords WP_List_Table, with search, order and pagination arguments if supplied
	 *
	 * @since   2.6.5
	 */
	private function redirect_after_keyword_action() {

		// Nonce verification already completed, so can safely ignore phpcs errors.
		$url = add_query_arg(
			array(
				'page'    => $this->base->plugin->name . '-keywords',
				's'       => ( isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification
				'paged'   => ( isset( $_REQUEST['paged'] ) ? sanitize_text_field( $_REQUEST['paged'] ) : 1 ), // phpcs:ignore WordPress.Security.NonceVerification
				'orderby' => ( isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'keyword' ), // phpcs:ignore WordPress.Security.NonceVerification
				'order'   => ( isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'ASC' ), // phpcs:ignore WordPress.Security.NonceVerification
			),
			'admin.php'
		);

		wp_safe_redirect( $url );
		die;

	}

	/**
	 * Outputs the Keywords Screens
	 *
	 * @since   1.0.0
	 */
	public function keywords_screen() {

		// Get command.
		$cmd = ( ( isset( $_GET['cmd'] ) ) ? sanitize_text_field( $_GET['cmd'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
		switch ( $cmd ) {
			/**
			 * Add / Edit Keyword
			 */
			case 'form':
				// Define blank Keyword.
				$keyword = array(
					'keyword' => '',
					'source'  => 'local',
				);

				// Get existing Keyword from database.
				if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					// Editing an existing Keyword.
					// Get Keyword from DB.
					$keyword_id = absint( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification
					$keyword    = $this->base->get_class( 'keywords' )->get_by_id( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

					// View.
					$view = 'views/admin/keywords-form-edit.php';
				} else {
					// Adding a new Keyword.
					// View.
					$view = 'views/admin/keywords-form.php';
				}

				// If the form has been posted, an error occured if we are here.
				// Apply the posted values to the keyword.
				if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'save_keyword' ) ) {
					$keyword['keyword'] = wp_unslash( sanitize_text_field( $_POST['keyword'] ) );
					$keyword['source']  = wp_unslash( sanitize_text_field( $_POST['source'] ) );
					$keyword['options'] = stripslashes_deep( $_POST[ $keyword['source'] ] );
				}

				// Get Keyword Sources.
				$sources = $this->base->get_class( 'keywords' )->get_sources();
				break;

			/**
			 * Delete Keyword
			 * Index
			 */
			case 'delete':
			default:
				// Setup Table.
				$keywords_table = new Page_Generator_Pro_Keywords_Table( $this->base );
				$keywords_table->prepare_items();

				// View.
				$view = 'views/admin/keywords-table.php';
				break;

		}

		// Load View.
		include_once $this->base->plugin->folder . $view;

	}

	/**
	 * Save Keyword
	 *
	 * @since   1.0.0
	 *
	 * @return  mixed   WP_Error | int
	 */
	public function save_keyword() {

		// Check if a POST request was made.
		if ( ! isset( $_POST['submit'] ) ) {
			return false;
		}

		// Run security checks.
		// Missing nonce.
		if ( ! isset( $_POST['nonce'] ) ) {
			return new WP_Error( 'page_generator_pro_admin_save_keyword', __( 'Nonce field is missing. Settings NOT saved.', 'page-generator' ) );
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'save_keyword' ) ) {
			return new WP_Error( 'page_generator_pro_admin_save_keyword', __( 'Invalid nonce specified. Settings NOT saved.', 'page-generator' ) );
		}

		// Validate Form Inputs.
		$id           = ( ( isset( $_REQUEST['id'] ) && ! empty( $_REQUEST['id'] ) ) ? absint( $_REQUEST['id'] ) : '' );
		$keyword_name = sanitize_text_field( $_POST['keyword'] );
		$source       = sanitize_text_field( $_POST['source'] );
		$options      = array(
			'data' => ( isset( $_POST[ $source ]['data'] ) ? sanitize_textarea_field( $_POST[ $source ]['data'] ) : '' ),
		);

		// Build Keyword.
		$keyword = array(
			'keyword'   => $keyword_name,
			'source'    => $source,
			'options'   => $options,

			// Determined by the Source.
			'data'      => '',
			'delimiter' => '',
			'columns'   => '',
		);

		/**
		 * Define the Keyword properties (data, delimiter and columns) for the given Source
		 * before saving the Keyword to the database.
		 *
		 * @since   3.0.8
		 *
		 * @param   array   $keyword    Keyword arguments.
		 */
		$keyword = apply_filters( 'page_generator_pro_keywords_save_' . $source, $keyword );

		// If the Keyword is a WP_Error, bail.
		if ( is_wp_error( $keyword ) ) {
			return $keyword;
		}

		// Save Keyword (returns WP_Error or Keyword ID).
		return $this->base->get_class( 'keywords' )->save( $keyword, $id );

	}

	/**
	 * Generates content for the given Group and Group Type
	 *
	 * @since   1.2.3
	 */
	public function generate_screen() {

		// Setup notices class, enabling persistent storage.
		$this->base->get_class( 'notices' )->set_key_prefix( 'page_generator_pro_' . wp_get_current_user()->ID );

		// Bail if no Group ID was specified.
		if ( ! isset( $_REQUEST['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->base->get_class( 'notices' )->add_error_notice( __( 'No Group ID was specified.', 'page-generator' ) );
			include_once $this->base->plugin->folder . 'views/admin/notices.php';
			return;
		}

		// Get Group ID and Type.
		$id   = absint( $_REQUEST['id'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$type = ( isset( $_REQUEST['type'] ) ? sanitize_text_field( $_REQUEST['type'] ) : 'content' ); // phpcs:ignore WordPress.Security.NonceVerification

		// Get groups class.
		$group = $this->base->get_class( 'groups' );

		// If this Group has a request to cancel generation, silently clear the status, system and cancel
		// flags before performing further checks on whether we should generate.
		if ( $group->cancel_generation_requested( $id ) ) {
			$group->stop_generation( $id );
		}

		// Fetch group settings.
		$settings = $group->get_settings( $id, false );

		// Bail if an error occured.
		if ( is_wp_error( $settings ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( $settings->get_error_message() );
			include_once $this->base->plugin->folder . 'views/admin/generate-run-notice.php';
			return;
		}

		// Define return to Group URL and Post/Taxonomy Type, depending on the type.
		$return_url   = admin_url( 'post.php?post=' . $id . '&amp;action=edit' );
		$object       = get_post_type_object( $settings['type'] );
		$object_label = $object->labels->name;

		// Validate group.
		$validated = $group->validate( $id );
		if ( is_wp_error( $validated ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( $validated->get_error_message() );
			include_once $this->base->plugin->folder . 'views/admin/generate-run-notice.php';
			return;
		}

		/**
		 * Runs any actions before Generate Content has started.
		 *
		 * @since   3.0.7
		 *
		 * @param   int     $group_id   Group ID.
		 * @param   bool    $test_mode  Test Mode.
		 * @param   string  $system     System.
		 */
		do_action( 'page_generator_pro_generate_content_before', $id, false, 'browser' );

		// Calculate how many pages could be generated.
		$number_of_pages_to_generate = $this->base->get_class( 'generate' )->get_max_number_of_pages( $settings );
		if ( is_wp_error( $number_of_pages_to_generate ) ) {
			$this->base->get_class( 'notices' )->add_error_notice( $number_of_pages_to_generate->get_error_message() );
			include_once $this->base->plugin->folder . 'views/admin/generate-run-notice.php';
			return;
		}

		// Check that the number of posts doesn't exceed the maximum that can be generated.
		if ( $settings['numberOfPosts'] > $number_of_pages_to_generate ) {
			$settings['numberOfPosts'] = $number_of_pages_to_generate;
		}

		// If no limit specified, set one now.
		if ( $settings['numberOfPosts'] == 0 ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			if ( $settings['method'] === 'random' ) {
				$settings['numberOfPosts'] = 10;
			} elseif ( $settings['resumeIndex'] > 0 ) {
					$settings['numberOfPosts'] = $number_of_pages_to_generate - $settings['resumeIndex'];
			} else {
				$settings['numberOfPosts'] = $number_of_pages_to_generate;
			}
		}

		// Check that the number of posts doesn't exceed the maximum that can be generated.
		if ( $settings['numberOfPosts'] > $number_of_pages_to_generate ) {
			$settings['numberOfPosts'] = $number_of_pages_to_generate;
		}

		// Set last generated post date and time based on the Group's settings (i.e. date/time of now,
		// specific date/time or a random date/time).
		$last_generated_post_date_time = $this->base->get_class( 'generate' )->post_date( $settings );

		// Add Plugin Settings.
		$settings['stop_on_error']       = (int) $this->base->get_class( 'settings' )->get_setting( $this->base->plugin->name . '-generate', 'stop_on_error', 0 );
		$settings['stop_on_error_pause'] = (int) $this->base->get_class( 'settings' )->get_setting( $this->base->plugin->name . '-generate', 'stop_on_error_pause', 5 );

		// Enqueue and localize Generate Browser script with the necessary parameters for synchronous AJAX requests.
		wp_enqueue_script( $this->base->plugin->name . '-generate-browser' );
		wp_localize_script(
			$this->base->plugin->name . '-generate-browser',
			'page_generator_pro_generate_browser',
			array(
				'action'                        => 'page_generator_pro_generate_' . $type,
				'action_on_start'               => 'page_generator_pro_generate_' . $type . '_before',
				'action_on_finished'            => 'page_generator_pro_generate_' . $type . '_after',
				'nonce'                         => wp_create_nonce( 'page-generator-pro-generate-browser' ),
				'id'                            => $id,
				'last_generated_post_date_time' => $last_generated_post_date_time,
				'max_number_of_pages'           => $number_of_pages_to_generate, // Determines the /[total] portion of the output.
				'number_of_requests'            => $settings['numberOfPosts'],
				'resume_index'                  => $settings['resumeIndex'],
				'stop_on_error'                 => (int) $this->base->get_class( 'settings' )->get_setting( $this->base->plugin->name . '-generate', 'stop_on_error', 0 ),
				'stop_on_error_pause'           => (int) ( $this->base->get_class( 'settings' )->get_setting( $this->base->plugin->name . '-generate', 'stop_on_error_pause', 5 ) * 1000 ),
				'exit_screen'                   => __( 'Closing / navigating away from this window will stop generation. Are you sure?', 'page-generator' ),
				'browser_title'                 => array(
					'processing' => sprintf(
						/* translators: Post Type */
						__( '%1$s Generated', 'page-generator' ),
						$object_label
					),
					'success'    => __( 'Generation Complete', 'page-generator' ),
					'cancelled'  => __( 'Generation Cancelled', 'page-generator' ),
				),
			)
		);

		// Set a flag to denote that this Group is generating content.
		$group->start_generation( $id, 'generating', 'browser' );

		// Load View.
		include_once $this->base->plugin->folder . 'views/admin/generate-run.php';

	}

	/**
	 * Outputs the hidden Javascript Modal and Overlay in the Footer
	 *
	 * @since   2.4.6
	 */
	public function output_modal() {

		// Load view.
		require_once $this->base->plugin->folder . '_modules/dashboard/views/modal.php';

	}

}
