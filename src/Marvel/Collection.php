<?php
declare(strict_types=1);

namespace Rossato\Marvel;

use Guzzle\Http\Message\Response;
use Guzzle\Exception\RequestException;

class Collection implements \Countable {
	/**
	 * The fetcher used to fetch more data if it is necessary.
	 * @var Fetcher
	 */
	private $fetcher;

	/**
	 * A boolean that indicates if the resource was fully loaded or not.
	 * @var boolean
	 */
	public $loaded;

	/**
	 * Creates an abstract collection and validates the collection data.
	 *
	 * @param array $collectionData  The associative array with the collection data.
	 * @param Fetcher $fetcher       (optional) A fetcher object to retrieve more information from the server.
	 */
	public function __construct($collectionData, $fetcher = null) {
		$this->fetcher = $fetcher;

		// Validates the minimal parameters that all resources must have.
		if (!$this->isCollection($collectionData)) {
			throw new Exception("Collection data is missing basic properties");
		}

		$this->returned = $collectionData["returned"];
		$this->total = $collectionData["available"];
		$this->collectionURI = $collectionData["collectionURI"];
	}

	/**
	 * Apply the collection data.
	 *
	 * @param array $collectionData  The collection data in an associative array.
	 */
	private function applyResourceData($collectionData) {
		$this->loaded = true;
		foreach ($collectionData as $key) {
			if (Resource::isResource($collectionData[$key])) {
				$this->{$key} = new Resource($collectionData[$key], $this->fetcher);
				continue;
			}

			$this->{$key} = $collectionData[$key];
		}
	}

	/**
	 * Uses the collectionURI to determine the type of this collection.
	 *
	 * @return string  The type of the collection in its plural form (like 'stories', 'characters' or 'comics')
	 */
	public function getCollectionType() {
		$parts = explode("/", $this->requestURI);
		$type = array_pop($parts);
		return $type;
	}

	/**
	 * Returns how many resources are inside this collection.
	 *
	 * @return integer
	 */
	public function count() {
		return $this->total;
	}

	/**
	 * Verifies if a given object is a valid collection data.
	 *
	 * @param  mixed  $object  The object to be tested.
	 * @return boolean         Trus if its an associative array with the collections required keys.
	 */
	public static function isCollection($object) {
		if (!is_array($object)) {
			return false;
		}
		if (!array_key_exists("available", $object)) {
			return false;
		}
		if (!array_key_exists("returned", $object)) {
			return false;
		}
		if (!array_key_exists("collectionURI", $object)) {
			return false;
		}
		return true;
	}
}
