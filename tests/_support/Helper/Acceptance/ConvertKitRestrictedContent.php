<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the Member Content functionality
 * of the ConvertKit Plugin, which are then available using $I->{yourFunctionName}.
 *
 * @since   2.1.0
 */
class ConvertKitRestrictedContent extends \Codeception\Module
{
	/**
	 * Returns the expected default settings for Restricted Content.
	 *
	 * @since   2.1.0
	 *
	 * @return  array
	 */
	public function getRestrictedContentDefaultSettings()
	{
		return array(
			// Restrict by Product.
			'subscribe_heading'      => 'Read this post with a premium subscription',
			'subscribe_text'         => 'This post is only available to premium subscribers. Join today to get access to all posts.',

			// Restrict by Tag.
			'subscribe_heading_tag'  => 'Subscribe to keep reading',
			'subscribe_text_tag'     => 'This post is free to read but only available to subscribers. Join today to get access to all posts.',

			// All.
			'subscribe_button_label' => 'Subscribe',
			'email_text'             => 'Already subscribed?',
			'email_button_label'     => 'Log in',
			'email_description_text' => 'We\'ll email you a magic code to log you in without a password.',
			'email_check_heading'    => 'We just emailed you a log in code',
			'email_check_text'       => 'Enter the code below to finish logging in',
			'no_access_text'         => 'Your account does not have access to this content. Please use the button above to purchase, or enter the email address you used to purchase the product.',
		);
	}

	/**
	 * Creates a Page in the database with the given title for restricted content.
	 *
	 * The Page's content comprises of a mix of visible and member's only content.
	 * The default form setting is set to 'None'.
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                          Tester.
	 * @param   string           $postType                   Post Type.
	 * @param   string           $title                      Title.
	 * @param   string           $visibleContent             Content that should always be visible.
	 * @param   string           $memberContent              Content that should only be available to authenticated subscribers.
	 * @param   string           $restrictContentSetting     Restrict Content setting.
	 * @return  int                                          Page ID.
	 */
	public function createRestrictedContentPage($I, $postType, $title, $visibleContent = 'Visible content.', $memberContent = 'Member only content.', $restrictContentSetting = '')
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => $postType,
				'post_title'   => $title,

				// Emulate Gutenberg content with visible and members only content sections.
				'post_content' => '<!-- wp:paragraph --><p>' . $visibleContent . '</p><!-- /wp:paragraph -->
<!-- wp:more --><!--more--><!-- /wp:more -->
<!-- wp:paragraph -->' . $memberContent . '<!-- /wp:paragraph -->',

