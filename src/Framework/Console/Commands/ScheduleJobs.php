<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Schedule\Scheduler;

class ScheduleJobs implements ICommand
{
    /** @var array */
    protected $schedules;

    /** @var Scheduler */
    protected $scheduler;

    public function __construct()
    {
        $this->schedules = config('schedules');
        $this->scheduler = new Scheduler();
        $this->addScheduledJobs();
    }

    public function run(array $arguments = [])
    {
        if(in_array('--async', $arguments)) {
            $this->scheduler->runAsync();
        } else {
            $this->scheduler->run();
        }
    }

    protected function addScheduledJobs()
    {
        foreach ($this->schedules as $schedule) {
            $this->scheduler->addJob($schedule['job'], $schedule['interval']);
        }
    }
}
