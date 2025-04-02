<?php

namespace Lightpack\Cache\Providers;

use Lightpack\Container\Container;
use Lightpack\Cache\Memcached\Memcached;
use Lightpack\Cache\Drivers\MemcachedDriver;

class MemcachedProvider
{
    public function register(Container $container): void
    {
        $container->register('memcached', function() {
            return new Memcached([
                [$_ENV['MEMCACHED_HOST'] ?? '127.0.0.1', 
                 $_ENV['MEMCACHED_PORT'] ?? 11211]
            ]);
        });
        
        $container->register('cache.memcached', function($container) {
            return new MemcachedDriver($container->get('memcached'));
        });
    }
}
