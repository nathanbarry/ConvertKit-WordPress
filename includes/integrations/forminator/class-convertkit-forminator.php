<?php
/**
 * ConvertKit Forminator class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Forminator Integration
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Forminator {

	/**
	 * Constructor. Registers required hooks with Forminator.
	 *
	 * @since   2.3.0
	 */
	public function __construct() {

		add_action( 'forminator_custom_form_submit_before_set_fields', array( $this, 'maybe_subscribe' ), 10, 3 );

	}

	/**
	 * Sends a Forminator's Form Name and Email values through the ConvertKit API
	 * if a ConvertKit Form is mapped to this Forminator Form in the ConvertKit
	 * Settings.
	 *
	 * @since   2.3.0
	 *
	 * @param   array $entry              Entry.
	 * @param   int   $form_id            Forminator Form ID.
	 * @param   array $form_data_array    Forminator submitted data.
	 */
	public function maybe_subscribe( $entry, $form_id, $form_data_array ) {

		// Get ConvertKit Form ID mapped to this Forminator Form.
		$forminator_settings = new ConvertKit_Forminator_Settings();
		$convertkit_form_id  = $forminator_settings->get_convertkit_form_id_by_forminator_form_id( $form_id );

		// If no ConvertKit Form is mapped to this Forminator Form, bail.
		if ( ! $convertkit_form_id ) {
			return;
		}

		// Bail if the API hasn't been configured.
		$settings = new ConvertKit_Settings();
		if ( ! $settings->has_api_key_and_secret() ) {
			return;
		}

		// Extract the name and email field values.
		$first_name = false;
		$email      = false;
		foreach ( $form_data_array as $form_field ) {
			// Skip field if it doesn't have a type - it's likely an IP address value.
			if ( ! array_key_exists( 'field_type', $form_field ) ) {
				continue;
			}

			// Extract the name / email address, depending on the field type.
			switch ( $form_field['field_type'] ) {
				case 'name':
					$name       = explode( ' ', $form_field['value'] );
					$first_name = $name[0];
					break;

				case 'email':
					$email = $form_field['value'];
					break;
			}
		}

		// Bail if no email address could be found.
		if ( ! $email ) {
			return;
		}

		// If here, subscribe the user to the ConvertKit Form.
		// Initialize the API.
		$api = new ConvertKit_API( $settings->get_api_key(), $settings->get_api_secret(), $settings->debug_enabled(), 'forminator' );

		// Send request.
		$api->form_subscribe( $convertkit_form_id, $email, $first_name );

	}

}

// Bootstrap.
add_action(
	'convertkit_initialize_global',
	function () {

		new ConvertKit_Forminator();

	}
);
