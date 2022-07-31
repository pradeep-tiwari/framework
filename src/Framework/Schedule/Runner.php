<?php

namespace Lightpack\Schedule;

class Runner
{   
    /**
     * @var \Lightpack\Schedule\Schedule $schedule
     */
    protected $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function run()
    {
        $jobs = $this->schedule->getJobs();

        foreach ($jobs as $job) {
            if($this->isDue($job['time'])) {
                $job['job']->run();
            }
        }
    }

    protected function isDue(string $cronTime)
    {
        return (new Cron)->isDue($cronTime);
    }
}