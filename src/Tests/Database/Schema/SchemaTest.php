<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Foreign;
use Lightpack\Database\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class SchemaTest extends TestCase
{
    /** @var \Lightpack\Database\Pdo */
    private $connection;

    /** @var \Lightpack\Database\Schema\Schema */
    private $schema;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';

        $this->connection = new Lightpack\Database\Adapters\Mysql($config);

        $this->schema = new Schema($this->connection);
    }

    public function testSchemaCanCreateTable()
    {
        $table = new Table('products');

        $table->column('id')->type('int')->increments(true)->index(Column::INDEX_PRIMARY);
        $table->column('title')->type('varchar')->length(55);

        $this->schema->create($table);
        
        $this->assertTrue(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAlterTableAddColumn()
    {
        $table = new Table('products');

        $table->column('description')->type('text');

        $this->schema->add($table);
    }

    private function getTables()
    {
        $tables = [];

        $rows = $this->connection->query('SHOW TABLES');

        while (($row = $rows->fetch())) {
            foreach($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }
}
