<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Debugger\Debug;
use Lightpack\Debugger\Output;

class DebugProvider 
{
    public function register(Container $container): void 
    {
        $config = $container->get('config');
        $environment = $config->get('app.env');
        $templatePath = $config->get('app.error_template');
        $logger = $container->get('logger');
        
        // Initialize debugger
        Debug::init($environment, $templatePath);
        Debug::setLogger([$logger, 'error']);
        
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
