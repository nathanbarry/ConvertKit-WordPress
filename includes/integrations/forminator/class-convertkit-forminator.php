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

		add_action( 'forminator_before_form_render', array( $this, 'maybe_enqueue_creator_network_recommendations_script' ) );
		add_action( 'forminator_custom_form_submit_before_set_fields', array( $this, 'maybe_subscribe' ), 10, 3 );

	}

	/**
	 * Enqueues the Creator Network Recommendations script, if the Forminator Form
	 * has the 'Enable Creator Network Recommendations' setting enabled at Settings >
	 * ConvertKit > Forminator.
	 *
	 * @since   2.3.0
	 *
	 * @param   int $form_id    Forminator Form ID.
	 */
	public function maybe_enqueue_creator_network_recommendations_script( $form_id ) {

		// Don't enqueue if this is a WordPress Admin screen request.
		if ( is_admin() ) {
			return;
		}

		// Initialize classes.
		$creator_network_recommendations = new ConvertKit_Resource_Creator_Network_Recommendations( 'forminator' );
		$forminator_settings             = new ConvertKit_Forminator_Settings();

		// Bail if Creator Network Recommendations are not enabled for this form.
		if ( ! $forminator_settings->get_creator_network_recommendations_enabled_by_forminator_form_id( $form_id ) ) {
			return;
		}

		// Get script.
		$script_url = $creator_network_recommendations->get();

		// Bail if no script exists (i.e. the Creator Network Recommendations is not enabled on the ConvertKit account).
		if ( ! $script_url ) {
			return;
		}

		// Enqueue script.
		wp_enqueue_script( 'convertkit-creator-network-recommendations', $script_url, array(), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Sends a Forminator's Form Name and Email values through the ConvertKit API
	 * if a ConvertKit Form is mapped to this Forminator Form in the ConvertKit
	 * Settings.
	 *
	 * @since   2.3.0
	 *
	 * @param   Forminator_Form_Entry_Model $entry              Entry.
	 * @param   int                         $form_id            Forminator Form ID.
	 * @param   array                       $form_data_array    Forminator submitted data.
	 */
	public function maybe_subscribe( $entry, $form_id, $form_data_array ) {

		// Get ConvertKit Form ID mapped to this Forminator Form.
		// We deliberately use the entry's form ID, as $form_id for a Quiz will point to a lead generation form, which
		// has a different Form ID.
		$forminator_settings          = new ConvertKit_Forminator_Settings();
		$convertkit_subscribe_setting = $forminator_settings->get_convertkit_subscribe_setting_by_forminator_form_id( $entry->form_id );

		// If no ConvertKit subscribe setting is defined, bail.
		if ( ! $convertkit_subscribe_setting ) {
			return;
		}

		// Bail if the API hasn't been configured.
		$settings = new ConvertKit_Settings();
		if ( ! $settings->has_access_and_refresh_token() ) {
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
		$api = new ConvertKit_API_V4(
			CONVERTKIT_OAUTH_CLIENT_ID,
			CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
			$settings->get_access_token(),
			$settings->get_refresh_token(),
			$settings->debug_enabled(),
			'forminator'
		);

		// If the resource setting is 'subscribe', create the subscriber in an active state and don't assign to a resource.
		if ( $convertkit_subscribe_setting === 'subscribe' ) {
			$api->create_subscriber( $email, $first_name );
			return;
		}

		// Determine the resource type and ID to assign to the subscriber.
		list( $resource_type, $resource_id ) = explode( ':', $convertkit_subscribe_setting );

		// Cast ID.
		$resource_id = absint( $resource_id );

		// Add the subscriber to the resource type (form, tag etc).
		switch ( $resource_type ) {

			/**
			 * Form
			 */
			case 'form':
				// Subscribe with inactive state.
				$subscriber = $api->create_subscriber( $email, $first_name, 'inactive' );

				// For Legacy Forms, a different endpoint is used.
				$forms = new ConvertKit_Resource_Forms();
				if ( $forms->is_legacy( $resource_id ) ) {
					return $api->add_subscriber_to_legacy_form( $resource_id, $subscriber['subscriber']['id'] );
				}

				// Add subscriber to form.
				return $api->add_subscriber_to_form( $resource_id, $subscriber['subscriber']['id'], $this->get_referrer_url() );

			/**
			 * Sequence
			 */
			case 'sequence':
				// Subscribe.
				$subscriber = $api->create_subscriber( $email, $first_name );

				// If an error occured, don't attempt to add the subscriber to the Form, as it won't work.
				if ( is_wp_error( $subscriber ) ) {
					return;
				}

				// Add subscriber to sequence.
				return $api->add_subscriber_to_sequence( $resource_id, $subscriber['subscriber']['id'] );

			/**
			 * Tag
			 */
			case 'tag':
				// Subscribe.
				$subscriber = $api->create_subscriber( $email, $first_name );

				// If an error occured, don't attempt to add the subscriber to the Form, as it won't work.
				if ( is_wp_error( $subscriber ) ) {
					return;
				}

				// Add subscriber to tag.
				return $api->tag_subscriber( $resource_id, $subscriber['subscriber']['id'] );

		}

	}

	/**
	 * Gets the referrer URL to send to the `form_subscribe` API method.
	 *
	 * Falls back to the action's AJAX URL if the Post ID the form was
	 * embedded in cannot be determined.
	 *
	 * @since   2.7.1
	 *
	 * @return  string
	 */
	private function get_referrer_url() {

		// If the request includes the HTTP referrer, return that URL
		// as it will include any UTM parameters.
		if ( array_key_exists( '_wp_http_referer', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// referrer is a relative path, so use home_url() to return a fully qualified URL.
			return esc_url( home_url( $_REQUEST['_wp_http_referer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// If the request includes the current_url, return that URL.
		// It won't include any UTM parameters, but is still an accurate URL.
		if ( array_key_exists( 'current_url', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return esc_url( $_REQUEST['current_url'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Return the AJAX URL.
		return home_url( add_query_arg( null, null ) );

	}

}

// Bootstrap.
add_action(
	'convertkit_initialize_global',
	function () {

		new ConvertKit_Forminator();

	}
);
