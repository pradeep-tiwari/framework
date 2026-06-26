<?php

namespace Lightpack\Pwa;

use Lightpack\Utils\Image;

/**
 * IconGenerator - Generates PWA icons from source image
 *
 * Creates multiple icon sizes required for PWA manifest
 * using GD library for image manipulation.
 */
class IconGenerator
{
    protected string $publicPath;
    protected string $iconsDir;

    public function __construct(string $publicPath)
    {
        $this->publicPath = $publicPath;
        $this->iconsDir = $publicPath . '/icons';
    }

    /**
     * Generate icons from source image
     */
    public function generate(string $sourcePath, array $sizes): array
    {
        $this->ensureIconsDirectory();

        $source = new Image($sourcePath);
        $icons = [];

        foreach ($sizes as $size) {
            $path = $this->iconsDir . "/icon-{$size}x{$size}.png";
            (clone $source)->resize($size, $size)->save($path);
            $icons[] = [
                'src' => '/icons/' . basename($path),
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
            ];
        }

        return $icons;
    }

    /**
     * Generate a maskable icon for Android home screens.
     *
     * Android devices can clip app icons into different shapes (circle, rounded
     * square, etc.) depending on the launcher. A maskable icon places your image
     * in the inner 80% of the canvas so it always looks good regardless of the
     * shape applied — no awkward white circles or cut-off edges.
     */
    public function generateMaskable(string $sourcePath, int $size = 512): string
    {
        $this->ensureIconsDirectory();

        $safeZoneSize = (int) ($size * 0.8);
        $path = $this->iconsDir . "/icon-{$size}x{$size}-maskable.png";

        (new Image($sourcePath))
            ->resize($safeZoneSize, $safeZoneSize)
            ->pad($size, $size)
            ->save($path);

        return $path;
    }

    /**
     * Generate favicon.png
     */
    public function generateFavicon(string $sourcePath): string
    {
        $path = $this->publicPath . '/favicon.png';

        (new Image($sourcePath))->resize(32, 32)->save($path);

        return $path;
    }

    /**
     * Ensure icons directory exists
     */
    protected function ensureIconsDirectory(): void
    {
        if (! is_dir($this->iconsDir)) {
            if (! mkdir($this->iconsDir, 0755, true)) {
                throw new \RuntimeException("Failed to create icons directory: {$this->iconsDir}");
            }
        }
    }
}
