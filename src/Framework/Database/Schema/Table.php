<?php

namespace Lightpack\Database\Schema;

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

    public function columns(): ColumnsCollection
    {
        return $this->columns;
    }

    public function keys(): ForeignsCollection
    {
        return $this->foreigns;
    }

    public function name()
    {
        return $this->table;
    }
}
