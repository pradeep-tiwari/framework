<?php

namespace Lightpack\Database\Schema\Columns;

use Lightpack\Database\Schema\Column;

class StringColumn extends Column
{
    public function __construct(string $name)
    {        
        $this->columnName = $name;
        $this->columnType = 'VARCHAR';
        $this->columnLength = 255;
    }
}