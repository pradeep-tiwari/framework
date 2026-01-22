<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\TaskBuilder;

/**
 * Integration test for TaskBuilder parameter schema handling.
 * Tests the actual coerceSchemaOnObject() logic that was broken in production.
 */
class TaskBuilderParameterTest extends TestCase
{
    public function testParameterSchemaWithDescriptions()
    {
        // Create a mock provider that returns JSON with the expected schema
        $mockProvider = $this->createMock(\Lightpack\AI\AI::class);
        $mockProvider->method('generate')
            ->willReturn([
                'text' => json_encode([
                    'tools' => ['search_products'],
                    'reasoning' => 'Need to search',
                    'parameters' => [
                        'search_products' => [
                            'query' => 'laptops',
                            'max_price' => 1000
                        ]
                    ]
                ])
            ]);
        
        $builder = new TaskBuilder($mockProvider);
        
        // Use parameter schema with [type, description] format
        $result = $builder
            ->prompt('Find laptops under $1000')
            ->expect([
                'tools' => ['array', 'List of tools to use'],
                'reasoning' => ['string', 'Explanation of tool selection'],
                'parameters' => ['object', 'Parameters for each tool']
            ])
            ->run();
        
        // Should succeed without type errors
        $this->assertTrue($result['success'], 'TaskBuilder should handle [type, description] format');
        $this->assertIsArray($result['data']['tools']);
        $this->assertIsString($result['data']['reasoning']);
        $this->assertEquals(['search_products'], $result['data']['tools']);
    }
    
    public function testParameterSchemaWithSimpleTypes()
    {
        $mockProvider = $this->createMock(\Lightpack\AI\AI::class);
        $mockProvider->method('generate')
            ->willReturn([
                'text' => json_encode([
                    'name' => 'John',
                    'age' => 30
                ])
            ]);
        
        $builder = new TaskBuilder($mockProvider);
        
        // Use simple type format (backward compatibility)
        $result = $builder
            ->prompt('Get user info')
            ->expect([
                'name' => 'string',
                'age' => 'int'
            ])
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertEquals('John', $result['data']['name']);
        $this->assertEquals(30, $result['data']['age']);
    }
}
