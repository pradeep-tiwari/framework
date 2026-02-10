<?php

namespace Lightpack\Pwa;

use Lightpack\Pwa\ManifestGenerator;
use Lightpack\Pwa\ServiceWorkerGenerator;
use Lightpack\Pwa\IconGenerator;

/**
 * PWA - Progressive Web App support for Lightpack
 * 
 * Provides a simple API for making Lightpack applications installable
 * as Progressive Web Apps with offline support, push notifications,
 * and native-like experience.
 */
class Pwa
{
    protected string $publicPath;
    protected string $baseUrl;
    protected ManifestGenerator $manifestGenerator;
    protected ServiceWorkerGenerator $serviceWorkerGenerator;
    protected IconGenerator $iconGenerator;
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->getDefaultConfig();
        $this->publicPath = $this->config['public_path'] ?? DIR_ROOT . '/public';
        $this->baseUrl = $this->config['base_url'] ?? (get_env('APP_URL') ?? '');
        
        $this->manifestGenerator = new ManifestGenerator($this->publicPath, $this->baseUrl);
        $this->serviceWorkerGenerator = new ServiceWorkerGenerator($this->publicPath);
        $this->iconGenerator = new IconGenerator($this->publicPath);
    }

    /**
     * Generate web app manifest
     */
    public function manifest(array $options = []): self
    {
        $defaults = [
            'name' => $this->config['name'] ?? get_env('APP_NAME', 'My App'),
            'short_name' => $this->config['short_name'] ?? get_env('APP_NAME', 'App'),
            'description' => $this->config['description'] ?? '',
            'theme_color' => $this->config['theme_color'] ?? '#4F46E5',
            'background_color' => $this->config['background_color'] ?? '#ffffff',
            'display' => $this->config['display'] ?? 'standalone',
            'start_url' => $this->config['start_url'] ?? '/',
            'scope' => $this->config['scope'] ?? '/',
            'orientation' => $this->config['orientation'] ?? 'any',
            'icons' => $this->config['icons'] ?? [],
        ];

        $manifest = array_merge($defaults, $options);
        $this->manifestGenerator->generate($manifest);

        return $this;
    }

    /**
     * Generate service worker
     */
    public function serviceWorker(array $options = []): self
    {
        $defaults = [
            'cache_name' => $this->config['cache_name'] ?? 'app-v1',
            'precache' => $this->config['precache'] ?? [],
            'runtime_cache' => $this->config['runtime_cache'] ?? [],
            'offline_page' => $this->config['offline_page'] ?? '/offline.html',
            'strategies' => $this->config['strategies'] ?? [],
        ];

        $config = array_merge($defaults, $options);
        $this->serviceWorkerGenerator->generate($config);

        return $this;
    }

    /**
     * Generate icons from source image
     */
    public function generateIcons(string $sourcePath, ?array $sizes = null): array
    {
        $sizes = $sizes ?? [72, 96, 128, 144, 152, 192, 384, 512];
        return $this->iconGenerator->generate($sourcePath, $sizes);
    }

    /**
     * Generate offline page
     */
    public function offlinePage(array $options = []): self
    {
        $defaults = [
            'title' => 'You are offline',
            'message' => 'Please check your internet connection and try again.',
            'retry_button' => true,
        ];

        $config = array_merge($defaults, $options);
        $this->generateOfflineHtml($config);

        return $this;
    }

    /**
     * Get HTML meta tags for PWA
     */
    public function meta(): string
    {
        $html = '';
        
        // Manifest link
        $html .= '<link rel="manifest" href="/manifest.json">' . "\n";
        
        // Theme color
        $themeColor = $this->config['theme_color'] ?? '#4F46E5';
        $html .= '<meta name="theme-color" content="' . $themeColor . '">' . "\n";
        
        // Apple touch icon
        if (file_exists($this->publicPath . '/icons/icon-192x192.png')) {
            $html .= '<link rel="apple-touch-icon" href="/icons/icon-192x192.png">' . "\n";
        }
        
        // Apple mobile web app capable
        $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        
        // Mobile viewport
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";

        return $html;
    }

    /**
     * Get service worker registration script
     */
    public function register(): string
    {
        return <<<HTML
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered:', registration);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
HTML;
    }

    /**
     * Generate complete PWA setup
     */
    public function init(array $options = []): self
    {
        // Generate manifest
        $this->manifest($options['manifest'] ?? []);
        
        // Generate service worker
        $this->serviceWorker($options['service_worker'] ?? []);
        
        // Generate icons if source provided
        if (isset($options['icon_source'])) {
            $this->generateIcons($options['icon_source']);
        }
        
        // Generate offline page
        $this->offlinePage($options['offline_page'] ?? []);

        return $this;
    }

    /**
     * Generate offline HTML page
     */
    protected function generateOfflineHtml(array $config): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$config['title']}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        button {
            background: white;
            color: #667eea;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: scale(1.05);
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📡</div>
        <h1>{$config['title']}</h1>
        <p>{$config['message']}</p>
HTML;

        if ($config['retry_button']) {
            $html .= <<<HTML
        <button onclick="window.location.reload()">Try Again</button>
HTML;
        }

        $html .= <<<HTML
    </div>
</body>
</html>
HTML;

        file_put_contents($this->publicPath . '/offline.html', $html);
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'public_path' => DIR_ROOT . '/public',
            'base_url' => get_env('APP_URL', ''),
            'name' => get_env('APP_NAME', 'My App'),
            'short_name' => get_env('APP_NAME', 'App'),
            'theme_color' => '#4F46E5',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'cache_name' => 'app-v1',
            'precache' => [],
            'runtime_cache' => [],
        ];
    }
}
