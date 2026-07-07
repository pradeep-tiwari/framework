<?php

namespace Lightpack\Meter;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class MeterProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->register('meter.manager', function ($container) {
            return new MeterManager($container);
        });

        $container->alias(MeterManager::class, 'meter.manager');
    }
}
