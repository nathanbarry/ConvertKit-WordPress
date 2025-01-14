<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class ConvertKitPlugin extends \Codeception\Module
{
	/**
	 * Helper method to activate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function activateConvertKitPlugin($I)
	{
		$I->activateThirdPartyPlugin($I, 'convertkit');
	}

	/**
	 * Helper method to deactivate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function deactivateConvertKitPlugin($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit');
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings, as if the
	 * user configured the Plugin at `Settings > Kit`.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 * @param   bool|array       $options {
	 *           Optional. An array of settings.
	 *
	 *     @type string $access_token       Access Token (if specified, used instead of CONVERTKIT_OAUTH_ACCESS_TOKEN).
	 *     @type string $refresh_token      Refresh Token (if specified, used instead of CONVERTKIT_OAUTH_REFRESH_TOKEN).
	 *     @type string $debug              Enable debugging (default: on).
	 *     @type string $no_scripts         Disable JS (default: off).
	 *     @type string $no_css             Disable CSS (default: off).
	 *     @type string $post_form          Default Form ID for Posts (if specified, used instead of CONVERTKIT_API_FORM_ID).
	 *     @type string $page_form          Default Form ID for Pages (if specified, used instead of CONVERTKIT_API_FORM_ID).
	 *     @type string $product_form       Default Form ID for WooCommerce Products (if specified, used instead of CONVERTKIT_API_FORM_ID).
	 *     @type string $non_inline_form    Default Global non-inline Form ID (if specified, none if false).
	 * }
	 */
	public function setupConvertKitPlugin($I, $options = false)
	{
		// Define default options.
		$defaults = [
			'access_token'    => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			'refresh_token'   => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			'debug'           => 'on',
			'no_scripts'      => '',
			'no_css'          => '',
			'post_form'       => $_ENV['CONVERTKIT_API_FORM_ID'],
			'page_form'       => $_ENV['CONVERTKIT_API_FORM_ID'],
			'product_form'    => $_ENV['CONVERTKIT_API_FORM_ID'],
			'non_inline_form' => array(),
		];

		// If supplied options are an array, merge them with the defaults.
		if (is_array($options)) {
			$options = array_merge($defaults, $options);
		} else {
			$options = $defaults;
		}

		// Define settings in options table.
		$I->haveOptionInDatabase('_wp_convertkit_settings', $options);
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings, as if the
	 * user configured the Plugin at `Settings > Kit` with a ConvertKit
	 * account that has no data (no forms, products, tags etc).
	 *
	 * @since   2.4.0
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 */
	public function setupConvertKitPluginCredentialsNoData($I)
	{
		$I->setupConvertKitPlugin(
			$I,
			[
				'access_token'  => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN_NO_DATA'],
				'refresh_token' => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN_NO_DATA'],
				'post_form'     => '',
				'page_form'     => '',
				'product_form'  => '',
			]
		);
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings, as if the
	 * user configured the Plugin at `Settings > Kit` with an invalid
	 * ConvertKit API Key and Secret.
	 *
	 * @since   2.4.0
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 */
	public function setupConvertKitPluginFakeAPIKey($I)
	{
		$I->setupConvertKitPlugin(
			$I,
			[
				'access_token'  => 'fakeAccessToken',
				'refresh_token' => 'fakeRefreshToken',
				'post_form'     => '',
				'page_form'     => '',
				'product_form'  => '',
			]
		);
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings, as if the
	 * user configured the Plugin at `Settings > Kit` with a ConvertKit
	 * API Key and Secret, and defined no default Forms for Posts, Pages and
	 * WooCommerce Products.
	 *
	 * @since   2.4.0
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 */
	public function setupConvertKitPluginNoDefaultForms($I)
	{
		$I->setupConvertKitPlugin(
			$I,
			[
				'post_form'    => '',
				'page_form'    => '',
				'product_form' => '',
			]
		);
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings, as if the
	 * user configured the Plugin at `Settings > Kit` with a ConvertKit
	 * API Key and Secret, and disabled JS.
	 *
	 * @since   2.4.0
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 */
	public function setupConvertKitPluginDisableJS($I)
	{
		$I->setupConvertKitPlugin(
			$I,
			[
				'no_scripts' => 'on',
			]
		);
	}

	/**
	 * Helper method to define cached Resources (Forms, Landing Pages, Posts, Products and Tags),
	 * directly into the database, instead of querying the API for them via the Resource classes.
	 *
	 * This can safely be done for Acceptance tests, as WPUnit tests ensure that
	 * caching Resources from calls made to the API work and store data in the expected
	 * structure.
	 *
	 * Defining cached Resources here reduces the number of API calls made for each test,
	 * reducing the likelihood of hitting a rate limit due to running tests in parallel.
	 *
	 * Resources are deliberately not in order, to emulate how the data might not always
	 * be in alphabetical / published order from the API.
	 *
	 * @since   2.0.7
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 */
	public function setupConvertKitPluginResources($I)
	{
		// Define Forms as if the Forms resource class populated them from the API.
		$I->haveOptionInDatabase(
			'convertkit_forms',
			[
				3003590 => [
					'id'         => 3003590,
					'name'       => 'Third Party Integrations Form',
					'created_at' => '2022-02-17T15:05:31.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/71cbcc4042/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/71cbcc4042',
					'archived'   => false,
					'uid'        => '71cbcc4042',
				],
				2780977 => [
					'id'         => 2780977,
					'name'       => 'Modal Form',
					'created_at' => '2021-11-17T04:22:06.000Z',
					'type'       => 'embed',
					'format'     => 'modal',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/397e876257/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/397e876257',
					'archived'   => false,
					'uid'        => '397e876257',
				],
				2780979 => [
					'id'         => 2780979,
					'name'       => 'Slide In Form',
					'created_at' => '2021-11-17T04:22:24.000Z',
					'type'       => 'embed',
					'format'     => 'slide in',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/e0d65bed9d/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/e0d65bed9d',
					'archived'   => false,
					'uid'        => 'e0d65bed9d',
				],
				2765139 => [
					'id'         => 2765139,
					'name'       => 'Page Form',
					'created_at' => '2021-11-11T15:30:40.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/85629c512d/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/85629c512d',
					'archived'   => false,
					'uid'        => '85629c512d',
				],
				470099  => [
					'id'                  => 470099,
					'name'                => 'Legacy Form',
					'created_at'          => null,
					'type'                => 'embed',
					'url'                 => 'https://app.kit.com/landing_pages/470099',
					'embed_js'            => 'https://api.kit.com/api/v3/forms/470099.js?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'embed_url'           => 'https://api.kit.com/api/v3/forms/470099.html?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'title'               => 'Join the newsletter',
					'description'         => '<p>Subscribe to get our latest content by email.</p>',
					'sign_up_button_text' => 'Subscribe',
					'success_message'     => 'Success! Now check your email to confirm your subscription.',
					'archived'            => false,
				],
				2780980 => [
					'id'         => 2780980,
					'name'       => 'Sticky Bar Form',
					'created_at' => '2021-11-17T04:22:42.000Z',
					'type'       => 'embed',
					'format'     => 'sticky bar',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/9f5c601482/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/9f5c601482',
					'archived'   => false,
					'uid'        => '9f5c601482',
				],
				3437554 => [
					'id'         => 3437554,
					'name'       => 'AAA Test',
					'created_at' => '2022-07-15T15:06:32.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/3bb15822a2/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/3bb15822a2',
					'archived'   => false,
					'uid'        => '3bb15822a2',
				],
				2765149 => [
					'id'         => 2765149,
					'name'       => 'WooCommerce Product Form',
					'created_at' => '2021-11-11T15:32:54.000Z',
					'type'       => 'embed',
					'format'     => 'inline',
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/7e238f3920/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/7e238f3920',
					'archived'   => false,
					'uid'        => '7e238f3920',
				],
			]
		);

		// Define Landing Pages.
		$I->haveOptionInDatabase(
			'convertkit_landing_pages',
			[
				2765196 => [
					'id'         => 2765196,
					'name'       => 'Landing Page',
					'created_at' => '2021-11-11T15:45:33.000Z',
					'type'       => 'hosted',
					'format'     => null,
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/99f1db6843/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/99f1db6843',
					'archived'   => false,
					'uid'        => '99f1db6843',
				],
				2849151 => [
					'id'         => 2849151,
					'name'       => 'Character Encoding',
					'created_at' => '2021-12-16T14:55:58.000Z',
					'type'       => 'hosted',
					'format'     => null,
					'embed_js'   => 'https://cheerful-architect-3237.kit.com/cc5eb21744/index.js',
					'embed_url'  => 'https://cheerful-architect-3237.kit.com/cc5eb21744',
					'archived'   => false,
					'uid'        => 'cc5eb21744',
				],
				470103  => [
					'id'                  => 470103,
					'name'                => 'Legacy Landing Page',
					'created_at'          => null,
					'type'                => 'hosted',
					'url'                 => 'https://app.kit.com/landing_pages/470103',
					'embed_js'            => 'https://api.kit.com/api/v3/forms/470103.js?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'embed_url'           => 'https://api.kit.com/api/v3/forms/470103.html?api_key=' . $_ENV['CONVERTKIT_API_KEY'],
					'title'               => '',
					'description'         => '',
					'sign_up_button_text' => 'Register',
					'success_message'     => null,
					'archived'            => false,
				],
			]
		);

		// Define Posts.
		$I->haveOptionInDatabase(
			'convertkit_posts',
			[
				224758  => [
					'id'            => 224758,
					'title'         => 'Test Subject',
					'url'           => 'https://cheerful-architect-3237.kit.com/posts/test-subject',
					'published_at'  => '2022-01-24T00:00:00.000Z',
					'description'   => 'Description text for Test Subject',
					'thumbnail_url' => 'https://placehold.co/600x400',
					'thumbnail_alt' => 'Alt text for Test Subject',
					'is_paid'       => null,
				],
				489480  => [
					'id'            => 489480,
					'title'         => 'Broadcast 2',
					'url'           => 'https://cheerful-architect-3237.kit.com/posts/broadcast-2',
					'published_at'  => '2022-04-08T00:00:00.000Z',
					'description'   => 'Description text for Broadcast 2',
					'thumbnail_url' => 'https://placehold.co/600x400',
					'thumbnail_alt' => 'Alt text for Broadcast 2',
					'is_paid'       => null,
				],
				3175837 => [
					'id'            => 3175837,
					'title'         => 'HTML Template Test',
					'url'           => 'https://cheerful-architect-3237.kit.com/posts/html-template-test',
					'published_at'  => '2023-08-02T16:34:51.000Z',
					'description'   => "Heading 1\nParagraph\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ultrices vehicula erat, eu faucibus ligula viverra sit amet. Nullam porta scelerisque lacus eu dignissim. Curabitur mattis dui est, sed gravida ex tincidunt sed.\nLorem ipsum dolor sit amet, consectetur adipiscing...",
					'thumbnail_url' => 'https://embed.filekitcdn.com/e/pX62TATVeCKK5QzkXWNLw3/qM63x7vF3qN1whboGdEpuL',
					'thumbnail_alt' => 'MacBook Pro beside plant in vase',
					'is_paid'       => true,
				],
				572575  => [
					'id'            => 572575,
					'title'         => 'Paid Subscriber Broadcast',
					'url'           => 'https://cheerful-architect-3237.kit.com/posts/paid-subscriber-broadcast',
					'published_at'  => '2022-05-03T00:00:00.000Z',
					'description'   => 'Description text for Paid Subscriber Broadcast',
					'thumbnail_url' => 'https://placehold.co/600x400',
					'thumbnail_alt' => 'Alt text for Paid Subscriber Broadcast',
					'is_paid'       => true,
				],
				5395025 => [
					'id'            => 5395025,
					'title'         => 'New Broadcast',
					'url'           => 'https://cheerful-architect-3237.kit.com/posts/',
					'published_at'  => '2024-04-30T08:00:36.000Z',
					'description'   => 'Test Content',
					'thumbnail_url' => null,
					'thumbnail_alt' => null,
					'is_paid'       => null,
				],
			]
		);

		// Define Products.
		$I->haveOptionInDatabase(
			'convertkit_products',
			[
				42847 => [
					'id'        => 42847,
					'name'      => 'Example Tip Jar',
					'url'       => 'https://cheerful-architect-3237.kit.com/products/example-tip-jar',
					'published' => true,
				],
				36377 => [
					'id'        => 36377,
					'name'      => 'Newsletter Subscription',
					'url'       => 'https://cheerful-architect-3237.kit.com/products/newsletter-subscription',
					'published' => true,
				],
			]
		);

		// Define Tags.
		$I->haveOptionInDatabase(
			'convertkit_tags',
			[
				2744672 => [
					'id'         => 2744672,
					'name'       => 'wordpress',
					'created_at' => '2021-11-11T19:30:06.000Z',
				],
				2907192 => [
					'id'         => 2907192,
					'name'       => 'gravityforms-tag-1',
					'created_at' => '2022-02-02T14:06:32.000Z',
				],
				3748541 => [
					'id'         => 3748541,
					'name'       => 'wpforms',
					'created_at' => '2023-03-29T12:32:38.000Z',
				],
				2907193 => [
					'id'         => 2907193,
					'name'       => 'gravityforms-tag-2',
					'created_at' => '2022-02-02T14:06:38.000Z',
				],
			]
		);

		// Define last queried to now for all resources, so they're not automatically immediately refreshed by the Plugin's logic.
		$I->haveOptionInDatabase( 'convertkit_forms_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_landing_pages_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_posts_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_products_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_tags_last_queried', strtotime( 'now' ) );
	}

	/**
	 * Helper method to define cached Resources (Forms, Landing Pages, Posts, Products and Tags),
	 * directly into the database, instead of querying the API for them via the Resource classes
	 * as if the ConvertKit account is new and has no resources defined in ConvertKit.
	 *
	 * @since   2.0.7
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 */
	public function setupConvertKitPluginResourcesNoData($I)
	{
		// Define Forms as if the Forms resource class populated them from the API.
		$I->haveOptionInDatabase(
			'convertkit_forms',
			[]
		);

		// Define Landing Pages.
		$I->haveOptionInDatabase(
			'convertkit_landing_pages',
			[]
		);

		// Define Posts.
		$I->haveOptionInDatabase(
			'convertkit_posts',
			[]
		);

		// Define Products.
		$I->haveOptionInDatabase(
			'convertkit_products',
			[]
		);

		// Define Tags.
		$I->haveOptionInDatabase(
			'convertkit_tags',
			[]
		);

		// Define last queried to now for all resources, so they're not automatically immediately refreshed by the Plugin's logic.
		$I->haveOptionInDatabase( 'convertkit_forms_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_landing_pages_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_posts_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_products_last_queried', strtotime( 'now' ) );
		$I->haveOptionInDatabase( 'convertkit_tags_last_queried', strtotime( 'now' ) );
	}

	/**
	 * Helper method to reset the ConvertKit Plugin settings, as if it's a clean installation.
	 *
	 * @since   1.9.6.7
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function resetConvertKitPlugin($I)
	{
		// Plugin Settings.
		$I->dontHaveOptionInDatabase('_wp_convertkit_settings');
		$I->dontHaveOptionInDatabase('_wp_convertkit_settings_restrict_content');
		$I->dontHaveOptionInDatabase('_wp_convertkit_settings_broadcasts');
		$I->dontHaveOptionInDatabase('convertkit_version');

		// Resources.
		$I->dontHaveOptionInDatabase('convertkit_broadcasts');
		$I->dontHaveOptionInDatabase('convertkit_broadcasts_last_queried');
		$I->dontHaveOptionInDatabase('convertkit_forms');
		$I->dontHaveOptionInDatabase('convertkit_forms_last_queried');
		$I->dontHaveOptionInDatabase('convertkit_landing_pages');
		$I->dontHaveOptionInDatabase('convertkit_landing_pages_last_queried');
		$I->dontHaveOptionInDatabase('convertkit_posts');
		$I->dontHaveOptionInDatabase('convertkit_posts_last_queried');
		$I->dontHaveOptionInDatabase('convertkit_products');
		$I->dontHaveOptionInDatabase('convertkit_products_last_queried');
		$I->dontHaveOptionInDatabase('convertkit_tags');
		$I->dontHaveOptionInDatabase('convertkit_tags_last_queried');

		// Review Request.
		$I->dontHaveOptionInDatabase('convertkit-review-request');
		$I->dontHaveOptionInDatabase('convertkit-review-dismissed');

		// Upgrades.
		$I->dontHaveOptionInDatabase('_wp_convertkit_upgrade_posts');
	}

	/**
	 * Helper method to load the Plugin's Settings > General screen.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function loadConvertKitSettingsGeneralScreen($I)
	{
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to load the Plugin's Settings > Tools screen.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function loadConvertKitSettingsToolsScreen($I)
	{
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=tools');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to clear the Plugin's debug log.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 */
	public function clearDebugLog($I)
	{
		// Go to the Plugin's Tools Screen.
		$I->loadConvertKitSettingsToolsScreen($I);

		// Click the Clear log button.
		$I->click('Clear log');
	}

	/**
	 * Helper method to determine if the given entry exists in the Plugin Debug Log screen's textarea.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 * @param   string           $entry     Log entry.
	 */
	public function seeInPluginDebugLog($I, $entry)
	{
		$I->loadConvertKitSettingsToolsScreen($I);
		$I->seeInSource($entry);
	}

	/**
	 * Helper method to determine if the given entry does not exist in the Plugin Debug Log screen's textarea.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 * @param   string           $entry     Log entry.
	 */
	public function dontSeeInPluginDebugLog($I, $entry)
	{
		$I->loadConvertKitSettingsToolsScreen($I);
		$I->dontSeeInSource($entry);
	}

	/**
	 * Helper method to determine that the order of the Form resources in the given
	 * select element are in the expected alphabetical order.
	 *
	 * @since   2.0.8
	 *
	 * @param   AcceptanceTester $I                 AcceptanceTester.
	 * @param   string           $selectElement     <select> element.
	 * @param   bool|array       $prependOptions    Option elements that should appear before the resources.
	 */
	public function checkSelectFormOptionOrder($I, $selectElement, $prependOptions = false)
	{
		// Define options.
		$options = [
			'AAA Test [inline]', // First item.
			'WooCommerce Product Form [inline]', // Last item.
		];

		// Prepend options, such as 'Default' and 'None' to the options, if required.
		if ( $prependOptions ) {
			$options = array_merge( $prependOptions, $options );
		}

		// Check order.
		$I->checkSelectOptionOrder($I, $selectElement, $options);
	}

	/**
	 * Helper method to determine that the order of the Form resources in the given
	 * select element are in the expected alphabetical order.
	 *
	 * @since   2.0.8
	 *
	 * @param   AcceptanceTester $I                 AcceptanceTester.
	 * @param   string           $selectElement     <select> element.
	 * @param   bool|array       $prependOptions    Option elements that should appear before the resources.
	 */
	public function checkSelectLandingPageOptionOrder($I, $selectElement, $prependOptions = false)
	{
		// Define options.
		$options = [
			'Character Encoding', // First item.
			'Legacy Landing Page', // Last item.
		];

		// Prepend options, such as 'Default' and 'None' to the options, if required.
		if ( $prependOptions ) {
			$options = array_merge( $prependOptions, $options );
		}

		// Check order.
		$I->checkSelectOptionOrder($I, $selectElement, $options);
	}

	/**
	 * Helper method to determine that the order of the Form resources in the given
	 * select element are in the expected alphabetical order.
	 *
	 * @since   2.0.8
	 *
	 * @param   AcceptanceTester $I                 AcceptanceTester.
	 * @param   string           $selectElement     <select> element.
	 * @param   bool|array       $prependOptions    Option elements that should appear before the resources.
	 */
	public function checkSelectTagOptionOrder($I, $selectElement, $prependOptions = false)
	{
		// Define options.
		$options = [
			'gravityforms-tag-1', // First item.
			'wpforms', // Last item.
		];

		// Prepend options, such as 'Default' and 'None' to the options, if required.
		if ( $prependOptions ) {
			$options = array_merge( $prependOptions, $options );
		}

		// Check order.
		$I->checkSelectOptionOrder($I, $selectElement, $options);
	}

	/**
	 * Helper method to determine the order of <option> values for the given select element
	 * and values.
	 *
	 * @since   2.0.8
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $selectElement <select> element.
	 * @param   array            $values        <option> values.
	 */
	public function checkSelectOptionOrder($I, $selectElement, $values)
	{
		foreach ( $values as $i => $value ) {
			// Define the applicable CSS selector.
			if ( $i === 0 ) {
				$nth = 'first-child';
			} elseif ( $i + 1 === count( $values ) ) {
				$nth = 'last-child';
			} else {
				$nth = 'nth-child(' . ( $i + 1 ) . ')';
			}

			$I->assertEquals(
				$I->grabTextFrom('select' . $selectElement . ' option:' . $nth),
				$value
			);
		}
	}

	/**
	 * Test that the 'Click here to connect your Kit account' link displays a popup window,
	 * when using a block with no API Keys specified.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I                 Tester.
	 * @param   string           $blockName         Block Name.
	 * @param   bool|string      $expectedMessage   Expected message displayed in block after entering valid API Keys.
	 */
	public function testBlockNoAPIKeyPopupWindow($I, $blockName, $expectedMessage = false)
	{
		// Confirm that the Form block displays instructions to the user on how to enter their API Key.
		$I->see(
			'Not connected to Kit.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Click the link to confirm it loads the Plugin's setup wizard.
		$I->click(
			'Click here to connect your Kit account.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Switch to the window that just opened.
		$I->switchToWindow('convertkit_popup_window');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm no logo or progress bar is displayed, as this is the modal version of the wizard.
		$I->dontSeeElementInDOM('#convertkit-setup-wizard-header');

		// Confirm no exit wizard link is displayed.
		$I->dontSeeElementInDOM('#convertkit-setup-wizard-exit-link');

		// Confirm expected title is displayed.
		$I->see('Welcome to the Kit Setup Wizard');

		// Confirm Step text is correct.
		$I->see('Step 1 of 2');

		// Test Connect button.
		$I->click('Connect');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm no logo or progress bar is displayed, as this is the modal version of the wizard.
		$I->dontSeeElementInDOM('#convertkit-setup-wizard-header');

		// Confirm no exit wizard link is displayed.
		$I->dontSeeElementInDOM('#convertkit-setup-wizard-exit-link');

		// Confirm expected title is displayed.
		$I->see('Connect your Kit account');

		// Confirm Step text is correct.
		$I->see('Step 2 of 2');

		// Confirm Back and Connect buttons display.
		$I->seeElementInDOM('#convertkit-setup-wizard-footer div.left a.button');
		$I->seeElementInDOM('#convertkit-setup-wizard-footer div.right button');

		// Fill fields with valid API Keys.
		$I->fillField('api_key', $_ENV['CONVERTKIT_API_KEY']);
		$I->fillField('api_secret', $_ENV['CONVERTKIT_API_SECRET']);

		// Click Connect button.
		$I->click('Connect');

		// Switch back to the main browser window.
		$I->switchToWindow();

		// Wait until the block changes to refreshing.
		$I->waitForElementVisible('.' . $blockName . ' span.spinner', 5);

		// Wait for the refresh button to disappear, confirming that the block refresh completed
		// and that resources now exist.
		$I->waitForElementNotVisible('div.convertkit-no-content button.convertkit-block-refresh');

		// Confirm that the block displays the expected message.
		if ($expectedMessage) {
			$I->see(
				$expectedMessage,
				[
					'css' => '.convertkit-no-content',
				]
			);
		}
	}

	/**
	 * Check that the given Page does output the Creator Network Recommendations
	 * script.
	 *
	 * @since   2.2.7
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $pageID        Page ID.
	 */
	public function seeCreatorNetworkRecommendationsScript($I, $pageID)
	{
		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the recommendations script was not loaded.
		$I->seeInSource('recommendations.js');
	}

	/**
	 * Check that the given Page does not output the Creator Network Recommendations
	 * script.
	 *
	 * @since   2.2.7
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $pageID        Page ID.
	 */
	public function dontSeeCreatorNetworkRecommendationsScript($I, $pageID)
	{
		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeInSource('recommendations.js');
	}

	/**
	 * Selects all text for the given input field.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I         Acceptance Tester.
	 * @param   string           $selector  CSS or ID selector for the input element.
	 */
	public function selectAllText($I, $selector)
	{
		// Determine whether to use the control or command key, depending on the OS.
		$key = \Facebook\WebDriver\WebDriverKeys::CONTROL;

		// If we're on OSX, use the command key instead.
		if (array_key_exists('TERM_PROGRAM', $_SERVER) && strpos( $_SERVER['TERM_PROGRAM'], 'Apple') !== false) {
			$key = \Facebook\WebDriver\WebDriverKeys::COMMAND;
		}

		// Press Ctrl/Command + a on Keyboard.
		$I->pressKey($selector, array( $key, 'a' ));
	}

	/**
	 * Changes the WPWebBrowser Chrome User Agent to a mobile device,
	 * restarting the headless Chrome instance.
	 *
	 * @since   2.4.6
	 */
	public function enableMobileEmulation()
	{
		$this->getModule('WPWebDriver')->_restart(
			[
				'browser'      => 'chrome',
				'capabilities' => [
					'goog:chromeOptions' => [
						'args'            => [
							'--headless=new',
							'--disable-gpu',
							'--user-agent=' . $_ENV['TEST_SITE_HTTP_USER_AGENT_MOBILE'],
						],
						'mobileEmulation' => [
							'deviceMetrics' => [
								'width'      => 430,
								'height'     => 932,
								'pixelRatio' => 1,
							],
							'clientHints'   => [
								'platform' => 'Android',
								'mobile'   => true,
							],
							'userAgent'     => $_ENV['TEST_SITE_HTTP_USER_AGENT_MOBILE'],
						],
					],
				],
			]
		);
	}

	/**
	 * Changes the WPWebBrowser Chrome User Agent to a desktop device,
	 * restarting the headless Chrome instance.
	 *
	 * @since   2.4.6
	 */
	public function disableMobileEmulation()
	{
		$this->getModule('WPWebDriver')->_restart(
			[
				'browser'      => 'chrome',
				'capabilities' => [
					'goog:chromeOptions' => [
						'args' => [
							'--headless=new',
							'--disable-gpu',
							'--user-agent=' . $_ENV['TEST_SITE_HTTP_USER_AGENT'],
						],
						// excluding mobileEmulation arguments here makes chromedriver behave in desktop mode.
					],
				],
			]
		);
	}
}
