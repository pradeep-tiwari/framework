<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnsCollection;
use Lightpack\Database\Schema\ForeignsCollection;

class Drop
{
    public function compile(string $table, string ...$columns): string
    {
        $sql = "ALTER TABLE {$table}";

        foreach($columns as $column) {
            $sql .= " DROP {$column},";
        }

        return rtrim($sql, ',') . ";";
    }
}
