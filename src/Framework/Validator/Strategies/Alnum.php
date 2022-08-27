<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Alnum implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = Arr::get($field, $dataSource);

        return ctype_alnum($data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must contain only alphabets and numbers", $field);
    }
}