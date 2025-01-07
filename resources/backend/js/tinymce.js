/**
 * Handles registration of TinyMCE buttons.
 *
 * @since   1.9.6
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers the given block as a TinyMCE Plugin, with a button in
 * the Visual Editor toolbar.
 *
 * @since 	1.9.6
 *
 * @param 	object 	block 	Block
 */
function convertKitTinyMCERegisterPlugin( block ) {

	tinymce.PluginManager.add(
		'convertkit_' + block.name,
		function ( editor, url ) {

			// Add Button to Visual Editor Toolbar.
			editor.addButton(
				'convertkit_' + block.name,
				{
					title: 	block.title,
					image: 	url + '../../../../' + block.icon,
					cmd: 	'convertkit_' + block.name,
				}
			);

			// Load View when button clicked.
			editor.addCommand(
				'convertkit_' + block.name,
				function () {

					// Close any existing QuickTags modal.
					convertKitQuickTagsModal.close();

					// Open the TinyMCE Modal.
					editor.windowManager.open(
						{
							id: 	'convertkit-modal-body',
							title: 	block.title,
							width: 	block.modal.width,

							// Set modal height up to a maximum of 580px.
							// Content will overflow-y to show a scrollbar where necessary.
							height: ( block.modal.height < 580 ? block.modal.height : 580 ),
							inline: 1,
							buttons: [
								{
									text: 'Cancel',
									classes: 'cancel'
							},
								{
									text: 'Insert',
									subtype: 'primary',
									classes: 'insert'
							}
							]
						}
					);

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
									'editor_type':  'tinymce',
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
							// Inject HTML into modal.
							document.querySelector( '#convertkit-modal-body-body' ).innerHTML = result;

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
	);

}

/**
 * Listens for changes to input[type=color] inputs within a TinyMCE or QuickTags modal,
 * assigning the value to the input's data-value attribute.
 *
 * The modal.js will check the data-value for the 'true' value, as any color input will always
 * send #000000 as the value, even when none is supplied.
 *
 * @since 	2.4.3
 */
function convertKitColorInputInit() {

	document.querySelectorAll( '.convertkit-option input[type=color]' ).forEach(
		function ( colorPicker ) {
			colorPicker.addEventListener(
				'change',
				function ( event ) {
					event.target.dataset.value = event.target.value;
				}
			);
		}
	);

}
