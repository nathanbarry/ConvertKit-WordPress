<?php
/**
 * ConvertKit Elementor Widget class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Extend this class for a specific block; see class-convertkit-elementor-widget-form.php
 * for an example.
 *
 * @package ConvertKit
 * @author  ConvertKit
 */
class ConvertKit_Elementor_Widget extends Elementor\Widget_Base {

	/**
	 * Holds the block's properties
	 *
	 * @since   1.9.7.2
	 *
	 * @var     WP_Error|array
	 */
	private $block;

	/**
	 * The module's slug. Must be different from the block's name i.e. cannot be convertkit-form.
	 *
	 * @since   1.9.7.2
	 *
	 * @var     string
	 */
	public $slug = '';

	/**
	 * Defines the Widget Name
	 *
	 * @since   1.9.7.2
	 *
	 * @return  string
	 */
	public function get_name() {

		return $this->slug;

	}

	/**
	 * Defines the Block Name, which is the slug excluding the
	 * `convertkit-elementor-` prefix.
	 *
	 * @since   1.9.7.2
	 *
	 * @return  string
	 */
	public function get_block_name() {

		return str_replace( 'convertkit-elementor-', '', $this->slug );

	}

	/**
	 * Defines the Widget Title
	 *
	 * @since   1.9.7.2
	 *
	 * @return  string
	 */
	public function get_title() {

		// Get block.
		$this->block = $this->get_block();

		// Bail if the block could not be found.
		if ( is_wp_error( $this->block ) ) {
			return $this->block->get_error_message();
		}

		// Return block's title.
		return $this->block['title'];

	}

	/**
	 * Defines the Widget Icon
	 *
	 * @since   1.9.7.2
	 *
	 * @return  string
	 */
	public function get_icon() {

		return 'eicon-convertkit-' . $this->get_block_name();

	}

	/**
	 * Defines the Widget Categories
	 *
	 * @since   1.9.7.2
	 *
	 * @return  array
	 */
	public function get_categories() {

		return array( 'convertkit' );

	}

	/**
	 * Defines the fields for this Widget
	 *
	 * @since   1.9.7.2
	 */
	protected function register_controls() {

		// Bail if the request is not for the WordPress Administration or frontend editor.
		if ( ! WP_ConvertKit()->is_admin_or_frontend_editor() ) {
			return;
		}

		// Get block.
		$this->block = $this->get_block();

		// Bail if the block could not be found.
		if ( is_wp_error( $this->block ) ) {
			return;
		}

		// Iterate through panels, building a section for each.
		foreach ( $this->block['panels'] as $panel_name => $panel_properties ) {
			// Start section.
			$this->start_controls_section(
				// Deliberately prefix, as if a tab and field have the same name, it won't render.
				'section_' . $panel_name,
				array(
					'label' => $panel_properties['label'],
					'tab'   => Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			// Add controls to this section.
			foreach ( $panel_properties['fields'] as $field_name ) {
				// Get field.
				$field = $this->block['fields'][ $field_name ];

				// Get Elementor Control for this field.
				$control = $this->get_field_control_args( $field );

				// Finally, register the control for this field.
				$this->add_control( $field_name, $control );
			}

			// Close the section.
			$this->end_controls_section();

		}

	}

	/**
	 * Returns the given field's control arguments, so that the field can be registered
	 * as an Elementor Control.
	 *
	 * @since   1.9.7.2
	 *
	 * @param   array $field  Block Field.
	 * @return  array           Elementor Control Arguments, compatible with add_control()
	 */
	private function get_field_control_args( $field ) {

		// Start building control.
		$control = array(
			'default'     => ( isset( $field['default_value'] ) ? $field['default_value'] : '' ),
			'label'       => $field['label'],
			'placeholder' => ( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
			'desc'        => ( isset( $field['description'] ) ? $field['description'] : '' ),
		);

		// Add control depending on the field type.
		switch ( $field['type'] ) {
			/**
			 * Select
			 */
			case 'resource':
			case 'select':
				$control = array_merge(
					$control,
					array(
						'type'    => Elementor\Controls_Manager::SELECT,
						'options' => $field['values'],
					)
				);
				break;

			/**
			 * Number
			 */
			case 'number':
				$control = array_merge(
					$control,
					array(
						'type' => Elementor\Controls_Manager::NUMBER,
						'min'  => $field['min'],
						'max'  => $field['max'],
						'step' => $field['step'],
					)
				);
				break;

			/**
			 * Toggle
			 */
			case 'toggle':
				$control = array_merge(
					$control,
					array(
						'type' => Elementor\Controls_Manager::SWITCHER,
					)
				);
				break;

			/**
			 * Color Picker
			 */
			case 'color':
				$control = array_merge(
					$control,
					array(
						'type' => Elementor\Controls_Manager::COLOR,
					)
				);
				break;

			default:
				$control = array_merge(
					$control,
					array(
						'type' => Elementor\Controls_Manager::TEXT,
					)
				);
				break;

		}

		return $control;

	}

	/**
	 * Renders the block.
	 *
	 * @since   1.9.7.2
	 */
	protected function render() {

		// Bail if the block could not be found.
		if ( is_wp_error( $this->block ) ) {
			return $this->block->get_error_message();
		}

		// Render using Block class' render() function.
		// Output is already escaped in render() function.
		echo WP_ConvertKit()->get_class( 'blocks_convertkit_' . $this->get_block_name() )->render( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Return the block for the Elementor Widget.
	 *
	 * @since   1.9.7.2
	 *
	 * @return  WP_Error|array
	 */
	private function get_block() {

		// Get blocks.
		$blocks = convertkit_get_blocks();

		// Bail if no blocks are available.
		if ( ! count( $blocks ) ) {
			return new WP_Error( 'convertkit_elementor_widget_get_block_error', __( 'No blocks are registered. Register blocks using the `convertkit_blocks` filter.', 'convertkit' ) );
		}

		// Bail if block doesn't exist.
		if ( ! array_key_exists( $this->get_block_name(), $blocks ) ) {
			return new WP_Error(
				'convertkit_elementor_widget_get_block_error',
				sprintf(
					/* translators: %1$s: Block name, %2$s: Elementor Widget name */
					__( 'Block %1$s is not registered. Register using the `convertkit_blocks` filter, and ensure the Elementor Widget for this block has its `slug` property set to %2$s.', 'convertkit' ),
					$this->get_block_name(),
					$this->slug
				)
			);
		}

		// Return block.
		return $blocks[ $this->get_block_name() ];

	}

}
