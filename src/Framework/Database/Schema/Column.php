<?php

namespace Lightpack\Database\Schema;

use RuntimeException;

class Column
{
    private $column;
    private $type;
    private $length;
    private $values;
    private $default;
    private $nullable;

    public function __construct(string $column, string $type, array $options = [])
    {
        $this->column = $column;
        $this->type = strtoupper($type);

        $this->setOptions($options);
    }

    public function compile()
    {
        $this->definition = "{$this->column} {$this->type}";

        if ($this->length) {
            $this->definition .= "($this->length)";
        }

        return $this->definition;
    }

    private function setOptions(array $options = [])
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'length':
                    $this->length = $value;
                    break;
                case 'values':
                    $this->values = $value;
                    break;
                default:
                    throw new RuntimeException("Invalid column option {$key}");
            }
        }
    }
}
