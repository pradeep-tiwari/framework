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

    public const DEFAULT_NULL = 'NULL';
    public const DEFAULT_CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';
    public const ATTRIBUTE_BINARY = 'BINARY';
    public const ATTRIBUTE_UNSIGNED = 'UNSIGNED ZEROFILL';
    public const ATTRIBUTE_UNSIGNED_ZEROFILL = 'BINARY';
    public const ATTRIBUTE_ON_UPDATE_CURRENT_TIMESTAMP = 'ON UPDATE CURRENT_TIMESTAMP';
    public const INDEX_PRIMARY = 'PRIMARY KEY';
    public const INDEX_UNIQUE = 'UNIQUE';
    public const INDEX_INDEX = 'INDEX';
    public const INDEX_FULLTEXT = 'FULLTEXT';
    public const INDEX_SPATIAL = 'SPATIAL';

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
            if($this->default !== 'NULL' && $this->default !== 'CURRENT_TIMESTAMP') {
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
