<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Pdo;
use Lightpack\Database\Schema\Table;
use Lightpack\Database\Schema\Compilers\AddColumn;
use Lightpack\Database\Schema\Compilers\DropTable;
use Lightpack\Database\Schema\Compilers\DropColumn;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\TruncateTable;

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
        $sql = (new TruncateTable)->compile($table);

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
        $sql = (new AddColumn)->compile($table);

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
        $sql = (new DropColumn)->compile($table, ...$columns);

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
        $sql = (new ModifyColumn)->compile($table);

        $this->connection->query($sql);
    }
}
