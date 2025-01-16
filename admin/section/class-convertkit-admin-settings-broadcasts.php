<?php
/**
 * ConvertKit Settings Broadcasts class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Broadcasts Settings that can be edited at Settings > Kit > Broadcasts.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Admin_Settings_Broadcasts extends ConvertKit_Settings_Base {

	/**
	 * Constructor.
	 *
	 * @since   2.2.9
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_Settings_Broadcasts();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		// Define the programmatic name, Title and Tab Text.
		$this->name     = 'broadcasts';
		$this->title    = __( 'Broadcasts', 'convertkit' );
		$this->tab_text = __( 'Broadcasts', 'convertkit' );

		// Identify that this is beta functionality.
		$this->is_beta = true;

		// Define settings sections.
		$this->settings_sections = array(
			'general' => array(
				'title'    => $this->title,
				'callback' => array( $this, 'print_section_info' ),
				'wrap'     => true,
			),
		);

		// Register and maybe output notices for this settings screen.
		if ( $this->on_settings_screen( $this->name ) ) {
			add_filter( 'convertkit_settings_base_register_notices', array( $this, 'register_notices' ) );
			add_action( 'convertkit_settings_base_render_before', array( $this, 'maybe_output_notices' ) );
		}

		// Enqueue scripts and CSS.
		add_action( 'convertkit_admin_settings_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'convertkit_admin_settings_enqueue_styles', array( $this, 'enqueue_styles' ) );

		parent::__construct();

		$this->maybe_import_now();

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
				'broadcast_import_error'   => __( 'Broadcasts import failed. Please try again.', 'convertkit' ),
				'broadcast_import_success' => __( 'Broadcasts import started. Check the Posts screen shortly to confirm Broadcasts imported successfully.', 'convertkit' ),
			)
		);

	}

	/**
	 * Import Broadcasts now, if requested through the UI.
	 *
	 * @since   2.2.9
	 */
	private function maybe_import_now() {

		// Bail if nonce verification fails.
		if ( ! isset( $_REQUEST['_convertkit_settings_broadcasts_nonce'] ) ) {
			return false;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['_convertkit_settings_broadcasts_nonce'] ), 'convertkit-settings-broadcasts' ) ) {
			return false;
		}

		// Run the import task through WordPress' Cron system now.
		$cron   = new ConvertKit_Cron();
		$result = $cron->run( 'convertkit_resource_refresh_posts' );

		// If an error occured, show it now.
		if ( is_wp_error( $result ) ) {
			// Redirect to Broadcasts screen with error.
			$this->redirect_with_error_description( $result->get_error_message() );
			return;
		}

		// If here, the task scheduled.
		// Redirect with success notice.
		$this->redirect_with_success_notice( 'broadcast_import_success' );

	}

	/**
	 * Enqueues scripts for the Settings > Broadcasts screen.
	 *
	 * @since   2.2.9
	 *
	 * @param   string $section    Settings section / tab (general|tools|restrict-content|broadcasts).
	 */
	public function enqueue_scripts( $section ) {

		// Bail if we're not on the Broadcasts section.
		if ( $section !== $this->name ) {
			return;
		}

		// Enqueue Select2 JS.
		convertkit_select2_enqueue_scripts();

		// Enqueue JS.
		wp_enqueue_script( 'convertkit-admin-settings-conditional-display', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/settings-conditional-display.js', array( 'jquery' ), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Enqueues styles for the Settings > General screen.
	 *
	 * @since   2.2.9
	 *
	 * @param   string $section    Settings section / tab (general|tools|restrict-content|broadcasts).
	 */
	public function enqueue_styles( $section ) {

		// Bail if we're not on the Broadcasts section.
		if ( $section !== $this->name ) {
			return;
		}

		// Enqueue Select2 CSS.
		convertkit_select2_enqueue_styles();

	}

	/**
	 * Registers settings fields for this section.
	 *
	 * @since   2.2.9
	 */
	public function register_fields() {

		// Check if DOMDocument is installed.
		// It should be installed as mosts hosts include php-dom and php-xml modules.
		// If not, disable Broadcast to Posts import functionality as we can't parse
		// imported Broadcasts.
		if ( ! class_exists( 'DOMDocument' ) ) {
			// Disable saving settings.
			$this->save_disabled = true;

			// Return if we're not on the Plugin settings screen.
			if ( ! $this->on_settings_screen( 'broadcasts' ) ) {
				return;
			}

			// Output a notice if we're on the Broadcasts settings screen.
			$this->output_error(
				__( 'Importing public broadcasts from Kit requires the PHP extensions `php-dom` and `php-xml` to be installed. Work with your web host to do this, and reload the page when done.', 'convertkit' )
			);
			return;
		}

		// Initialize classes that will be used.
		$posts = new ConvertKit_Resource_Posts( 'cron' );

		// Define description for the 'Enabled' setting.
		// If enabled, include the next scheduled date and time the Plugin will import broadcasts.
		// If the next scheduled timestamp is 1, the event is running now.
		$enabled_description = '';
		if ( $this->settings->enabled() && $posts->get_cron_event_next_scheduled() && $posts->get_cron_event_next_scheduled() > 1 ) {
			$enabled_description = sprintf(
				'%s %s<br />%s <strong>%s</strong> %s',
				esc_html__( 'Broadcasts will next import at approximately ', 'convertkit' ),
				// The cron event's next scheduled timestamp is always in UTC.
				// Display it converted to the WordPress site's timezone.
				get_date_from_gmt(
					gmdate( 'Y-m-d H:i:s', $posts->get_cron_event_next_scheduled() ),
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
				),
				esc_html__( 'Broadcasts', 'convertkit' ),
				esc_html__( 'must', 'convertkit' ),
				esc_html__( 'have their "Enabled on public feeds" setting enabled in Kit, to be eligible for import.', 'convertkit' )
			);
		}

		add_settings_field(
			'enabled',
			__( 'Enable Automatic Import', 'convertkit' ),
			array( $this, 'enable_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'enabled',
				'label_for'   => 'enabled',
				'label'       => __( 'Enables automatic publication of public Kit Broadcasts as WordPress Posts.', 'convertkit' ),
				'description' => $enabled_description,
			)
		);

		// Render import button if the feature is enabled.
		if ( $this->settings->enabled() && $posts->get_cron_event_next_scheduled() ) {
			add_settings_field(
				'import_button',
				'',
				array( $this, 'import_button_callback' ),
				$this->settings_key,
				$this->name
			);
		}

		add_settings_field(
			'post_status',
			__( 'Status', 'convertkit' ),
			array( $this, 'post_status_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'post_status',
				'label_for'   => 'post_status',
				'description' => __( 'The WordPress Post status to assign imported public broadcasts to.', 'convertkit' ),
			)
		);

		add_settings_field(
			'author_id',
			__( 'Author', 'convertkit' ),
			array( $this, 'author_id_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'author_id',
				'label_for'   => 'author_id',
				'description' => __( 'The WordPress User to set as the author for WordPress Posts created from imported public broadcasts.', 'convertkit' ),
			)
		);

		add_settings_field(
			'category_id',
			__( 'Category', 'convertkit' ),
			array( $this, 'category_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'category_id',
				'label_for'   => 'category_id',
				'description' => __( 'The category to assign imported public broadcasts to.', 'convertkit' ),
			)
		);

		add_settings_field(
			'import_thumbnail',
			__( 'Include Thumbnail', 'convertkit' ),
			array( $this, 'import_thumbnail_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'import_thumbnail',
				'label_for'   => 'import_thumbnail',
				'label'       => __( 'If enabled, the Broadcast\'s thumbnail will be used as the WordPress Post\'s featured image.', 'convertkit' ),
				'description' => '',
			)
		);

		add_settings_field(
			'import_images',
			__( 'Import Images', 'convertkit' ),
			array( $this, 'import_images_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'import_images',
				'label_for'   => 'import_images',
				'label'       => __( 'If enabled, the imported Broadcast\'s inline images will be stored in the Media Library, instead of served by Kit.', 'convertkit' ),
				'description' => '',
			)
		);

		add_settings_field(
			'published_at_min_date',
			__( 'Earliest Date', 'convertkit' ),
			array( $this, 'date_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'published_at_min_date',
				'label_for'   => 'published_at_min_date',
				'description' => __( 'The earliest date to import public broadcasts from, based on the broadcast\'s published date and time.', 'convertkit' ),
			)
		);

		add_settings_field(
			'enabled_export',
			__( 'Enable Export Actions', 'convertkit' ),
			array( $this, 'enable_export_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'      => 'enabled_export',
				'label_for' => 'enabled_export',
				'label'     => __( 'Displays actions in WordPress to create draft broadcasts from existing WordPress posts.', 'convertkit' ),
			)
		);

		add_settings_field(
			'no_styles',
			__( 'Disable Styles', 'convertkit' ),
			array( $this, 'no_styles_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'name'        => 'no_styles',
				'label_for'   => 'no_styles',
				'description' => __( 'Removes inline styles and layout when importing broadcasts and exporting posts.', 'convertkit' ),
			)
		);

	}

	/**
	 * Prints help info for this section
	 *
	 * @since   2.2.9
	 */
	public function print_section_info() {

		?>
		<span class="convertkit-beta-label"><?php esc_html_e( 'Beta', 'convertkit' ); ?></span>
		<p class="description"><?php esc_html_e( 'Defines whether public broadcasts ("Enabled on public feeds") in Kit should automatically be published on this site as WordPress Posts, and whether to enable options to create draft Kit Broadcasts from WordPress Posts.', 'convertkit' ); ?></p>
		<?php

		// If the DISABLE_WP_CRON constant exists and is true, display a warning that this functionality
		// may not work.
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true ) { // @phpstan-ignore-line Constant is not always true.
			?>
			<div class="notice notice-info">
				<p>
					<?php
					printf(
						'%s %s %s %s %s',
						esc_html__( 'We\'ve detected that the', 'convertkit' ),
						'<code>DISABLE_WP_CRON</code>',
						esc_html__( 'constant is enabled. If broadcasts do not import automatically or when using the import button below, either remove this constant from your', 'convertkit' ),
						'<code>wp-config.php</code>',
						esc_html__( 'file, or check that your web host is triggering the WordPress Cron via an alternate method. If importing broadcasts work, no changes to your WordPress configuration file are required.', 'convertkit' )
					);
					?>
				</p>
			</div>
			<?php
		}

	}


	/**
	 * Returns the URL for the ConvertKit documentation for this setting section.
	 *
	 * @since   2.2.9
	 *
	 * @return  string  Documentation URL.
	 */
	public function documentation_url() {

		return 'https://help.kit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

	}

	/**
	 * Renders the input for the Enable setting.
	 *
	 * @since   2.2.9
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function enable_callback( $args ) {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			'on',
			$this->settings->enabled(), // phpcs:ignore WordPress.Security.EscapeOutput
			$args['label'],  // phpcs:ignore WordPress.Security.EscapeOutput
			$args['description'], // phpcs:ignore WordPress.Security.EscapeOutput
			array( 'convertkit-conditional-display' )
		);

	}

	/**
	 * Renders the import button.
	 *
	 * @since   2.2.9
	 */
	public function import_button_callback() {

		// Define link to import Broadcasts now.
		$import_url = add_query_arg(
			array(
				'page'                                  => '_wp_convertkit_settings',
				'tab'                                   => 'broadcasts',
				'_convertkit_settings_broadcasts_nonce' => wp_create_nonce( 'convertkit-settings-broadcasts' ),
			),
			'options-general.php'
		);

		echo '<a href="' . esc_url( $import_url ) . '" class="button button-secondary enabled">' . esc_html__( 'Import now', 'convertkit' ) . '</a>';

	}

	/**
	 * Renders the input for the status setting.
	 *
	 * @since   2.3.4
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function post_status_callback( $args ) {

		// Build field.
		$select_field = $this->get_select_field(
			$args['name'],
			$this->settings->post_status(),
			get_post_statuses(),
			$args['description'],
			array(
				'enabled',
				'convertkit-select2',
			)
		);

		// Output field.
		echo '<div class="convertkit-select2-container">' . $select_field . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Renders the input for the author setting.
	 *
	 * @since   2.3.9
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function author_id_callback( $args ) {

		// Build field.
		$select_field = wp_dropdown_users(
			array(
				'echo'             => false,
				'selected'         => $this->settings->author_id(),
				'include_selected' => true,
				'name'             => $this->settings_key . '[' . $args['name'] . ']',
				'id'               => $this->settings_key . '_' . $args['name'],
				'class'            => 'enabled convertkit-select2',
			)
		);

		// Output field.
		echo '<div class="convertkit-select2-container">' . $select_field . '</div>' . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Renders the input for the category setting.
	 *
	 * @since   2.2.9
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function category_callback( $args ) {

		// Build field.
		$select_field = wp_dropdown_categories(
			array(
				'show_option_none' => __( 'None', 'convertkit' ),
				'echo'             => 0,
				'hierarhical'      => 1,
				'name'             => $this->settings_key . '[' . $args['name'] . ']',
				'id'               => $this->settings_key . '_' . $args['name'],
				'class'            => 'convertkit-select2 enabled',
				'selected'         => $this->settings->category_id(),
				'taxonomy'         => 'category',
				'hide_empty'       => false,
			)
		);

		// Output field.
		echo '<div class="convertkit-select2-container">' . $select_field . '</div>' . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Renders the input for the Import Thumbnail setting.
	 *
	 * @since   2.4.1
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function import_thumbnail_callback( $args ) {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			'on',
			$this->settings->import_thumbnail(), // phpcs:ignore WordPress.Security.EscapeOutput
			$args['label'],  // phpcs:ignore WordPress.Security.EscapeOutput
			$args['description'], // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'enabled',
			)
		);

	}

	/**
	 * Renders the input for the Import Images setting.
	 *
	 * @since   2.6.3
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function import_images_callback( $args ) {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			'on',
			$this->settings->import_images(), // phpcs:ignore WordPress.Security.EscapeOutput
			$args['label'],  // phpcs:ignore WordPress.Security.EscapeOutput
			$args['description'], // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'enabled',
			)
		);

	}

	/**
	 * Renders the input for the date setting.
	 *
	 * @since   2.2.9
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function date_callback( $args ) {

		// Output field.
		echo $this->get_date_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			esc_attr( $this->settings->published_at_min_date() ),
			$args['description'], // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'enabled',
			)
		);

	}

	/**
	 * Renders the input for the Enable Export setting.
	 *
	 * @since   2.4.0
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function enable_export_callback( $args ) {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			'on',
			$this->settings->enabled_export(), // phpcs:ignore WordPress.Security.EscapeOutput
			$args['label']  // phpcs:ignore WordPress.Security.EscapeOutput
		);

	}

	/**
	 * Renders the input for the No Styles setting.
	 *
	 * @since   2.2.9
	 *
	 * @param   array $args   Setting field arguments (name,description).
	 */
	public function no_styles_callback( $args ) {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			$args['name'],
			'on',
			$this->settings->no_styles(), // phpcs:ignore WordPress.Security.EscapeOutput
			$args['description'] // phpcs:ignore WordPress.Security.EscapeOutput
		);

	}

	/**
	 * Sanitizes the settings prior to being saved.
	 *
	 * @since   2.4.1
	 *
	 * @param   array $settings   Submitted Settings Fields.
	 * @return  array               Sanitized Settings with Defaults
	 */
	public function sanitize_settings( $settings ) {

		// If the 'Include Thumbnail' setting isn't checked, it won't be included
		// in the array of settings, and the defaults will enable this.
		// Therefore, if the setting doesn't exist, set it to blank.
		if ( ! array_key_exists( 'import_thumbnail', $settings ) ) {
			$settings['import_thumbnail'] = '';
		}

		// If the 'Include Images' setting isn't checked, it won't be included
		// in the array of settings, and the defaults will enable this.
		// Therefore, if the setting doesn't exist, set it to blank.
		if ( ! array_key_exists( 'import_images', $settings ) ) {
			$settings['import_images'] = '';
		}

		// Merge settings with defaults.
		$settings = wp_parse_args( $settings, $this->settings->get_defaults() );

		// Return settings to be saved.
		return $settings;

	}

}

// Bootstrap.
add_action(
	'convertkit_admin_settings_register_sections',
	function ( $sections ) {

		$sections['broadcasts'] = new ConvertKit_Admin_Settings_Broadcasts();
		return $sections;

	}
);
