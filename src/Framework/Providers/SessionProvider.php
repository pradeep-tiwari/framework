<?php

namespace Lightpack\Providers;

use Lightpack\Config\Config;
use Lightpack\Session\Session;
use Lightpack\Container\Container;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Drivers\ArrayDriver;
use Lightpack\Session\Drivers\CacheDriver;
use Lightpack\Session\Drivers\FileDriver;
use Lightpack\Session\Drivers\NativeDriver;

class SessionProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('session', function ($container) {
            /** @var \Lightpack\Http\Request */
            $request = $container->get('request');

            $config = $container->get('config');

            $session = new Session(
                $this->getDriver($config),
                $this->getSecret(),
                $config->get('session.name')
            );

            $session->start();

            if ($request->isGet()) {
                $session->set('_previous_url', $request->fullUrl());
            }

            return $session;
        });

        $container->alias(Session::class, 'session');
    }

    protected function getDriver(Config $config): DriverInterface
    {
        $sessionDriver = $config->get('session.driver');

        if ($sessionDriver === 'native') {
            return new NativeDriver();
        }

        if ($sessionDriver === 'array') {
            return new ArrayDriver();
        }

        if ($sessionDriver === 'file') {
            return new FileDriver(
                $config->get('session.file.path'),
                $config->get('session.file.lifetime'),
            );
        }

        if ($sessionDriver === 'cache') {
            return new CacheDriver(
                $config->get('cache'),
                $config->get('session.file.lifetime'),
            );
        }

        throw new \Exception('Session driver not found');
    }

    protected function getSecret(): string 
    {
        $secret = get_env('APP_KEY');

        if (!$secret) {
            throw new \RuntimeException(
                'APP_KEY environment variable must be set for secure sessions'
            );
        }

        return $secret;
    }
}
