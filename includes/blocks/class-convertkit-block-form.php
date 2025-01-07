<?php
/**
 * ConvertKit Form Block class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * ConvertKit Form Block for Gutenberg and Shortcode.
 *
 * @package ConvertKit
 * @author  ConvertKit
 */
class ConvertKit_Block_Form extends ConvertKit_Block {

	/**
	 * Constructor
	 *
	 * @since   1.9.6
	 */
	public function __construct() {

		// Register this as a shortcode in the ConvertKit Plugin.
		add_filter( 'convertkit_shortcodes', array( $this, 'register' ) );

		// Register this as a Gutenberg block in the ConvertKit Plugin.
		add_filter( 'convertkit_blocks', array( $this, 'register' ) );

		// Enqueue scripts for this Gutenberg Block in the editor view.
		add_action( 'convertkit_gutenberg_enqueue_scripts', array( $this, 'enqueue_scripts_editor' ) );

		// Enqueue styles for this Gutenberg Block in the editor view.
		add_action( 'convertkit_gutenberg_enqueue_styles', array( $this, 'enqueue_styles_editor' ) );

		// Enqueue scripts and styles for this Gutenberg Block in the editor and frontend views.
		add_action( 'convertkit_gutenberg_enqueue_styles_editor_and_frontend', array( $this, 'enqueue_styles' ) );

	}

