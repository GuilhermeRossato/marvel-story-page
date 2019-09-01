<?php
declare(strict_types=1);

namespace Rossato\Marvel\Model;

use Guzzle\Http\Message\Response;
use Rossato\Marvel\Resource;

class Character extends Resource {
	public $id;
	public $name;
	public $description;
	public $modified;
	public $thumbnail;
	public $resourceURI;
	public $comics;
	public $series;
	public $stories;
	public $events;
	public $urls;

	public static function createFromResource($resource, $fetcher = null, bool $isLoaded = false) {
		$resourceData = is_array($resource) ? $resource : get_class_vars($resource);

		return new self($resourceData, $fetcher, $isLoaded);
	}
}
