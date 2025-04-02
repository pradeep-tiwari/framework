<?php

use PHPUnit\Framework\TestCase;
use Lightpack\SessionStore\Drivers\FileDriver;

class FileDriverTest extends TestCase
{
    private $driver;
    private $path;
    private $lifetime = 7200;
    private $originalUserAgent;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/session_test_' . uniqid();
        mkdir($this->path);
        $this->originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
        $this->driver = new FileDriver($this->path, $this->lifetime);
    }

    protected function tearDown(): void
    {
        $files = glob($this->path . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->path);
        
        if ($this->originalUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $this->originalUserAgent;
        } else {
            unset($_SERVER['HTTP_USER_AGENT']);
        }
    }

    public function testCreateSession()
    {
        $id = $this->driver->create();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $id);
        $this->assertTrue(file_exists($this->path . '/' . $id . '.json'));
    }

    public function testLoadSession()
    {
        $id = $this->driver->create();
        $data = ['test' => 'value'];
        $this->driver->save($id, $data);

        $loaded = $this->driver->load($id);
        $this->assertEquals($data, $loaded);
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
        $this->assertFalse(file_exists($this->path . '/' . $id . '.json'));
    }

    public function testSessionValidity()
    {
        $id = $this->driver->create();
        $this->assertTrue($this->driver->isValid($id));

        // Test invalid ID format
        $this->assertFalse($this->driver->isValid('invalid-id'));

        // Test expired session
        $reflection = new \ReflectionClass($this->driver);
        $property = $reflection->getProperty('lifetime');
        $property->setAccessible(true);
        $property->setValue($this->driver, -1); // Set negative lifetime to force expiration

        $this->assertFalse($this->driver->isValid($id));
    }

    public function testUserAgentVerification()
    {
        $id = $this->driver->create();
        $this->assertTrue($this->driver->isValid($id));

        // Change user agent and create new driver instance
        $_SERVER['HTTP_USER_AGENT'] = 'Different Browser';
        $newDriver = new FileDriver($this->path, $this->lifetime);
        $this->assertFalse($newDriver->isValid($id));
    }

    public function testSessionTimestamps()
    {
        $id = $this->driver->create();
        $createdAt = $this->driver->getCreatedAt($id);
        $lastAccessedAt = $this->driver->getLastAccessedAt($id);

        $this->assertIsInt($createdAt);
        $this->assertIsInt($lastAccessedAt);
        $this->assertGreaterThan(0, $createdAt);
        $this->assertGreaterThan(0, $lastAccessedAt);
    }

    public function testLastAccessedTimeUpdates()
    {
        $id = $this->driver->create();
        $firstAccess = $this->driver->getLastAccessedAt($id);
        
        sleep(1); // Wait a second
        $this->driver->save($id, ['key' => 'value']);
        
        $secondAccess = $this->driver->getLastAccessedAt($id);
        $this->assertGreaterThan($firstAccess, $secondAccess);
    }
}
