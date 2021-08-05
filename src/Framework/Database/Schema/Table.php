<?php

namespace Lightpack\Database\Schema;

class Table
{
    protected $tableName;
    protected $tableColumns;
    protected $tableKeys;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->tableColumns = new ColumnsCollection();
        $this->tableKeys = new KeysCollection();
    }

    public function column(string $column): Column
    {
        $column = new Column($column);

        $this->tableColumns->add($column);

        return $column;
    }

    public function key(string $column): Key
    {
        $foreign = new Key($column);

        $this->tableKeys->add($foreign);

        return $foreign;
    }

    public function columns(): ColumnsCollection
    {
        return $this->tableColumns;
    }

    public function keys(): KeysCollection
    {
        return $this->tableKeys;
    }

    public function name()
    {
        return $this->tableName;
    }
}
