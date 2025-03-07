<?php

namespace Lightpack\Providers;

use Lightpack\Routing\Router;
use Lightpack\Container\Container;

class RouterProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('router', function ($container) {
            return new Router($container->get('route'));
        });

        $container->alias(Router::class, 'router');
    }
}
