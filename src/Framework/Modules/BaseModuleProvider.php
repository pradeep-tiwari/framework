<?php

namespace Lightpack\Modules;

use Lightpack\Providers\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\Console\Console;

abstract class BaseModuleProvider implements ProviderInterface
{
    /**
     * Module root path (e.g., /path/to/modules/Blog)
     * Must be set by child class.
     */
    protected string $modulePath;
    
    /**
     * Module namespace for views (e.g., 'blog')
     * Must be set by child class.
     */
    protected string $namespace;
    
    public function register(Container $container)
    {
        $this->loadConfig($container);
        $this->loadRoutes();
        $this->loadEvents($container);
        $this->loadCommands();
        $this->loadSchedules();
        $this->loadViews($container);
        $this->loadFilters($container);
        $this->registerServices($container);
    }
    
    protected function loadRoutes(): void
    {
        $file = $this->modulePath . '/routes.php';
        if (file_exists($file)) {
            require $file;
        }
    }
    
    protected function loadEvents(Container $container): void
    {
        $file = $this->modulePath . '/events.php';
        if (!file_exists($file)) {
            return;
        }
        
        $events = require $file;
        $eventManager = $container->get('event');
        
        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $eventManager->subscribe($event, $listener);
            }
        }
    }
    
    protected function loadCommands(): void
    {
        $file = $this->modulePath . '/commands.php';
        if (!file_exists($file)) {
            return;
        }
        
        $commands = require $file;
        
        foreach ($commands as $name => $handler) {
            Console::register($name, new $handler);
        }
    }
    
    protected function loadSchedules(): void
    {
        $file = $this->modulePath . '/schedules.php';
        if (file_exists($file)) {
            require $file;
        }
    }
    
    protected function loadViews(Container $container): void
    {
        $viewPath = $this->modulePath . '/Views';
        if (is_dir($viewPath)) {
            $container->get('template')->addViewPath($this->namespace, $viewPath);
        }
    }
    
    protected function loadConfig(Container $container): void
    {
        $configDir = $this->modulePath . '/Config';
        if (!is_dir($configDir)) {
            return;
        }
        
        $configs = glob($configDir . '/*.php');
        $config = $container->get('config');
        
        foreach ($configs as $file) {
            $name = basename($file, '.php');
            $data = require $file;
            
            // Store under modules.{namespace}.{config_name}
            // e.g., modules.blog.settings
            $config->set("modules.{$this->namespace}.{$name}", $data);
        }
    }
    
    protected function loadFilters(Container $container): void
    {
        $file = $this->modulePath . '/filters.php';
        if (!file_exists($file)) {
            return;
        }
        
        $filters = require $file;
        
        // Initialize global registry if not exists
        if (!isset($GLOBALS['modules_filters_registry'])) {
            $GLOBALS['modules_filters_registry'] = [];
        }
        
        // Merge module filters into global registry
        $GLOBALS['modules_filters_registry'] = array_merge($GLOBALS['modules_filters_registry'], $filters);
    }
    
    /**
     * Override this method to register module-specific services.
     */
    protected function registerServices(Container $container): void
    {
        // Optional: Override in child class
    }
}
