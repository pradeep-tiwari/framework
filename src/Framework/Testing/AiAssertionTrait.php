<?php

namespace Lightpack\Testing;

use Lightpack\AI\Providers\Fake;
use Lightpack\AI\Tools\ToolExecutor;

trait AiAssertionTrait
{
    /**
     * Tool invocations observed since fakeAi() was called, as
     * ['name' => ..., 'params' => ..., 'result' => ...] entries.
     */
    private array $toolCalls = [];

    /**
     * Swap the default AI driver for a scripted Fake and return it.
     *
     * Call before the code under test resolves ai():
     *
     *     $this->fakeAi(['{"tool": "invite", "params": {"email": "x@y.z"}}', 'Done.']);
     */
    public function fakeAi(array $responses = []): Fake
    {
        ToolExecutor::onInvoke(
            fn (string $name, array $params, mixed $result) =>
            $this->toolCalls[] = ['name' => $name, 'params' => $params, 'result' => $result]
        );

        $manager = app('ai.manager');
        $manager->setDefaultDriver('fake');
        $fake = $manager->driver('fake');
        $fake->respondWith($responses);

        return $fake;
    }

    public function assertToolCalled(string $name): self
    {
        $this->assertNotEmpty(
            $this->callsTo($name),
            "Expected tool '{$name}' to be called, but it was not."
        );

        return $this;
    }

    public function assertToolNotCalled(string $name): self
    {
        $this->assertEmpty(
            $this->callsTo($name),
            "Expected tool '{$name}' not to be called, but it was."
        );

        return $this;
    }

    /**
     * Assert a tool was called with (at least) the given params.
     */
    public function assertToolCalledWith(string $name, array $params): self
    {
        foreach ($this->callsTo($name) as $call) {
            $matches = true;

            foreach ($params as $key => $value) {
                if (! array_key_exists($key, $call['params']) || $call['params'][$key] != $value) {
                    $matches = false;

                    break;
                }
            }

            if ($matches) {
                $this->assertTrue(true);

                return $this;
            }
        }

        $this->fail("Expected tool '{$name}' to be called with given params, but it was not.");

        return $this;
    }

    public function assertAiCalled(): self
    {
        $this->assertNotEmpty(
            $this->fake()->requests(),
            'Expected an AI request, but none were made.'
        );

        return $this;
    }

    /**
     * Assert some recorded AI request contained the given text
     * (searched in prompts, messages, and other params).
     */
    public function assertAiPromptContains(string $text): self
    {
        foreach ($this->fake()->requests() as $params) {
            if (str_contains(json_encode($params), $text)) {
                $this->assertTrue(true);

                return $this;
            }
        }

        $this->fail("No recorded AI request contained: {$text}");

        return $this;
    }

    /**
     * Clear the tool-invocation log.
     */
    public function clearToolCalls(): void
    {
        $this->toolCalls = [];
    }

    private function callsTo(string $name): array
    {
        return array_filter(
            $this->toolCalls,
            fn ($call) => $call['name'] === $name
        );
    }

    private function fake(): Fake
    {
        return app('ai.manager')->driver('fake');
    }
}
