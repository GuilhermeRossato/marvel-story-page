<?php

use PHPUnit\Framework\TestCase;

use Dotenv\Dotenv;
use Dotenv\Validator;

class EnvVariablesTest extends TestCase {
    /**
     * A dotenv instance.
     *
     * @var Dotenv
     */
    protected static $dotenv;

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
            'MARVEL_PUBLIC_KEY',
            'MARVEL_PRIVATE_KEY',
            'MARVEL_CHARACTER_ID',
            'MARVEL_STORY_ID'
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