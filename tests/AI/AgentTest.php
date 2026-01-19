<?php

use Lightpack\AI\Agent;
use Lightpack\AI\AI;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    /** @var AI|MockObject */
    private $mockAI;
    private Agent $agent;
    
    protected function setUp(): void
    {
        $this->mockAI = $this->createMock(AI::class);
        $this->agent = new Agent($this->mockAI);
    }
    
    public function testCanRegisterTool()
    {
        $this->agent->tool('test_tool', fn($q) => 'result');
        
        $this->assertContains('test_tool', $this->agent->getTools());
    }
    
    public function testCanRegisterMultipleTools()
    {
        $this->agent->tool('tool1', fn($q) => 'result1');
        $this->agent->tool('tool2', fn($q) => 'result2');
        $this->agent->tool('tool3', fn($q) => 'result3');
        
        $tools = $this->agent->getTools();
        
        $this->assertCount(3, $tools);
        $this->assertContains('tool1', $tools);
        $this->assertContains('tool2', $tools);
        $this->assertContains('tool3', $tools);
    }
    
    public function testToolRegistrationReturnsAgent()
    {
        $result = $this->agent->tool('test', fn($q) => 'result');
        
        $this->assertInstanceOf(Agent::class, $result);
    }
    
    public function testCanChainToolRegistrations()
    {
        $this->agent
            ->tool('tool1', fn($q) => 'result1')
            ->tool('tool2', fn($q) => 'result2')
            ->tool('tool3', fn($q) => 'result3');
        
        $this->assertCount(3, $this->agent->getTools());
    }
    
    public function testAskWithNoTools()
    {
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Answer without tools');
        
        $result = $this->agent->ask('What is 2+2?');
        
        $this->assertInstanceOf(\Lightpack\AI\AgentResult::class, $result);
        $this->assertEquals('Answer without tools', $result->answer());
        $this->assertEmpty($result->toolsUsed());
    }
    
    public function testAskWithTools()
    {
        $this->agent->tool('calculator', function($query) {
            return ['result' => 4];
        }, 'Performs calculations');
        
        $mockTaskBuilder = $this->createMockTaskBuilder([
            'tools' => ['calculator'],
            'reasoning' => 'Need to calculate'
        ]);
        
        $this->mockAI->expects($this->once())
            ->method('task')
            ->willReturn($mockTaskBuilder);
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('The answer is 4');
        
        $result = $this->agent->ask('What is 2+2?');
        
        $this->assertInstanceOf(\Lightpack\AI\AgentResult::class, $result);
        $this->assertEquals('The answer is 4', $result->answer());
        $this->assertContains('calculator', $result->toolsUsed());
        $this->assertEquals(['result' => 4], $result->toolResult('calculator'));
    }
    
    public function testToolExecutionHandlesErrors()
    {
        $this->agent->tool('failing_tool', function($query) {
            throw new \Exception('Tool failed');
        });
        
        $mockTaskBuilder = $this->createMockTaskBuilder([
            'tools' => ['failing_tool'],
            'reasoning' => 'Using failing tool'
        ]);
        
        $this->mockAI->expects($this->once())
            ->method('task')
            ->willReturn($mockTaskBuilder);
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Handled error gracefully');
        
        $result = $this->agent->ask('Test error handling');
        
        $this->assertEquals('Handled error gracefully', $result->answer());
        $this->assertArrayHasKey('error', $result->toolResult('failing_tool'));
    }
    
    public function testConversationCreation()
    {
        // Mock cache for conversation
        $mockCache = $this->createMock(\Lightpack\Cache\Cache::class);
        $mockCache->method('get')->willReturn(null);
        
        app()->instance('cache', $mockCache);
        
        $conversation = $this->agent->conversation('session_123');
        
        $this->assertInstanceOf(\Lightpack\AI\Conversation::class, $conversation);
    }
    
    public function testToolWithParameters()
    {
        $this->agent->tool('search', function($params) {
            return [
                'category' => $params['category'],
                'max_price' => $params['max_price']
            ];
        }, 'Search products', [
            'category' => ['string', 'Product category'],
            'max_price' => ['number', 'Maximum price']
        ]);
        
        $mockTaskBuilder = $this->createMockTaskBuilder([
            'tools' => ['search'],
            'parameters' => [
                'search' => ['category' => 'laptops', 'max_price' => 1000]
            ],
            'reasoning' => 'Search with parameters'
        ]);
        
        $this->mockAI->expects($this->once())
            ->method('task')
            ->willReturn($mockTaskBuilder);
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Found laptops under $1000');
        
        $result = $this->agent->ask('Find laptops under $1000');
        
        $this->assertEquals('Found laptops under $1000', $result->answer());
        $toolResult = $result->toolResult('search');
        $this->assertEquals('laptops', $toolResult['category']);
        $this->assertEquals(1000, $toolResult['max_price']);
    }
    
    public function testTemperatureControl()
    {
        $this->agent->tool('helper', fn($q) => 'tool result', 'Helper tool');
        $this->agent->temperature(0.7);
        
        $mockTaskBuilder = $this->createMockTaskBuilder([
            'tools' => ['helper'],
            'reasoning' => 'Using helper'
        ]);
        
        $mockTaskBuilder->expects($this->once())
            ->method('temperature')
            ->with(0.7)
            ->willReturnSelf();
        
        $this->mockAI->expects($this->once())
            ->method('task')
            ->willReturn($mockTaskBuilder);
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Creative answer');
        
        $result = $this->agent->ask('Be creative');
        
        $this->assertEquals('Creative answer', $result->answer());
    }
    
    public function testSystemPrompt()
    {
        $this->agent->tool('helper', fn($q) => 'tool result', 'Helper tool');
        $this->agent->system('You are a helpful assistant. Be concise.');
        
        $mockTaskBuilder = $this->createMockTaskBuilder([
            'tools' => ['helper'],
            'reasoning' => 'Using helper'
        ]);
        
        $this->mockAI->expects($this->once())
            ->method('task')
            ->willReturn($mockTaskBuilder);
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Concise answer');
        
        $result = $this->agent->ask('Help me');
        
        $this->assertEquals('Concise answer', $result->answer());
    }
    
    public function testAgentResultToString()
    {
        $result = new \Lightpack\AI\AgentResult('Test answer');
        
        $this->assertEquals('Test answer', (string) $result);
    }
    
    public function testAgentResultToArray()
    {
        $result = new \Lightpack\AI\AgentResult(
            'Answer',
            ['tool1' => 'result1'],
            ['tool1'],
            'Reasoning'
        );
        
        $array = $result->toArray();
        
        $this->assertEquals('Answer', $array['answer']);
        $this->assertEquals(['tool1' => 'result1'], $array['tool_results']);
        $this->assertEquals(['tool1'], $array['tools_used']);
        $this->assertEquals('Reasoning', $array['reasoning']);
    }
    
    public function testAgentResultUsedTool()
    {
        $result = new \Lightpack\AI\AgentResult(
            'Answer',
            ['search' => 'results'],
            ['search', 'filter']
        );
        
        $this->assertTrue($result->usedTool('search'));
        $this->assertTrue($result->usedTool('filter'));
        $this->assertFalse($result->usedTool('other'));
    }
    
    private function createMockTaskBuilder(?array $runResult = null)
    {
        $mockTaskBuilder = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['prompt', 'expect', 'run', 'temperature'])
            ->getMock();
        
        $mockTaskBuilder->method('prompt')->willReturnSelf();
        $mockTaskBuilder->method('expect')->willReturnSelf();
        $mockTaskBuilder->method('temperature')->willReturnSelf();
        
        if ($runResult !== null) {
            $mockTaskBuilder->method('run')->willReturn($runResult);
        }
        
        return $mockTaskBuilder;
    }
}
