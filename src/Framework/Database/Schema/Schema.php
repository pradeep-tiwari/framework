<?php

namespace Lightpack\Database\Schema;

use Lightpack\Database\Pdo;
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
}
