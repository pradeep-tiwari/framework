<?php

namespace Lightpack\Meter\Events;

class MeterReset
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $period,
        public readonly string $key,
    ) {
    }
}
