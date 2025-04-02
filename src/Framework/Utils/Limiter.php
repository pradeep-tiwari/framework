<?php

namespace Lightpack\Utils;

use Lightpack\Cache\Cache;
use Lightpack\Container\Container;

class Limiter 
{
    protected Cache $cache;
    protected string $prefix = 'limiter:';

    public function __construct() 
    {
        $this->cache = Container::getInstance()->get('cache');
    }

    public function attempt(string $key, int $max, int $mins): bool 
    {
        $key = $this->prefix . $key;
        $hits = (int) ($this->cache->get($key) ?? 0);
        
        if ($hits >= $max) {
            return false;
        }

        $this->cache->set($key, $hits + 1, $mins * 60, false);
        return true;
    }

    public function reset(string $key): void 
    {
        $this->cache->delete($this->prefix . $key);
    }

    public function hits(string $key): int 
    {
        return (int) ($this->cache->get($this->prefix . $key) ?? 0);
    }
}
