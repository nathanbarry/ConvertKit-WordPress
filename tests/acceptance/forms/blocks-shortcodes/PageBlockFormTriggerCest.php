<?php
/**
 * Tests for the ConvertKit Form Trigger Gutenberg Block.
 *
 * @since   2.2.0
 */
class PageBlockFormTriggerCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
	}

	/**
	 * Test the Form Trigger block works when using a valid Form parameter.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithValidFormParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Valid Form Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Form setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Form Trigger',
			'convertkit-formtrigger',
			[
				'form' => [ 'select', $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_NAME'] ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Subscribe');
	}

	/**
	 * Test that multiple Form Trigger blocks work when using a valid Form parameter.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlocksWithValidFormParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Valid Form Param, Multiple Blocks');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Form setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Form Trigger',
			'convertkit-formtrigger',
			[
				'form' => [ 'select', $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_NAME'] ],
			]
		);

		// Add the same block again.
		$I->addGutenbergBlock(
			$I,
			'Kit Form Trigger',
			'convertkit-formtrigger',
			[
				'form' => [ 'select', $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_NAME'] ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Subscribe');

		// Confirm that one ConvertKit Form is output in the DOM.
		// This confirms that there is only one script on the page for this form, which renders the form.
		$I->seeNumberOfElementsInDOM('form[data-sv-form="' . $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_ID'] . '"]', 1);
	}

	/**
	 * Test the Form Trigger block works when not defining a Form parameter.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithNoFormParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: No Form Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Form Trigger', 'convertkit-formtrigger');

		// Confirm that the Form block displays instructions to the user on how to select a Form.
		$I->see(
			'Select a Form using the Form option in the Gutenberg sidebar.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that no ConvertKit Form trigger button is displayed.
		$I->dontSeeFormTriggerOutput($I);
	}

	/**
	 * Test the Form Trigger block's text parameter works.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithTextParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Text Param');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Form Trigger',
			'convertkit-formtrigger',
			[
				'form' => [ 'select', $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_NAME'] ],
				'text' => [ 'text', 'Sign up' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Sign up');
	}

	/**
	 * Test the Form Trigger block's default text value is output when the text parameter is blank.
	 *
	 * @since   2.0.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithBlankTextParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Blank Text Param');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Form Trigger',
			'convertkit-formtrigger',
			[
				'form' => [ 'select', $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_NAME'] ],
				'text' => [ 'text', '' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Subscribe');
	}

	/**
	 * Test the Form Trigger block's theme color parameters works.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithThemeColorParameters(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Define colors.
		$backgroundColor = 'white';
		$textColor       = 'purple';

		// It's tricky to interact with Gutenberg's color picker, so we programmatically create the Page
		// instead to then confirm the color settings apply on the output.
		// We don't need to test the color picker itself, as it's a Gutenberg supplied component, and our
		// other Acceptance tests confirm that the block can be added in Gutenberg etc.
		$I->havePageInDatabase(
			[
				'post_name'    => 'convertkit-page-form-trigger-block-theme-color-params',
				'post_content' => '<!-- wp:convertkit/formtrigger {"form":"' . $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_ID'] . '","backgroundColor":"' . $backgroundColor . '","textColor":"' . $textColor . '"} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-form-trigger-block-theme-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL']);

		// Confirm that the chosen colors are applied as CSS styles.
		$I->seeInSource('class="wp-block-button__link convertkit-formtrigger has-text-color has-' . $textColor . '-color has-background has-' . $backgroundColor . '-background-color');
	}

	/**
	 * Test the Form Trigger block's hex color parameters works.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWithHexColorParameters(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Define colors.
		$backgroundColor = '#ee1616';
		$textColor       = '#1212c0';

		// It's tricky to interact with Gutenberg's color picker, so we programmatically create the Page
		// instead to then confirm the color settings apply on the output.
		// We don't need to test the color picker itself, as it's a Gutenberg supplied component, and our
		// other Acceptance tests confirm that the block can be added in Gutenberg etc.
		$I->havePageInDatabase(
			[
				'post_name'    => 'convertkit-page-form-trigger-block-hex-color-params',
				'post_content' => '<!-- wp:convertkit/formtrigger {"form":"' . $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_ID'] . '","style":{"color":{"text":"' . $textColor . '","background":"' . $backgroundColor . '"}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-form-trigger-block-hex-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Subscribe', $textColor, $backgroundColor);
	}

	/**
	 * Test the Form Trigger block's parameters are correctly escaped on output,
	 * to prevent XSS.
	 *
	 * @since   2.0.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockParameterEscaping(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Define a 'bad' block.  This is difficult to do in Gutenberg, but let's assume it's possible.
		$I->havePageInDatabase(
			[
				'post_name'    => 'convertkit-page-form-trigger-block-parameter-escaping',
				'post_content' => '<!-- wp:convertkit/formtrigger {"form":"' . $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_ID'] . '","style":{"color":{"text":"red\" onmouseover=\"alert(1)\""}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-form-trigger-block-parameter-escaping');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the output is escaped.
		$I->seeInSource('style="color:red&quot; onmouseover=&quot;alert(1)&quot;"');
		$I->dontSeeInSource('style="color:red" onmouseover="alert(1)""');

		// Confirm that the ConvertKit Form Trigger is displayed.
		$I->seeFormTriggerOutput($I, $_ENV['CONVERTKIT_API_FORM_FORMAT_MODAL_URL'], 'Subscribe');
	}

	/**
	 * Test the Form Trigger block displays a message with a link to the Plugin's
	 * settings screen, when the Plugin has no credentials specified.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWhenNoCredentials(AcceptanceTester $I)
	{
		$I->markTestIncomplete();

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Block: No Credentials');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Form Trigger', 'convertkit-formtrigger');

		// Test that the popup window works.
		$I->testBlockNoAPIKeyPopupWindow(
			$I,
			'convertkit-formtrigger',
			'Select a Form using the Form option in the Gutenberg sidebar.'
		);

		// Save page to avoid alert box when _passed() runs to deactivate the Plugin.
		$I->publishGutenbergPage($I);
	}

	/**
	 * Test the Form Trigger block displays a message with a link to the Plugin's
	 * settings screen, when the ConvertKit account has no forms.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockWhenNoForms(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Block: No Forms');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Form Trigger', 'convertkit-formtrigger');

		// Confirm that the Form block displays instructions to the user on how to add a Form in ConvertKit.
		$I->see(
			'No modal, sticky bar or slide in forms exist in Kit.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Click the link to confirm it loads ConvertKit.
		$I->click(
			'Click here to create a form.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Switch to next browser tab, as the link opens in a new tab.
		$I->switchToNextTab();

		// Confirm the ConvertKit login screen loaded.
		$I->waitForElementVisible('input[name="user[email]"]');

		// Close tab.
		$I->closeTab();

		// Save page to avoid alert box when _passed() runs to deactivate the Plugin.
		$I->publishGutenbergPage($I);
	}

	/**
	 * Test the Form Trigger block's refresh button works.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testFormTriggerBlockRefreshButton(AcceptanceTester $I)
	{
		// Setup Plugin with ConvertKit Account that has no Forms.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Form Trigger: Refresh Button');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Form Trigger', 'convertkit-formtrigger');

		// Setup Plugin with a valid API Key and resources, as if the user performed the necessary steps to authenticate
		// and create a form.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Click the refresh button.
		$I->click('div.convertkit-no-content button.convertkit-block-refresh');

		// Wait for the refresh button to disappear, confirming that an API Key and resources now exist.
		$I->waitForElementNotVisible('div.convertkit-no-content button.convertkit-block-refresh');

		// Confirm that the Form Trigger block displays instructions to the user on how to select a Form.
		$I->see(
			'Select a Form using the Form option in the Gutenberg sidebar.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   2.2.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
