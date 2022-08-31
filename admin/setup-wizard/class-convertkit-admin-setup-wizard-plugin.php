<?php
/**
 * ConvertKit Admin Setup Wizard class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Provides a UI for setting up the ConvertKit Plugin when activated for the
 * first time.
 *
 * If the Plugin has previously been configured (i.e. settings exist in the database),
 * this UI isn't triggered on activation.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Admin_Setup_Wizard_Plugin extends ConvertKit_Admin_Setup_Wizard {

	/**
	 * Holds the ConvertKit Forms resource class.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|ConvertKit_Resource_Forms
	 */
	public $forms = false;

	/**
	 * Holds the ConvertKit Settings class.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|ConvertKit_Settings
	 */
	public $settings = false;

	/**
	 * Holds the nonce for validating a frontend preview request.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|string
	 */
	public $preview_nonce = false;

	/**
	 * Holds the URL to the most recent WordPress Post, used when previewing a Form below a Post
	 * on the frontend site.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|string
	 */
	public $preview_post_url = false;

	/**
	 * Holds the URL to the most recent WordPress Page, used when previewing a Form below a Page
	 * on the frontend site.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|string
	 */
	public $preview_page_url = false;

	/**
	 * The programmatic name for this wizard.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     string
	 */
	public $page_name = 'convertkit-setup';

	/**
	 * The URL to take the user to when they click the Exit link.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     string
	 */
	public $exit_url = 'options-general.php?page=_wp_convertkit_settings';

	/**
	 * Registers action and filter hooks.
	 *
	 * @since   1.9.8.5
	 */
	public function __construct() {

		// Define details for each step in the setup process.
		$this->steps = array(
			1 => array(
				'name' => __( 'Setup', 'convertkit' ),
			),
			2 => array(
				'name'        => __( 'Connect Account', 'convertkit' ),
				'next_button' => array(
					'label' => __( 'Connect', 'convertkit' ),
				),
			),
			3 => array(
				'name'        => __( 'Form Configuration', 'convertkit' ),
				'next_button' => array(
					'label' => __( 'Finish Setup', 'convertkit' ),
				),
			),
			4 => array(
				'name' => __( 'Done', 'convertkit' ),
			),
		);

		add_action( 'admin_init', array( $this, 'maybe_redirect_to_setup_screen' ), 9999 );
		add_action( 'convertkit_admin_setup_wizard_process_form_convertkit-setup', array( $this, 'process_form' ) );
		add_action( 'convertkit_admin_setup_wizard_load_screen_data_convertkit-setup', array( $this, 'load_screen_data' ) );

		// Call parent class constructor.
		parent::__construct();

	}

	/**
	 * Redirects to the setup screen if a transient was created on Plugin activation,
	 * and the Plugin has no API Key and Secret configured.
	 *
	 * @since   1.9.8.5
	 */
	public function maybe_redirect_to_setup_screen() {

		// If no transient was set by the Plugin's activation routine, don't redirect to the setup screen.
		// This transient will only exist for 30 seconds by design, so we don't hijack a later WordPress
		// Admin screen request.
		if ( ! get_transient( $this->page_name ) ) {
			return;
		}

		// Delete the transient, so we don't redirect again.
		delete_transient( $this->page_name );

		// Check if any settings exist.
		// If they do, the Plugin has already been setup, so no need to show the setup screen.
		$settings = new ConvertKit_Settings();
		if ( $settings->has_api_key_and_secret() ) {
			return;
		}

		// Show the setup screen.
		wp_safe_redirect( admin_url( 'index.php?page=' . $this->page_name ) );
		exit;

	}

	/**
	 * Process posted data from the submitted form.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   int $step   Current step.
	 */
	public function process_form( $step ) {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Nonce verification has been performed in ConvertKit_Admin_Setup_Wizard:process_form(), prior to calling this function.

		// Depending on the step, process the form data.
		switch ( $step ) {
			case 3:
				// Check that the API Key and Secret work.
				$api_key    = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );
				$api_secret = sanitize_text_field( wp_unslash( $_POST['api_secret'] ) );

				$api    = new ConvertKit_API( $api_key, $api_secret );
				$result = $api->account();

				// Show an error message if Account Details could not be fetched e.g. API credentials supplied are invalid.
				if ( is_wp_error( $result ) ) {
					// Decrement the step.
					$this->step  = ( $this->step - 1 );
					$this->error = $result->get_error_message();
					return;
				}

				// If here, API credentials are valid.
				// Save them.
				$settings = new ConvertKit_Settings();
				$settings->save(
					array(
						'api_key'    => $api_key,
						'api_secret' => $api_secret,
					)
				);
				break;

			case 4:
				// Save Default Page and Post Form settings.
				$settings = new ConvertKit_Settings();
				$settings->save(
					array(
						'post_form' => sanitize_text_field( $_POST['post_form'] ),
						'page_form' => sanitize_text_field( $_POST['page_form'] ),
					)
				);
				break;
		}

		// phpcs:enable

	}

	/**
	 * Load any data into class variables for the given setup wizard name and current step.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   int $step   Current step.
	 */
	public function load_screen_data( $step ) {

		switch ( $step ) {
			case 2:
				// Load settings class.
				$this->settings = new ConvertKit_Settings();
				break;

			case 3:
				// Re-load settings class now that the API Key and Secret has been defined.
				$this->settings = new ConvertKit_Settings();

				// Fetch Forms.
				$this->forms = new ConvertKit_Resource_Forms();
				$this->forms->refresh();

				// If no Forms exist in ConvertKit, change the next button label.
				$this->steps[3]['next_button']['label'] = __( 'I\'ve created a form in ConvertKit', 'convertkit' );

				// Fetch a Post and a Page, appending the preview nonce to their URLs.
				$this->preview_nonce = wp_create_nonce( 'convertkit-preview-form' );

				$this->preview_post_url = add_query_arg(
					array(
						'convertkit-preview-nonce' => $this->preview_nonce,
					),
					get_permalink( $this->get_most_recent( 'post' ) )
				);

				$this->preview_page_url = add_query_arg(
					array(
						'convertkit-preview-nonce' => $this->preview_nonce,
					),
					get_permalink( $this->get_most_recent( 'page' ) )
				);
				break;
		}

	}

	/**
	 * Returns the most recent published Post ID.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   string $post_type  Post Type.
	 * @return  false|int           Post ID
	 */
	private function get_most_recent( $post_type = 'post' ) {

		// Run query.
		$query = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		// Return false if no Posts exist for the given type.
		if ( empty( $query->posts ) ) {
			return false;
		}

		// Return the Post ID.
		return $query->posts[0];

	}

}