<?php

namespace Lightpack\Meter\Events;

class MeterExceeded
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $period,
        public readonly string $key,
        public readonly int $limit,
        public readonly int $attempted,
        public readonly int $current,
    ) {
    }
}
