<?php

use Lightpack\Database\Schema\Column;
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

    public function tearDown(): void
    {
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->schema->dropTable('products');
        $this->schema->dropTable('categories');
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testSchemaCanCreateTable()
    {
        $table = new Table('products');

        // $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        // $table->column('title')->type('varchar')->length(55);

        $table->id();
        $table->title(125);
        $table->email(125)->nullable();

        $this->schema->createTable($table);

        $this->assertTrue(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAlterTableAddColumn()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $this->schema->createTable($table);

        // Add new column
        $table = new Table('products');
        $table->column('description')->type('text');
        $this->schema->addColumn($table);

        $this->assertTrue(in_array('description', $this->getColumns('products')));
    }

    public function testSchemaCanAlterTableModifyColumn()
    {
        // First drop the table if exists
        $this->schema->dropTable('products');

        // First create the table
        $table = new Table('products');
        $table->column('description')->type('text');
        $this->schema->createTable($table);

        // Now lets modify the description column
        $table = new Table('products');
        $table->column('description')->type('varchar')->length(150);
        $this->schema->modifyColumn($table);

        // If column modified successfully, we should get its type 
        $descriptionColumnInfo = $this->getColumn('products', 'description');

        $this->assertEquals($descriptionColumnInfo['Type'], 'varchar(150)');
    }

    public function testSchemaCanTruncateTable()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $this->schema->createTable($table);

        // Truncate the table
        $this->schema->truncateTable('products');

        $count = $this->connection->query("SELECT COUNT(*) AS count FROM products")->fetch();

        $this->assertEquals(0, $count['count']);
    }

    public function testSchemaCanDropTable()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $this->schema->createTable($table);

        // Drop the table
        $this->schema->dropTable('products');

        $this->assertFalse(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAddForeignKey()
    {
        // Create categories table
        $table = new Table('categories');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $table->column('title')->type('varchar')->length(55);
        $this->schema->createTable($table);

        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $table->column('category_id')->type('int');
        $table->column('title')->type('varchar')->length(55);
        $table->key('category_id')->references('categories')->on('id');
        $this->schema->createTable($table);

        $this->assertTrue(in_array('products', $this->getTables()));
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

    private function getColumn(string $table, string $column)
    {
        $rows = $this->connection->query('DESCRIBE ' . $table);

        while (($row = $rows->fetch())) {
            if ($column === $row['Field']) {
                return $row;
            }
        }

        return null;
    }
}
