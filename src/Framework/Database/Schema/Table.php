<?php

namespace Lightpack\Database\Schema;

class Table
{
    protected $tableName;
    protected $columns;
    protected $keys;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->columns = new ColumnsCollection();
        $this->keys = new ForeignsCollection();
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

        $this->keys->add($foreign);

        return $foreign;
    }

    public function columns(): ColumnsCollection
    {
        return $this->columns;
    }

    public function keys(): ForeignsCollection
    {
        return $this->keys;
    }

    public function name()
    {
        return $this->tableName;
    }
}
