<?php

namespace Splashsky;

class Router
{
    private static array $routes = [];
    private static $pathNotFound;
    private static $methodNotAllowed;
    private static string $defaultConstraint = '([\w\-]+)';
    private static string $currentPrefix = '';
    private static string $lastInsertedRoute = '';

    /**
     * A quick static function to register a route in the router. Used by the shorthand methods as well.
     * 
     * @param string $route The path to be used as the route.
     * @param callable|string $action Either a callable to be executed, or a string reference to a method.
     * @param string|array $methods The HTTP verb(s) this route accepts.
     * @return Router
     */
    public static function add(string $route, callable|string $action, string|array $methods = 'GET')
    {
        // If a prefix exists, prepend it to the route
        if (!empty(self::$currentPrefix)) {
            $route = self::$currentPrefix.$route;
        }

        $trimmed = self::trimRoute($route);

        self::$routes[$trimmed] = [
            'route' => $trimmed,
            'action' => $action,
            'methods' => $methods,
            'constraints' => []
        ];

        self::$lastInsertedRoute = $trimmed;

        return new self;
    }

    /**
     * Shorthand function to define a GET route
     *
     * @param string $route
     * @param callable $action
     * @return Router
     */
    public static function get(string $route, callable $action)
    {
        return self::add($route, $action, 'GET');
    }

    /**
     * Default function to define a POST route
     *
     * @param string $route
     * @param callable $action
     * @return Router
     */
    public static function post(string $route, callable $action)
    {
        return self::add($route, $action, 'POST');
    }

    /**
     * Return all routes currently registered
     *
     * @return array
     */
    public static function getAllRoutes()
    {
        return self::$routes;
    }

    /**
     * Defines an action to be called when a path isn't found - i.e. a 404
     *
     * @param callable $action
     * @return void
     */
    public static function pathNotFound(callable $action)
    {
        self::$pathNotFound = $action;
    }

    /**
     * Defines an action to be called with a method isn't allowed on a route - i.e. a 405
     *
     * @param callable $action
     * @return void
     */
    public static function methodNotAllowed(callable $action)
    {
        self::$methodNotAllowed = $action;
    }

    /**
     * Redefine the default constraint for route parameters. Default is '([\w\-]+)'
     *
     * @param string $constraint The RegEx you want parameters to adhere to by default. Defaults to '([\w\-]+)'
     * @return void
     */
    public static function setDefaultConstraint(string $constraint = '([\w\-]+)')
    {
        self::$defaultConstraint = $constraint;
    }

    private static function trimRoute(string $route): string
    {
        $route = trim(trim($route), '/');
        return "/$route";
    }

    /**
     * Accepts a callable that defines routes, and adds a prefix to them.
     *
     * @param string $prefix The prefix you want added to the routes.
     * @param callable $routes A function that defines routes.
     * @return void
     */
    public static function prefix(string $prefix, callable $routes)
    {
        self::$currentPrefix = $prefix;

        $routes();

        self::$currentPrefix = '';
    }

    /**
     * Define a constraint for a route parameter. If only passing one parameter, 
     * provide the parameter name as first argument and constraint as second. If 
     * adding constraints for multiple parameters, pass an array of 'parameter' => 'constraint'
     * pairs.
     * 
     * @param string|array $parameter
     * @param string $constraint
     * @return Router
     */
    public static function with(string|array $parameter, string $constraint = '')
    {
        $last = self::$lastInsertedRoute;

        if (is_array($parameter)) {
            foreach ($parameter as $param => $constraint) {
                self::$routes[$last]['constraints'][$param] = $constraint;
            }

            return new self;
        }

        self::$routes[$last]['constraints'][$parameter] = $constraint;

        return new self;
    }

    /**
     * Tokenizes the given URI using our constraint rules and returns the tokenized URI
     *
     * @param string $uri
     * @return string
     */
    private static function tokenize(string $uri)
    {
        $constraints = self::$routes[$uri]['constraints'];
        $constraintKeys = array_keys($constraints);

        preg_match_all('/(?:{([\w\-]+)})+/', $uri, $matches);
        $matches = $matches[1];

        foreach ($matches as $match) {
            $pattern = '{'.$match.'}';

            if (in_array($match, $constraintKeys)) {
                // Do some voodoo to allow users to use parentheses in their constraints if they want
                $constraint = '('.rtrim(ltrim(trim($constraints[$match]), '('), ')').')';

                $uri = str_replace($pattern, $constraint, $uri);
            } else {
                $uri = str_replace($pattern, self::$defaultConstraint, $uri);
            }
        }

        return $uri;
    }

    /**
     * Runs the router. Accepts a base path from which to serve the routes, and optionally whether you want to try
     * and match multiple routes.
     *
     * @param string $basePath
     * @param boolean $multimatch
     * @return void
     */
    public static function run(string $basePath = '', bool $multimatch = false)
    {
        $basePath = self::trimRoute($basePath);
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $path = urldecode(self::trimRoute($uri));

        $pathMatchFound = false;
        $routeMatchFound = false;

        // Begin looking through routes
        foreach (self::$routes as $route) {
            // If the basePath isn't just "root"
            if ($basePath != '/') {
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