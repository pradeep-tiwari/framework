<?php

declare(strict_types=1);

use Lightpack\Schedule\Cron;
use PHPUnit\Framework\TestCase;

final class CronTest extends TestCase
{
    public function testCron(): void
    {
        $cron = new Cron('* * * * *');
        $this->assertTrue($cron->minuteIsDue());
        $this->assertTrue($cron->hourIsDue());
        $this->assertTrue($cron->dayIsDue());
        $this->assertTrue($cron->monthIsDue());
    }

    public function testCronWithInvalidExpression(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid cron time expression');
        new Cron('* * * *');
    }

    public function testCronWithInvalidExpression2(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid cron time expression');
        new Cron('* * * * * *');
    }

    public function testCronIsDueInMinutes(): void
    {
        // Assert: At every minute
        $this->assertTrue(Cron::isDue('* * * * *'));
        $this->assertTrue(Cron::isDue('*/1 * * * *')); 

        // Assert: At minute 'N'
        $minutes = date('i');
        $this->assertTrue(Cron::isDue($minutes . ' * * * *'));

        // Assert: At every minute from 'N' to 'N+5'
        $minutes = date('i') . '-' . date('i') + 5;
        $this->assertTrue(Cron::isDue($minutes . ' * * * *'));

        // Assert: At every 'N+5'th minute
        $minutes = date('i') + 5;
        $this->assertFalse(Cron::isDue("*/$minutes * * * *"));
    }
}