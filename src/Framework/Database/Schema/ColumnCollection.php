<?php

namespace Lightpack\Database\Schema;

class ColumnCollection
{
    private $columns = [];

    public function add(string $column, string $type, array $options = [])
    {
        $this->columns[$column] = ['type' => $type];
        
        foreach($options as $key => $value) {
            $this->columns[$column][$key] = $value;
        }
    }
}