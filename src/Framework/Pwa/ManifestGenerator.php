<?php

namespace Lightpack\Pwa;

/**
 * ManifestGenerator - Generates web app manifest.json
 * 
 * Creates a valid Web App Manifest for Progressive Web Apps
 * with proper validation and defaults.
 */
class ManifestGenerator
{
    protected string $publicPath;
    protected string $baseUrl;

    public function __construct(string $publicPath, string $baseUrl)
    {
        $this->publicPath = $publicPath;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Generate manifest.json file
     */
    public function generate(array $config): void
    {
        $manifest = $this->buildManifest($config);
        $this->validate($manifest);
        $this->write($manifest);
    }

    /**
     * Build manifest structure
     */
    protected function buildManifest(array $config): array
    {
        $manifest = [
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'start_url' => $config['start_url'],
            'display' => $config['display'],
            'background_color' => $config['background_color'],
            'theme_color' => $config['theme_color'],
            'orientation' => $config['orientation'],
            'scope' => $config['scope'],
        ];

        // Add description if provided
        if (!empty($config['description'])) {
            $manifest['description'] = $config['description'];
        }

        // Add icons
        if (!empty($config['icons'])) {
            $manifest['icons'] = $this->formatIcons($config['icons']);
        } else {
            $manifest['icons'] = $this->discoverIcons();
        }

        // Add categories if provided
        if (!empty($config['categories'])) {
            $manifest['categories'] = $config['categories'];
        }

        // Add screenshots if provided
        if (!empty($config['screenshots'])) {
            $manifest['screenshots'] = $config['screenshots'];
        }

        // Add shortcuts if provided
        if (!empty($config['shortcuts'])) {
            $manifest['shortcuts'] = $config['shortcuts'];
        }

        return $manifest;
    }

    /**
     * Format icons array
     */
    protected function formatIcons(array $icons): array
    {
        $formatted = [];

        foreach ($icons as $icon) {
            if (is_string($icon)) {
                // Simple path string
                $formatted[] = $this->createIconEntry($icon);
            } elseif (is_array($icon)) {
                // Full icon config
                $formatted[] = $icon;
            }
        }

        return $formatted;
    }

    /**
     * Create icon entry from path
     */
    protected function createIconEntry(string $path): array
    {
        // Extract size from filename (e.g., icon-192x192.png)
        if (preg_match('/(\d+)x(\d+)/', $path, $matches)) {
            $size = $matches[1] . 'x' . $matches[2];
        } else {
            $size = '512x512'; // Default
        }

        // Determine type from extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $type = 'image/' . ($extension === 'svg' ? 'svg+xml' : $extension);

        return [
            'src' => $this->baseUrl . '/' . ltrim($path, '/'),
            'sizes' => $size,
            'type' => $type,
        ];
    }

    /**
     * Auto-discover icons in public/icons directory
     */
    protected function discoverIcons(): array
    {
        $icons = [];
        $iconsDir = $this->publicPath . '/icons';

        if (!is_dir($iconsDir)) {
            return $icons;
        }

        $files = glob($iconsDir . '/icon-*.{png,jpg,jpeg,svg}', GLOB_BRACE);

        foreach ($files as $file) {
            $relativePath = '/icons/' . basename($file);
            $icons[] = $this->createIconEntry($relativePath);
        }

        // Sort by size
        usort($icons, function ($a, $b) {
            $sizeA = (int) explode('x', $a['sizes'])[0];
            $sizeB = (int) explode('x', $b['sizes'])[0];
            return $sizeA - $sizeB;
        });

        return $icons;
    }

    /**
     * Validate manifest structure
     */
    protected function validate(array $manifest): void
    {
        $required = ['name', 'short_name', 'start_url', 'display'];

        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                throw new \InvalidArgumentException("Manifest field '{$field}' is required");
            }
        }

        // Validate display mode
        $validDisplayModes = ['fullscreen', 'standalone', 'minimal-ui', 'browser'];
        if (!in_array($manifest['display'], $validDisplayModes)) {
            throw new \InvalidArgumentException("Invalid display mode: {$manifest['display']}");
        }

        // Validate orientation
        $validOrientations = ['any', 'natural', 'landscape', 'portrait', 'portrait-primary', 'portrait-secondary', 'landscape-primary', 'landscape-secondary'];
        if (!in_array($manifest['orientation'], $validOrientations)) {
            throw new \InvalidArgumentException("Invalid orientation: {$manifest['orientation']}");
        }

        // Validate colors
        if (!$this->isValidColor($manifest['theme_color'])) {
            throw new \InvalidArgumentException("Invalid theme_color: {$manifest['theme_color']}");
        }

        if (!$this->isValidColor($manifest['background_color'])) {
            throw new \InvalidArgumentException("Invalid background_color: {$manifest['background_color']}");
        }
    }

    /**
     * Validate color format
     */
    protected function isValidColor(string $color): bool
    {
        // Check hex color format
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }

    /**
     * Write manifest to file
     */
    protected function write(array $manifest): void
    {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $path = $this->publicPath . '/manifest.json';

        if (file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Failed to write manifest.json to {$path}");
        }
    }
}
