<?php

namespace Lightpack\Database\Schema;

class Foreign
{
    private $foreignKey;
    private $referenceTable;
    private $parentColumn;
    private $updateAction;
    private $deleteAction;

    public function __construct(string $foreignKey = null)
    {
        $this->foreignKey = $foreignKey;
    }

    public function references(string $table): self
    {
        $this->referenceTable = $table;

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
}