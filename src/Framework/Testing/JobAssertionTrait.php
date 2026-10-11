<?php

namespace Lightpack\Testing;

use Lightpack\Jobs\Connection;
use Lightpack\Jobs\Engines\FakeEngine;

trait JobAssertionTrait
{
    /**
     * Swap the job engine for a recording FakeEngine and return it.
     * Dispatched jobs are recorded, not executed.
     */
    protected function fakeJobs(): FakeEngine
    {
        $engine = new FakeEngine;
        Connection::setEngine($engine);

        return $engine;
    }

    public function assertJobDispatched(string $jobClass): self
    {
        $this->assertNotEmpty(
            $this->dispatchedJobs($jobClass),
            "Expected job '{$jobClass}' to be dispatched, but it was not."
        );

        return $this;
    }

    public function assertJobNotDispatched(string $jobClass): self
    {
        $this->assertEmpty(
            $this->dispatchedJobs($jobClass),
            "Expected job '{$jobClass}' not to be dispatched, but it was."
        );

        return $this;
    }

    /**
     * Assert a job was dispatched with (at least) the given payload keys.
     */
    public function assertJobDispatchedWith(string $jobClass, array $payload): self
    {
        foreach ($this->dispatchedJobs($jobClass) as $job) {
            $matches = true;

            foreach ($payload as $key => $value) {
                if (! array_key_exists($key, $job['payload']) || $job['payload'][$key] != $value) {
                    $matches = false;

                    break;
                }
            }

            if ($matches) {
                $this->assertTrue(true);

                return $this;
            }
        }

        $this->fail("Expected job '{$jobClass}' to be dispatched with given payload, but it was not.");

        return $this;
    }

    public function assertJobCount(int $count): self
    {
        $this->assertCount(
            $count,
            $this->dispatchedJobs(),
            "Expected {$count} jobs to be dispatched, but " . count($this->dispatchedJobs()) . ' were.'
        );

        return $this;
    }

    public function assertNoJobsDispatched(): self
    {
        $this->assertEmpty(
            $this->dispatchedJobs(),
            'Expected no jobs to be dispatched, but some were.'
        );

        return $this;
    }

    private function dispatchedJobs(?string $jobClass = null): array
    {
        $engine = Connection::getJobEngine();

        if (! $engine instanceof FakeEngine) {
            $this->fail('Job engine is not faked. Call $this->fakeJobs() first.');
        }

        /** @var FakeEngine $engine */
        $jobs = $engine->dispatched();

        if ($jobClass === null) {
            return $jobs;
        }

        return array_filter($jobs, fn ($job) => $job['handler'] === $jobClass);
    }
}
