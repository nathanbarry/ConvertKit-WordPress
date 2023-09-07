<?php
/**
 * ConvertKit Forminator Admin Settings class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Forminator Settings that can be edited at Settings > ConvertKit > Forminator.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Forminator_Admin_Settings extends ConvertKit_Settings_Base {

	/**
	 * Constructor
	 *
	 * @since   2.3.0
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_Forminator_Settings();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		// Define the programmatic name, Title and Tab Text.
		$this->name     = 'forminator';
		$this->title    = __( 'Forminator Integration Settings', 'convertkit' );
		$this->tab_text = __( 'Forminator', 'convertkit' );

		parent::__construct();

	}

	/**
	 * Register fields for this section
	 *
	 * @since   2.3.0
	 */
	public function register_fields() {

		// No fields are registered, because they are output in a WP_List_Table
		// in this class' render() function.
		// This function is deliberately blank.
	}

	/**
	 * Prints help info for this section.
	 *
	 * @since   2.3.0
	 */
	public function print_section_info() {

		?>
		<p>
			<?php
			esc_html_e( 'ConvertKit seamlessly integrates with Forminator to let you add subscribers using Forminator forms.', 'convertkit' );
			?>
		</p>
		<p>
			<?php
			esc_html_e( 'The Forminator form must have Name and Email fields. These fields will be sent to ConvertKit for the subscription', 'convertkit' );
			?>
		</p>
		<?php

	}

	/**
	 * Returns the URL for the ConvertKit documentation for this setting section.
	 *
	 * @since   2.3.0
	 *
	 * @return  string  Documentation URL.
	 */
	public function documentation_url() {

		return 'https://help.convertkit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

	}

	/**
	 * Outputs the section as a WP_List_Table of Forminator Forms, with options to choose
	 * a ConvertKit Form mapping for each.
	 *
	 * @since   2.3.0
	 */
	public function render() {

		// Render opening container.
		$this->render_container_start();

		do_settings_sections( $this->settings_key );

		// Get Forms.
		$forms = new ConvertKit_Resource_Forms( 'forminator' );

		// Bail with an error if no ConvertKit Forms exist.
		if ( ! $forms->exist() ) {
			$this->output_error( __( 'No Forms exist on ConvertKit.', 'convertkit' ) );
			$this->render_container_end();
			return;
		}

		// Build array of select options.
		$options = array(
			'default' => __( 'None', 'convertkit' ),
		);
		foreach ( $forms->get() as $form ) {
			$options[ esc_attr( $form['id'] ) ] = esc_html( $form['name'] );
		}

		// Get Forminator Forms.
		$forminator_forms = $this->get_forminator_forms();

		// Bail with an error if no Forminator Forms exist.
		if ( ! $forminator_forms ) {
			$this->output_error( __( 'No Forminator Forms exist in the Forminator Plugin.', 'convertkit' ) );
			$this->render_container_end();
			return;
		}

		// Setup WP_List_Table.
		$table = new Multi_Value_Field_Table();
		$table->add_column( 'title', __( 'Forminator Form', 'convertkit' ), true );
		$table->add_column( 'form', __( 'ConvertKit Form', 'convertkit' ), false );

		// Iterate through Forminator Forms, adding a table row for each Forminator Form.
		foreach ( $forminator_forms as $forminator_form ) {
			// Build row.
			$table_row = array(
				'title' => $forminator_form['name'],
				'form'  => $this->get_select_field(
					$forminator_form['id'],
					(string) $this->settings->get_convertkit_form_id_by_forminator_form_id( $forminator_form['id'] ),
					$options
				),
			);

			// Add row to table of settings.
			$table->add_item( $table_row );
		}

		// Prepare and display WP_List_Table.
		$table->prepare_items();
		$table->display();

		// Register settings field.
		settings_fields( $this->settings_key );

		// Render submit button.
		submit_button();

		// Render closing container.
		$this->render_container_end();

	}

	/**
	 * Gets available forms from Forminator
	 *
	 * @since   2.3.0
	 *
	 * @return  bool|array
	 */
	private function get_forminator_forms() {

		$forms = array();

		// Get forms using Forminator API class.
		$results = Forminator_API::get_forms( null, 1, 100 );

		// Bail if no results.
		if ( ! count( $results ) ) {
			return false;
		}

		foreach ( $results as $forminator_form ) {
			$forms[] = array(
				'id'   => $forminator_form->id,
				'name' => $forminator_form->name,
			);
		}

		return $forms;

	}

}

// Register Admin Settings section.
add_filter(
	'convertkit_admin_settings_register_sections',
	/**
	 * Register Forminator as a settings section at Settings > ConvertKit.
	 *
	 * @param   array   $sections   Settings Sections.
	 * @return  array
	 */
	function ( $sections ) {

		// Bail if Forminator isn't enabled.
		if ( ! defined( 'FORMINATOR_VERSION' ) ) {
			return $sections;
		}

		// Register this class as a section at Settings > ConvertKit.
		$sections['forminator'] = new ConvertKit_Forminator_Admin_Settings();
		return $sections;

	}
);
