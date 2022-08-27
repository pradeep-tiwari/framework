<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\IValidationStrategy;

class Min implements IValidationStrategy
{
    private $_length;
    
    public function validate(array $dataSource, string $field, $num)
    {
        $data = $dataSource[$field];

        $this->_length = $num;
        
        return strlen($data) >= $num;  
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must be >= %s", $field, $this->_length);
    }
}