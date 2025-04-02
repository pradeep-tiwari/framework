<?php

namespace Lightpack\Tests\Session;

use PHPUnit\Framework\TestCase;
use Lightpack\Session\DriverInterface;
use Lightpack\Http\Cookie;
use Lightpack\Session\Session;

class SessionTest extends TestCase
{
    /** @var DriverInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $driver;
    private $session;
    /** @var Cookie&\PHPUnit\Framework\MockObject\MockObject */
    private $cookie;
    private $secret = 'test-secret-key';
    private $cookieName = 'LPSESSID';

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->cookie = $this->createMock(Cookie::class);
        
        $this->session = new Session(
            $this->driver,
            $this->secret,
            $this->cookieName
        );

        // Use reflection to inject mock cookie
        $reflection = new \ReflectionClass($this->session);
        $property = $reflection->getProperty('cookie');
        $property->setAccessible(true);
        $property->setValue($this->session, $this->cookie);
    }

    public function testStartWithValidExistingSession()
    {
        $id = 'test-session-id';
        $data = ['key' => 'value'];

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->cookieName)
            ->willReturn($id);

        $this->driver->expects($this->once())
            ->method('isValid')
            ->with($id)
            ->willReturn(true);

        $this->driver->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturn($data);

        $this->assertTrue($this->session->start());
        $this->assertEquals($id, $this->session->getId());
        $this->assertEquals($data['key'], $this->session->get('key'));
    }

    public function testStartWithInvalidSession()
    {
        $id = 'test-session-id';
        $newId = 'new-session-id';

        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->cookieName)
            ->willReturn($id);

        $this->driver->expects($this->once())
            ->method('isValid')
            ->with($id)
            ->willReturn(false);

        $this->driver->expects($this->once())
            ->method('create')
            ->willReturn($newId);

        $this->cookie->expects($this->once())
            ->method('set')
            ->with(
                $this->cookieName,
                $newId,
                0,
                ['secure' => true]
            );

        $this->assertTrue($this->session->start());
        $this->assertEquals($newId, $this->session->getId());
    }

    public function testSetAndGetWithDotNotation()
    {
        $this->session->start();

        $this->session->set('user.profile.name', 'John');
        $this->assertEquals('John', $this->session->get('user.profile.name'));

        $expected = [
            'user' => [
                'profile' => [
                    'name' => 'John'
                ]
            ]
        ];

        $this->assertEquals($expected['user'], $this->session->get('user'));
    }

    public function testFlashMessages()
    {
        $this->session->start();

        $this->session->flash('success', 'Operation completed');
        $this->assertEquals('Operation completed', $this->session->get('_flash.new.success'));

        // Age flash data
        $reflection = new \ReflectionClass($this->session);
        $method = $reflection->getMethod('ageFlashData');
        $method->setAccessible(true);
        $method->invoke($this->session);

        $this->assertEquals('Operation completed', $this->session->flash('success'));
        $this->assertNull($this->session->flash('success')); // Second read should be null
    }

    public function testCsrfTokenGeneration()
    {
        $this->session->start();

        $token = $this->session->token();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars

        // Token should persist
        $this->assertEquals($token, $this->session->token());
    }

    public function testCsrfTokenVerification()
    {
        $this->session->start();
        $token = $this->session->token();

        // Test header verification
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $this->assertTrue($this->session->verifyToken());

        // Test POST verification
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_token'] = $token;
        $this->assertTrue($this->session->verifyToken());

        // Test invalid token
        $_POST['_token'] = 'invalid-token';
        $this->assertFalse($this->session->verifyToken());
    }

    public function testSessionRegeneration()
    {
        $this->session->start();
        $oldId = $this->session->getId();
        $data = ['key' => 'value'];
        
        $this->session->set('key', 'value');

        $newId = 'new-session-id';
        $this->driver->expects($this->once())
            ->method('create')
            ->willReturn($newId);

        $this->driver->expects($this->once())
            ->method('save')
            ->with($newId, $data)
            ->willReturn(true);

        $this->driver->expects($this->once())
            ->method('destroy')
            ->with($oldId);

        $this->assertTrue($this->session->regenerate());
        $this->assertEquals($newId, $this->session->getId());
        $this->assertEquals('value', $this->session->get('key'));
    }

    public function testSessionDestruction()
    {
        $id = 'test-session-id';
        $this->cookie->expects($this->once())
            ->method('get')
            ->with($this->cookieName)
            ->willReturn($id);

        $this->driver->expects($this->exactly(2))
            ->method('isValid')
            ->with($id)
            ->willReturn(true);

        $this->driver->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturn(['key' => 'value']);

        $this->session->start();

        $this->driver->expects($this->once())
            ->method('destroy')
            ->with($id);

        $this->cookie->expects($this->once())
            ->method('delete')
            ->with($this->cookieName);

        $this->session->destroy();
        $this->assertNull($this->session->getId());
        $this->assertEmpty($this->session->all());
    }
}
