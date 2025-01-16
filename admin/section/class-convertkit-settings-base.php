<?php
/**
 * ConvertKit Settings Base class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * ConvertKit Settings class
 *
 * @package ConvertKit
 * @author ConvertKit
 */
abstract class ConvertKit_Settings_Base {

	/**
	 * Section name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Section title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Section tab text
	 *
	 * @var string
	 */
	public $tab_text;

	/**
	 * Options table key
	 *
	 * @var string
	 */
	public $settings_key;

	/**
	 * Holds the settings class for the section.
	 *
	 * @since   1.9.6
	 *
	 * @var     false|ConvertKit_Settings|ConvertKit_ContactForm7_Settings|ConvertKit_Wishlist_Settings|ConvertKit_Settings_Restrict_Content|ConvertKit_Settings_Broadcasts|ConvertKit_Forminator_Settings
	 */
	public $settings;

	/**
	 * Holds the settings sections for a settings screen.
	 *
	 * @since   2.7.1
	 *
	 * @var     array
	 */
	public $settings_sections = array();

	/**
	 * Holds whether this settings section is for beta functionality.
	 *
	 * @since   2.1.0
	 *
	 * @var     bool
	 */
	public $is_beta = false;

	/**
	 * Holds whether the save button should be disabled e.g. there are no
	 * settings on screen to save.
	 *
	 * @since   2.4.9
	 *
	 * @var     bool
	 */
	public $save_disabled = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		// If tab text is not defined, use the title for the tab's text.
		if ( empty( $this->tab_text ) ) {
			$this->tab_text = $this->title;
		}

