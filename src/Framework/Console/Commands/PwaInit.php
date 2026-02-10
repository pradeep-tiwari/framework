<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Args;
use Lightpack\Pwa\Pwa;
use Lightpack\Pwa\WebPush\VapidHelper;

/**
 * PwaInit - Initialize PWA setup
 * 
 * Command: php console pwa:init
 */
class PwaInit implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $args = new Args($arguments);
        
        fputs(STDOUT, "Initializing PWA setup...\n\n");

        try {
            // Get configuration from arguments or use defaults
            $appName = $args->get('name', get_env('APP_NAME', 'My App'));
            $shortName = $args->get('short-name', substr($appName, 0, 12));
            $description = $args->get('description', '');
            $themeColor = $args->get('theme-color', '#4F46E5');
            $iconSource = $args->get('icon', '');
            
            // Initialize PWA
            $pwa = app('pwa');
            
            $config = [
                'manifest' => [
                    'name' => $appName,
                    'short_name' => $shortName,
                    'description' => $description,
                    'theme_color' => $themeColor,
                ],
                'service_worker' => [
                    'precache' => [
                        '/css/app.css',
                        '/js/app.js',
                    ],
                    'runtime_cache' => [
                        '/api/*' => 'network-first',
                        '/img/*' => 'cache-first',
                        '/' => 'stale-while-revalidate',
                    ],
                ],
            ];
            
            if ($iconSource && file_exists($iconSource)) {
                $config['icon_source'] = $iconSource;
            }
            
            $pwa->init($config);
            
            fputs(STDOUT, "✓ Generated manifest.json\n");
            fputs(STDOUT, "✓ Created service worker (sw.js)\n");
            
            if ($iconSource && file_exists($iconSource)) {
                fputs(STDOUT, "✓ Generated PWA icons\n");
            }
            
            fputs(STDOUT, "✓ Created offline.html\n\n");
            
            // Generate VAPID keys if not exist
            if (!get_env('PWA_VAPID_PUBLIC_KEY')) {
                fputs(STDOUT, "Generating VAPID keys for push notifications...\n\n");
                $keys = VapidHelper::generateKeys();
                
                $envContent = VapidHelper::formatForEnv($keys);
                fputs(STDOUT, "Add these to your .env file:\n");
                fputs(STDOUT, $envContent . "\n\n");
            }
            
            // Show next steps
            fputs(STDOUT, "Next steps:\n");
            fputs(STDOUT, "1. Add VAPID keys to .env (if not already added)\n");
            fputs(STDOUT, "2. Run: php console create:config --support=pwa\n");
            fputs(STDOUT, "3. Run: php console create:migration --support=pwa\n");
            fputs(STDOUT, "4. Run: php console migrate:up\n");
            fputs(STDOUT, "5. Add to your layout:\n");
            fputs(STDOUT, "   <?= pwa()->meta() ?>\n");
            fputs(STDOUT, "   <?= pwa()->register() ?>\n\n");
            
            fputs(STDOUT, "✓ PWA initialization complete!\n\n");
            
        } catch (\Exception $e) {
            fputs(STDERR, "Error: Failed to initialize PWA: " . $e->getMessage() . "\n\n");
            return 1;
        }

        return 0;
    }
}
