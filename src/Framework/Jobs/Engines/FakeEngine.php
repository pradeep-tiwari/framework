<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Throwable;

/**
 * Records dispatched jobs without executing them, for testing.
 *
 * Swap in via Connection::setEngine(new FakeEngine) or JOB_ENGINE=fake.
 * Inspect recorded dispatches via dispatched().
 */
class FakeEngine extends BaseEngine
{
    private array $jobs = [];

    public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void
    {
        $this->jobs[] = [
            'handler' => $jobHandler,
            'payload' => $payload,
            'delay' => $delay,
            'queue' => $queue,
        ];
    }

    /**
     * All jobs dispatched since this engine was installed, in order.
     */
    public function dispatched(): array
    {
        return $this->jobs;
    }

    public function fetchNextJob(?string $queue = null)
    {
        return null;
    }

    public function deleteJob($job): void
    {
        // Do nothing
    }

    public function markFailedJob($job, Throwable $e): void
    {
        // Do nothing
    }

    public function release($job, string $delay = 'now'): void
    {
        // Do nothing
    }

    public function retryFailedJobs($jobId = null, ?string $queue = null): int
    {
        return 0;
    }
}
