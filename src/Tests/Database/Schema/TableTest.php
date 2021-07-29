<?php

use Lightpack\Database\Schema\Column;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreate()
    {
        $table = new Table('products');

        $table->column('id')->type('int')->length(6)->increments(true)->attribute('unsigned')->index('primary key');
        $table->column('title')->type('varchar')->length(55)->index(Column::INDEX_UNIQUE)->default('Untitled');
        $table->column('description')->type('text')->default(Column::DEFAULT_NULL);
        $table->column('created_at')->type('datetime')->default(Column::DEFAULT_CURRENT_TIMESTAMP);

        // Assertion
        $this->assertEquals(
            "CREATE TABLE products (id INT, title VARCHAR(55), created_at DATETIME);",
            $table->compileCreate()
        );
    }
}
