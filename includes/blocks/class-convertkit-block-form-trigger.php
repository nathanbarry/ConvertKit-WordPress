<?php
/**
 * ConvertKit Form Trigger Button Block class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * ConvertKit Form Trigger Button Block for Gutenberg and Shortcode.
 *
 * @package ConvertKit
 * @author  ConvertKit
 */
class ConvertKit_Block_Form_Trigger extends ConvertKit_Block {

	/**
	 * Constructor
	 *
	 * @since   2.2.0
	 */
	public function __construct() {

		// Register this as a shortcode in the ConvertKit Plugin.
		add_filter( 'convertkit_shortcodes', array( $this, 'register' ) );

		// Register this as a Gutenberg block in the ConvertKit Plugin.
		add_filter( 'convertkit_blocks', array( $this, 'register' ) );

		// Enqueue scripts and styles for this Gutenberg Block in the editor view.
		add_action( 'convertkit_gutenberg_enqueue_scripts', array( $this, 'enqueue_scripts_editor' ) );

		// Enqueue scripts and styles for this Gutenberg Block in the editor and frontend views.
		add_action( 'convertkit_gutenberg_enqueue_styles_editor_and_frontend', array( $this, 'enqueue_styles' ) );

	}

	/**
	 * Enqueues scripts for this Gutenberg Block in the editor view.
	 *
	 * @since   2.2.0
	 */
	public function enqueue_scripts_editor() {

		wp_enqueue_script( 'convertkit-gutenberg-block-form-trigger', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/gutenberg-block-form-trigger.js', array( 'convertkit-gutenberg' ), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Enqueues styles for this Gutenberg Block in the editor and frontend views.
	 *
	 * @since   2.2.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'convertkit-button', CONVERTKIT_PLUGIN_URL . 'resources/frontend/css/button.css', array(), CONVERTKIT_PLUGIN_VERSION );

	}

	/**
	 * Returns this block's programmatic name, excluding the convertkit- prefix.
	 *
	 * @since   2.2.0
	 */
	public function get_name() {

		/**
		 * This will register as:
		 * - a shortcode, with the name [convertkit_formtrigger].
		 * - a Gutenberg block, with the name convertkit/formtrigger.
		 */
		return 'formtrigger';

	}

	/**
	 * Returns this block's Title, Icon, Categories, Keywords and properties.
	 *
	 * @since   2.2.0
	 */
	public function get_overview() {

		$convertkit_forms = new ConvertKit_Resource_Forms( 'block_edit' );
		$settings         = new ConvertKit_Settings();

		return array(
			'title'                             => __( 'Kit Form Trigger', 'convertkit' ),
			'description'                       => __( 'Displays a modal, sticky bar or slide in form to display when the button is pressed.', 'convertkit' ),
			'icon'                              => 'resources/backend/images/block-icon-formtrigger.svg',
			'category'                          => 'convertkit',
			'keywords'                          => array(
				__( 'ConvertKit', 'convertkit' ),
				__( 'Kit', 'convertkit' ),
				__( 'Form', 'convertkit' ),
			),

			// Function to call when rendering as a block or a shortcode on the frontend web site.
			'render_callback'                   => array( $this, 'render' ),

			// Shortcode: TinyMCE / QuickTags Modal Width and Height.
			'modal'                             => array(
				'width'  => 500,
				'height' => 282,
			),

			// Shortcode: Include a closing [/shortcode] tag when using TinyMCE or QuickTag Modals.
			'shortcode_include_closing_tag'     => false,

			// Gutenberg: Block Icon in Editor.
			'gutenberg_icon'                    => convertkit_get_file_contents( CONVERTKIT_PLUGIN_PATH . '/resources/backend/images/block-icon-formtrigger.svg' ),

			// Gutenberg: Example image showing how this block looks when choosing it in Gutenberg.
			'gutenberg_example_image'           => CONVERTKIT_PLUGIN_URL . 'resources/backend/images/block-example-formtrigger.png',

			// Help descriptions, displayed when no API key / resources exist and this block/shortcode is added.
			'no_access_token'                   => array(
				'notice'           => __( 'Not connected to Kit.', 'convertkit' ),
				'link'             => convertkit_get_setup_wizard_plugin_link(),
				'link_text'        => __( 'Click here to connect your Kit account.', 'convertkit' ),
				'instruction_text' => __( 'Connect your Kit account at Settings > Kit, and then refresh this page to select a form.', 'convertkit' ),
			),
			'no_resources'                      => array(
				'notice'           => __( 'No modal, sticky bar or slide in forms exist in Kit.', 'convertkit' ),
				'link'             => convertkit_get_new_form_url(),
				'link_text'        => __( 'Click here to create a form.', 'convertkit' ),
				'instruction_text' => __( 'Add a non-inline form to your Kit account, and then refresh this page to select a form.', 'convertkit' ),
			),
			'gutenberg_help_description'        => __( 'Select a Form using the Form option in the Gutenberg sidebar.', 'convertkit' ),

			// Gutenberg: JS function to call when rendering the block preview in the Gutenberg editor.
			// If not defined, render_callback above will be used.
			'gutenberg_preview_render_callback' => 'convertKitGutenbergFormTriggerBlockRenderPreview',

			// Whether an API Key exists in the Plugin, and are the required resources (non-inline forms) available.
			// If no API Key is specified in the Plugin's settings, render the "No API Key" output.
			'has_access_token'                  => $settings->has_access_and_refresh_token(),
			'has_resources'                     => $convertkit_forms->non_inline_exist(),
		);

	}

