<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnsCollection;
use Lightpack\Database\Schema\ForeignsCollection;

class Change
{
    public function compile(string $table, ColumnsCollection $columns, ForeignsCollection $foreigns)
    {
        $columns->context('change');

        $sql = "ALTER TABLE {$table} ";
        $sql .= $columns->compile();

        if($constraints = $foreigns->compile()) {
            $sql .= ', ' . $constraints;
        }

        $sql .= ";";

        return $sql;
    }
}
