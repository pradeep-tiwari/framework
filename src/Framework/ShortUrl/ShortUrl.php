<?php

namespace Lightpack\ShortUrl;

use Lightpack\Database\Lucid\Model;

class ShortUrl extends Model
{
    protected $table = 'short_urls';

    protected $timestamps = true;

    protected $casts = [
        'hits' => 'int',
    ];

    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return strtotime($this->expires_at) < time();
    }

    public function recordClick(): void
    {
        $this->hits++;
        $this->last_clicked_at = date('Y-m-d H:i:s');
        $this->save();
    }

    public function shortUrl(): string
    {
        return url()->to('/s/' . $this->code);
    }
}
