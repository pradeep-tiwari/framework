<?php

namespace Lightpack\Tests\Testing;

use Lightpack\Container\Container;
use Lightpack\Http\Redirect;
use Lightpack\Http\Response;
use Lightpack\Testing\AssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * Stub services for testing session/cookie/auth/route assertions.
 *
 * These mimic the public surface used by AssertionTrait without
 * requiring a real HTTP or session stack.
 */
class StubSession
{
    private array $data = [];

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }
}

class StubCookie
{
    private array $data = [];

    public function set(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }

    public function get($key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function delete($key): void
    {
        unset($this->data[$key]);
    }
}

class StubAuth
{
    private bool $loggedIn = false;

    public function setLoggedIn(bool $value): void
    {
        $this->loggedIn = $value;
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    public function isGuest(): bool
    {
        return ! $this->loggedIn;
    }
}

class StubRouteRegistry
{
    public function url(string $name, array $params = []): string
    {
        $query = $params ? '?' . http_build_query($params) : '';

        return "/{$name}{$query}";
    }
}

class AssertionTraitTest extends TestCase
{
    use AssertionTrait;

    protected Container $container;

    protected Response $response;

    protected StubSession $session;
    protected StubCookie $cookie;
    protected StubAuth $auth;
    protected StubRouteRegistry $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = Container::getInstance();

        $this->session = new StubSession;
        $this->cookie = new StubCookie;
        $this->auth = new StubAuth;
        $this->route = new StubRouteRegistry;

        $this->container->register('session', fn () => $this->session);
        $this->container->register('cookie', fn () => $this->cookie);
        $this->container->register('auth', fn () => $this->auth);
        $this->container->register('route', fn () => $this->route);
    }

    public function getArrayResponse(): array
    {
        return json_decode($this->response->getBody(), true) ?? [];
    }

