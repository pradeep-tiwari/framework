<?php

namespace Lightpack\Database\Schema\Compilers;

use Lightpack\Database\Schema\ColumnsCollection;
use Lightpack\Database\Schema\ForeignsCollection;

class Truncate
{
    public function compile(string $table): string
    {
       return "TRUNCATE {$table};";
    }
}
