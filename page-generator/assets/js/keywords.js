/**
 * Add/Edit Keyword
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

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
