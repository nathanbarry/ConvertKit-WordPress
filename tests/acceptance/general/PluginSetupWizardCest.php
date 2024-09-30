<?php
/**
 * Tests for the ConvertKit Plugin Setup Wizard, displayed on new Plugin activations.
 *
 * @since   1.9.8.4
 */
class PluginSetupWizardCest
{
	/**
	 * Test that the Setup Wizard displays when the Plugin is activated.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardDisplays(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');
	}

	/**
	 * Test that the Setup Wizard displays when the Plugin is activated on a site
	 * where the Plugin has previously been activated and configured with API Keys.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardDoesNotDisplayWhenConfigured(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Activate Plugin.
		$this->_activatePlugin($I);

		// Confirm setup wizard does not display.
		$I->dontSee('Welcome to the ConvertKit Setup Wizard');
	}

	/**
	 * Test that the Dashboard submenu item for this wizard does not display when a
	 * third party Admin Menu editor type Plugin is installed and active.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testNoSetupWizardDashboardSubmenuItem(AcceptanceTester $I)
	{
		// Activate Admin Menu Editor Plugin.
		$I->activateThirdPartyPlugin($I, 'admin-menu-editor');

		// Setup ConvertKit Plugin.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Activate Plugin.
		$this->_activatePlugin($I);

		// Navigate to Admin Menu Editor's settings.
		$I->amOnAdminPage('options-general.php?page=menu_editor');

		// Save settings. If hiding submenu items fails in the Plugin, this step
		// will display those submenu items on subsequent page loads.
		$I->click('Save Changes');

		// Navigate to Dashboard.
		$I->amOnAdminPage('index.php');

		// Confirm no Dashboard Submenu item exists.
		$I->dontSeeInSource('<a href="options.php?page=convertkit-setup"></a>');
	}

	/**
	 * Test that the Setup Wizard exit link works.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardExitLink(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');

		// Click Exit wizard link.
		$I->click('Exit wizard');

		// Confirm exit.
		$I->acceptPopup();

		// Confirm Plugin settings screen loaded.
		$I->seeInCurrentUrl('options-general.php?page=_wp_convertkit_settings');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Test that the Setup Wizard > Setup > Connect button works.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardSetupScreenConnectButton(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');

		// Test Connect button.
		$I->click('Connect');

		// Confirm the ConvertKit hosted OAuth login screen is displayed.
		$I->waitForElementVisible('body.sessions');
		$I->seeInSource('oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']);

		// Act as if we completed OAuth.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 2, 'Display an email capture form');
	}

	/**
	 * Test that the Setup Wizard > Connect Account screen works as expected when invalid API credentials
	 * are specified.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardConnectAccountScreenWithInvalidCredentials(AcceptanceTester $I)
	{
		// Define OAuth error code and description.
		$error            = 'access_denied';
		$errorDescription = 'The resource owner or authorization server denied the request.';

		// Activate Plugin.
		$this->_activatePlugin($I);

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');

		// Test Connect button.
		$I->click('Connect');

		// Confirm the ConvertKit hosted OAuth login screen is displayed.
		$I->waitForElementVisible('body.sessions');
		$I->seeInSource('oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']);

		// Act as if OAuth failed i.e. the user didn't authenticate.
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2&error=' . $error . '&error_description=' . urlencode($errorDescription));

		// Confirm expected setup wizard screen is still displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');

		// Confirm error notification is displayed.
		$I->seeElement('div.notice.notice-error.is-dismissible');
		$I->see($errorDescription);

		// Dismiss notification.
		$I->click('div.notice-error button.notice-dismiss');

		// Confirm notification no longer displayed.
		$I->wait(1);
		$I->dontSeeElement('div.notice.notice-error.is-dismissible');
	}

	/**
	 * Test that the Setup Wizard > Form Configuration screen works as expected.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardFormConfigurationScreen(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Define Plugin settings.
		$I->setupConvertKitPluginNoDefaultForms($I);

		// Create a Page and a Post, so that preview links display.
		$I->havePostInDatabase(
			[
				'post_title'  => 'Kit: Setup Wizard: Page',
				'post_type'   => 'page',
				'post_status' => 'publish',
			]
		);
		$I->havePostInDatabase(
			[
				'post_title'  => 'Kit: Setup Wizard: Post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);

		// Load Step 2/3.
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 2, 'Display an email capture form');

		// Select a Post Form.
		$I->fillSelect2Field($I, '#select2-wp-convertkit-form-posts-container', $_ENV['CONVERTKIT_API_FORM_NAME']);

		// Open preview.
		$I->click('a#convertkit-preview-form-post');
		$I->wait(2); // Required, otherwise switchToNextTab fails.

		// Switch to newly opened tab.
		$I->switchToNextTab();

		// Confirm that the preview is a WordPress Post.
		$I->seeElementInDOM('body.single-post');

		// Confirm that one ConvertKit Form is output in the DOM.
		// This confirms that there is only one script on the page for this form, which renders the form.
		$I->seeFormOutput($I, $_ENV['CONVERTKIT_API_FORM_ID']);

		// Close newly opened tab.
		$I->closeTab();

		// Select a Page Form.
		$I->fillSelect2Field($I, '#select2-wp-convertkit-form-pages-container', $_ENV['CONVERTKIT_API_FORM_NAME']);

		// Open preview.
		$I->click('a#convertkit-preview-form-page');
		$I->wait(2); // Required, otherwise switchToNextTab fails.

		// Switch to newly opened tab.
		$I->switchToNextTab();

		// Confirm that the preview is a WordPress Page.
		$I->seeElementInDOM('body.page');

		// Confirm that one ConvertKit Form is output in the DOM.
		// This confirms that there is only one script on the page for this form, which renders the form.
		$I->seeFormOutput($I, $_ENV['CONVERTKIT_API_FORM_ID']);

		// Close newly opened tab.
		$I->closeTab();

		// Click Finish Setup button.
		$I->click('Finish Setup');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 3, 'Setup complete');

		// Click Plugin Settings.
		$I->click('Plugin Settings');

		// Confirm that Plugin Settings screen contains no errors.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Test that the Setup Wizard > Form Configuration screen works as expected
	 * when API credentials are supplied for a ConvertKit account that contains
	 * no forms.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardFormConfigurationScreenWhenNoFormsExist(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Define Plugin settings with a ConvertKit account containing no forms.
		$I->setupConvertKitPluginCredentialsNoData($I);

		// Load Step 2/3.
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 2, 'Create your first ConvertKit Form', true);

		// Confirm button link to create a form on ConvertKit is correct.
		$I->seeInSource('<a href="https://app.kit.com/forms/new/?utm_source=wordpress&amp;utm_term=en_US&amp;utm_content=convertkit"');

		// Define Plugin settings with a ConvertKit account containing forms,
		// as if we created a form in ConvertKit.
		$I->setupConvertKitPluginNoDefaultForms($I);

		// Click "I've created a form in ConvertKit" button.
		$I->click('I\'ve created a form in ConvertKit');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 2, 'Display an email capture form');

		// Confirm we can select a Post Form.
		$I->fillSelect2Field($I, '#select2-wp-convertkit-form-posts-container', $_ENV['CONVERTKIT_API_FORM_NAME']);
	}

	/**
	 * Test that the Setup Wizard > Form Configuration screen does not display preview links
	 * when no Pages and Posts exist in WordPress.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardFormConfigurationScreenWhenNoPostsOrPagesExist(AcceptanceTester $I)
	{
		// Activate Plugin.
		$this->_activatePlugin($I);

		// Define Plugin settings.
		$I->setupConvertKitPluginNoDefaultForms($I);

		// Load Step 2/3.
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 2, 'Display an email capture form');

		// Confirm no Page or Post preview links exist, because there are no Pages or Posts in WordPress.
		$I->dontSeeElementInDOM('a#convertkit-preview-form-post');
		$I->dontSeeElementInDOM('a#convertkit-preview-form-page');
	}

	/**
	 * Tests that a link to the Setup Wizard exists on the Plugins screen, and works when clicked.
	 *
	 * @since   2.1.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardLinkOnPluginsScreen(AcceptanceTester $I)
	{
		// Activate and Setup ConvertKit plugin.
		$I->activateConvertKitPlugin($I);
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Navigate to Plugins screen.
		$I->amOnPluginsPage();

		// Click Setup Wizard link underneath the Plugin in the WP_List_Table.
		$I->click('tr[data-slug="convertkit"] td div.row-actions span.setup_wizard a');

		// Confirm expected setup wizard screen is displayed.
		$this->_seeExpectedSetupWizardScreen($I, 1, 'Welcome to the ConvertKit Setup Wizard');
	}

	/**
	 * Activate the Plugin, without checking it is activated, so that its Setup Wizard
	 * screen loads.
	 *
	 * This differs from the activateConvertKitPlugin() method, which will ignore a Setup Wizard
	 * screen by reloading the Plugins screen to confirm a Plugin's activation.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	private function _activatePlugin(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->activatePlugin('convertkit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Runs tests on a Setup Wizard screen, to confirm that the expected step, title and buttons
	 * are displayed.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I      Tester.
	 * @param   int              $step   Current step.
	 * @param   string           $title  Expected title.
	 * @param   bool             $nextButtonIsLink   Check that next button is a link (false = must be a <button> element).
	 */
	private function _seeExpectedSetupWizardScreen(AcceptanceTester $I, $step, $title, $nextButtonIsLink = false)
	{
		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm expected setup wizard screen loaded.
		$I->seeInCurrentUrl('options.php?page=convertkit-setup');

		// Confirm expected title is displayed.
		$I->see($title);

		// Confirm current and previous steps are highlighted as 'done'.
		for ($stepCount = 1; $stepCount <= $step; $stepCount++) {
			$I->seeElement('li.step-' . $stepCount . '.done');
		}

		// Confirm Step text is correct.
		$I->see('Step ' . $step . ' of 3');

		// Depending on the step, confirm previous/next buttons exist / do not exist.
		switch ($step) {
			/**
			 * First step should only display Connect button.
			 */
			case 1:
				$I->dontSeeElementInDOM('#convertkit-setup-wizard-footer div.left a.button');
				$I->dontSeeElementInDOM('#convertkit-setup-wizard-footer div.right button');
				$I->seeElementInDOM('#convertkit-setup-wizard-footer div.right a.button');
				break;

			/**
			 * Middle step should always display footer buttons.
			 */
			case 2:
				$I->seeElementInDOM('#convertkit-setup-wizard-footer div.left a.button');

				if ($nextButtonIsLink) {
					// Next button must be a link.
					$I->seeElementInDOM('#convertkit-setup-wizard-footer div.right a.button');
				} else {
					// Next button must be a <button> element to submit form.
					$I->seeElementInDOM('#convertkit-setup-wizard-footer div.right button');
				}
				break;

			/**
			 * Last step should not display any footer buttons.
			 */
			case 3:
				$I->dontSeeElementInDOM('#convertkit-setup-wizard-footer div.left a.button');
				$I->dontSeeElementInDOM('#convertkit-setup-wizard-footer div.right button');
				$I->dontSeeElementInDOM('#convertkit-setup-wizard-footer div.right a.button');
				break;
		}
	}

	/**
	 * Tests that a slimline modal version of the Plugin Setup Wizard is displayed
	 * when the `convertkit-modal` request parameter is included.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testSetupWizardModal(AcceptanceTester $I)
	{
		// Activate ConvertKit Plugin.
		$I->activateConvertKitPlugin($I);

		// Manually navigate to the Plugin Setup Wizard; this will be performed via a block
		// in a future PR, so this test can be moved to e.g. PageBlockFormCest.
		$I->amOnAdminPage('options.php?page=convertkit-setup&convertkit-modal=1');

		// Confirm the ConvertKit hosted OAuth login screen is displayed.
		$I->waitForElementVisible('body.sessions');
		$I->seeInSource('oauth/authorize?client_id=' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']);

		// Act as if we completed OAuth.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->amOnAdminPage('options.php?page=convertkit-setup&step=2&convertkit-modal=1');

		// Confirm the close modal view was loaded, which includes some JS.
		$I->seeInSource('self.close();');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.9.8.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
