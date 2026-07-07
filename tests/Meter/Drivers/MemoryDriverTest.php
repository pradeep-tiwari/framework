<?php

use Lightpack\Meter\Drivers\MemoryDriver;
use PHPUnit\Framework\TestCase;

final class MemoryDriverTest extends TestCase
{
    private MemoryDriver $driver;

    public function setUp(): void
    {
        $this->driver = new MemoryDriver;
    }

    public function testValueStartsAtZero(): void
    {
        $this->assertEquals(0, $this->driver->value('hits'));
    }

    public function testIncrement(): void
    {
        $this->driver->increment('hits');
        $this->assertEquals(1, $this->driver->value('hits'));
    }

    public function testIncrementByAmount(): void
    {
        $this->driver->increment('hits', 5);
        $this->assertEquals(5, $this->driver->value('hits'));
    }

    public function testReset(): void
    {
        $this->driver->increment('hits', 10);
        $this->driver->reset('hits');
        $this->assertEquals(0, $this->driver->value('hits'));
    }

    public function testKeysAreIsolated(): void
    {
        $this->driver->increment('a', 1);
        $this->driver->increment('b', 2);
        $this->assertEquals(1, $this->driver->value('a'));
        $this->assertEquals(2, $this->driver->value('b'));
    }
}
