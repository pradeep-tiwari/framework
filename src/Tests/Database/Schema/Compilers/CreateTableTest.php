<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Table;
use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
{
    public function testCompilerCanCreateTable(): void
    {
        $table = new Table('products');

        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        
        $sql = (new CreateTable)->compile($table);
        
        $expected = 'CREATE TABLE products (id INT AUTO_INCREMENT, PRIMARY KEY (id));';

        $this->assertEquals($expected, $sql);
    }
}
