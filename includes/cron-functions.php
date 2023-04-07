<?php
/**
 * ConvertKit WordPress Cron functions.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * Refresh the Posts Resource cache, triggered by WordPress' Cron.
 *
 * @since   1.9.7.4
 */
function convertkit_resource_refresh_posts() {

	// Get Settings and Log classes.
	$settings = new ConvertKit_Settings();
	$log      = new ConvertKit_Log( CONVERTKIT_PLUGIN_PATH );

	// If debug logging is enabled, write to it now.
	if ( $settings->debug_enabled() ) {
		$log->add( 'CRON: convertkit_resource_refresh_posts(): Started' );
	}

	// Refresh Posts Resource.
	$posts  = new ConvertKit_Resource_Posts( 'cron' );
	$result = $posts->refresh();

	// If debug logging is enabled, write to it now.
	if ( $settings->debug_enabled() ) {
		// If an error occured, log it.
		if ( is_wp_error( $result ) ) {
			$log->add( 'CRON: convertkit_resource_refresh_posts(): Error: ' . $result->get_error_message() );
		}
		if ( is_array( $result ) ) {
			$log->add( 'CRON: convertkit_resource_refresh_posts(): Success: ' . count( $result ) . ' broadcasts fetched from API and cached.' );
		}

		$log->add( 'CRON: convertkit_resource_refresh_posts(): Finished' );
	}

}

// Register action to run above function; this action is created by WordPress' wp_schedule_event() function
// in the ConvertKit_Resource_Posts class.
add_action( 'convertkit_resource_refresh_posts', 'convertkit_resource_refresh_posts' );

/**
 * Fetch notices from ConvertKit, triggered by WordPress' Cron.
 * 
 * @since 	2.2.0
 */
function convertkit_get_notices() {

	/*
	// Get settings class.
	$settings = new ConvertKit_Settings();

	// Bail if no API Key and Secret are defined in the Plugin's settings.
	if ( ! $settings->has_api_key_and_secret() ) {
		return;
	}

	// Initialize the API.
	$api = new ConvertKit_API(
		$settings->get_api_key(),
		$settings->get_api_secret(),
		$settings->debug_enabled(),
		'notices'
	);
	*/

	// @TODO Fetch notices from e.g. a /wordpress/notices API endpoint.
	$notices = [
		[
			'type' => 'error',
			'message'=> 'You have reached the 1,000 subscriber limit on your free plan.',
			'cta_text'=> 'Fix now',
			'cta_link'=> 'https://app.convertkit.com/account_settings/billing',
		],
		[
			'type'=> 'warning',
			'message'=> 'Congratulations! You\'ve almost reached 1,000 subscribers. To keep growing your list, consider upgrading your plan.',
			'cta_text'=> 'View plans',
			'cta_link'=> 'https://app.convertkit.com/account_settings/billing',
		],
		[
			'type'=> 'success',
			'message'=> 'Great news! For Black Friday 2023, we\'re offering 20% off all Creator plans!',
			'cta_text'=> 'See offers',
			'cta_link'=> 'https://app.convertkit.com/account_settings/billing',
		],
	];

	foreach ( $notices as $notice ) {
		WP_ConvertKit()->get_class( 'dismissible_notices' )->add_notice( $notice['type'], $notice['message'], $notice['cta_text'], $notice['cta_link'] );
	}

	// @TODO Tell the API that we've retrieved the notices and they can be dismissed.
	
}

// Register action to run above function; this action is created by WordPress' wp_schedule_event() function
// when the Plugin is activated or updated.
add_action( 'convertkit_get_notices', 'convertkit_get_notices' );
