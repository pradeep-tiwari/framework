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
    private $indexName;
    private $increments = false;
    private $attribute;

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

    public function nullable(): self
    {
        $this->nullable = true;

        return $this;
    }

    public function index(string $index, string $name = null): self
    {
        $this->index = strtoupper($index);

        if ($name) {
            $this->indexName = $name;
        }

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

    public function default(string $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function compileIndex()
    {
        if (!$this->index) {
            return null;
        }

        $index = "{$this->index}";

        if ($this->indexName) {
            $index .= " $this->indexName";
        }

        $index .= " ($this->column)";

        return $index;
    }

    public function compileColumn()
    {
        $column = "{$this->column} {$this->type}";

        if ($this->length) {
            $column .= "($this->length)";
        }

        if ($this->attribute) {
            $column .= " {$this->attribute}";
        }

        if ($this->increments) {
            $column .= " AUTO_INCREMENT";
        }

        if ($this->nullable) {
            $column .= " NULL";
        }

        if ($this->default) {
            if ($this->default !== 'NULL' && $this->default !== 'CURRENT_TIMESTAMP') {
                $default = "'{$this->default}'";
            } else {
                $default = "{$this->default}";
            }

            $column .= " DEFAULT {$default}";
        }

        return $column;
    }
}
