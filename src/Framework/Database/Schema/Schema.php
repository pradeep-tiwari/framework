<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Pdo;
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
     * Alter table add columns.
     *
     * @param Table $table
     * @return void
     */
    public function add(Table $table): void
    {
        $sql = $table->compileAdd();

        $this->connection->query($sql);
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return void
     */
    public function truncate(string $table): void
    {
        $table = new Table($table);

        $sql = $table->compileTruncate();

        $this->connection->query($sql);
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return void
     */
    public function drop(string $table): void
    {
        $sql = (new DropTable)->compile($table);

        $this->connection->query($sql);
    }

    /**
     * Create a new table.
     *
     * @param Table $table
     * @return void
     */
    public function createTable(Table $table): void
    {
        $sql = $table->compileCreate();

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
        // todo...
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return void
     */
    public function truncateTable(string $table): void
    {
        // todo...
    }

    /**
     * Add column in a table.
     *
     * @param Table $table
     * @return void
     */
    public function addColumn(Table $table): void
    {
        // todo...
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
        // todo...
    }

    /**
     * Modify a column.
     *
     * @param Table $table
     * @return void
     */
    public function modifyColumn(Table $table): void
    {
        // todo...
    }
}
