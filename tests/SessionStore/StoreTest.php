<?php

use PHPUnit\Framework\TestCase;
use Lightpack\SessionStore\Store;
use Lightpack\SessionStore\Contracts\StoreInterface;
use Lightpack\Http\Cookie;
use Lightpack\Utils\Arr;

class StoreTest extends TestCase
{
    /** @var StoreInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $driver;
    private $store;
    /** @var Cookie&\PHPUnit\Framework\MockObject\MockObject */
    private $cookie;
    private $secret = 'test-secret-key';
    private $cookieName = 'LPSESSID';

    protected function setUp(): void
    {
        $this->driver = $this->createMock(StoreInterface::class);
        $this->cookie = $this->createMock(Cookie::class);
        
        $this->store = new Store(
            $this->driver,
            $this->secret,
            $this->cookieName
        );

        // Use reflection to inject mock cookie
        $reflection = new \ReflectionClass($this->store);
        $property = $reflection->getProperty('cookie');
        $property->setAccessible(true);
        $property->setValue($this->store, $this->cookie);
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

        $this->assertTrue($this->store->start());
        $this->assertEquals($id, $this->store->getId());
        $this->assertEquals($data['key'], $this->store->get('key'));
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

        $this->assertTrue($this->store->start());
        $this->assertEquals($newId, $this->store->getId());
    }

    public function testSetAndGetWithDotNotation()
    {
        $this->store->start();

        $this->store->set('user.profile.name', 'John');
        $this->assertEquals('John', $this->store->get('user.profile.name'));

        $expected = [
            'user' => [
                'profile' => [
                    'name' => 'John'
                ]
            ]
        ];

        $this->assertEquals($expected['user'], $this->store->get('user'));
    }

    public function testFlashMessages()
    {
        $this->store->start();

        $this->store->flash('success', 'Operation completed');
        $this->assertEquals('Operation completed', $this->store->get('_flash.new.success'));

        // Age flash data
        $reflection = new \ReflectionClass($this->store);
        $method = $reflection->getMethod('ageFlashData');
        $method->setAccessible(true);
        $method->invoke($this->store);

        $this->assertEquals('Operation completed', $this->store->flash('success'));
        $this->assertNull($this->store->flash('success')); // Second read should be null
    }

    public function testCsrfTokenGeneration()
    {
        $this->store->start();

        $token = $this->store->token();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars

        // Token should persist
        $this->assertEquals($token, $this->store->token());
    }

    public function testCsrfTokenVerification()
    {
        $this->store->start();
        $token = $this->store->token();

        // Test header verification
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $this->assertTrue($this->store->verifyToken());

        // Test POST verification
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_token'] = $token;
        $this->assertTrue($this->store->verifyToken());

        // Test invalid token
        $_POST['_token'] = 'invalid-token';
        $this->assertFalse($this->store->verifyToken());
    }

    public function testSessionRegeneration()
    {
        $this->store->start();
        $oldId = $this->store->getId();
        $data = ['key' => 'value'];
        
        $this->store->set('key', 'value');

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

        $this->assertTrue($this->store->regenerate());
        $this->assertEquals($newId, $this->store->getId());
        $this->assertEquals('value', $this->store->get('key'));
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

        $this->store->start();

        $this->driver->expects($this->once())
            ->method('destroy')
            ->with($id);

        $this->cookie->expects($this->once())
            ->method('delete')
            ->with($this->cookieName);

        $this->store->destroy();
        $this->assertNull($this->store->getId());
        $this->assertEmpty($this->store->all());
    }
}
