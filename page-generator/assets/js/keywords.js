/**
 * Add/Edit Keyword
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Delete Keyword Confirmation
		 */
		$( 'span.trash a, input[name="bulk_action"]' ).click(
			function ( e ) {

				switch ( $( this ).attr( 'name' ) ) {
					case 'bulk_action':
						// Confirm the Delete bulk action option was selected.
						if ( $( 'select[name="action"]' ).val() !== 'delete' && $( 'select[name="action2"]' ).val() !== 'delete' ) {
							return;
						}

						result = confirm( 'Are you sure you want to delete these Keywords?' );
						break;
					default:
						result = confirm( 'Are you sure you want to delete this Keyword?' );
						break;
				}

				// If the user cancels, bail.
				if ( ! result ) {
					e.preventDefault();
					return false;
				}

				// Allow the request through.
			}
		);

		// Initialize conditional fields.
		page_generator_pro_conditional_fields_initialize();
		$( 'select[name="source"]' ).trigger( 'change' );

		// Initialize CodeMirror.
		if ( $( 'textarea#data' ).length > 0 ) {
			wp.codeEditor.initialize( $( 'textarea#data' ), page_generator_pro_keywords );
		}

		// Initialize datatables for previewing third party sources.
		if ( $( 'table.page-generator-pro-keywords-table' ).length ) {
			var pageGeneratorProKeywordTermsTable = $( 'table.page-generator-pro-keywords-table' ).DataTable(
				{
					ajax: {
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 	'page_generator_pro_keywords_get_terms',
							nonce:  	$( 'input[name="nonce"]' ).val(),
							id: 		$( 'table.page-generator-pro-keywords-table' ).data( 'keyword-id' )
						},
					},
					processing: true,
					serverSide: true,
					responsive: false,
					autoWidth: true,
					scrollX: true,
					scrollY: 500
				}
			);
		}

	}
);
