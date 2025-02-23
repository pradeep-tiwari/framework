<?php

namespace Lightpack\Auth\Models;

use Lightpack\Auth\TokenIdentity;
use Lightpack\Database\Lucid\Model;

class AuthToken extends Model implements TokenIdentity
{
    protected $table = 'tokens';

    protected $primaryKey = 'id';

    protected $timestamps = true;

    protected $hidden = [
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUserId(): int|string
    {
        return $this->user_id;
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return strtotime($this->expires_at) < time();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastUsedAt(): ?string
    {
        return $this->last_used_at;
    }

    public function touch(): void
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Create a new token for a user
     */
    public function generate(int|string $userId, string $name, ?string $expiresAt = null): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash_hmac('sha256', $token, config('app.key'));

        $this->user_id = $userId;
        $this->name = $name;
        $this->token = $hash;
        $this->expires_at = $expiresAt;

        $this->save();

        return $token;
    }

    /**
     * Verify a token
     */
    public function verify(string $token): ?self
    {
        $hash = hash_hmac('sha256', $token, config('app.key'));
        
        $model = $this->query()->where('token', $hash)->one();

        if (!$model || $model->isExpired()) {
            return null;
        }

        $model->touch();
        
        return $model;
    }
}
