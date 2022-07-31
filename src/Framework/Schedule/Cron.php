<?php

namespace Lightpack\Schedule;

class Cron
{
    /**
     * @var string $cronTime Cron time expression
     */
    protected $cronTime;

    /**
     * @var string $cronTime Cron time expression
     */
    public function isDue(string $cronTime)
    {
        $this->cronTime = $cronTime;

        // Split cron time expression into array
        $cronParts = explode(' ', $this->cronTime);

        // Get cron seconds: 0-59
        $cronSecond = $cronParts[0];

        // Get cron minutes: 0-59
        $cronMinute = $cronParts[1];

        // Get cron hours: 0-23
        $cronHour = $cronParts[2];

        // Get cron day of month: 1-31
        $cronDayOfMonth = $cronParts[3];

        // Get cron month: 0-11 or JAN-DEC
        $cronMonth = $cronParts[4];

        // Get cron day of week: 1-7 or SUN-SAT
        $cronDayOfWeek = $cronParts[5];

        // Get cron year: 1970-2099 or NONE
        $cronYear = $cronParts[6];

        // Check if cron year is set
        if ($cronYear == 'NONE') {
            $cronYear = date('Y');
        }

        // Check if cron month is set
        if ($cronMonth == '*') {
            $cronMonth = date('m');
        }

        // Check if cron day of month is set
        if ($cronDayOfMonth == '*') {
            $cronDayOfMonth = date('d');
        }

        // Check if cron hour is set
        if ($cronHour == '*') {
            $cronHour = date('H');
        }

        // Check if cron minute is set
        if ($cronMinute == '*') {
            $cronMinute = date('i');
        }

        // Check if cron seconds is set
        if ($cronSecond == '*') {
            $cronSecond = date('s');
        }

        // Check if cron day of week is set
        if ($cronDayOfWeek == '*') {
            $cronDayOfWeek = date('w');
        }

        // Check if cron seconds is due
        if ($cronSecond == date('s')) {
            $cronSecond = true;
        } else {
            $cronSecond = false;
        }

        // Check if cron minute is due
        if ($cronMinute == date('i')) {
            $cronMinute = true;
        } else {
            $cronMinute = false;
        }

        // Check if cron hour is due
        if ($cronHour == date('H')) {
            $cronHour = true;
        } else {
            $cronHour = false;
        }

        // Check if cron day of month is due
        if ($cronDayOfMonth == date('d')) {
            $cronDayOfMonth = true;
        } else {
            $cronDayOfMonth = false;
        }

        // Check if cron month is due
        if ($cronMonth == date('m')) {
            $cronMonth = true;
        } else {
            $cronMonth = false;
        }

        // Check if cron year is due
        if ($cronYear == date('Y')) {
            $cronYear = true;
        } else {
            $cronYear = false;
        }

        // Check if cron day of week is due
        if ($cronDayOfWeek == date('w')) {
            $cronDayOfWeek = true;
        } else {
            $cronDayOfWeek = false;
        }

        // Check if cron is due
        if ($cronSecond && $cronMinute && $cronHour && $cronDayOfMonth && $cronMonth && $cronYear && $cronDayOfWeek) {
            return true;
        }
        return false;
    }
}
