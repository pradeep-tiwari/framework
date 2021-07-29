<?php

namespace Lightpack\Database\Schema\Compilers;

class Create
{
    public function compile()
    {
        return 'CREATE TABLE table_name';
    }
}