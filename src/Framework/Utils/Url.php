<?php

namespace Lightpack\Utils;

use Lightpack\Container\Container;

class Url
{
    /**
     * Generate URL with support for query params.
     * 
     * It takes any number of string texts and concatenates them to
     * generate the URL. To append query params, pass an array as
     * key-value pairs, and it will be converted to a query string.
     *
     * For example:
     * Url::to('users', ['sort' => 'asc', 'status' => 'active']);
     * That  will produce: /users?sort=asc&status=active 
     */
    public function to(...$params): string
    {
        // If absolute URL, return it.
        if (filter_var($params[0], FILTER_VALIDATE_URL)) {
            return $params[0];
        }

        $queryString = '';

        if (is_array($queryParams = end($params))) {
            $queryString = $this->buildQueryString($queryParams);
            array_pop($params);
        }

        // Remove empty values from the array.
        $params = array_filter($params, function ($value) {
            return $value && trim($value) ? true : false;
        });

        // Trim whitespace and slashes from URL params
        array_walk($params, fn (&$el) => $el = trim($el, '/ '));

        $url = '/' . implode('/', $params) . $queryString;

        // if (get_env('APP_URL')) {
        //     $url = rtrim(get_env('APP_URL'), '/') . $url;
        // }

        return rtrim($url, '/') ?: '/';
    }

    /**
     * ------------------------------------------------------------
     * Generates URL for assets in /public/assets folder.
     * ------------------------------------------------------------
     * 
     * Usage: 
     * 
     * Url::asset('css/styles.css');
     * Url::asset('img/favicon.png');
     * Url::asset('js/scripts.js');
     */
    public function asset(string $file): ?string
    {
        // trim whitespace and slashes from the file path
        $file = trim($file, '/ ');
        $file = $file ? '/' . $file : '';

        if (get_env('ASSET_URL')) {
            return rtrim(get_env('ASSET_URL'), '/') . $file;
        }

        return '/assets' . $file;
    }

    public function route(string $routeName, array $params = [])
    {
        /** @var \Lightpack\Routing\Route */
        $route = Container::getInstance()->get('route')->getByName($routeName);

        if (!$route) {
            throw new \Exception("Route with name '$routeName' not found.");
        }

        $uri = explode('/', trim($route->getUri(), '/ '));

        // We do not want the subdomain while resolving route urls
        if($route->getHost() !== '') {
            unset($uri[0]);
        }

        $uriPatterns = array_filter($uri, fn ($val) => strpos($val, ':') === 0);
        $lastCharacterForEndParam = substr(end($uriPatterns), -1);
        $minimumRequiredParams = $lastCharacterForEndParam == '?' ? count($uriPatterns) - 1 : count($uriPatterns);

        if ($minimumRequiredParams > count($params)) {
            throw new \Exception("Invalid number of parameters for route '$routeName'. Expected " . count($uriPatterns) . " but got " . count($params));
        }

        foreach ($uri as $key => $value) {
            if (strpos($value, ':') === 0) {
                $isOptionalParam = substr($value, -1) == '?';
                $value = trim($value, ':?');

                if (!$isOptionalParam && !isset($params[$value])) {
                    throw new \Exception("Undefined parameter [:{$value}] for route '{$routeName}'");
                }

                $uri[$key] = $params[$value] ?? null;
                unset($params[$value]);
            }
        }

        $uri[] = $params ?? [];

        return $this->to(...$uri);
    }

    /**
     * Builds a query string from an array of key-value pairs.
     */
    protected static function buildQueryString(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        // Remove empty values from the array.
        $params = array_filter($params, function ($value) {
            return $value && trim($value) ? true : false;
        });

        $queryString = http_build_query($params);

        return $queryString ? '?' . $queryString : '';
    }

    /**
     * Generate a signed URL for a given route.
     *
     * @param string $route The route name.
     * @param array $params The route params.
     * @param int $expiration Expiration time in seconds (default: 3600)
     * @return string
     */
    public function sign(string $route, array $params = [], int $expiration = 3600): string
    {
        $url = $this->route($route, $params);
        $expirationTime = time() + $expiration;
        $stringToSign = $url . $expirationTime;

        $crypto = Container::getInstance()->get('crypto');
        $encryptedSignature = $crypto->hash($stringToSign);

        // Append the encrypted signature and expiration timestamp as query parameters
        $url .= '&signature=' . urlencode($encryptedSignature);
        $url .= '&expires=' . $expirationTime;

        return $url;
    }

