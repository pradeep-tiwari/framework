<?php

namespace Lightpack\Tests\Session\Drivers;

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Cache;
use Lightpack\Cache\Drivers\FileDriver as CacheFileDriver;
use Lightpack\Session\Drivers\CacheDriver;

class CacheDriverTest extends TestCase
{
    private CacheDriver $driver;
    private Cache $cache;
    private int $lifetime = 3600;
    private string $prefix = 'test:';
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = __DIR__ . '/tmp';
        mkdir($this->cacheDir);
        
        $this->cache = new Cache(new CacheFileDriver($this->cacheDir));
        $this->driver = new CacheDriver($this->cache, $this->lifetime, $this->prefix);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->cacheDir . '/*'));
        rmdir($this->cacheDir);
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
        $this->driver->touch($id);
        $newAccess = $this->driver->getLastAccessedAt($id);
        
        $this->assertGreaterThan($initialAccess, $newAccess);
    }

    public function testSessionExpiration()
    {
        $shortLifetime = 1; // 1 second
        $driver = new CacheDriver($this->cache, $shortLifetime, $this->prefix);
        
        $id = $driver->create();
        $this->assertTrue($driver->isValid($id));
        
        sleep(2); // Wait for session to expire
        $this->assertFalse($driver->isValid($id));
        $this->assertNull($driver->load($id));
    }
}
