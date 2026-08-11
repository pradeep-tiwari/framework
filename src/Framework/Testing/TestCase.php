<?php

namespace Lightpack\Testing;

use Lightpack\App;
use Lightpack\Auth\IdentityInterface;
use Lightpack\Container\Container;
use Lightpack\Filters\FilterProvider;
use Lightpack\Http\Response;
use Lightpack\Mail\Mail;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * @method void beginTransaction()
 * @method void rollbackTransaction()
 */
class TestCase extends BaseTestCase
{
    use AssertionTrait;
    use MailAssertionTrait;

    protected Container $container;
    protected Response $response;
    protected $isJsonRequest = false;
    protected $isMultipartFormdata = false;

    /** @var IdentityInterface|null User to authenticate before each request. */
    protected ?IdentityInterface $actingAsUser = null;

    /** @var bool When true, route filters are bypassed for the next request. */
    protected bool $bypassFilters = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootApp();

        $this->container = Container::getInstance();

        Mail::clearSentMails();

        // Reset all superglobals that carry request state between tests.
        $_POST   = [];
        $_GET    = [];
        $_FILES  = [];
        $_COOKIE = [];

        if (method_exists($this, 'beginTransaction')) {
            $this->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        try {
            if (method_exists($this, 'rollbackTransaction')) {
                $this->rollbackTransaction();
            }
        } catch (\Throwable $e) {
            // Swallow rollback errors so cleanup always continues.
        }

        // Ensure user identity is cleared after each test.
        try {
            if ($this->container->get('config')->has('auth')) {
                auth()->logout();
            }
        } catch (\Throwable $e) {
            // Swallow logout errors so reset still runs.
        }

        // Reset per-test state.
        $this->actingAsUser = null;
        $this->bypassFilters = false;

        parent::tearDown();

        Container::getInstance()->reset();
    }

    public function request(string $method, string $route, array $params = []): Response
    {
        $method = strtoupper($method);

        // Parse the route to separate path and query
        $parsedUrl = parse_url($route);
        $path = $parsedUrl['path'];
        $queryString = $parsedUrl['query'] ?? '';

        // Parse query parameters
        parse_str($queryString, $queryParams);

        // Set GET/POST params.
        if ($method === 'GET') {
            $_GET = array_merge($queryParams, $params);
            $_POST = [];
        } else {
            $params['_token'] = csrf_token();
            $_POST = $params;
            $_GET = $queryParams;
        }

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_URI'] = empty($queryString) ? $path : "{$path}?{$queryString}";
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        if ($this->isJsonRequest) {
            $_SERVER['HTTP_ACCEPT'] = 'application/json';
            $_SERVER['X_LIGHTPACK_RAW_INPUT'] = json_encode($params);
        } else {
            $_SERVER['HTTP_ACCEPT'] = 'text/html';
        }

        $this->setRequestContentType();
        $this->registerAppRequest();
        $this->container->get('request')->setMethod($method);

        // Bypass route filters for this request when withoutFilters() was called.
        if ($this->bypassFilters) {
            $this->container->register('filter', function () {
                return new class {
                    private $response;

                    public function register(string $route, string $filter, array $params = []): void {}

                    public function setResponse($response): void
                    {
                        $this->response = $response;
                    }

                    public function processBeforeFilters(string $route): void {}

                    public function processAfterFilters(string $route)
                    {
                        return $this->response;
                    }
                };
            });
        }

        // Authenticate as the specified user before dispatching.
        if ($this->actingAsUser !== null) {
            auth()->loginAs($this->actingAsUser);
        }

        $response = $this->response = \Lightpack\App::run();

        // Restore the real filter service so subsequent requests are unaffected.
        if ($this->bypassFilters) {
            (new FilterProvider)->register($this->container);
            $this->bypassFilters = false;
        }

        // Reset per-request flags and superglobals so they do not bleed into
        // subsequent request() calls within the same test.
        $this->isJsonRequest = false;
        $this->isMultipartFormdata = false;
        $_FILES = [];

        return $response;
    }

    public function requestJson(string $method, string $route, array $params = []): Response
    {
        $this->isJsonRequest = true;

        return $this->request($method, $route, $params);
    }

    /**
     * Set a user to be authenticated before every subsequent request() call.
     *
     * The user remains active for the lifetime of the test. Call it once and
     * all further request() calls in that test run as that user.
     *
     * @param IdentityInterface $user
     * @return self
     */
    public function actingAs(IdentityInterface $user): self
    {
        $this->actingAsUser = $user;

        return $this;
    }

    /**
     * Bypass route filters for the next request() call only.
     *
     * Useful when testing controller logic in isolation, without rate-limiting,
     * auth, or other filters interfering.
     *
     * @return self
     */
    public function withoutFilters(): self
    {
        $this->bypassFilters = true;

        return $this;
    }

    protected function registerAppRequest()
    {
        $this->container->register('request', function () {
            return new \Lightpack\Http\Request('/');
        });

        $this->container->alias(\Lightpack\Http\Request::class, 'request');
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] = $value;
        }

        return $this;
    }

    public function withCookies(array $cookies): self
    {
        foreach ($cookies as $cookie => $value) {
            $_COOKIE[$cookie] = $value;
        }

        return $this;
    }

    public function withSession(array $session): self
    {
        foreach ($session as $key => $value) {
            session()->set($key, $value);
        }

        return $this;
    }

    public function withFiles(array $files): self
    {
        $this->isMultipartFormdata = true;

        foreach ($files as $file => $value) {
            $_FILES[$file] = $value;
        }

        return $this;
    }

    public function getArrayResponse(): array
    {
        if (! $this->isJsonRequest) {
            return [];
        }

        return json_decode($this->response->getBody(), true);
    }

    protected function setRequestContentType()
    {
        if ($this->isMultipartFormdata) {
            $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';

            return;
        }

        if ($this->isJsonRequest) {
            $_SERVER['CONTENT_TYPE'] = 'application/json';

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

            return;
        }

        $_SERVER['CONTENT_TYPE'] = 'text/html';
    }

    protected function bootApp(): void
    {
        $cwd = getcwd();

        require_once $cwd . '/vendor/autoload.php';
        require_once $cwd . '/boot/constants.php';

        App::boot();
    }
}
