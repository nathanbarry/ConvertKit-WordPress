<?php
/**
 * ConvertKit Settings Tools class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Tools for debugging and system information that can be accessed at Settings > Kit > Tools.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Settings_Tools extends ConvertKit_Settings_Base {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Initialize WP_Filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$this->settings_key = '_wp_convertkit_tools'; // Required for ConvertKit_Settings_Base, but we don't save settings on the Tools screen.
		$this->name         = 'tools';
		$this->title        = __( 'Tools', 'convertkit' );
		$this->tab_text     = __( 'Tools', 'convertkit' );

		// Register and maybe output notices for this settings screen.
		if ( $this->on_settings_screen( $this->name ) ) {
			add_filter( 'convertkit_settings_base_register_notices', array( $this, 'register_notices' ) );
			add_action( 'convertkit_settings_base_render_before', array( $this, 'maybe_output_notices' ) );
		}

		parent::__construct();

		$this->maybe_perform_actions();
	}

	/**
	 * Registers success and error notices for the Tools screen, to be displayed
	 * depending on the action.
	 *
	 * @since   2.5.1
	 *
	 * @param   array $notices    Regsitered success and error notices.
	 * @return  array
	 */
	public function register_notices( $notices ) {

		return array_merge(
			$notices,
			array(
				'import_configuration_upload_error'      => __( 'An error occured uploading the configuration file.', 'convertkit' ),
				'import_configuration_invalid_file_type' => __( 'The uploaded configuration file isn\'t valid.', 'convertkit' ),
				'import_configuration_empty'             => __( 'The uploaded configuration file contains no settings.', 'convertkit' ),
				'import_configuration_success'           => __( 'Configuration imported successfully.', 'convertkit' ),
			)
		);

	}

	/**
	 * Possibly perform some actions, such as clearing the log, downloading the log,
	 * downloading system information or any third party actions now.
	 *
	 * @since   1.9.7.4
	 */
	private function maybe_perform_actions() {

		// Bail if nonce is invalid.
		if ( ! $this->verify_nonce() ) {
			return;
		}

		$this->maybe_clear_log();
		$this->maybe_download_log();
		$this->maybe_download_system_info();
		$this->maybe_export_configuration();
		$this->maybe_import_configuration();

	}

	/**
	 * Clears the Log.
	 *
	 * @since   1.9.6
	 */
	private function maybe_clear_log() {

		// Bail if the submit button for clearing the debug log was not clicked.
		// Nonce verification already performed in maybe_perform_actions() which calls this function.
		if ( ! array_key_exists( 'convertkit-clear-debug-log', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Clear Log.
		$log = new ConvertKit_Log( CONVERTKIT_PLUGIN_PATH );
		$log->clear();

		// Redirect to Tools screen.
		$this->redirect();

	}

	/**
	 * Prompts a browser download for the log file, if the user clicked
	 * the Download Log button.
	 *
	 * @since   1.9.6
	 */
	private function maybe_download_log() {

		global $wp_filesystem;

		// Bail if the submit button for downloading the debug log was not clicked.
		// Nonce verification already performed in maybe_perform_actions() which calls this function.
		if ( ! array_key_exists( 'convertkit-download-debug-log', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Get Log and download.
		$log = new ConvertKit_Log( CONVERTKIT_PLUGIN_PATH );

		// Download.
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=convertkit-log.txt' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo esc_html( $wp_filesystem->get_contents( $log->get_filename() ) );
		exit();

	}

	/**
	 * Prompts a browser download for the system information, if the user clicked
	 * the Download System Info button.
	 *
	 * @since   1.9.6
	 */
	private function maybe_download_system_info() {

		global $wp_filesystem;

		// Bail if the submit button for downloading the system info was not clicked.
		// Nonce verification already performed in maybe_perform_actions() which calls this function.
		if ( ! array_key_exists( 'convertkit-download-system-info', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Get System Info.
		$system_info = $this->get_system_info();

		// Write contents to temporary file.
		$tmpfile  = tmpfile();
		$filename = stream_get_meta_data( $tmpfile )['uri'];
		$wp_filesystem->put_contents(
			$filename,
			esc_attr( $system_info )
		);

		// Download.
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=convertkit-system-info.txt' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo esc_html( $wp_filesystem->get_contents( $filename ) );
		$wp_filesystem->delete( $filename );
		exit();

	}

	/**
	 * Prompts a browser download for the configuration file, if the user clicked
	 * the Export button.
	 *
	 * @since   1.9.7.4
	 */
	private function maybe_export_configuration() {

		// Bail if the submit button for exporting the configuration was not clicked.
		// Nonce verification already performed in maybe_perform_actions() which calls this function.
		if ( ! array_key_exists( 'convertkit-export', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Initialize classes that hold settings.
		$settings                  = new ConvertKit_Settings();
		$restrict_content_settings = new ConvertKit_Settings_Restrict_Content();

		// Define configuration data to include in the export file.
		$json = wp_json_encode(
			array(
				'settings'         => $settings->get(),
				'restrict_content' => $restrict_content_settings->get(),
			)
		);

		// Download.
		header( 'Content-type: application/x-msdownload' );
		header( 'Content-Disposition: attachment; filename=convertkit-export.json' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();

	}

	/**
	 * Imports the configuration file, if it's included in the form request
	 * and has the expected structure.
	 *
	 * @since   1.9.7.4
	 */
	private function maybe_import_configuration() {

		// Allow us to easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Bail if the submit button for importing the configuration was not clicked.
		// Nonce verification already performed in maybe_perform_actions() which calls this function.
		if ( ! array_key_exists( 'convertkit-import', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if no configuration file was supplied.
		if ( $_FILES['import']['error'] !== 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->redirect_with_error_notice( 'import_configuration_upload_error' );
		}

		// Read file.
		$json = $wp_filesystem->get_contents( $_FILES['import']['tmp_name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Decode.
		$import = json_decode( $json, true );

		// Bail if the data isn't JSON.
		if ( is_null( $import ) ) {
			$this->redirect_with_error_notice( 'import_configuration_invalid_file_type' );
		}

		// Bail if no settings exist.
		if ( ! array_key_exists( 'settings', $import ) ) {
			$this->redirect_with_error_notice( 'import_configuration_empty' );
		}

		// Import: Settings.
		if ( array_key_exists( 'settings', $import ) ) {
			$settings = new ConvertKit_Settings();
			update_option( $settings::SETTINGS_NAME, $import['settings'] );
		}

		// Import: Restrict Content Settings.
		if ( array_key_exists( 'restrict_content', $import ) ) {
			$restrict_content_settings = new ConvertKit_Settings_Restrict_Content();
			update_option( $restrict_content_settings::SETTINGS_NAME, $import['restrict_content'] );
		}

		// Redirect to Tools screen.
		$this->redirect_with_success_notice( 'import_configuration_success' );

	}

	/**
	 * Verifies if the _convertkit_settings_tools_nonce nonce was included in the request,
	 * and if so whether the nonce action is valid.
	 *
	 * @since   1.9.6
	 *
	 * @return  bool
	 */
	private function verify_nonce() {

		// Bail if nonce verification fails.
		if ( ! isset( $_REQUEST['_convertkit_settings_tools_nonce'] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_key( $_REQUEST['_convertkit_settings_tools_nonce'] ), 'convertkit-settings-tools' );

	}

	/**
	 * Outputs the Debug Log and System Info view.
	 *
	 * @since   1.9.6
	 */
	public function render() {

		/**
		 * Performs actions prior to rendering the settings form.
		 *
		 * @since   2.0.0
		 */
		do_action( 'convertkit_settings_base_render_before' );

		// Get Log and System Info.
		$log         = new ConvertKit_Log( CONVERTKIT_PLUGIN_PATH );
		$system_info = $this->get_system_info();

		// Output view.
		require_once CONVERTKIT_PLUGIN_PATH . '/views/backend/settings/tools.php';

		/**
		 * Performs actions after rendering of the settings form.
		 *
		 * @since   2.0.0
		 */
		do_action( 'convertkit_settings_base_render_after' );

	}

	/**
	 * Prints help info for this section
	 */
	public function print_section_info() {

		?>
		<p><?php esc_html_e( 'Tools to help you manage Kit on your site.', 'convertkit' ); ?></p>
		<?php

	}

	/**
	 * Returns the URL for the ConvertKit documentation for this setting section.
	 *
	 * @since   2.0.8
	 *
	 * @return  string  Documentation URL.
	 */
	public function documentation_url() {

		return 'https://help.kit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

	}

	/**
	 * Returns a string comprising of the WordPress system information, with Plugin information
	 * prepended.
	 *
	 * @since   1.9.8.3
	 */
	private function get_system_info() {

		// If we're using WordPress < 5.2, there's no WP_Debug_Data class to fetch system information from.
		if ( version_compare( get_bloginfo( 'version' ), '5.2', '<' ) ) {
			return __( 'WordPress 5.2 or higher is required for system information report.', 'convertkit' );
		}

		// Use WordPress' debug_data() function to get system info, matching how Tools > Site Health > Info works.
		if ( ! class_exists( 'WP_Debug_Data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
		}

		return str_replace( '`', '', WP_Debug_Data::format( WP_Debug_Data::debug_data(), 'debug' ) );

	}

}
