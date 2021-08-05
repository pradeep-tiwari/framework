<?php

namespace Lightpack\Database\Schema;

class Column
{
    private $columnName;
    private $columnType;
    private $columnLength;
    private $columnDefaultValue;
    private $columnIsNullable;
    private $columnIndexType;
    private $columnIndexName;
    private $columnIncrements = false;
    private $columnAttribute;

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

    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
    }

    public function type(string $columnType): self
    {
        $this->columnType = strtoupper($columnType);

        return $this;
    }

    public function length(int $columnLength): self
    {
        $this->columnLength = $columnLength;

        return $this;
    }

    public function nullable(): self
    {
        $this->columnIsNullable = true;

        return $this;
    }

    public function index(string $indexType, string $indexName = null): self
    {
        $this->columnIndexType = strtoupper($indexType);

        if ($indexName) {
            $this->columnIndexName = $indexName;
        }

        return $this;
    }

    public function increments(): self
    {
        $this->columnIncrements = true;

        return $this;
    }

    public function attribute(string $columnAttribute): self
    {
        $this->columnAttribute = strtoupper($columnAttribute);

        return $this;
    }

    public function default(string $value): self
    {
        $this->columnDefaultValue = $value;

        return $this;
    }

    public function compileIndex()
    {
        if (!$this->columnIndexType) {
            return null;
        }

        $index = "{$this->columnIndexType}";

        if ($this->columnIndexName) {
            $index .= " $this->columnIndexName";
        }

        $index .= " ($this->columnName)";

        return $index;
    }

    public function compileColumn()
    {
        $column = "{$this->columnName} {$this->columnType}";

        if ($this->columnLength) {
            $column .= "($this->columnLength)";
        }

        if ($this->columnAttribute) {
            $column .= " {$this->columnAttribute}";
        }

        if ($this->columnIncrements) {
            $column .= " AUTO_INCREMENT";
        }

        if ($this->columnIsNullable) {
            $column .= " NULL";
        }

        if ($this->columnDefaultValue) {
            if ($this->columnDefaultValue !== 'NULL' && $this->columnDefaultValue !== 'CURRENT_TIMESTAMP') {
                $default = "'{$this->columnDefaultValue}'";
            } else {
                $default = "{$this->columnDefaultValue}";
            }

            $column .= " DEFAULT {$default}";
        }

        return $column;
    }

    public function name()
    {
        return $this->columnName;
    }
}
