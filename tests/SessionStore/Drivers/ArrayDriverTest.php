<?php

namespace Lightpack\Tests\SessionStore\Drivers;

use PHPUnit\Framework\TestCase;
use Lightpack\SessionStore\Drivers\ArrayDriver;

class ArrayDriverTest extends TestCase
{
    private ArrayDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ArrayDriver();
    }

    protected function tearDown(): void
    {
        $this->driver->clear();
    }

    public function testCreateSession()
    {
        $id = $this->driver->create();
        $this->assertNotEmpty($id);
        $this->assertTrue($this->driver->isValid($id));
        $this->assertEmpty($this->driver->load($id));
    }

    public function testLoadSession()
    {
        $id = $this->driver->create();
        $data = ['key' => 'value'];
        
        $this->driver->save($id, $data);
        $this->assertEquals($data, $this->driver->load($id));
    }

    public function testSaveSession()
    {
        $id = $this->driver->create();
        $data = ['key' => 'value'];
        
        $this->assertTrue($this->driver->save($id, $data));
        $this->assertEquals($data, $this->driver->load($id));
    }

    public function testDestroySession()
    {
        $id = $this->driver->create();
        $this->assertTrue($this->driver->destroy($id));
        $this->assertFalse($this->driver->isValid($id));
        $this->assertNull($this->driver->load($id));
    }

    public function testSessionValidity()
    {
        $id = $this->driver->create();
        $this->assertTrue($this->driver->isValid($id));
        $this->driver->destroy($id);
        $this->assertFalse($this->driver->isValid($id));
    }

    public function testSessionTimestamps()
    {
        $id = $this->driver->create();
        $createdAt = $this->driver->getCreatedAt($id);
        
        $this->assertNotNull($createdAt);
        $this->assertIsInt($createdAt);
        $this->assertLessThanOrEqual(time(), $createdAt);
    }

    public function testLastAccessedTimeUpdates()
    {
        $id = $this->driver->create();
        $initialAccess = $this->driver->getLastAccessedAt($id);
        
        sleep(1);
        $this->driver->load($id);
        $newAccess = $this->driver->getLastAccessedAt($id);
        
        $this->assertGreaterThan($initialAccess, $newAccess);
    }

    public function testClearSessions()
    {
        $id1 = $this->driver->create();
        $id2 = $this->driver->create();
        
        $this->driver->clear();
        
        $this->assertFalse($this->driver->isValid($id1));
        $this->assertFalse($this->driver->isValid($id2));
    }
}
