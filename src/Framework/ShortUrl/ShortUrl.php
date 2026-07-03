<?php

namespace Lightpack\ShortUrl;

use DateTime;
use InvalidArgumentException;
use Lightpack\Database\Lucid\Model;

class ShortUrl extends Model
{
    protected $table = 'short_urls';

    protected $timestamps = true;

    protected $casts = [
        'hits' => 'int',
        'is_active' => 'bool',
        'expires_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    /**
     * Runs on every save() call. Auto-generates a unique code on insert,
     * defaults is_active to true on insert, and validates the target URL.
     */
    protected function beforeSave()
    {
        if (! $this->attributes->get($this->primaryKey)) {
            if (! $this->code) {
                $this->code = $this->generateUniqueCode();
            }

            if ($this->is_active === null) {
                $this->is_active = true;
            }
        }

        $this->validateUrl();
    }

    /**
     * Set an expiry using a relative date/time string.
     *
     * Example: $short->expiresIn('+7 days')->save();
     */
    public function expiresIn(string $modifier): self
    {
        $this->expires_at = new DateTime($modifier);

        return $this;
    }

    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->getTimestamp() < time();
    }

    /**
     * True when the URL is enabled and not expired.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active && ! $this->isExpired();
    }

    /**
     * Atomically increments hits and updates last_clicked_at,
     * avoiding lost updates under concurrent traffic.
     */
    public function recordClick(): void
    {
        $id = $this->attributes->get($this->primaryKey);
        $now = date('Y-m-d H:i:s');

        self::query()->where($this->primaryKey, $id)->increment('hits');
        self::query()->where($this->primaryKey, $id)->update(['last_clicked_at' => $now]);

        $this->hits = $this->hits + 1;
        $this->last_clicked_at = $now;
    }

    public function shortUrl(): string
    {
        return url()->to('/s/' . $this->code);
    }

    protected function validateUrl(): void
    {
        if ($this->url && ! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$this->url}");
        }
    }

    protected function generateUniqueCode(int $length = 6): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $max = strlen($alphabet) - 1;

        do {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
