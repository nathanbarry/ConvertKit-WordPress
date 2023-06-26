/**
 * Registers blocks in the Gutenberg editor.
 *
 * @since   1.9.6.5
 *
 * @package ConvertKit
 * @author ConvertKit
 */

// Register Gutenberg Blocks if the Gutenberg Editor is loaded on screen.
// This prevents JS errors if this script is accidentally enqueued on a non-
// Gutenberg editor screen, or the Classic Editor Plugin is active.
if ( typeof wp !== 'undefined' &&
	typeof wp.blocks !== 'undefined' ) {

	// Register each ConvertKit Block in Gutenberg.
	for ( const block in convertkit_blocks ) {
		convertKitGutenbergRegisterBlock( convertkit_blocks[ block ] );
	}

}

/**
 * Registers the given block in Gutenberg.
 *
 * @since 	1.9.6.5
 *
 * @param 	object 	block 	Block
 */
function convertKitGutenbergRegisterBlock( block ) {

	( function( blocks, editor, element, components ) {

		// Define some constants for the various items we'll use.
		const el                    = element.createElement;
		const { registerBlockType } = blocks;
		const { InspectorControls } = editor;
		const { Fragment }          = element;
		const {
			Button,
			Dashicon,
			TextControl,
			SelectControl,
			ToggleControl,
			Panel,
			PanelBody,
			PanelRow
		}                           = components;

		/**
		 * Returns the icon to display for this block, depending
		 * on the supplied block's configuration.
		 *
		 * @since   2.2.0
		 *
		 * @return  element|string
		 */
		const getIcon = function() {

			// Return a fallback default icon if none is specified for this block.
			if ( typeof block.gutenberg_icon === 'undefined' ) {
				return 'dashicons-tablet';
			}

			// Return HTML element if the icon is an SVG string.
			if ( block.gutenberg_icon.search( 'svg' ) >= 0 ) {
				return element.RawHTML(
					{
						children: block.gutenberg_icon
					}
				);
			}

			// Just return the string, as it's a dashicon CSS class.
			return block.gutenberg_icon;

		}

		/**
		 * Return a field element for the block sidebar, which is displayed in a panel's row
		 * when this block is being edited.
		 *
		 * @since   2.2.0
		 *
		 * @param   object  props           Block properties.
		 * @param   object  field      		Field attributes.
		 * @param 	string 	attribute 		Attribute name to store the field's data in.
		 * @return  array                   Field element
		 */
		const getField = function( props, field, attribute ) {

			// Define some field properties shared across all field types.
			let fieldProperties = {
				id:  		'convertkit_' + block.name + '_' + attribute,
				label: 		field.label,
				help: 		field.description,
				value: 		props.attributes[ attribute ],
				onChange: 	function( value ) {
					if ( field.type === 'number' ) {
						// If value is a blank string i.e. no attribute value was provided,
						// cast it to the field's minimum number setting.
						// This prevents WordPress' block renderer API returning a 400 error
						// because a blank value will be passed as a string, when WordPress
						// expects it to be a numerical value.
						if ( value === '' ) {
							value = field.min;
						}

						// Cast value to integer if a value exists.
						if ( value.length > 0 ) {
							value = Number( value );
						}
					}

					let newValue          = {};
					newValue[ attribute ] = value;
					props.setAttributes( newValue );
				}
			};

			// Define additional Field Properties and the Field Element,
			// depending on the Field Type (select, textarea, text etc).
			switch ( field.type ) {

				case 'select':
					// Build options for <select> input.
					let fieldOptions = [];
					fieldOptions.push(
						{
							label: '(None)',
							value: '',
						}
					);
					for ( let value in field.values ) {
						fieldOptions.push(
							{
								label: field.values[ value ],
								value: value
							}
						);
					}

					// Sort field's options alphabetically by label.
					fieldOptions.sort(
						function ( x, y ) {

							let a = x.label.toUpperCase(),
							b     = y.label.toUpperCase();
							return a.localeCompare( b );

						}
					);

					// Assign options to field.
					fieldProperties.options = fieldOptions;

					// Return field element.
					return el(
						SelectControl,
						fieldProperties
					);
					break;

				case 'toggle':
					// Define field properties.
					fieldProperties.checked = props.attributes[ attribute ];

					// Return field element.
					return el(
						ToggleControl,
						fieldProperties
					);
					break;

				case 'number':
					// Define field properties.
					fieldProperties.type = field.type;
					fieldProperties.min  = field.min;
					fieldProperties.max  = field.max;
					fieldProperties.step = field.step;

					// Return field element.
					return el(
						TextControl,
						fieldProperties
					);
					break;

				default:
					// Return field element.
					return el(
						TextControl,
						fieldProperties
					);
					break;
			}

		}

		/**
		 * Return an array of rows to display in the given block sidebar's panel when
		 * this block is being edited.
		 *
		 * @since   2.2.0
		 *
		 * @param   object  props   Block properties.
		 * @param   string  panel 	Panel name.
		 * @return  array           Panel rows
		 */
		const getPanelRows = function( props, panel ) {

			// Build Inspector Control Panel Rows, one for each Field.
			let rows = [];
			for ( let i in block.panels[ panel ].fields ) {
				const attribute = block.panels[ panel ].fields[ i ], // e.g. 'term'.
					field       = block.fields[ attribute ]; // field array.

				// If this field doesn't exist as an attribute in the block's get_attributes(),
				// this is a non-Gutenberg field (such as a color picker for shortcodes),
				// which should be ignored.
				if ( typeof block.attributes[ attribute ] === 'undefined' ) {
					continue;
				}

				rows.push(
					el(
						PanelRow,
						{
							key: attribute
						},
						getField( props, field, attribute )
					)
				);
			}

			return rows;

		}

		/**
		 * Return an array of panels to display in the block's sidebar when the block
		 * is being edited.
		 *
		 * @since   2.2.0
		 *
		 * @param   object  props 	Block formatter properties.
		 * @return 	array 			Block sidebar panels.
		 */
		const getPanels = function( props ) {

			let panels      = [],
				initialOpen = true;

			// Build Inspector Control Panels.
			for ( const panel in block.panels ) {
				let panelRows = getPanelRows( props, panel );

				// If no panel rows exist (e.g. this is a shortcode only panel,
				// for styles, which Gutenberg registers in its own styles tab),
				// don't add this panel.
				if ( ! panelRows.length ) {
					continue;
				}

				panels.push(
					el(
						PanelBody,
						{
							title: block.panels[ panel ].label,
							key: panel,
							initialOpen: initialOpen
						},
						panelRows
					)
				);

				// Don't open any further panels.
				initialOpen = false;
			}

			return panels;

		}

		/**
		 * Display settings sidebar when the block is being edited, and save
		 * changes that are made.
		 *
		 * @since   2.2.0
		 *
		 * @param   object  props   Block properties.
		 * @return  object          Block settings sidebar elements
		 */
		const editBlock = function( props ) {

			// If requesting an example of how this block looks (which is requested
			// when the user adds a new block and hovers over this block's icon),
			// show the preview image.
			if ( props.attributes.is_gutenberg_example === true ) {
				return (
					Fragment,
					{},
					el(
						'img',
						{
							src: block.gutenberg_example_image,
						}
					)
				);
			}

			// Build Inspector Control Panels, which will appear in the Sidebar when editing the Block.
			let panels = getPanels( props );

			// Generate Block Preview.
			let preview = '';

			// If no API Key has been defined in the Plugin, or no resources exist in ConvertKit
			// for this block, show a message in the block to tell the user what to do.
			if ( ! block.has_api_key || ! block.has_resources ) {
				return displayNoticeWithLink( props );
			}

			if ( typeof block.gutenberg_preview_render_callback !== 'undefined' ) {
				// Use a custom callback function to render this block's preview in the Gutenberg Editor.
				// This doesn't affect the output for this block on the frontend site, which will always
				// use the block's PHP's render() function.
				preview = window[ block.gutenberg_preview_render_callback ]( block, props );
			}

			// Return settings sidebar panel with fields and the block preview.
			return (
				el(
					// Sidebar Panel with Fields.
					Fragment,
					{},
					el(
						InspectorControls,
						{},
						panels
					),
					// Block Preview.
					preview
				)
			);

		}

		/**
		 * Display a notice in the block with a clickable link to perform an action, and a refresh
		 * button to trigger editBlock().  Typically used when no API key exists in the Plugin,
		 * or no resources (forms, products) exist in ConvertKit.
		 *
		 * @since 	2.2.5
		 *
		 * @param   object  props   Block properties.
		 * @return  object          Notice.
		 */
		const displayNoticeWithLink = function( props ) {

			// Build notice, depending on the type of notice that needs displaying.
			let notice    = '',
				link      = '',
				link_text = '';
			if ( ! block.has_api_key ) {
				notice    = block.no_api_key.notice;
				link      = block.no_api_key.link;
				link_text = block.no_api_key.link_text;
			} else {
				notice    = block.no_resources.notice;
				link      = block.no_resources.link;
				link_text = block.no_resources.link_text;
			}

			// Return the element with the notice and refresh button.
			return el(
				'div',
				{
					// convertkit-no-content class allows resources/backend/css/gutenberg.css
					// to apply styling/branding to the block.
					className: 'convertkit-' + block.name + ' convertkit-no-content'
				},
				[
					notice + ' ',
					el(
						'a',
						{
							href: link,
							target: '_blank'
						},
						link_text
					),
					getRefreshButton( props )
				]
			);

		}

		/**
		 * Returns a refresh button.
		 * 
		 * @since 	2.2.6
		 * 
		 * @param 	object 	props 	Block properties.
		 * @return 	object 			Button.
		 */
		const getRefreshButton = function( props ) {

			return el(
				Button,
				{
					className: 'button button-secondary convertkit-block-refresh',
					text: 'Refresh',
					icon: el(
						Dashicon,
						{
							icon: 'update'
						}
					),
					onClick: function( e ) {

						// Disable button to prevent multiple clicks.
						e.target.disabled = true;

						// Refresh block definitions.
						refreshBlocksDefinitions( props, e.target );

					}
				}
			)

		}

		/**
		 * Refreshes this block's properties by:
		 * - making an AJAX call to fetch all registered blocks via convertkit_get_blocks(),
		 * - storing the registered blocks in the `convertkit_blocks` global object,
		 * - updating this block's properties by updating the `block` object.
		 *
		 * @since 	2.2.5
		 *
		 * @param   object  props   Block properties.
		 * @param 	object  button 	Refresh button.
		 * @return  object          Notice.
		 */
		const refreshBlocksDefinitions = function( props, button ) {

			jQuery.ajax(
				{
					type: 'POST',
					data: {
						action: 'convertkit_get_blocks',
						nonce: convertkit_gutenberg.get_blocks_nonce,
					},
					url: ajaxurl,
					success: function ( response ) {

						// Update global ConvertKit Blocks object, so that any updated resources
						// are reflected when adding new ConvertKit Blocks.
						convertkit_blocks = response.data;

						// Update this block's properties, so that has_api_key, has_resources
						// and the resources properties are updated.
						block = convertkit_blocks[ block.name ];

						// Call setAttributes on props to trigger the editBlock() function, which will re-render
						// the block, reflecting any changes to its properties.
						props.setAttributes(
							{
								refresh: Date.now()
							}
						);

						// Enable refresh button.
						button.disabled = false;

					}
				}
			).fail(
				function ( response ) {

					// Show an error in the Gutenberg editor.
					wp.data.dispatch( 'core/notices' ).createErrorNotice(
						'ConvertKit: ' + response.status + ' ' + response.statusText,
						{
							id: 'convertkit-error'
						}
					);

					// Enable refresh button.
					button.disabled = false;

				}
			);

		}

		// Register Block.
		registerBlockType(
			'convertkit/' + block.name,
			{
				title:      block.title,
				description:block.description,
				category:   block.category,
				icon:       getIcon,
				keywords: 	block.keywords,
				attributes: block.attributes,
				supports: 	block.supports,
				example: 	{
					attributes: {
						is_gutenberg_example: true,
					}
				},

				// Editor.
				edit: editBlock,

				// Output.
				save: function( props ) {

					// Deliberate; preview in the editor is determined by the return statement in `edit` above.
					// On the frontend site, the block's render() PHP class is always called, so we dynamically
					// fetch the content.
					return null;

				},
			}
		);

	} (
		window.wp.blocks,
		window.wp.blockEditor,
		window.wp.element,
		window.wp.components
	) );

}

/**
 * Outputs a notice for the block.  Typically used when a block's settings
 * have not been defined, no API key exists in the Plugin or no resources
 * (forms, products) exist in ConvertKit, and the user adds an e.g.
 * Form / Product block.
 *
 * @since 	2.2.3
 *
 * @param 	string 	block_name 	Block Name.
 * @param 	string 	notice 		Notice to display.
 * @return 	object 				HTMLElement
 */
function convertKitGutenbergDisplayBlockNotice( block_name, notice ) {

	return wp.element.createElement(
		'div',
		{
			// convertkit-no-content class allows resources/backend/css/gutenberg.css
			// to apply styling/branding to the block.
			className: 'convertkit-' + block_name + ' convertkit-no-content'
		},
		notice
	);

}
