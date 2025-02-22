<?php

namespace Lightpack\Transformer;

abstract class AbstractTransformer implements TransformerInterface
{
    /**
     * Transform a collection of items
     */
    public function collection(array $items): array
    {
        return array_map([$this, 'transform'], $items);
    }

    /**
     * Transform a single item
     */
    abstract public function transform($item): array;
}
