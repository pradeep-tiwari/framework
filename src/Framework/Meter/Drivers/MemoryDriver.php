<?php

namespace Lightpack\Meter\Drivers;

use Lightpack\Meter\MeterDriverInterface;

class MemoryDriver implements MeterDriverInterface
{
    protected array $values = [];

    public function increment(string $key, int $amount = 1): void
    {
        $this->values[$key] = ($this->values[$key] ?? 0) + $amount;
    }

    public function value(string $key): int
    {
        return $this->values[$key] ?? 0;
    }

    public function reset(string $key): void
    {
        unset($this->values[$key]);
    }
}
