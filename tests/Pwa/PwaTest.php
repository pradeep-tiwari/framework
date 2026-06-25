<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\Pwa;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PwaTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/pwa_core_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    private function pwa(array $config = []): Pwa
    {
        return new Pwa(array_merge(['public_path' => $this->tmpDir], $config));
    }

    private function getConfig(Pwa $pwa): array
    {
        $ref = new ReflectionClass($pwa);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);

        return $prop->getValue($pwa);
    }

    // -----------------------------------------------------------------------
    // Constructor merges defaults with user config
    // -----------------------------------------------------------------------

    public function testDefaultConfigIsApplied(): void
    {
        $pwa = $this->pwa();
        $config = $this->getConfig($pwa);

        $this->assertEquals('standalone', $config['display']);
        $this->assertEquals('#4F46E5', $config['theme_color']);
        $this->assertEquals('app-v1', $config['cache_name']);
    }

    public function testUserConfigOverridesDefaults(): void
    {
        $pwa = $this->pwa(['theme_color' => '#FF0000', 'display' => 'fullscreen']);
        $config = $this->getConfig($pwa);

        $this->assertEquals('#FF0000', $config['theme_color']);
        $this->assertEquals('fullscreen', $config['display']);
    }

    public function testPartialUserConfigPreservesOtherDefaults(): void
    {
        $pwa = $this->pwa(['theme_color' => '#FF0000']);
        $config = $this->getConfig($pwa);

        // Overridden
        $this->assertEquals('#FF0000', $config['theme_color']);
        // Default preserved
        $this->assertEquals('standalone', $config['display']);
        $this->assertEquals('app-v1', $config['cache_name']);
    }

    // -----------------------------------------------------------------------
    // register() generates valid service worker script
    // -----------------------------------------------------------------------

    public function testRegisterReturnsScriptTag(): void
    {
        $html = $this->pwa()->register();

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    public function testRegisterContainsServiceWorkerCheck(): void
    {
        $html = $this->pwa()->register();

        $this->assertStringContainsString('serviceWorker', $html);
        $this->assertStringContainsString('navigator', $html);
    }

    public function testRegisterUsesConfiguredSwUrl(): void
    {
        $html = $this->pwa(['sw_url' => '/worker.js'])->register();

        $this->assertStringContainsString('/worker.js', $html);
        $this->assertStringNotContainsString('/sw.js', $html);
    }

    public function testRegisterUsesConfiguredScope(): void
    {
        $html = $this->pwa(['sw_scope' => '/app/'])->register();

        $this->assertStringContainsString('/app/', $html);
    }

    public function testRegisterDefaultSwUrlIsSwJs(): void
    {
        $html = $this->pwa()->register();

        $this->assertStringContainsString('/sw.js', $html);
    }

    public function testRegisterExposesWindowPwaConfig(): void
    {
        $html = $this->pwa()->register();

        $this->assertStringContainsString('window.PWAConfig', $html);
        $this->assertStringContainsString('swUrl', $html);
        $this->assertStringContainsString('scope', $html);
    }

    // -----------------------------------------------------------------------
    // meta() generates valid HTML head tags
    // -----------------------------------------------------------------------

    public function testMetaReturnsManifestLink(): void
    {
        $html = $this->pwa()->meta();

        $this->assertStringContainsString('<link rel="manifest"', $html);
        $this->assertStringContainsString('/manifest.json', $html);
    }

    public function testMetaReturnsThemeColorMeta(): void
    {
        $html = $this->pwa(['theme_color' => '#ABCDEF'])->meta();

        $this->assertStringContainsString('theme-color', $html);
        $this->assertStringContainsString('#ABCDEF', $html);
    }

    public function testMetaReturnsViewportMeta(): void
    {
        $html = $this->pwa()->meta();

        $this->assertStringContainsString('viewport', $html);
    }

    public function testMetaReturnsAppleMobileCapable(): void
    {
        $html = $this->pwa()->meta();

        $this->assertStringContainsString('apple-mobile-web-app-capable', $html);
    }

    public function testMetaIncludesFaviconLinkWhenFileExists(): void
    {
        file_put_contents($this->tmpDir . '/favicon.png', '');

        $html = $this->pwa()->meta();

        $this->assertStringContainsString('favicon.png', $html);
    }

    public function testMetaOmitsFaviconLinkWhenFileAbsent(): void
    {
        $html = $this->pwa()->meta();

        $this->assertStringNotContainsString('favicon', $html);
    }

    // -----------------------------------------------------------------------
    // serviceWorker() generates sw.js
    // -----------------------------------------------------------------------

    public function testServiceWorkerWritesSwJsFile(): void
    {
        $this->pwa()->serviceWorker();

        $this->assertFileExists($this->tmpDir . '/sw.js');
    }

    // -----------------------------------------------------------------------
    // offlinePage() generates offline.html
    // -----------------------------------------------------------------------

    public function testOfflinePageWritesOfflineHtmlFile(): void
    {
        $this->pwa()->offlinePage();

        $this->assertFileExists($this->tmpDir . '/offline.html');
    }

    public function testOfflinePageContainsRetryButton(): void
    {
        $this->pwa()->offlinePage(['retry_button' => true]);

        $content = file_get_contents($this->tmpDir . '/offline.html');
        $this->assertStringContainsString('Try Again', $content);
    }
}
