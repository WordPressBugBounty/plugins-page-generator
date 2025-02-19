<?php
/**
 * Page Generator class.
 *
 * @package Page_Generator
 * @author WP Zinc
 */

/**
 * Main Page Generator class, used to load the Plugin.
 *
 * @package   Page_Generator
 * @author    WP Zinc
 * @version   1.0.0
 */
class Page_Generator {

	/**
	 * Holds the class object.
	 *
	 * @since   1.1.3
	 *
	 * @var     object
	 */
	public static $instance;

	/**
	 * Holds the plugin information object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $plugin = '';

	/**
	 * Holds the dashboard class object.
	 *
	 * @since   1.1.6
	 *
	 * @var     object
	 */
	public $dashboard = '';

	/**
	 * Classes
	 *
	 * @since   1.9.8
	 *
	 * @var     array
	 */
	public $classes = '';

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Plugin Details.
		$this->plugin                    = new stdClass();
		$this->plugin->name              = 'page-generator';
		$this->plugin->displayName       = 'Page Generator';
		$this->plugin->author_name       = 'WP Zinc';
		$this->plugin->version           = PAGE_GENERATOR_PLUGIN_VERSION;
		$this->plugin->buildDate         = PAGE_GENERATOR_PLUGIN_BUILD_DATE;
		$this->plugin->php_requires      = '7.1';
		$this->plugin->folder            = PAGE_GENERATOR_PLUGIN_PATH;
		$this->plugin->url               = PAGE_GENERATOR_PLUGIN_URL;
		$this->plugin->documentation_url = 'https://www.wpzinc.com/documentation/page-generator-pro';
		$this->plugin->support_url       = 'https://www.wpzinc.com/support';
		$this->plugin->upgrade_url       = 'https://www.wpzinc.com/plugins/page-generator-pro';
		$this->plugin->logo              = PAGE_GENERATOR_PLUGIN_URL . 'assets/images/icons/logo.svg';
		$this->plugin->review_name       = 'page-generator';
		$this->plugin->review_notice     = sprintf(
			/* translators: Plugin Name */
			__( 'Thanks for using %s to generate content!', 'page-generator' ),
			$this->plugin->displayName
		);

		// ConvertKit Form UID.
		$this->plugin->convertkit_form_uid = '3fcb562250';

