https://img.shields.io/badge/version-v3.0.0-blue?style=flat
https://img.shields.io/badge/php-8.0%2B-blueviolet?style=flat

# SimpleRouter

Aloha! SimpleRouter is a super-small, lightweight, and easy-to-use router for your PHP project. It can handle any type of request, and features RegEx pattern matching for URI parameters. You can also easily define routes for 404 and 405 errors.

As this implementation is very simple, it works great as boilerplate for a more complicated router if your project demands it. I created this to serve as a basic router for a small RPG game in PHP. Let me know what you use it for in the Discussions tab!

## Usage
```php
// Include the class... (this can also be done via autoloading)
include 'src\Splashsky\Router.php';

// Use the namespace...
use Splashsky\Router;

// Create an instance of the router...
$router = new Router();

// Add the first GET route, and limit the id parameter to numbers...
$router->get('/user/{id}/edit', function($id) {
    return 'Editing user with id '.$id;
})->with('id', '[0-9]+');

// Complementary POST route...
$router->post('/user/{id}/edit', function($id) {
    // do posty stuff
    return 'Edited user id '.$id.' username changed to '.$_POST['username'];
});

// You can even make a prefix group...
$router->prefix('foo', function($router) {
    $router->get('', function() {
        return 'Ya foo!';
    });

    $router->get('/bar', function($router) {
        return 'Is a foobar';
    });
})

// Run the router!
echo $router->run();
```

## Installation

The easiest way to use SimpleRouter is to install it in your project via Composer.

```bash
composer require splashsky/simplerouter-php
```

Otherwise, download the latest Release and use `include` or `require` in your code.

## Caveats

Using SimpleRouter is... simple! There will always be a caveat or two, though. Here's the current things to note. . .

### PHP Version 8.0+
This project requires running PHP version 8.0+ due to the usage of new features to the language. I am thinking about making a PHP 7 compatible branch, though.

### Parameters are in order they appear

In the example of `/api/hello/{name}`, your first instinct when getting this parmeter in your action is that the variable will be named `$name`. This isn't the case - route parameters are in the order they are found in the route, and names are irrelevant.

## Contributing

I'm happy to look over and review any Issues or Pull Requests for this project. If you've run into a problem or have an enhancement or fix, submit it! It's my goal to answer and review everything within 48 hours.

## Credit

The original code had largely been initially written by [@SteamPixel](https://github.com/steampixel), so make sure to give him a follow or a star on the original repo as well. Further improvements to the router have been inspired by many projects, one of which being the [FOMO framework](https://github.com/fomo-framework/framework), which helped lay the groundwork for the v3 rework.

## License

This project is licensed under the MIT License. See LICENSE for further information.
