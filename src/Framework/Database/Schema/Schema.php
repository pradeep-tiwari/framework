<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Pdo;
use Lightpack\Database\Schema\Compilers\Create;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\DropTable;
use Lightpack\Database\Schema\Table;

class Schema
{
    /**
     * @var \Lightpack\Database\Pdo
     */
    private $connection;

    public function __construct(Pdo $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new table.
     *
     * @param Table $table
     * @return void
     */
    public function createTable(Table $table): void
    {
        $sql = (new CreateTable)->compile($table);

        $this->connection->query($sql);
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return void
     */
    public function dropTable(string $table): void
    {
        $sql = (new DropTable)->compile($table);

        $this->connection->query($sql);
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return void
     */
    public function truncateTable(string $table): void
    {
        $table = new Table($table);

        $sql = $table->compileTruncate();

        $this->connection->query($sql);
    }

    /**
     * Alter table add columns.
     *
     * @param Table $table
     * @return void
     */
    public function addColumn(Table $table): void
    {
        $sql = $table->compileAdd();

        $this->connection->query($sql);
    }

    /**
     * Drop columns in a table.
     *
     * @param string $table
     * @param string ...$columns
     * @return void
     */
    public function dropColumn(string $table, string ...$columns): void
    {
        $sql = (new Table($table))->compileDropColumns(...$columns);

        $this->connection->query($sql);
    }

    /**
     * Modify a column.
     *
     * @param Table $table
     * @return void
     */
    public function modifyColumn(Table $table): void
    {
        $sql = $table->compileChange();

        $this->connection->query($sql);
    }
}
