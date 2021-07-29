<?php

namespace Lightpack\Database\Schema;

class ColumnsCollection
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
        $columns = [];
        $indexes = [];

        foreach ($this->columns as $column) {
            $columns[] = $column->compileColumn();

            if($index = $column->compileIndex()) {
                $indexes[] = $index;
            }
        }

        return implode(', ', array_merge($columns, $indexes));
    }
}
