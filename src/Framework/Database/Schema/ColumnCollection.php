<?php

namespace Lightpack\Database\Schema;

class ColumnCollection
{
    private $columns = [];

    public function add(string $column, array $options)
    {
        foreach($options as $key => $value) {
            $this->columns[$column][$key] = $value;
        }
    }
}