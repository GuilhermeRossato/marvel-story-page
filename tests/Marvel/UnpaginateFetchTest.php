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
class UnpaginateMockedClient {
	public function request($method, $url, $parameters) {
		return new UnpaginateMockedResponse($method, $url, $parameters);
	}
}

/**
 * A Response class with a partial (but relevant) PSR7 interface.
 */
class UnpaginateMockedResponse {
	public function __construct($method, $url, $params) {
		$this->method = $method;
		$this->url = $url;
		$this->params = $params;
		$this->query = array_key_exists("query", $params) ? $params["query"] : [];
	}

	public function getBody() {
		$offset = array_key_exists("offset", $this->query) ? $this->query["offset"] : 0;
		$limit = array_key_exists("limit", $this->query) ? $this->query["limit"] : 100;
		$total = 550;
		return json_encode([
			"data" => [
				"offset" => $offset,
				"limit" => $limit,
				"total" => $total,
				"count" => min($limit, $total - $offset),
				"results" => [
					[
						"id" => 1,
						"title" => "Mocked Result",
						"resourceURI" => $this->url
					]
				]
			]
		]);
	}
}

class UnpaginateFetchTest extends TestCase {
	/**
	 * A fetcher instance.
	 *
	 * @var Fetcher
	 */
	protected static $fetcher;

	/**
	 * Create a fetcher instance to be used throughout the test.
	 */
	protected function setUp(): void {
		self::$fetcher = new Fetcher("publickey", "privatekey");
		self::$fetcher->setClient(new UnpaginateMockedClient);
	}

	public function testFetchesMultipleResources() {
		Fetcher::resetRequestCount();
		$stories = self::$fetcher->getStories();

		$this->assertIsArray($stories);
		$this->assertInstanceOf(Story::class, $stories[0]);
		$this->assertEquals(6, count($stories));

		$requestCount = self::$fetcher->getRequestCount();

		$this->assertGreaterThan(1, $requestCount);
	}

	protected function tearDown(): void {
		self::$fetcher = null;
	}
}
