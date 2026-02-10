<?php

namespace Lightpack\Pwa;

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
        $this->validateSource($sourcePath);
        $this->ensureIconsDirectory();

        $sourceImage = $this->loadImage($sourcePath);
        $icons = [];

        foreach ($sizes as $size) {
            $iconPath = $this->generateIcon($sourceImage, $size);
            $icons[] = [
                'src' => '/icons/' . basename($iconPath),
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
            ];
        }

        imagedestroy($sourceImage);

        return $icons;
    }

    /**
     * Validate source image
     */
    protected function validateSource(string $sourcePath): void
    {
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source image not found: {$sourcePath}");
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException("Invalid image file: {$sourcePath}");
        }

        // Check if GD library is available
        if (!extension_loaded('gd')) {
            throw new \RuntimeException("GD library is required for icon generation");
        }
    }

    /**
     * Ensure icons directory exists
     */
    protected function ensureIconsDirectory(): void
    {
        if (!is_dir($this->iconsDir)) {
            if (!mkdir($this->iconsDir, 0755, true)) {
                throw new \RuntimeException("Failed to create icons directory: {$this->iconsDir}");
            }
        }
    }

    /**
     * Load image from file
     */
    protected function loadImage(string $path)
    {
        $imageInfo = getimagesize($path);
        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                throw new \InvalidArgumentException("Unsupported image type: {$mimeType}");
        }
    }

    /**
     * Generate single icon at specified size
     */
    protected function generateIcon($sourceImage, int $size): string
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Create new image with transparency
        $icon = imagecreatetruecolor($size, $size);
        
        // Enable alpha blending
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        
        // Fill with transparent background
        $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
        imagefill($icon, 0, 0, $transparent);
        
        // Enable alpha blending for resampling
        imagealphablending($icon, true);

        // Resize image
        imagecopyresampled(
            $icon,
            $sourceImage,
            0, 0, 0, 0,
            $size, $size,
            $sourceWidth, $sourceHeight
        );

        // Save icon
        $filename = "icon-{$size}x{$size}.png";
        $path = $this->iconsDir . '/' . $filename;

        if (!imagepng($icon, $path, 9)) {
            throw new \RuntimeException("Failed to save icon: {$path}");
        }

        imagedestroy($icon);

        return $path;
    }

    /**
     * Generate maskable icon (with safe zone)
     */
    public function generateMaskable(string $sourcePath, int $size = 512): string
    {
        $this->validateSource($sourcePath);
        $this->ensureIconsDirectory();

        $sourceImage = $this->loadImage($sourcePath);
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Create canvas with safe zone (80% of size)
        $icon = imagecreatetruecolor($size, $size);
        
        // Enable alpha blending
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        
        // Fill with background color (or transparent)
        $background = imagecolorallocatealpha($icon, 255, 255, 255, 0);
        imagefill($icon, 0, 0, $background);
        
        imagealphablending($icon, true);

        // Calculate safe zone (80% of canvas)
        $safeZoneSize = (int) ($size * 0.8);
        $offset = (int) (($size - $safeZoneSize) / 2);

        // Resize and place image in safe zone
        imagecopyresampled(
            $icon,
            $sourceImage,
            $offset, $offset, 0, 0,
            $safeZoneSize, $safeZoneSize,
            $sourceWidth, $sourceHeight
        );

        // Save maskable icon
        $filename = "icon-{$size}x{$size}-maskable.png";
        $path = $this->iconsDir . '/' . $filename;

        if (!imagepng($icon, $path, 9)) {
            throw new \RuntimeException("Failed to save maskable icon: {$path}");
        }

        imagedestroy($icon);
        imagedestroy($sourceImage);

        return $path;
    }

    /**
     * Generate favicon.ico
     */
    public function generateFavicon(string $sourcePath): string
    {
        $this->validateSource($sourcePath);

        $sourceImage = $this->loadImage($sourcePath);
        
        // Create 32x32 icon for favicon
        $favicon = imagecreatetruecolor(32, 32);
        
        imagealphablending($favicon, false);
        imagesavealpha($favicon, true);
        
        $transparent = imagecolorallocatealpha($favicon, 0, 0, 0, 127);
        imagefill($favicon, 0, 0, $transparent);
        
        imagealphablending($favicon, true);

        imagecopyresampled(
            $favicon,
            $sourceImage,
            0, 0, 0, 0,
            32, 32,
            imagesx($sourceImage), imagesy($sourceImage)
        );

        $path = $this->publicPath . '/favicon.ico';
        
        // Save as PNG (browsers support PNG favicons)
        if (!imagepng($favicon, $path, 9)) {
            throw new \RuntimeException("Failed to save favicon: {$path}");
        }

        imagedestroy($favicon);
        imagedestroy($sourceImage);

        return $path;
    }
}
