<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Cache\Cache;
use Lightpack\Session\DriverInterface;

class CacheDriver implements DriverInterface
{
    private Cache $cache;
    private int $lifetime;
    private string $prefix;

    public function __construct(
        Cache $cache,
        int $lifetime = 7200, // 2 hours default
        string $prefix = 'session:'
    ) {
        $this->cache = $cache;
        $this->lifetime = $lifetime;
        $this->prefix = $prefix;
    }

    /**
     * Create a new session and return its ID
     */
    public function create(): string
    {
        $id = bin2hex(random_bytes(32));
        $this->cache->set(
            $this->prefix . $id,
            [
                'data' => [],
                'created_at' => time(),
                'last_accessed_at' => time(),
            ],
            $this->lifetime
        );
        return $id;
    }

    /**
     * Load session data by ID
     */
    public function load(string $id): ?array
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $session = $this->cache->get($this->prefix . $id);
        if ($session === null) {
            return null;
        }

        $this->touch($id);
        return $session['data'];
    }

    /**
     * Save session data
     */
    public function save(string $id, array $data): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $session = $this->cache->get($this->prefix . $id);
        if ($session === null) {
            return false;
        }

        $session['data'] = $data;
        $session['last_accessed_at'] = time();

        $this->cache->set(
            $this->prefix . $id,
            $session,
            $this->lifetime
        );

        return true;
    }

    /**
     * Destroy session data
     */
    public function destroy(string $id): bool
    {
        $this->cache->delete($this->prefix . $id);
        return true;
    }

    /**
     * Check if session is valid
     */
    public function isValid(string $id): bool
    {
        return $this->cache->has($this->prefix . $id);
    }

    /**
     * Update session last access time
     */
    public function touch(string $id): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $session = $this->cache->get($this->prefix . $id);
        if ($session === null) {
            return false;
        }

        $session['last_accessed_at'] = time();

        $this->cache->set(
            $this->prefix . $id,
            $session,
            $this->lifetime
        );

        return true;
    }

    /**
     * Get session creation time
     */
    public function getCreatedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $session = $this->cache->get($this->prefix . $id);
        if ($session === null) {
            return null;
        }

        return $session['created_at'];
    }

    /**
     * Get session last access time
     */
    public function getLastAccessedAt(string $id): ?int
    {
        if (!$this->isValid($id)) {
            return null;
        }

        $session = $this->cache->get($this->prefix . $id);
        if ($session === null) {
            return null;
        }

        return $session['last_accessed_at'];
    }
}
