<?php
declare(strict_types=1);

namespace Rossato;

use \Rossato\Marvel\Fetcher;
use \Dotenv\Dotenv;
use \Twig\Loader\FilesystemLoader;
use \Twig\Environment;
use \Rossato\Marvel\Resource;

class App {
	/**
	 * The internal fetcher instance that reads data from the endpoint.
	 *
	 * @var Fetcher
	 */
	private $fetcher;

	/**
	 * A twig instance to use to render the html.
	 *
	 * @var Environment
	 */
	private $twig;

	/**
	 * The comic id to be used for the page creation.
	 * @var null|integer
	 */
	private $comicId = null;

	/**
	 * The character id to be used for the page creation.
	 * @var null|integer
	 */
	public $characterId = null;

	/**
	 * Initialize the variables required to run the app.
	 */
	public function init(string $publicKey, string $privateKey) {
		$this->fetcher = new Fetcher($publicKey, $privateKey);

		$loader = new FilesystemLoader(__DIR__."/../views");

		$twig = new Environment($loader);

		$this->twig = $twig;
	}

	/**
	 * Sets the comic id to be used for the page creation.
	 * If null is passed, a random one will be used from the character;
	 *
	 * @param null|int $id  (optional) The id of the comic.
	 */
	public function setComicId($id = null) {
		if (is_integer($id)) {
			$this->comicId = $id;
		} elseif ($id === null) {
			$this->comicId = null;
		} else {
			throw new \InvalidArgumentException("The comic identifier must be an integer or null");
		}
	}

	/**
	 * Sets the id of the character to be used for the page creation.
	 *
	 * @param integer $id  The id of the character.
	 */
	public function setCharacterId(int $id) {
		$this->characterId = $id;
	}

	/**
	 * Creates a build dir if it doesn't exist or throws an exception if it couldn't create it.
	 *
	 * @param  string $buildPath  The path to the folder to save the built page.
	 */
	public function makeBuildDir($buildPath) {
		if (!file_exists($buildPath)) {
			mkdir($buildPath);
		}
		if (!file_exists($buildPath)) {
			throw new \Exception("Could not create build directory at \"".$buildPath."\"");
		}
	}

	/**
	 * Copy all files recursively from an asset path to a target directory.
	 *
	 * @param  string $assetPath  The asset folder path.
	 * @param  string $targetDir  The build folder path
	 */
	public function copyAssets(string $assetPath, string $targetDir) {
		if (!file_exists($assetPath)) {
			return;
		}
		$dir = opendir($assetPath);
		@mkdir($targetDir);
		$ds = DIRECTORY_SEPARATOR;

		while (false !== ( $file = readdir($dir))) {
			if (($file === ".") || ($file === "..")) {
				continue;
			}
			if (is_dir($assetPath . $ds . $file)) {
				$this->copyAssets($assetPath . $ds . $file, $targetDir . $ds . $file);
				continue;
			}

			copy($assetPath . $ds . $file, $targetDir . $ds . $file);
		}

		closedir($dir);
	}

	/**
	 * Saves a string to a file, overwriting the old file if it exists.
	 *
	 * @param  string $indexPath  The path to the file to be saved.
	 * @param  string $content    The raw content of the file to be saved.
	 * @return integer            The amount of bytes written to the file.
	 */
	public function saveToFile(string $path, string $content) {
		return file_put_contents($path, $content);
	}

	/**
	 * Removes a directory recursively.
	 *
	 * @param  string $dir The directory to start removing files.
	 */
	public function removeDirectory($dir) {
		$ds = DIRECTORY_SEPARATOR;
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $file) {
				if (($file === ".") || ($file === "..")) {
					continue;
				}
				if (is_dir($dir.$ds.$file)) {
					$this->removeDirectory($dir.$ds.$file);
				} else {
					unlink($dir.$ds.$file);
				}
			}
			@rmdir($dir);
			if (is_dir($dir)) {
				throw new \Exception("Could not remove the directory \"".$dir."\"");
			}
		}
	}

	/**
	 * Generates a page for the current character and comic.
	 *
	 * @param  string $assetPath  The path to the folder with the assets.
	 * @param  string $buildPath  The path to the folder to save the built page.
	 * @param  string $fileName  (optional) The file name of the main index file.
	 *
	 * @return integer           The bytes written to disk.
	 */
	public function build($assetPath, $buildPath, $fileName = "index.html") {

		if (file_exists($buildPath)) {
			$this->removeDirectory($buildPath);
		}
		$this->makeBuildDir($buildPath);

		$comic = $this->fetcher->getComic($this->comicId);

		// Some sanity tests:
		if (!$comic) {
			throw new \Exception("Could not retrieve comic");
		}
		if (!$comic->id) {
			throw new \Exception("Could not retrieve comic id from comic");
		}
		if (isset($this->comicId) && $this->comicId !== null && $comic->id !== $this->comicId) {
			throw new \Exception(
				"The returned comic's id (".$comic->id.") doesn't match the expected id (".$this->comicId.")"
			);
		}

		$characterList = array_map(function($character) {
			return [
				"id" => $character->id,
				"name" => $character->name,
				"description" => $character->description,
				"thumbnail" => $character->getThumbnailUrl("portrait_incredible")
			];
		}, $comic->characters->asList());

		$pageHtml = $this->twig->render("index.twig.html", [
			"pageTitle" => "An Automatically Generated Marvel Comic Page",
			"attributionText" => $this->fetcher->getAttributionText(false),
			"favoriteCharacterId" => $this->characterId,
			"comic" => [
				"id" => $comic->id,
				"title" => $comic->title,
				"mobileThumbnail" => $comic->getThumbnailUrl("portrait_medium"),
				"desktopThumbnail" => $comic->getThumbnailUrl("portrait_incredible"),
				"description" => $comic->description,
				"characters" => $characterList,
				"creators" => $comic->creators->asList()
			],
		]);

		$indexPath = rtrim($buildPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;

		$this->copyAssets($assetPath, $buildPath);
		$this->saveToFile($indexPath, $pageHtml);

		echo "Build script finished\n";
	}
}
