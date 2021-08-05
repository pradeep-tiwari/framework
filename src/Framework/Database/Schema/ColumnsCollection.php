<?php

namespace Lightpack\Database\Schema;

class ColumnsCollection
{
    /**
     * @var array Lightpack\Database\Schema\Column
     */
    private $columns = [];

    /**
     * @var string The table operation type as context.
     */
    private $context = 'create';

    public function add(Column $column)
    {
        $this->columns[] = $column;
    }

    public function context(string $context): void
    {
        $this->context = $context;
    }

    public function compile()
    {
        $columns = [];
        $indexes = [];

        foreach ($this->columns as $column) {
            $columns[$column->name()] = $column->compileColumn();

            if($index = $column->compileIndex()) {
                $indexes[] = $index;
            }
        }

        $elements = array_merge($columns, $indexes);

        if($this->context === 'add') {
            foreach($elements as $key => $value) {
                $elements[$key] = "ADD {$value}";
            }
        }

        if($this->context === 'change') {
            foreach($elements as $key => $value) {
                $elements[$key] = "CHANGE {$key} {$value}";
            }
        }

        return implode(', ', $elements);
    }
}
