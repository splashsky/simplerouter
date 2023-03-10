<?php

require './src/Router.php';

use Splashsky\Router;

$router = new Router();
$router->get('/', function() {
    return 'Foo!';
});

$router->get('/foo/{test}', function($test) {
    return 'Foo! '.$test;
});

$router->get('/test', function() {
    return '<form action="/test" method="POST"><input type="text" name="foo" placeholder="test me"><button type="submit">Submit</button></form>';
});

$router->post('/test', function() {
    return $_POST['foo'];
});

echo $router->run();