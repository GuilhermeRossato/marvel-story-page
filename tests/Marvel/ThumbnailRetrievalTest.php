<?php
declare(strict_types=1);

namespace Tests\Marvel;

use PHPUnit\Framework\TestCase;

use Rossato\Marvel\Resource;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses


class ThumbnailRetrievalTest extends TestCase {
	public function testResourceThumbnailRetrieval() {
		$resource = new Resource([
			"resourceURI" => ".",
			"name" => ".",
			"thumbnail" => [
				"url" => "http://hello.com",
				"extension" => "gif"
			],
		]);

		$thumbnailUrl = $resource->getThumbnailUrl();

		$this->assertEquals("http://hello.com/detail.gif", $thumbnailUrl);
	}

	public function testThumbnailCleanRetrieval() {
		$resource = new Resource([
			"resourceURI" => ".",
			"name" => ".",
			"thumbnail" => [
				"url" => "http://hello.com/",
				"extension" => ".gif"
			],
		]);

		$thumbnailUrl = $resource->getThumbnailUrl();

		$this->assertEquals("http://hello.com/detail.gif", $thumbnailUrl);
	}

	public function testThumbnailNull() {
		$resource = new Resource([
			"resourceURI" => ".",
			"name" => ".",
			"thumbnail" => null,
		]);

		$thumbnailUrl = $resource->getThumbnailUrl("standard_small");

		$this->assertEquals("http://i.annihil.us/u/prod/marvel/i/mg/b/40/image_not_available/standard_small.jpg", $thumbnailUrl);
	}

	public function testThumbnailTypeInvalid() {
		$this->expectException(\InvalidArgumentException::class);

		$resource = new Resource([
			"resourceURI" => ".",
			"name" => ".",
			"thumbnail" => null,
		]);

		$thumbnailUrl = $resource->getThumbnailUrl("something_invalid");
	}
}