    public function verify(string $url, array $ignoredParameters = []): bool
    {
        // Extract the signature and expiration time from the URL
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['query'])) {
            return false; // URL has no query parameters
        }

        parse_str($parsedUrl['query'], $queryParams);
        if (!isset($queryParams['signature']) || !isset($queryParams['expires'])) {
            return false; // URL is missing signature or expires parameter
        }

        $signature = urldecode($queryParams['signature']);
        $expires = (int) $queryParams['expires'];

        // Remove the signature and expires parameters from the query string
        unset($queryParams['signature'], $queryParams['expires']);

        // Remove ignored parameters from the query string
        foreach ($ignoredParameters as $ignoredParam) {
            unset($queryParams[$ignoredParam]);
        }

        // Reconstruct the URL without the signature, expires, and ignored parameters
        $urlWithoutSignature = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        if (!empty($queryParams)) {
            $urlWithoutSignature .= '?' . http_build_query($queryParams);
        }

        // Recreate the expected signature using the same signing algorithm and secret key
        $stringToSign = $urlWithoutSignature . $expires;
        $crypto = Container::getInstance()->get('crypto');
        $expectedSignature = $crypto->hash($stringToSign);

        // Compare the expected signature with the extracted signature
        if ($signature !== $expectedSignature) {
            return false; // Signature doesn't match, URL has been tampered with
        }

        // Check the expiration time
        if ($expires < time()) {
            return false; // URL has expired
        }

        return true; // URL is valid and correct
    }

    /**
     * Parse a URL into its components.
     * 
     * Returns an array with the following components:
     * - scheme: The URL scheme (e.g., 'http', 'https')
     * - host: The hostname
     * - port: The port number (null if not specified)
     * - user: The username (null if not specified)
     * - pass: The password (null if not specified)
     * - path: The path component
     * - query: Array of query parameters
     * - fragment: The fragment identifier (null if not specified)
     * 
     * Example:
     * parse('https://example.com/blog?page=2#comments')
     * Returns: [
     *     'scheme' => 'https',
     *     'host' => 'example.com',
     *     'path' => '/blog',
     *     'query' => ['page' => '2'],
     *     'fragment' => 'comments',
     *     ...
     * ]
     */
    public function parse(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/')) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        $components = parse_url($url);
        if ($components === false) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        // Ensure all components exist with default values
        $defaults = [
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
            'fragment' => null,
        ];

        $components = array_merge($defaults, $components);

        // Parse query string into array if it exists
        if ($components['query'] !== null) {
            parse_str($components['query'], $query);
            $components['query'] = $query;
        } else {
            $components['query'] = [];
        }

        // Ensure path starts with /
        if ($components['path'] !== null && !str_starts_with($components['path'], '/')) {
            $components['path'] = '/' . $components['path'];
        }

        return $components;
    }

    /**
     * Add or update query parameters in a URL.
     * 
     * Example:
     * withQuery('https://example.com/search', ['q' => 'php'])
     * Returns: https://example.com/search?q=php
     * 
     * // With array parameters
     * withQuery('https://example.com/posts', ['tags' => ['php', 'mysql']])
     * Returns: https://example.com/posts?tags[0]=php&tags[1]=mysql
     */
    public function withQuery(string $url, array $parameters): string
    {
        $parts = $this->parse($url);
        
        // Merge with existing query parameters
        $parts['query'] = array_merge($parts['query'], $parameters);

        // Remove null/empty values
        $parts['query'] = array_filter($parts['query'], function ($value) {
            return $value !== null && $value !== '' && 
                   (!is_array($value) || !empty($value));
        });

        // Rebuild URL
        $newUrl = '';
        
        // Add scheme and authority
        if ($parts['scheme']) {
            $newUrl .= $parts['scheme'] . '://';
        }
        
        // Add user info if present
        if ($parts['user']) {
            $newUrl .= $parts['user'];
            if ($parts['pass']) {
                $newUrl .= ':' . $parts['pass'];
            }
            $newUrl .= '@';
        }
        
        // Add host and port
        if ($parts['host']) {
            $newUrl .= $parts['host'];
            if ($parts['port']) {
                $newUrl .= ':' . $parts['port'];
            }
        }
        
        // Add path
        if ($parts['path']) {
            $newUrl .= $parts['path'];
        }
        
        // Add query string with support for array parameters
        if (!empty($parts['query'])) {
            $newUrl .= '?' . http_build_query($parts['query'], '', '&', PHP_QUERY_RFC3986);
        }
        
        // Add fragment
        if ($parts['fragment']) {
            $newUrl .= '#' . $parts['fragment'];
        }
        
        return $newUrl;
    }

    /**
     * Normalize a URL by cleaning up common issues.
     * 
     * This method:
     * - Removes duplicate slashes
     * - Resolves directory traversal (.., .)
     * - Ensures consistent formatting
     * 
     * Example:
     * normalize('https://example.com//blog/../api/./users//')
     * Returns: https://example.com/api/users
     */
    public function normalize(string $url): string
    {
        $parts = $this->parse($url);
        
        if ($parts['path']) {
            // Remove duplicate slashes
            $parts['path'] = preg_replace('#/+#', '/', $parts['path']);
            
            // Split path into segments
            $segments = array_filter(explode('/', $parts['path']), 'strlen');
            $pathSegments = [];
            
            // Process each segment
            foreach ($segments as $segment) {
                if ($segment === '.') {
                    continue;
                }
                if ($segment === '..') {
                    array_pop($pathSegments);
                    continue;
                }
                $pathSegments[] = $segment;
            }
            
            // Rebuild path
            $parts['path'] = '/' . implode('/', $pathSegments);
        }
        
        // Rebuild URL with all components
        $normalizedUrl = '';
        
        // Add scheme
        if ($parts['scheme']) {
            $normalizedUrl .= $parts['scheme'] . '://';
        }
        
        // Add authentication
        if ($parts['user']) {
            $normalizedUrl .= $parts['user'];
            if ($parts['pass']) {
                $normalizedUrl .= ':' . $parts['pass'];
            }
            $normalizedUrl .= '@';
        }
        
        // Add host and port
        if ($parts['host']) {
            $normalizedUrl .= $parts['host'];
            if ($parts['port']) {
                $normalizedUrl .= ':' . $parts['port'];
            }
        }
        
        // Add path
        $normalizedUrl .= $parts['path'] ?? '';
        
        // Add query string
        if (!empty($parts['query'])) {
            $normalizedUrl .= '?' . http_build_query($parts['query']);
        }
        
        // Add fragment
        if ($parts['fragment']) {
            $normalizedUrl .= '#' . $parts['fragment'];
        }
        
        return $normalizedUrl;
    }
}
