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
     * @param string $method The HTTP verb this route services.
     */
    public static function add(string $route, callable|string $action, string $method = 'GET')
    {
        self::$routes[] = [
            'route' => $route,
            'action' => $action,
            'method' => $method
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

    public static function run(string $basePath = '', bool $caseMatters = false, bool $multimatch = false)
    {
        $basePath = rtrim($basePath, '/');
        $path = rtrim(parse_url($_SERVER['REQUEST_URI']), '/');
        $method = $_SERVER['REQUEST_METHOD'];

        $path = urldecode($path);

        $pathMatchFound = false;
        $routeMatchFound = false;

        foreach (self::$routes as $route) {
            if ($basePath != '' && $basePath != '/') {
                $route['route'] = '('.$basePath.')'.$route['route'];
            }
        
            // Add string start and end automatically
            $route['route'] = '^'.$route['route'].'$';

            die('#'.$route['route'].'#'.($caseMatters ? '' : 'i').'u');
        
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
            
                        if ($return = call_user_func_array($route['action'], $matches)) {
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