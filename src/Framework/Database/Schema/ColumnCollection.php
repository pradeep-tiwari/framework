<?php

namespace Lightpack\Database\Schema;

class ColumnCollection
{
    /**
     * @var array Lightpack\Database\Schema\Column
     */
    private $columns = [];

    public function add(Column $column)
    {
        $this->columns[] = $column;
    }

    public function compile()
    {
        $sql = [];

        foreach ($this->columns as $column) {
            $sql[] = $column->compile();
        }

        return implode(', ', $sql);
    }
}
