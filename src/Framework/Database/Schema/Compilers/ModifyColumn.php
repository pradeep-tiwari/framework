<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnsCollection;
use Lightpack\Database\Schema\ForeignsCollection;
use Lightpack\Database\Schema\Table;

class ModifyColumn
{
    public function compile(Table $table)
    {
        $columns = $table->columns();
        $columns->context('change');

        $sql = "ALTER TABLE {$table->name()} ";
        $sql .= $columns->compile();

        if($constraints = $table->keys()->compile()) {
            $sql .= ', ' . $constraints;
        }

        $sql .= ";";

        return $sql;
    }
}
