<?php

namespace Lightpack\Database\Schema\Columns;

use Lightpack\Database\Schema\Column;

class IdColumn extends Column
{
    public function __construct(string $name = 'id')
    {        
        $this->columnName = $name;
        $this->columnType = 'INT';
        $this->columnLength = 11;
        $this->columnIsNullable = false;
        $this->columnIncrements = true;
        $this->columnAttribute = self::ATTRIBUTE_UNSIGNED;
        $this->columnIndexType = self::INDEX_PRIMARY;
    }
}