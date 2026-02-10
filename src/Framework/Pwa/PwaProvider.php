<?php

namespace Lightpack\Pwa;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Pwa\Pwa;
use Lightpack\Pwa\WebPush\WebPush;

/**
 * PwaProvider - Register PWA services
 * 
 * Registers PWA and WebPush services with the container.
 */
class PwaProvider implements ProviderInterface
{
    /**
     * Register the service provider
     */
    public function register(Container $container)
    {
        // Register Pwa service
        $container->register('pwa', function ($container) {
            $config = $container->get('config');
            return new Pwa($config->get('pwa'));
        });

        $container->alias(Pwa::class, 'pwa');

        // Register WebPush service
        $container->register('webpush', function ($container) {
            $config = $container->get('config');
            return new WebPush($config->get('pwa'));
        });

        $container->alias(WebPush::class, 'webpush');
    }
}
