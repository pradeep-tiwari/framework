<?php

namespace Lightpack\SessionStore\Drivers;

use Lightpack\SessionStore\Contracts\StoreInterface;

class ArrayDriver implements StoreInterface
{
    private array $sessions = [];
    private array $timestamps = [];

    /**
     * Create a new session and return its ID
     */
    public function create(): string
    {
        $id = bin2hex(random_bytes(32));
        $this->sessions[$id] = [];
        $this->timestamps[$id] = [
            'created_at' => time(),
            'last_accessed_at' => time(),
        ];
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

        $this->touch($id);
        return $this->sessions[$id];
    }

    /**
     * Save session data
     */
    public function save(string $id, array $data): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $this->sessions[$id] = $data;
        $this->touch($id);
        return true;
    }

    /**
     * Destroy session data
     */
    public function destroy(string $id): bool
    {
        unset($this->sessions[$id], $this->timestamps[$id]);
        return true;
    }

    /**
     * Check if session is valid
     */
    public function isValid(string $id): bool
    {
        return isset($this->sessions[$id]);
    }

    /**
     * Update session last access time
     */
    public function touch(string $id): bool
    {
        if (!$this->isValid($id)) {
            return false;
        }

        $this->timestamps[$id]['last_accessed_at'] = time();
        return true;
    }

    /**
     * Get session creation time
     */
    public function getCreatedAt(string $id): ?int
    {
        return $this->timestamps[$id]['created_at'] ?? null;
    }

    /**
     * Get session last access time
     */
    public function getLastAccessedAt(string $id): ?int
    {
        return $this->timestamps[$id]['last_accessed_at'] ?? null;
    }

    /**
     * Clear all sessions (useful for testing)
     */
    public function clear(): void
    {
        $this->sessions = [];
        $this->timestamps = [];
    }
}
