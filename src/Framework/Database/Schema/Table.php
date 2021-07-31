<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Schema\Compilers\Add;
use Lightpack\Database\Schema\Compilers\Create;

class Table
{
    protected $table;
    protected $columns;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->columns = new ColumnsCollection();
        $this->foreigns = new ForeignsCollection();
    }

    public function column(string $column): Column
    {
        $column = new Column($column);

        $this->columns->add($column);

        return $column;
    }

    public function foreign(string $column): Foreign
    {
        $foreign = new Foreign($column);

        $this->foreigns->add($foreign);

        return $foreign;
    }

    public function compileCreate()
    {
        return (new Create)->compile($this->table, $this->columns, $this->foreigns);
    }

    public function compileAdd()
    {
        return (new Add)->compile($this->table, $this->columns, $this->foreigns);
    }

    public function compileChange()
    {
        return (new Add)->compile($this->table, $this->columns, $this->foreigns);
    }
}