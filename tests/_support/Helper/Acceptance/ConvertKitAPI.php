<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.9.6
 */
class ConvertKitAPI extends \Codeception\Module
{
	/**
	 * Returns an encoded `state` parameter compatible with OAuth.
	 *
	 * @since   2.5.0
	 *
	 * @param   string $returnTo   Return URL.
	 * @param   string $clientID   OAuth Client ID.
	 * @return  string
	 */
	public function apiEncodeState($returnTo, $clientID)
	{
		$str = json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			array(
				'return_to' => $returnTo,
				'client_id' => $clientID,
			)
		);

		// Encode to Base64 string.
		$str = base64_encode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$str = strtr( $str, '+/', '-_' );

		// Remove padding character from the end of line.
		$str = rtrim( $str, '=' );

		return $str;
	}

	/**
	 * Check the given email address exists as a subscriber.
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,

				// Check all subscriber states.
				'status'              => 'all',
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['pagination']['total_count']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// Return subscriber ID.
		return $results['subscribers'][0]['id'];
	}

	/**
	 * Check the given subscriber ID has been assigned to the given form ID.
	 *
	 * @since   2.7.1
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $formID        Form ID.
	 * @param   string           $referrer      Referrer.
	 */
	public function apiCheckSubscriberHasForm($I, $subscriberID, $formID, $referrer = false)
	{
		// Run request.
		$results = $this->apiRequest(
			'forms/' . $formID . '/subscribers',
			'GET',
			[
				// Check all subscriber states.
				'status' => 'all',
			]
		);

		// Iterate through subscribers.
		$subscriberHasForm = false;
		foreach ($results['subscribers'] as $subscriber) {
			if ($subscriber['id'] === $subscriberID) {
				$subscriberHasForm = true;
				break;
			}
		}

		// Assert if the subscriber has the form.
		$this->assertTrue($subscriberHasForm);

		// If a referrer is specified, assert it matches the subscriber's referrer now.
		if ($referrer) {
			$I->assertEquals($subscriber['referrer'], $referrer);
		}
	}

	/**
	 * Check the given subscriber ID has been assigned to the given sequence ID.
	 *
	 * @since   2.5.2
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $sequenceID         Sequence ID.
	 */
	public function apiCheckSubscriberHasSequence($I, $subscriberID, $sequenceID)
	{
		// Run request.
		$results = $this->apiRequest(
			'sequences/' . $sequenceID . '/subscribers',
			'GET'
		);

		// Iterate through subscribers.
		$subscriberHasSequence = false;
		foreach ($results['subscribers'] as $subscriber) {
			if ($subscriber['id'] === $subscriberID) {
				$subscriberHasSequence = true;
				break;
			}
		}

		// Assert if the subscriber has the sequence.
		$this->assertTrue($subscriberHasSequence);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm the tag has been assigned to the subscriber.
		$I->assertEquals($tagID, $results['tags'][0]['id']);
	}

	/**
	 * Check the given subscriber ID has no tags assigned.
	 *
	 * @since   2.4.9.1
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm no tags have been assigned to the subscriber.
		$I->assertCount(0, $results['tags']);
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['pagination']['total_count']);
	}

	/**
	 * Subscribes the given email address. Useful for
	 * creating a subscriber to use in tests.
	 *
	 * @param   string $emailAddress   Email Address.
	 * @return  int                    Subscriber ID
	 */
	public function apiSubscribe($emailAddress)
	{
		// Run request.
		$result = $this->apiRequest(
			'subscribers',
			'POST',
			[
				'email_address' => $emailAddress,
			]
		);

		// Return subscriber ID.
		return $result['subscriber']['id'];
	}

	/**
	 * Unsubscribes the given email address. Useful for clearing the API
	 * between tests.
	 *
	 * @param   string $emailAddress   Email Address.
	 */
	public function apiUnsubscribe($emailAddress)
	{
		// Run request.
		$this->apiRequest(
			'unsubscribe',
			'PUT',
			[
				'email' => $emailAddress,
			]
		);
	}

	/**
	 * Fetches the given broadcast from ConvertKit.
	 *
	 * @since   2.4.0
	 *
	 * @param   int $broadcastID    Broadcast ID.
	 */
	public function apiGetBroadcast($broadcastID)
	{
		// Run request.
		return $this->apiRequest(
			'broadcasts/' . $broadcastID,
			'GET'
		);
	}

	/**
	 * Deletes the given broadcast from ConvertKit.
	 *
	 * @since   2.4.0
	 *
	 * @param   int $broadcastID    Broadcast ID.
	 */
	public function apiDeleteBroadcast($broadcastID)
	{
		// Run request.
		$this->apiRequest(
			'broadcasts/' . $broadcastID,
			'DELETE'
		);
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Send request.
		$client = new \GuzzleHttp\Client();
		switch ($method) {
			case 'GET':
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint . '?' . http_build_query($params),
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
					]
				);
				break;

			default:
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint,
					[
						'headers' => [
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json; charset=utf-8',
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}
}
