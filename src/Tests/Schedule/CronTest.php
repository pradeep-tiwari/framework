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

        // Assert: Every 'N'th minute
        $minutes = date('i');
        $this->assertTrue(Cron::isDue("*/$minutes * * * *"));

        // Assert: At minute 'N+5'
        $minutes = date('i') + 5;
        $this->assertFalse(Cron::isDue("*/$minutes * * * *"));
    }

    public function testCronIsDueInHours(): void
    {
        // Assert: At every minute past every hour
        $this->assertTrue(Cron::isDue('* */1 * * *'));

        // Assert: At every minute past hour 'N'
        $hours = date('H');
        $this->assertTrue(Cron::isDue('* ' . $hours . ' * * *'));

        // Assert: At every minute past every hour from 'N' to 'N+5'
        $hours = date('H') . '-' . date('H') + 5;
        $this->assertTrue(Cron::isDue('* ' . $hours . ' * * *'));

        // Assert: At every minute past every 'N'th hour
        $hours = date('H');
        $this->assertTrue(Cron::isDue('* */' . $hours . ' * * *'));

        // Assert: At every minute past every 'N+5' hour
        $hours = date('H') + 5;
        $this->assertFalse(Cron::isDue('* */' . $hours . ' * * *'));
    }
}