<?php
declare(strict_types=1);

namespace Rossato\Marvel;

use GuzzleHttp\Client;
use Rossato\Marvel\Resource;
use Rossato\Marvel\Model\Story;
use Rossato\Marvel\Model\Character;
use Rossato\Marvel\Model\Comic;
use Rossato\Marvel\Model\Creator;

class Fetcher {
	/**
	 * A request counter to debug how many requests are sent by all instances of this class to the endpoint.
	 * (Only counts current request).
	 *
	 * @var number
	 */
	private static $requestCount = 0;

	/**
	 * The default gateway used to request data
	 * @var string
	 */
	private $baseUrl = "https://gateway.marvel.com/v1/public/";

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
	 * The default pagination limit to use when not specified.
	 */
	private $limit = 100;

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
	 * Retrieves a character object from the endpoint.
	 *
	 * @param  integer $id  The identifier of the resource to retrieve.
	 * @return Character    The Character resource.
	 */
	public function getCharacter($id) {
		return $this->getResource("characters", $id, Character::class);
	}

	/**
	 * Retrieves a comic object from the endpoint.
	 *
	 * @param  integer $id  The identifier of the resource to retrieve.
	 * @return Comic        The Comic resource.
	 */
	public function getComic($id) {
		return $this->getResource("comics", $id, Comic::class);
	}

	/**
	 * Retrieves a creator object from the endpoint.
	 *
	 * @param  integer $id  The identifier of the resource to retrieve.
	 * @return Creator      The Creator resource.
	 */
	public function getCreator($id) {
		return $this->getResource("creators", $id, Creator::class);
	}

	/**
	 * Retrieves a single resource from the endpoint.
	 *
	 * @param  string $resourceType  The resource type in its plural form (e.g. 'stories').
	 * @param  integer $resourceId   The identifier of the resource to retrieve.
	 * @param  class $wrappingClass  (optional) The resource-extending model class to wrap the result with.
	 *
	 * @return mixed                 A Resource object or an extending model class as provided
	 */
	private function getResource(string $resourceType, int $resourceId, $wrappingClass = Resource::class) {
		$url = $this->combineUrl("/".$resourceType."/".$resourceId);

		$response = $this->requestURL($url, ["limit" => 1]);

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
	 * A safe url host must match the base endpoint url.
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
	 * Sets the default pagination limit to use when not specified by parameters.
	 *
	 * @param integer $limit  The desired default pagination limit.
	 */
	public function setDefaultLimit(int $limit) {
		$this->limit = $limit;
	}

	/**
	 * Resets the request count of all instances of this class.
	 */
	public static function resetRequestCount() {
		self::$requestCount = 0;
	}

	/**
	 * Retrieves how many requests instances of this class created in this request.
	 *
	 * @return integer
	 */
	public static function getRequestCount() {
		return self::$requestCount;
	}

	/**
	 * Combines an operation string with the baseUrl to create a full url to an API endpoint.
	 *
	 * @param string $operation  The relative (from the endpoint point-of-view) url to the operation.
	 * @param array $params      (optional) The associative array with the parameters to be sent.
	 *
	 * @return object  The raw json-decoded object from the response.
	 */
	public function combineUrl(string $operation) {
		$url = rtrim($this->baseUrl, "/") . "/" . ltrim($operation, "/");

		return $url;
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
			throw new \Exception("Security Error: The URL in the request is not allowed or is deemed unsafe: ".$url);
		}


		$client = isset($this->client) ? $this->client : new Client();

		if (!array_key_exists("limit", $params) && !array_key_exists("offset", $params)) {
			$params["limit"] = 1;
		}
		$params["ts"] = time();
		$params["apikey"] = $this->publicApiKey;
		$params["hash"] = md5(time() . $this->privateApiKey . $this->publicApiKey);

		self::$requestCount += 1;
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
		$url = $this->combineUrl("/stories");

		$response = $this->requestURL($url, ["limit" => 1]);

		return array_key_exists("attributionText", $response) ? $reponse["attributionText"] : "";
	}
}
