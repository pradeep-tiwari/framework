<?php

namespace Lightpack\Transformer;

interface TransformerInterface
{
    /**
     * Transform a single item
     */
    public function transform($item): array;

    /**
     * Transform a collection of items
     */
    public function collection(array $items): array;
}
