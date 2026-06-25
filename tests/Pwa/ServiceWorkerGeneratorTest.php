<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\ServiceWorkerGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ServiceWorkerGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/pwa_sw_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    private function generator(): ServiceWorkerGenerator
    {
        return new ServiceWorkerGenerator($this->tmpDir);
    }

    private function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'cache_name' => 'test-v1',
            'precache' => [],
            'runtime_cache' => [],
            'offline_page' => '/offline.html',
            'strategies' => [],
        ], $overrides);
    }

    private function callMethod(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($obj, $args);
    }

    // -----------------------------------------------------------------------
    // generate() writes sw.js
    // -----------------------------------------------------------------------

    public function testGenerateWritesSwJsFile(): void
    {
        $this->generator()->generate($this->baseConfig());

        $this->assertFileExists($this->tmpDir . '/sw.js');
    }

    public function testGeneratedSwContainsCacheName(): void
    {
        $this->generator()->generate($this->baseConfig(['cache_name' => 'my-app-v2']));

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('my-app-v2', $content);
    }

    public function testGeneratedSwContainsPrecachedUrls(): void
    {
        $this->generator()->generate($this->baseConfig([
            'precache' => ['/css/app.css', '/js/app.js'],
        ]));

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('/css/app.css', $content);
        $this->assertStringContainsString('/js/app.js', $content);
    }

    public function testGeneratedSwContainsOfflinePage(): void
    {
        $this->generator()->generate($this->baseConfig(['offline_page' => '/custom-offline.html']));

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('/custom-offline.html', $content);
    }

    public function testGeneratedSwContainsInstallActivateFetchEvents(): void
    {
        $this->generator()->generate($this->baseConfig());

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString("'install'", $content);
        $this->assertStringContainsString("'activate'", $content);
        $this->assertStringContainsString("'fetch'", $content);
    }

    public function testGeneratedSwContainsPushEvent(): void
    {
        $this->generator()->generate($this->baseConfig());

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString("'push'", $content);
    }

    public function testGeneratedSwHasNoConsoleLogs(): void
    {
        $this->generator()->generate($this->baseConfig());

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringNotContainsString('console.log', $content);
        $this->assertStringNotContainsString('console.error', $content);
    }

    public function testGeneratedSwHasPerStrategyCaches(): void
    {
        $this->generator()->generate($this->baseConfig());

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('CACHE_ASSETS', $content);
        $this->assertStringContainsString('CACHE_DYNAMIC', $content);
    }

    // -----------------------------------------------------------------------
    // Runtime caching strategies are included when configured
    // -----------------------------------------------------------------------

    public function testRuntimeCacheStrategyIsEmbedded(): void
    {
        $this->generator()->generate($this->baseConfig([
            'runtime_cache' => [
                '/api/*' => 'network-first',
                '/img/*' => 'cache-first',
            ],
        ]));

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('\/api\/', $content);
        $this->assertStringContainsString('\/img\/', $content);
    }

    // -----------------------------------------------------------------------
    // toRegex() converts URL patterns
    // -----------------------------------------------------------------------

    public function testToRegexConvertsWildcard(): void
    {
        $gen = $this->generator();
        $regex = $this->callMethod($gen, 'patternToRegex', ['/api/*']);

        $this->assertStringContainsString('.*', $regex);
        $this->assertStringStartsWith('/', $regex);
        $this->assertStringEndsWith('/', $regex);
    }

    public function testToRegexProducesValidRegex(): void
    {
        $gen = $this->generator();
        $regex = $this->callMethod($gen, 'patternToRegex', ['/img/*']);

        $this->assertEquals(1, preg_match($regex, '/img/logo.png'));
        $this->assertEquals(0, preg_match($regex, '/css/app.css'));
    }

    // -----------------------------------------------------------------------
    // Offline fallback returns 503 for non-navigate requests
    // -----------------------------------------------------------------------

    public function testOfflineFallbackHas503ForNonNavigate(): void
    {
        $this->generator()->generate($this->baseConfig());

        $content = file_get_contents($this->tmpDir . '/sw.js');
        $this->assertStringContainsString('503', $content);
        $this->assertStringContainsString('Service Unavailable', $content);
    }
}
