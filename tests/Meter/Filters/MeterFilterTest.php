<?php

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Meter\Filters\MeterFilter;
use Lightpack\Meter\MeterManager;
use PHPUnit\Framework\TestCase;

final class MeterFilterTest extends TestCase
{
    private MeterFilter $filter;
    private Request $request;
    private Response $response;

    public function setUp(): void
    {
        $container = Container::getInstance();

        $container->register('config', function () {
            return new class {
                public function get($key, $default = null)
                {
                    return $default;
                }
            };
        });

        $container->register('auth', function () {
            return new class {
                public function user()
                {
                    return null;
                }
            };
        });

        $container->register('meter.manager', function ($container) {
            return new MeterManager($container);
        });

        $container->get('meter.manager')->setDefaultDriver('memory');

        $this->filter = new MeterFilter;
        $this->request = new Request;
        $this->response = new Response;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        Container::destroy();
    }

    public function testAllowsRequestsWithinLimit(): void
    {
        $this->filter->before($this->request, ['api_calls', 3]);
        $this->filter->before($this->request, ['api_calls', 3]);
        $result = $this->filter->after($this->request, $this->response, ['api_calls', 3]);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testBlocksRequestsExceedingLimit(): void
    {
        $this->filter->before($this->request, ['api_calls', 2]);
        $this->filter->before($this->request, ['api_calls', 2]);

        $this->expectException(\Lightpack\Exceptions\TooManyRequestsException::class);
        $this->filter->before($this->request, ['api_calls', 2]);
    }

    public function testThrowsOnMissingParams(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->filter->before($this->request, []);
    }
}
