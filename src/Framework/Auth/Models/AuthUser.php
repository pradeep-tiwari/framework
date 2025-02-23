<?php

namespace Lightpack\Auth\Models;

use Lightpack\Auth\AuthIdentity;
use Lightpack\Auth\Models\AuthToken;
use Lightpack\Database\Lucid\Model;

class AuthUser extends Model implements AuthIdentity
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $timestamps = true;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
        $this->save();
    }

    public function tokens()
    {
        return $this->hasMany(AuthToken::class, 'user_id');
    }

    /**
     * Create a new token for this user
     */
    public function createToken(string $name, ?string $expiresAt = null): string
    {
        $authToken = new AuthToken();

        return $authToken->generate($this->id, $name, $expiresAt);
    }
}
