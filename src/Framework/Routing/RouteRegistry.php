<?php

namespace Lightpack\Routing;

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
    ];

    private $names = [];

    private $request;

    private $subdomain;

    private $wildcardSubdomain = ':subdomain';

    public function __construct(\Lightpack\Http\Request $request, ?string $subdomain = '')
{
    $this->request = $request;
    $this->subdomain = $subdomain;
}

    public function get(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('GET', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function post(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('POST', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function put(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PUT', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function patch(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PATCH', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function delete(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('DELETE', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function options(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('OPTIONS', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function paths(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    public function group(array $options, callable $callback): void
{
    $oldOptions = $this->options;
    $this->options = \array_merge($oldOptions, $options);
    $this->options['prefix'] = $oldOptions['prefix'] . $this->options['prefix'];

    // Extract subdomain from options
    $subdomain = $this->options['subdomain'] ?? '';

    // Handle wildcard subdomain
    if ($subdomain && $subdomain !== '*') {
        $this->subdomain = $subdomain;
        $routeRegistry = new RouteRegistry($this->request, $subdomain);
        $callback($routeRegistry);
    } else {
        $callback($this);
    }

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

    public function matches(string $path): false|Route
    {
        if($this->subdomain && $this->subdomain !== $this->request->subdomain()) {
            return false;
        }

        $routes = $this->getRoutesForCurrentRequest();

        foreach ($routes as $routeUri => $route) {
            ['params' => $params, 'regex' => $regex] = $this->compileRegexWithParams($routeUri, $route->getPattern());

            if (preg_match('@^' . $regex. '$@', $path, $matches)) {
                \array_shift($matches);

                /** @var Route */
                $route = $this->routes[$this->request->method()][$routeUri];
                $route->setParams($params ? array_combine($params, $matches) : $matches);
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

    // Set subdomain for the route
    if ($this->subdomain === $this->wildcardSubdomain) {
        $route->setSubdomain($this->wildcardSubdomain);
    } else {
        $route->setSubdomain($this->subdomain);
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
            if(strpos($fragment, ':') === 0) {
                $param = substr($fragment, 1);
                $params[] = $param;
                $registeredPattern = $pattern[$param] ?? ':seg';
                $registeredPattern = $this->placeholders[$registeredPattern] ?? $registeredPattern;
                $parts[] = '(' . $registeredPattern . ')';
            } else {
                $parts[] = $fragment;
            }
        }

        return [
            'params' => $params,
            'regex' => implode('/', $parts),
        ];
    }

    private function getRoutesForCurrentRequest()
{
    $requestMethod = $this->request->method();
    $requestMethod = trim($requestMethod);
    $routes = $this->routes[$requestMethod] ?? [];

    // Filter routes based on subdomain
    if ($this->subdomain) {
        $filteredRoutes = [];
        foreach ($routes as $uri => $route) {
            if ($route->getSubdomain() === $this->subdomain || $route->getSubdomain() === $this->wildcardSubdomain) {
                $filteredRoutes[$uri] = $route;
            }
        }
        $routes = $filteredRoutes;
    }

    return $routes;
}

    private function setRouteName(Route $route): void
    {
        if(false === $route->hasName()) {
            return;
        }

        $name = $route->getName();

        if (isset($this->names[$name])) {
            throw new \Exception('Duplicate route name: ' . $name);
        }

        $this->names[$name] = $route;
    }
}
