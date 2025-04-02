<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\SessionStore\Store;
use Lightpack\SessionStore\Drivers\FileDriver;

class SessionStoreProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('session', function ($container) {
            /** @var \Lightpack\Http\Request */
            $request = $container->get('request');

            $store = new Store(
                $this->getDriver(),
                $this->getSecret(),
                $this->getCookieName()
            );

            $store->start();

            if ($request->isGet()) {
                $store->set('_previous_url', $request->fullUrl());
            }

            return $store;
        });

        $container->alias(Store::class, 'session');
    }

    protected function getDriver(): FileDriver
    {
        $path = get_env('SESSION_PATH', sys_get_temp_dir() . '/sessions');
        $lifetime = (int) get_env('SESSION_LIFETIME', 7200);

        return new FileDriver($path, $lifetime);
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

    protected function getCookieName(): string
    {
        return get_env('SESSION_COOKIE', 'LPSESSID');
    }
}
