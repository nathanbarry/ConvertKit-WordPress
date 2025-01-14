<?php
/**
 * Divi Module
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers blocks as Divi Modules.
 *
 * @package ConvertKit
 * @author  ConvertKit
 */
class ConvertKit_Divi_Module extends ET_Builder_Module {

	/**
	 * How modules are supported in the Visual Builder (off|partial|on)
	 *
	 * @since   2.5.6
	 *
	 * @var     string
	 */
	public $vb_support = 'on';

	/**
	 * The ConvertKit block name.
	 *
	 * @since   2.5.6
	 *
	 * @var     string
	 */
	public $block_name = '';

	/**
	 * The ConvertKit Divi module name.
	 *
	 * @since   2.5.6
	 *
	 * @var     string
	 */
	public $slug = '';

	/**
	 * The full path to the SVG icon for this Divi module.
	 *
	 * @since   2.5.7
	 *
	 * @var     string
	 */
	public $icon_path = '';

	/**
	 * Holds the block definition, properties and fields.
	 *
	 * @since   2.5.6
	 *
	 * @var     bool|WP_Error|array
	 */
	public $block = false;

	/**
	 * Defines the Module name
	 *
	 * @since   2.5.6
	 */
	public function init() {

		// Get block.
		$blocks = convertkit_get_blocks();

		// Bail if no blocks are available.
		if ( ! count( $blocks ) ) {
			return;
		}

		// Bail if the block doesn't exist.
		if ( ! array_key_exists( $this->block_name, $blocks ) ) {
			return;
		}

		// Define the block, name and icon.
		$this->block     = $blocks[ $this->block_name ];
		$this->name      = esc_html( $this->block['title'] );
		$this->icon_path = CONVERTKIT_PLUGIN_PATH . '/' . $this->block['icon'];

	}

	/**
	 * Defines the fields that can be configured for this Module
	 *
	 * @since   2.5.6
	 */
	public function get_fields() {

		// Bail if no block.
		if ( is_wp_error( $this->block ) ) {
			return array();
		}

		// Bail if no fields.
		if ( ! is_array( $this->block['fields'] ) ) {
			return array();
		}

		// Build fields.
		$fields = array();
		foreach ( $this->block['fields'] as $field_name => $field ) {
			// Start building field definition.
			$fields[ $field_name ] = array(
				'type'        => $field['type'],
				'default'     => $this->get_default_value( $field ),
				'description' => ( isset( $field['description'] ) ? $field['description'] : '' ),
				'label'       => $field['label'],
				'toggle_slug' => 'main_content',
			);

			// Add/change field parameters depending on the field's type.
			switch ( $field['type'] ) {
				/**
				 * Number
				 */
				case 'number':
					$fields[ $field_name ] = array_merge(
						$fields[ $field_name ],
						array(
							'type'           => 'range',
							'range_settings' => array(
								'min'  => $field['min'],
								'max'  => $field['max'],
								'step' => $field['step'],
							),
							'unitless'       => true,
						)
					);
					break;

				/**
				 * Select
				 */
				case 'resource':
				case 'select':
					// For select dropdowns, Divi treats the first <option> as the default. If it's selected,
					// Divi won't pass the underlying value, resulting in no output.
					// Forcing a 'None' option as the first ensures the user must select an <option>, therefore
					// ensuring output is correct.
					$fields[ $field_name ]['type']    = 'select';
					$fields[ $field_name ]['options'] = array( 0 => __( '(None)', 'convertkit' ) ) + $field['values'];
					break;

				/**
				 * Toggle
				 */
				case 'toggle':
					$fields[ $field_name ] = array_merge(
						$fields[ $field_name ],
						array(
							'type'    => 'yes_no_button',
							'default' => ( $fields[ $field_name ]['default'] ? 'on' : 'off' ),
							'options' => array(
								'off' => __( 'No', 'convertkit' ),
								'on'  => __( 'Yes', 'convertkit' ),
							),
						)
					);
					break;

			}
		}

		// Return.
		return $fields;

	}

	/**
	 * Render the module.
	 *
	 * @since   2.5.6
	 *
	 * @param   array|string $unprocessed_props  Unprocessed properties.
	 * @param   array|string $content            Content.
	 * @param   string       $render_slug        Slug.
	 * @return  string                           Block's output.
	 */
	public function render( $unprocessed_props, $content, $render_slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Render using Block class' render() function.
		// Output is already escaped in render() function.
		return WP_ConvertKit()->get_class( 'blocks_convertkit_' . $this->block_name )->render( $unprocessed_props ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Returns the default value for the given field configuration.
	 *
	 * If the field's default value is an array, it's converted to a string,
	 * to prevent Divi builder timeout errors on the frontend.
	 *
	 * @since   2.5.6
	 *
	 * @param   array $field  Field.
	 * @return  string|int|object         Default Value
	 */
	private function get_default_value( $field ) {

		// Return a blank string if the field doesn't specify a default value.
		if ( ! array_key_exists( 'default_value', $field ) ) {
			return '';
		}

		return $field['default_value'];

	}

}
