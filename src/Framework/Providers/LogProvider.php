<?php

namespace Lightpack\Providers;

use Lightpack\Logger\Logger;
use Lightpack\Container\Container;
use Lightpack\Logger\Drivers\FileLogger;
use Lightpack\Logger\Drivers\NullLogger;

class LogProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('logger', function ($container) {
            $logDriver = new NullLogger;

            if ('file' === get_env('LOG_DRIVER')) {
                $logDriver = new FileLogger(
                    $container->get('config')->get('storage.logs') . '/logs.txt'
                );
            }

            return new Logger($logDriver);
        });
    }
}
