<?php

use Lightpack\Schedule\Cron;
use PHPUnit\Framework\TestCase;

final class CronTest extends TestCase
{
    public function testValidCronExpression()
    {
        $cronExpression = '* * * * *';
        $cron = new Cron($cronExpression);
        $this->assertInstanceOf(Cron::class, $cron);
    }

    public function testInvalidCronExpression()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid cron time expression');
        new Cron('* * * *'); // Missing one field
    }

    public function testComplexCronExpression()
    {
        // Test combination of list and step
        $cron = new Cron('1,15,30,45 * * * *');
        
        // Should match at specific minutes
        $this->assertTrue($cron->minuteIsDue(new \DateTime('2023-05-14 10:15:00')));
        $this->assertTrue($cron->minuteIsDue(new \DateTime('2023-05-14 10:30:00')));
        $this->assertFalse($cron->minuteIsDue(new \DateTime('2023-05-14 10:16:00')));
    }

    public function testBoundaryConditions()
    {
        // Test hour boundaries
        $cron = new Cron('* 23 * * *'); // Last hour of day
        $this->assertTrue($cron->hourIsDue(new \DateTime('2023-05-14 23:00:00')));
        $this->assertFalse($cron->hourIsDue(new \DateTime('2023-05-14 22:00:00')));

        // Test minute boundaries
        $cron = new Cron('59 * * * *'); // Last minute of hour
        $this->assertTrue($cron->minuteIsDue(new \DateTime('2023-05-14 10:59:00')));
        $this->assertFalse($cron->minuteIsDue(new \DateTime('2023-05-14 10:58:00')));
    }

    public function testMultipleStepValues()
    {
        // Test step values
        $cron = new Cron('*/15 */6 * * *');
        
        // Should match every 15 minutes in every 6th hour
        $this->assertTrue($cron->isDue(new \DateTime('2023-01-01 00:00:00')));
        $this->assertTrue($cron->isDue(new \DateTime('2023-01-01 00:15:00')));
        $this->assertTrue($cron->isDue(new \DateTime('2023-01-01 06:00:00')));
        $this->assertFalse($cron->isDue(new \DateTime('2023-01-01 01:00:00')));
    }

    public function testRangeValues()
    {
        // Test range values
        $cron = new Cron('1-5 1-5 * * *');
        
        // Test within range
        $this->assertTrue($cron->isDue(new \DateTime('2023-01-01 01:01:00')));
        $this->assertTrue($cron->isDue(new \DateTime('2023-01-01 02:03:00')));
        
        // Test outside range
        $this->assertFalse($cron->isDue(new \DateTime('2023-01-01 00:00:00')));
        $this->assertFalse($cron->isDue(new \DateTime('2023-01-01 06:06:00')));
    }

    public function testSpecialTimeStrings()
    {
        // Test @hourly
        $cron = new Cron('@hourly');
        $this->assertTrue($cron->isDue(new \DateTime('2023-05-14 01:00:00')));
        $this->assertFalse($cron->isDue(new \DateTime('2023-05-14 01:01:00')));

        // Test @daily
        $cron = new Cron('@daily');
        $this->assertTrue($cron->isDue(new \DateTime('2023-05-14 00:00:00')));
        $this->assertFalse($cron->isDue(new \DateTime('2023-05-14 00:01:00')));

        // Test @monthly
        $cron = new Cron('@monthly');
        $this->assertTrue($cron->isDue(new \DateTime('2023-05-01 00:00:00')));
        $this->assertFalse($cron->isDue(new \DateTime('2023-05-01 00:01:00')));
    }

    public function testNextDueDate()
    {
        // Test next due date within same hour
        $cron = new Cron('*/15 * * * *');
        $currentDateTime = new \DateTime('2023-05-14 10:00:00');
        $expectedNextDateTime = new \DateTime('2023-05-14 10:15:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));

        // Test next due date in next hour
        $currentDateTime = new \DateTime('2023-05-14 10:45:00');
        $expectedNextDateTime = new \DateTime('2023-05-14 11:00:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));
    }

    public function testPreviousDueDate()
    {
        // Test previous due date within same hour
        $cron = new Cron('*/15 * * * *');
        $currentDateTime = new \DateTime('2023-05-14 10:16:00');
        $expectedPreviousDateTime = new \DateTime('2023-05-14 10:15:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));

        // Test previous due date in previous hour
        $currentDateTime = new \DateTime('2023-05-14 11:00:00');
        $expectedPreviousDateTime = new \DateTime('2023-05-14 10:45:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));
    }

    public function testAlternativeWeekdayFormat()
    {
        $cron = new Cron('* * * * 7');
        $cron->useAlternativeWeekdays();
        
        // Test on a Sunday (should match when using alternative format)
        $this->assertTrue($cron->weekdayIsDue(new \DateTime('2023-05-14'))); // This is a Sunday
        
        // Test on a Monday (should not match)
        $this->assertFalse($cron->weekdayIsDue(new \DateTime('2023-05-15'))); // This is a Monday
    }

    public function testMaxIterationsLimit()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to determine the due date within a reasonable number of iterations');

        // Create a cron expression that would require more than 1440 iterations
        // We'll use a specific minute (59) and hour (23) that's far from the current time
        $cron = new Cron('59 23 31 12 *'); // 23:59 on December 31st
        $currentDateTime = new \DateTime('2023-01-01 00:00:00'); // Start of year
        $cron->nextDueAt($currentDateTime);
    }
}
