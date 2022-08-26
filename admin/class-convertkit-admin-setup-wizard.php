<?php
/**
 * ConvertKit Admin Setup Wizard class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Provides a UI for displaying a step by step wizard style screen in the WordPress
 * Administration.
 * 
 * To use this class, extend it with your own configuration.
 * 
 * Refer to the admin/setup-wizard folder for current implementations.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Admin_Setup_Wizard {

	/**
	 * The steps available in this wizard.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	array
	 */
	public $steps = array();

	/**
	 * Holds an error message to display on screen.
	 *
	 * @since   1.9.8.5
	 *
	 * @var     bool|string
	 */
	public $error = false;

	/**
	 * The current step in the setup process the user is on.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	int
	 */
	public $step = 1;

	/**
	 * The programmatic name of the setup screen.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	bool|string
	 */
	public $page_name = false;

	/**
	 * The URL to take the user to when they click the Exit link.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	bool|string
	 */
	public $exit_url = false;

	/**
	 * Holds the URL for the current step in the setup process.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	bool|string
	 */
	private $current_step_url = false;

	/**
	 * Holds the URL to the next step in the setup process.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	bool|string
	 */
	private $next_step_url = false;

	/**
	 * Holds the URL to the previous step in the setup process.
	 * 
	 * @since 	1.9.8.5
	 * 
	 * @var 	bool|string
	 */
	private $previous_step_url = false;

	/**
	 * Registers action and filter hooks.
	 *
	 * @since   1.9.8.5
	 */
	public function __construct() {

		// Bail if no page name is defined.
		if ( $this->page_name === false ) {
			return;
		}

		// Define actions to register the setup screen.
		add_action( 'admin_menu', array( $this, 'register_screen' ) );
		add_action( 'admin_head', array( $this, 'hide_screen_from_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_load_setup_screen' ) );
		
	}

	/**
	 * Register the setup screen in WordPress' Dashboard, so that index.php?page={$this->page_name}
	 * does not 404 when in the WordPress Admin interface.
	 *
	 * @since   1.9.8.5
	 */
	public function register_screen() {

		add_dashboard_page( '', '', 'edit_posts', $this->page_name, '' );

	}

	/**
	 * Hides the menu registered when register_screen() above is called, otherwise
	 * we would have a blank submenu entry below the Dashboard menu.
	 * 
	 * @since 	1.9.8.5
	 */ 
	public function hide_screen_from_menu() {

		remove_submenu_page( 'index.php', $this->page_name );

	}

	/**
	 * Loads the setup screen if the request URL is for this class
	 *
	 * @since   1.9.8.5
	 */
	public function maybe_load_setup_screen() {

		// Bail if this isn't a request for the setup screen.
		if ( ! $this->is_setup_request() ) {
			return;
		}

		// Define current screen, so that calls to get_current_screen() tell Plugins which screen is loaded.
		set_current_screen( $this->page_name );

		// Populate class variables, such as the current step and URLs.
		$this->populate_step_and_urls();

		// Process any posted form data.
		$this->process_form();

		// Load any data for the current screen.
		$this->load_screen_data();

		// Load scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		
		// Output custom HTML for the setup screen.
		$this->output_header();
		$this->output_content();
		$this->output_footer();
		exit;

	}

	/**
	 * Populates the class variables with key information, covering:
	 * - current step in the setup process
	 * - previous, current and next step URLs.
	 * 
	 * @since 	1.9.8.5
	 */
	private function populate_step_and_urls() {

		// Define the step the user is on in the setup process.
		$this->step = ( isset( $_REQUEST['step'] ) ? absint( $_REQUEST['step'] ) : 1 );

		// Define the current step URL.
		$this->current_step_url = add_query_arg(
			array(
				'page' => $this->page_name,
				'step' => $this->step,
			),
			admin_url( 'index.php' )
		);

		// Define the previous step URL if we're not on the first or last step.
		if ( $this->step > 1 && $this->step < count( $this->steps ) ) {
			$this->previous_step_url = add_query_arg(
				array(
					'page' => $this->page_name,
					'step' => ( $this->step - 1 ),
				),
				admin_url( 'index.php' )
			);
		}

		// Define the next step URL if we're not on the last page.
		if ( $this->step < count( $this->steps ) ) {
			$this->next_step_url = add_query_arg(
				array(
					'page' => $this->page_name,
					'step' => ( $this->step + 1 ),
				),
				admin_url( 'index.php' )
			);
		}

	}

	/**
	 * Process submitted form data for the given setup wizard name and current step.
	 * 
	 * @since 	1.9.8.5
	 */
	private function process_form() {

		// Run security checks.
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), $this->page_name ) ) {
			$this->error = __( 'Invalid nonce specified.', 'convertkit' );
			return;
		}

		/**
		 * Process submitted form data for the given setup wizard name and current step.
		 * 
		 * @since 	1.9.8.5
		 * 
		 * @param 	int 	$this->step 	Current step number.
		 */
		do_action( 'convertkit_admin_setup_wizard_process_form_' . $this->page_name, $this->step );

	}

	/**
	 * Load any data into class variables for the given setup wizard name and current step.
	 * 
	 * @since 	1.9.8.5
	 */
	private function load_screen_data() {

		/**
		 * Load any data into class variables for the given setup wizard name and current step.
		 * 
		 * @since 	1.9.8.5
		 * 
		 * @param 	int 	$this->step 	Current step number.
		 */
		do_action( 'convertkit_admin_setup_wizard_load_screen_data_' . $this->page_name, $this->step );

	}

	/**
	 * Enqueue CSS when viewing the Setup screen.
	 *
	 * @since   1.9.8.5
	 */
	public function enqueue_scripts() {

		// Enqueue Select2 JS.
		convertkit_select2_enqueue_scripts();

		// Enqueue Setup JS.
		wp_enqueue_script( 'convertkit-admin-setup-wizard', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/setup-wizard.js', array( 'jquery' ), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Enqueue CSS when viewing the setup screen.
	 *
	 * @since   1.9.8.5
	 */
	public function enqueue_styles() {

		// Enqueue WordPress default styles.
		wp_enqueue_style( 'common' );
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'forms' );

		// Enqueue Select2 CSS.
		convertkit_select2_enqueue_styles();

		// Enqueue styles for the setup wizard.
		wp_enqueue_style( 'convertkit-admin-setup-wizard', CONVERTKIT_PLUGIN_URL . 'resources/backend/css/setup-wizard.css', array(), CONVERTKIT_PLUGIN_VERSION );

	}

	/**
	 * Outputs the <head> and opening <body> tag for the standalone setup screen
	 *
	 * @since   1.9.8.5
	 */
	private function output_header() {

		// Remove scripts.
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		// Enqueue scripts.
		do_action( 'admin_enqueue_scripts' );

		// Load header view.
		include_once CONVERTKIT_PLUGIN_PATH . '/views/backend/setup-wizard/header.php';

	}

	/**
	 * Outputs the HTML for the <body> section for the standalone setup screen
	 * and defines any form option data that might be needed.
	 *
	 * @since   1.9.8.5
	 */
	private function output_content() {

		// Load content view.
		include_once CONVERTKIT_PLUGIN_PATH . '/views/backend/setup-wizard/' . $this->page_name . '/content-' . $this->step . '.php';

	}

	/**
	 * Outputs the closing </body> and </html> tags, and runs some WordPress actions, for the standalone setup screen
	 *
	 * @since   1.9.8.5
	 */
	private function output_footer() {

		do_action( 'admin_footer', '' );
		do_action( 'admin_print_footer_scripts' );

		// Load footer view.
		include_once CONVERTKIT_PLUGIN_PATH . '/views/backend/setup-wizard/footer.php';

	}

	/**
	 * Determines if the request is for the setup screen
	 *
	 * @since   1.9.8.5
	 *
	 * @return  bool    Is setup screen request
	 */
	private function is_setup_request() {

		// Don't load if this is an AJAX call.
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		// Bail if we're not on the setup screen.
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}
		if ( sanitize_text_field( $_GET['page'] ) !== $this->page_name ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		return true;

	}

}
