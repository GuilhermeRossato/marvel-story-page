<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Rossato\Marvel\Fetcher;

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
}