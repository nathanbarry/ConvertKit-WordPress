<?php
/**
 * ConvertKit Settings OAuth class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers OAuth integration that is be accessed at Settings > Kit, when the Plugin
 * has no Access Token specified.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Settings_OAuth extends ConvertKit_Settings_Base {

	/**
	 * Constructor
	 *
	 * @since   2.2.0
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_Settings();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		$this->name     = 'oauth';
		$this->title    = __( 'OAuth', 'convertkit' );
		$this->tab_text = __( 'OAuth', 'convertkit' );

		// Output notices for this settings screen.
		if ( $this->on_settings_screen( 'general' ) ) {
			add_action( 'convertkit_settings_base_render_before', array( $this, 'maybe_output_notices' ) );
		}

		parent::__construct();

		$this->maybe_get_and_store_access_token();
	}

	/**
	 * Requests an access token via OAuth, if an authorization code and verifier are included in the request.
	 *
	 * @since   2.2.0
	 */
	private function maybe_get_and_store_access_token() {

		// Bail if we're not on the settings screen.
		if ( ! $this->on_settings_screen( 'general' ) ) {
			return;
		}

		// Bail if no authorization code is included in the request.
		if ( ! array_key_exists( 'code', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Sanitize token.
		$authorization_code = sanitize_text_field( $_REQUEST['code'] ); // phpcs:ignore WordPress.Security.NonceVerification

		// Exchange the authorization code and verifier for an access token.
		$api    = new ConvertKit_API_V4( CONVERTKIT_OAUTH_CLIENT_ID, CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI );
		$result = $api->get_access_token( $authorization_code );

		// Redirect with an error if we could not fetch the access token.
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'              => '_wp_convertkit_settings',
						'error_description' => $result->get_error_message(),
					),
					'options-general.php'
				)
			);
			exit();
		}

		// Store Access Token, Refresh Token and expiry.
		$this->settings->save(
			array(
				'access_token'  => $result['access_token'],
				'refresh_token' => $result['refresh_token'],
				'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
			)
		);

		// Redirect to General screen, which will now show the Plugin's settings, because the Plugin
		// is now authenticated.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => '_wp_convertkit_settings',
					'success' => 'oauth2_success',
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Outputs the OAuth screen.
	 *
	 * @since   2.2.0
	 */
	public function render() {

		// Determine the OAuth URL to begin the authorization process.
		$api       = new ConvertKit_API_V4( CONVERTKIT_OAUTH_CLIENT_ID, CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI );
		$oauth_url = $api->get_oauth_url( admin_url( 'options-general.php?page=_wp_convertkit_settings' ), get_site_url() );

		/**
		 * Performs actions prior to rendering the settings form.
		 *
		 * @since   2.0.0
		 */
		do_action( 'convertkit_settings_base_render_before' );

		// Output view.
		require_once CONVERTKIT_PLUGIN_PATH . '/views/backend/settings/oauth.php';

		/**
		 * Performs actions after rendering of the settings form.
		 *
		 * @since   2.0.0
		 */
		do_action( 'convertkit_settings_base_render_after' );

	}

	/**
	 * Prints help info for this section
	 *
	 * @since   2.2.0
	 */
	public function print_section_info() {
	}

	/**
	 * Returns the URL for the ConvertKit documentation for this setting section.
	 *
	 * @since   2.2.0
	 *
	 * @return  string  Documentation URL.
	 */
	public function documentation_url() {

		return 'https://help.kit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

	}

}