				// Don't display a Form on this Page, so we test against Restrict Content's Form.
				'meta_input'   => [
					'_wp_convertkit_post_meta' => [
						'form'             => '-1',
						'landing_page'     => '',
						'tag'              => '',
						'restrict_content' => $restrictContentSetting,
					],
				],
			]
		);
	}

	/**
	 * Run frontend tests for restricted content by ConvertKit Product, to confirm that visible and member's content
	 * is / is not displayed when logging in with valid and invalid subscriber email addresses.
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string|int       $urlOrPageID        URL or ID of Restricted Content Page.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 * @param   bool|array       $textItems          Expected text for subscribe text, subscribe button label, email text etc. If not defined, uses expected defaults.
	 */
	public function testRestrictedContentByProductOnFrontend($I, $urlOrPageID, $visibleContent = 'Visible content.', $memberContent = 'Member only content.', $textItems = false)
	{
		// Define expected text and labels if not supplied.
		if ( ! $textItems ) {
			$textItems = $this->getRestrictedContentDefaultSettings();
		}

		// Navigate to the page.
		if ( is_numeric( $urlOrPageID ) ) {
			$I->amOnPage('?p=' . $urlOrPageID);
		} else {
			$I->amOnUrl($urlOrPageID);
		}

		// Confirm Restrict Content CSS is output.
		$I->seeInSource('<link rel="stylesheet" id="convertkit-restrict-content-css" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-content/plugins/convertkit/resources/frontend/css/restrict-content.css');

		// Check content is not displayed, and CTA displays with expected text.
		$this->testRestrictContentByProductHidesContentWithCTA($I, $visibleContent, $memberContent, $textItems);

		// Login as a ConvertKit subscriber who does not exist in ConvertKit.
		$I->waitForElementVisible('input#convertkit_email');
		$I->fillField('convertkit_email', 'fail@convertkit.com');
		$I->click('input.wp-block-button__link');

		// Confirm an inline error message is displayed.
		$I->seeInSource('<div class="convertkit-restrict-content-notice convertkit-restrict-content-notice-error">invalid: Email address is invalid</div>');
		$I->seeInSource('<div id="convertkit-restrict-content-email-field" class="convertkit-restrict-content-error">');

		// Check content is not displayed, and CTA displays with expected text.
		$this->testRestrictContentByProductHidesContentWithCTA($I, $visibleContent, $memberContent, $textItems);

		// Set cookie with signed subscriber ID and reload the restricted content page, as if we entered the
		// code sent in the email as a ConvertKit subscriber who has not subscribed to the product.
		$I->setCookie('ck_subscriber_id', $_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID_NO_ACCESS']);
		if ( is_numeric( $urlOrPageID ) ) {
			$I->amOnPage('?p=' . $urlOrPageID . '&ck-cache-bust=' . microtime() );
		} else {
			$I->amOnUrl($urlOrPageID . '?ck-cache-bust=' . microtime() );
		}

		// Confirm an inline error message is displayed.
		$I->seeInSource('<div class="convertkit-restrict-content-notice convertkit-restrict-content-notice-error">' . $textItems['no_access_text'] . '</div>');
		$I->seeInSource('<div id="convertkit-restrict-content-email-field" class="convertkit-restrict-content-error">');

		// Check content is not displayed, and CTA displays with expected text.
		$this->testRestrictContentByProductHidesContentWithCTA($I, $visibleContent, $memberContent, $textItems);

		// Login as a ConvertKit subscriber who has subscribed to the product.
		$I->waitForElementVisible('input#convertkit_email');
		$I->fillField('convertkit_email', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$I->click('input.wp-block-button__link');

		// Confirm that confirmation an email has been sent is displayed.
		$this->testRestrictContentShowsEmailCodeForm($I, $visibleContent, $memberContent, $textItems);

		// Test that the restricted content displays when a valid signed subscriber ID is used,
		// as if we entered the code sent in the email.
		$this->testRestrictedContentShowsContentWithValidSubscriberID($I, $urlOrPageID, $visibleContent, $memberContent);
	}

	/**
	 * Run frontend tests for restricted content by ConvertKit Product, using the modal authentication flow, to confirm
	 * that visible and member's content is / is not displayed when logging in with valid and invalid subscriber email addresses.
	 *
	 * @since   2.3.8
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string|int       $urlOrPageID        URL or ID of Restricted Content Page.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 * @param   bool|array       $textItems          Expected text for subscribe text, subscribe button label, email text etc. If not defined, uses expected defaults.
	 */
	public function testRestrictedContentModalByProductOnFrontend($I, $urlOrPageID, $visibleContent = 'Visible content.', $memberContent = 'Member only content.', $textItems = false)
	{
		// Define expected text and labels if not supplied.
		if ( ! $textItems ) {
			$textItems = $this->getRestrictedContentDefaultSettings();
		}

		// Navigate to the page.
		if ( is_numeric( $urlOrPageID ) ) {
			$I->amOnPage('?p=' . $urlOrPageID);
		} else {
			$I->amOnUrl($urlOrPageID);
		}

		// Confirm Restrict Content CSS is output.
		$I->seeInSource('<link rel="stylesheet" id="convertkit-restrict-content-css" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-content/plugins/convertkit/resources/frontend/css/restrict-content.css');

		// Check content is not displayed, and CTA displays with expected text.
		$this->testRestrictContentByProductHidesContentWithCTA($I, $visibleContent, $memberContent, $textItems);

		// Login as a ConvertKit subscriber who does not exist in ConvertKit.
		$I->click('a.convertkit-restrict-content-modal-open');
		$I->waitForElementVisible('#convertkit-restrict-content-modal');
		$I->waitForElementVisible('input#convertkit_email');
		$I->fillField('convertkit_email', 'fail@convertkit.com');
		$I->click('#convertkit-restrict-content-modal input.wp-block-button__link');

		// Confirm an inline error message is displayed.
		$I->waitForElementVisible('.convertkit-restrict-content-notice-error');
		$I->see('invalid: Email address is invalid', '.convertkit-restrict-content-notice-error');

		// Login as a ConvertKit subscriber who has subscribed to the product.
		$I->fillField('convertkit_email', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$I->click('#convertkit-restrict-content-modal input.wp-block-button__link');

		// Confirm that confirmation an email has been sent is displayed.
		$I->waitForElementVisible('input#convertkit_subscriber_code');
		$I->see($textItems['email_check_heading'], 'h4');
		$I->see($textItems['email_check_text'], 'p');

		// Enter an invalid code.
		$I->fillField('subscriber_code', '999999');

		// Confirm an inline error message is displayed.
		$I->waitForElementVisible('.convertkit-restrict-content-notice-error');
		$I->see('The entered code is invalid. Please try again, or click the link sent in the email.', '.convertkit-restrict-content-notice-error');
		$I->seeElementInDOM('input#convertkit_subscriber_code');

		// Test that the restricted content displays when a valid signed subscriber ID is used,
		// as if we entered the code sent in the email.
		$this->testRestrictedContentShowsContentWithValidSubscriberID($I, $urlOrPageID, $visibleContent, $memberContent);
	}

	/**
	 * Run frontend tests for restricted content by ConvertKit Product, to confirm that visible and member's content
	 * is / is not displayed when logging in with valid and invalid subscriber email addresses.
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string|int       $urlOrPageID        URL or ID of Restricted Content Page.
	 * @param   string           $emailAddress       Email Address.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 */
	public function testRestrictedContentByTagOnFrontend($I, $urlOrPageID, $emailAddress, $visibleContent = 'Visible content.', $memberContent = 'Member only content.')
	{
		// Get default settings.
		$textItems = $this->getRestrictedContentDefaultSettings();

		// Navigate to the page.
		if ( is_numeric( $urlOrPageID ) ) {
			$I->amOnPage('?p=' . $urlOrPageID);
		} else {
			$I->amOnUrl($urlOrPageID);
		}

		// Clear any existing cookie from a previous test and reload.
		$I->resetCookie('ck_subscriber_id');
		$I->reloadPage();

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm Restrict Content CSS is output.
		$I->seeInSource('<link rel="stylesheet" id="convertkit-restrict-content-css" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-content/plugins/convertkit/resources/frontend/css/restrict-content.css');

		// Confirm that the visible text displays, hidden text does not display and the CTA displays.
		$I->see($visibleContent);
		$I->dontSee($memberContent);

		// Confirm that the CTA displays with the expected headings, text and other elements.
		$I->seeElementInDOM('#convertkit-restrict-content');
		$I->seeInSource('<h3>' . $textItems['subscribe_heading_tag'] . '</h3>');
		$I->see($textItems['subscribe_text_tag']);
		$I->seeInSource('<input type="submit" class="wp-block-button__link wp-block-button__link" value="' . $textItems['subscribe_button_label'] . '">');

		// Enter the email address and submit the form.
		$I->fillField('convertkit_email', $emailAddress);
		$I->click('input.wp-block-button__link');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the restricted content is now displayed.
		$I->testRestrictContentDisplaysContent($I, $visibleContent, $memberContent);
	}

	/**
	 * Run frontend tests for restricted content, to confirm that both visible and member content is displayed
	 * when a valid signed subscriber ID is set as a cookie, as if the user entered a code sent in the email.
	 *
	 * @since   2.2.2
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string|int       $urlOrPageID        URL or ID of Restricted Content Page.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 */
	public function testRestrictedContentShowsContentWithValidSubscriberID($I, $urlOrPageID, $visibleContent, $memberContent)
	{
		// Set cookie with signed subscriber ID, as if we entered the code sent in the email.
		$I->setCookie('ck_subscriber_id', $_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID']);

		// Reload the restricted content page.
		if ( is_numeric( $urlOrPageID ) ) {
			$I->amOnPage('?p=' . $urlOrPageID );
		} else {
			$I->amOnUrl($urlOrPageID );
		}

		// Confirm cookie was set with the expected value.
		$I->assertEquals($I->grabCookie('ck_subscriber_id'), $_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID']);

		// Confirm that the restricted content is now displayed, as we've authenticated as a subscriber
		// who has access to this Product.
		$I->testRestrictContentDisplaysContent($I, $visibleContent, $memberContent);
	}

	/**
	 * Run frontend tests for restricted content, to confirm that:
	 * - visible content is displayed,
	 * - member's content is not displayed,
	 * - the CTA is displayed with the expected text
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 * @param   bool|array       $textItems          Expected text for subscribe text, subscribe button label, email text etc. If not defined, uses expected defaults.
	 */
	public function testRestrictContentByProductHidesContentWithCTA($I, $visibleContent = 'Visible content.', $memberContent = 'Member only content.', $textItems = false)
	{
		// Define expected text and labels if not supplied.
		if ( ! $textItems ) {
			$textItems = $this->getRestrictedContentDefaultSettings();
		}

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the visible text displays, hidden text does not display and the CTA displays.
		if ( ! empty($visibleContent)) {
			$I->see($visibleContent);
		}
		$I->dontSee($memberContent);

		// Confirm that the CTA displays with the expected headings, text, buttons and other elements.
		$I->seeElementInDOM('#convertkit-restrict-content');

		$I->seeInSource('<h3>' . $textItems['subscribe_heading'] . '</h3>');
		$I->see($textItems['subscribe_text']);

		$I->see($textItems['subscribe_button_label']);
		$I->seeInSource('<a href="' . $_ENV['CONVERTKIT_API_PRODUCT_URL'] . '" class="wp-block-button__link');

		$I->see($textItems['email_text']);
		$I->seeInSource('<input type="submit" class="wp-block-button__link wp-block-button__link" value="' . $textItems['email_button_label'] . '">');
		$I->seeInSource('<small>' . $textItems['email_description_text'] . '</small>');
	}

	/**
	 * Run frontend tests for restricted content, to confirm that:
	 * - visible content is displayed,
	 * - member's content is not displayed,
	 * - the email code form is displayed with the expected text.
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 * @param   bool|array       $textItems          Expected text for subscribe text, subscribe button label, email text etc. If not defined, uses expected defaults.
	 */
	public function testRestrictContentShowsEmailCodeForm($I, $visibleContent = 'Visible content.', $memberContent = 'Member only content.', $textItems)
	{
		// Define expected text and labels if not supplied.
		if ( ! $textItems ) {
			$textItems = $this->getRestrictedContentDefaultSettings();
		}

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the visible text displays, hidden text does not display and the CTA displays.
		if ( ! empty($visibleContent)) {
			$I->see($visibleContent);
		}
		$I->dontSee($memberContent);

		// Confirm that the CTA displays with the expected text.
		$I->seeElementInDOM('#convertkit-restrict-content');
		$I->seeInSource('<h4>' . $textItems['email_check_heading'] . '</h4>');
		$I->see($textItems['email_check_text']);
		$I->seeElementInDOM('input#convertkit_subscriber_code');
		$I->seeElementInDOM('input.wp-block-button__link');

		// Enter an invalid code.
		$I->fillField('subscriber_code', '999999');
		$I->click('Verify');

		// Confirm an inline error message is displayed.
		$I->seeInSource('<div class="convertkit-restrict-content-notice convertkit-restrict-content-notice-error">The entered code is invalid. Please try again, or click the link sent in the email.</div>');
		$I->seeInSource('<div id="convertkit-subscriber-code-container" class="convertkit-restrict-content-error">');
	}

	/**
	 * Run frontend tests for restricted content, to confirm that:
	 * - visible content is displayed,
	 * - member's content is displayed,
	 * - the CTA is not displayed
	 *
	 * @since   2.1.0
	 *
	 * @param   AcceptanceTester $I                  Tester.
	 * @param   string           $visibleContent     Content that should always be visible.
	 * @param   string           $memberContent      Content that should only be available to authenticated subscribers.
	 */
	public function testRestrictContentDisplaysContent($I, $visibleContent = 'Visible content.', $memberContent = 'Member only content.')
	{
		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the visible and hidden text displays.
		if ( ! empty($visibleContent)) {
			$I->see($visibleContent);
		}
		$I->see($memberContent);

		// Confirm that the CTA is not displayed.
		$I->dontSeeElementInDOM('#convertkit-restrict-content');
	}
}