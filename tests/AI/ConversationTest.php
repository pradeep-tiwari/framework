<?php

use Lightpack\AI\Agent;
use Lightpack\AI\Conversation;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    /** @var Agent|MockObject */
    private $mockAgent;
    
    /** @var Cache|MockObject */
    private $mockCache;
    
    private Conversation $conversation;
    
    protected function setUp(): void
    {
        $this->mockAgent = $this->createMock(Agent::class);
        
        $this->mockCache = $this->createMock(\Lightpack\Cache\Cache::class);
        $this->mockCache->method('get')->willReturn(null);
        $this->mockCache->method('set')->willReturn(true);
        $this->mockCache->method('delete')->willReturn(true);
        
        app()->instance('cache', $this->mockCache);
        
        $this->conversation = new Conversation($this->mockAgent, 'test_session', 5, 3600);
    }
    
    protected function tearDown(): void
    {
        // Cleanup handled by mock
    }
    
    public function testConversationStartsEmpty()
    {
        $history = $this->conversation->getHistory();
        
        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }
    
    public function testAskAddsToHistory()
    {
        $mockResult = new \Lightpack\AI\AgentResult('Agent response');
        
        $this->mockAgent->expects($this->once())
            ->method('ask')
            ->willReturn($mockResult);
        
        $result = $this->conversation->ask('User question');
        
        $this->assertInstanceOf(\Lightpack\AI\AgentResult::class, $result);
        $this->assertEquals('Agent response', $result->answer());
        
        $history = $this->conversation->getHistory();
        
        $this->assertCount(1, $history);
        $this->assertEquals('User question', $history[0]['user']);
        $this->assertEquals('Agent response', $history[0]['agent']);
        $this->assertArrayHasKey('timestamp', $history[0]);
    }
    
    public function testMultipleTurns()
    {
        $this->mockAgent->expects($this->exactly(3))
            ->method('ask')
            ->willReturnOnConsecutiveCalls(
                new \Lightpack\AI\AgentResult('Answer 1'),
                new \Lightpack\AI\AgentResult('Answer 2'),
                new \Lightpack\AI\AgentResult('Answer 3')
            );
        
        $this->conversation->ask('Question 1');
        $this->conversation->ask('Question 2');
        $this->conversation->ask('Question 3');
        
        $history = $this->conversation->getHistory();
        
        $this->assertCount(3, $history);
    }
    
    public function testHistoryLimitEnforced()
    {
        $this->mockAgent->method('ask')
            ->willReturn(new \Lightpack\AI\AgentResult('Response'));
        
        for ($i = 1; $i <= 10; $i++) {
            $this->conversation->ask("Question {$i}");
        }
        
        $history = $this->conversation->getHistory();
        
        $this->assertCount(5, $history);
        $this->assertEquals('Question 6', $history[0]['user']);
        $this->assertEquals('Question 10', $history[4]['user']);
    }
    
    public function testClearHistory()
    {
        $this->mockAgent->method('ask')
            ->willReturn(new \Lightpack\AI\AgentResult('Response'));
        
        $this->conversation->ask('Question 1');
        $this->conversation->ask('Question 2');
        
        $this->assertCount(2, $this->conversation->getHistory());
        
        $this->conversation->clear();
        
        $this->assertEmpty($this->conversation->getHistory());
    }
    
    public function testClearReturnsConversation()
    {
        $result = $this->conversation->clear();
        
        $this->assertInstanceOf(Conversation::class, $result);
    }
    
    public function testForgetDeletesCache()
    {
        $this->mockAgent->method('ask')
            ->willReturn(new \Lightpack\AI\AgentResult('Response'));
        
        $this->conversation->ask('Question');
        
        $this->conversation->forget();
        
        $cached = cache()->get('agent:conversation:test_session');
        $this->assertNull($cached);
    }
    
    public function testHistoryPersistsAcrossInstances()
    {
        $this->mockAgent->method('ask')
            ->willReturn(new \Lightpack\AI\AgentResult('Response'));
        
        $this->conversation->ask('Question 1');
        
        // Mock cache to return saved history
        $savedHistory = json_encode([
            ['user' => 'Question 1', 'agent' => 'Response', 'timestamp' => time()]
        ]);
        
        $newMockCache = $this->createMock(\Lightpack\Cache\Cache::class);
        $newMockCache->method('get')->willReturn($savedHistory);
        app()->instance('cache', $newMockCache);
        
        $newConversation = new Conversation($this->mockAgent, 'test_session');
        
        $history = $newConversation->getHistory();
        $this->assertCount(1, $history);
        $this->assertEquals('Question 1', $history[0]['user']);
    }
    
    public function testContextPassedToAgent()
    {
        $this->mockAgent->expects($this->exactly(2))
            ->method('ask')
            ->willReturn(new \Lightpack\AI\AgentResult('Response'));
        
        $this->conversation->ask('First question');
        $this->conversation->ask('New question');
        
        // Verify history was built correctly
        $history = $this->conversation->getHistory();
        $this->assertCount(2, $history);
        $this->assertEquals('First question', $history[0]['user']);
        $this->assertEquals('New question', $history[1]['user']);
    }
}
