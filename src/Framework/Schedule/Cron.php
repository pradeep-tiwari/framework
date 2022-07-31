<?php

namespace Lightpack\Schedule;

class Cron
{
    /**
     * @var string $cronTime Cron time expression
     */
    protected $cronTime;

    protected $minutes;
    protected $hours;
    protected $days;
    protected $months;
    protected $weekdays;

    /**
     * @var string $cronTime Cron time expression
     */
    public function __construct(string $cronTime)
    {
        $this->cronTime = $cronTime;

        // Split cron time expression into array
        $cronParts = explode(' ', $this->cronTime);

        if (count($cronParts) !== 5) {
            throw new \Exception('Invalid cron time expression');
        }

        // Get cron minutes: 0-59
        $this->minutes = $cronParts[0];

        // Get cron hours: 0-23
        $this->hours = $cronParts[1];

        // Get cron day of month: 1-31
        $this->days = $cronParts[2];

        // Get cron month: 0-11 or JAN-DEC
        $this->months = $cronParts[3];

        // Get cron day of week: 1-7 or SUN-SAT
        $this->weekdays = $cronParts[4];
    }

    public function minuteIsDue()
    {
        return $this->checkIfDue($this->minutes, date('i'));
    }

    public function hourIsDue()
    {
        return $this->checkIfDue($this->hours, date('H'));
    }

    public function dayIsDue()
    {
        return $this->checkIfDue($this->days, date('j'));
    }

    public function monthIsDue()
    {
        return $this->checkIfDue($this->months, date('n'));
    }

    public function weekdayIsDue()
    {
        return $this->checkIfDue($this->weekdays, date('w'));
    }

    public function isDue()
    {
        return $this->minuteIsDue() && $this->hourIsDue() && $this->dayIsDue() && $this->monthIsDue() && $this->weekdayIsDue();
    }

    protected function checkIfDue($expression, $current)
    {
        if ($expression === '*') {
            return true;
        }

        if ($expression === $current) {
            return true;
        }

        if (strpos($expression, '-') !== false) {
            $parts = explode('-', $expression);
            if ($current >= $parts[0] && $current <= $parts[1]) {
                return true;
            }
        }

        if (strpos($expression, '/') !== false) {
            $parts = explode('/', $expression);
            if ($current % $parts[1] === 0) {
                return true;
            }
        }

        return false;
    }
}
