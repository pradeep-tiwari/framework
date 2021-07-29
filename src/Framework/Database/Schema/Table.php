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

    public function column(string $column): Column
    {
        $column = new Column($column);

        $this->columns->add($column);

        return $column;
    }

    public function compileCreate()
    {
        return (new Create)->compile($this->tableName, $this->columns);
    }
}