	/**
	 * Enqueues scripts for this Gutenberg Block in the editor view.
	 *
	 * @since   1.9.6.5
	 */
	public function enqueue_scripts_editor() {

		wp_enqueue_script( 'convertkit-gutenberg-block-form', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/gutenberg-block-form.js', array( 'convertkit-gutenberg' ), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Enqueues styles for this Gutenberg Block in the editor view.
	 *
	 * @since   1.9.6.9
	 */
	public function enqueue_styles_editor() {

		wp_enqueue_style( 'convertkit-gutenberg', CONVERTKIT_PLUGIN_URL . 'resources/backend/css/gutenberg.css', array( 'wp-edit-blocks' ), CONVERTKIT_PLUGIN_VERSION );

	}

	/**
	 * Enqueues styles for this Gutenberg Block in the editor and frontend views.
	 *
	 * @since   2.3.3
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'convertkit-form', CONVERTKIT_PLUGIN_URL . 'resources/frontend/css/form.css', array(), CONVERTKIT_PLUGIN_VERSION );

	}

	/**
	 * Returns this block's programmatic name, excluding the convertkit- prefix.
	 *
	 * @since   1.9.6
	 *
	 * @return  string
	 */
	public function get_name() {

		/**
		 * This will register as:
		 * - a shortcode, with the name [convertkit_form].
		 * - a shortcode, with the name [convertkit], for backward compat.
		 * - a Gutenberg block, with the name convertkit/form.
		 */
		return 'form';

	}

	/**
	 * Returns this block's Title, Icon, Categories, Keywords and properties.
	 *
	 * @since   1.9.6
	 *
	 * @return  array
	 */
	public function get_overview() {

		$convertkit_forms = new ConvertKit_Resource_Forms( 'block_edit' );
		$settings         = new ConvertKit_Settings();

		return array(
			'title'                             => __( 'Kit Form', 'convertkit' ),
			'description'                       => __( 'Displays a Kit Form.', 'convertkit' ),
			'icon'                              => 'resources/backend/images/block-icon-form.svg',
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
				'height' => 55,
			),

			// Shortcode: Include a closing [/shortcode] tag when using TinyMCE or QuickTag Modals.
			'shortcode_include_closing_tag'     => false,

			// Gutenberg: Block Icon in Editor.
			'gutenberg_icon'                    => convertkit_get_file_contents( CONVERTKIT_PLUGIN_PATH . '/resources/backend/images/block-icon-form.svg' ),

			// Gutenberg: Example image showing how this block looks when choosing it in Gutenberg.
			'gutenberg_example_image'           => CONVERTKIT_PLUGIN_URL . 'resources/backend/images/block-example-form.png',

			// Help descriptions, displayed when no API key / resources exist and this block/shortcode is added.
			'no_access_token'                   => array(
				'notice'           => __( 'Not connected to Kit.', 'convertkit' ),
				'link'             => convertkit_get_setup_wizard_plugin_link(),
				'link_text'        => __( 'Click here to connect your Kit account.', 'convertkit' ),
				'instruction_text' => __( 'Connect your Kit account at Settings > Kit, and then refresh this page to select a form.', 'convertkit' ),
			),
			'no_resources'                      => array(
				'notice'           => __( 'No forms exist in Kit.', 'convertkit' ),
				'link'             => convertkit_get_new_form_url(),
				'link_text'        => __( 'Click here to create your first form.', 'convertkit' ),
				'instruction_text' => __( 'Add a form to your Kit account, and then refresh this page to select a form.', 'convertkit' ),
			),

			// Gutenberg: Help descriptions, displayed when no settings defined for a newly added Block.
			'gutenberg_help_description'        => __( 'Select a Form using the Form option in the Gutenberg sidebar.', 'convertkit' ),

			// Gutenberg: JS function to call when rendering the block preview in the Gutenberg editor.
			// If not defined, render_callback above will be used.
			'gutenberg_preview_render_callback' => 'convertKitGutenbergFormBlockRenderPreview',

			// General: Any other strings for use in JS that need to support translation / i18n.
			'i18n'                              => array(
				/* translators: Form name in ConvertKit */
				'gutenberg_form_modal'      => __( 'Modal form "%s" selected. View on the frontend site to see the modal form.', 'convertkit' ),

				/* translators: Form name in ConvertKit */
				'gutenberg_form_slide_in'   => __( 'Slide in form "%s" selected. View on the frontend site to see the slide in form.', 'convertkit' ),

				/* translators: Form name in ConvertKit */
				'gutenberg_form_sticky_bar' => __( 'Sticky bar form "%s" selected. View on the frontend site to see the sticky bar form.', 'convertkit' ),
			),

			// Whether an API Key exists in the Plugin, and are the required resources (forms) available.
			// If no API Key is specified in the Plugin's settings, render the "No API Key" output.
			'has_access_token'                  => $settings->has_access_and_refresh_token(),
			'has_resources'                     => $convertkit_forms->exist(),
		);

	}

	/**
	 * Returns this block's Attributes
	 *
	 * @since   1.9.6.5
	 *
	 * @return  array
	 */
	public function get_attributes() {

		return array(
			'form'                 => array(
				'type' => 'string',
			),

			// Always required for Gutenberg.
			'is_gutenberg_example' => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);

	}

	/**
	 * Returns this block's Fields
	 *
	 * @since   1.9.6
	 *
	 * @return  bool|array
	 */
	public function get_fields() {

		// Bail if the request is not for the WordPress Administration or frontend editor.
		if ( ! WP_ConvertKit()->is_admin_or_frontend_editor() ) {
			return false;
		}

		// Get ConvertKit Forms.
		$forms            = array();
		$convertkit_forms = new ConvertKit_Resource_Forms( 'block_edit' );
		if ( $convertkit_forms->exist() ) {
			foreach ( $convertkit_forms->get() as $form ) {
				// Legacy forms don't include a `format` key, so define them as inline.
				$forms[ absint( $form['id'] ) ] = sprintf(
					'%s [%s]',
					sanitize_text_field( $form['name'] ),
					( ! empty( $form['format'] ) ? sanitize_text_field( $form['format'] ) : 'inline' )
				);
			}
		}

		return array(
			'form' => array(
				'label'    => __( 'Form', 'convertkit' ),
				'type'     => 'resource',
				'resource' => 'forms',
				'values'   => $forms,
				'data'     => array(
					// Used by resources/backend/js/gutenberg-block-form.js to determine the selected form's format
					// (modal, slide in, sticky bar) and output a message in the block editor for the preview to explain
					// why some formats cannot be previewed.
					'forms' => ( $convertkit_forms->exist() ? $convertkit_forms->get() : array() ),
				),
			),
		);

	}

	/**
	 * Returns this block's UI panels / sections.
	 *
	 * @since   1.9.6
	 *
	 * @return  bool|array
	 */
	public function get_panels() {

		// Bail if the request is not for the WordPress Administration or frontend editor.
		if ( ! WP_ConvertKit()->is_admin_or_frontend_editor() ) {
			return false;
		}

		return array(
			'general' => array(
				'label'  => __( 'General', 'convertkit' ),
				'fields' => array(
					'form',
				),
			),
		);

	}

	/**
	 * Returns this block's Default Values
	 *
	 * @since   1.9.6
	 *
	 * @return  array
	 */
	public function get_default_values() {

		return array(
			'form' => '',
			'id'   => '', // Backward compat.
		);

	}

	/**
	 * Returns the block's output, based on the supplied configuration attributes.
	 *
	 * @since   1.9.6
	 *
	 * @param   array $atts   Block / Shortcode Attributes.
	 * @return  string          Output
	 */
	public function render( $atts ) {

		// Check if the Block Visibility Plugin permits displaying this block.
		if ( ! $this->is_block_visible( $atts ) ) {
			// Block should not be displayed due to Block Visibility Plugin conditions.
			// Return a blank string now.
			return '';
		}

		// Parse shortcode attributes, defining fallback defaults if required.
		$atts = shortcode_atts(
			$this->get_default_values(),
			$this->sanitize_atts( $atts ),
			$this->get_name()
		);

		// Setup Settings class.
		$settings = new ConvertKit_Settings();

		// Determine Form ID.
		// 'id' attribute is for backward compat.
		$form_id = 0;
		if ( $atts['form'] > 0 ) {
			$form_id = $atts['form'];
		} elseif ( $atts['id'] > 0 ) {
			$form_id = $atts['id'];
		}

		// If no Form ID specified, bail.
		if ( ! $form_id ) {
			if ( $settings->debug_enabled() ) {
				return '<!-- No Form ID Specified  -->';
			}

			return '';
		}

		// Get Form HTML.
		$forms = new ConvertKit_Resource_Forms( 'output_form' );
		$form  = $forms->get_html( $form_id );

		// If an error occured, it might be that we're requesting a Form ID that exists in ConvertKit
		// but does not yet exist in the Plugin's Form Resources.
		// If so, refresh the Form Resources and try again.
		if ( is_wp_error( $form ) ) {
			// Refresh Forms from the API.
			$forms->refresh();

			// Get Form HTML again.
			$form = $forms->get_html( $form_id );
		}

		// If an error still occured, the shortcode might be from the ConvertKit App for a Legacy Form ID
		// These ConvertKit App shortcodes, for some reason, use a different Form ID than the one presented
		// to us in the API.
		// For example, a Legacy Form ID might be 470099, but the ConvertKit app says to use the shortcode [convertkit form=5281783]).
		// In this instance, fetch the Form HTML without checking that the Form ID exists in the Form Resources.
		if ( is_wp_error( $form ) ) {
			// Initialize the API.
			$api = new ConvertKit_API_V4(
				CONVERTKIT_OAUTH_CLIENT_ID,
				CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
				$settings->get_access_token(),
				$settings->get_refresh_token(),
				$settings->debug_enabled(),
				'output_form'
			);

			// Return Legacy Form HTML from the API, which bypasses any internal Plugin check to see if the Form ID exists.
			$form = $api->get_form_html( $form_id, $settings->get_api_key() );
		}

		// Finally, if we still get an error, there's nothing more we can do. The Form ID isn't valid.
		if ( is_wp_error( $form ) ) {
			if ( $settings->debug_enabled() ) {
				return '<!-- ' . $form->get_error_message() . ' -->';
			}

			return '';
		}

		/**
		 * Filter the block's content immediately before it is output.
		 *
		 * @since   1.9.6
		 *
		 * @param   string  $form       ConvertKit Form HTML.
		 * @param   array   $atts       Block Attributes.
		 * @param   int     $form_id    Form ID.
		 */
		$form = apply_filters( 'convertkit_block_form_render', $form, $atts, $form_id );

		/**
		 * Backward compat. filter for < 1.9.6. Filter the block's content immediately before it is output.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $form   ConvertKit Form HTML.
		 * @param   array   $atts   Block Attributes.
		 */
		$form = apply_filters( 'wp_convertkit_get_form_embed', $form, $atts );

		return $form;

	}

}
