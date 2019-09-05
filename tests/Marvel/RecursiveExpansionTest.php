<?php
declare(strict_types=1);

namespace Tests\Marvel;

use PHPUnit\Framework\TestCase;

use Rossato\Marvel\Fetcher;
use Rossato\Marvel\Resource;
use Rossato\Marvel\Collection;
use Rossato\Marvel\Model\Story;
use Rossato\Marvel\Model\Character;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * A Client mocked with a partial (but relevant) PSR7 interface.
 */
class REMockedClient {
	public function request($method, $url, $parameters) {
		return new REMockedResponse($method, $url, $parameters);
	}
}

/**
 * A Response class with a partial (but relevant) PSR7 interface.
 */
class REMockedResponse {
	public function __construct($method, $url, $params) {
		$this->method = $method;
		$this->url = $url;
		$this->params = $params;
		$this->query = array_key_exists("query", $params) ? $params["query"] : [];
	}

	public function getBody() {
		$url = $this->url;
		$offset = array_key_exists("offset", $this->query) ? $this->query["offset"] : 0;
		$limit = array_key_exists("limit", $this->query) ? $this->query["limit"] : -1;

		if ($limit !== 1) {
			throw new \Exception(
				"Expected default limit to have been used, got ".(($limit === -1) ? "unset variable" : $limit)
			);
		}

		if (endsWith($url, "/characters") || endsWith($url, "/characters/1") || endsWith($url, "/characters/2")) {
			$id = $offset + 1;

			return json_encode([
				"data" => [
					"offset" => $offset,
					"limit" => 1,
					"total" => 2,
					"count" => 1,
					"results" => [
						[
							"resourceURI" => "http://gateway.marvel.com/v1/public/characters/".$id,
							"name" => $offset === 0 ? "First Character" : "Second Character",
							"id" => $id,
							"events" => [
								"available" => 1,
								"collectionURI" => "http://gateway.marvel.com/v1/public/characters/".$id."/events",
								"items" => [
									[
										"loaded" => false,
										"resourceURI" => "http://gateway.marvel.com/v1/public/events/1",
										"name" => "Mocked Event"
									]
								],
								"returned" => 1
							]
						]
					]
				]
			]);
		}

		if (endsWith($url, "/events/1")) {
			$id = 1;

			return json_encode([
				"data" => [
					"offset" => $offset,
					"limit" => 1,
					"total" => 1,
					"count" => 1,
					"results" => [
						[
							"resourceURI" => "http://gateway.marvel.com/v1/public/events/".$id,
							"name" => "Mocked Event",
							"id" => $id,
							"characters" => [
								"available" => 2,
								"collectionURI" => "http://gateway.marvel.com/v1/public/events/".$id."/characters",
								"items" => [
									[
										"loaded" => false,
										"resourceURI" => "http://gateway.marvel.com/v1/public/events/1",
										"name" => "First Character"
									]
								],
								"returned" => 1
							]
						]
					]
				]
			]);
		}

		throw new \Exception("Unknown url: ".$url);
	}
}

/**
 * Test whether unknown resources are just Resource objects
 */
class UnknownResourceExpansionTest extends TestCase {
	/**
	 * A fetcher instance to be used to retrieve models.
	 *
	 * @var Fetcher
	 */
	protected static $fetcher;

	/**
	 * A character instance to be used to retrieve its foo objects.
	 *
	 * @var Character
	 */
	protected static $character;

	/**
	 * An abstract instance to be used to retrieve its character objects.
	 *
	 * @var Resource
	 */
	protected static $event;

	/**
	 * Create a fetcher instance to be used throughout the test.
	 */
	protected function setUp(): void {
		self::$fetcher = new Fetcher("publickey", "privatekey");
		self::$fetcher->setClient(new REMockedClient);
		self::$fetcher->setDefaultLimit(1);
	}

	public function testCharacterRetrievalFetchExecutesMultipleTimes() {
		Fetcher::resetRequestCount();

		$characters = self::$fetcher->getCharacters();

		$this->assertGreaterThan(1, self::$fetcher->getRequestCount());
		$this->assertEquals("First Character", $characters[0]->name);
		$this->assertEquals("Second Character", $characters[1]->title);

		self::$character = $characters[0];
	}

	public function testEventsShouldLoadAsCollection() {
		$events = self::$character->events;

		$this->assertEquals(1, count($events));

		$this->assertInstanceOf(Collection::class, $events);

		self::$event = $events[0];
	}

	public function testCharactersUnknownCollectionCanBeLoadedAsAbstractResource() {
		$this->assertInstanceOf(Resource::class, self::$event);
	}

	public function testCanLoadCharactersFromCollection() {
		$characters = self::$event->characters;

		$this->assertInstanceOf(Collection::class, $characters);
		$this->assertInstanceOf(Character::class, $characters[1]);

		self::$character = $characters[1];
	}

	public function testCanRecursivelyFetchEventsAgain() {
		$events = self::$character->events;

		$this->assertEquals(1, count($events));
		$this->assertInstanceOf(Collection::class, $events);

		self::$event = $events[0];
	}

	public function testCanRecursivelyFetchCharacterAgain() {
		$characters = self::$event->characters;

		$this->assertEquals(2, count($characters));
		$this->assertInstanceOf(Collection::class, $characters);
	}
}
