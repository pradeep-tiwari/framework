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

        $table->id();
        $table->title(125);
        $table->email(125)->nullable();

        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        $this->assertTrue(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAlterTableAddColumn()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        // Add new column
        $table = new Table('products');
        $table->column('description')->type('text');
        $sql = $this->schema->addColumn($table);
        $this->connection->query($sql);

        $this->assertTrue(in_array('description', $this->getColumns('products')));
    }

    public function testSchemaCanAlterTableModifyColumn()
    {
        // First drop the table if exists
        $sql = $this->schema->dropTable('products');
        $this->connection->query($sql);

        // First create the table
        $table = new Table('products');
        $table->column('description')->type('text');
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        // Now lets modify the description column
        $table = new Table('products');
        $table->column('description')->type('varchar')->length(150);
        $sql = $this->schema->modifyColumn($table);
        $this->connection->query($sql);

        // If column modified successfully, we should get its type 
        $descriptionColumnInfo = $this->getColumn('products', 'description');

        $this->assertEquals($descriptionColumnInfo['Type'], 'varchar(150)');
    }

    public function testSchemaCanTruncateTable()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        // Truncate the table
        $sql = $this->schema->truncateTable('products');
        $this->connection->query($sql);

        $count = $this->connection->query("SELECT COUNT(*) AS count FROM products")->fetch();

        $this->assertEquals(0, $count['count']);
    }

    public function testSchemaCanDropTable()
    {
        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        // Drop the table
        $sql = $this->schema->dropTable('products');
        $this->connection->query($sql);

        $this->assertFalse(in_array('products', $this->getTables()));
    }

    public function testSchemaCanAddForeignKey()
    {
        // Create categories table
        $table = new Table('categories');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $table->column('title')->type('varchar')->length(55);
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

        // Create products table
        $table = new Table('products');
        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $table->column('category_id')->type('int');
        $table->column('title')->type('varchar')->length(55);
        $table->foreignKey('category_id')->references('id')->on('categories');
        $sql = $this->schema->createTable($table);
        $this->connection->query($sql);

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
