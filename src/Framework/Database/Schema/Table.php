<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Schema\Compilers\Create;

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

    public function column(string $column, string $type, array $options = [])
    {
        $this->columns->add($column, $type, $options);

        return $this;
    }

    public function create()
    {
        $sql = (new Create)->compile();

        print_r($sql);
    }
}