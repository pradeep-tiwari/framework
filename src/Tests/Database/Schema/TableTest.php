<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreate()
    {
        $table = new Table('products');

        $table
            ->column('id', 'int')
            ->column('title', 'varchar', ['length' => 55])
            ->column('created_at', 'datetime');

        // Assertion
        $this->assertEquals(
            $table->compileCreate(),
            "CREATE TABLE products (id INT, title VARCHAR(55), created_at DATETIME);"
        );
    }
}
