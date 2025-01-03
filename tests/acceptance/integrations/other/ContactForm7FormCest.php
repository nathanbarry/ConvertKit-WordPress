<?php
/**
 * Tests for ConvertKit Forms integration with Contact Form 7.
 *
 * @since   1.9.6
 */
class ContactForm7FormCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'contact-form-7');
	}

	/**
	 * Tests that no Contact Form 7 settings display and a 'No Forms exist on Kit'
	 * notification displays when no credentials are defined in the Plugin's settings.
	 *
	 * @since   2.2.7
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7WhenNoCredentials(AcceptanceTester $I)
	{
		// Load Contact Form 7 Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=contactform7');

		// Confirm no settings table is displayed.
		$I->dontSeeElementInDOM('table.wp-list-table');
	}

	/**
	 * Test that saving a Contact Form 7 to ConvertKit Form Mapping works.
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7ToConvertKitFormMapping(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			$_ENV['CONVERTKIT_API_THIRD_PARTY_INTEGRATIONS_FORM_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has the expected form and referrer value set.
		$I->apiCheckSubscriberHasForm(
			$I,
			$subscriberID,
			$_ENV['CONVERTKIT_API_THIRD_PARTY_INTEGRATIONS_FORM_ID'],
			$_ENV['TEST_SITE_WP_URL'] . $I->grabFromCurrentUrl()
		);
	}

	/**
	 * Test that saving a Contact Form 7 to ConvertKit Legacy Form Mapping works.
	 *
	 * @since   2.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7ToConvertKitLegacyFormMapping(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			$_ENV['CONVERTKIT_API_LEGACY_FORM_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Test that saving a Contact Form 7 to ConvertKit Tag Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7ToConvertKitTagMapping(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			$_ENV['CONVERTKIT_API_TAG_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriberID, $_ENV['CONVERTKIT_API_TAG_ID']);
	}

	/**
	 * Test that saving a Contact Form 7 to ConvertKit Sequence Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7ToConvertKitSequenceMapping(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			$_ENV['CONVERTKIT_API_SEQUENCE_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the sequence.
		$I->apiCheckSubscriberHasSequence($I, $subscriberID, $_ENV['CONVERTKIT_API_SEQUENCE_ID']);
	}

	/**
	 * Test that setting a Contact Form 7 Form to the '(Do not subscribe)' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7DoNotSubscribeOption(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			'(Do not subscribe)'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	/**
	 * Test that setting a Contact Form 7 Form to the 'Subscribe' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7SubscribeOption(AcceptanceTester $I)
	{
		// Setup Contact form 7 Form and configuration for this test.
		$pageID = $this->_contactForm7SetupForm(
			$I,
			'Subscribe'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Contact Form 7 Form.
		$this->_contactForm7CompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is not displayed when invalid credentials are specified at WPForms > Settings > Integrations > ConvertKit.
	 *
	 * @since   2.2.7
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7CreatorNetworkRecommendationsOptionWhenDisabledOnConvertKitAccount(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResources($I);

		// Create Contact Form 7 Form.
		$contactForm7ID = $this->_createContactForm7Form($I);

		// Load Contact Form 7 Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=contactform7');

		// Confirm a message is displayed telling the user a paid plan is required.
		$I->seeInSource('Creator Network Recommendations requires a <a href="https://app.kit.com/account_settings/billing/?utm_source=wordpress&amp;utm_term=en_US&amp;utm_content=convertkit" target="_blank">paid Kit Plan</a>');

		// Create Page with Contact Form 7 Shortcode.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Contact Form 7: Creator Network Recommendations Disabled on Kit',
				'post_name'    => 'convertkit-contact-form-7-creator-network-recommendations-disabled-convertkit',
				'post_content' => 'Form:
[contact-form-7 id="' . $contactForm7ID . '"]',
			]
		);

		// Confirm the recommendations script was not loaded, as the credentials are invalid.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is displayed and saves correctly when valid credentials are specified at WPForms > Settings > Integrations > ConvertKit,
	 * and the ConvertKit account has the Creator Network enabled.  Viewing and submitting the Form then correctly
	 * displays the Creator Network Recommendations modal.
	 *
	 * @since   2.2.7
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsContactForm7CreatorNetworkRecommendationsWhenEnabledOnConvertKitAccount(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Create Contact Form 7 Form.
		$contactForm7ID = $this->_createContactForm7Form($I);

		// Load Contact Form 7 Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=contactform7');

		// Enable Creator Network Recommendations on the Contact Form 7.
		$I->checkOption('#creator_network_recommendations_' . $contactForm7ID);

		// Save.
		$I->click('Save Changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm checkbox is checked after saving.
		$I->seeCheckboxIsChecked('#creator_network_recommendations_' . $contactForm7ID);

		// Create Page with Contact Form 7 Shortcode.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Contact Form 7: Creator Network Recommendations',
				'post_name'    => 'convertkit-contact-form-7-creator-network-recommendations',
				'post_content' => 'Form:
[contact-form-7 id="' . $contactForm7ID . '"]',
			]
		);

		// Confirm the recommendations script was loaded.
		$I->seeCreatorNetworkRecommendationsScript($I, $pageID);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete Name and Email.
		$I->fillField('input[name=your-name]', 'Kit Name');
		$I->fillField('input[name=your-email]', $emailAddress);
		$I->fillField('input[name=your-subject]', 'Kit Subject');

		// Submit Form.
		$I->click('Submit');

		// Confirm the form submitted without errors.
		$I->performOn(
			'form.sent',
			function($I) {
				$I->see('Thank you for your message. It has been sent.');
			}
		);

		// Wait for Creator Network Recommendations modal to display.
		$I->waitForElementVisible('.formkit-modal');
		$I->switchToIFrame('.formkit-modal iframe');
		$I->waitForElementVisible('div[data-component="Page"]');
	}

	/**
	 * Creates a Contact Form 7 Form
	 *
	 * @since   1.9.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 * @return  int                     Form ID
	 */
	private function _createContactForm7Form(AcceptanceTester $I)
	{
		return $I->havePostInDatabase(
			[
				'post_name'   => 'contact-form-7-form',
				'post_title'  => 'Contact Form 7 Form',
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
				'meta_input'  => [
					// Don't attempt to send mail, as this will fail when run through a GitHub Action.
					// @see https://contactform7.com/additional-settings/#skipping-mail.
					'_form'                => '[text* your-name] [email* your-email] [text* your-subject] [textarea your-message] [submit "Submit"]',
					'_additional_settings' => 'skip_mail: on',
				],
			]
		);
	}

	/**
	 * Tests that existing settings are automatically migrated when updating
	 * the Plugin to 2.5.2 or higher, with:
	 * - Form IDs prefixed with 'form:',
	 * - Form IDs with value `default` are changed to a blank string
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsMigratedOnUpgrade(AcceptanceTester $I)
	{
		// Create settings as if they were created / edited when the ConvertKit Plugin < 2.5.2
		// was active.
		$I->haveOptionInDatabase(
			'_wp_convertkit_integration_contactform7_settings',
			[
				'1'                                 => $_ENV['CONVERTKIT_API_FORM_ID'],
				'creator_network_recommendations_1' => '1',
				'2'                                 => '',
				'3'                                 => 'default',
			]
		);

		// Downgrade the Plugin version to simulate an upgrade.
		$I->haveOptionInDatabase('convertkit_version', '2.4.9');

		// Load admin screen.
		$I->amOnAdminPage('index.php');

		// Check settings structure has been updated.
		$settings = $I->grabOptionFromDatabase('_wp_convertkit_integration_contactform7_settings');
		$I->assertArrayHasKey('1', $settings);
		$I->assertArrayHasKey('creator_network_recommendations_1', $settings);
		$I->assertArrayHasKey('2', $settings);
		$I->assertEquals($settings['1'], 'form:' . $_ENV['CONVERTKIT_API_FORM_ID']);
		$I->assertEquals($settings['creator_network_recommendations_1'], '1');
		$I->assertEquals($settings['2'], '');
		$I->assertEquals($settings['3'], '');
	}

	/**
	 * Maps the given resource name to the created Contact Form 7 Form,
	 * embeds the shortcode on a new Page, returning the Page ID.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   string           $optionName    <select> option name.
	 * @return  int                             Page ID
	 */
	private function _contactForm7SetupForm(AcceptanceTester $I, string $optionName)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Create Contact Form 7 Form.
		$contactForm7ID = $this->_createContactForm7Form($I);

		// Load Contact Form 7 Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=contactform7');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that a Form Mapping option is displayed.
		$I->seeElementInDOM('#_wp_convertkit_integration_contactform7_settings_' . $contactForm7ID);

		// Change Form to value specified in the .env file.
		$I->selectOption('#_wp_convertkit_integration_contactform7_settings_' . $contactForm7ID, $optionName);

		$I->click('Save Changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the value of the Form field matches the input provided.
		$I->seeOptionIsSelected('#_wp_convertkit_integration_contactform7_settings_' . $contactForm7ID, $optionName);

		// Create Page with Contact Form 7 Shortcode.
		return $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Contact Form 7 Shortcode: Form: ' . $optionName,
				'post_content' => 'Form:
[contact-form-7 id="' . $contactForm7ID . '"]',
			]
		);
	}

	/**
	 * Fills out the Contact Form 7 Form on the given WordPress Page ID,
	 * and submits it, confirming them form submitted without errors.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   int              $pageID        Page ID.
	 * @param   string           $emailAddress  Email Address.
	 */
	private function _contactForm7CompleteAndSubmitForm(AcceptanceTester $I, int $pageID, string $emailAddress)
	{
		// Load Page.
		$I->amOnPage('?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Name and Email.
		$I->fillField('input[name=your-name]', 'Kit Name');
		$I->fillField('input[name=your-email]', $emailAddress);
		$I->fillField('input[name=your-subject]', 'Kit Subject');

		// Submit Form.
		$I->click('Submit');

		// Confirm the form submitted without errors.
		$I->performOn(
			'form.sent',
			function($I) {
				$I->see('Thank you for your message. It has been sent.');
			}
		);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.9.6.7
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);

		// We don't use deactivateThirdPartyPlugin(), as this checks for PHP warnings/errors.
		// Contact Form 7 throws a warning on deactivation related to WordPress capabilities,
		// which is outside of our control and would result in the test not completing.
		$I->amOnPluginsPage();

		// Deactivate the Plugin.
		$I->deactivatePlugin('contact-form-7');
	}
}
