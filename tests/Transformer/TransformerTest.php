<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Lightpack\Transformer\AbstractTransformer;
use DateTime;

class TransformerTest extends TestCase
{
    private $transformer;
    private $nestedTransformer;

    protected function setUp(): void
    {
        // Create a transformer for nested items
        $this->nestedTransformer = new class extends AbstractTransformer {
            public function transform($item): array 
            {
                return [
                    'type' => $item->type,
                    'value' => $item->value,
                ];
            }
        };

        // Main transformer that includes nested data
        $this->transformer = new class extends AbstractTransformer {
            private $nestedTransformer;

            public function setNestedTransformer($transformer) {
                $this->nestedTransformer = $transformer;
                return $this;
            }

            public function transform($item): array 
            {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'timestamp' => $item->timestamp->format('Y-m-d H:i:s'),
                    'nested' => $this->include($item->nested, $this->nestedTransformer),
                    'collection' => $this->include($item->collection, $this->nestedTransformer),
                ];
            }
        };

        $this->transformer->setNestedTransformer($this->nestedTransformer);
    }

    public function testTransformSingleItem()
    {
        // Create nested items
        $nested = new class {
            public $type = 'detail';
            public $value = 'test value';
        };

        $collection = [
            new class {
                public $type = 'tag';
                public $value = 'first';
            },
            new class {
                public $type = 'tag';
                public $value = 'second';
            },
        ];

        // Create a mock item with nested data
        $item = new class {
            public $id = 1;
            public $name = 'Test Item';
            public $timestamp;
            public $nested;
            public $collection;

            public function __construct() {
                $this->timestamp = new DateTime('2025-01-01 10:00:00');
            }
        };
        $item->nested = $nested;
        $item->collection = $collection;

        $expected = [
            'id' => 1,
            'name' => 'Test Item',
            'timestamp' => '2025-01-01 10:00:00',
            'nested' => [
                'type' => 'detail',
                'value' => 'test value',
            ],
            'collection' => [
                [
                    'type' => 'tag',
                    'value' => 'first',
                ],
                [
                    'type' => 'tag',
                    'value' => 'second',
                ],
            ],
        ];

        $result = $this->transformer->transform($item);
        $this->assertEquals($expected, $result);
    }

    public function testNullValues()
    {
        // Create item with null values
        $item = new class {
            public $id = 1;
            public $name = 'Test Item';
            public $timestamp;
            public $nested = null;
            public $collection = null;

            public function __construct() {
                $this->timestamp = new DateTime('2025-01-01 10:00:00');
            }
        };

        $expected = [
            'id' => 1,
            'name' => 'Test Item',
            'timestamp' => '2025-01-01 10:00:00',
            'nested' => null,
            'collection' => null,
        ];

        $result = $this->transformer->transform($item);
        $this->assertEquals($expected, $result);
    }

    public function testNullCollection()
    {
        $result = $this->transformer->collection(null);
        $this->assertEquals([], $result);
    }
}
