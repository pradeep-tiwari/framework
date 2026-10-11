<?php

namespace Lightpack\Tests\Testing;

use Lightpack\AI\AiProvider;
use Lightpack\AI\Providers\Fake;
use Lightpack\AI\Tools\ToolExecutor;
use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\ArrayDriver;
use Lightpack\Config\Config;
use Lightpack\Container\Container;
use Lightpack\Testing\AiAssertionTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class AiAssertionTraitTest extends TestCase
{
    use AiAssertionTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $container->register('cache', fn () => new Cache(new ArrayDriver));
        $container->register('config', fn () => new Config);
        (new AiProvider)->register($container);

        ToolExecutor::flushRecordedCalls();
    }

    protected function tearDown(): void
    {
        ToolExecutor::flushRecordedCalls();
        Container::getInstance()->reset();
        parent::tearDown();
    }

    public function testFakeAiReturnsAndInstallsFakeDriver()
    {
        $fake = $this->fakeAi();

        $this->assertInstanceOf(Fake::class, $fake);
        $this->assertSame($fake, ai());
    }

    public function testScriptedResponsesFlowThroughAiHelper()
    {
        $this->fakeAi(['42']);

        $this->assertSame('42', ai()->ask('meaning of life'));
    }

    public function testAssertAiCalled()
    {
        $this->fakeAi(['answer']);

        ai()->ask('a question');

        $this->assertAiCalled();
    }

    public function testAssertAiCalledFailsWithoutRequests()
    {
        $this->fakeAi();

        $this->expectException(AssertionFailedError::class);

        $this->assertAiCalled();
    }

    public function testAssertAiPromptContains()
    {
        $this->fakeAi(['answer']);

        ai()->ask('what is the meaning of php');

        $this->assertAiPromptContains('meaning of php');
    }

    public function testAssertAiPromptContainsFailsWhenAbsent()
    {
        $this->fakeAi(['answer']);

        ai()->ask('what is the meaning of php');

        $this->expectException(AssertionFailedError::class);

        $this->assertAiPromptContains('quantum physics');
    }

    public function testAssertToolCalled()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertToolCalled('get_weather');
    }

    public function testAssertToolCalledWith()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertToolCalledWith('get_weather', ['city' => 'Paris']);
    }

    public function testAssertToolCalledWithFailsOnMismatch()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->expectException(AssertionFailedError::class);

        $this->assertToolCalledWith('get_weather', ['city' => 'London']);
    }

    public function testAssertToolNotCalled()
    {
        $this->fakeAi(['{"tool": "none"}', 'no tool needed']);

        ai()->task()
            ->prompt('hello')
            ->tool('get_weather', fn ($p) => 'x', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertToolNotCalled('get_weather');
    }

    public function testAssertToolNotCalledFailsWhenToolWasCalled()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->expectException(AssertionFailedError::class);

        $this->assertToolNotCalled('get_weather');
    }

    public function testClearToolCalls()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->clearToolCalls();

        $this->assertSame([], ToolExecutor::recordedCalls());
    }

    public function testFluentChaining()
    {
        $this->fakeAi([
            '{"tool": "get_weather", "params": {"city": "Paris"}}',
            'Sunny.',
        ]);

        ai()->task()
            ->prompt('weather in Paris?')
            ->tool('get_weather', fn ($p) => 'sunny', 'Get weather', ['city' => ['string', 'City']])
            ->run();

        $this->assertAiCalled()
            ->assertToolCalled('get_weather')
            ->assertToolCalledWith('get_weather', ['city' => 'Paris'])
            ->assertToolNotCalled('send_email');
    }
}
