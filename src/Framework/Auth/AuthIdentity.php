<?php

namespace Lightpack\Auth;

interface AuthIdentity
{
    /**
     * Get the unique identifier for the user.
     *
     * @return null|int|string
     */
    public function getId(): mixed;

    /**
     * Get the remember token for the user.
     */
    public function getRememberToken(): ?string;

    /**
     * Set the remember token for the user.
     */
    public function setRememberToken(string $token): void;

    /**
     * Get all tokens for this user.
     * 
     * @return \Lightpack\Database\Lucid\Collection
     */
    public function tokens();

    /**
     * Create a new token for this user.
     */
    public function createToken(string $name, ?string $expiresAt = null): string;
}
