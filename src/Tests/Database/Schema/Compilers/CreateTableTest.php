<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\RenameColumn;
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

    public function testCompilerCanAddForeignKey(): void
    {
        $table = new Table('products');

        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        $table->column('category_id')->type('int');
        $table->column('title')->type('varchar')->length(55);
        $table->key('category_id')->references('categories')->on('id');
        
        $sql = (new CreateTable)->compile($table);
        
        $expected = 'CREATE TABLE products (id INT AUTO_INCREMENT, category_id INT, title VARCHAR(55), PRIMARY KEY (id), FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE RESTRICT);';

        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanRenameColumnSql()
    {
        $table = new Table('products');

        $table->renameColumn('title', 'heading');
        
        $sql = (new RenameColumn)->compile($table);

        
        $expected = 'ALTER TABLE products RENAME COLUMN title TO heading;';

        $this->assertEquals($expected, $sql);
    }

    public function testcompilerCanChangeColumnSql()
    {
        $table = new Table('products');

        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        
        $sql = (new ModifyColumn)->compile($table);
        
        $expected = 'ALTER TABLE products CHANGE id id INT AUTO_INCREMENT;';

        $this->assertEquals($expected, $sql);
    }
}
