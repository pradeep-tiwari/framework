<?php

namespace Lightpack\Schedule;

class Cron
{
    protected string $minutes;
    protected string $hours;
    protected string $days;
    protected string $months;
    protected string $weekdays;
    protected bool $useAlternativeWeekdays = false;

    public function __construct(string $cronExpression)
    {
        // Handle special time strings first
        $cronExpression = $this->parseSpecialTimeString($cronExpression);
        
        $cronParts = explode(' ', $cronExpression);

        if (count($cronParts) !== 5) {
            throw new \Exception('Invalid cron time expression');
        }

        $this->minutes = $cronParts[0];
        $this->hours = $cronParts[1];
        $this->days = $cronParts[2];
        $this->months = $cronParts[3];
        $this->weekdays = $cronParts[4];
    }

    protected function parseSpecialTimeString(string $expression): string 
    {
        return match($expression) {
            '@yearly', '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily', '@midnight' => '0 0 * * *',
            '@hourly' => '0 * * * *',
            default => $expression,
        };
    }

    public function useAlternativeWeekdays(bool $use = true): self
    {
        $this->useAlternativeWeekdays = $use;
        return $this;
    }

    public function minuteIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->minutes, (int) $currentDateTime->format('i'));
    }

    public function hourIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->hours, (int) $currentDateTime->format('H'));
    }

    public function dayIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->days, (int) $currentDateTime->format('j'));
    }

    public function monthIsDue(\DateTime $currentDateTime): bool
    {
        return $this->checkIfDue($this->months, (int) $currentDateTime->format('n'));
    }

    public function weekdayIsDue(\DateTime $currentDateTime): bool
    {
        $weekday = (int)$currentDateTime->format('w');
        
        // Convert Sunday from 0 to 7 if using alternative weekday format
        if ($this->useAlternativeWeekdays && $weekday === 0) {
            $weekday = 7;
        }
        
        return $this->checkIfDue($this->weekdays, $weekday);
    }

    public function isDue(\DateTime $currentDateTime): bool
    {
        return $this->minuteIsDue($currentDateTime)
            && $this->hourIsDue($currentDateTime)
            && $this->dayIsDue($currentDateTime)
            && $this->monthIsDue($currentDateTime)
            && $this->weekdayIsDue($currentDateTime);
    }

    public function nextDueAt(\DateTime $currentDateTime): \DateTime
    {
        $interval = '+1 minute';
        $dueDateTime = clone $currentDateTime;
        $dueDateTime->modify($interval);

        $maxIterations = 1440; // Maximum number of minutes in a day

        while ($maxIterations > 0) {
            if ($this->isDue($dueDateTime)) {
                return $dueDateTime;
            }

            $dueDateTime->modify($interval);
            $maxIterations--;
        }

        throw new \Exception('Unable to determine the due date within a reasonable number of iterations');
    }

    public function previousDueAt(\DateTime $currentDateTime): \DateTime
    {
        $previousDateTime = clone $currentDateTime;
        $previousDateTime->modify('-1 minute');
        
        $maxIterations = 1440; // Maximum number of minutes in a day
        while ($maxIterations > 0) {
            if ($this->isDue($previousDateTime)) {
                return $previousDateTime;
            }
            $previousDateTime->modify('-1 minute');
            $maxIterations--;
        }
        
        throw new \Exception('Unable to determine the previous due date within a reasonable number of iterations');
    }

    protected function checkIfDue($expression, $current): bool
    {
        if ($expression === '*') {
            return true;
        }

        if ($expression == $current) {
            return true;
        }

        if (strpos($expression, '-') !== false) {
            [$start, $end] = explode('-', $expression);
            if ((int)$current >= (int)$start && (int)$current <= (int)$end) {
                return true;
            }
        }

        if (strpos($expression, '/') !== false) {
            $parts = explode('/', $expression);
            $step = (int)$parts[1];
            $start = $parts[0] === '*' ? 0 : (int)$parts[0];
            
            if ($current >= $start && ($current - $start) % $step === 0) {
                return true;
            }
        }

        if (strpos($expression, ',') !== false) {
            $parts = explode(',', $expression);
            foreach ($parts as $part) {
                if ((int)$part === (int)$current) {
                    return true;
                }
            }
        }

        return false;
    }
}
