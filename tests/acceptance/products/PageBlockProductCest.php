<?php
/**
 * Tests for the ConvertKit Product Gutenberg Block.
 *
 * @since   1.9.8.5
 */
class PageBlockProductCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
	}

	/**
	 * Test the Product block works when using a valid Product parameter.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithValidProductParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Valid Product Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Product setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product' => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');
	}

	/**
	 * Test the Product block works when not defining a Product parameter.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithNoProductParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: No Product Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Product', 'convertkit-product');

		// Confirm that the Product block displays instructions to the user on how to select a Product.
		$I->see(
			'Select a Product using the Product option in the Gutenberg sidebar.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that no ConvertKit Product button is displayed.
		$I->dontSeeProductOutput($I);
	}

	/**
	 * Test the Product block's text parameter works.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithTextParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Text Param');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product' => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'text'    => [ 'text', 'Buy Now' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy Now');
	}

	/**
	 * Test the Product block's default text value is output when the text parameter is blank.
	 *
	 * @since   2.0.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithBlankTextParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Blank Text Param');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product' => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'text'    => [ 'text', '' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');
	}

	/**
	 * Test the Product block works when using a valid discount code.
	 *
	 * @since   2.4.1
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithValidDiscountCodeParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Valid Discount Code Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Product setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product'       => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'discount_code' => [ 'text', $_ENV['CONVERTKIT_API_PRODUCT_DISCOUNT_CODE'] ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');

		// Confirm the discount code has been applied.
		$I->switchToIFrame('iframe[data-active]');
		$I->waitForElementVisible('.formkit-main');
		$I->see('$0.00');
	}

	/**
	 * Test the Product block works when using an invalid discount code.
	 *
	 * @since   2.4.1
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithInvalidDiscountCodeParameter(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Invalid Discount Code Param');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Product setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product'       => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'discount_code' => [ 'text', 'fake' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');

		// Confirm the discount code is not valid, but the modal displays so the user can still purchase.
		$I->switchToIFrame('iframe[data-active]');
		$I->waitForElementVisible('.formkit-main');
		$I->see('The coupon is not valid.');
	}

	/**
	 * Test the Product shortcode opens the ConvertKit Product's checkuot step
	 * when the Checkout option is enabled.
	 *
	 * @since   2.4.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithCheckoutParameterEnabled(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Checkout Step');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Product setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product'                     => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'#inspector-toggle-control-0' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the ConvertKit Product is displayed.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');

		// Confirm the checkout step is displayed.
		$I->switchToIFrame('iframe[data-active]');
		$I->waitForElementVisible('.formkit-main');
		$I->see('Order Summary');
	}

	/**
	 * Test the Product block opens the ConvertKit Product in the same window instead
	 * of a modal when the Disable modal on mobile option is enabled.
	 *
	 * @since   2.4.1
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithDisableModalOnMobileParameterEnabled(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Disable Modal on Mobile');

		// Configure metabox's Form setting = None, ensuring we only test the block in Gutenberg.
		$I->configureMetaboxSettings(
			$I,
			'wp-convertkit-meta-box',
			[
				'form' => [ 'select2', 'None' ],
			]
		);

		// Add block to Page, setting the Product setting to the value specified in the .env file.
		$I->addGutenbergBlock(
			$I,
			'Kit Product',
			'convertkit-product',
			[
				'product'                     => [ 'select', $_ENV['CONVERTKIT_API_PRODUCT_NAME'] ],
				'#inspector-toggle-control-1' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$url = $I->publishAndViewGutenbergPage($I);

		// Change device and user agent to a mobile.
		$I->enableMobileEmulation();

		// Load page.
		$I->amOnUrl($url);

		// Confirm that the block displays without the data-commerce attribute.
		$I->seeElementInDOM('.convertkit-product a');
		$I->dontSeeElementInDOM('.convertkit-product a[data-commerce]');

		// Confirm that clicking the button opens the URL in the same browser tab, and not a modal.
		$I->click('.convertkit-product a');
		$I->waitForElementVisible('body[data-template]');

		// Change device and user agent to desktop.
		$I->disableMobileEmulation();
	}

	/**
	 * Test the Product block's theme color parameters works.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithThemeColorParameters(AcceptanceTester $I)
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
				'post_name'    => 'convertkit-page-product-block-theme-color-params',
				'post_content' => '<!-- wp:convertkit/product {"product":"36377","backgroundColor":"' . $backgroundColor . '","textColor":"' . $textColor . '"} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-product-block-theme-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL']);

		// Confirm that the chosen colors are applied as CSS styles.
		$I->seeInSource('class="wp-block-button__link convertkit-product has-text-color has-' . $textColor . '-color has-background has-' . $backgroundColor . '-background-color');
	}

	/**
	 * Test the Product block's hex color parameters works.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWithHexColorParameters(AcceptanceTester $I)
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
				'post_name'    => 'convertkit-page-product-block-hex-color-params',
				'post_content' => '<!-- wp:convertkit/product {"product":"36377","style":{"color":{"text":"' . $textColor . '","background":"' . $backgroundColor . '"}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-product-block-hex-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product', $textColor, $backgroundColor);
	}

	/**
	 * Test the Product block displays a message with a link to the Plugin's
	 * settings screen, when the Plugin has Not connected to Kit.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWhenNoAPIKey(AcceptanceTester $I)
	{
		$I->markTestIncomplete();

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Block: No API Key');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Product', 'convertkit-product');

		// Test that the popup window works.
		$I->testBlockNoAPIKeyPopupWindow(
			$I,
			'convertkit-product',
			'Select a Product using the Product option in the Gutenberg sidebar.'
		);

		// Save page to avoid alert box when _passed() runs to deactivate the Plugin.
		$I->publishGutenbergPage($I);
	}

	/**
	 * Test the Product block displays a message with a link to the Plugin's
	 * settings screen, when the ConvertKit account has no products.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockWhenNoProducts(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Block: No Products');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Product', 'convertkit-product');

		// Confirm that the Product block displays instructions to the user on how to add a Product in ConvertKit.
		$I->see(
			'No products exist in Kit.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Click the link to confirm it loads ConvertKit.
		$I->click(
			'Click here to create your first product.',
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
	 * Test the Product block's refresh button works.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockRefreshButton(AcceptanceTester $I)
	{
		// Setup Plugin with ConvertKit Account that has no Products.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Product: Refresh Button');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Product', 'convertkit-product');

		// Setup Plugin with a valid API Key and resources, as if the user performed the necessary steps to authenticate
		// and create a product.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Click the refresh button.
		$I->click('div.convertkit-no-content button.convertkit-block-refresh');

		// Wait for the refresh button to disappear, confirming that an API Key and resources now exist.
		$I->waitForElementNotVisible('div.convertkit-no-content button.convertkit-block-refresh');

		// Confirm that the Product block displays instructions to the user on how to select a Product.
		$I->see(
			'Select a Product using the Product option in the Gutenberg sidebar.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);
	}

	/**
	 * Test the Product block's parameters are correctly escaped on output,
	 * to prevent XSS.
	 *
	 * @since   2.0.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testProductBlockParameterEscaping(AcceptanceTester $I)
	{
		// Setup ConvertKit Plugin with no default form specified.
		$I->setupConvertKitPluginNoDefaultForms($I);
		$I->setupConvertKitPluginResources($I);

		// Define a 'bad' block.  This is difficult to do in Gutenberg, but let's assume it's possible.
		$I->havePageInDatabase(
			[
				'post_name'    => 'convertkit-page-product-block-parameter-escaping',
				'post_content' => '<!-- wp:convertkit/product {"product":"' . $_ENV['CONVERTKIT_API_PRODUCT_ID'] . '","style":{"color":{"text":"red\" onmouseover=\"alert(1)\""}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-product-block-parameter-escaping');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the output is escaped.
		$I->seeInSource('style="color:red&quot; onmouseover=&quot;alert(1)&quot;"');
		$I->dontSeeInSource('style="color:red" onmouseover="alert(1)""');

		// Confirm that the ConvertKit Product is displayed.
		$I->seeProductOutput($I, $_ENV['CONVERTKIT_API_PRODUCT_URL'], 'Buy my product');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.9.8.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
