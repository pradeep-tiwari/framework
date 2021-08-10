<?php

namespace Lightpack\Database\Adapters;

use Lightpack\Database\Pdo;

class Pgsql extends Pdo
{
    public function __construct(array $args)
    {
        $dsn = "pgsql:host={$args['host']};port={$args['port']};dbname={$args['database']}";
        parent::__construct($dsn, $args['username'], $args['password'], $args['options']);
    }
}