		// Register the settings section.
		$this->register_section();

	}

	/**
	 * Helper method to determine if we're viewing the current settings screen.
	 *
	 * @since   2.5.0
	 *
	 * @param   string $tab    Current settings tab (general|tools|restrict-content|broadcasts).
	 * @return  bool
	 */
	public function on_settings_screen( $tab ) {

		// phpcs:disable WordPress.Security.NonceVerification

		// Bail if we're not on the settings screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) {
			return false;
		}
		if ( sanitize_text_field( $_REQUEST['page'] ) !== '_wp_convertkit_settings' ) {
			return false;
		}

		// Define current settings tab.
		// General screen won't always be loaded with a `tab` parameter.
		$current_tab = ( array_key_exists( 'tab', $_REQUEST ) ? sanitize_text_field( $_REQUEST['tab'] ) : 'general' );

		// Return whether the request is for the current settings tab.
		return ( $current_tab === $tab );

		// phpcs:enable

	}

	/**
	 * Register settings section.
	 */
	public function register_section() {

		// Register settings sections.
		foreach ( $this->settings_sections as $name => $settings_section ) {
			// Determine if this settings section needs to be wrapped in its own container.
			$wrap = array();
			if ( $settings_section['wrap'] ) {
				$wrap = array(
					'before_section' => $this->get_render_container_start(),
					'after_section'  => $this->get_render_container_end(),
				);
			}

			add_settings_section(
				( $name === 'general' ? $this->name : $this->name . '-' . $name ),
				$settings_section['title'],
				$settings_section['callback'],
				$this->settings_key,
				$wrap
			);
		}

		// Register settings fields.
		$this->register_fields();

		// Register setting to store data in options table.
		register_setting(
			$this->settings_key,
			$this->settings_key,
			array( $this, 'sanitize_settings' )
		);

	}

	/**
	 * Register fields for this section
	 */
	public function register_fields() {
	}

	/**
	 * Prints help info for this section
	 */
	abstract public function print_section_info();

	/**
	 * Returns the URL for the ConvertKit documentation for this setting section.
	 */
	abstract public function documentation_url();

	/**
	 * Outputs success and/or error notices if required.
	 *
	 * @since   2.0.0
	 */
	public function maybe_output_notices() {

		// Define notices that might be displayed as a notification.
		$notices = array();

		/**
		 * Register success and error notices for settings screens.
		 *
		 * @since   2.5.1
		 *
		 * @param   array   $notices    Regsitered success and error notices.
		 * @return  array
		 */
		$notices = apply_filters( 'convertkit_settings_base_register_notices', $notices );

		// Output the verbose error description if supplied (e.g. OAuth).
		if ( isset( $_REQUEST['error_description'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->output_error( sanitize_text_field( $_REQUEST['error_description'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Output error notification if defined.
		if ( isset( $_REQUEST['error'] ) && array_key_exists( sanitize_text_field( $_REQUEST['error'] ), $notices ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->output_error( $notices[ sanitize_text_field( $_REQUEST['error'] ) ] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Output success notification if defined.
		if ( isset( $_REQUEST['success'] ) && array_key_exists( sanitize_text_field( $_REQUEST['success'] ), $notices ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->output_success( $notices[ sanitize_text_field( $_REQUEST['success'] ) ] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

	}

	/**
	 * Renders the section
	 */
	public function render() {

		/**
		 * Performs actions prior to rendering the settings form.
		 *
		 * @since   1.9.6
		 */
		do_action( 'convertkit_settings_base_render_before' );

		do_settings_sections( $this->settings_key );

		settings_fields( $this->settings_key );

		if ( ! $this->save_disabled ) {
			submit_button();
		}

		/**
		 * Performs actions after rendering of the settings form.
		 *
		 * @since   1.9.6
		 */
		do_action( 'convertkit_settings_base_render_after' );

	}

	/**
	 * Outputs opening .metabox-holder and .postbox container div elements,
	 * used before beginning a setting screen's output.
	 *
	 * @since   2.0.0
	 */
	public function render_container_start() {

		echo $this->get_render_container_start(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Outputs closing .metabox-holder and .postbox container div elements,
	 * used after finishing a setting screen's output.
	 *
	 * @since   2.0.0
	 */
	public function render_container_end() {

		echo $this->get_render_container_end(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Returns opening .metabox-holder and .postbox container div elements,
	 * used before beginning a section of a settings screen output.
	 *
	 * @since   2.7.1
	 */
	public function get_render_container_start() {

		return '<div class="metabox-holder"><div class="postbox ' . sanitize_html_class( $this->is_beta ? 'convertkit-beta' : '' ) . '">';

	}

	/**
	 * Returns closing .metabox-holder and .postbox container div elements,
	 * used after finishing a section of a settings screen output.
	 *
	 * @since   2.7.1
	 */
	public function get_render_container_end() {

		return '</div></div>';

	}

	/**
	 * Redirects to the settings screen.
	 *
	 * @since   2.2.9
	 */
	public function redirect() {

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => '_wp_convertkit_settings',
					'tab'  => $this->name,
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Redirects to the settings screen with an error notice key.
	 *
	 * The function maybe_output_notices() will then output the translated error notice
	 * based on the supplied key.
	 *
	 * @since   2.5.1
	 *
	 * @param   string $error      The error notice key, registered using `convertkit_settings_base_register_notices`.
	 */
	public function redirect_with_error_notice( $error ) {

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => '_wp_convertkit_settings',
					'tab'   => $this->name,
					'error' => $error,
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Redirects to the settings screen with the verbose error description.
	 *
	 * @since   2.5.1
	 *
	 * @param   string $error_description      The error description.
	 */
	public function redirect_with_error_description( $error_description ) {

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => '_wp_convertkit_settings',
					'tab'               => $this->name,
					'error_description' => $error_description,
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Redirects to the settings screen with a success notice key.
	 *
	 * The function maybe_output_notices() will then output the translated success notice
	 * based on the supplied key.
	 *
	 * @since   2.5.1
	 *
	 * @param   string $success      The success notice key, registered using `convertkit_settings_base_register_notices`.
	 */
	public function redirect_with_success_notice( $success ) {

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => '_wp_convertkit_settings',
					'tab'     => $this->name,
					'success' => $success,
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Outputs the given success message in an inline notice.
	 *
	 * @since   2.0.0
	 *
	 * @param   string $success_message  Success Message.
	 */
	public function output_success( $success_message ) {

		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php echo esc_attr( $success_message ); ?>
			</p>
		</div>
		<?php

	}

	/**
	 * Outputs the given error message in an inline notice.
	 *
	 * @since   1.9.6
	 *
	 * @param   string $error_message  Error Message.
	 */
	public function output_error( $error_message ) {

		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php echo esc_attr( $error_message ); ?>
			</p>
		</div>
		<?php

	}

	/**
	 * Returns a masked value.
	 *
	 * @since   1.9.6
	 *
	 * @param   string      $value          Value.
	 * @param   bool|string $description    Description.
	 * @return  string                      Masked Value
	 */
	public function get_masked_value( $value, $description = false ) {

		$html = sprintf(
			'<code>%s</code>',
			str_repeat( '*', strlen( $value ) - 4 ) . substr( $value, - 4 )
		);

		if ( $description ) {
			$html .= $this->get_description( $description );
		}

		return $html;

	}

	/**
	 * Returns a text field.
	 *
	 * @since   1.9.6
	 *
	 * @param   string            $name           Name.
	 * @param   string            $value          Value.
	 * @param   bool|string|array $description    Description (false|string|array).
	 * @param   bool|array        $css_classes    CSS Classes (false|array).
	 * @return  string                              HTML Field
	 */
	public function get_text_field( $name, $value = '', $description = false, $css_classes = false ) {

		$html = sprintf(
			'<input type="text" class="%s" id="%s" name="%s[%s]" value="%s" />',
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : 'regular-text' ),
			$name,
			$this->settings_key,
			$name,
			$value
		);

		return $html . $this->get_description( $description );

	}

	/**
	 * Returns a number field.
	 *
	 * @since   2.6.1
	 *
	 * @param   string            $name           Name.
	 * @param   string            $value          Value.
	 * @param   int|float         $min            `min` attribute value.
	 * @param   int|float         $max            `max` attribute value.
	 * @param   int|float         $step           `step` attribute value.
	 * @param   bool|string|array $description    Description (false|string|array).
	 * @param   bool|array        $css_classes    CSS Classes (false|array).
	 * @return  string                            HTML Field
	 */
	public function get_number_field( $name, $value = '', $min = 0, $max = 9999, $step = 1, $description = false, $css_classes = false ) {

		$html = sprintf(
			'<input type="number" class="%s" id="%s" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" />',
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : 'small-text' ),
			$name,
			$this->settings_key,
			$name,
			$value,
			$min,
			$max,
			$step
		);

		return $html . $this->get_description( $description );

	}

	/**
	 * Returns a textarea field.
	 *
	 * @since   2.3.5
	 *
	 * @param   string            $name           Name.
	 * @param   string            $value          Value.
	 * @param   bool|string|array $description    Description (false|string|array).
	 * @param   bool|array        $css_classes    CSS Classes (false|array).
	 * @return  string                              HTML Field
	 */
	public function get_textarea_field( $name, $value = '', $description = false, $css_classes = false ) {

		$html = sprintf(
			'<textarea class="%s" id="%s" name="%s[%s]">%s</textarea>',
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : 'regular-text' ),
			$name,
			$this->settings_key,
			$name,
			$value
		);

		return $html . $this->get_description( $description );

	}

	/**
	 * Returns a date field.
	 *
	 * @since   2.2.8
	 *
	 * @param   string            $name           Name.
	 * @param   string            $value          Value.
	 * @param   bool|string|array $description    Description (false|string|array).
	 * @param   bool|array        $css_classes    CSS Classes (false|array).
	 * @return  string                              HTML Field
	 */
	public function get_date_field( $name, $value = '', $description = false, $css_classes = false ) {

		$html = sprintf(
			'<input type="date" class="%s" id="%s" name="%s[%s]" value="%s" />',
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : 'regular-text' ),
			$name,
			$this->settings_key,
			$name,
			$value
		);

		return $html . $this->get_description( $description );

	}

	/**
	 * Returns a select dropdown field.
	 *
	 * @since   1.9.6
	 *
	 * @param   string      $name            Name.
	 * @param   string      $value           Value.
	 * @param   array       $options         Options / Choices.
	 * @param   bool|string $description     Description.
	 * @param   bool|array  $css_classes     <select> CSS class(es).
	 * @param   bool|array  $attributes      <select> attributes.
	 * @return  string                           HTML Select Field
	 */
	public function get_select_field( $name, $value = '', $options = array(), $description = false, $css_classes = false, $attributes = false ) {

		// Build opening <select> tag.
		$html = sprintf(
			'<select id="%s" name="%s[%s]" class="%s" size="1" %s>',
			$this->settings_key . '_' . $name,
			$this->settings_key,
			$name,
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : '' ),
			( is_array( $attributes ) ? $this->array_to_attributes( $attributes ) : '' )
		);

		// Build <option> tags.
		foreach ( $options as $option => $label ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				$option,
				selected( $value, $option, false ),
				$label
			);
		}

		// Close <select>.
		$html .= '</select>';

		// If no description exists, just return the select field.
		if ( empty( $description ) ) {
			return $html;
		}

		// Return select field with description appended to it.
		return $html . $this->get_description( $description );

	}

	/**
	 * Returns a checkbox field.
	 *
	 * @since   1.9.6
	 *
	 * @param   string            $name           Name.
	 * @param   string            $value          Value.
	 * @param   bool              $checked        Should checkbox be checked/ticked.
	 * @param   bool|string       $label          Label.
	 * @param   bool|string|array $description    Description.
	 * @param   bool|array        $css_classes    CSS class(es).
	 * @return  string                            HTML Checkbox
	 */
	public function get_checkbox_field( $name, $value, $checked = false, $label = '', $description = false, $css_classes = false ) {

		$html = '';

		if ( $label ) {
			$html .= sprintf(
				'<label for="%s">',
				$name
			);
		}

		$html .= sprintf(
			'<input type="checkbox" id="%s" name="%s[%s]" class="%s" value="%s" %s />',
			$name,
			$this->settings_key,
			$name,
			( is_array( $css_classes ) ? implode( ' ', $css_classes ) : '' ),
			$value,
			( $checked ? ' checked' : '' )
		);

		if ( $label ) {
			$html .= sprintf(
				'%s</label>',
				$label
			);
		}

		// If no description exists, just return the field.
		if ( empty( $description ) ) {
			return $html;
		}

		// Return field with description appended to it.
		return $html . $this->get_description( $description );

	}

	/**
	 * Returns the given text wrapped in a paragraph with the description class.
	 *
	 * @since   1.9.6
	 *
	 * @param   bool|string|array $description    Description.
	 * @return  string                              HTML Description
	 */
	public function get_description( $description ) {

		// Return blank string if no description specified.
		if ( ! $description ) {
			return '';
		}

		// Return description in paragraph if a string.
		if ( ! is_array( $description ) ) {
			return '<p class="description">' . $description . '</p>';
		}

		// Return description lines in a paragraph, using breaklines for each description entry in the array.
		return '<p class="description">' . implode( '<br />', $description ) . '</p>';

	}

	/**
	 * Converts the given key/value array pairs into a HTML attribute="value" string.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   array $attributes_array  Attributes.
	 * @return  string                  HTML attributes string
	 */
	private function array_to_attributes( $attributes_array ) {

		$attributes = '';
		foreach ( $attributes_array as $key => $value ) {
			$attributes .= esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		return trim( $attributes );

	}

	/**
	 * Sanitizes the settings prior to being saved.
	 *
	 * @since   1.9.6
	 *
	 * @param   array $settings   Submitted Settings Fields.
	 * @return  array               Sanitized Settings with Defaults
	 */
	public function sanitize_settings( $settings ) {

		// Merge settings with defaults.
		$updated_settings = wp_parse_args( $settings, $this->settings->get_defaults() );

		/**
		 * Performs actions prior to settings being saved.
		 *
		 * @since   2.2.8
		 */
		do_action( 'convertkit_settings_base_sanitize_settings', $this->name, $updated_settings );

		// Return settings to be saved.
		return $updated_settings;

	}

}
