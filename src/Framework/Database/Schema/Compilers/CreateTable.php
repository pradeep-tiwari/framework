<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\Table;

class CreateTable
{
    public function compile(Table $table)
    {
        $sql = "CREATE TABLE {$table->name()} (";
        $sql .= $table->columns()->compile();

        if($constraints = $table->keys()->compile()) {
            $sql .= ', ' . $constraints;
        }

        $sql .= ");";

        return $sql;
    }
}