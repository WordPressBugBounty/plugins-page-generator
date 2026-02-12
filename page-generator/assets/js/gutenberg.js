/**
 * Registers Dynamic Elements as Gutenberg Blocks
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

// Register Gutenberg Blocks if the Gutenberg Editor is loaded on screen.
// This prevents JS errors if this script is accidentally enqueued on a non-
// Gutenberg editor screen, or the Classic Editor Plugin is active.
if ( typeof wp !== 'undefined' &&
	typeof wp.blockEditor !== 'undefined' ) {

	if ( typeof page_generator_pro_gutenberg != 'undefined' ) {

		// Remove some panels if we're using Gutenberg on Content Groups.
		if ( page_generator_pro_gutenberg.post_type == 'page-generator-pro' && typeof wp.editPost !== 'undefined' ) {
			wp.data.dispatch( 'core/editor' ).removeEditorPanel( 'post-link' );
			wp.data.dispatch( 'core/editor' ).removeEditorPanel( 'page-attributes' );
			wp.data.dispatch( 'core/editor' ).removeEditorPanel( 'template' );

			// Display notice if an integration is detected that requires the Pro version.
			if ( typeof page_generator_pro_gutenberg.notice === 'object' ) {
				wp.data.dispatch( 'core/notices' ).createInfoNotice(
					page_generator_pro_gutenberg.notice.text,
					{
						id: 'page-generator-pro-integration-notice',
						isDismissible: false,
						actions: [
							{
								label: 'Upgrade',
								url: page_generator_pro_gutenberg.notice.url
						},
						]
					}
				);
			}
		}

		// Initialize conditional fields.
		page_generator_pro_conditional_fields_initialize();

		// Initialize autocomplete instance on Gutenberg Title field, if Keywords exist.
		// We do this when the Title field is selected, because initializing Tribute's
		// autocomplete instance sooner than this doesn't work.
		const pageGeneratorProContentGroupEditorIsReady = wp.data.subscribe(
			function () {

				// Hacky; can't find a Gutenberg native way to determine if the Post Title field is focused.
				if ( jQuery( 'h1.wp-block-post-title' ).hasClass( 'is-selected' ) ) {
					// Post Title editor is ready; initialize autocomplete on Gutenberg Title field, if Keywords exist.
					if ( typeof wp_zinc_autocomplete_initialize !== 'undefined' ) {
						wp_zinc_autocomplete_initialize( '.editor-visual-editor__post-title-wrapper' );
					}

					// Calling the constant will stop subscribing to future events, as we've now initialized
					// Tribute on the Title field, and don't need to initialize it again.
					pageGeneratorProContentGroupEditorIsReady();
				}

			}
		);

	}

}
