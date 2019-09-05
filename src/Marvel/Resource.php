<?php
declare(strict_types=1);

namespace Rossato\Marvel;

use Guzzle\Http\Message\Response;

/**
 * A resource is an abstract representation of a data that is sent by the MarvelAPI.
 */
class Resource {
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
	 * Construct the abstract resource and validates the resource data.
	 *
	 * @param array $resourceData  The associative array with the resource data.
	 * @param Fetcher $fetcher     (optional) A fetcher to fetch data when a parameter is found to be missing.
	 * @param bool $isLoaded       (optional) Whether the resource is fully loaded or not.
	 */
	public function __construct($resourceData, $fetcher = null, $isLoaded = false) {
		$this->fetcher = $fetcher;
		$this->loaded = $isLoaded;

		// Validates the minimal parameters that all resources must have.
		if (!$this->isResource($resourceData)) {
			if (!is_array($resourceData)) {
				throw new \Exception("Resource data should be an array");
			} elseif (!array_key_exists("resourceURI", $resourceData)) {
				throw new \Exception("Resource data is missing resourceURI");
			} elseif (!array_key_exists("name", $resourceData) && !array_key_exists("title", $resourceData)) {
				throw new \Exception("Resource data is missing its 'name' key or 'title' key");
			} else {
				throw new \Exception("Resource data is missing basic properties");
			}
		}

		$this->applyResourceData($resourceData);
	}

	/**
	 * Apply a resource data, setting the loaded variable to true if all resource keys are present, false otherwise.
	 */
	private function applyResourceData($resourceData) {
		foreach ($resourceData as $key => $value) {
			if (Collection::isCollection($value)) {
				$this->{$key} = new Collection($value, $this->fetcher);
				continue;
			}

			$this->{$key} = $value;
		}
	}

	/**
	 * Uses the resourceURI to determine the type of this resource.
	 *
	 * @return string  The type of the resource in its plural form (like 'stories', 'characters' or 'comics')
	 */
	public function getResourceType() {
		if (!is_string($this->resourceURI)) {
			throw new \Exception("The request URI is not a valid string.");
		}

		$parts = explode("/", $this->resourceURI);
		$id = array_pop($parts);
		$type = array_pop($parts);
		return $type;
	}

	public function loadFromResourceURI() {
		if (!$this->fetcher) {
			throw new \Exception("The fetcher class is missing and there is no way to retrieve data from the endpoint");
		}

		$this->loaded = true;

		$response = $this->fetcher->requestURL($this->resourceURI);
		if (!is_array($response) ||
			!array_key_exists("data", $response) ||
			!array_key_exists("results", $response["data"]) ||
			count($response["data"]["results"]) <= 0
		) {
			return null;
		}

		return $this->applyResourceData($response["data"]["results"][0]);
	}

	/**
	 * Retrieves a loaded property or sends a requests to retrieve it from the endpoint.
	 *
	 * @param  string $propertyName  The property being requested.
	 *
	 * @return mixed                 The property value.
	 */
	public function __get($propertyName) {
		// Name and title are interchangeable in the API depending on the way the resource was loaded.
		if ($propertyName === "title") {
			return isset($this->title) ? $this->title : $this->name;
		}
		if ($propertyName === "name") {
			return isset($this->name) ? $this->name : $this->title;
		}

		// Anything else is returned as is.
		if (isset($this->{$propertyName})) {
			return $this->{$propertyName};
		}

		if ($this->loaded) {
			return null;
		}

		// Attempt to load the property from the endpoint.
		$this->loadFromResourceURI();

		if (isset($this->{$propertyName})) {
			return $this->{$propertyName};
		}

		return null;
	}

	/**
	 * Verifies if a given object is a valid resource data.
	 *
	 * @param  mixed  $object  The object to be tested.
	 * @return boolean         Whether the object is an associative array with the keys required to be a resources.
	 */
	public static function isResource($object) {
		if (!is_array($object)) {
			return false;
		}

		if (!array_key_exists("resourceURI", $object) && is_string($object["resourceURI"])) {
			return false;
		}

		if (!array_key_exists("name", $object) && !array_key_exists("title", $object)) {
			return false;
		}
		return true;
	}

	/**
	 * Generates an instanceof of self from another Resource class (or extended class).
	 * Copies properties similar to a Class clone.
	 *
	 * @param mixed  $seed  The resource object (or an resource data associative array) to seed the new class.
	 * @param Fetcher $fetcher  (optional) A fetcher to fetch data when a parameter is found to be missing.
	 * @param bool $isLoaded    (optional) Whether the resource is fully loaded or not.
	 * @return Resource         The resulting class with the public properties copied from the seed.
	 */
	public static function createFromResource($seed, $fetcher = null, bool $isLoaded = false) {
		$resourceData = is_array($seed) ? $seed : get_object_vars($seed);

		return new self($resourceData, $fetcher, $isLoaded);
	}
}
