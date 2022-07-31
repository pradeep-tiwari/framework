<?php

namespace Lightpack\Schedule;

use Lightpack\Jobs\Job;

class Schedule
{
    /**
     * @var array
     */
    protected $jobs = [];

    /**
     * Add a job to the schedule.
     */
    public function job(Job $job, string $cronTime)
    {
        $this->jobs[] = [
            'job' => $job,
            'time' => $cronTime
        ];
    }

    /**
     * Return all scheduled jobs.
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Run all scheduled jobs that are due.
     */
    public function getDueJobs(): array
    {
        $dueJobs = [];

        foreach ($this->jobs as $job) {
            if (Cron::isDue($job['time'])) {
                $dueJobs[] = $job;
            }
        }

        return $dueJobs;
    }

    /**
     * Run all scheduled jobs.
     */
    public function run()
    {
        foreach ($this->jobs as $job) {
            if (Cron::isDue($job['time'])) {
                $job['job']->run();
            }
        }
    }
}
