<?php

namespace Lightpack\Pwa\Views\Config;

class PwaView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'pwa' => [
        // Manifest settings
        'name'             => get_env('APP_NAME', 'My App'),
        'short_name'       => get_env('APP_NAME', 'App'),
        'theme_color'      => '#4F46E5',
        'background_color' => '#ffffff',
        'display'          => 'standalone',
        'description'      => '',

        // Service worker
        'cache_name'    => 'app-v1',
        'sw_url'        => '/sw.js',
        'sw_scope'      => '/',
        'precache'      => ['/css/app.css', '/js/app.js'],
        'runtime_cache' => [
            '/api/*' => 'network-first',
            '/img/*' => 'cache-first',
        ],

        // Push notifications (VAPID)
        'vapid_subject'     => get_env('PWA_VAPID_SUBJECT'),
        'vapid_public_key'  => get_env('PWA_VAPID_PUBLIC_KEY'),
        'vapid_private_key' => get_env('PWA_VAPID_PRIVATE_KEY'),
    ],
];
PHP;
    }
}
