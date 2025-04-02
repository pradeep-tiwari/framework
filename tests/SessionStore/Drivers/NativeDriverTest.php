<?php

namespace Lightpack\Tests\SessionStore\Drivers;

use PHPUnit\Framework\TestCase;
use Lightpack\SessionStore\Drivers\NativeDriver;

/**
 * @runTestsInSeparateProcesses
 */
class NativeDriverTest extends TestCase
{
    private NativeDriver $driver;
    private string $sessionDir;

    protected function setUp(): void
    {
        $this->sessionDir = __DIR__ . '/tmp';
        if (!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir);
        }

        $this->driver = new NativeDriver([
            'save_path' => $this->sessionDir,
            'lifetime' => 3600,
        ]);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        array_map('unlink', glob($this->sessionDir . '/*'));
        rmdir($this->sessionDir);
    }

    public function testCreateSession()
    {
        $id = $this->driver->create();
        $this->assertNotEmpty($id);
        $this->assertTrue($this->driver->isValid($id));
        $this->assertEmpty(array_diff_key(
            $this->driver->load($id),
            ['_created_at' => true, '_last_accessed_at' => true, '_expires_at' => true]
        ));
    }

    public function testLoadSession()
    {
        $id = $this->driver->create();
        $data = ['key' => 'value'];
        
        $this->driver->save($id, $data);
        $loaded = $this->driver->load($id);
        $this->assertEquals('value', $loaded['key']);
    }

    public function testSaveSession()
    {
        $id = $this->driver->create();
        $data = ['key' => 'value'];
        
        $this->assertTrue($this->driver->save($id, $data));
        $loaded = $this->driver->load($id);
        $this->assertEquals('value', $loaded['key']);
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
        $driver = new NativeDriver([
            'save_path' => $this->sessionDir,
            'lifetime' => 1, // 1 second
        ]);
        
        $id = $driver->create();
        $this->assertTrue($driver->isValid($id));
        
        sleep(2); // Wait for session to expire
        
        // Load session data directly to avoid memory issues
        $path = $this->sessionDir . '/sess_' . $id;
        $this->assertFalse($driver->isValid($id));
        $this->assertNull($driver->load($id));
    }

    public function testCustomSessionOptions()
    {
        $options = [
            'name' => 'CUSTOMSESSID',
            'lifetime' => 1800,
            'path' => '/custom',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true,
            'save_path' => $this->sessionDir,
        ];

        $driver = new NativeDriver($options);
        $this->assertEquals('CUSTOMSESSID', ini_get('session.name'));
        $this->assertEquals($this->sessionDir, ini_get('session.save_path'));
    }
}