		// Upgrade Reasons.
		$this->plugin->upgrade_reasons = array(
			array(
				__( 'Generate any Post Type', 'page-generator' ),
				__( 'Pro provides options to generate any Post Type, including Pages, Posts and Custom Post Types.', 'page-generator' ),
			),
			array(
				__( 'Powerful Content Generation', 'page-generator' ),
				__( 'Combine Keyword combinations sequentially, at random or use all possible combinations. Generate content in-browser, using WP-Cron or WP-CLI.', 'page-generator' ),
			),
			array(
				__( 'Generate Unlimited, Unique Posts, Pages and Custom Post Types', 'page-generator' ),
				__( 'Create as many Content Groups as you wish, each with different settings.', 'page-generator' ),
			),
			array(
				__( 'SEO and Schema Compatible', 'page-generator' ),
				__( 'Works with SEO and Schema Plugins including AIOSEO, Genesis, Meta Tag Manager, Platinum SEO, Rank Math, Schema Pro, SEOPress, SEOPressor, WPSSO, Yoast and more.', 'page-generator' ),
			),
			array(
				__( 'Page Builder Support', 'page-generator' ),
				__( 'Works with Ark, Avada, Avia, Beaver Builder, BeTheme, Bold, Divi, Elementor, Enfold, Flatsome, Fusion Builder, Fresh Builder, Live Composer, Muffin, Pro, SiteOrigin, Thrive Architect, Visual Composer, X and more.', 'page-generator' ),
			),
			array(
				__( 'Use existing Site Designs / Layouts', 'page-generator' ),
				__( 'One click functionality to import existing layouts from your WordPress Pages/Posts.', 'page-generator' ),
			),
			array(
				__( 'Build Interlinked Directory Sites', 'page-generator' ),
				__( 'Full support for hierarchical content generation and interlinking, such as Region > County > City > ZIP Code > Service.', 'page-generator' ),
			),
			array(
				__( 'Multiple Keyword Sources', 'page-generator' ),
				__( 'Import your text file and CSV keyword lists, use a local database table or link to remotely hosted CSV files.', 'page-generator' ),
			),
			array(
				__( 'City, County, Region/State and ZIP Code Keywords', 'page-generator' ),
				__( 'Enter a city name, country and radius to automatically build a keyword containing all nearby cities, counties, ZIP codes and/or Phone Area Codes.', 'page-generator' ),
			),
			array(
				__( 'Powerful Keyword Transformations', 'page-generator' ),
				__( 'Change the case of Keyword Terms, extract sub terms, force specific terms to be used and more.', 'page-generator' ),
			),
			array(
				__( 'Overwrite or Skip Existing Generated Content', 'page-generator' ),
				__( 'Refresh existing content, correct mistakes in previously generated Pages or choose to skip already generated content to avoid duplication.', 'page-generator' ),
			),
			array(
				__( 'Embed Dynamic Images, Maps, Wikipedia and Yelp Content', 'page-generator' ),
				__( 'Dynamic shortcodes can be inserted into your content to output Google Maps, Media Library Images, OpenStreetMap, Pexels / Pixabay Images, Related Links, Wikipedia Content, Yelp! Business Listings and YouTube Videos.', 'page-generator' ),
			),
			array(
				__( 'Advanced Scheduling Functionality', 'page-generator' ),
				__( 'Publish content in the past, now or schedule for the future, to drip feed content over time.', 'page-generator' ),
			),
			array(
				__( 'Page Attribute and Menu Support', 'page-generator' ),
				__( 'Define Page Templates, Page Parent and Menu items for your generated Pages.', 'page-generator' ),
			),
			array(
				__( 'Full Taxonomy Support', 'page-generator' ),
				__( 'Choose taxonomy terms to assign to your generated content, or have Page Generator Pro create new taxonomy terms.  For more dynamic content, keyword support in taxonomies is provided.', 'page-generator' ),
			),
			array(
				__( 'Multilingual Support', 'page-generator' ),
				__( 'Page Generator Pro supports Polylang and WPML, to generate content in multiple languages.', 'page-generator' ),
			),
			array(
				__( 'Generate Comments', 'page-generator' ),
				__( 'Generate comments for each generated Post, with options to specify the number of comments, the date of each comment between a date range, each comment’s author and comment..  For more dynamic content, keyword support in taxonomies is provided.', 'page-generator' ),
			),
		);

		// Dashboard Submodule.
		if ( ! class_exists( 'WPZincDashboardWidget' ) ) {
			require_once $this->plugin->folder . '_modules/dashboard/class-wpzincdashboardwidget.php';
		}
		$this->dashboard = new WPZincDashboardWidget( $this->plugin, 'https://www.wpzinc.com/wp-content/plugins/lum-deactivation' );

		// Defer loading of Plugin Classes.
		add_action( 'init', array( $this, 'initialize' ), 1 );
		add_action( 'init', array( $this, 'upgrade' ), 2 );

		// Admin Menus.
		add_action( 'page_generator_pro_admin_admin_menu', array( $this, 'admin_menus' ) );

