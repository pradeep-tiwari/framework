<?php

namespace Lightpack\Database\Schema;

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
        $column = new Column($name);

        $column->type('BIGINT')->attribute('UNSIGNED')->increments()->index(Column::INDEX_PRIMARY);

        $this->tableColumns->add($column);

        return $column;
    }

    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name);

        $column->type('VARCHAR');
        $column->length($length);

        $this->tableColumns->add($column);

        return $column;
    }

    public function boolean(string $column, bool $default = false): Column
    {
        $column = new Column($column);

        $column->type('BOOLEAN');
        $column->default($default);

        $this->tableColumns->add($column);

        return $column;
    }

    public function created_at(): Column
    {
        $column = new Column('created_at');

        $column->type('DATETIME');
        $column->default('CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function updated_at(): Column
    {
        $column = new Column('updated_at');

        $column->type('DATETIME');
        $column->attribute('ON UPDATE CURRENT_TIMESTAMP');

        $this->tableColumns->add($column);

        return $column;
    }

    public function deleted_at(): Column
    {
        $column = new Column('deleted_at');

        $column->type('DATETIME');
        $column->nullable();

        $this->tableColumns->add($column);

        return $column;
    }

    public function datetime(string $column): Column
    {
        $column = new Column($column);

        $column->type('DATETIME');
        $column->nullable();

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
        // if column ends with '_at', set the column type to DATETIME
        if (substr($name, -3) === '_at') {
            return $this->datetime($name);
        }

        // otherwise, set the column type to VARCHAR
        return $this->string($name, $arguments[0] ?? 255);
    }
}
