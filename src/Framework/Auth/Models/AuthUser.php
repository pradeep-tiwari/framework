<?php

namespace Lightpack\Auth\Models;

use Lightpack\Auth\Identity;
use Lightpack\Database\Lucid\Model;
use Lightpack\Auth\Models\AccessToken;

class AuthUser extends Model implements Identity
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

    public function getAuthToken(): ?string
    {
        return $this->api_token;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setAuthToken(string $token): void
    {
        $this->api_token = $token;
        $this->save();
    }

    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
        $this->save();
    }

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class, 'user_id');
    }

    public function createToken(string $name, array $abilities = ['*'], ?string $expiresAt = null): AccessToken
    {
        $token = bin2hex(random_bytes(40));

        $accessToken = new AccessToken;

        $accessToken->user_id = $this->id;
        $accessToken->name = $name;
        $accessToken->token = hash('sha256', $token);
        $accessToken->abilities = json_encode($abilities);
        $accessToken->expires_at = $expiresAt;
        $accessToken->save;

        return $accessToken;
    }

    public function tokens()
    {
        return $this->accessTokens;
    }

    public function deleteToken($tokenId)
    {
        return $this->accessTokens()->where('id', $tokenId)->delete();
    }

    public function tokenCan(string $ability): bool
    {
        return $this->currentAccessToken && $this->currentAccessToken->can($ability);
    }
}
