<?php
/**
 * Keywords Class
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

/**
 * Reads and writes Keywords from the database table,
 * performing validation on create/edit/delete actions.
 *
 * Handles import functionality within the Keywords
 * section of the Plugin.
 *
 * @package Page_Generator_Pro
 * @author  WP Zinc
 * @version 1.0.0
 */
class Page_Generator_Pro_Keywords {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.9.7
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Primary SQL Table
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $table = 'page_generator_keywords';

	/**
	 * Primary SQL Table Primary Key
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $key = 'keywordID';

	/**
	 * Holds query results from calling get_keywords_names(),
	 * for performance
	 *
	 * @since   3.0.9
	 *
	 * @var     mixed
	 */
	private $keywords_names = false;

	/**
	 * Holds query results from calling get_keywords_and_columns(),
	 * for performance
	 *
	 * @since   3.0.7
	 *
	 * @var     mixed
	 */
	private $keywords_columns = false;

	/**
	 * Holds query results from calling get_keywords_and_columns(),
	 * for performance
	 *
	 * @since   3.1.3
	 *
	 * @var     mixed
	 */
	private $keywords_columns_with_curly_braces = false;

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
	 * Activation routines for this Model
	 *
	 * @since   1.0.7
	 *
	 * @global  $wpdb   WordPress DB Object
	 */
	public function activate() {

		global $wpdb;

		// Enable error output if WP_DEBUG is enabled.
		$wpdb->show_errors = true;

		// Create database tables.
		$wpdb->query(
			' CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'page_generator_keywords (
                            `keywordID` int(10) NOT NULL AUTO_INCREMENT,
                            `keyword` varchar(191) NOT NULL,
                            `source` varchar(191) NOT NULL,
                            `options` text NOT NULL,
                            `columns` text NOT NULL,
                            `delimiter` varchar(191) NOT NULL,
                            `data` longtext NOT NULL,
                            PRIMARY KEY `keywordID` (`keywordID`),
                            UNIQUE KEY `keyword` (`keyword`)
                        ) ' . $wpdb->get_charset_collate() . ' AUTO_INCREMENT=1'
		);

	}

	/**
	 * Upgrades the Model's database table if required columns
	 * are missing.
	 *
	 * @since   1.7.8
	 *
	 * @global  $wpdb   WordPress DB Object.
	 */
	public function upgrade() {

		global $wpdb;

		// Fetch columns.
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'page_generator_keywords' );

		// Bail if no columns found.
		if ( ! is_array( $columns ) || count( $columns ) === 0 ) {
			return true;
		}

		// Define columns we're searching for.
		$required_columns = array(
			'source'    => false,
			'options'   => false,
			'columns'   => false,
			'delimiter' => false,
		);

		// Iterate through columns.
		foreach ( $columns as $column ) {
			if ( array_key_exists( $column->Field, $required_columns ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$required_columns[ $column->Field ] = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		// Iterate through our required columns, adding them to the database table if they don't exist.
		foreach ( $required_columns as $column => $exists ) {
			if ( $exists ) {
				continue;
			}

			switch ( $column ) {
				/**
				 * Text columns
				 */
				case 'options':
				case 'columns':
					$wpdb->query(
						$wpdb->prepare(
							'ALTER TABLE %1$spage_generator_keywords ADD COLUMN `%2$s` text NOT NULL AFTER `keyword`', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
							$wpdb->prefix,
							$column
						)
					);
					break;

				/**
				 * Varchar columns
				 */
				case 'source':
				case 'delimiter':
					$wpdb->query(
						$wpdb->prepare(
							'ALTER TABLE %1$spage_generator_keywords ADD COLUMN `%2$s` varchar(191) NOT NULL AFTER `keyword`', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
							$wpdb->prefix,
							$column
						)
					);
					break;
			}
		}

		return true;

	}

	/**
	 * Changes the 'columns' field from varchar to text, so that many column names can be stored
	 * against a Keyword
	 *
	 * @since   2.4.5
	 */
	public function upgrade_columns_type_to_text() {

		global $wpdb;

		// Fetch columns.
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'page_generator_keywords' );

		// Find column.
		foreach ( $columns as $column ) {
			if ( $column->Field !== 'columns' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				continue;
			}

			// If here, we found the column we want.
			if ( $column->Type === 'text' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				// Already set to the correct type.
				return true;
			}

			// Change column from varchar to text.
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %1$spage_generator_keywords MODIFY COLUMN `%2$s` text NOT NULL', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					$wpdb->prefix,
					$column->Field // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				)
			);

			return true;
		}

		return true;

	}

