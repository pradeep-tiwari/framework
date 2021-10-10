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

        $this->schema->createTable($table);

        $this->assertTrue(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAlterTableAddColumn()
    {
        $table = new Table('products');

        $table->column('description')->type('text');

        $this->schema->addColumn($table);

        $this->assertTrue(in_array('description', $this->getColumns('products')));
    }

    public function testSchemaCanTruncateTable()
    {
        $this->schema->truncateTable('products');

        $count = $this->connection->query("SELECT COUNT(*) AS count FROM products")->fetch();

        $this->assertEquals(0, $count['count']);
    }

    public function testSchemaCanDropTable()
    {
        $this->schema->dropTable('products');

        $this->assertFalse(in_array('products', $this->getTables()));
    }

    private function getTables()
    {
        $tables = [];

        $rows = $this->connection->query('SHOW TABLES');

        while (($row = $rows->fetch())) {
            foreach ($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

    private function getColumns(string $table)
    {
        $columns = [];

        $rows = $this->connection->query('DESCRIBE ' . $table);

        while (($row = $rows->fetch())) {
            $columns[] = $row['Field'];
        }

        return $columns;
    }
}
