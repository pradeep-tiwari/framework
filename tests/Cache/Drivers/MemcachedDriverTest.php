<?php

namespace Lightpack\Tests\Cache\Drivers;

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Memcached\Memcached;
use Lightpack\Cache\Drivers\MemcachedDriver;

interface MockMemcached
{
    public function set($key, $value, $expiration);
    public function get($key);
    public function delete($key);
    public function flush();
}

class MemcachedDriverTest extends TestCase
{
    private MemcachedDriver $driver;
    private Memcached $memcached;
    private $client;
    
    protected function setUp(): void
    {
        // Create mock Memcached client
        if (extension_loaded('memcached')) {
            $this->client = $this->createMock(\Memcached::class);
        } else {
            $this->client = $this->createMock(MockMemcached::class);
        }
        
        // Create mock Memcached wrapper
        $this->memcached = $this->createMock(Memcached::class);
        $this->memcached->method('getClient')
            ->willReturn($this->client);
            
        $this->driver = new MemcachedDriver($this->memcached);
    }
    
    public function testCanSetAndGetValue()
    {
        $this->client->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value', 60)
            ->willReturn(true);
            
        $this->client->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('test_value');
            
        $this->driver->set('test_key', 'test_value', 60);
        $this->assertEquals('test_value', $this->driver->get('test_key'));
    }
    
    public function testHasReturnsTrueForExistingKey()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('test_value');
            
        $this->assertTrue($this->driver->has('test_key'));
    }
    
    public function testHasReturnsFalseForNonExistingKey()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('non_existing_key')
            ->willReturn(false);
            
        $this->assertFalse($this->driver->has('non_existing_key'));
    }
    
    public function testCanDeleteKey()
    {
        $this->client->expects($this->once())
            ->method('delete')
            ->with('test_key')
            ->willReturn(true);
            
        $this->assertTrue($this->driver->delete('test_key'));
    }
    
    public function testCanFlushCache()
    {
        $this->client->expects($this->once())
            ->method('flush')
            ->willReturn(true);
            
        $this->assertTrue($this->driver->flush());
    }
}
