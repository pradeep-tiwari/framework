<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class BoolRule
{
    use ValidationMessageTrait;

    
    public function __construct()
    {
        $this->message = 'Must be a boolean value';
    }

    public function __invoke($value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off']);
        }

        if (is_int($value)) {
            return $value === 0 || $value === 1;
        }

        return false;
    }
}
