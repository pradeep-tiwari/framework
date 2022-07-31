<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Schedule\Scheduler;

class ScheduleJobs implements ICommand
{
    public function run(array $arguments = [])
    {
        $scheduler = new Scheduler();
        $schedules = config('schedules');

        foreach ($schedules as $schedule) {
            $scheduler->addJob($schedule['job'], $schedule['time']);
        }

        $scheduler->run();
    }
}
