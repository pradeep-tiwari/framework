<?php

namespace Lightpack\Schedule;

use Lightpack\Jobs\Job;

class Scheduler
{
    /**
     * @var array
     */
    protected $jobs = [];

    /**
     * Add a job to the schedule.
     * 
     * @param Job $job
     * @param string $interval Cron time expression.
     */
    public function addJob(Job $job, string $interval)
    {
        $this->jobs[] = [
            'job' => $job,
            'interval' => $interval
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
     * Get all scheduled jobs that are due.
     */
    public function getDueJobs(): array
    {
        $dueJobs = [];

        foreach ($this->jobs as $job) {
            if (Cron::isDue($job['interval'])) {
                $dueJobs[] = $job;
            }
        }

        return $dueJobs;
    }

    /**
     * Run all scheduled jobs synchronously.
     */
    public function run()
    {
        foreach ($this->jobs as $job) {
            if (Cron::isDue($job['interval'])) {
                $job['job']->execute([]);
            }
        }
    }

    /**
     * Run all scheduled jobs by dispatching them into queue.
     */
    public function runAsync()
    {
        foreach ($this->jobs as $job) {
            if (Cron::isDue($job['interval'])) {
                $job['job']->dispatch();
            }
        }
    }
}
