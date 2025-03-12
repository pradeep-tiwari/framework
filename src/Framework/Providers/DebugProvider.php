<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Debugger\Debug;
use Lightpack\Debugger\Output;

class DebugProvider 
{
    public function register(Container $container): void 
    {
        $environment = $container->get('config')->get('app.env');
        
        // Initialize debugger
        Debug::init($environment);
        
        // Register debugger in container
        $container->register('debugger', function() {
            return Debug::class;
        });

        // Register output handler in container
        $container->register('debugger.output', function() {
            return Output::class;
        });
    }
}