	/**
	 * Returns this block's Attributes
	 *
	 * @since   2.2.0
	 */
	public function get_attributes() {

		return array(
			// Block attributes.
			'form'                 => array(
				'type'    => 'string',
				'default' => $this->get_default_value( 'form' ),
			),
			'text'                 => array(
				'type'    => 'string',
				'default' => $this->get_default_value( 'text' ),
			),

			// The below are built in Gutenberg attributes registered in get_supports().

			// Color.
			'backgroundColor'      => array(
				'type' => 'string',
			),
			'textColor'            => array(
				'type' => 'string',
			),

			// Typography.
			'fontSize'             => array(
				'type' => 'string',
			),

			// Spacing/Dimensions > Padding.
			'style'                => array(
				'type'        => 'object',
				'visualizers' => array(
					'type'    => 'object',
					'padding' => array(
						'type'   => 'object',
						'top'    => array(
							'type' => 'boolean',
						),
						'bottom' => array(
							'type' => 'boolean',
						),
						'left'   => array(
							'type' => 'boolean',
						),
						'right'  => array(
							'type' => 'boolean',
						),
					),
				),
			),

			// Always required for Gutenberg.
			'is_gutenberg_example' => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);

	}

	/**
	 * Returns this block's supported built-in Attributes.
	 *
	 * @since   2.2.0
	 *
	 * @return  array   Supports
	 */
	public function get_supports() {

		return array(
			'className'  => true,
			'color'      => array(
				'background'                      => true,
				'text'                            => true,

				// Don't apply styles to the block editor's div element.
				// This ensures what's rendered in the Gutenberg editor matches the frontend output for styling.
				// See: https://github.com/WordPress/gutenberg/issues/32417.
				'__experimentalSkipSerialization' => true,
			),
			'typography' => array(
				'fontSize' => true,
			),
			'spacing'    => array(
				'padding' => array(
					'horizontal',
					'vertical',
				),
			),
		);

	}

	/**
	 * Returns this block's Fields
	 *
	 * @since   2.2.0
	 *
	 * @return  bool|array
	 */
	public function get_fields() {

		// Bail if the request is not for the WordPress Administration or frontend editor.
		if ( ! WP_ConvertKit()->is_admin_or_frontend_editor() ) {
			return false;
		}

		// Get non-inline ConvertKit Forms.
		$forms            = array();
		$convertkit_forms = new ConvertKit_Resource_Forms( 'block_edit' );
		if ( $convertkit_forms->non_inline_exist() ) {
			foreach ( $convertkit_forms->get_non_inline() as $form ) {
				// Legacy forms don't include a `format` key, so define them as inline.
				$forms[ absint( $form['id'] ) ] = sprintf(
					'%s [%s]',
					sanitize_text_field( $form['name'] ),
					( ! empty( $form['format'] ) ? sanitize_text_field( $form['format'] ) : 'inline' )
				);
			}
		}

		// Gutenberg's built-in fields (such as styling, padding etc) don't need to be defined here, as they'll be included
		// automatically by Gutenberg.
		return array(
			'form'             => array(
				'label'       => __( 'Form', 'convertkit' ),
				'type'        => 'resource',
				'resource'    => 'forms',
				'values'      => $forms,
				'description' => __( 'The modal, sticky bar or slide in form to display when the button is pressed. To embed a form, use the Kit Form block instead.', 'convertkit' ),
			),
			'text'             => array(
				'label'       => __( 'Button Text', 'convertkit' ),
				'type'        => 'text',
				'description' => __( 'The text to display for the button.', 'convertkit' ),
			),

			// These fields will only display on the shortcode, and are deliberately not registered in get_attributes(),
			// because Gutenberg will register its own color pickers for link, background and text.
			'background_color' => array(
				'label' => __( 'Background color', 'convertkit' ),
				'type'  => 'color',
			),
			'text_color'       => array(
				'label' => __( 'Text color', 'convertkit' ),
				'type'  => 'color',
			),
		);

	}

	/**
	 * Returns this block's UI panels / sections.
	 *
	 * @since   2.2.0
	 *
	 * @return  bool|array
	 */
	public function get_panels() {

		// Bail if the request is not for the WordPress Administration or frontend editor.
		if ( ! WP_ConvertKit()->is_admin_or_frontend_editor() ) {
			return false;
		}

		// Gutenberg's built-in fields (such as styling, padding etc) don't need to be defined here, as they'll be included
		// automatically by Gutenberg.
		return array(
			'general' => array(
				'label'  => __( 'General', 'convertkit' ),
				'fields' => array(
					'form',
					'text',
					'background_color',
					'text_color',
				),
			),
		);

	}

