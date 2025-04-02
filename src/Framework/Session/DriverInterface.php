<?php

namespace Lightpack\Session;

interface DriverInterface
{
    /**
     * Create a new session and return its ID
     */
    public function create(): string;

    /**
     * Load session data by ID
     */
    public function load(string $id): ?array;

    /**
     * Save session data
     */
    public function save(string $id, array $data): bool;

    /**
     * Destroy a session
     */
    public function destroy(string $id): bool;

    /**
     * Check if session ID exists and is valid
     */
    public function isValid(string $id): bool;

    /**
     * Update session last access time
     */
    public function touch(string $id): bool;

    /**
     * Get session creation time
     */
    public function getCreatedAt(string $id): ?int;

    /**
     * Get session last access time
     */
    public function getLastAccessedAt(string $id): ?int;
}
