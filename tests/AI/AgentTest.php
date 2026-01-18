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
        $mockTaskBuilder = $this->createMockTaskBuilder();
        
        $this->mockAI->expects($this->once())
            ->method('ask')
            ->willReturn('Answer without tools');
        
        $answer = $this->agent->ask('What is 2+2?');
        
        $this->assertEquals('Answer without tools', $answer);
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
        
        $answer = $this->agent->ask('What is 2+2?');
        
        $this->assertEquals('The answer is 4', $answer);
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
        
        $answer = $this->agent->ask('Test error handling');
        
        $this->assertEquals('Handled error gracefully', $answer);
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
    
    private function createMockTaskBuilder(?array $runResult = null)
    {
        $mockTaskBuilder = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['prompt', 'expect', 'run'])
            ->getMock();
        
        $mockTaskBuilder->method('prompt')->willReturnSelf();
        $mockTaskBuilder->method('expect')->willReturnSelf();
        
        if ($runResult !== null) {
            $mockTaskBuilder->method('run')->willReturn($runResult);
        }
        
        return $mockTaskBuilder;
    }
}
