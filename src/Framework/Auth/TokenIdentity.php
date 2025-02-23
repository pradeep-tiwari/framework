<?php

namespace Lightpack\Auth;

interface TokenIdentity
{
    /**
     * Get the token string.
     */
    public function getToken(): string;

    /**
     * Get the user ID associated with this token.
     */
    public function getUserId(): int|string;

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool;

    /**
     * Get the token name/description.
     */
    public function getName(): string;

    /**
     * Get the last time this token was used.
     */
    public function getLastUsedAt(): ?string;

    /**
     * Update the last used timestamp.
     */
    public function touch(): void;

    /**
     * Generate a new token
     */
    public function generate(int|string $userId, string $name, ?string $expiresAt = null): string;

    /**
     * Verify a token
     */
    public function verify(string $token): ?self;
}
