<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreate()
    {
        $table = new Table('products');

        $table->column('id')->type('int')->length(6)->increments(true)->attribute('unsigned')->index('primary key');
        $table->column('title')->type('varchar')->length(55)->index('unique')->default('Untitled');
        $table->column('created_at')->type('datetime');

        // Assertion
        $this->assertEquals(
            "CREATE TABLE products (id INT, title VARCHAR(55), created_at DATETIME);",
            $table->compileCreate()
        );
    }
}
