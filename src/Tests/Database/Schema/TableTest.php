<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreate()
    {
        $table = new Table('products');

        $table->column('title', 'varchar');

        $table->column('created_at', 'datetime');

        $table->showDefinition();

        $table->create();
    }
}
