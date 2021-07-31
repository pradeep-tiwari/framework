<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Foreign;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreateTableCategories()
    {
        $table = new Table('categories');

        $table->column('id')->type('int')->increments(true)->index(Column::INDEX_PRIMARY);
        $table->column('title')->type('varchar')->length(55);

        // Assertion
        $this->assertEquals(
            "CREATE TABLE categories (id INT AUTO_INCREMENT, title VARCHAR(55), PRIMARY KEY (id));",
            $table->compileCreate()
        );
    }

    public function testCreateTableProducts()
    {
        $table = new Table('products');
        
        $table
        ->column('id')
        ->type('int')
        ->length(6)
        ->increments(true)
        ->attribute('unsigned')
        ->index(Column::INDEX_PRIMARY);

        $table->column('category_id')->type('int');

        $table
            ->column('title')
            ->type('varchar')
            ->length(55)
            ->index(Column::INDEX_UNIQUE, 'idx_product_title')
            ->default('Untitled');

        $table
            ->column('description')
            ->type('text')
            ->default(Column::DEFAULT_NULL);

        $table
            ->column('created_at')
            ->type('datetime')
            ->default(Column::DEFAULT_CURRENT_TIMESTAMP);

        $table
            ->foreign('category_id')
            ->references('categories')
            ->on('id')
            ->update(Foreign::ACTION_CASCADE)
            ->delete(Foreign::ACTION_CASCADE);

        // Assertion
        $this->assertEquals(
            "CREATE TABLE products (id INT(6) UNSIGNED AUTO_INCREMENT, category_id INT, title VARCHAR(55) DEFAULT 'Untitled', description TEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE idx_product_title (title), FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE ON UPDATE CASCADE);",
            $table->compileCreate()
        );
    }

    public function testCanAlterTableAddColumns()
    {
        $table = new Table('products');

        $table->column('x')->type('int');
        $table->column('y')->type('int');

        // Assertion
        $this->assertEquals(
            "ALTER TABLE products ADD x INT, ADD y INT;",
            $table->compileAdd()
        );
    }
}
