<?php
/**
 * Tests for the ConvertKit Broadcasts Gutenberg Block.
 *
 * @since   1.9.7.4
 */
class PageBlockBroadcastsCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
	}

	/**
	 * Test the Broadcasts block displays a message with a link that opens
	 * a popup window with the Plugin's Setup Wizard, when the Plugin has
	 * Not connected to Kit.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWhenNoAPIKey(AcceptanceTester $I)
	{
		$I->markTestIncomplete();

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Block: No API Key');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Broadcasts', 'convertkit-broadcasts');

		// Test that the popup window works.
		$I->testBlockNoAPIKeyPopupWindow(
			$I,
			'convertkit-broadcasts'
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);
	}

	/**
	 * Test the Broadcasts block outputs a message when no Broadcasts exist.
	 *
	 * @since   2.0.1
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithNoBroadcasts(AcceptanceTester $I)
	{
		// Setup Plugin with ConvertKit Account that has no Broadcasts.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: No Broadcasts');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Broadcasts', 'convertkit-broadcasts');

		// Confirm that the Broadcasts block displays instructions to the user on how to add a Broadcast in ConvertKit.
		$I->see(
			'No broadcasts exist in Kit.',
			[
				'css' => '.convertkit-no-content',
			]
		);

		// Click the link to confirm it loads ConvertKit.
		$I->click(
			'Click here to send your first broadcast.',
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

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that no ConvertKit Broadcasts are displayed.
		$I->dontSeeElementInDOM('div.convertkit-broadcasts');
	}

	/**
	 * Test the Broadcasts block's refresh button works.
	 *
	 * @since   2.2.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockRefreshButton(AcceptanceTester $I)
	{
		// Setup Plugin with ConvertKit Account that has no Broadcasts.
		$I->setupConvertKitPluginCredentialsNoData($I);
		$I->setupConvertKitPluginResourcesNoData($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Refresh Button');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Broadcasts', 'convertkit-broadcasts');

		// Setup Plugin with a valid API Key and resources, as if the user performed the necessary steps to authenticate
		// and created a broadcast.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Click the refresh button.
		$I->click('div.convertkit-no-content button.convertkit-block-refresh');

		// Wait for the refresh button to disappear, confirming that an API Key and resources now exist.
		$I->waitForElementNotVisible('div.convertkit-no-content button.convertkit-block-refresh');

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);
	}

	/**
	 * Test the Broadcasts block works when using the default parameters.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDefaultParameters(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Default Params');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Broadcasts', 'convertkit-broadcasts');

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);

		// Confirm that the default date format is as expected.
		$I->seeInSource('<time datetime="' . date( 'Y-m-d', strtotime( $_ENV['CONVERTKIT_API_BROADCAST_FIRST_DATE'] ) ) . '">' . date( 'F j, Y', strtotime( $_ENV['CONVERTKIT_API_BROADCAST_FIRST_DATE'] ) ) . '</time>');

		// Confirm that the expected Broadcast name is displayed first links to the expected URL, with UTM parameters.
		$I->assertEquals(
			$I->grabAttributeFrom('div.convertkit-broadcasts ul.convertkit-broadcasts-list li.convertkit-broadcast:nth-child(2) a', 'href'),
			$_ENV['CONVERTKIT_API_BROADCAST_FIRST_URL'] . '?utm_source=wordpress&utm_term=en_US&utm_content=convertkit'
		);
	}

	/**
	 * Test the Broadcasts block's "Display as grid" parameter works.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDisplayGridParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Display as Grid');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'#inspector-toggle-control-0' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts in the grid format.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
				'see_grid'     => true,
			]
		);
	}

	/**
	 * Test the Broadcasts block's date format parameter works.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDateFormatParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Date Format Param');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'date_format' => [ 'select', 'Y-m-d' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);

		// Confirm that the date format is as expected.
		$I->seeInSource('<time datetime="' . date( 'Y-m-d', strtotime( $_ENV['CONVERTKIT_API_BROADCAST_FIRST_DATE'] ) ) . '">' . date( 'Y-m-d', strtotime( $_ENV['CONVERTKIT_API_BROADCAST_FIRST_DATE'] ) ) . '</time>');

		// Confirm that the expected Broadcast name is displayed first links to the expected URL, with UTM parameters.
		$I->assertEquals(
			$I->grabAttributeFrom('div.convertkit-broadcasts ul.convertkit-broadcasts-list li.convertkit-broadcast:nth-child(2) a', 'href'),
			$_ENV['CONVERTKIT_API_BROADCAST_FIRST_URL'] . '?utm_source=wordpress&utm_term=en_US&utm_content=convertkit'
		);
	}

	/**
	 * Test the Broadcasts block's "Display image" parameter works.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDisplayImageParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Display image');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'#inspector-toggle-control-0' => [ 'toggle', true ],
				'#inspector-toggle-control-1' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts in the grid format.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
				'see_grid'     => true,
				'see_image'    => true,
			]
		);
	}

	/**
	 * Test the Broadcasts block's "Display description" parameter works.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDisplayDescriptionParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Display description');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'#inspector-toggle-control-2' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts in the grid format.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts'    => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
				'see_description' => true,
			]
		);
	}

	/**
	 * Test the Broadcasts block's "Display read more link" parameter works.
	 *
	 * @since   2.2.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithDisplayReadMoreLinkParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Display read more link');

		// Add block to Page, setting the date format.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'#inspector-toggle-control-3' => [ 'toggle', true ],
				'read_more_label'             => [ 'input', 'Continue reading' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts in the grid format.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts'  => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
				'see_read_more' => 'Continue reading',
			]
		);
	}

	/**
	 * Test the Broadcasts block's limit parameter works.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithLimitParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Limit Param');

		// Add block to Page, setting the limit.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'limit' => [ 'input', '2', 'Pagination' ], // Click the Pagination tab first before starting to complete fields.
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => 2,
			]
		);

		// Confirm that the expected Broadcast name is displayed first links to the expected URL, with UTM parameters.
		$I->assertEquals(
			$I->grabAttributeFrom('div.convertkit-broadcasts ul.convertkit-broadcasts-list li.convertkit-broadcast:nth-child(2) a', 'href'),
			$_ENV['CONVERTKIT_API_BROADCAST_FIRST_URL'] . '?utm_source=wordpress&utm_term=en_US&utm_content=convertkit'
		);
	}

	/**
	 * Test the Broadcasts block renders when the limit parameter is blank.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithBlankLimitParameter(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Blank Limit Param');

		// Add block to Page.
		$I->addGutenbergBlock($I, 'Kit Broadcasts', 'convertkit-broadcasts');

		// When the sidebar appears, blank the limit parameter as the user might, by pressing the backspace key twice.
		$I->waitForElementVisible('.interface-interface-skeleton__sidebar[aria-label="Editor settings"]');
		$I->click('Pagination', '.interface-interface-skeleton__sidebar[aria-label="Editor settings"]');
		$I->pressKey('#convertkit_broadcasts_limit', \Facebook\WebDriver\WebDriverKeys::BACKSPACE );
		$I->pressKey('#convertkit_broadcasts_limit', \Facebook\WebDriver\WebDriverKeys::BACKSPACE );

		// Confirm that the block did not encounter an error and fail to render.
		$I->checkGutenbergBlockHasNoErrors($I, 'Kit Broadcasts');

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => 1,
			]
		);

		// Confirm that the expected Broadcast name is displayed first links to the expected URL, with UTM parameters.
		$I->assertEquals(
			$I->grabAttributeFrom('div.convertkit-broadcasts ul.convertkit-broadcasts-list li.convertkit-broadcast:first-child a', 'href'),
			'https://cheerful-architect-3237.kit.com/posts/?utm_source=wordpress&utm_term=en_US&utm_content=convertkit'
		);
	}

	/**
	 * Test the Broadcasts block's pagination works when enabled.
	 *
	 * @since   1.9.7.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithPaginationEnabled(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Pagination');

		// Add block to Page, setting the limit.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'limit'                       => [ 'input', '2', 'Pagination' ], // Click the Pagination tab first before starting to complete fields.
				'#inspector-toggle-control-4' => [ 'toggle', true ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Test pagination.
		$I->testBroadcastsPagination($I, 'Previous', 'Next');
	}

	/**
	 * Test the Broadcasts block's pagination labels work when defined.
	 *
	 * @since   1.9.7.6
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithPaginationLabelParameters(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Pagination Labels');

		// Add block to Page, setting the limit.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'limit'                       => [ 'input', '2', 'Pagination' ], // Click the Pagination tab first before starting to complete fields.
				'#inspector-toggle-control-4' => [ 'toggle', true ],
				'paginate_label_prev'         => [ 'input', 'Newer' ],
				'paginate_label_next'         => [ 'input', 'Older' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Test pagination.
		$I->testBroadcastsPagination($I, 'Older', 'Newer');
	}

	/**
	 * Test the Broadcasts block's default pagination labels display when not defined in the block.
	 *
	 * @since   2.0.3
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithBlankPaginationLabelParameters(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Add a Page using the Gutenberg editor.
		$I->addGutenbergPage($I, 'page', 'Kit: Page: Broadcasts: Blank Pagination Labels');

		// Add block to Page, setting the limit.
		$I->addGutenbergBlock(
			$I,
			'Kit Broadcasts',
			'convertkit-broadcasts',
			[
				'limit'                       => [ 'input', '2', 'Pagination' ], // Click the Pagination tab first before starting to complete fields.
				'#inspector-toggle-control-4' => [ 'toggle', true ],
				'paginate_label_prev'         => [ 'input', '' ],
				'paginate_label_next'         => [ 'input', '' ],
			]
		);

		// Publish and view the Page on the frontend site.
		$I->publishAndViewGutenbergPage($I);

		// Test pagination.
		$I->testBroadcastsPagination($I, 'Previous', 'Next');
	}

	/**
	 * Test the Broadcasts block's theme color parameters works.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithThemeColorParameters(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
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
				'post_name'    => 'convertkit-page-broadcasts-block-theme-color-params',
				'post_content' => '<!-- wp:convertkit/broadcasts {"backgroundColor":"' . $backgroundColor . '","textColor":"' . $textColor . '"} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-broadcasts-block-theme-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);

		// Confirm that our stylesheet loaded.
		$I->seeInSource('<link rel="stylesheet" id="convertkit-broadcasts-css" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-content/plugins/convertkit/resources/frontend/css/broadcasts.css');

		// Confirm that the chosen colors are applied as CSS styles.
		$I->seeInSource('<div class="convertkit-broadcasts has-text-color has-' . $textColor . '-color has-background has-' . $backgroundColor . '-background-color"');
	}

	/**
	 * Test the Broadcasts block's hex color parameters works.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockWithHexColorParameters(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
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
				'post_name'    => 'convertkit-page-broadcasts-block-hex-color-params',
				'post_content' => '<!-- wp:convertkit/broadcasts {"date_format":"m/d/Y","limit":' . $_ENV['CONVERTKIT_API_BROADCAST_COUNT'] . ',"style":{"color":{"text":"' . $textColor . '","background":"' . $backgroundColor . '"}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-broadcasts-block-hex-color-params');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the block displays correctly with the expected number of Broadcasts.
		$I->seeBroadcastsOutput(
			$I,
			[
				'number_posts' => $_ENV['CONVERTKIT_API_BROADCAST_COUNT'],
			]
		);

		// Confirm that our stylesheet loaded.
		$I->seeInSource('<link rel="stylesheet" id="convertkit-broadcasts-css" href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-content/plugins/convertkit/resources/frontend/css/broadcasts.css');

		// Confirm that the chosen colors are applied as CSS styles.
		$I->seeInSource('<div class="convertkit-broadcasts has-text-color has-background" style="color:' . $textColor . ';background-color:' . $backgroundColor . '"');
	}

	/**
	 * Test the Broadcasts block's parameters are correctly escaped on output,
	 * to prevent XSS.
	 *
	 * @since   2.0.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testBroadcastsBlockParameterEscaping(AcceptanceTester $I)
	{
		// Setup Plugin and enable debug log.
		$I->setupConvertKitPlugin($I);
		$I->setupConvertKitPluginResources($I);

		// Define a 'bad' block.  This is difficult to do in Gutenberg, but let's assume it's possible.
		$I->havePageInDatabase(
			[
				'post_name'    => 'convertkit-page-broadcasts-block-parameter-escaping',
				'post_content' => '<!-- wp:convertkit/broadcasts {"limit":2,"paginate":true,"style":{"color":{"text":"red\" onmouseover=\"alert(1)\""}}} /-->',
			]
		);

		// Load the Page on the frontend site.
		$I->amOnPage('/convertkit-page-broadcasts-block-parameter-escaping');

		// Wait for frontend web site to load.
		$I->waitForElementVisible('body.page-template-default');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm that the output is escaped.
		$I->seeInSource('style="color:red&quot; onmouseover=&quot;alert(1)&quot;"');
		$I->dontSeeInSource('style="color:red" onmouseover="alert(1)""');

		// Test pagination.
		$I->testBroadcastsPagination($I, 'Previous', 'Next');

		// Confirm that the output is still escaped.
		$I->seeInSource('style="color:red&quot; onmouseover=&quot;alert(1)&quot;"');
		$I->dontSeeInSource('style="color:red" onmouseover="alert(1)""');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.9.7.4
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
