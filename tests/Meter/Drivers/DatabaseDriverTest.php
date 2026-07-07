<?php

use Lightpack\Database\Adapters\Mysql;
use Lightpack\Meter\Drivers\DatabaseDriver;
use PHPUnit\Framework\TestCase;

final class DatabaseDriverTest extends TestCase
{
    private ?Mysql $db = null;
    private ?DatabaseDriver $driver = null;
    private string $table = 'meters_test';

    public function setUp(): void
    {
        try {
            $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
            $this->db = new Mysql($config);
            $this->db->query("DROP TABLE IF EXISTS {$this->table}");
            $this->db->query("CREATE TABLE {$this->table} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(255) NOT NULL,
                value BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY meters_test_key_unique (`key`)
            )");
            $this->driver = new DatabaseDriver($this->db, $this->table);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to database: ' . $e->getMessage());
        }
    }

    public function tearDown(): void
    {
        if ($this->db) {
            $this->db->query("DROP TABLE IF EXISTS {$this->table}");
            $this->db = null;
        }
        $this->driver = null;
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

    public function testMultipleIncrements(): void
    {
        $this->driver->increment('hits');
        $this->driver->increment('hits');
        $this->driver->increment('hits', 3);
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
