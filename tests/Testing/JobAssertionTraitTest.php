<?php

namespace Lightpack\Tests\Testing;

use Lightpack\Jobs\Connection;
use Lightpack\Jobs\Engines\FakeEngine;
use Lightpack\Jobs\Engines\NullEngine;
use Lightpack\Jobs\Job;
use Lightpack\Testing\JobAssertionTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class FlaggedJob extends Job
{
    public static bool $ran = false;

    public function run()
    {
        self::$ran = true;
    }
}

class QueuedJob extends Job
{
    protected $queue = 'emails';

    public function run()
    {
    }
}

class JobAssertionTraitTest extends TestCase
{
    use JobAssertionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        FlaggedJob::$ran = false;
    }

    protected function tearDown(): void
    {
        Connection::setEngine(null);
        putenv('JOB_ENGINE');
        parent::tearDown();
    }

    public function testFakeJobsReturnsAndInstallsFakeEngine()
    {
        $engine = $this->fakeJobs();

        $this->assertInstanceOf(FakeEngine::class, $engine);
        $this->assertSame($engine, Connection::getJobEngine());
    }

    public function testDispatchIsRecordedWithPayload()
    {
        $engine = $this->fakeJobs();

        (new FlaggedJob)->dispatch(['user_id' => 42]);

        $jobs = $engine->dispatched();

        $this->assertCount(1, $jobs);
        $this->assertSame(FlaggedJob::class, $jobs[0]['handler']);
        $this->assertSame(['user_id' => 42], $jobs[0]['payload']);
        $this->assertSame('now', $jobs[0]['delay']);
        $this->assertSame('default', $jobs[0]['queue']);
    }

    public function testDispatchedJobIsNotExecuted()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch();

        $this->assertFalse(FlaggedJob::$ran);
    }

    public function testDispatchRecordsDelayAndQueue()
    {
        $engine = $this->fakeJobs();

        (new QueuedJob)->delay('+1 hour')->dispatch();

        $jobs = $engine->dispatched();

        $this->assertSame('+1 hour', $jobs[0]['delay']);
        $this->assertSame('emails', $jobs[0]['queue']);
    }

    public function testDispatchesAreRecordedInOrder()
    {
        $engine = $this->fakeJobs();

        (new FlaggedJob)->dispatch(['n' => 1]);
        (new QueuedJob)->dispatch();
        (new FlaggedJob)->dispatch(['n' => 2]);

        $jobs = $engine->dispatched();

        $this->assertSame(FlaggedJob::class, $jobs[0]['handler']);
        $this->assertSame(QueuedJob::class, $jobs[1]['handler']);
        $this->assertSame(['n' => 2], $jobs[2]['payload']);
    }

    public function testJobEngineEnvSelectsFakeEngine()
    {
        putenv('JOB_ENGINE=fake');
        Connection::setEngine(null);

        $this->assertInstanceOf(FakeEngine::class, Connection::getJobEngine());
    }

    public function testSetEngineNullResolvesFreshEngine()
    {
        putenv('JOB_ENGINE=null');
        Connection::setEngine(null);

        $this->assertInstanceOf(NullEngine::class, Connection::getJobEngine());
    }

    public function testAssertJobDispatched()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch();

        $this->assertJobDispatched(FlaggedJob::class);
    }

    public function testAssertJobNotDispatched()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch();

        $this->assertJobNotDispatched(QueuedJob::class);
    }

    public function testAssertJobDispatchedWith()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch(['user_id' => 42, 'extra' => 'x']);

        $this->assertJobDispatchedWith(FlaggedJob::class, ['user_id' => 42]);
    }

    public function testAssertJobDispatchedWithFailsOnMismatch()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch(['user_id' => 42]);

        $this->expectException(AssertionFailedError::class);

        $this->assertJobDispatchedWith(FlaggedJob::class, ['user_id' => 99]);
    }

    public function testAssertJobCount()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch();
        (new FlaggedJob)->dispatch();

        $this->assertJobCount(2);
    }

    public function testAssertNoJobsDispatched()
    {
        $this->fakeJobs();

        $this->assertNoJobsDispatched();
    }

    public function testAssertionsFailWithoutFakeEngine()
    {
        Connection::setEngine(new NullEngine);

        $this->expectException(AssertionFailedError::class);

        $this->assertJobDispatched(FlaggedJob::class);
    }

    public function testFluentChaining()
    {
        $this->fakeJobs();

        (new FlaggedJob)->dispatch(['user_id' => 7]);

        $this->assertJobDispatched(FlaggedJob::class)
            ->assertJobDispatchedWith(FlaggedJob::class, ['user_id' => 7])
            ->assertJobNotDispatched(QueuedJob::class)
            ->assertJobCount(1);
    }
}
