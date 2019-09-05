<?php
declare(strict_types=1);

namespace Rossato\Marvel\Model;

use Guzzle\Http\Message\Response;
use Rossato\Marvel\Resource;

class Character extends Resource {

	/**
	 * The list of properties this model is expected to have once fully loaded.
	 *
	 * @const array
	 */
	const PROPERTIES = [
		"id",
		"name",
		"description",
		"modified",
		"thumbnail",
		"resourceURI",
		"comics",
		"series",
		"stories",
		"events",
		"urls",
	];

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
