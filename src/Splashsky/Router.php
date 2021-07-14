<?php

namespace Splashsky;

class Router
{
    private static array $routes = [];
    private static $pathNotFound;
    private static $methodNotAllowed;

    public static function add(string $route, callable $callback, string $method = 'get')
    {
        self::$routes[] = [
            'route' => $route,
            'callback' => $callback,
            'method' => $method
        ];
    }

    public static function get(string $route, callable $callback)
    {
        self::add($route, $callback, 'get');
    }

    public static function post(string $route, callable $callback)
    {
        self::add($route, $callback, 'post');
    }

    public static function getAllRoutes()
    {
        return self::$routes;
    }

    public static function pathNotFound(callable $callback)
    {
        self::$pathNotFound = $callback;
    }

    public static function methodNotAllowed(callable $callback)
    {
        self::$methodNotAllowed = $callback;
    }

    public static function run(string $basePath = '', bool $caseMatters = false, bool $trailingSlashMatters = false, bool $multimatch = false)
    {
        $basePath = rtrim($basePath, '/');
        $url = parse_url($_SERVER['REQUEST_URI']);
        $path = '/';
        $method = $_SERVER['REQUEST_METHOD'];

        if (isset($url['path'])) {
            if ($trailingSlashMatters) {
                $path = $url['path'];
            } else {
                if($basePath.'/' != $url['path']) {
                    $path = rtrim($url['path'], '/');
                } else {
                    $path = $url['path'];
                }
            }
        }

        $path = urldecode($path);

        $pathMatchFound = false;
        $routeMatchFound = false;

        foreach (self::$routes as $route) {
            if ($basePath != '' && $basePath != '/') {
                $route['route'] = '('.$basePath.')'.$route['route'];
            }
        
            // Add string start and end automatically
            $route['route'] = '^'.$route['route'].'$';
        
            // Check path match
            if (preg_match('#'.$route['route'].'#'.($caseMatters ? '' : 'i').'u', $path, $matches)) {
                $pathMatchFound = true;
        
                // Cast allowed method to array if it's not one already, then run through all methods
                foreach ((array) $route['method'] as $allowedMethod) {
                    // Check method match
                    if (strtolower($method) == strtolower($allowedMethod)) {
                        array_shift($matches); // Always remove first element. This contains the whole string
            
                        if ($basePath != '' && $basePath != '/') {
                            array_shift($matches); // Remove basepath
                        }
            
                        if ($return = call_user_func_array($route['callback'], $matches)) {
                            echo $return;
                        }
            
                        $routeMatchFound = true;
            
                        // Do not check other routes
                        break;
                    }
                }
            }

            // Break the loop if the first found route is a match
            if($routeMatchFound && !$multimatch) {
                break;
            }
        }

        // No matching route was found
        if (!$routeMatchFound) {
            // But a matching path exists
            if ($pathMatchFound) {
                if (self::$methodNotAllowed) {
                    die('Method not allowed');
                    //call_user_func_array(self::$methodNotAllowed, Array($path, $method));
                }
            } else {
                if (self::$pathNotFound) {
                    die('Path not found');
                    //call_user_func_array(self::$pathNotFound, Array($path));
                }
            }
        }
    }
}