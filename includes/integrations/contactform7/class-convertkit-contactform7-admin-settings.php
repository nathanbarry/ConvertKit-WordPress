<?php
/**
 * ConvertKit Contact Form 7 Admin Settings class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Contact Form 7 Settings that can be edited at Settings > Kit > Contact Form 7.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_ContactForm7_Admin_Settings extends ConvertKit_Settings_Base {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_ContactForm7_Settings();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		// Define the programmatic name, Title and Tab Text.
		$this->name     = 'contactform7';
		$this->title    = __( 'Contact Form 7 Integration Settings', 'convertkit' );
		$this->tab_text = __( 'Contact Form 7', 'convertkit' );

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
	 * Register fields for this section
	 */
	public function register_fields() {

		// No fields are registered, because they are output in a WP_List_Table
		// in this class' render() function.
		// This function is deliberately blank.
	}

	/**
	 * Prints help info for this section.
	 */
	public function print_section_info() {

		?>
		<p>
			<?php
			esc_html_e( 'Kit seamlessly integrates with Contact Form 7 to let you add subscribers using Contact Form 7 forms.', 'convertkit' );
			?>
		</p>
		<p>
			<?php
			printf(
				'%s <code>text*</code> %s <code>your-name</code> %s <code>email*</code> %s <code>your-email</code>%s',
				esc_html__( 'The Contact Form 7 form must have a', 'convertkit' ),
				esc_html__( 'field named', 'convertkit' ),
				esc_html__( 'and an', 'convertkit' ),
				esc_html__( 'field named', 'convertkit' ),
				esc_html__( '. These fields will be sent to Kit for the subscription.', 'convertkit' )
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'Each Contact Form 7 Form has the following Kit options:', 'convertkit' ); ?>
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
	 * @since   2.0.8
	 *
	 * @return  string  Documentation URL.
	 */
	public function documentation_url() {

		return 'https://help.kit.com/en/articles/2502591-the-convertkit-wordpress-plugin';

	}

	/**
	 * Outputs the section as a WP_List_Table of Contact Form 7 Forms, with options to choose
	 * a ConvertKit Form mapping for each.
	 *
	 * @since   1.9.6
	 */
	public function render() {

		// Render opening container.
		$this->render_container_start();

		do_settings_sections( $this->settings_key );

		// Get Contact Form 7 Forms.
		$cf7_forms = $this->get_cf7_forms();

		// Bail with an error if no Contact Form 7 Forms exist.
		if ( ! $cf7_forms ) {
			$this->output_error( __( 'No Contact Form 7 Forms exist in the Contact Form 7 Plugin.', 'convertkit' ) );
			$this->render_container_end();
			return;
		}

		// Get Creator Network Recommendations script.
		$creator_network_recommendations         = new ConvertKit_Resource_Creator_Network_Recommendations( 'contact_form_7' );
		$creator_network_recommendations_enabled = $creator_network_recommendations->enabled();

		// Setup WP_List_Table.
		$table = new Multi_Value_Field_Table();
		$table->add_column( 'title', __( 'Contact Form 7 Form', 'convertkit' ), true );
		$table->add_column( 'form', __( 'Kit', 'convertkit' ), false );
		$table->add_column( 'email', __( 'Contact Form 7 Email Field', 'convertkit' ), false );
		$table->add_column( 'name', __( 'Contact Form 7 Name Field', 'convertkit' ), false );
		$table->add_column( 'creator_network_recommendations', __( 'Enable Creator Network Recommendations', 'convertkit' ), false );

		// Iterate through Contact Form 7 Forms, adding a table row for each Contact Form 7 Form.
		foreach ( $cf7_forms as $cf7_form ) {
			// Build row.
			$table_row = array(
				'title' => $cf7_form['name'],
				'form'  => convertkit_get_subscription_dropdown_field(
					'_wp_convertkit_integration_contactform7_settings[' . $cf7_form['id'] . ']',
					(string) $this->settings->get_convertkit_subscribe_setting_by_cf7_form_id( $cf7_form['id'] ),
					'_wp_convertkit_integration_contactform7_settings_' . $cf7_form['id'],
					'widefat',
					'contact_form_7'
				),
				'email' => 'your-email',
				'name'  => 'your-name',
			);

			// Add Creator Network Recommendations table column.
			if ( $creator_network_recommendations_enabled ) {
				// Show checkbox to enable Creator Network Recommendations for this Contact Form 7 Form.
				$table_row['creator_network_recommendations'] = $this->get_checkbox_field(
					'creator_network_recommendations_' . $cf7_form['id'],
					'1',
					$this->settings->get_creator_network_recommendations_enabled_by_cf7_form_id( $cf7_form['id'] )
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
	 * Gets available forms from CF7
	 */
	private function get_cf7_forms() {

		$forms = array();

		$result = new WP_Query(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( ! count( $result->posts ) ) {
			return false;
		}

		foreach ( $result->posts as $post ) {
			$forms[] = array(
				'id'   => $post->ID,
				'name' => $post->post_title,
			);
		}

		return $forms;

	}

}

// Register Admin Settings section.
add_filter(
	'convertkit_admin_settings_register_sections',
	/**
	 * Register WishList Member as a section at Settings > Kit.
	 *
	 * @param   array   $sections   Settings Sections.
	 * @return  array
	 */
	function ( $sections ) {

		// Bail if Contact Form 7 isn't enabled.
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			return $sections;
		}

		// Register this class as a section at Settings > Kit.
		$sections['contactform7'] = new ConvertKit_ContactForm7_Admin_Settings();
		return $sections;

	}
);
