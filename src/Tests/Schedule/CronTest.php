<?php

declare(strict_types=1);

use Lightpack\Schedule\Cron;
use PHPUnit\Framework\TestCase;

final class CronTest extends TestCase
{
    public function testMinuteIsDue()
    {
        echo 'Minute is due: ' . date('i') . PHP_EOL;

        // Every minute
        $cron = new Cron('* * * * *');
        $this->assertTrue($cron->isDue());

        // At every 5th minute.
        $cron = new Cron('*/23 * * * *');
        $this->assertTrue($cron->isDue());
    }
}
