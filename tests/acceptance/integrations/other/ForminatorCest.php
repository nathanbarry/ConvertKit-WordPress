<?php
/**
 * Tests for ConvertKit Forms integration with Forminator.
 *
 * @since   2.3.0
 */
class ForminatorCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'forminator');
	}

	/**
	 * Tests that no Forminator settings display and a 'No Forms exist on Kit'
	 * notification displays when no credentials are defined in the Plugin's settings.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorWhenNoCredentials(AcceptanceTester $I)
	{
		// Load Forminator Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=forminator');

		// Confirm no settings table is displayed.
		$I->dontSeeElementInDOM('table.wp-list-table');
	}

	/**
	 * Test that saving a Forminator Form to ConvertKit Form Mapping works.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormToConvertKitFormMapping(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			$_ENV['CONVERTKIT_API_THIRD_PARTY_INTEGRATIONS_FORM_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
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
	 * Test that saving a Forminator Form to ConvertKit Legacy Form Mapping works.
	 *
	 * @since   2.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormToConvertKitLegacyFormMapping(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			$_ENV['CONVERTKIT_API_LEGACY_FORM_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Test that saving a Forminator Form to ConvertKit Tag Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormToConvertKitTagMapping(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			$_ENV['CONVERTKIT_API_TAG_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
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
	 * Test that saving a Forminator Form to ConvertKit Sequence Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormToConvertKitSequenceMapping(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			$_ENV['CONVERTKIT_API_SEQUENCE_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
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
	 * Test that setting a Forminator Form to the '(Do not subscribe)' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormDoNotSubscribeOption(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			'(Do not subscribe)'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	/**
	 * Test that setting a Forminator Form to the 'Subscribe' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorFormSubscribeOption(AcceptanceTester $I)
	{
		// Setup Forminator Form and configuration for this test.
		$pageID = $this->_forminatorSetupForm(
			$I,
			'Subscribe'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Form.
		$this->_forminatorCompleteAndSubmitForm(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Test that saving a Forminator Quiz to ConvertKit Form Mapping works.
	 *
	 * @since   2.4.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorQuizToConvertKitFormMapping(AcceptanceTester $I)
	{
		// Setup Forminator Quiz and configuration for this test.
		$pageID = $this->_forminatorSetupQuiz(
			$I,
			$_ENV['CONVERTKIT_API_THIRD_PARTY_INTEGRATIONS_FORM_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Quiz.
		$this->_forminatorCompleteAndSubmitQuiz(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Test that saving a Forminator Quiz to ConvertKit Tag Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorQuizToConvertKitTagMapping(AcceptanceTester $I)
	{
		// Setup Forminator Quiz and configuration for this test.
		$pageID = $this->_forminatorSetupQuiz(
			$I,
			$_ENV['CONVERTKIT_API_TAG_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Quiz.
		$this->_forminatorCompleteAndSubmitQuiz(
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
	 * Test that saving a Forminator Quiz to ConvertKit Sequence Mapping works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorQuizToConvertKitSequenceMapping(AcceptanceTester $I)
	{
		// Setup Forminator Quiz and configuration for this test.
		$pageID = $this->_forminatorSetupQuiz(
			$I,
			$_ENV['CONVERTKIT_API_SEQUENCE_NAME']
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Quiz.
		$this->_forminatorCompleteAndSubmitQuiz(
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
	 * Test that setting a Forminator Quiz Form to the '(Do not subscribe)' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorQuizDoNotSubscribeOption(AcceptanceTester $I)
	{
		// Setup Forminator Quiz and configuration for this test.
		$pageID = $this->_forminatorSetupQuiz(
			$I,
			'(Do not subscribe)'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Quiz.
		$this->_forminatorCompleteAndSubmitQuiz(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was not added to ConvertKit.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	/**
	 * Test that setting a Forminator Quiz to the 'Subscribe' option works.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorQuizSubscribeOption(AcceptanceTester $I)
	{
		// Setup Forminator Quiz and configuration for this test.
		$pageID = $this->_forminatorSetupQuiz(
			$I,
			'Subscribe'
		);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete and submit Forminator Quiz.
		$this->_forminatorCompleteAndSubmitQuiz(
			$I,
			$pageID,
			$emailAddress
		);

		// Confirm that the email address was added to ConvertKit.
		$I->apiCheckSubscriberExists($I, $emailAddress);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' is not displayed when connected
	 * to a ConvertKit account that does not have Creator Network Recommendations enabled.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorCreatorNetworkRecommendationsWhenDisabledOnConvertKitAccount(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResources($I);

		// Create Forminator Form.
		$forminatorFormID = $this->_createForminatorForm($I);

		// Load Forminator Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=forminator');

		// Confirm a message is displayed telling the user a paid plan is required.
		$I->seeInSource('Creator Network Recommendations requires a <a href="https://app.kit.com/account_settings/billing/?utm_source=wordpress&amp;utm_term=en_US&amp;utm_content=convertkit" target="_blank">paid Kit Plan</a>');

		// Create Page with Forminator Shortcode.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Forminator: Creator Network Recommendations Disabled on Kit',
				'post_name'    => 'convertkit-forminator-creator-network-recommendations-disabled-convertkit',
				'post_content' => 'Form:
[forminator_form id="' . $forminatorFormID . '"]',
			]
		);

		// Confirm the recommendations script was not loaded, as the credentials are invalid.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option is displayed and saves correctly when
	 * valid credentials are specified, and the ConvertKit account has the Creator Network enabled.
	 * Viewing and submitting the Form then correctly displays the Creator Network Recommendations modal.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSettingsForminatorCreatorNetworkRecommendationsWhenEnabledOnConvertKitAccount(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Create Forminator Form.
		$forminatorFormID = $this->_createForminatorForm($I);

		// Load Forminator Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=forminator');

		// Enable Creator Network Recommendations on the Forminator Form.
		$I->checkOption('#creator_network_recommendations_' . $forminatorFormID);

		// Save.
		$I->click('Save Changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm checkbox is checked after saving.
		$I->seeCheckboxIsChecked('#creator_network_recommendations_' . $forminatorFormID);

		// Create Page with Forminator Shortcode.
		$pageID = $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Forminator: Creator Network Recommendations',
				'post_name'    => 'convertkit-forminator-creator-network-recommendations',
				'post_content' => 'Form:
[forminator_form id="' . $forminatorFormID . '"]',
			]
		);

		// Confirm the recommendations script was loaded.
		$I->seeCreatorNetworkRecommendationsScript($I, $pageID);

		// Define email address for this test.
		$emailAddress = $I->generateEmailAddress();

		// Complete Name and Email.
		$I->fillField('input[name=name-1]', 'Kit Name');
		$I->fillField('input[name=email-1]', $emailAddress);

		// Submit Form.
		$I->click('button.forminator-button-submit');

		// Wait for response message.
		$I->waitForElementVisible('.forminator-response-message');

		// Confirm the form submitted without errors.
		$I->performOn(
			'.forminator-response-message',
			function($I) {
				$I->see('Form entry saved');
			}
		);

		// Wait for Creator Network Recommendations modal to display.
		$I->waitForElementVisible('.formkit-modal');
		$I->switchToIFrame('.formkit-modal iframe');
		$I->waitForElementVisible('div[data-component="Page"]');
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
			'_wp_convertkit_integration_forminator_settings',
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
		$settings = $I->grabOptionFromDatabase('_wp_convertkit_integration_forminator_settings');
		$I->assertArrayHasKey('1', $settings);
		$I->assertArrayHasKey('creator_network_recommendations_1', $settings);
		$I->assertArrayHasKey('2', $settings);
		$I->assertEquals($settings['1'], 'form:' . $_ENV['CONVERTKIT_API_FORM_ID']);
		$I->assertEquals($settings['creator_network_recommendations_1'], '1');
		$I->assertEquals($settings['2'], '');
		$I->assertEquals($settings['3'], '');
	}

	/**
	 * Creates a Forminator Form
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 * @return  int                     Form ID
	 */
	private function _createForminatorForm(AcceptanceTester $I)
	{
		return $I->havePostInDatabase(
			[
				'post_name'   => 'forminator-form',
				'post_title'  => 'Forminator Form',
				'post_type'   => 'forminator_forms',
				'post_status' => 'publish',
				'meta_input'  => [
					'forminator_form_meta' => [
						'fields'   => [
							[
								'id'          => 'name-1',
								'element_id'  => 'name-1',
								'type'        => 'name',
								'required'    => 'true',
								'field_label' => 'First Name',
							],
							[
								'id'          => 'email-1',
								'element_id'  => 'email-1',
								'type'        => 'email',
								'required'    => 'true',
								'field_label' => 'Email Address',
							],
						],
						'settings' => [
							'enable-ajax' => 'true',
						],
					],
				],
			]
		);
	}

	/**
	 * Creates a Forminator Quiz
	 *
	 * @since   2.4.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 * @return  int                     Form ID
	 */
	private function _createForminatorQuiz(AcceptanceTester $I)
	{
		// Create Form for Leads.
		$formLeadsID = $I->havePostInDatabase(
			[
				'post_name'   => 'forminator-form-leads',
				'post_title'  => 'Forminator Form Leads',
				'post_type'   => 'forminator_forms',
				'post_status' => 'publish',
				'meta_input'  => [
					'forminator_form_meta' => [
						'fields'   => [
							[
								'id'          => 'name-1',
								'element_id'  => 'name-1',
								'type'        => 'name',
								'required'    => 'true',
								'field_label' => 'First Name',
							],
							[
								'id'          => 'email-1',
								'element_id'  => 'email-1',
								'type'        => 'email',
								'required'    => 'true',
								'field_label' => 'Email Address',
							],
						],
						'settings' => [
							'form-type'            => 'leads',
							'submission-behaviour' => 'behaviour-thankyou',
							'thankyou-message'     => 'Thank you for contacting us, we will be in touch shortly.',
							'submitData'           => [
								'custom-submit-text' => 'Submit',
								'custom-invalid-form-message' => 'Error: Your form is not valid, please fix the errors!',
							],
							'enable-ajax'          => 'true',
							'validation-inline'    => true,
							'formName'             => 'Forminator Quiz - Leads Form',
						],
					],
				],
			]
		);

		// Create and return Quiz Form.
		return $I->havePostInDatabase(
			[
				'post_name'   => 'forminator-quiz',
				'post_title'  => 'Forminator Quiz',
				'post_type'   => 'forminator_quizzes',
				'post_status' => 'publish',
				'meta_input'  => [
					'forminator_form_meta' => [
						'fields'    => [],
						'settings'  => [
							'hasLeads'          => '1',
							'formName'          => 'Forminator Quiz',
							'results_behav'     => 'end',
							'leadsId'           => $formLeadsID,
							'store_submissions' => '1',
							'quiz_title'        => 'Forminator Quiz',
							'wrappers'          => [
								[
									'wrapper_id' => '1',
									'fields'     => [
										[
											'id'          => 'name-1',
											'element_id'  => 'name-1',
											'type'        => 'name',
											'required'    => 'true',
											'field_label' => 'First Name',
										],
										[
											'id'          => 'email-1',
											'element_id'  => 'email-1',
											'type'        => 'email',
											'required'    => 'true',
											'field_label' => 'Email Address',
										],
									],
								],
							],
							'lead_settings'     => [
								'form-type'            => 'leads',
								'submission-behaviour' => 'behaviour-thankyou',
								'thankyou-message'     => 'Thank you for contacting us, we will be in touch shortly.',
								'submitData'           => [
									'custom-submit-text' => 'Submit',
									'custom-invalid-form-message' => 'Error: Your form is not valid, please fix the errors!',
								],
								'enable-ajax'          => 'true',
								'validation-inline'    => '1',
								'formName'             => 'Forminator Quiz - Leads form',
								'form_id'              => $formLeadsID,
								'store_submissions'    => '1',
							],
							'quiz_name'         => 'Forminator Quiz',
							'form-placement'    => 'end',
						],
						'questions' => [
							[
								'slug'    => 'question-1',
								'answers' => [
									[
										'title'  => 'Correct Answer',
										'toggle' => '1',
									],
									[
										'title' => 'Incorrect Answer',
									],
								],
								'type'    => 'knowledge',
								'title'   => 'Question',
							],
						],
						'quiz_type' => 'knowledge',
					],
				],
			]
		);
	}

	/**
	 * Maps the given resource name to the created Forminator Form,
	 * embeds the shortcode on a new Page, returning the Page ID.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   string           $optionName    <select> option name.
	 * @return  int                             Page ID
	 */
	private function _forminatorSetupForm(AcceptanceTester $I, string $optionName)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Create Forminator Form.
		$forminatorFormID = $this->_createForminatorForm($I);

		// Load Forminator Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=forminator');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that a Form Mapping option is displayed.
		$I->seeElementInDOM('#_wp_convertkit_integration_forminator_settings_' . $forminatorFormID);

		// Change Form to value specified in the .env file.
		$I->selectOption('#_wp_convertkit_integration_forminator_settings_' . $forminatorFormID, $optionName);

		$I->click('Save Changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the value of the Form field matches the input provided.
		$I->seeOptionIsSelected('#_wp_convertkit_integration_forminator_settings_' . $forminatorFormID, $optionName);

		// Create Page with Forminator Shortcode.
		return $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Forminator Shortcode: Form: ' . $optionName,
				'post_content' => 'Form:
[forminator_form id="' . $forminatorFormID . '"]',
			]
		);
	}

	/**
	 * Maps the given resource name to the created Forminator Quiz,
	 * embeds the shortcode on a new Page, returning the Page ID.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   string           $optionName    <select> option name.
	 * @return  int                             Page ID
	 */
	private function _forminatorSetupQuiz(AcceptanceTester $I, string $optionName)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Create Forminator Form.
		$forminatorQuizID = $this->_createForminatorQuiz($I);

		// Load Forminator Plugin Settings.
		$I->amOnAdminPage('options-general.php?page=_wp_convertkit_settings&tab=forminator');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check that a Form Mapping option is displayed.
		$I->seeElementInDOM('#_wp_convertkit_integration_forminator_settings_' . $forminatorQuizID);

		// Change Form to value specified in the .env file.
		$I->selectOption('#_wp_convertkit_integration_forminator_settings_' . $forminatorQuizID, $optionName);

		$I->click('Save Changes');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Check the value of the Form field matches the input provided.
		$I->seeOptionIsSelected('#_wp_convertkit_integration_forminator_settings_' . $forminatorQuizID, $optionName);

		// Create Page with Forminator Shortcode.
		return $I->havePageInDatabase(
			[
				'post_title'   => 'Kit: Forminator Shortcode: Quiz: ' . $optionName,
				'post_content' => 'Quiz:
[forminator_quiz id="' . $forminatorQuizID . '"]',
			]
		);
	}

	/**
	 * Fills out the Forminator Form on the given WordPress Page ID,
	 * and submits it, confirming them form submitted without errors.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   int              $pageID        Page ID.
	 * @param   string           $emailAddress  Email Address.
	 */
	private function _forminatorCompleteAndSubmitForm(AcceptanceTester $I, int $pageID, string $emailAddress)
	{
		// Load Page.
		$I->amOnPage('?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Name and Email.
		$I->fillField('input[name=name-1]', 'Kit Name');
		$I->fillField('input[name=email-1]', $emailAddress);

		// Submit Form.
		$I->click('button.forminator-button-submit');

		// Wait for response message.
		$I->waitForElementVisible('.forminator-response-message');

		// Confirm the form submitted without errors.
		$I->performOn(
			'.forminator-response-message',
			function($I) {
				$I->see('Form entry saved');
			}
		);
	}

	/**
	 * Fills out the Forminator Quiz on the given WordPress Page ID,
	 * and submits it, confirming them form submitted without errors.
	 *
	 * @since   2.5.3
	 *
	 * @param   AcceptanceTester $I             Tester.
	 * @param   int              $pageID        Page ID.
	 * @param   string           $emailAddress  Email Address.
	 */
	private function _forminatorCompleteAndSubmitQuiz(AcceptanceTester $I, int $pageID, string $emailAddress)
	{
		// Load Page.
		$I->amOnPage('?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete quiz.
		$I->checkOption('answers[question-1-0]', '0');
		$I->click('View Results');

		// Complete Name and Email.
		$I->waitForElementVisible('input[name=name-1]');
		$I->fillField('input[name=name-1]', 'Kit Name');
		$I->fillField('input[name=email-1]', $emailAddress);

		// Submit Form.
		$I->click('Submit');

		// Wait for submission to complete.
		$I->waitForElementVisible('.forminator-icon-check');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   2.3.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->deactivateThirdPartyPlugin($I, 'forminator');
		$I->resetConvertKitPlugin($I);
	}
}
