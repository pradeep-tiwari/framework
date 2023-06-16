<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Routing\RouteRegistry;

class RouteProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('route', function ($container) {
            $request = $container->get('request');
            $subdomain = $request->subdomain();

            return new RouteRegistry($request, $subdomain);
        });

        $container->alias(RouteRegistry::class, 'route');
    }
}
