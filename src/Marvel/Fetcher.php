<?php
declare(strict_types=1);

namespace Rossato\Marvel;

use GuzzleHttp\Client;
use Rossato\Marvel\Resource;
use Rossato\Marvel\Model\Story;

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

	/**
	 * Retrieves a story object from the endpoint.
	 *
	 * @param  integer $id  The identifier of the resource to retrieve.
	 * @return Story        The Story resource.
	 */
	public function getStory($id) {
		return $this->getResource("stories", $id, Story::class);
	}

	/**
	 * Retrieves a single resource from the endpoint.
	 *
	 * @param  string $resourceType  The resource type in its plural form (e.g. 'stories').
	 * @param  integer $resourceId   The identifier of the resource to retrieve.
	 * @param  class $wrappingClass  The resource-extending model class to wrap the result with.
	 *
	 * @return mixed                 A Resource object or an extending model class as provided
	 */
	private function getResource(string $resourceType, int $resourceId, $wrappingClass = Resource::class) {
		$response = $this->requestOperation("/".$resourceType, ["limit" => 1]);

		if (!is_array($response) ||
			!array_key_exists("data", $response) ||
			!array_key_exists("results", $response["data"]) ||
			count($response["data"]["results"]) <= 0 ||
			!array_key_exists(0, $response["data"]["results"])
		) {
			return null;
		}

		$result = $response["data"]["results"][0];

		if ($wrappingClass === Resource::class) {
			return new Resource($result, $this, true);
		}

		return $wrappingClass::createFromResource($result, $this, true);
	}

	/**
	 * Check if a given url is safe to send the API credentials.
	 * A safe url should be sent by 'https' (SSL) protocol and its host must match the base endpoint url.
	 *
	 * @param  string  $url  The full url that the request will be sent to.
	 *
	 * @return boolean       Whether the url can or cannot be trusted with the internal credentials.
	 */
	private function isSafeUrl($url) {
		$parts = parse_url($url);

		if ($parts["scheme"] !== "https") {
			return false;
		}

		$safeParts = parse_url($this->baseUrl);
		if ($parts["host"] !== $safeParts["host"]) {
			return false;
		}

		return true;
	}

	/**
	 * Sends a request to an operation to the API endpoint.
	 *
	 * @param string $operation  The relative (from the endpoint point-of-view) url to the operation.
	 * @param array $params      (optional) The associative array with the parameters to be sent.
	 *
	 * @return object  The raw json-decoded object from the response.
	 */
	public function requestOperation(string $operation, $params = []) {
		$url = rtrim($this->baseUrl, "/") . "/" . ltrim($operation, "/");

		return $this->requestURL($url, $params);
	}

	/**
	 * Replaces the current client (default Guzzle) with another PSR7 http client.
	 *
	 * @param mixed $client  The PSR7-compliant http client with methods such as 'request'.
	 */
	public function setClient($client) {
		$this->client = $client;
	}

	/**
	 * Sends a request with a full url to the API endpoint.
	 *
	 * @param  string $url            The absolute url (full) to the desired operation.
	 * @param  array  $params         (optional) The parameters in an associative array.
	 * @throws \Exception       If the url is not safe (host differs from endpoint's or not a secure protocol)
	 *
	 * @return object  The raw json-decoded object from the response.
	 */
	public function requestURL(string $url, array $params = []) {
		if (!$this->isSafeUrl($url)) {
			throw new \Exception("Security Error: The URL in the request is not allowed or is deemed unsafe.");
		}


		$client = isset($this->client) ? $this->client : new Client();

		if (!array_key_exists("limit", $params) && !array_key_exists("offset", $params)) {
			$params['limit'] = 1;
		}
		$params['ts'] = time();
		$params['apikey'] = $this->publicApiKey;
		$params['hash'] = md5(time() . $this->privateApiKey . $this->publicApiKey);

		$response = $client->request("GET", $url, ["query" => $params]);

		$json = json_decode((string) $response->getBody(), true);

		return $json;
	}

	/**
	 * Retrieves the attribution text from a request to the Marvel API.
	 *
	 * @param  boolean $html  (optional) Whether the result can be in html format or not. (default false)
	 * @return string         The attribution text in a string or an empty string if it could not be retrieved.
	 */
	public function getAttributionText($html = false) {
		$response = $this->requestOperation("/stories", ["limit" => 1]);
		return array_key_exists("attributionText", $response) ? $reponse["attributionText"] : "";
	}
}
