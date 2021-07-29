<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;

final class TableTest extends TestCase
{

    public function testCreate()
    {
        $table = new Table('products');

        $table->addColumn('title', [
            'type' => 'varchar',
            'length' => 55,
            'default' => 'Untitled',
            'nullable' => false,
        ]);

        $table->addColumn('created_at', [
            'type' => 'datetime',
            'default' => 'current_timestamp',
            'nullable' => false,
        ]);

        $table->showDefinition();
    }
}
