<?php

namespace Lightpack\Console\Views\Config;

class PwaView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'pwa' => [
        /**
         * PWA Configuration:
         * Configure Progressive Web App settings including manifest,
         * service worker, and push notifications.
         */

        /**
         * Public directory path
         */
        'public_path' => DIR_ROOT . '/public',

        /**
         * Base URL for assets
         */
        'base_url' => get_env('APP_URL', ''),

        /**
         * App name (shown on home screen)
         */
        'name' => get_env('APP_NAME', 'My App'),

        /**
         * Short name (shown under icon)
         */
        'short_name' => get_env('APP_NAME', 'App'),

        /**
         * App description
         */
        'description' => '',

        /**
         * Theme color (browser UI color)
         */
        'theme_color' => '#4F46E5',

        /**
         * Background color (splash screen)
         */
        'background_color' => '#ffffff',

        /**
         * Display mode: fullscreen, standalone, minimal-ui, browser
         */
        'display' => 'standalone',

        /**
         * Start URL (where app opens)
         */
        'start_url' => '/',

        /**
         * Scope (which URLs are part of PWA)
         */
        'scope' => '/',

        /**
         * Orientation: any, natural, landscape, portrait
         */
        'orientation' => 'any',

        /**
         * Icons (auto-discovered from public/icons if empty)
         */
        'icons' => [],

        /**
         * Service worker cache name
         */
        'cache_name' => 'app-v1',

        /**
         * Files to precache on install
         */
        'precache' => [
            '/css/app.css',
            '/js/app.js',
            '/offline.html',
        ],

        /**
         * Runtime caching strategies
         * 
         * Strategies: cache-first, network-first, stale-while-revalidate,
         *            network-only, cache-only
         */
        'runtime_cache' => [
            '/api/*' => 'network-first',
            '/img/*' => 'cache-first',
            '/' => 'stale-while-revalidate',
        ],

        /**
         * Offline fallback page
         */
        'offline_page' => '/offline.html',

        /**
         * VAPID subject (mailto: or https: URL)
         */
        'vapid_subject' => get_env('PWA_VAPID_SUBJECT', 'mailto:admin@example.com'),

        /**
         * VAPID public key (generate with: php console pwa:generate-vapid)
         */
        'vapid_public_key' => get_env('PWA_VAPID_PUBLIC_KEY', ''),

        /**
         * VAPID private key (keep secret!)
         */
        'vapid_private_key' => get_env('PWA_VAPID_PRIVATE_KEY', ''),
    ],
];
PHP;
    }
}
