# SimpleRouter, PHP Edition

Aloha! SimpleRouter is a super-small, lightweight, and easy-to-use router for your PHP project. It can handle any type of request, and features RegEx pattern matching for URI parameters. You can also easily define routes for 404 and 405 errors.

As this implementation is very simple, it works great as boilerplate for a more complicated router if your project demands it. I created this to serve as a basic router for a small RPG game in PHP. Let me know what you use it for in the Discussions tab!

## Usage
```php
// Include the class... (this can also be done via autoloading)
include 'src\Splashsky\Router.php';

// Use the namespace...
use Splashsky\Router;

// Add the first GET route...
Router::get('/user/{id}/edit', function ($id) {
    return 'Edit user with id '.$id.'<br>';
});

// Run the router!
Router::run();
```

## Installation

The easiest way to use SimpleRouter is to install it in your project via Composer.

```bash
composer require splashsky/simplerouter-php
```

Otherwise, download the latest Release and use `include` or `require` in your code.

## Caveats

Using SimpleRouter is... simple! There's a couple of principles to note, however.

### Root Route

You can't have an empty route (`Router::get('', ...);`), as the router **always assumes you at least have a `/` in your URI**. The root route should always be `/`, such as in `Router::get('/', function () {});`.

### Parameters are in order they appear

In the example of `/api/hello/{name}`, your first instinct when getting this parmeter in your action is that the variable will be named `$name`. This isn't the case - route parameters are in the order they are found in the route, and names are irrelevant.

## Routing for subfolders
If you're wanting to route for seperate uses (such as an api), you can create another entrypoint (in `/api/v1` for example) and pass a custom base path to the router.

```php
Router::run('/api/v1');
```

Ensure that your web server points traffic from `/api/v1` to this entrypoint appropriately.

## Contributing

I'm happy to look over and review any Issues or Pull Requests for this project. If you've run into a problem or have an enhancement or fix, submit it! It's my goal to answer and review everything within 48 hours.

## Credit

Most of the code so far has been initially written by [@SteamPixel](https://github.com/steampixel), so make sure to give him a follow or a star on the original repo as well. Thanks!

## License

This project is licensed under the MIT License. See LICENSE for further information.
