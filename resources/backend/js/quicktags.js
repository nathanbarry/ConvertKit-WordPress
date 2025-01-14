/**
 * Registers Blocks in the text editor as QuickTag Buttons.
 *
 * @since   1.9.6
 *
 * @package ConvertKit
 * @author ConvertKit
 */

for ( const block in convertkit_quicktags ) {

	convertKitQuickTagRegister( convertkit_quicktags[ block ] );

}

/**
 * Registers the given block as a Quick Tag, with a button in
 * the Text Editor toolbar.
 *
 * @since 	1.9.6
 *
 * @param 	object 	block 	Block
 */
function convertKitQuickTagRegister( block ) {

	QTags.addButton(
		'convertkit-' + block.name,
		block.title,
		function () {

			// Perform an AJAX call to load the modal's view.
			fetch(
				ajaxurl,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams(
						{
							'action': 		'convertkit_admin_tinymce_output_modal',
							'nonce':  		convertkit_admin_tinymce.nonce,
							'editor_type':  'quicktags',
							'shortcode': 	block.name
						}
					),
				}
			)
			.then(
				function ( response ) {
					return response.text();
				}
			)
			.then(
				function ( result ) {
					// Show Modal.
					convertKitQuickTagsModal.open();

					// Get Modal.
					const quicktagsModal         = document.querySelector( 'div.convertkit-quicktags-modal div.media-modal.wp-core-ui' ),
							quicktagsModalHeader = quicktagsModal.querySelector( 'div.media-frame-title' ),
							quicktagsModalFooter = quicktagsModal.querySelector( 'div.media-frame-toolbar div.media-toolbar' );

					// Resize Modal so it's not full screen.
					quicktagsModal.style.width  = block.modal.width + 'px';
					quicktagsModal.style.height = block.modal.height + quicktagsModalHeader.offsetHeight + quicktagsModalFooter.offsetHeight + 'px'; // Prevents a vertical scroll bar.

					// Set Title.
					document.querySelector( '#convertkit-quicktags-modal .media-frame-title h1' ).textContent = block.title;

					// Inject HTML into modal.
					document.querySelector( '#convertkit-quicktags-modal .media-frame-content' ).innerHTML = result;

					// Initialize tabbed interface.
					convertKitTabsInit();

					// Listen for color input changes.
					convertKitColorInputInit();

					// Bind refresh resource event listeners.
					convertKitRefreshResourcesInitEventListeners();
				}
			)
			.catch(
				function ( error ) {
					console.error( error );
				}
			);

		}
	);

}
