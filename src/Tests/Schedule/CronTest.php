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

    public function testCronIsDue(): void
    {
        echo date('i');
        // Test if cron is due for multiple possible cron expressions.
        $this->assertTrue(Cron::isDue('* * * * *'));
        $this->assertTrue(Cron::isDue('*/1 * * * *'));
        // $this->assertFalse(Cron::isDue('*/3 * * * *'));
        $this->assertFalse(Cron::isDue('*/5 * * * *'));
        $this->assertFalse(Cron::isDue('*/10 * * * *'));
        $this->assertFalse(Cron::isDue('*/47 * * * *'));
        $this->assertTrue(Cron::isDue('*/17 * * * *'));
    }
}