<?php

require_once __DIR__."/vendor/autoload.php";

use Dotenv\Dotenv;
use GuzzleHttp\Client;

Dotenv::create(__DIR__)->load();

$publicKey = getenv("MARVEL_PUBLIC_KEY");
$privateKey = getenv("MARVEL_PRIVATE_KEY");

$endpoint = "https://gateway.marvel.com/v1/public/";

$client = new Client();

$timestamp = time();
$hash = md5($timestamp . $privateKey . $publicKey);

$params = [
	'ts' => $timestamp,
	'apikey' => $publicKey,
	'hash' => $hash,
	'limit' => 3
];

$url = $endpoint . "stories";

$response = $client->request("GET", $url, ["query" => $params]);

$result = json_decode((string) $response->getBody(), true);

echo json_encode($result, JSON_PRETTY_PRINT);