	/**
	 * Changes the 'data' field from mediumtext to longtext, so that more Terms can be stored against a Keyword
	 *
	 * @since   3.1.9
	 */
	public function upgrade_data_type_to_longtext() {

		global $wpdb;

		// Fetch columns.
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'page_generator_keywords' );

		// Find column.
		foreach ( $columns as $column ) {
			if ( $column->Field !== 'data' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				continue;
			}

			// If here, we found the column we want.
			if ( $column->Type === 'longtext' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				// Already set to the correct type.
				return true;
			}

			// Change column from varchar to text.
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %1$spage_generator_keywords MODIFY COLUMN `%2$s` longtext NOT NULL', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					$wpdb->prefix,
					$column->Field // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				)
			);

			return true;
		}

		return true;

	}

	/**
	 * Returns an array of Keyword Sources and their attributes.
	 *
	 * @since   3.0.8
	 *
	 * @return  mixed   bool | array
	 */
	public function get_sources() {

		return apply_filters( 'page_generator_pro_keywords_register_sources', array() );

	}

	/**
	 * Updates Terms for the given Keywords, if a Keyword's Source isn't local,
	 * by fetching them from the remote sources.
	 *
	 * @since   3.0.8
	 *
	 * @param   array $keywords   Keywords to Update Terms for.
	 * @return  mixed               WP_Error | true
	 */
	public function refresh_terms( $keywords ) {

		global $wpdb;

		// Iterate through Keywords, updating each Keyword's Terms.
		foreach ( $keywords as $keyword ) {
			// Get Keyword.
			$keyword = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT keywordID, keyword, source, columns, delimiter, options FROM {$wpdb->prefix}page_generator_keywords WHERE keyword = %s LIMIT 1",
					$keyword
				),
				ARRAY_A
			);

			// Skip if the Keyword doesn't exist.
			if ( is_null( $keyword ) ) {
				continue;
			}
			if ( ! count( $keyword ) ) {
				continue;
			}

			// Skip if the Keyword's source is local or blank (blank is a local source prior to 3.0.8).
			if ( $keyword['source'] === 'local' || empty( $keyword['source'] ) ) {
				continue;
			}

			// Expand options JSON.
			if ( ! empty( $keyword['options'] ) ) {
				$keyword['options'] = json_decode( $keyword['options'], true );
			}

			/**
			 * Refresh the given Keyword's Columns and Terms by fetching them from the database
			 * immediately before starting generation.
			 *
			 * @since   3.0.8
			 *
			 * @param   string  $terms      Terms.
			 * @param   array   $keyword    Keyword.
			 */
			$result = apply_filters( 'page_generator_pro_keywords_refresh_terms_' . $keyword['source'], '', $keyword );

			// If the result is a WP_Error, bail.
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Update Keyword Delimiter, Columns and Data.
			$keyword = array_merge(
				$keyword,
				array(
					'delimiter' => $result['delimiter'],
					'columns'   => ( is_array( $result['columns'] ) ? implode( ',', $result['columns'] ) : '' ),
					'data'      => implode( "\n", $result['data'] ),
				)
			);

			// Save Keyword (returns WP_Error or Keyword ID).
			$result = $this->save( $keyword, $keyword['keywordID'] );

			// If saving the Keyword failed, bail.
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// All Keyword Terms refreshed.
		return true;

	}

	/**
	 * Gets a record by its ID
	 *
	 * @since   1.0.0
	 *
	 * @param   int $id  ID.
	 * @return  mixed       Record | false
	 */
	public function get_by_id( $id ) {

		global $wpdb;

		// Get record.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}page_generator_keywords WHERE keywordID = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);

		// Check a record was found   . .
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		// Return single result from results.
		return $this->get( $results[0] );

	}

	/**
	 * Gets a single result by the key/value pair
	 *
	 * @since   1.0.0
	 *
	 * @param   string $field  Field Name.
	 * @param   string $value  Field Value.
	 * @return  array           Records
	 */
	public function get_by( $field, $value ) {

		global $wpdb;

		// Get record.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}page_generator_keywords WHERE {$field} = %s", // phpcs:ignore WordPress.DB
				$value
			),
			ARRAY_A
		);

		// Check a record was found.
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		// Return single result from results.
		return $this->get( $results[0] );

	}

	/**
	 * Returns an array of records
	 *
	 * @since   1.0.0
	 *
	 * @param   string $order_by           Order By Column (default: keyword, optional).
	 * @param   string $order              Order Direction (default: ASC, optional).
	 * @param   int    $paged              Pagination (default: 1, optional).
	 * @param   int    $results_per_page   Results per page (default: 10, optional).
	 * @param   string $search             Search Keywords (optional).
	 * @return  array                       Records
	 */
	public function get_all( $order_by = 'keyword', $order = 'ASC', $paged = 1, $results_per_page = 10, $search = '' ) {

		global $wpdb;

		$get_all = ( ( $paged == -1 ) ? true : false ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual

		// Sanitize order by.
		$order_by_sql = sanitize_sql_orderby( "{$order_by} {$order}" );

		// Search.
		if ( ! empty( $search ) ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}page_generator_keywords WHERE keyword LIKE %s ORDER BY {$order_by_sql}", // phpcs:ignore WordPress.DB
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		} else {
			$query = "SELECT * FROM {$wpdb->prefix}page_generator_keywords ORDER BY {$order_by_sql}"; // phpcs:ignore WordPress.DB
		}

		// Add Limit.
		if ( ! $get_all ) {
			$query = $query . $wpdb->prepare(
				' LIMIT %d, %d',
				( ( $paged - 1 ) * $results_per_page ),
				$results_per_page
			);
		}

		// Get results.
		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB

		// Check a record was found.
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		return stripslashes_deep( $results );

	}

	/**
	 * Returns keywords names in lowercase
	 *
	 * @since   3.0.9
	 *
	 * @param   bool $include_curly_braces   Include Curly Braces on Keywords in Results.
	 * @return  array                           Keywords
	 */
	public function get_keywords_names( $include_curly_braces = false ) {

		// If the query results are already stored, use those for performance.
		if ( $this->keywords_names ) {
			return $this->keywords_names;
		}

		global $wpdb;

		// Get results.
		$results = $wpdb->get_results( "SELECT keyword FROM {$wpdb->prefix}page_generator_keywords ORDER BY keyword ASC", ARRAY_A );

		// Check a record was found   .
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		// Iterate through results, building keywords.
		$keywords = array();
		foreach ( $results as $result ) {
			// Add keywords.
			$keywords[] = strtolower( ( $include_curly_braces ? '{' : '' ) . $result['keyword'] . ( $include_curly_braces ? '}' : '' ) );
		}

		// Store results in class for performance, to save running this query again.
		$this->keywords_names = stripslashes_deep( $keywords );

		// Return.
		return $this->keywords_names;

	}

	/**
	 * Returns keywords and keywords with individual column subsets.
	 *
	 * @since   1.9.7
	 *
	 * @param   bool $include_curly_braces   Include Curly Braces on Keywords in Results.
	 * @return  array                           Keywords
	 */
	public function get_keywords_and_columns( $include_curly_braces = false ) {

		// If the query results are already stored, use those for performance.
		if ( $include_curly_braces ) {
			if ( $this->keywords_columns_with_curly_braces ) {
				return $this->keywords_columns_with_curly_braces;
			}
		} elseif ( $this->keywords_columns ) {
				return $this->keywords_columns;
		}

		global $wpdb;

		// Get results.
		$results = $wpdb->get_results( "SELECT keyword, columns, delimiter FROM {$wpdb->prefix}page_generator_keywords ORDER BY keyword ASC", ARRAY_A ); // phpcs:ignore WordPress.DB

		// Check a record was found.
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		// Iterate through results, building keywords.
		$keywords = array();
		foreach ( $results as $result ) {
			// Add keywords.
			$keywords[] = ( $include_curly_braces ? '{' : '' ) . $result['keyword'] . ( $include_curly_braces ? '}' : '' );

			// If the columns are empty, ignore.
			if ( empty( $result['columns'] ) ) {
				continue;
			}

			// If the delimiter is missing, ignore.
			if ( empty( $result['delimiter'] ) ) {
				continue;
			}

			// Get columns.
			$columns = explode( ',', $result['columns'] );
			if ( count( $columns ) === 0 ) {
				continue;
			}
			if ( ! is_array( $columns ) ) {
				continue;
			}

			// Add each column as a keyword.
			foreach ( $columns as $column ) {
				$keywords[] = ( $include_curly_braces ? '{' : '' ) . $result['keyword'] . '(' . trim( $column ) . ')' . ( $include_curly_braces ? '}' : '' );
			}
		}

		// Store results in class for performance, to save running this query again.
		if ( $include_curly_braces ) {
			$this->keywords_columns_with_curly_braces = stripslashes_deep( $keywords );
			return $this->keywords_columns_with_curly_braces;
		}

		$this->keywords_columns = stripslashes_deep( $keywords );
		return $this->keywords_columns;

	}

	/**
	 * Confirms whether a keyword already exists.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $keyword        Keyword.
	 * @param   int    $id             Keyword ID (if defined and matches the ID found for an existing keyword, it's ignored).
	 * @return  bool                    Exists
	 */
	public function exists( $keyword, $id = '' ) {

		global $wpdb;

		// Prepare query.
		if ( empty( $id ) ) {
			$query = $wpdb->prepare(
				"SELECT keywordID FROM {$wpdb->prefix}page_generator_keywords WHERE keyword = %s",
				$keyword
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT keywordID FROM {$wpdb->prefix}page_generator_keywords WHERE keyword = %s AND keywordID != %d",
				$keyword,
				$id
			);
		}

		// Run query.
		$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB

		// Check a record was found.
		if ( ! $results ) {
			return false;
		}
		if ( count( $results ) === 0 ) {
			return false;
		}

		return true;

	}

	/**
	 * Get the number of matching records
	 *
	 * @since   1.0.0
	 *
	 * @param   string $search Search Keywords (optional).
	 * @return  bool            Exists
	 */
	public function total( $search = '' ) {

		global $wpdb;

		// Prepare query.
		if ( ! empty( $search ) ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(keywordID) FROM {$wpdb->prefix}page_generator_keywords WHERE keyword LIKE %s",
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		} else {
			$query = "SELECT COUNT(keywordID) FROM {$wpdb->prefix}page_generator_keywords";
		}

		// Return count.
		return $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB

	}

	/**
	 * Returns the given record, casting values, stripping slashes
	 * and expanding data into arrays
	 *
	 * @since   3.0.8
	 *
	 * @param   array $result     Keyword Row.
	 * @return  array               Keyword Row
	 */
	private function get( $result ) {

		// Cast values.
		$result['keywordID'] = absint( $result['keywordID'] );

		// Stripslashes.
		$result['data']      = wp_unslash( $result['data'] );
		$result['delimiter'] = wp_unslash( $result['delimiter'] );
		$result['columns']   = wp_unslash( $result['columns'] );

		// Expand data into array.
		$result['dataArr']    = explode( "\n", $result['data'] );
		$result['columnsArr'] = explode( ',', $result['columns'] );

		// Expand options JSON.
		if ( ! empty( $result['options'] ) ) {
			$result['options'] = json_decode( $result['options'], true );
		}

		// Define the source as local if no source exists.
		if ( empty( $result['source'] ) ) {
			$result['source'] = 'local';
		}

		// Return record.
		return $result;

	}

	/**
	 * Adds or edits a record, based on the given data array.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $data           Array of data to save.
	 * @param   int   $id             ID (if set, edits the existing record).
	 * @param   bool  $append_terms   Whether to append terms to the existing Keyword Term data (false = replace).
	 * @return  mixed                   ID or WP_Error
	 */
	public function save( $data, $id = '', $append_terms = false ) {

		global $wpdb;

		// Fill missing keys with empty values to avoid DB errors.
		if ( ! isset( $data['source'] ) ) {
			$data['source'] = '';
		}
		if ( ! isset( $data['options'] ) ) {
			$data['options'] = '';
		}
		if ( ! isset( $data['columns'] ) ) {
			$data['columns'] = '';
		}
		if ( ! isset( $data['delimiter'] ) ) {
			$data['delimiter'] = '';
		}

		// Strip empty newlines from Terms.
		$data['data'] = trim( preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $data['data'] ) );

		// If the data isn't UTF-8, UTF-8 encode it so it can be inserted into the DB.
		if ( function_exists( 'mb_detect_encoding' ) && ! mb_detect_encoding( $data['data'], 'UTF-8', true ) ) {
			$data['data'] = mb_convert_encoding( $data['data'], 'UTF-8', mb_list_encodings() );
		}

		// Remove spaces from column names.
		$data['columns'] = str_replace( ' ', '', $data['columns'] );

		// Validate Keyword.
		$validated = $this->validate( $data, $id );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Process options data.
		if ( is_array( $data['options'] ) ) {
			// JSON encode options, if it's an array.
			$data['options'] = wp_json_encode( $data['options'] );
		} elseif ( ! empty( $data['options'] ) ) {
			// If options is a string, decode and encode it to ensure it's a valid, escaped JSON string.
			$options = json_decode( $data['options'], true );
			if ( is_array( $options ) ) {
				$data['options'] = wp_json_encode( $options );
			}
		}

		// If here, the Keyword can be added/edited in the database.
		// Depending on whether an ID has been defined, update or insert the keyword.
		if ( ! empty( $id ) ) {
			if ( $append_terms ) {
				// Run query.
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}page_generator_keywords SET keyword = %s, source = %s, options = %s, delimiter = %s, columns = %s, data = concat(data, %s) WHERE keywordID = %s",
						$data['keyword'],
						$data['source'],
						$data['options'],
						$data['delimiter'],
						$data['columns'],
						addslashes( $data['data'] ),
						$id
					)
				);
			} else {
				// Editing an existing record.
				$result = $wpdb->update(
					$wpdb->prefix . $this->table,
					$data,
					array(
						$this->key => $id,
					),
					array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				);
			}

			// Check query was successful.
			if ( $result === false ) {
				return new WP_Error(
					'db_query_error',
					sprintf(
						/* translators: Database error */
						__( 'Keyword could not be updated in the database. Database error: %s', 'page-generator' ),
						$wpdb->last_error
					)
				);
			}

			// Success!
			return $id;
		} else {
			// Create new record.
			$result = $wpdb->insert(
				$wpdb->prefix . $this->table,
				$data,
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			// Check query was successful.
			if ( $result === false ) {
				return new WP_Error(
					'db_query_error',
					sprintf(
						/* translators: Database error */
						__( 'Keyword could not be added to the database. Database error: %s', 'page-generator' ),
						$wpdb->last_error
					)
				);
			}

			// Get and return ID.
			return $wpdb->insert_id;
		}

	}

	/**
	 * Performs a number of validation checks on the supplied Keyword, before it is
	 * added / updated in the database
	 *
	 * @since   3.0.8
	 *
	 * @param   array $data   Keyword.
	 * @param   int   $id     ID (if set, editing an existing Keyword).
	 * @return  mixed           boolean | WP_Error
	 */
	private function validate( $data, $id = '' ) {

		// Check for required data fields.
		if ( empty( $data['keyword'] ) ) {
			return new WP_Error( 'page_generator_pro_keywords_save_validation_error', __( 'Please complete the keyword field.', 'page-generator' ) );
		}

		// Check keyword name doesn't already exist as another keyword.
		if ( $this->exists( $data['keyword'], $id ) ) {
			return new WP_Error(
				'page_generator_pro_keywords_save_validation_error',
				sprintf(
					/* translators: Keyword Name */
					__( 'The Keyword "%s" already exists. Please choose a different name.', 'page-generator' ),
					$data['keyword']
				)
			);
		}

		// Check that the keyword does not contain spaces.
		if ( preg_match( '/[\\s\'\/~`\!@#\$%\^&\*\(\)\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $data['keyword'] ) ) {
			return new WP_Error( 'page_generator_pro_keywords_save_validation_error', __( 'The Keyword field can only contain letters, numbers, hyphens and underscores.', 'page-generator' ) );
		}

		// Column name checks.
		if ( ! empty( $data['columns'] ) ) {
			// Check column names don't contain invalid characters.
			if ( preg_match( '/[\\s\'\/~`\!@#\$%\^&\*\(\)\+=\{\}\[\]\|;:"\<\>\.\?\\\]/', $data['columns'] ) ) {
				return new WP_Error( 'page_generator_pro_keywords_save_validation_error', __( 'The Columns field can only contain letters, numbers, commas, hyphens and underscores.', 'page-generator' ) );
			}

			// Check a delimiter exists.
			if ( empty( $data['delimiter'] ) ) {
				return new WP_Error(
					'page_generator_pro_keywords_save_validation_error',
					__( 'Delimiter Field: When specifying column names in the Columns Field, a delimiter must also be specified.', 'page-generator' )
				);
			}

			// Check the delimiter does not exceed a single character.
			if ( strlen( $data['delimiter'] ) > 1 ) {
				return new WP_Error(
					'page_generator_pro_keywords_save_validation_error',
					__( 'The Delimiter field must be a single character.', 'page-generator' )
				);
			}
		}

		// If a delimiter is supplied, perform some further validation checks.
		if ( ! empty( $data['delimiter'] ) ) {
			// Check the delimiter isn't a pipe symbol, curly brace or bracket.
			foreach ( $this->get_invalid_delimiters() as $invalid_delimiter ) {
				if ( strpos( $data['delimiter'], $invalid_delimiter ) !== false ) {
					return new WP_Error(
						'page_generator_pro_keywords_save_validation_error',
						sprintf(
							/* translators: delimiter character */
							__( 'Delimiter Field: %s cannot be used as a delimiter, as it may conflict with Keyword syntax', 'page-generator' ),
							'<code>' . $data['delimiter'] . '</code>'
						)
					);
				}
			}

			// Check that column names are specified.
			if ( empty( $data['columns'] ) ) {
				return new WP_Error(
					'page_generator_pro_keywords_save_validation_error',
					__( 'Columns Field: Two or more column names must be specified in the Columns Field When specifying a delimiter.', 'page-generator' )
				);
			}

			// Check that there is a comma in the column names for separating columns.
			if ( strpos( $data['columns'], ',' ) === false ) {
				return new WP_Error(
					'page_generator_pro_keywords_save_validation_error',
					__( 'Columns Field: The values specified in the Columns Field must be separated by a comma.', 'page-generator' )
				);
			}
		}

		// If here, basic validation has passed.
		$result = true;

		/**
		 * Runs validation tests specific to this source for a Keyword immediately before it's saved to the database.
		 *
		 * @since   3.0.9
		 *
		 * @param   bool    $result     Validation Result.
		 * @param   array   $data       Keyword.
		 * @param   int     $id         ID (if set, editing an existing Keyword).
		 * @return  mixed               WP_Error | bool
		 */
		$result = apply_filters( 'page_generator_pro_keywords_validate_' . $data['source'], $result, $data, $id );

		// Return result, which will be WP_Error or true.
		return $result;

	}

	/**
	 * Deletes the record for the given primary key ID
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $data   Single ID or array of IDs.
	 * @return  bool            Success
	 */
	public function delete( $data ) {

		global $wpdb;

		if ( is_array( $data ) ) {
			foreach ( $data as $keyword_id ) {
				// Delete Keyword.
				$result = $wpdb->delete(
					$wpdb->prefix . $this->table,
					array(
						'keywordID' => $keyword_id,
					)
				);

				// Check query was successful.
				if ( $result === false ) {
					return new WP_Error(
						'db_query_error',
						sprintf(
							/* translators: Database error */
							__( 'Record(s) could not be deleted from the database. Database error: %s', 'page-generator' ),
							$wpdb->last_error
						)
					);
				}
			}
		} else {
			// Delete Keyword.
			$result = $wpdb->delete(
				$wpdb->prefix . $this->table,
				array(
					'keywordID' => $data,
				)
			);

			// Check query was successful.
			if ( $result === false ) {
				return new WP_Error(
					'db_query_error',
					sprintf(
						/* translators: Database error */
						__( 'Record(s) could not be deleted from the database. Database error: %s', 'page-generator' ),
						$wpdb->last_error
					)
				);
			}
		}

		return true;

	}

	/**
	 * Outputs a <select> dropdown comprising of Keywords, including any
	 * Keyword with Column combinations.
	 *
	 * @since   1.9.7
	 *
	 * @param   array  $keywords   Keywords.
	 * @param   string $element    HTML Element ID to insert Keyword into when selected in dropdown.
	 */
	public function output_dropdown( $keywords, $element ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Load view.
		include $this->base->plugin->folder . 'views/admin/keywords-dropdown.php';

	}

	/**
	 * Returns an array of delimiters that cannot be used with Keywords, as using
	 * them would result in errors with processing Keywords
	 *
	 * @since   unknown
	 *
	 * @return  array
	 */
	private function get_invalid_delimiters() {

		return array(
			'|',
			'{',
			'}',
			'(',
			')',
			':',
		);

	}

}
