<?php
/**
 * ConvertKit Forminator Admin Settings class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Forminator Settings that can be edited at Settings > Kit > Forminator.
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

		// Define settings sections.
		$this->settings_sections = array(
			'general' => array(
				'title'    => $this->title,
				'callback' => array( $this, 'print_section_info' ),
				'wrap'     => false,
			),
		);

		parent::__construct();

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
			esc_html_e( 'Kit seamlessly integrates with Forminator to let you add subscribers using Forminator forms.', 'convertkit' );
			?>
		</p>
		<p>
			<?php
			esc_html_e( 'The Forminator form must have Name and Email fields. These fields will be sent to Kit for the subscription', 'convertkit' );
			?>
		</p>
		<p>
			<?php esc_html_e( 'Each Forminator form and quiz has the following Kit options:', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Do not subscribe', 'convertkit' ); ?></code>: <?php esc_html_e( 'Do not subscribe the email address to Kit', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Subscribe', 'convertkit' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to Kit', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Form', 'convertkit' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to Kit, and adds the subscriber to the Kit form', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Tag', 'convertkit' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to Kit, tagging the subscriber', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Sequence', 'convertkit' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to Kit, and adds the subscriber to the Kit sequence', 'convertkit' ); ?>
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

		return 'https://help.kit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

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

		// Get Forminator Forms.
		$forminator_forms = $this->get_forminator_forms();

		// Bail with an error if no Forminator Forms exist.
		if ( ! $forminator_forms ) {
			$this->output_error( __( 'No Forminator Forms or Quizzes exist in the Forminator Plugin.', 'convertkit' ) );
			$this->render_container_end();
			return;
		}

		// Get Creator Network Recommendations script.
		$creator_network_recommendations         = new ConvertKit_Resource_Creator_Network_Recommendations( 'forminator' );
		$creator_network_recommendations_enabled = $creator_network_recommendations->enabled();

		// Setup WP_List_Table.
		$table = new Multi_Value_Field_Table();
		$table->add_column( 'title', __( 'Forminator Form', 'convertkit' ), true );
		$table->add_column( 'form', __( 'Kit', 'convertkit' ), false );
		$table->add_column( 'creator_network_recommendations', __( 'Enable Creator Network Recommendations', 'convertkit' ), false );

		// Iterate through Forminator Forms, adding a table row for each Forminator Form.
		foreach ( $forminator_forms as $forminator_form ) {
			// Build row.
			$table_row = array(
				'title' => $forminator_form['name'],
				'form'  => convertkit_get_subscription_dropdown_field(
					'_wp_convertkit_integration_forminator_settings[' . $forminator_form['id'] . ']',
					(string) $this->settings->get_convertkit_subscribe_setting_by_forminator_form_id( $forminator_form['id'] ),
					'_wp_convertkit_integration_forminator_settings_' . $forminator_form['id'],
					'widefat',
					'forminator'
				),
			);

			// Add Creator Network Recommendations table column.
			if ( $creator_network_recommendations_enabled ) {
				// Show checkbox to enable Creator Network Recommendations for this Forminator Form.
				$table_row['creator_network_recommendations'] = $this->get_checkbox_field(
					'creator_network_recommendations_' . $forminator_form['id'],
					'1',
					$this->settings->get_creator_network_recommendations_enabled_by_forminator_form_id( $forminator_form['id'] )
				);
			} else {
				// Show a link to the ConvertKit billing page, as a paid plan is required for Creator Network Recommendations.
				$table_row['creator_network_recommendations'] = sprintf(
					'%s <a href="%s" target="_blank">%s</a>',
					esc_html__( 'Creator Network Recommendations requires a', 'convertkit' ),
					convertkit_get_billing_url(),
					esc_html__( 'paid Kit Plan', 'convertkit' )
				);
			}

			// Add row to table of settings.
			$table->add_item( $table_row );
		}

		// Prepare and display WP_List_Table.
		$table->prepare_items();
		$table->display();

		// Register settings field.
		settings_fields( $this->settings_key );

		// Render closing container.
		$this->render_container_end();

		// Render submit button.
		submit_button();

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

		// Get all forms using Forminator API class.
		foreach ( Forminator_API::get_forms( null, 1, -1 ) as $forminator_form ) {
			$forms[] = array(
				'id'   => $forminator_form->id,
				'name' => sprintf(
					'%s: %s',
					esc_html__( 'Form', 'convertkit' ),
					$forminator_form->name
				),
			);
		}

		foreach ( Forminator_API::get_quizzes( null, 1, -1 ) as $forminator_form ) {
			$forms[] = array(
				'id'   => $forminator_form->id,
				'name' => sprintf(
					'%s: %s',
					esc_html__( 'Quiz', 'convertkit' ),
					$forminator_form->name
				),
			);
		}

		// If no Forms or Quizzes were found in Forminator, return false.
		if ( ! count( $forms ) ) {
			return false;
		}

		return $forms;

	}

}

// Register Admin Settings section.
add_filter(
	'convertkit_admin_settings_register_sections',
	/**
	 * Register Forminator as a settings section at Settings > Kit.
	 *
	 * @param   array   $sections   Settings Sections.
	 * @return  array
	 */
	function ( $sections ) {

		// Bail if Forminator isn't enabled.
		if ( ! defined( 'FORMINATOR_VERSION' ) ) {
			return $sections;
		}

		// Register this class as a section at Settings > Kit.
		$sections['forminator'] = new ConvertKit_Forminator_Admin_Settings();
		return $sections;

	}
);
