<?php

namespace Lightpack\Tests\Console\Commands;

use Lightpack\Console\Commands\RouteListCommand;
use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Routing\RouteRegistry;
use PHPUnit\Framework\TestCase;

class RouteListCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $container->instance('request', new Request);
        $container->register('route', fn ($container) => new RouteRegistry($container));
    }

    protected function tearDown(): void
    {
        Container::getInstance()->reset();
        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunFailsWhenDirBootIsNotDefined()
    {
        $command = new RouteListCommand([]);

        ob_start();
        $exit = $command->run();
        $output = ob_get_clean();

        $this->assertSame(RouteListCommand::FAILURE, $exit);
        $this->assertStringContainsString('DIR_BOOT is not defined', $output);
    }

    public function testRunListsRegisteredRoutes()
    {
        if (! defined('DIR_BOOT')) {
            define('DIR_BOOT', __DIR__ . '/../../fixtures/boot');
        }

        $command = new RouteListCommand([]);

        ob_start();
        $exit = $command->run();
        $output = ob_get_clean();

        $this->assertSame(RouteListCommand::SUCCESS, $exit);
        $this->assertStringContainsString('METHOD', $output);
        $this->assertStringContainsString('GET', $output);
        $this->assertStringContainsString('/fixture-users', $output);
        $this->assertStringContainsString('FixtureUserController@index', $output);
        $this->assertStringContainsString('fixture.users', $output);
        $this->assertStringContainsString('auth,csrf', $output);
        $this->assertStringContainsString('POST', $output);
        $this->assertStringContainsString('/fixture-login', $output);
        $this->assertStringContainsString('FixtureAuthController@authenticate', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunReportsWhenNoRoutesRegistered()
    {
        define('DIR_BOOT', __DIR__ . '/../../fixtures/boot-empty');

        $command = new RouteListCommand([]);

        ob_start();
        $exit = $command->run();
        $output = ob_get_clean();

        $this->assertSame(RouteListCommand::SUCCESS, $exit);
        $this->assertStringContainsString('No routes registered', $output);
    }
}
