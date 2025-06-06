<?php

namespace Lightpack\Http;

/**
 * Cookie
 * 
 * Represents a cookie object.
 * 
 * @source Cookie
 */
class Cookie
{
    /**
     * @var string $secret Shared secret used for signing a cookie.
     */
    private $secret;

    /**
     * @todo Constructor should accept an options parameter to be
     * configurable.
     */
    public function __construct(string $secret = '')
    {
        $this->secret = $secret;
    }

    public function set(string $key, string $value, int $expire = 0, array $options = []): bool {
        $value = $this->hash($value) . ':' . $value;
        $path = $options['path'] ?? '/';
        $domain = $options['domain'] ?? '';
        $secure = $options['secure'] ?? false;
        $httpOnly = $options['http_only'] ?? true;
        $sameSite = $options['same_site'] ?? 'lax';

        return setcookie($key, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ]);
    }

    public function forever(string $key, string $value, array $options = [])
    {
        $expire = time() + (60 * 60 * 24 * 365 * 5);
        $this->set($key, $value, $expire, $options);
    }

    public function get($key = null)
    {
        if(! $key) {
            return $_COOKIE;
        }

        if($this->has($key)) {
            return $this->parse($_COOKIE[$key]);
        }

        return null;
    }

    public function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    public function delete($key)
    {
        if($_COOKIE[$key] ?? null) {
            unset($_COOKIE[$key]);
            return $this->set($key, '', time() - 3600);
        }

        return false;
    }

    protected function hash($value)
    {
        return hash_hmac('sha1', $value, $this->secret);
    }
    
    protected function parse($value)
    {
        list($hash, $value) = explode(':', $value);

        if(!$hash || !$value) {
            return null;
        }

        if (hash_equals($this->hash($value), $hash) !== true) {
            return null;
        }

        return $value;
    }
}