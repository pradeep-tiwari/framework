<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnsCollection;

class Create
{
    public function compile(string $table, ColumnsCollection $columns)
    {
        $sql = "CREATE TABLE {$table} (";
        $sql .= $columns->compile();
        $sql .= ");";

        return $sql;
    }
}
