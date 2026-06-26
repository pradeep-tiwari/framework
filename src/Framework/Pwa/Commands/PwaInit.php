<?php

namespace Lightpack\Pwa\Commands;

use Lightpack\Console\Command;
use Lightpack\Pwa\Pwa;
use Lightpack\Pwa\WebPush\VapidHelper;

class PwaInit extends Command
{
    public function run()
    {
        $name = $this->args->get('name', get_env('APP_NAME', 'My App'));
        $shortName = $this->args->get('short-name', $name);
        $themeColor = $this->args->get('theme-color', '#4F46E5');
        $iconSource = $this->args->get('icon');
        $description = $this->args->get('description', '');

        $this->output->newline();
        $this->output->info('Initializing PWA...');
        $this->output->newline();

        try {
            $pwa = new Pwa([
                'name' => $name,
                'short_name' => $shortName,
                'theme_color' => $themeColor,
                'description' => $description,
            ]);

            $options = [];

            if ($iconSource) {
                $options['icon_source'] = $iconSource;
            }

            $pwa->init($options);

            $this->output->success('✓ public/manifest.json');
            $this->output->success('✓ public/sw.js');
            $this->output->success('✓ public/offline.html');

            if ($iconSource) {
                $this->output->success('✓ public/icons/ (all sizes)');
                $this->output->success('✓ public/favicon.png');
            }

            $this->output->newline();
            $this->output->info('Generating VAPID keys...');
            $this->output->newline();

            $keys = VapidHelper::generateKeys();

            $this->output->line('Add these to your .env file:');
            $this->output->newline();
            $this->output->line('PWA_VAPID_SUBJECT=mailto:admin@example.com');
            $this->output->line('PWA_VAPID_PUBLIC_KEY=' . $keys['public_key']);
            $this->output->line('PWA_VAPID_PRIVATE_KEY="' . $keys['private_key'] . '"');

            $this->output->newline();
            $this->publishRoutes();
            $this->output->newline();
            $this->output->info('Next steps:');
            $this->output->line('  1. Add the VAPID keys above to .env');
            $this->output->line('  2. Run: php console create:config --support=pwa');
            $this->output->line('  3. Run: php console create:migration --support=pwa');
            $this->output->line('  4. Run: php console migrate:up');
            $this->output->line("  5. In your main routes file add: require __DIR__ . '/pwa.php';");
            $this->output->line('  6. Add <?= pwa()->meta() ?> to your layout <head>');
            $this->output->line('  7. Add <?= pwa()->register() ?> before </body>');
            $this->output->newline();
        } catch (\Exception $e) {
            $this->output->error('PWA init failed: ' . $e->getMessage());
            $this->output->newline();

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function publishRoutes(): void
    {
        $source = dirname(__DIR__) . '/routes/pwa.php';
        $projectRoutes = (defined('DIR_ROOT') ? DIR_ROOT : '.') . '/routes';
        $target = $projectRoutes . '/pwa.php';

        if (! is_dir($projectRoutes)) {
            return;
        }

        if (file_exists($target)) {
            $this->output->success('✓ routes/pwa.php (already exists, skipped)');

            return;
        }

        if (@copy($source, $target)) {
            $this->output->success('✓ routes/pwa.php');
        }
    }
}
