<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use Dotenv\Dotenv;
use Dotenv\Validator;

/**
 * This test will be ignored on development enviroment.
 * If you wish to run it set either the IS_PRODUCTION or RUN_PRODUCTION_TESTS environment variable to "1".
 */
class EnvVariablesTest extends TestCase {
	/**
	 * A dotenv instance.
	 *
	 * @var Dotenv
	 */
	protected static $dotenv;

	/**
	 * Skip this test if we're not running in production or forcing all tests to be run.
	 */
	protected function setUp(): void {
		if (!getenv("IS_PRODUCTION") && !getenv("RUN_PRODUCTION_TESTS")) {
			$this->markTestSkipped("Enviroment test is not required in development / local mode.");
		}
	}

	/**
	 * Initializes the tests with a dotenv instance to merge .env file and environment variables together
	 */
	public static function setUpBeforeClass(): void {
		$rootPath = rtrim(__DIR__, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR;
		self::$dotenv = Dotenv::create($rootPath);
		self::$dotenv->load();
	}

	public function testDefinedMarvelKeys() {
		$validation = self::$dotenv->required([
			"MARVEL_PUBLIC_KEY",
			"MARVEL_PRIVATE_KEY",
			"MARVEL_CHARACTER_ID"
		])->notEmpty();

		$this->assertInstanceOf(Validator::class, $validation);
	}

	public function testMarvelPublicKeyIsString() {
		$publicKey = getenv("MARVEL_PUBLIC_KEY");

		$this->assertIsString($publicKey);
	}

	public function testMarvelPrivateKeyIsString() {
		$privateKey = getenv("MARVEL_PRIVATE_KEY");

		$this->assertIsString($privateKey);
	}

	public function testMarvelPrivateKeyIsLargerString() {
		$publicKey = getenv("MARVEL_PUBLIC_KEY");
		$privateKey = getenv("MARVEL_PRIVATE_KEY");

		$expected = strlen($publicKey);
		$actual = strlen($privateKey);

		$this->assertGreaterThan($expected, $actual);
	}
}
