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
    private $index;
    private $increments = false;
    private $attribute;
    private $collation;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function compile()
    {
        $this->definition = "{$this->column} {$this->type}";

        if ($this->length) {
            $this->definition .= "($this->length)";
        }

        if ($this->attribute) {
            $this->definition .= " {$this->attribute}";
        }

        if ($this->increments) {
            $this->definition .= " AUTO_INCREMENT";
        }

        if ($this->index) {
            $this->definition .= " {$this->index}";
        }

        if ($this->nullable) {
            $this->definition .= " NULL";
        }

        if ($this->default) {
            if($this->default !== 'NULL' || $this->default !== 'CURRENT_TIMESTAMP') {
                $default = "'{$this->default}'";
            } else {
                $default = "{$this->default}";
            }

            $this->definition .= " DEFAULT {$default}";
        }

        return $this->definition;
    }

    public function type(string $type): self
    {
        $this->type = strtoupper($type);

        return $this;
    }
    
    public function length(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function nullable(bool $flag): self
    {
        $this->nullable = $flag;

        return $this;
    }

    public function index(string $index): self
    {
        $this->index = strtoupper($index);

        return $this;
    }

    public function increments(bool $flag): self
    {
        $this->increments = $flag;

        return $this;
    }

    public function attribute(string $attribute): self
    {
        $this->attribute = strtoupper($attribute);

        return $this;
    }

    public function collation(string $collation): self
    {
        $this->collation = $collation;

        return $this;
    }

    public function default(string $default): self
    {
        $this->default = $default;

        return $this;
    }
}
