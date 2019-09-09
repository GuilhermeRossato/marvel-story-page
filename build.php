<?php

require_once __DIR__."/vendor/autoload.php";

use \Rossato\App;

use \Rossato\Marvel\Fetcher;
use \Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();
$dotenv->required([
	"MARVEL_PUBLIC_KEY",
	"MARVEL_PRIVATE_KEY",
	"MARVEL_COMIC_ID",
	"MARVEL_FAVORITE_CHARACTER_ID"
])->notEmpty();

$publicKey = getenv("MARVEL_PUBLIC_KEY");
$privateKey = getenv("MARVEL_PRIVATE_KEY");

$app = new App();

$app->init($publicKey, $privateKey);

$app->setComicId(intval(getenv("MARVEL_COMIC_ID")));
$app->setCharacterId(getenv("MARVEL_FAVORITE_CHARACTER_ID") ? intval(getenv("MARVEL_FAVORITE_CHARACTER_ID")) : null);

$app->build(__DIR__.DIRECTORY_SEPARATOR."assets", __DIR__.DIRECTORY_SEPARATOR."build");
