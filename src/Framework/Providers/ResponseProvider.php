<?php

namespace Lightpack\Providers;

use Lightpack\Http\Response;
use Lightpack\Container\Container;
use Lightpack\Http\Redirect;
use Lightpack\Http\EventStream;
use Lightpack\Utils\Url;

class ResponseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('response', function ($container) {
            return new Response();
        });

        $container->register('redirect', function ($container) {
            return new Redirect();
        });

        $container->register('event_stream', function ($container) {
            return new EventStream();
        });

        $container->alias(Response::class, 'response');
        $container->alias(Redirect::class, 'redirect');
        $container->alias(EventStream::class, 'event_stream');
    }
}