    protected function tearDown(): void
    {
        Container::getInstance()->reset();
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // Response status / body / JSON assertions
    // ---------------------------------------------------------------------

    public function testAssertResponseStatus()
    {
        $this->response = (new Response)->setStatus(201);

        $this->assertResponseStatus(201);
    }

    public function testAssertResponseBody()
    {
        $this->response = (new Response)->setBody('hello world');

        $this->assertResponseBody('hello world');
    }

    public function testAssertResponseBodyContains()
    {
        $this->response = (new Response)->setBody('{"message":"login successful","token":"abc"}');

        $this->assertResponseBodyContains('login successful');
    }

    public function testAssertResponseHasValidJson()
    {
        $this->response = (new Response)->setBody('{"ok":true}');

        $this->assertResponseHasValidJson();
    }

    public function testAssertResponseJson()
    {
        $this->response = (new Response)->setBody('{"ok":true}');

        $this->assertResponseJson(['ok' => true]);
    }

    public function testAssertResponseJsonHasKey()
    {
        $this->response = (new Response)->setBody('{"data":{"id":42},"meta":{"page":1}}');

        $this->assertResponseJsonHasKey('data.id')
            ->assertResponseJsonHasKey('meta.page');
    }

    public function testAssertResponseJsonKeyValue()
    {
        $this->response = (new Response)->setBody('{"data":{"id":42}}');

        $this->assertResponseJsonKeyValue('data.id', 42);
    }

    public function testAssertResponseJsonKeyMissing()
    {
        $this->response = (new Response)->setBody('{"data":{"id":42}}');

        $this->assertResponseJsonKeyMissing('data.password');
    }

    // ---------------------------------------------------------------------
    // Header assertions
    // ---------------------------------------------------------------------

    public function testAssertResponseHasHeader()
    {
        $this->response = (new Response)->setHeader('X-Test', 'yes');

        $this->assertResponseHasHeader('X-Test');
    }

    public function testAssertResponseHeaderEquals()
    {
        $this->response = (new Response)->setHeader('Content-Type', 'application/json');

        $this->assertResponseHeaderEquals('Content-Type', 'application/json');
    }

    // ---------------------------------------------------------------------
    // Redirect assertions
    // ---------------------------------------------------------------------

    public function testAssertResponseIsRedirect()
    {
        $this->response = new Redirect;

        $this->assertResponseIsRedirect();
    }

    public function testAssertRedirectUrl()
    {
        $this->response = (new Redirect)->setRedirectUrl('/dashboard');

        $this->assertRedirectUrl('/dashboard');
    }

    public function testAssertRedirectRoute()
    {
        $this->response = (new Redirect)->setRedirectUrl('/dashboard');

        $this->assertRedirectRoute('dashboard');
    }

    // ---------------------------------------------------------------------
    // Session assertions
    // ---------------------------------------------------------------------

    public function testAssertSessionHasKeyOnly()
    {
        $this->session->set('user_id', 7);

        $this->assertSessionHas('user_id');
    }

    public function testAssertSessionHasKeyAndValue()
    {
        $this->session->set('user_id', 7);

        $this->assertSessionHas('user_id', 7);
    }

    public function testAssertSessionMissing()
    {
        $this->assertSessionMissing('user_id');
    }

    public function testAssertSessionHasErrorsWithoutKeys()
    {
        $this->session->set('_validation_errors', ['email' => 'Invalid']);

        $this->assertSessionHasErrors();
    }

    public function testAssertSessionHasErrorsWithKeys()
    {
        $this->session->set('_validation_errors', ['email' => 'Invalid', 'name' => 'Required']);

        $this->assertSessionHasErrors(['email', 'name']);
    }

    public function testAssertSessionHasNoErrors()
    {
        $this->assertSessionHasNoErrors();
    }

    public function testAssertSessionHasOldInputWithoutKeys()
    {
        $this->session->set('_old_input', ['email' => 'a@b.com']);

        $this->assertSessionHasOldInput();
    }

    public function testAssertSessionHasOldInputWithKeys()
    {
        $this->session->set('_old_input', ['email' => 'a@b.com', 'name' => 'Jane']);

        $this->assertSessionHasOldInput(['email', 'name']);
    }

    public function testAssertSessionHasOldInputWithKeyAndValue()
    {
        $this->session->set('_old_input', ['email' => 'a@b.com']);

        $this->assertSessionHasOldInput(['email' => 'a@b.com']);
    }

    // ---------------------------------------------------------------------
    // Cookie assertions
    // ---------------------------------------------------------------------

    public function testAssertCookieHas()
    {
        $this->cookie->set('theme', 'dark');

        $this->assertCookieHas('theme');
    }

    public function testAssertCookieEquals()
    {
        $this->cookie->set('theme', 'dark');

        $this->assertCookieEquals('theme', 'dark');
    }

    public function testAssertCookieMissing()
    {
        $this->assertCookieMissing('theme');
    }

    // ---------------------------------------------------------------------
    // Auth assertions
    // ---------------------------------------------------------------------

    public function testAssertAuthenticated()
    {
        $this->auth->setLoggedIn(true);

        $this->assertAuthenticated();
    }

    public function testAssertGuest()
    {
        $this->auth->setLoggedIn(false);

        $this->assertGuest();
    }

    // ---------------------------------------------------------------------
    // Fluent chaining
    // ---------------------------------------------------------------------

    public function testFluentChaining()
    {
        $this->response = (new Response)
            ->setStatus(200)
            ->setBody('{"data":{"id":42},"meta":{"page":1}}')
            ->setHeader('Content-Type', 'application/json');

        $this->assertResponseStatus(200)
            ->assertResponseBodyContains('"id":42')
            ->assertResponseHasValidJson()
            ->assertResponseJsonHasKey('data.id')
            ->assertResponseJsonKeyMissing('data.password')
            ->assertResponseHasHeader('Content-Type')
            ->assertResponseHeaderEquals('Content-Type', 'application/json');
    }
}
