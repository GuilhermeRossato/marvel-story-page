# Marvel Comics Page Generator

A web-page generator that uses the Marvel API to generate a page with a Marvel Comic defined with the characters involved in the story alongside the list of comics the character was present in.

## Preview

![Page Preview](https://github.com/GuilhermeRossato/marvel-story-page/blob/master/preview.png?raw=true),


## Setup

Clone the repository:

```bash
git clone https://github.com/GuilhermeRossato/marvel-story-page.git
```

Retrieve your credentials (public key and private key) from your [Marvel Account](https://developer.marvel.com/account) and save it in the environment.

If you create a `.env` file at the root of this project, you can add your keys to it effortlessly.

Remember to include the character id and (optionally) a comic id (retrieved from the API) to it.

```env
MARVEL_PUBLIC_KEY=63f9XXXXXXXXXXXXXXXXXXX0144
MARVEL_PRIVATE_KEY=c1d4XXXXXXXXXXXXXXXXXXXXXXXXX67bd
MARVEL_COMIC_ID=33072
MARVEL_FAVORITE_CHARACTER_ID=1010744
```

The above values are just an example and are available at the `.env-example` file.

Finally run the composer script to build the page:

```bash
composer run build
```

You're all set, you should see a new `build` folder at the root of this application with the built web page.

## Testing

Testing is orchestrated by PHPUnit and are defined at the `tests` folder and can be activated by the following composer script:

```bash
composer run test
```

## How does it work?

The Marvel API Fetcher class retrieves resources from the marvel API, which can be read [from here](https://developer.marvel.com/docs).

Marvel API works by returning a paginated resource list (limited by 100 results for requests), and each resource can have collections, which are lists of resources associated with the primary requested resource.

Collections contain incomplete Resources, so the Resource object is lazy-loaded whenever a 'missing' property is requested, which triggers a request for that resource in the endpoint so that it loads the expected property.

Currently there are two fully defined models: Story and Character, both of which extend the Resource class and define their expected properties. All other resources (comics, events, etc) are represented and fetches as Resource classes, fully functional, but less defined.

The Fetcher class can also load unpaginated results

## How to use the classes in this project

This app is about page creation, so the main Class does just that:

```php
require_once __DIR__."/vendor/autoload.php";

use Rossato\App;

$app = new App("xxx_publickey_xxx", "xxx_privatekey_xxx");
$app->build(__DIR__."/build");
```

The above code uses the Marvel API to create a page in the 'build' folder.

You can also use the Marvel Fetcher class manually:

```php
require_once __DIR__."/vendor/autoload.php";

use Rossato\Marvel\Fetcher;

// Create an instance of the class with your Marvel credentials
$fetcher = new Fetcher("xxx_publickey_xxx", "xxx_privatekey_xxx");


// Fetches a single story by Id

$story = $fetcher->getStory(7);
echo "Story 7 is named \"".$story->title."\"\n";

// Fetches all stories and list their characters
$stories = $fetcher->getStories();
echo "There are ".count($stories)." stories:";
foreach ($stories as $story) {
    echo "Story Id: ".$story->id."\n";
    echo "Story Title: ".$story->title."\n";
    echo "Story Description: ".$story->description."\n";
    echo "Story Image: ".$story->getThumbnailUrl()."\n";
    echo "Characters involved: ".count($story->characters)."\n";
    foreach ($story->characters as $character) {
        echo "\t"."Character Id: ".$character->id."\n";
        echo "\t"."Character Name: ".$character->name."\n";
        echo "\t"."Character Description".$character->description."\n";
        echo "\n";
    }
    echo "\n";
}

// Get all characters
$characters = $fetcher->getCharacters();
echo "There are ".count($characters)." characters in the marvel api\n";

```

All class methods and their parameters are documented in the code, so a quick look through the source code will let you see what it does and how to use it.

## Credits

Website and page creation done by Guilherme Rossato

Images, characters, stories created by Marvel

Data provided by Marvel. © [MARVEL](http://marvel.com)

