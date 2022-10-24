<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Schema\Columns\IdColumn;
use Lightpack\Database\Schema\Columns\StringColumn;

class Table
{
    private $tableName;
    private $tableColumns;
    private $renameColumns = [];
    private $tableKeys;

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

    public function renameColumn(string $oldName, string $newName): void
    {
        $this->renameColumns[$oldName] = $newName;
    }

    public function getRenameColumns(): array
    {
        return $this->renameColumns;
    }

    public function id(string $name = 'id'): Column
    {
        $column = new IdColumn($name);

        $this->tableColumns->add($column);

        return $column;
    }

    public function string(string $name, int $length = 255): Column
    {
        $column = new StringColumn($name);

        $column->length($length);

        $this->tableColumns->add($column);

        return $column;
    }

    /**
     * Set a string column automagically.
     * 
     * For example: 
     * $table->email(125); // Sets the column type to VARCHAR and the column length to 125.
     */
    public function __call($name, $arguments): Column
    {
        $column = new StringColumn($name);

        if(isset($arguments[0]) && is_int($arguments[0])) {
            $column->length($arguments[0]);
        }

        $this->tableColumns->add($column);

        return $column;
    }
}
