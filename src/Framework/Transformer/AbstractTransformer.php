<?php

namespace Lightpack\Transformer;

abstract class AbstractTransformer implements TransformerInterface
{
    /**
     * Relations to include in transformation
     */
    protected $includes = [];

    /**
     * Data groups to include
     */
    protected $groups = ['basic'];

    /**
     * Set relations to include in transformation
     */
    public function with(array $includes): self
    {
        $this->includes = $includes;
        return $this;
    }

    /**
     * Set data groups to include
     */
    public function groups(array $groups): self
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Check if a relation should be included
     */
    protected function shouldInclude(string $relation): bool
    {
        return empty($this->includes) || in_array($relation, $this->includes);
    }

    /**
     * Check if a field belongs to current groups
     */
    protected function inGroups(array $groups): bool
    {
        return count(array_intersect($this->groups, $groups)) > 0;
    }

    /**
     * Transform a collection of items
     */
    public function collection(?array $items): array
    {
        if ($items === null) {
            return [];
        }
        
        return array_map([$this, 'transform'], $items);
    }

    /**
     * Transform a single item
     */
    abstract public function transform($item): array;
}
