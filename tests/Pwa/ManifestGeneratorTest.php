<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\ManifestGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ManifestGeneratorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/pwa_manifest_' . uniqid();
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

    private function generator(): ManifestGenerator
    {
        return new ManifestGenerator($this->tmpDir, 'https://example.com');
    }

    private function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test App',
            'short_name' => 'App',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#4F46E5',
            'orientation' => 'any',
            'scope' => '/',
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
    // generate() writes valid JSON
    // -----------------------------------------------------------------------

    public function testGenerateWritesManifestJson(): void
    {
        $this->generator()->generate($this->baseConfig());

        $this->assertFileExists($this->tmpDir . '/manifest.json');
    }

    public function testGeneratedManifestContainsRequiredFields(): void
    {
        $this->generator()->generate($this->baseConfig());

        $manifest = json_decode(file_get_contents($this->tmpDir . '/manifest.json'), true);

        $this->assertEquals('Test App', $manifest['name']);
        $this->assertEquals('App', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('#4F46E5', $manifest['theme_color']);
    }

    public function testGeneratedManifestIncludesDescription(): void
    {
        $this->generator()->generate($this->baseConfig(['description' => 'My PWA']));

        $manifest = json_decode(file_get_contents($this->tmpDir . '/manifest.json'), true);

        $this->assertEquals('My PWA', $manifest['description']);
    }

    public function testManifestWithoutDescriptionOmitsField(): void
    {
        $this->generator()->generate($this->baseConfig());

        $manifest = json_decode(file_get_contents($this->tmpDir . '/manifest.json'), true);

        $this->assertArrayNotHasKey('description', $manifest);
    }

    // -----------------------------------------------------------------------
    // validate() rejects bad values
    // -----------------------------------------------------------------------

    public function testValidateThrowsOnMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->generator()->generate($this->baseConfig(['name' => '']));
    }

    public function testValidateThrowsOnInvalidDisplayMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->generator()->generate($this->baseConfig(['display' => 'fullpage']));
    }

    public function testValidateThrowsOnInvalidOrientation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->generator()->generate($this->baseConfig(['orientation' => 'diagonal']));
    }

    public function testValidateThrowsOnInvalidThemeColor(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->generator()->generate($this->baseConfig(['theme_color' => 'notacolor']));
    }

    // -----------------------------------------------------------------------
    // isValidColor()
    // -----------------------------------------------------------------------

    public function testIsValidColorAcceptsHex6(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['#4F46E5']));
    }

    public function testIsValidColorAcceptsHex3(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['#FFF']));
    }

    public function testIsValidColorAcceptsRgb(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['rgb(255, 0, 0)']));
    }

    public function testIsValidColorAcceptsRgba(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['rgba(255, 0, 0, 0.5)']));
    }

    public function testIsValidColorAcceptsHsl(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['hsl(240, 100%, 50%)']));
    }

    public function testIsValidColorAcceptsNamedColor(): void
    {
        $gen = $this->generator();
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['white']));
        $this->assertTrue($this->callMethod($gen, 'isValidColor', ['transparent']));
    }

    public function testIsValidColorRejectsGarbage(): void
    {
        $gen = $this->generator();
        $this->assertFalse($this->callMethod($gen, 'isValidColor', ['notacolor']));
        $this->assertFalse($this->callMethod($gen, 'isValidColor', ['#GGGGGG']));
        $this->assertFalse($this->callMethod($gen, 'isValidColor', ['']));
    }

    // -----------------------------------------------------------------------
    // createIconEntry()
    // -----------------------------------------------------------------------

    public function testCreateIconEntryExtractsSizeFromFilename(): void
    {
        $gen = $this->generator();
        $entry = $this->callMethod($gen, 'createIconEntry', ['/icons/icon-192x192.png']);

        $this->assertEquals('192x192', $entry['sizes']);
        $this->assertEquals('image/png', $entry['type']);
        $this->assertStringContainsString('/icons/icon-192x192.png', $entry['src']);
    }

    public function testCreateIconEntryDefaultsToMaxSizeWhenNoSizeInName(): void
    {
        $gen = $this->generator();
        $entry = $this->callMethod($gen, 'createIconEntry', ['/icons/logo.png']);

        $this->assertEquals('512x512', $entry['sizes']);
    }

    // -----------------------------------------------------------------------
    // discoverIcons() scans directory
    // -----------------------------------------------------------------------

    public function testDiscoverIconsReturnsEmptyArrayWhenNoDirExists(): void
    {
        $gen = $this->generator();
        $icons = $this->callMethod($gen, 'discoverIcons');

        $this->assertIsArray($icons);
        $this->assertEmpty($icons);
    }

    public function testDiscoverIconsFindsIconFiles(): void
    {
        $iconsDir = $this->tmpDir . '/icons';
        mkdir($iconsDir);
        file_put_contents($iconsDir . '/icon-192x192.png', '');
        file_put_contents($iconsDir . '/icon-512x512.png', '');

        $gen = $this->generator();
        $icons = $this->callMethod($gen, 'discoverIcons');

        $this->assertCount(2, $icons);
        // Should be sorted ascending by size
        $this->assertEquals('192x192', $icons[0]['sizes']);
        $this->assertEquals('512x512', $icons[1]['sizes']);
    }
}
