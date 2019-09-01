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
class MockedClient {
	public function request($method, $url, $parameters) {
		return new MockedResponse($method, $url, $parameters);
	}
}

/**
 * A Response class with a partial (but relevant) PSR7 interface.
 */
class MockedResponse {
	public function __construct($method, $url, $parameters) {
		$this->url = $url;
	}

	public function getBody() {
		return json_encode([
			"data" => [
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

class MarvelFetcherTest extends TestCase {
	public function testCreatesWithoutErrors() {
		$fetcher = new Fetcher("publickey", "privatekey");

		$this->assertInstanceOf(Fetcher::class, $fetcher);
	}

	public function testWontAllowMissingParameters() {
		$this->expectException(\ArgumentCountError::class);

		new Fetcher();
	}

	public function testWontAllowIncorrectParameters() {
		$this->expectException(\TypeError::class);

		new Fetcher(1, 2);
	}

	public function testFetchesAStory() {
		$fetcher = new Fetcher("publickey", "privatekey");
		$fetcher->setClient(new MockedClient);

		$storyId = 1;
		$story = $fetcher->getStory(intval($storyId));

		$this->assertInstanceOf(Story::class, $story);
		$this->assertEquals($story->name, "Mocked Result");
	}

	public function testFetchesACharacter() {
		$fetcher = new Fetcher("publickey", "privatekey");
		$fetcher->setClient(new MockedClient);

		$characterId = 2;
		$character = $fetcher->getCharacter(intval($characterId));

		$this->assertInstanceOf(Character::class, $character);
		$this->assertEquals($character->title, "Mocked Result");
	}
}
