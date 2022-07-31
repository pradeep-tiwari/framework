<?php

namespace Lightpack\Schedule;

use Lightpack\Jobs\Job;

class Schedule
{
    /**
     * @var array
     */
    protected $jobs = [];

    public function job(Job $job, string $cronTime)
    {
        $this->jobs[] = [
            'job' => $job,
            'time' => $cronTime
        ];
    }

    public function getJobs(): array
    {
        return $this->jobs;
    }
}