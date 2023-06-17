<?php

namespace Lightpack\Routing;

class Route
{
    private string $name;
    private string $controller;
    private string $action;
    private array $filters = [];
    private array $params = [];
    private string $path;
    private string $uri;
    private array $pattern = [];
    private $domain;

    /**
     * @var string HTTP method
     */
    private string $verb;

    /**
     * @var string $controller Controller class name.
     */
    public function setController(string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string Controller class name.
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $action The controller action to execute.
     * @return Route
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string The controller action to execute.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param array $filters Array of route filters.
     * @return Route
     */
    public function filter(string|array $filter): self
    {
        if (is_string($filter)) {
            $filter = [$filter];
        }

        $this->filters = array_merge($this->filters, $filter);
        $this->filters = array_unique($this->filters);

        return $this;
    }

    /**
     * @return array Array of route filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $params Array of matched route parameters
     * @return Route
     */
    public function setParams(array $params): self
    {
        // Make sure we have resolved the widlcard subdomain in params
        if(count($params) > 0 && strpos(array_key_first($params), '.') !== false) {
            $wildcardName = explode('.', array_key_first($params))[0];
            $wildcardValue = explode('.', reset($params))[0];
            array_shift($params);
            $params = array_merge([$wildcardName => $wildcardValue], $params);
        }

        $this->params = $params;
        return $this;
    }

    /**
     * @return array $params Array of matched route parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $path The request path to match against.
     * @return Route
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string The request path to match against.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $route The route URI pattern.
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string The route URI pattern.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $verb HTTP method.
     */
    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    /**
     * @return string HTTP method.
     */
    public function getVerb(): string
    {
        return $this->verb;
    }

     /**
     * Set the domain for the route.
     *
     * @param string|null $domain
     * @return Route
     */
    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get the domain for the route.
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return isset($this->name);
    }

    public function pattern(array $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getPattern(): array
    {
        return $this->pattern;
    }
}
