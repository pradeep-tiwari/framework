<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Lightpack\Transformer\AbstractTransformer;
use DateTime;

class TransformerTest extends TestCase
{
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new class extends AbstractTransformer {
            public function transform($item): array 
            {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'timestamp' => $item->timestamp->format('Y-m-d H:i:s'),
                ];
            }
        };
    }

    public function testTransformSingleItem()
    {
        // Create a mock item
        $item = new class {
            public $id = 1;
            public $name = 'Test Item';
            public $timestamp;

            public function __construct() {
                $this->timestamp = new DateTime('2025-01-01 10:00:00');
            }
        };

        $expected = [
            'id' => 1,
            'name' => 'Test Item',
            'timestamp' => '2025-01-01 10:00:00',
        ];

        $result = $this->transformer->transform($item);
        $this->assertEquals($expected, $result);
    }

    public function testTransformCollection()
    {
        // Create mock items
        $item1 = new class {
            public $id = 1;
            public $name = 'Item One';
            public $timestamp;

            public function __construct() {
                $this->timestamp = new DateTime('2025-01-01 10:00:00');
            }
        };

        $item2 = new class {
            public $id = 2;
            public $name = 'Item Two';
            public $timestamp;

            public function __construct() {
                $this->timestamp = new DateTime('2025-01-02 11:00:00');
            }
        };

        $items = [$item1, $item2];

        $expected = [
            [
                'id' => 1,
                'name' => 'Item One',
                'timestamp' => '2025-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Item Two',
                'timestamp' => '2025-01-02 11:00:00',
            ],
        ];

        $result = $this->transformer->collection($items);
        $this->assertEquals($expected, $result);
    }
}
