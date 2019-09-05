<?php
declare(strict_types=1);

namespace Tests\Marvel;

use PHPUnit\Framework\TestCase;

use Rossato\Marvel\Fetcher;
use Rossato\Marvel\Model\Story;
use Rossato\Marvel\Model\Character;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * A Client mocked with a partial (but relevant) PSR7 interface.
 */
class ExpandCollectionMockedClient {
	public function request($method, $url, $parameters) {
		return new ExpandCollectionMockedResponse($method, $url, $parameters);
	}
}

/**
 * A Response class with a partial (but relevant) PSR7 interface.
 */
class ExpandCollectionMockedResponse {
	public function __construct($method, $url, $params) {
		$this->method = $method;
		$this->url = $url;
		$this->params = $params;
		$this->query = array_key_exists("query", $params) ? $params["query"] : [];
	}

	public function getBody() {
		$url = $this->url;
		$limit = array_key_exists("limit", $this->query) ? $this->query["limit"] : 100;

		// Reply Story Fetch
		if (endsWith($url, "/stories/1")) {
			return json_encode([
				"data" => [
					"offset" => 0,
					"limit" => $limit,
					"total" => 1,
					"count" => 1,
					"results" => [
						[
							"id" => 1,
							"title" => "Mocked Result",
							"resourceURI" => $url,
							"characters" => [
								"available" => 2,
								"collectionURI" => "https://gateway.marvel.com/v1/public/stories/1/characters",
								"items" => [
									[
										"resourceURI" => "https://gateway.marvel.com/v1/public/characters/1",
										"name" => "Mocked Character"
									]
								],
								"returned" => 1
							]
						]
					]
				]
			]);
		}

		// Reply Character Fetch
		if (endsWith($url, "/characters/1")) {
			return json_encode([
				"data" => [
					"offset" => 0,
					"limit" => $limit,
					"total" => 2,
					"count" => 2,
					"results" => [
						[
							"id" => 19815,
							"title" => "Mocked Character",
							"description" => "Mocked description",
							"resourceURI" => $url,
							"stories" => [
							]
						]
					]
				]
			]);
		}

		throw new \Exception("Unknown url: ".$url);
	}
}

class ExpandCollectionTest extends TestCase {
	/**
	 * A fetcher instance to be used to retrieve models.
	 *
	 * @var Fetcher
	 */
	protected static $fetcher;

	/**
	 * Create a fetcher instance to be used in the tests
	 */
	protected function setUp(): void {
		self::$fetcher = new Fetcher("publickey", "privatekey");
		self::$fetcher->setClient(new ExpandCollectionMockedClient);
	}

	/**
	 * A story instance to be used in the next test.
	 * @var Story
	 */
	protected static $story;

	public function testFetchesTheStory() {
		Fetcher::resetRequestCount();

		$story = self::$fetcher->getStory(1);

		$this->assertInstanceOf(Story::class, $story);

		$requestCount = self::$fetcher->getRequestCount();

		$this->assertEquals(1, $requestCount);

		self::$story = $story;
	}

	public function testFetchesTheStoriesCharacters() {
		Fetcher::resetRequestCount();

		$story = self::$story;

		$this->assertInstanceOf(Story::class, $story);

		$characters = self::$story->characters;

		$this->assertInstanceOf(Character::class, $characters[0]);

		$requestCount = self::$fetcher->getRequestCount();

		// Made no requests, since character data was included on stories response
		$this->assertEquals(0, $requestCount);
	}

	public function testCountCoincidesWithResponseTotal() {
		$characterCount = count(self::$story->characters);

		$this->assertEquals(2, $characterCount);
	}

	public function testDinamicallyLoadsSubResourcesProperties() {
		Fetcher::resetRequestCount();

		$character = self::$story->characters[0];

		$this->assertInstanceOf(Character::class, $character);

		$this->assertEquals("Mocked description", $character->description);

		$requestCount = self::$fetcher->getRequestCount();

		$this->assertEquals(1, $requestCount);
	}

	protected function tearDown(): void {
		self::$fetcher = null;
	}
}
