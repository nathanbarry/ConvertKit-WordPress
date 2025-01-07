/**
 * Handles the Insert and Cancel events on TinyMCE and QuickTag Modals
 *
 * @since   1.9.6
 *
 * @package ConvertKit
 * @author ConvertKit
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {

		// Cancel.
		document.body.addEventListener(
			'click',
			function ( e ) {

				// Check if a cancel button was clicked.
				if ( e.target.matches( '#convertkit-modal-body div.mce-cancel button, #convertkit-modal-body div.mce-cancel button *, #convertkit-quicktags-modal .media-toolbar .media-toolbar-secondary button.cancel' ) ) {

					// TinyMCE.
					if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
						tinymce.activeEditor.windowManager.close();
						return;
					}

					// Text Editor.
					// Close the QuickTags modal.
					convertKitQuickTagsModal.close();
				}
			}
		);

		// Insert.
		document.body.addEventListener(
			'click',
			function ( e ) {

				// Check if an insert button was clicked.
				if ( e.target.matches( '#convertkit-modal-body div.mce-insert button, #convertkit-modal-body div.mce-insert button *, #convertkit-quicktags-modal .media-toolbar .media-toolbar-primary button.button-primary' ) ||
					e.target.matches( 'button.wp-convertkit-refresh-resources, span.dashicons-update' ) ) {

					// Prevent default action.
					e.preventDefault();

					// Don't close the modal if the refresh button was clicked.
					if ( e.target.matches( 'button.wp-convertkit-refresh-resources, span.dashicons-update' ) ) {
						return;
					}

					// Get containing form.
					const form = document.querySelector( 'form.convertkit-tinymce-popup' );

					// Build Shortcode.
					let shortcode        = '[' + form.querySelector( 'input[name="shortcode"]' ).value;
					const shortcodeClose = form.querySelector( 'input[name="close_shortcode"]' ).value === '1';

					// Iterate through form fields.
					form.querySelectorAll( 'input, select' ).forEach(
						function ( element ) {
							// Skip if no data-shortcode attribute.
							if ( ! element.dataset.shortcode ) {
								return;
							}

							let val = '';

							// If this is a color picker, #000000 will be submitted by the browser as the value
							// even if no value / color was selected.
							// Check the data-value attribute instead.
							switch ( element.type ) {
								case 'color':
									val = element.dataset.value;
									break;
								default:
									val = element.value;
									break;
							}

							// Skip if the value is empty.
							if ( ! val ) {
								return;
							}
							if ( val.length === 0 ) {
								return;
							}

							// Get shortcode attribute.
							const key  = element.dataset.shortcode;
							const trim = element.dataset.trim !== '0';

							// Skip if the shortcode is empty.
							if ( ! key.length ) {
								return;
							}

							// Trim the value, unless the shortcode attribute disables string trimming.
							if ( trim ) {
								val = val.trim();
							}

							// Append attribute and value to shortcode string.
							shortcode += ' ' + key.trim() + '="' + val + '"';
						}
					);

					// Close Shortcode.
					shortcode += ']';

					// If the shortcode includes a closing element, append it now.
					if ( shortcodeClose ) {
						shortcode += '[/' + form.querySelector( 'input[name="shortcode"]' ).value + ']';
					}

					// Depending on the editor type, insert the shortcode.
					switch ( form.querySelector( 'input[name="editor_type"]' ).value ) {
						case 'tinymce':
							// Sanity check that a Visual editor exists and is active.
							if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
								// Insert into editor.
								tinyMCE.activeEditor.execCommand( 'mceReplaceContent', false, shortcode );

								// Close modal.
								tinyMCE.activeEditor.windowManager.close();
							}
							break;

						case 'quicktags':
							// Insert into editor.
							QTags.insertContent( shortcode );

							// Close modal.
							convertKitQuickTagsModal.close();
							break;
					}
				}
			}
		);

	}
);

// QuickTags: Setup Backbone Modal and Template.
if ( typeof wp !== 'undefined' && typeof wp.media !== 'undefined' ) {

	// Declared globally, as used in this file and quicktags.js.
	var convertKitQuickTagsModal          = new wp.media.view.Modal(
		{
			controller: { trigger: function () {} },
			className: 'convertkit-quicktags-modal'
		}
	);
	const convertKitQuickTagsModalContent = wp.Backbone.View.extend(
		{
			template: wp.template( 'convertkit-quicktags-modal' )
		}
	);
	convertKitQuickTagsModal.content( new convertKitQuickTagsModalContent() );

	/**
	 * Resets the content of the convertKitQuickTagsModal when closing.
	 *
	 * If this isn't performed, switching from Text to Visual Editor for the same shortcode results
	 * code picking up data from the QuickTags modal, not the TinyMCE one, due to this 'stale'
	 * modal remaining in the DOM, resulting in e.g. the tabbed UI not loading correctly.
	 */
	convertKitQuickTagsModal.on(
		'close',
		function ( e ) {
			this.content( new convertKitQuickTagsModalContent() );
		}
	);

}
