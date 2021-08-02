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
     * Create a new table.
     *
     * @param Table $table
     * @return void
     */
    public function create(Table $table): void
    {
        $sql = $table->compileCreate();

        $this->connection->query($sql);
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

    public function drop(string $table): void
    {
        $sql = (new DropTable)->compile($table);

        $this->connection->query($sql);   
    }
}
