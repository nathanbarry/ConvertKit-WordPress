<?php
/**
 * ConvertKit Contact Form 7 class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Contact Form 7 Integration
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_ContactForm7 {

	/**
	 * Constructor. Registers required hooks with Contact Form 7.
	 *
	 * @since   1.9.6
	 */
	public function __construct() {

		add_action( 'wpcf7_contact_form', array( $this, 'maybe_enqueue_creator_network_recommendations_script' ) );
		add_action( 'wpcf7_submit', array( $this, 'handle_wpcf7_submit' ), 10, 2 );

	}

	/**
	 * Enqueues the Creator Network Recommendations script, if the Contact Form 7 Form
	 * has the 'Enable Creator Network Recommendations' setting enabled at Settings >
	 * ConvertKit > Contact Form 7.
	 *
	 * @since   2.2.7
	 *
	 * @param   WPCF7_ContactForm $contact_form   Contact Form 7 Form.
	 */
	public function maybe_enqueue_creator_network_recommendations_script( $contact_form ) {

		// Don't enqueue if this is a WordPress Admin screen request.
		if ( is_admin() ) {
			return;
		}

		// Initialize classes.
		$creator_network_recommendations = new ConvertKit_Resource_Creator_Network_Recommendations( 'contact_form_7' );
		$contact_form_7_settings         = new ConvertKit_ContactForm7_Settings();

		// Bail if Creator Network Recommendations are not enabled for this form.
		if ( ! $contact_form_7_settings->get_creator_network_recommendations_enabled_by_cf7_form_id( $contact_form->id() ) ) {
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
	 * Sends a Contact Form 7's Form Name and Email values through the ConvertKit API
	 * if a ConvertKit Form is mapped to this Contact Form 7 Form in the ConvertKit
	 * Settings.
	 *
	 * @since   1.9.6
	 *
	 * @param   WPCF7_ContactForm $contact_form   Contact Form 7 Form.
	 * @param   array             $result         Submission Result.
	 */
	public function handle_wpcf7_submit( $contact_form, $result ) {

		// If Demo Mode is enabled on the Contact Form 7 Form, don't send anything to ConvertKit.
		// @see https://contactform7.com/additional-settings/.
		if ( isset( $result['demo_mode'] ) ) {
			return;
		}

		// If the form submission failed, don't send anything to ConvertKit.
		if ( $result['status'] !== 'mail_sent' ) {
			return;
		}

		// Get ConvertKit Form ID mapped to this Contact Form 7 Form.
		$contact_form_7_settings      = new ConvertKit_ContactForm7_Settings();
		$convertkit_subscribe_setting = $contact_form_7_settings->get_convertkit_subscribe_setting_by_cf7_form_id( $contact_form->id() );

		// If no ConvertKit subscribe setting is defined, bail.
		if ( ! $convertkit_subscribe_setting ) {
			return;
		}

		// Bail if the API hasn't been configured.
		$settings = new ConvertKit_Settings();
		if ( ! $settings->has_access_and_refresh_token() ) {
			return;
		}

		// Get Contact Form 7 Submission.
		$form_data = WPCF7_Submission::get_instance()->get_posted_data();

		// Bail if the expected email form field does not exist.
		if ( ! isset( $form_data['your-email'] ) || empty( $form_data['your-email'] ) ) {
			return;
		}

		// Get email and first name.
		$email      = $form_data['your-email'];
		$first_name = '';
		if ( isset( $form_data['your-name'] ) && ! empty( $form_data['your-name'] ) ) {
			$name       = explode( ' ', $form_data['your-name'] );
			$first_name = $name[0];
		}

		// If here, subscribe the user to the ConvertKit Form.
		// Initialize the API.
		$api = new ConvertKit_API_V4(
			CONVERTKIT_OAUTH_CLIENT_ID,
			CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
			$settings->get_access_token(),
			$settings->get_refresh_token(),
			$settings->debug_enabled(),
			'contact_form_7'
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

				// If an error occured, don't attempt to add the subscriber to the Form, as it won't work.
				if ( is_wp_error( $subscriber ) ) {
					return;
				}

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

		// If the request includes the Post ID the form was embedded in,
		// return that URL.
		if ( array_key_exists( '_wpcf7_container_post', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return get_permalink( absint( $_REQUEST['_wpcf7_container_post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Return the AJAX URL.
		return home_url( add_query_arg( null, null ) );

	}

}

// Bootstrap.
add_action(
	'convertkit_initialize_global',
	function () {

		new ConvertKit_ContactForm7();

	}
);
