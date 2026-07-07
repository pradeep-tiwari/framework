<?php

use Lightpack\Meter\Drivers\MemoryDriver;
use Lightpack\Meter\Meter;
use PHPUnit\Framework\TestCase;

final class MeterTest extends TestCase
{
    private Meter $meter;

    public function setUp(): void
    {
        $this->meter = new Meter(new MemoryDriver, 'api_calls');
    }

    public function testHitAndValue(): void
    {
        $this->assertEquals(0, $this->meter->value());
        $this->meter->hit();
        $this->assertEquals(1, $this->meter->value());
    }

    public function testHitWithAmount(): void
    {
        $this->meter->hit(5);
        $this->assertEquals(5, $this->meter->value());
    }

    public function testRemaining(): void
    {
        $this->meter->hit(3);
        $this->assertEquals(7, $this->meter->remaining(10));
    }

    public function testAvailable(): void
    {
        $this->meter->hit(3);
        $this->assertTrue($this->meter->available(10));
        $this->meter->hit(7);
        $this->assertFalse($this->meter->available(10));
    }

    public function testConsumeWithinLimit(): void
    {
        $this->assertTrue($this->meter->consume(1, 5));
        $this->assertEquals(1, $this->meter->value());
    }

    public function testConsumeExceedsLimit(): void
    {
        $this->meter->hit(5);
        $this->assertFalse($this->meter->consume(1, 5));
        $this->assertEquals(5, $this->meter->value());
    }

    public function testReset(): void
    {
        $this->meter->hit(5);
        $this->meter->reset();
        $this->assertEquals(0, $this->meter->value());
    }

    public function testPeriodKey(): void
    {
        $daily = new Meter(new MemoryDriver, 'api_calls', 'daily');
        $daily->hit();
        $this->assertEquals(1, $daily->value());

        $monthly = new Meter(new MemoryDriver, 'api_calls', 'monthly');
        $monthly->hit();
        $this->assertEquals(1, $monthly->value());
    }

    public function testInvalidPeriodThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $meter = new Meter(new MemoryDriver, 'api_calls', 'invalid');
        $meter->value();
    }
}
