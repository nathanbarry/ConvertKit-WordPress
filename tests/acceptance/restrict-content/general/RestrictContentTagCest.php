<?php
/**
 * Tests Restrict Content by Tag functionality.
 *
 * @since   2.3.2
 */
class RestrictContentTagCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate and Setup ConvertKit plugin.
		$I->activateConvertKitPlugin($I);
		$I->setupConvertKitPlugin($I);
	}

	/**
	 * Test that restricting content by a Tag specified in the Page Settings works when
	 * creating and viewing a new WordPress Page.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByTag(AcceptanceTester $I)
	{
		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Restrict Content: Tag');

		// Configure metabox's Restrict Content setting = Tag name.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form'             => [ 'select2', 'None' ],
				'restrict_content' => [ 'select2', $_ENV['CONVERTKIT_API_TAG_NAME'] ],
			]
		);

		// Add blocks.
		$I->addGutenbergParagraphBlock($I, 'Visible content.');
		$I->addGutenbergBlock($I, 'More', 'more');
		$I->addGutenbergParagraphBlock($I, 'Member-only content.');

		// Publish Page.
		$url = $I->publishGutenbergPage($I);

		// Test Restrict Content functionality.
		$I->testRestrictedContentByTagOnFrontend($I, $url, $I->generateEmailAddress());
	}

	/**
	 * Test that restricting content by a Tag that does not exist does not output
	 * a fatal error and instead displays all of the Page's content.
	 *
	 * This checks for when a Tag is deleted in ConvertKit, but is still specified
	 * as the Restrict Content setting for a Page.
	 *
	 * @since   2.3.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByInvalidTag(AcceptanceTester $I)
	{
		// Programmatically create a Page.
		$pageID = $I->createRestrictedContentPage(
			$I,
			[
				'post_title'               => 'Kit: Page: Restrict Content: Invalid Tag',
				'restrict_content_setting' => 'tag_12345', // A fake Tag that does not exist in ConvertKit.
			]
		);

		// Navigate to the page.
		$I->amOnPage('?p=' . $pageID);

		// Confirm all content displays, with no errors, as the Tag is invalid.
		$I->testRestrictContentDisplaysContent($I);
	}

	/**
	 * Test that restricting content by a Tag specified in the Page Settings works when
	 * creating and viewing a new WordPress Page, with Google's reCAPTCHA enabled.
	 *
	 * @since   2.6.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByTagWithRecaptchaEnabled(AcceptanceTester $I)
	{
		// Setup Restrict Content functionality with reCAPTCHA enabled.
		$I->setupConvertKitPluginRestrictContent(
			$I,
			[
				'recaptcha_site_key'      => $_ENV['CONVERTKIT_API_RECAPTCHA_SITE_KEY'],
				'recaptcha_secret_key'    => $_ENV['CONVERTKIT_API_RECAPTCHA_SECRET_KEY'],
				'recaptcha_minimum_score' => '0.01', // Set a low score to ensure reCAPTCHA passes the subscriber.
			]
		);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Restrict Content: Tag: reCAPTCHA');

		// Configure metabox's Restrict Content setting = Tag name.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form'             => [ 'select2', 'None' ],
				'restrict_content' => [ 'select2', $_ENV['CONVERTKIT_API_TAG_NAME'] ],
			]
		);

		// Add blocks.
		$I->addGutenbergParagraphBlock($I, 'Visible content.');
		$I->addGutenbergBlock($I, 'More', 'more');
		$I->addGutenbergParagraphBlock($I, 'Member-only content.');

		// Publish Page.
		$url = $I->publishGutenbergPage($I);

		// Test Restrict Content functionality.
		$I->testRestrictedContentByTagOnFrontend($I, $url, $I->generateEmailAddress(), false, true);
	}

	/**
	 * Test that restricting content by a Tag specified in the Page Settings works when
	 * creating and viewing a new WordPress Page, with Google's reCAPTCHA enabled.
	 *
	 * @since   2.6.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByTagWithRecaptchaEnabledWithHighMinimumScore(AcceptanceTester $I)
	{
		// Setup Restrict Content functionality with reCAPTCHA enabled.
		$I->setupConvertKitPluginRestrictContent(
			$I,
			[
				'recaptcha_site_key'      => $_ENV['CONVERTKIT_API_RECAPTCHA_SITE_KEY'],
				'recaptcha_secret_key'    => $_ENV['CONVERTKIT_API_RECAPTCHA_SECRET_KEY'],
				'recaptcha_minimum_score' => '0.99', // Set a high score to ensure reCAPTCHA blocks the subscriber.
			]
		);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Restrict Content: Tag: reCAPTCHA High Min Score');

		// Configure metabox's Restrict Content setting = Tag name.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form'             => [ 'select2', 'None' ],
				'restrict_content' => [ 'select2', $_ENV['CONVERTKIT_API_TAG_NAME'] ],
			]
		);

		// Add blocks.
		$I->addGutenbergParagraphBlock($I, 'Visible content.');
		$I->addGutenbergBlock($I, 'More', 'more');
		$I->addGutenbergParagraphBlock($I, 'Member-only content.');

		// Publish Page.
		$url = $I->publishGutenbergPage($I);

		// Load page.
		$I->amOnUrl($url);

		// Enter the email address and submit the form.
		$I->fillField('convertkit_email', $I->generateEmailAddress());
		$I->click('input.wp-block-button__link');

		// Wait for reCAPTCHA to fully load.
		$I->wait(3);

		// Confirm an error message is displayed.
		$I->waitForElementVisible('#convertkit-restrict-content');
		$I->seeInSource('<div class="convertkit-restrict-content-notice convertkit-restrict-content-notice-error">Google reCAPTCHA failed</div>');
	}

	/**
	 * Test that restricting content by a Tag specified in the Page Settings works when
	 * using the Quick Edit functionality.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByTagUsingQuickEdit(AcceptanceTester $I)
	{
		// Programmatically create a Page.
		$pageID = $I->createRestrictedContentPage(
			$I,
			[
				'post_title' => 'Kit: Page: Restrict Content: Tag: ' . $_ENV['CONVERTKIT_API_TAG_NAME'] . ': Quick Edit',
			]
		);

		// Quick Edit the Page in the Pages WP_List_Table.
		$I->quickEdit(
			$I,
			'page',
			$pageID,
			[
				'restrict_content' => [ 'select', $_ENV['CONVERTKIT_API_TAG_NAME'] ],
			]
		);

		// Test Restrict Content functionality.
		$I->testRestrictedContentByTagOnFrontend($I, $pageID, $I->generateEmailAddress());
	}

	/**
	 * Test that restricting content by a Tag specified in the Page Settings works when
	 * using the Bulk Edit functionality.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testRestrictContentByTagUsingBulkEdit(AcceptanceTester $I)
	{
		// Programmatically create two Pages.
		$pageIDs = array(
			$I->createRestrictedContentPage(
				$I,
				[
					'post_title' => 'Kit: Page: Restrict Content: Tag: ' . $_ENV['CONVERTKIT_API_TAG_NAME'] . ': Bulk Edit #1',
				]
			),
			$I->createRestrictedContentPage(
				$I,
				[
					'post_title' => 'Kit: Page: Restrict Content: Tag: ' . $_ENV['CONVERTKIT_API_TAG_NAME'] . ': Bulk Edit #2',
				]
			),
		);

		// Bulk Edit the Pages in the Pages WP_List_Table.
		$I->bulkEdit(
			$I,
			'page',
			$pageIDs,
			[
				'restrict_content' => [ 'select', $_ENV['CONVERTKIT_API_TAG_NAME'] ],
			]
		);

		// Iterate through Pages to run frontend tests.
		foreach ($pageIDs as $pageID) {
			// Test Restrict Content functionality.
			$I->testRestrictedContentByTagOnFrontend($I, $pageID, $I->generateEmailAddress());
		}
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   2.3.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->resetCookie('ck_subscriber_id');
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
