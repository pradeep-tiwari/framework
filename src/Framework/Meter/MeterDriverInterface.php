<?php

namespace Lightpack\Meter;

interface MeterDriverInterface
{
    /**
     * Increment the value for a meter key.
     */
    public function increment(string $key, int $amount = 1): void;

    /**
     * Get the current value for a meter key.
     */
    public function value(string $key): int;

    /**
     * Reset the value for a meter key.
     */
    public function reset(string $key): void;
}
