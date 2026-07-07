<?php

namespace Lightpack\Meter;

use Lightpack\Container\Container;
use Lightpack\Meter\Drivers\DatabaseDriver;
use Lightpack\Meter\Drivers\MemoryDriver;
use Lightpack\Support\BaseManager;

class MeterManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }

    protected function registerBuiltInDrivers(): void
    {
        $this->register('database', function ($container) {
            $config = $container->get('config');

            return new DatabaseDriver(
                $container->get('db'),
                $config->get('meter.database.table', 'meters')
            );
        });

        $this->register('memory', function ($container) {
            return new MemoryDriver;
        });
    }

    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('meter.driver', 'database');
        $this->setDefaultDriver($default);
    }

    /**
     * Get a meter instance for the given name and optional period.
     */
    public function meter(string $name, ?string $period = null): Meter
    {
        return new Meter($this->driver(), $name, $period);
    }

    /**
     * Get a driver instance by name.
     */
    public function driver(?string $name = null): MeterDriverInterface
    {
        $name = $name ?? $this->defaultDriver;

        return $this->resolve($name);
    }

    protected function getErrorMessage(string $name): string
    {
        return "Meter driver not found: {$name}";
    }
}
