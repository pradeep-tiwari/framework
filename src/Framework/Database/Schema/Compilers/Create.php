<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnCollection;

class Create
{
    public function compile(string $table, ColumnCollection $columns)
    {
        $sql = "CREATE TABLE {$table} (";
        $sql .= $columns->compile();
        $sql .= ");";

        return $sql;
    }
}