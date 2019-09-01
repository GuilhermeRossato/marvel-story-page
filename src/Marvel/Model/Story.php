<?php
declare(strict_types=1);

namespace Rossato\Marvel\Model;

use Guzzle\Http\Message\Response;
use Rossato\Marvel\Resource;

class Story extends Resource {
	public $id;
	public $title;
	public $description;
	public $resourceURI;
	public $type;
	public $modified;
	public $thumbnail;
	public $creators;
	public $characters;
	public $series;
	public $comics;
	public $events;
	public $originalIssue;

	public static function createFromResource($resource, $fetcher = null, bool $isLoaded = false) {
		$resourceData = is_array($resource) ? $resource : get_class_vars($resource);

		return new self($resourceData, $fetcher, $isLoaded);
	}
}