	/**
	 * Returns this block's Default Values
	 *
	 * @since   2.2.0
	 *
	 * @return  array
	 */
	public function get_default_values() {

		return array(
			'form'             => '',
			'text'             => __( 'Subscribe', 'convertkit' ),
			'background_color' => '',
			'text_color'       => '',

			// Built-in Gutenberg block attributes.
			'backgroundColor'  => '',
			'textColor'        => '',
			'fontSize'         => '',
			'style'            => array(
				'visualizers' => array(
					'padding' => array(
						'top'    => '',
						'bottom' => '',
						'left'   => '',
						'right'  => '',
					),
				),
			),
		);

	}

	/**
	 * Returns the block's output, based on the supplied configuration attributes.
	 *
	 * @since   2.2.0
	 *
	 * @param   array $atts   Block / Shortcode Attributes.
	 * @return  string          Output
	 */
	public function render( $atts ) {

		// Parse attributes, defining fallback defaults if required
		// and moving some attributes (such as Gutenberg's styles), if defined.
		$atts = $this->sanitize_and_declare_atts( $atts );

		// Setup Settings class.
		$settings = new ConvertKit_Settings();

		// Build HTML.
		$html = $this->get_html( $atts['form'], $atts['text'], $atts['_css_classes'], $atts['_css_styles'], $this->is_block_editor_request() );

		// Bail if an error occured.
		if ( is_wp_error( $html ) ) {
			if ( $settings->debug_enabled() ) {
				return '<!-- ' . $html->get_error_message() . ' -->';
			}

			return '';
		}

		/**
		 * Filter the block's content immediately before it is output.
		 *
		 * @since   2.2.0
		 *
		 * @param   string  $html   ConvertKit Button HTML.
		 * @param   array   $atts   Block Attributes.
		 */
		$html = apply_filters( 'convertkit_block_form_trigger_render', $html, $atts );

		return $html;

	}

	/**
	 * Returns the HTML button markup for the given Form ID.
	 *
	 * @since   2.0.0
	 *
	 * @param   int    $id             Form ID.
	 * @param   string $button_text    Button Text.
	 * @param   array  $css_classes    CSS classes to apply to link (typically included when using Gutenberg).
	 * @param   array  $css_styles     CSS inline styles to apply to link (typically included when using Gutenberg).
	 * @param   bool   $return_as_span If true, returns a <span> instead of <a>. Useful for the block editor so that the element is interactible.
	 * @return  WP_Error|string        Button HTML
	 */
	private function get_html( $id, $button_text, $css_classes = array(), $css_styles = array(), $return_as_span = false ) {

		// Cast ID to integer.
		$id = absint( $id );

		// Load classes.
		$convertkit_forms = new ConvertKit_Resource_Forms( 'render' );

		// Get form.
		$form = $convertkit_forms->get_by_id( $id );

		// Bail if the form could not be found.
		if ( ! $form ) {
			return new WP_Error(
				'convertkit_block_form_trigger_get_html',
				sprintf(
					/* translators: ConvertKit Form ID */
					__( 'Kit Form ID %s does not exist on Kit.', 'convertkit' ),
					$id
				)
			);
		}

		// Bail if no uid or embed_js properties exist.
		if ( ! array_key_exists( 'uid', $form ) ) {
			return new WP_Error(
				'convertkit_block_form_trigger_get_html',
				sprintf(
					/* translators: ConvertKit Form ID */
					__( 'Kit Form ID %s has no uid property.', 'convertkit' ),
					$id
				)
			);
		}
		if ( ! array_key_exists( 'embed_js', $form ) ) {
			return new WP_Error(
				'convertkit_block_form_trigger_get_html',
				sprintf(
					/* translators: ConvertKit Form ID */
					__( 'Kit Form ID %s has no embed_js property.', 'convertkit' ),
					$id
				)
			);
		}

		// Build button HTML.
		$html = '<div class="convertkit-button">';

		if ( $return_as_span ) {
			$html .= '<span';
		} else {
			$html .= '<a data-formkit-toggle="' . esc_attr( $form['uid'] ) . '" href="' . esc_url( $form['embed_url'] ) . '"';
		}

		$html .= ' class="wp-block-button__link ' . implode( ' ', map_deep( $css_classes, 'sanitize_html_class' ) ) . '" style="' . implode( ';', map_deep( $css_styles, 'esc_attr' ) ) . '">';
		$html .= esc_html( $button_text );

		if ( $return_as_span ) {
			$html .= '</span>';
		} else {
			$html .= '</a>';
		}

		$html .= '</div>';

		// Register the script, so it's only loaded once for this non-inline form across the entire page.
		add_filter(
			'convertkit_output_scripts_footer',
			function ( $scripts ) use ( $form ) {

				$scripts[] = array(
					'async'    => true,
					'data-uid' => $form['uid'],
					'src'      => $form['embed_js'],
				);

				return $scripts;

			}
		);

		// Return.
		return $html;
	}

}
