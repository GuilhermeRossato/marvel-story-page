<?php
declare(strict_types=1);

namespace Rossato\Marvel;

use Guzzle\Http\Message\Response;
use Guzzle\Exception\RequestException;
use Rossato\Marvel\Model\Story;
use Rossato\Marvel\Model\Character;

class Collection implements \ArrayAccess, \Countable {
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
	 * The number of currently available resources in the collection
	 * @var boolean
	 */
	private $available;

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

		$this->available = $collectionData["returned"];
		$this->total = $collectionData["available"];
		$this->collectionURI = $collectionData["collectionURI"];
		$this->loaded = $this->total === $this->available;

		$this->applyItems($collectionData["items"]);
	}

	/**
	 * Apply the collection items.
	 *
	 * @param array $items  The items inside the collection.
	 */
	private function applyItems($items) {
		$this->items = [];

		foreach (array_filter($items) as $resourceData) {
			if ($resourceData instanceof Resource) {
				$resource = $resourceData;
			} else {
				$resource = new Resource($resourceData, $this->fetcher, false);
			}

			$type = $resource->getResourceType();

			if ($type === "stories") {
				$item = Story::createFromResource($resource, $this->fetcher, false);
			} elseif ($type === "characters") {
				$item = Character::createFromResource($resource, $this->fetcher, false);
			} else {
				$item = $resource;
			}
			array_push($this->items, $item);
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
	 * Alias for offsetGet.
	 * @param  integer $id  The offset index of the resource to be retrieved.
	 * @return mixed        An Resource object (or an extending class if one is found)
	 */
	public function __get($id) {
		return $this->offsetGet($id);
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
	 * Required as per ArrayObject implementation, but throws an error and shouldn't be executed.
	 *
	 * @throws \Exception
	 */
	public function offsetSet($offset, $value) {
		throw new Exception("The collection class does not allow values to be set manually");
	}

	/**
	 * Required as per ArrayObject implementation, but throws an error and shouldn't be executed.
	 *
	 * @throws \Exception
	 */
	public function offsetUnset($offset) {
		throw new Exception("The collection class does not allow values to be set manually");
	}

	/**
	 * Checks whenever an offset value exists, called usually by an 'isset' call or 'array_key_exists'.
	 *
	 * @param  integer $offset The index of the offset
	 * @return boolean         Whether it exists or not.
	 */
	public function offsetExists($offset) {
		if (is_string($offset) || !is_numeric($offset) || !is_integer($offset)) {
			return false;
		}
		if ($offset < 0 || $offset >= $this->total) {
			return false;
		}
		return true;
	}

	/**
	 * Retrieves an index value, fetching it from the endpoint if it does not exist yet.
	 *
	 * @param  integer $offset  The offset index of the resource to be retrieved.
	 * @return mixed            An Resource object (or an extending class if one is found)
	 */
	public function offsetGet($offset) {
		if (!$this->offsetExists($offset)) {
			return null;
		}
		if ($this->fetcher && !$this->loaded && $offset >= $this->available && $offset < $this->total) {
			$this->loaded = true;
			$this->applyItems($this->fetcher->retrieveAllResources($this->collectionURI));
		}

		return isset($this->items[$offset]) ? $this->items[$offset] : null;
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
