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
     * Create a new table instance.
     */
    public function for(string $name): Table
    {
        return new Table($name);
    }

    /**
     * Create a new table.
     *
     * @param Table $table
     * @return void
     */
    public function createTable(Table $table): string
    {
        $sql = (new CreateTable)->compile($table);
        
        return $sql;
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return void
     */
    public function dropTable(string $table): string
    {
        $sql = (new DropTable)->compile($table);

        return $sql;
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return void
     */
    public function truncateTable(string $table): string
    {
        $sql = (new TruncateTable)->compile($table);

        return $sql;
    }

    /**
     * Alter table add columns.
     *
     * @param Table $table
     * @return void
     */
    public function addColumn(Table $table): string
    {
        $sql = (new AddColumn)->compile($table);

        return $sql;
    }

    /**
     * Drop columns in a table.
     *
     * @param string $table
     * @param string ...$columns
     * @return void
     */
    public function dropColumn(string $table, string ...$columns): string
    {
        $sql = (new DropColumn)->compile($table, ...$columns);

        return $sql;
    }

    /**
     * Modify a column.
     *
     * @param Table $table
     * @return void
     */
    public function modifyColumn(Table $table): string
    {
        $sql = (new ModifyColumn)->compile($table);

        return $sql;
    }
}
