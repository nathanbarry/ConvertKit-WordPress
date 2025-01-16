<?php
/**
 * ConvertKit Settings General class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers General Settings that can be edited at Settings > Kit > General.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Settings_General extends ConvertKit_Settings_Base {

	/**
	 * Holds the API instance.
	 *
	 * @since   1.9.6
	 *
	 * @var     ConvertKit_API_V4
	 */
	private $api;

	/**
	 * Holds the ConvertKit Account Name.
	 *
	 * @since   1.9.6
	 *
	 * @var     bool|WP_Error|array
	 */
	private $account = false;

	/**
	 * Holds the ConvertKit Forms Resource.
	 *
	 * @since   1.9.6
	 *
	 * @var     bool|ConvertKit_Resource_Forms
	 */
	private $forms = false;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_Settings();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		// Define the programmatic name, Title and Tab Text.
		$this->name     = 'general';
		$this->title    = __( 'General Settings', 'convertkit' );
		$this->tab_text = __( 'General', 'convertkit' );

		// Define settings sections.
		$this->settings_sections = array(
			'general'  => array(
				'title'    => $this->title,
				'callback' => array( $this, 'print_section_info' ),
				'wrap'	   => true,
			),
			'advanced' => array(
				'title'    => __( 'Advanced', 'convertkit' ),
				'callback' => array( $this, 'print_section_info_advanced' ),
				'wrap'	   => true,
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

		$this->check_credentials();
		$this->maybe_disconnect();

	}

	/**
	 * Registers success and error notices for the General screen, to be displayed
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
				'oauth2_success' => __( 'Successfully authorized with Kit.', 'convertkit' ),
			)
		);

	}

	/**
	 * Test the access token, if it exists.
	 * If the access token has been revoked or is invalid, remove it from the settings now.
	 *
	 * @since   2.5.0
	 */
	private function check_credentials() {

		// Bail if we're not on the settings screen.
		if ( ! $this->on_settings_screen( $this->name ) ) {
			return;
		}

		// Bail if no access and refresh token exist.
		if ( ! $this->settings->has_access_and_refresh_token() ) {
			return;
		}

		// Initialize the API.
		$this->api = new ConvertKit_API_V4(
			CONVERTKIT_OAUTH_CLIENT_ID,
			CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
			$this->settings->get_access_token(),
			$this->settings->get_refresh_token(),
			$this->settings->debug_enabled(),
			'settings'
		);

		// Get Account Details, which we'll use in account_name_callback(), but also lets us test
		// whether the API credentials are valid.
		$this->account = $this->api->get_account();

		// If the request succeeded, no need to perform further actions.
		if ( ! is_wp_error( $this->account ) ) {
			// Remove any existing persistent notice.
			WP_ConvertKit()->get_class( 'admin_notices' )->delete( 'authorization_failed' );

			return;
		}

		// Depending on the error code, maybe persist a notice in the WordPress Administration until the user
		// fixes the problem.
		switch ( $this->account->get_error_data( $this->account->get_error_code() ) ) {
			case 401:
				// Access token either expired or was revoked in ConvertKit.
				// Remove from settings.
				$this->settings->delete_credentials();

				// Display a site wide notice.
				WP_ConvertKit()->get_class( 'admin_notices' )->add( 'authorization_failed' );

				// Redirect to General screen, which will now show the ConvertKit_Settings_OAuth screen, because
				// the Plugin has no access token.
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => $this->settings_key,
						),
						'options-general.php'
					)
				);
				exit();
		}

		// Output a non-401 error now.
		$this->output_error( $this->account->get_error_message() );

	}

	/**
	 * Deletes the OAuth Access Token, Refresh Token and Expiry from the Plugin's settings, if the user
	 * clicked the Disconnect button.
	 *
	 * @since   2.5.0
	 */
	private function maybe_disconnect() {

		// Bail if we're not on the settings screen.
		if ( ! $this->on_settings_screen( $this->name ) ) {
			return;
		}

		// Bail if nonce verification fails.
		if ( ! isset( $_REQUEST['_convertkit_settings_oauth_disconnect'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['_convertkit_settings_oauth_disconnect'] ), 'convertkit-oauth-disconnect' ) ) {
			return;
		}

		// Delete Access Token.
		$settings = new ConvertKit_Settings();
		$settings->delete_credentials();

		// Delete cached resources.
		$creator_network = new ConvertKit_Resource_Creator_Network_Recommendations();
		$forms           = new ConvertKit_Resource_Forms();
		$landing_pages   = new ConvertKit_Resource_Landing_Pages();
		$posts           = new ConvertKit_Resource_Posts();
		$products        = new ConvertKit_Resource_Products();
		$tags            = new ConvertKit_Resource_Tags();
		$creator_network->delete();
		$forms->delete();
		$landing_pages->delete();
		$posts->delete();
		$products->delete();
		$tags->delete();

		// Redirect to General screen, which will now show the ConvertKit_Settings_OAuth screen, because
		// the Plugin has no access token.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => $this->settings_key,
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Enqueues scripts for the Settings > General screen.
	 *
	 * @since   2.2.4
	 *
	 * @param   string $section    Settings section / tab (general|tools|restrict-content).
	 */
	public function enqueue_scripts( $section ) {

		// Bail if we're not on the general section.
		if ( $section !== $this->name ) {
			return;
		}

		// Enqueue Select2 JS.
		convertkit_select2_enqueue_scripts();

		// Enqueue JS.
		wp_enqueue_script( 'convertkit-admin-preview-output', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/preview-output.js', array( 'jquery' ), CONVERTKIT_PLUGIN_VERSION, true );
		wp_enqueue_script( 'convertkit-admin-settings-conditional-display', CONVERTKIT_PLUGIN_URL . 'resources/backend/js/settings-conditional-display.js', array( 'jquery' ), CONVERTKIT_PLUGIN_VERSION, true );

	}

	/**
	 * Enqueues styles for the Settings > General screen.
	 *
	 * @since   2.2.4
	 *
	 * @param   string $section    Settings section / tab (general|tools|restrict-content).
	 */
	public function enqueue_styles( $section ) {

		// Bail if we're not on the general section.
		if ( $section !== $this->name ) {
			return;
		}

		// Enqueue Select2 CSS.
		convertkit_select2_enqueue_styles();

	}

	/**
	 * Registers settings fields for this section.
	 */
	public function register_fields() {

		// Initialize resource classes.
		$this->maybe_initialize_and_refresh_resources();

		add_settings_field(
			'account_name',
			__( 'Account Name', 'convertkit' ),
			array( $this, 'account_name_callback' ),
			$this->settings_key,
			$this->name
		);

		// Initialize resource classes and perform a refresh if this hasn't yet been done.
		foreach ( convertkit_get_supported_post_types() as $supported_post_type ) {
			// Get Post Type's Label.
			$post_type = get_post_type_object( $supported_post_type );

			// Skip if the Post Type doesn't exist.
			if ( ! $post_type ) {
				continue;
			}

			// Add Settings Fields.
			add_settings_field(
				$supported_post_type . '_form',
				sprintf(
					/* translators: Post Type Name, plural */
					__( 'Default Form (%s)', 'convertkit' ),
					$post_type->label
				),
				array( $this, 'default_form_callback' ),
				$this->settings_key,
				$this->name,
				array(
					'label_for'        => '_wp_convertkit_settings_' . $supported_post_type . '_form',
					'post_type'        => $supported_post_type,
					'post_type_object' => $post_type,
				)
			);

			if ( $this->forms->exist() ) {
				add_settings_field(
					$supported_post_type . '_form_position',
					sprintf(
						/* translators: Post Type Name, plural */
						__( 'Form Position (%s)', 'convertkit' ),
						$post_type->label
					),
					array( $this, 'default_form_position_callback' ),
					$this->settings_key,
					$this->name,
					array(
						'label_for'        => '_wp_convertkit_settings_' . $supported_post_type . '_form_position',
						'post_type'        => $supported_post_type,
						'post_type_object' => $post_type,
					)
				);

				add_settings_field(
					$supported_post_type . '_form_position_element',
					'',
					array( $this, 'default_form_position_element_callback' ),
					$this->settings_key,
					$this->name,
					array(
						'label_for'        => '_wp_convertkit_settings_' . $supported_post_type . '_form_position_element',
						'post_type'        => $supported_post_type,
						'post_type_object' => $post_type,
					)
				);
			}
		}

		add_settings_field(
			'non_inline_form',
			__( 'Default Forms (Site Wide)', 'convertkit' ),
			array( $this, 'non_inline_form_callback' ),
			$this->settings_key,
			$this->name,
			array(
				'label_for' => 'non_inline_form',
			)
		);

		// Advanced.
		add_settings_field(
			'debug',
			__( 'Debug', 'convertkit' ),
			array( $this, 'debug_callback' ),
			$this->settings_key,
			$this->name . '-advanced',
			array(
				'label_for' => 'debug',
			)
		);

		add_settings_field(
			'no_scripts',
			__( 'Disable JavaScript', 'convertkit' ),
			array( $this, 'no_scripts_callback' ),
			$this->settings_key,
			$this->name . '-advanced',
			array(
				'label_for' => 'no_scripts',
			)
		);

		add_settings_field(
			'no_css',
			__( 'Disable CSS', 'convertkit' ),
			array( $this, 'no_css_callback' ),
			$this->settings_key,
			$this->name . '-advanced',
			array(
				'label_for' => 'no_css',
			)
		);

	}

	/**
	 * Prints help info for this section
	 */
	public function print_section_info() {

		?>
		<p><?php esc_html_e( 'Choosing a default form will embed it at the top, bottom or top and bottom of every post or page (in single view only) across your site.', 'convertkit' ); ?></p>
		<p><?php esc_html_e( 'If you wish to turn off form embedding or select a different form for an individual post or page, you can do so using the Kit meta box on the edit page.', 'convertkit' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: [convertkit] shortcode, wrapped in <code> tags */
				esc_html__( 'The default form can be inserted into the middle of post or page content by using either the %s shortcode or block.', 'convertkit' ),
				'<code>[convertkit]</code>'
			);
			?>
		</p>
		<?php

	}

	/**
	 * Prints help info for the advanced section of the settings screen.
	 *
	 * @since   2.7.1
	 */
	public function print_section_info_advanced() {

		?>
		<p class="description"><?php esc_html_e( 'Defines advanced configuration settings, usually when working with support or needing to disable JS or CSS.', 'convertkit' ); ?></p>
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
	 * Outputs the Account Name
	 *
	 * @since   1.9.6
	 */
	public function account_name_callback() {

		// Output Account Name.
		$html = sprintf(
			'<p>%s</p>',
			isset( $this->account['account']['name'] ) ? esc_attr( $this->account['account']['name'] ) : esc_html__( '(Not specified)', 'convertkit' )
		);

		// Display an option to disconnect.
		$html .= sprintf(
			'<p><a href="%1$s" class="button button-secondary">%2$s</a></p>',
			esc_url(
				add_query_arg(
					array(
						'page' => '_wp_convertkit_settings',
						'_convertkit_settings_oauth_disconnect' => wp_create_nonce( 'convertkit-oauth-disconnect' ),
					),
					'options-general.php'
				)
			),
			esc_html__( 'Disconnect', 'convertkit' )
		);

		// Output has already been run through escaping functions above.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Initialize resource classes and perform a and refresh of resources,
	 * if initialization has not yet taken place.
	 *
	 * @since   2.5.9
	 */
	public function maybe_initialize_and_refresh_resources() {

		// If the Forms resource class is initialized, this has already been done.
		if ( $this->forms !== false ) {
			return;
		}

		// Initialize forms resource class.
		$this->forms = new ConvertKit_Resource_Forms( 'settings' );

		// Don't refresh resources if we're not on the settings screen, as
		// it's a resource intense process that can take several seconds.
		// We don't want to block other parts of the admin UI.
		if ( ! $this->on_settings_screen( $this->name ) ) {
			return;
		}

		// Refresh Forms.
		$this->forms->refresh();

		// Also refresh Landing Pages, Tags and Posts. Whilst not displayed in the Plugin Settings, this ensures up to date
		// lists are stored for when editing e.g. Pages.
		$landing_pages = new ConvertKit_Resource_Landing_Pages( 'settings' );
		$landing_pages->refresh();

		remove_all_actions( 'convertkit_resource_refreshed_posts' );
		$posts = new ConvertKit_Resource_Posts( 'settings' );
		$posts->refresh();

		$products = new ConvertKit_Resource_Products( 'settings' );
		$products->refresh();

		$sequences = new ConvertKit_Resource_Sequences( 'settings' );
		$sequences->refresh();

		$tags = new ConvertKit_Resource_Tags( 'settings' );
		$tags->refresh();
	}

	/**
	 * Renders the input for the Default Form setting for the given Post Type.
	 *
	 * @since  1.9.6
	 *
	 * @param   array $args  Field arguments.
	 */
	public function default_form_callback( $args ) {

		// Bail if no Forms exist.
		if ( ! $this->forms->exist() ) {
			esc_html_e( 'No Forms exist in Kit.', 'convertkit' );
			echo '<br /><a href="' . esc_url( convertkit_get_new_form_url() ) . '" target="_blank">' . esc_html__( 'Click here to create your first form', 'convertkit' ) . '</a>';
			return;
		}

		// Build description with preview link.
		$description = false;
		$preview_url = WP_ConvertKit()->get_class( 'preview_output' )->get_preview_form_url( $args['post_type'] );
		if ( $preview_url ) {
			// Include a preview link in the description.
			$description = sprintf(
				'%s %s %s',
				sprintf(
					/* translators: Post Type name, plural */
					esc_html__( 'Select a form above to automatically output below all %s.', 'convertkit' ),
					$args['post_type_object']->label
				),
				'<a href="' . esc_url( $preview_url ) . '" id="convertkit-preview-form-' . esc_attr( $args['post_type'] ) . '" target="_blank">' . esc_html__( 'Click here', 'convertkit' ) . '</a>',
				esc_html__( 'to preview how this will display.', 'convertkit' )
			);
		} else {
			// Just output the field's description.
			$description = sprintf(
				/* translators: Post Type name, plural */
				esc_html__( 'Select a form above to automatically output below all %s.', 'convertkit' ),
				$args['post_type_object']->label
			);
		}

		// Build field.
		$select_field = $this->forms->get_select_field_all(
			$this->settings_key . '[' . $args['post_type'] . '_form]',
			$this->settings_key . '_' . $args['post_type'] . '_form',
			array(
				'convertkit-select2',
				'convertkit-preview-output-link',
			),
			$this->settings->get_default_form( $args['post_type'] ),
			array(
				'default' => esc_html__( 'None', 'convertkit' ),
			),
			array(
				'data-target' => '#convertkit-preview-form-' . esc_attr( $args['post_type'] ),
				'data-link'   => esc_attr( $preview_url ) . '&convertkit_form_id=',
			),
			$description
		);

		// Output field.
		echo '<div class="convertkit-select2-container">' . $select_field . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Renders the input for the Default Form Position setting for the given Post Type.
	 *
	 * @since  2.5.8
	 *
	 * @param   array $args  Field arguments.
	 */
	public function default_form_position_callback( $args ) {

		echo $this->get_select_field( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$args['post_type'] . '_form_position',
			esc_attr( $this->settings->get_default_form_position( $args['post_type'] ) ),
			array(
				'before_content'       => sprintf(
					/* translators: Post type singular name */
					esc_attr__( 'Before %s content', 'convertkit' ),
					esc_attr( $args['post_type_object']->labels->singular_name )
				),
				'after_content'        => sprintf(
					/* translators: Post type singular name */
					esc_attr__( 'After %s content', 'convertkit' ),
					esc_attr( $args['post_type_object']->labels->singular_name )
				),
				'before_after_content' => sprintf(
					/* translators: Post type singular name */
					esc_attr__( 'Before and after %s content', 'convertkit' ),
					esc_attr( $args['post_type_object']->labels->singular_name )
				),
				'after_element'        => esc_html__( 'After element', 'convertkit' ),
			),
			sprintf(
				/* translators: Post Type name, plural */
				esc_html__( 'Where forms should display relative to the %s content', 'convertkit' ),
				esc_html( $args['post_type_object']->labels->singular_name )
			),
			array( 'convertkit-conditional-display' ),
			array(
				'data-conditional-value'   => 'after_element',
				'data-conditional-element' => esc_attr( $args['post_type'] ) . '_form_position_element_index',
			)
		);

	}

	/**
	 * Renders the input for the Default Form Position Index setting for the given Post Type.
	 *
	 * @since  2.6.1
	 *
	 * @param   array $args  Field arguments.
	 */
	public function default_form_position_element_callback( $args ) {

		echo $this->get_number_field( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$args['post_type'] . '_form_position_element_index',
			esc_attr( (string) $this->settings->get_default_form_position_element_index( $args['post_type'] ) ),
			1,
			999,
			1,
			false,
			array( 'after_element' )
		);

		echo $this->get_select_field( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$args['post_type'] . '_form_position_element',
			esc_attr( $this->settings->get_default_form_position_element( $args['post_type'] ) ),
			array(
				'p'   => esc_html__( 'Paragraphs', 'convertkit' ),
				'h2'  => esc_html__( 'Headings <h2>', 'convertkit' ),
				'h3'  => esc_html__( 'Headings <h3>', 'convertkit' ),
				'h4'  => esc_html__( 'Headings <h4>', 'convertkit' ),
				'h5'  => esc_html__( 'Headings <h5>', 'convertkit' ),
				'h6'  => esc_html__( 'Headings <h6>', 'convertkit' ),
				'img' => esc_html__( 'Images', 'convertkit' ),
			),
			esc_html__( 'The number of elements before outputting the form.', 'convertkit' ),
			array( 'after_element' )
		);

	}

	/**
	 * Renders the input for the Non-inline Form setting.
	 *
	 * @since  2.2.3
	 *
	 * @param   array $args  Field arguments.
	 */
	public function non_inline_form_callback( $args ) {

		// Bail if no non-inline Forms exist.
		if ( ! $this->forms->non_inline_exist() ) {
			esc_html_e( 'No non-inline Forms exist in Kit.', 'convertkit' );
			echo '<br /><a href="' . esc_url( convertkit_get_new_form_url() ) . '" target="_blank">' . esc_html__( 'Click here to create your first modal, slide in or sticky bar form', 'convertkit' ) . '</a>';
			return;
		}

		// Build description with preview link.
		$preview_url = WP_ConvertKit()->get_class( 'preview_output' )->get_preview_form_home_url();
		$description = sprintf(
			'%s %s %s',
			esc_html__( 'Select one or more non-inline modal, slide in or sticky bar forms to automatically display site wide. Ignored if a non-inline form is specified in Default Form settings above, individual Post / Page settings, or any block / shortcode.', 'convertkit' ),
			'<a href="' . esc_url( $preview_url ) . '" id="convertkit-preview-non-inline-form" target="_blank">' . esc_html__( 'Click here', 'convertkit' ) . '</a>',
			esc_html__( 'to preview how this will display.', 'convertkit' )
		);

		// Build field.
		$select_field = $this->forms->get_select_field_non_inline(
			$this->settings_key . '[non_inline_form]',
			$this->settings_key . '_non_inline_form',
			array(
				'convertkit-select2',
				'convertkit-preview-output-link',
			),
			$this->settings->get_non_inline_form(),
			false,
			array(
				'data-target' => '#convertkit-preview-non-inline-form',
				'data-link'   => esc_attr( $preview_url ) . '&convertkit_form_id=',
			),
			$description
		);

		// Output field.
		echo '<div class="convertkit-select2-container">' . $select_field . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Renders the input for the Debug setting.
	 *
	 * @since   1.9.6
	 */
	public function debug_callback() {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			'debug',
			'on',
			$this->settings->debug_enabled(), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html__( 'Log requests to file and output browser console messages.', 'convertkit' ),
			esc_html__( 'You can ignore this unless you\'re working with our support team to resolve an issue. Decheck this option to improve performance.', 'convertkit' )
		);

	}

	/**
	 * Renders the input for the Disable Javascript setting.
	 *
	 * @since   1.9.6
	 */
	public function no_scripts_callback() {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			'no_scripts',
			'on',
			$this->settings->scripts_disabled(), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html__( 'Prevent plugin from loading JavaScript files. This will disable the custom content and tagging features of the plugin. Does not apply to landing pages. Use with caution!', 'convertkit' )
		);

	}

	/**
	 * Renders the input for the Disable CSS setting.
	 *
	 * @since   1.9.6.9
	 */
	public function no_css_callback() {

		// Output field.
		echo $this->get_checkbox_field( // phpcs:ignore WordPress.Security.EscapeOutput
			'no_css',
			'on',
			$this->settings->css_disabled(), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html__( 'Prevents loading plugin CSS files. This will disable styling on broadcasts, form trigger buttons, product buttons and member\'s content. Use with caution!', 'convertkit' ),
			array(
				sprintf(
					'%s <a href="%s" target="_blank">%s</a>',
					esc_html__( 'To customize forms and their styling, use the', 'convertkit' ),
					esc_url( convertkit_get_form_editor_url() ),
					esc_html__( 'Kit form editor', 'convertkit' )
				),
				sprintf(
					'%s <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a>, <a href="https://wordpress.org/plugins/convertkit-gravity-forms/" target="_blank">Gravity Forms</a> %s <a href="https://wordpress.org/plugins/integrate-convertkit-wpforms/" target="_blank">WPForms</a> %s',
					esc_html__( 'For developers who require custom form designs through use of CSS, consider using the', 'convertkit' ),
					esc_html__( 'or', 'convertkit' ),
					esc_html__( 'integrations.', 'convertkit' )
				),
			)
		);

	}

	/**
	 * Sanitizes the settings prior to being saved.
	 *
	 * @since   2.4.3
	 *
	 * @param   array $settings   Submitted Settings Fields.
	 * @return  array               Sanitized Settings with Defaults
	 */
	public function sanitize_settings( $settings ) {

		// If no Access Token, Refresh Token or Token Expiry keys were specified in the settings
		// prior to save, don't overwrite them with the blank setting from get_defaults().
		// This ensures we only blank these values if we explicitly do so via $settings,
		// as they won't be included in the Settings screen for security.
		if ( ! array_key_exists( 'disconnect', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! array_key_exists( 'access_token', $settings ) ) {
				$settings['access_token'] = $this->settings->get_access_token();
			}
			if ( ! array_key_exists( 'refresh_token', $settings ) ) {
				$settings['refresh_token'] = $this->settings->get_refresh_token();
			}
			if ( ! array_key_exists( 'token_expires', $settings ) ) {
				$settings['token_expires'] = $this->settings->get_token_expiry();
			}
		}

		// Call parent class to merge settings with defaults.
		$settings = parent::sanitize_settings( $settings );

		// If a Form or Landing Page was specified that isn't the default,
		// request a review.
		// Since switching to OAuth means the settings screen will only display
		// settings if the access token is valid, the Default Forms options will
		// always be submitted. Previously, if no API Key/Secret was specified,
		// no Default Forms options would render.
		// This can safely be called multiple times, as the review request
		// class will ensure once a review request is dismissed by the user,
		// it is never displayed again.
		if ( ( isset( $settings['page_form'] ) && $settings['page_form'] !== 'default' ) ||
			( isset( $settings['post_form'] ) && $settings['post_form'] !== 'default' ) ) {
			WP_ConvertKit()->get_class( 'review_request' )->request_review();
		}

		// Return settings to be saved.
		return $settings;

	}

}
