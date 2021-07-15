<?php

namespace Splashsky;

class Router
{
    private static array $routes = [];
    private static $pathNotFound;
    private static $methodNotAllowed;

    /**
     * A quick static function to register a route in the router. Used by the shorthand methods as well.
     * @param string $route The path to be used as the route.
     * @param callable|string $action Either a callable to be executed, or a string reference to a method.
     * @param string|array $methods The HTTP verb(s) this route accepts.
     */
    public static function add(string $route, callable|string $action, string|array $methods = 'GET')
    {
        self::$routes[] = [
            'route' => $route,
            'action' => $action,
            'methods' => $methods
        ];
    }

    public static function get(string $route, callable $action)
    {
        self::add($route, $action, 'GET');
    }

    public static function post(string $route, callable $action)
    {
        self::add($route, $action, 'POST');
    }

    public static function getAllRoutes()
    {
        return self::$routes;
    }

    public static function pathNotFound(callable $action)
    {
        self::$pathNotFound = $action;
    }

    public static function methodNotAllowed(callable $action)
    {
        self::$methodNotAllowed = $action;
    }

    private static function tokenize(string $uri)
    {
        return preg_replace('/(?:{([A-Za-z]+)})+/', '([\w]+)', $uri);
    }

    public static function run(string $basePath = '', bool $multimatch = false)
    {
        $basePath = rtrim($basePath, '/');
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $method = $_SERVER['REQUEST_METHOD'];
        $path = urldecode(rtrim($uri, '/'));

        // If the path is empty (no slash in URI) place one to satisfy the root route ('/')
        if (empty($path)) {
            $path = '/';
        }

        $pathMatchFound = false;
        $routeMatchFound = false;

        // Begin looking through routes
        foreach (self::$routes as $route) {
            if ($basePath != '' && $basePath != '/') {
                $route['route'] = $basePath.$route['route'];
            }
        
            // Prepare route by tokenizing.
            $tokenized = '#^'.self::tokenize($route['route']).'$#u';

            // If the tokenized route matches the current path...
            if (preg_match($tokenized, $path, $matches)) {
                $pathMatchFound = true;
        
                // Run through the route's accepted method(s)
                foreach ((array) $route['methods'] as $allowedMethod) {
                    // See if the current request method matches
                    if (strtolower($method) == strtolower($allowedMethod)) {
                        array_shift($matches); // Remove the first match - always contains the full url
            
                        // If we're successful at calling the route's action, echo the result
                        if ($return = call_user_func_array($route['action'], $matches)) {
                            echo $return;
                        }
            
                        $routeMatchFound = true;
            
                        // Do not check other routes.
                        break;
                    }
                }
            }

            // Break the loop if the first found route is a match.
            if($routeMatchFound && !$multimatch) {
                break;
            }
        }

        // No matching route was found
        if (!$routeMatchFound) {
            // But a matching path exists
            if ($pathMatchFound) {
                if (self::$methodNotAllowed) {
                    call_user_func_array(self::$methodNotAllowed, Array($path, $method));
                } else {
                    die('405');
                }
            } else {
                if (self::$pathNotFound) {
                    call_user_func_array(self::$pathNotFound, Array($path));
                } else {
                    die('404');
                }
            }
        }
    }
}