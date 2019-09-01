<?php
declare(strict_types=1);

namespace Rossato\Marvel;

class Fetcher {
	/**
	 * The default gateway used to request data
	 * @var string
	 */
	private $baseUrl = 'https://gateway.marvel.com/v1/public/';

	/**
	 * The public API key provided by marvel.
	 * @var string
	 */
	private $publicApiKey;

	/**
	 * The private API key provided by marvel.
	 * @var string
	 */
	private $privateApiKey;

	/**
	 * Creates the class with the public and the private API keys necessary for requests to succeed.
	 *
	 * @param string $publicApiKey  The public API key provided at marvel's website.
	 * @param string $privateApiKey The private API key provided at marvel's website.
	 */
	public function __construct(string $publicApiKey, string $privateApiKey) {
		$this->publicApiKey = $publicApiKey;
		$this->privateApiKey = $privateApiKey;
	}
}
