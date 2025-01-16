<?php
/**
 * ConvertKit Wishlist Admin Settings class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Registers Wishlist Settings that can be edited at Settings > Kit > Wishlist.
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_Wishlist_Admin_Settings extends ConvertKit_Settings_Base {

	/**
	 * Constructor.
	 *
	 * @since   1.9.6
	 */
	public function __construct() {

		// Define the class that reads/writes settings.
		$this->settings = new ConvertKit_Wishlist_Settings();

		// Define the settings key.
		$this->settings_key = $this->settings::SETTINGS_NAME;

		// Define the programmatic name, Title and Tab Text.
		$this->name     = 'wishlist-member';
		$this->title    = __( 'WishList Member Integration Settings', 'convertkit' );
		$this->tab_text = __( 'WishList Member', 'convertkit' );

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
	 * Prints help info for this section.
	 *
	 * @since   1.9.6
	 */
	public function print_section_info() {

		?>
		<p>
			<?php
			esc_html_e( 'Kit seamlessly integrates with WishList Member to let you capture all of your WishList Membership registrations within your Kit forms.', 'convertkit' );
			?>
		</p>
		<p>
			<?php esc_html_e( 'Each membership level has the following Kit options:', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Do not subscribe', 'convertkit' ); ?></code>: <?php esc_html_e( 'Do not subscribe the email address to Kit', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Subscribe', 'convertkit' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to Kit', 'convertkit' ); ?>
			<br />
			<code><?php esc_html_e( 'Unsubscribe', 'convertkit' ); ?></code>: <?php esc_html_e( 'Unsubscribes the email address from Kit', 'convertkit' ); ?>
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
	 * Outputs the section as a WP_List_Table of WishList Member Levels, with options to choose
	 * a ConvertKit Form and Tag mapping for each.
	 *
	 * @since   1.9.6
	 */
	public function render() {

		// Render opening container.
		$this->render_container_start();

		do_settings_sections( $this->settings_key );

		// Get WishList Member Levels.
		$wlm_levels = $this->get_wlm_levels();

		// Bail with an error if no WishList Member Levels exist.
		if ( ! $wlm_levels ) {
			$this->output_error( __( 'No WishList Member Levels exist in the WishList Member Plugin.', 'convertkit' ) );
			$this->render_container_end();
			return;
		}

		// Setup WP_List_Table.
		$table = new Multi_Value_Field_Table();
		$table->add_column( 'title', __( 'WishList Membership Level', 'convertkit' ), true );
		$table->add_column( 'add', __( 'Assign to member', 'convertkit' ), false );
		$table->add_column( 'remove', __( 'Remove from member', 'convertkit' ), false );

		// Iterate through WishList Member Levels, adding a table row for each Level.
		foreach ( $wlm_levels as $wlm_level ) {
			$table->add_item(
				array(
					'title'  => $wlm_level['name'],
					'add'    => convertkit_get_subscription_dropdown_field(
						'_wp_convertkit_integration_wishlistmember_settings[' . $wlm_level['id'] . '_add]',
						(string) $this->settings->get_convertkit_add_setting_by_wishlist_member_level_id( $wlm_level['id'] ),
						'_wp_convertkit_integration_wishlistmember_settings_' . $wlm_level['id'] . '_add',
						'widefat',
						'wlm'
					),
					'remove' => convertkit_get_subscription_dropdown_field(
						'_wp_convertkit_integration_wishlistmember_settings[' . $wlm_level['id'] . '_remove]',
						(string) $this->settings->get_convertkit_remove_setting_by_wishlist_member_level_id( $wlm_level['id'] ),
						'_wp_convertkit_integration_wishlistmember_settings_' . $wlm_level['id'] . '_remove',
						'widefat',
						'wlm',
						array(
							'unsubscribe' => __( 'Unsubscribe', 'convertkit' ),
						)
					),
				)
			);
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
	 * Gets membership levels from WishList Member API
	 *
	 * @return bool|array
	 */
	public function get_wlm_levels() {

		// Get WishList Member Levels from the API.
		$wlm_get_levels = wlmapi_get_levels();

		// Bail if the API call failed.
		if ( $wlm_get_levels['success'] !== 1 ) {
			return false;
		}

		return $wlm_get_levels['levels']['level'];

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

		// Bail if WishList Member isn't enabled.
		if ( ! function_exists( 'wlmapi_get_levels' ) ) {
			return $sections;
		}

		// Register this class as a section at Settings > Kit.
		$sections['wishlist-member'] = new ConvertKit_Wishlist_Admin_Settings();
		return $sections;

	}
);
