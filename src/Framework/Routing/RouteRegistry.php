<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;

class RouteRegistry
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
        'OPTIONS' => [],
    ];
    private $placeholders = [
        ':any' => '.*',
        ':seg' => '[^/]+',
        ':num' => '[0-9]+',
        ':slug' => '[a-zA-Z0-9-]+',
        ':alpha' => '[a-zA-Z]+',
        ':alnum' => '[a-zA-Z0-9]+',
    ];

    private $options = [
        'prefix' => '',
        'filter' => [],
        'host' => '',
    ];

    private $names = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('GET', $this->buildUri($uri), $controller, $action);
    }

    public function post(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('POST', $this->buildUri($uri), $controller, $action);
    }

    public function put(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PUT', $this->buildUri($uri), $controller, $action);
    }

    public function patch(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PATCH', $this->buildUri($uri), $controller, $action);
    }

    public function delete(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('DELETE', $this->buildUri($uri), $controller, $action);
    }

    public function options(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('OPTIONS', $this->buildUri($uri), $controller, $action);
    }

    /**
     * Combines prefix and uri, ensuring exactly one slash between them.
     * Handles cases where either/both have or lack leading/trailing slashes.
     */
    protected function buildUri(string $uri): string
    {
        $prefix = $this->options['prefix'] ?? '';

        // Remove all leading/trailing slashes, backslashes, and whitespace
        $prefix = trim($prefix, " \/");
        $uri = trim($uri, " \/");

        // If both are empty, return '/'
        if ($prefix === '' && $uri === '') {
            return '/';
        }

        // If only prefix is empty
        if ($prefix === '') {
            return '/' . $uri;
        }

        // If only uri is empty
        if ($uri === '') {
            return $prefix === '' ? '/' : $prefix;
        }
        
        // Otherwise join with single slash
        return $prefix . '/' . $uri;
    }

    public function paths(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    public function group(array $options, callable $callback): void
    {
        $oldOptions = $this->options;
        // Merge prefix
        $options['prefix'] = ($oldOptions['prefix'] ?? '') . ($options['prefix'] ?? '');
        // Merge filters (cumulative array, unique)
        if (isset($options['filter']) && isset($oldOptions['filter'])) {
            $options['filter'] = array_unique(array_merge((array)$oldOptions['filter'], (array)$options['filter']));
        } elseif (isset($oldOptions['filter'])) {
            $options['filter'] = array_unique((array)$oldOptions['filter']);
        }
        // Inherit host if not set
        if (!isset($options['host']) && isset($oldOptions['host'])) {
            $options['host'] = $oldOptions['host'];
        }
        // Merge all options
        $merged = $oldOptions;
        foreach ($options as $key => $value) {
            $merged[$key] = $value;
        }
        $this->options = $merged;
        $callback($this);
        $this->options = $oldOptions;
    }

    public function map(array $verbs, string $route, string $controller, string $action = 'index'): void
    {
        foreach ($verbs as $verb) {
            if (false === \array_key_exists(strtoupper($verb), $this->routes)) {
                throw new \Exception('Unsupported HTTP request method: ' . $verb);
            }

            $this->{$verb}($route, $controller, $action);
        }
    }

    public function any(string $uri, string $controller, string $action = 'index'): void
    {
        $verbs = \array_keys($this->routes);

        foreach ($verbs as $verb) {
            $this->{$verb}($uri, $controller, $action);
        }
    }

    public function matches(string $path): bool|Route
    {
        $originalPath = $path;
        $routes = $this->getRoutesForCurrentRequest();

        foreach ($routes as $routeUri => $route) {
            ['params' => $params, 'regex' => $regex] = $this->compileRegexWithParams($routeUri, $route->getPattern());

            if ($route->getHost()) {
                $path = $this->container->get('request')->host() . '/' . trim($originalPath, '/');
            } else {
                $path = $originalPath;
            }

            if (preg_match('@^' . $regex . '$@', $path, $matches)) {
                \array_shift($matches);
                
                // Make sure we have extracted matched wildcard subdomain
                if($route->getHost() && strpos($routeUri[0], ':') === 0) {
                    $firstParams = explode('.',  $params[0]);
                    $firstMatches = explode('.',  $matches[0]);

                    $params[0] = $firstParams[0];
                    $matches[0] = $firstMatches[0];
                }

                $matches = array_map(function($match) {
                    return trim($match, '/');
                }, $matches);

                $routeParams = [];

                if($params) {
                    foreach($params as $key => $param) {
                        $routeParams[$param] = $matches[$key] ?? null;
                    }
                } else {
                    $routeParams = $matches;
                }
                
                /** @var Route */
                $route = $this->routes[$this->container->get('request')->method()][$routeUri];
                $route->setParams($routeParams);
                
                $route->setPath($path);

                return $route;
            }
        }

        return false;
    }

    public function getByName(string $name): ?Route
    {
        return $this->names[$name] ?? null;
    }

    public function bootRouteNames(): void
    {
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                $this->setRouteName($route);
            }
        }
    }

    private function add(string $method, string $uri, string $controller, string $action): Route
    {
        if (trim($uri) === '') {
            throw new \Exception('Empty route path');
        }

        $route = new Route();
        $route->setController($controller)->setAction($action)->filter($this->options['filter'])->setUri($uri)->setVerb($method);

        if ($this->options['host'] ?? false) {
            $uri = $this->options['host'] . '/' . trim($uri, '/');
            $route->host($this->options['host']);
            $route->setUri($uri);
        }

        $this->routes[$method][$uri] = $route;

        return $route;
    }

    private function regex(string $path): string
    {
        $search = \array_keys($this->placeholders);
        $replace = \array_values($this->placeholders);

        return str_replace($search, $replace, $path);
    }

    private function compileRegexWithParams(string $routePattern, array $pattern): array
    {
        $params = [];
        $parts = [];
        $fragments = explode('/', $routePattern);

        foreach ($fragments as $fragment) {
            if (strpos($fragment, ':') === 0) {
                $param = substr($fragment, 1);
                $isOptional = false;

                if (substr($param, -1) === '?') {
                    $param = substr($param, 0, -1);
                    $isOptional = true;
                }

                $params[] = $param;
                $registeredPattern = $pattern[$param] ?? ':seg';
                $registeredPattern = $this->placeholders[$registeredPattern] ?? $registeredPattern;

                if ($isOptional) {
                    $parts[] = '(\/' . $registeredPattern . ')?';
                } else {
                    $parts[] = '/(' . $registeredPattern . ')';
                }
            } else {
                $parts[] = '/' . $fragment;
            }
        }

        return [
            'params' => $params,
            'regex' => '/' . trim(implode('', $parts), '/'),
        ];
    }

    private function getRoutesForCurrentRequest()
    {
        $requestMethod = $this->container->get('request')->method();
        $requestMethod = trim($requestMethod);
        $routes = $this->routes[$requestMethod] ?? [];

        return $routes;
        // return \array_keys($routes);
    }

    private function setRouteName(Route $route): void
    {
        if (false === $route->hasName()) {
            return;
        }

        $name = $route->getName();

        if (isset($this->names[$name])) {
            throw new \Exception('Duplicate route name: ' . $name);
        }

        $this->names[$name] = $route;
    }
}
