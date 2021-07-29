<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Schema\Compilers\Create;

class Table
{
    protected $table;
    protected $columns;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->columns = new ColumnsCollection();
    }

    public function column(string $column): Column
    {
        $column = new Column($column);

        $this->columns->add($column);

        return $column;
    }

    public function compileCreate()
    {
        return (new Create)->compile($this->table, $this->columns);
    }
}