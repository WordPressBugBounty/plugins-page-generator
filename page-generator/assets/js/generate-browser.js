/**
 * Generate via Browser
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

// Global vars for whether generation was cancelled by the user, and the last response object.
var page_generator_pro_generating = false,
	page_generator_pro_cancelled  = false,
	page_generator_pro_response   = false;

// Add an event listener to warn the user if they navigate away from this screen.
window.addEventListener(
	'beforeunload',
	function ( event ) {
		// If not generating, permit navigating away.
		if ( ! page_generator_pro_generating ) {
			return undefined;
		}

		// Send an AJAX request to remove the generating flag.
		pageGeneratorProGenerateBrowserStopped();

		// If here, still generating.
		// Show a warning to the user.
		event.returnValue = page_generator_pro_generate_browser.exit_screen;
	}
);

/**
 * Generate via Browser
 */
function pageGeneratorProGenerateBrowser() {

	( function ( $ ) {

		// Cast some settings.
		page_generator_pro_generate_browser.number_of_requests = Number( page_generator_pro_generate_browser.number_of_requests );
		page_generator_pro_generate_browser.resume_index       = Number( page_generator_pro_generate_browser.resume_index );

		// Update browser tab title.
		if ( typeof page_generator_pro_generate_browser.browser_title !== 'undefined' ) {
			document.title = '🕐 ' + page_generator_pro_generate_browser.resume_index + '/' + page_generator_pro_generate_browser.number_of_requests + ' ' + page_generator_pro_generate_browser.browser_title.processing;
		}

		// Set flag to tell the script that we're generating.
		page_generator_pro_generating = true;

		$( '#progress-bar' ).synchronous_request(
			{
				url:                ajaxurl,
				number_requests:    page_generator_pro_generate_browser.number_of_requests,
				offset:             page_generator_pro_generate_browser.resume_index,
				data: {
					id:                             page_generator_pro_generate_browser.id,
					action:                         page_generator_pro_generate_browser.action,
					nonce:                          page_generator_pro_generate_browser.nonce,
					last_generated_post_date_time:  page_generator_pro_generate_browser.last_generated_post_date_time,
				},
				wait:               page_generator_pro_generate_browser.stop_on_error_pause,
				stop_on_error:      page_generator_pro_generate_browser.stop_on_error,

				/**
				 * Called when an AJAX request is successful.  The content may not have generated, which can be
				 * checked by inspecting the response.success flag.
				 *
				 * @since   3.1.8
				 *
				 * @param   object  response        Response
				 * @param   int     currentIndex    Current Index
				 */
				onRequestSuccess:   function ( response, currentIndex ) {

					// Store response in global var.
					page_generator_pro_response = response;

					// If the log exceeds 100 items, reset it.
					if ( $( '#log ul li' ).length >= 100 ) {
						$( '#log ul' ).html( '' );
					}

					if ( response.success ) {
						// Define message and CSS class.
						var message   = response.data.message + ' <a href="' + response.data.url + '" target="_blank">' + response.data.url + '</a><br />Time: ' + response.data.duration + ' seconds. Memory Usage / Peak: ' + response.data.memory_usage + '/' + response.data.memory_peak_usage + 'MB',
							css_class = ( response.data.generated ? 'success' : 'warning' );

						for ( var keyword in response.data.keywords_terms ) {
							message += '<br />{' + keyword + '}: ' + response.data.keywords_terms[ keyword ];
						}

						// Output Log.
						$( '#log ul' ).append( '<li class="' + css_class + '">' + ( currentIndex + 1 ) + '/' + page_generator_pro_generate_browser.max_number_of_pages + ': ' + message + '</li>' );

						// Update browser tab title.
						if ( typeof page_generator_pro_generate_browser.browser_title !== 'undefined' ) {
							document.title = '🕐 ' + ( currentIndex + 1 ) + '/' + page_generator_pro_generate_browser.max_number_of_pages + ' ' + page_generator_pro_generate_browser.browser_title.processing;
						}
					} else {
						// Something went wrong.
						// Define message.
						var message = ( currentIndex + 1 ) + '/' + page_generator_pro_generate_browser.max_number_of_pages + ': Response Error: ' + response.data;
						switch ( page_generator_pro_generate_browser.stop_on_error ) {
							// Stop.
							case 1:
								break;

							// Continue, attempting to regenerate the Content or Term again.
							case 0:
								message = message + '. Waiting ' + ( page_generator_pro_generate_browser.stop_on_error_pause / 1000 ) + ' seconds before attempting to regenerate this item.';
								break;

							// Continue, skipping the failed Content or Term.
							case -1:
								message = message + '. Waiting ' + ( page_generator_pro_generate_browser.stop_on_error_pause / 1000 ) + ' seconds before generating the next item.';
								break;
						}

						// Output Log.
						$( '#log ul' ).append( '<li class="error">' + message + '</li>' );
					}

					// Run the next request, unless the user clicked the 'Stop Generation' button.
					if ( page_generator_pro_cancelled == true ) {
						return false;
					}

					// Run the next request.
					return true;

				},

				/**
				 * Called when an AJAX request results in a HTTP or server error.  Depending on the Plugin settings, generation may
				 * continue or stop.
				 *
				 * @since   3.1.8
				 */
				onRequestError: function ( xhr, textStatus, e, currentIndex ) {

					// If the log exceeds 100 items, reset it.
					if ( $( '#log ul li' ).length >= 100 ) {
						$( '#log ul' ).html( '' );
					}

					// Output Log.
					$( '#log ul' ).append( '<li class="error">' + ( currentIndex + 1 ) + '/' + page_generator_pro_generate_browser.number_of_requests + ': Request Error: ' + xhr.status + ' ' + xhr.statusText + '</li>' );

					// Run the next request, unless the user clicked the 'Stop Generation' button.
					if ( page_generator_pro_cancelled == true ) {
						return false;
					}

					// Try again.
					return true;

				},

				/**
				 * Called immediately before the next request is made.
				 *
				 * @since   3.1.8
				 *
				 * @param   object  settings    Settings.
				 * @return  object              Settings
				 */
				updateSettings: function ( settings ) {

					// If flag is false, the user went to navigate away but then decided to keep the browser window/tab open.
					// Set the flag to true again.
					if ( ! page_generator_pro_generating ) {
						pageGeneratorProGenerateBrowserStarted();
					}

					// Assign the last generated post's date and time to the settings data object, so it is included in the next AJAX request.
					settings.data.last_generated_post_date_time = page_generator_pro_response.data.last_generated_post_date_time;
					return settings;

				},

				/**
				 * Called when the entire generation routine has completed for all requests,
				 * or when the user cancelled generation manually.
				 *
				 * @since   3.1.8
				 */
				onFinished: function () {

					// Update flag as generation stopped.
					page_generator_pro_generating = false;

					// If the user clicked the 'Stop Generation' button, show that in the log.
					if ( page_generator_pro_cancelled == true ) {
						$( '#log ul' ).append( '<li class="success">Process cancelled by user</li>' );

						// Update browser tab title.
						if ( typeof page_generator_pro_generate_browser.browser_title !== 'undefined' ) {
							document.title = '🛑 ' + page_generator_pro_generate_browser.browser_title.cancelled;
						}
					} else {
						$( '#log ul' ).append( '<li class="success">Finished</li>' );

						// Update browser tab title.
						if ( typeof page_generator_pro_generate_browser.browser_title !== 'undefined' ) {
							document.title = '✅ ' + page_generator_pro_generate_browser.browser_title.success;
						}
					}

					// Hide the 'Stop Generation' button.
					$( 'a.page-generator-pro-generate-cancel-button' ).hide();

					// Show the 'Return to Group' button.
					$( 'a.page-generator-pro-generate-return-button' ).removeClass( 'page-generator-pro-generate-return-button' );

					// Send an AJAX request to remove the generating flag on the Group.
					pageGeneratorProGenerateBrowserStopped();

				}

			}
		);

		// Sets the page_generator_pro_cancelled flag to true when the user clicks the 'Stop Generation' button.
		$( 'a.page-generator-pro-generate-cancel-button' ).on(
			'click',
			function ( e ) {

				e.preventDefault();
				page_generator_pro_cancelled = true;

			}
		);

	} )( jQuery );

}

/**
 * Sends an AJAX request to set the generating flag on the Group.
 *
 * @since   3.7.0
 */
function pageGeneratorProGenerateBrowserStarted() {

	page_generator_pro_generating = true;

	( function ( $ ) {

		$.ajax(
			{
				url:        ajaxurl,
				type:       'POST',
				async:      true,
				data:      {
					id:     page_generator_pro_generate_browser.id,
					action: page_generator_pro_generate_browser.action_on_start,
				},
				error: function ( a, b, c ) {
				},
				success: function ( result ) {
				}
			}
		);

	} )( jQuery );

}

/**
 * Sends an AJAX request to remove the generating flag on the Group.
 *
 * @since   3.7.0
 */
function pageGeneratorProGenerateBrowserStopped() {

	page_generator_pro_generating = false;

	( function ( $ ) {

		$.ajax(
			{
				url:        ajaxurl,
				type:       'POST',
				async:      true,
				data:      {
					id:     page_generator_pro_generate_browser.id,
					action: page_generator_pro_generate_browser.action_on_finished,
				},
				error: function ( a, b, c ) {
				},
				success: function ( result ) {
				}
			}
		);

	} )( jQuery );

}

// Run.
pageGeneratorProGenerateBrowser();
