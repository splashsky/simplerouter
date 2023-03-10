<?php

namespace Splashsky;

class Router
{
    private array $routes = [];
    private $pathNotFound;
    private $methodNotAllowed;
    private string $defaultConstraint = '([\w\-]+)';
    private string $currentPrefix = '';
    private string $lastInsertedRoute = '';

    public function __construct(private string $basePath = '')
    {
        // ...
    }

    /**
     * Shorthand function to define a GET route
     *
     * @param string $route
     * @param callable $action
     * @return Router
     */
    public function get(string $route, callable $action)
    {
        return $this->add($route, $action, 'GET');
    }

    /**
     * Default function to define a POST route
     *
     * @param string $route
     * @param callable $action
     * @return Router
     */
    public function post(string $route, callable $action)
    {
        return $this->add($route, $action, 'POST');
    }

    /**
     * Return all routes currently registered
     *
     * @return array
     */
    public function getAllRoutes()
    {
        return $this->routes;
    }

    /**
     * Defines an action to be called when a path isn't found - i.e. a 404
     *
     * @param callable $action
     * @return void
     */
    public function pathNotFound(callable $action)
    {
        $this->pathNotFound = $action;
    }

    /**
     * Defines an action to be called with a method isn't allowed on a route - i.e. a 405
     *
     * @param callable $action
     * @return void
     */
    public function methodNotAllowed(callable $action)
    {
        $this->methodNotAllowed = $action;
    }

    /**
     * Redefine the default constraint for route parameters. Default is '([\w\-]+)'
     *
     * @param string $constraint The RegEx you want parameters to adhere to by default. Defaults to '([\w\-]+)'
     * @return void
     */
    public function setDefaultConstraint(string $constraint = '([\w\-]+)')
    {
        $this->defaultConstraint = $constraint;
    }

    private function trimRoute(string $route): string
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
    public function prefix(string $prefix, callable $routes)
    {
        $this->currentPrefix = $prefix;

        $routes();

        $this->currentPrefix = '';
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
    public function with(string|array $parameter, string $constraint = ''): self
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $last = $this->lastInsertedRoute;

        if (is_array($parameter)) {
            foreach ($parameter as $param => $constraint) {
                $this->routes[$method][$last]['constraints'][$param] = $constraint;
            }

            return $this;
        }

        $this->routes[$method][$last]['constraints'][$parameter] = $constraint;

        return $this;
    }

    /**
     * Tokenizes the given URI using our constraint rules and returns the tokenized URI
     *
     * @param string $uri
     * @return string
     */
    private function tokenize(string $uri, array $constraints)
    {
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
                $uri = str_replace($pattern, $this->defaultConstraint, $uri);
            }
        }

        return $uri;
    }

    /**
     * Add a route to the list of routes.
     */
    public function add(string $route, callable|string $action, string $method = 'GET')
    {
        // If a prefix exists, prepend it to the route
        if (!empty($this->currentPrefix)) {
            $route = $this->currentPrefix . $route;
        }

        $trimmed = $this->trimRoute($route);
        $this->lastInsertedRoute = $trimmed;

        $this->routes[$method][$trimmed] = ['action' => $action, 'constraints' => []];
    }

    /**
     * Runs the router. Accepts a base path from which to serve the routes, and optionally whether you want to try
     * and match multiple routes.
     *
     * @param string $basePath
     * @param boolean $multimatch
     * @return void
     */
    public function run()
    {
        $basePath = $this->trimRoute($this->basePath);
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $path = urldecode($this->trimRoute($uri));

        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as $route => $opts) {
            $tokenized = '#^'.$this->tokenize($this->trimRoute($basePath.$route), $opts['constraints']).'$#u';

            if (preg_match($tokenized, $path, $matches)) {
                if ($return = call_user_func_array($opts['action'], $matches)) {
                    return $return;
                }
            }
        }

        if ($this->pathNotFound) {
            call_user_func_array($this->pathNotFound, [$path]);
        } else {
            die('404');
        }
    }
}