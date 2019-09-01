<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Rossato\Marvel\Fetcher;
use Rossato\Marvel\Model\Story;

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
	public function getBody() {
		return json_encode([
			"data" => [
				"results" => [
					[
						"id" => 1,
						"title" => "Mocked Story",
						"resourceURI" => "http://gateway.marvel.com/v1/public/stories/1"
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
		$this->expectException(ArgumentCountError::class);

		new Fetcher();
	}

	public function testWontAllowIncorrectParameters() {
		$this->expectException(TypeError::class);

		new Fetcher(1, 2);
	}

	public function testFetchesAStory() {
		$fetcher = new Fetcher("publickey", "privatekey");
		$fetcher->setClient(new MockedClient);

		$storyId = getenv("MARVEL_STORY_ID");
		$story = $fetcher->getStory($storyId);

		$this->assertInstanceOf(Story::class, $story);
		$this->assertEquals($story->name, "Mocked Story");
	}
}