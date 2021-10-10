<?php

namespace Lightpack\Database\Schema;

class Key
{
    private $foreignKey;
    private $parentTable;
    private $parentColumn;
    private $updateAction;
    private $deleteAction;

    public const ACTION_CASCADE = 'CASCADE';
    public const ACTION_RESTRICT = 'RESTRICT';
    public const ACTION_SET_NULL = 'SET_NULL';
    public const ACTION_NONE = 'NO ACTION';

    public function __construct(string $foreignKey = null)
    {
        $this->foreignKey = $foreignKey;
        $this->updateAction = self::ACTION_RESTRICT;
        $this->deleteAction = self::ACTION_RESTRICT;
    }

    public function references(string $table): self
    {
        $this->parentTable = $table;

        return $this;
    }

    public function on(string $column): self
    {
        $this->parentColumn = $column;

        return $this;
    }

    public function update(string $action): self
    {
        $this->updateAction = $action;

        return $this;
    }

    public function delete(string $action): self
    {
        $this->deleteAction = $action;

        return $this;
    }

    public function compile()
    {
        $constraint[] = "FOREIGN KEY ({$this->foreignKey})";
        $constraint[] = "REFERENCES {$this->parentTable}({$this->parentColumn})";

        if ($this->deleteAction) {
            $constraint[] = "ON DELETE {$this->deleteAction}";
        }

        if ($this->updateAction) {
            $constraint[] = "ON UPDATE {$this->updateAction}";
        }

        return implode(' ', $constraint);
    }
}
