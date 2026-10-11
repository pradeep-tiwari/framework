<?php

use Lightpack\AI\Providers\Fake;
use Lightpack\AI\Tools\ToolExecutor;
use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Config\Config;
use Lightpack\Http\Http;
use PHPUnit\Framework\TestCase;

class FakeProviderTest extends TestCase
{
    private Fake $ai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ai = new Fake(new Http, new Cache(new ArrayDriver), new Config);
        ToolExecutor::onInvoke(null);
    }

    protected function tearDown(): void
    {
        ToolExecutor::onInvoke(null);
        parent::tearDown();
    }

    public function testGenerateReturnsScriptedResponse()
    {
        $this->ai->respondWith(['hello world']);

        $result = $this->ai->generate(['prompt' => 'say hi']);

        $this->assertSame('hello world', $result['text']);
        $this->assertSame('stop', $result['finish_reason']);
        $this->assertSame(['fake' => true], $result['raw']);
    }

    public function testResponsesAreConsumedInOrder()
    {
        $this->ai->respondWith(['first', 'second']);

        $this->assertSame('first', $this->ai->generate([])['text']);
        $this->assertSame('second', $this->ai->generate([])['text']);
    }

    public function testLastResponseRepeatsWhenScriptExhausted()
    {
        $this->ai->respondWith(['only']);

        $this->assertSame('only', $this->ai->generate([])['text']);
        $this->assertSame('only', $this->ai->generate([])['text']);
    }

    public function testRespondWithResetsScriptAndCursor()
    {
        $this->ai->respondWith(['a', 'b']);
        $this->assertSame('a', $this->ai->generate([])['text']);

        $this->ai->respondWith(['c', 'd']);

        $this->assertSame('c', $this->ai->generate([])['text']);
        $this->assertSame('d', $this->ai->generate([])['text']);
    }

    public function testRequestsAreRecordedInOrder()
    {
        $this->ai->respondWith(['x', 'y']);

        $this->ai->generate(['prompt' => 'one']);
        $this->ai->generate(['prompt' => 'two', 'model' => 'm']);

        $requests = $this->ai->requests();

        $this->assertCount(2, $requests);
        $this->assertSame('one', $requests[0]['prompt']);
        $this->assertSame('two', $requests[1]['prompt']);
        $this->assertSame('m', $requests[1]['model']);
    }

    public function testGenerateStreamEmitsScriptedResponse()
    {
        $this->ai->respondWith(['streamed']);

        $chunks = [];
        $this->ai->generateStream(['prompt' => 'x'], function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        $this->assertSame(['streamed'], $chunks);
        $this->assertCount(1, $this->ai->requests());
    }

    public function testEmbedIsDeterministic()
    {
        $a = $this->ai->embed('hello');
        $b = $this->ai->embed('hello');
        $c = $this->ai->embed('different');

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertCount(16, $a);
    }

    public function testEmbedBatchReturnsVectorPerInput()
    {
        $vectors = $this->ai->embed(['one', 'two']);

        $this->assertCount(2, $vectors);
        $this->assertCount(16, $vectors[0]);
        $this->assertSame($this->ai->embed('one'), $vectors[0]);
    }

    public function testEmbedCallsAreRecorded()
    {
        $this->ai->embed('some text');

        $requests = $this->ai->requests();

        $this->assertSame('embed', $requests[0]['type']);
        $this->assertSame('some text', $requests[0]['input']);
    }

    public function testAskReturnsScriptedText()
    {
        $this->ai->respondWith(['42']);

        $this->assertSame('42', $this->ai->ask('meaning of life'));
    }

    public function testExpectDecodesScriptedJson()
    {
        $this->ai->respondWith(['{"title": "Dune", "rating": 9}']);

        $result = $this->ai->task()
            ->prompt('recommend a book')
            ->expect(['title' => 'string', 'rating' => 'int'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertSame('Dune', $result['data']['title']);
        $this->assertSame(9, $result['data']['rating']);
    }

    public function testExpectFailsOnNonJsonResponse()
    {
        $this->ai->respondWith(['definitely not json']);

        $result = $this->ai->task()
            ->prompt('give me json')
            ->expect(['title' => 'string'])
            ->run();

        $this->assertFalse($result['success']);
    }

    public function testToolCallFlowNotifiesInvokeObserver()
    {
        $calls = [];
        ToolExecutor::onInvoke(function ($name, $params, $result) use (&$calls) {
            $calls[] = ['name' => $name, 'params' => $params, 'result' => $result];
        });

        $this->ai->respondWith([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny in Paris',
        ]);

        $result = $this->ai->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny, 22C', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertSame('Sunny in Paris', $result['raw']);
        $this->assertSame(['get_weather'], $result['tools_used']);

        $this->assertCount(1, $calls);
        $this->assertSame('get_weather', $calls[0]['name']);
        $this->assertSame(['city' => 'Paris'], $calls[0]['params']);
        $this->assertSame('sunny, 22C', $calls[0]['result']);
    }

    public function testToolDecisionNoneSkipsInvocation()
    {
        $calls = [];
        ToolExecutor::onInvoke(function ($name, $params, $result) use (&$calls) {
            $calls[] = $name;
        });

        $this->ai->respondWith(['{"tool": "none"}', 'just an answer']);

        $result = $this->ai->task()
            ->prompt('hello')
            ->tool('get_weather', fn ($p) => 'x', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['tools_used']);
        $this->assertSame([], $calls);
    }

    public function testSimilarRanksMatchingContentFirst()
    {
        $items = [
            ['text' => 'php', 'embedding' => $this->ai->embed('php')],
            ['text' => 'java', 'embedding' => $this->ai->embed('java')],
        ];

        $results = $this->ai->similar($this->ai->embed('php'), $items);

        $this->assertSame('php', $results[0]['item']['text']);
        $this->assertEqualsWithDelta(1.0, $results[0]['similarity'], 0.0001);
    }
}