		// Localization.
		add_action( 'init', array( $this, 'load_language_files' ) );

	}

	/**
	 * Register menus and submenus.
	 *
	 * @since   5.0.0
	 *
	 * @param   string $minimum_capability  Minimum capability required for access.
	 */
	public function admin_menus( $minimum_capability ) {

		// Main Menu.
		add_menu_page( $this->plugin->displayName, $this->plugin->displayName, $minimum_capability, $this->plugin->name . '-keywords', array( $this->get_class( 'admin' ), 'keywords_screen' ), $this->plugin->logo );

		// Sub Menu.
		$keywords_page = add_submenu_page( $this->plugin->name . '-keywords', __( 'Keywords', 'page-generator' ), __( 'Keywords', 'page-generator' ), $minimum_capability, $this->plugin->name . '-keywords', array( $this->get_class( 'admin' ), 'keywords_screen' ) );
		add_action( "load-$keywords_page", array( $this->get_class( 'admin' ), 'add_keyword_screen_options' ) );

		$groups_page   = add_submenu_page( $this->plugin->name . '-keywords', __( 'Generate Content', 'page-generator' ), __( 'Generate Content', 'page-generator' ), $minimum_capability, 'edit.php?post_type=' . $this->get_class( 'post_type' )->post_type_name );
		$generate_page = add_submenu_page( $this->plugin->name . '-keywords', __( 'Generate', 'page-generator' ), __( 'Generate', 'page-generator' ), $minimum_capability, $this->plugin->name . '-generate', array( $this->get_class( 'admin' ), 'generate_screen' ) );

		// Menus.
		$upgrade_page = add_submenu_page( $this->plugin->name . '-keywords', __( 'Upgrade', 'page-generator' ), __( 'Upgrade', 'page-generator' ), $minimum_capability, $this->plugin->name . '-upgrade', array( $this->get_class( 'admin' ), 'upgrade_screen' ) );

	}

	/**
	 * Initializes required and licensed classes
	 *
	 * @since   1.9.8
	 */
	public function initialize() {

		$this->classes = new stdClass();

		$this->initialize_admin_or_frontend_editor();
		$this->initialize_frontend();

	}

	/**
	 * Initialize classes for the WordPress Administration interface or a frontend Page Builder
	 *
	 * @since   2.5.2
	 */
	private function initialize_admin_or_frontend_editor() {

		// Bail if this request isn't for the WordPress Administration interface and isn't for a frontend Page Builder.
		if ( ! $this->is_admin_or_frontend_editor() ) {
			return;
		}

		$this->classes->admin                 = new Page_Generator_Pro_Admin( self::$instance );
		$this->classes->ajax                  = new Page_Generator_Pro_AJAX( self::$instance );
		$this->classes->common                = new Page_Generator_Pro_Common( self::$instance );
		$this->classes->editor                = new Page_Generator_Pro_Editor( self::$instance );
		$this->classes->generate              = new Page_Generator_Pro_Generate( self::$instance );
		$this->classes->groups_ui             = new Page_Generator_Pro_Groups_UI( self::$instance );
		$this->classes->groups                = new Page_Generator_Pro_Groups( self::$instance );
		$this->classes->install               = new Page_Generator_Pro_Install( self::$instance );
		$this->classes->keywords              = new Page_Generator_Pro_Keywords( self::$instance );
		$this->classes->keywords_source_local = new Page_Generator_Pro_Keywords_Source_Local( self::$instance );
		$this->classes->notices               = new Page_Generator_Pro_Notices( self::$instance );
		$this->classes->post_type             = new Page_Generator_Pro_PostType( self::$instance );
		$this->classes->settings              = new Page_Generator_Pro_Settings( self::$instance );
		$this->classes->screen                = new Page_Generator_Pro_Screen( self::$instance );

	}

	/**
	 * Initialize classes for the frontend web site
	 *
	 * @since   2.5.2
	 */
	private function initialize_frontend() {

		// Bail if this request isn't for the frontend web site.
		if ( is_admin() ) {
			return;
		}

		$this->classes->common    = new Page_Generator_Pro_Common( self::$instance );
		$this->classes->post_type = new Page_Generator_Pro_PostType( self::$instance );
		$this->classes->settings  = new Page_Generator_Pro_Settings( self::$instance );

	}

	/**
	 * Improved version of WordPress' is_admin(), which includes whether we're
	 * editing on the frontend using a Page Builder.
	 *
	 * @since   2.5.2
	 *
	 * @return  bool    Is Admin or Frontend Editor Request
	 */
	public function is_admin_or_frontend_editor() {

		return is_admin();

	}

	/**
	 * Runs the upgrade routine once the plugin has loaded
	 *
	 * @since   1.1.7
	 */
	public function upgrade() {

		// Bail if we're not in the WordPress Admin.
		if ( ! is_admin() ) {
			return;
		}

		// Run upgrade routine.
		$this->get_class( 'install' )->upgrade();

	}

	/**
	 * Loads plugin textdomain
	 *
	 * @since   1.6.5
	 */
	public function load_language_files() {

		load_plugin_textdomain( 'page-generator', false, $this->plugin->name . '/languages' );

	}

	/**
	 * Returns the given class
	 *
	 * @since   1.9.8
	 *
	 * @param   string $name   Class Name.
	 * @return  object          Class Object
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes->{ $name } ) ) {
			// Define the error.
			$error = new WP_Error(
				'page_generator_get_class',
				sprintf(
					/* translators: %1$s: Plugin Name, %2$s: PHP class name */
					__( '%1$s: Error: Could not load Plugin class %2$s', 'page-generator' ),
					$this->plugin->displayName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_html( $error->get_error_message() ),
					sprintf(
						/* translators: Plugin Name */
						esc_html__( '%s: Error', 'page-generator' ),
						esc_html( $this->plugin->displayName ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes->{ $name };

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   1.1.6
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
