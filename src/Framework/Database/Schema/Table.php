<?php

namespace Lightpack\Database\Schema;

class Table
{
    protected $tableName;
    protected $columns;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->columns = new ColumnCollection();
    }

    public function showDefinition()
    {
        print_r($this->columns);
    }

    public function addColumn(string $column, array $options)
    {
        $this->columns->add($column, $options);

        return $this;
    }
}