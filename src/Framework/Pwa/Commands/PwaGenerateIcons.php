<?php

namespace Lightpack\Pwa\Commands;

use Lightpack\Console\Command;
use Lightpack\Pwa\IconGenerator;

class PwaGenerateIcons extends Command
{
    public function run()
    {
        $source = $this->args->argument(0) ?? $this->args->get('source');

        $this->output->newline();

        if (! $source) {
            $this->output->error('Please provide a source image path.');
            $this->output->newline();
            $this->output->line('Usage:');
            $this->output->line('  php console pwa:generate-icons <path-to-image>');
            $this->output->line('  php console pwa:generate-icons --source=<path-to-image>');
            $this->output->newline();

            return self::FAILURE;
        }

        if (! file_exists($source)) {
            $this->output->error("Image not found: {$source}");
            $this->output->newline();

            return self::FAILURE;
        }

        try {
            $publicPath = defined('DIR_ROOT') ? DIR_ROOT . '/public' : './public';
            $generator = new IconGenerator($publicPath);

            $sizes = [152, 180, 192, 512];
            $icons = $generator->generate($source, $sizes);

            $this->output->success('✓ Icons generated:');
            foreach ($icons as $icon) {
                $this->output->line('  public/icons/' . basename($icon['src']));
            }

            $generator->generateMaskable($source);
            $this->output->success('✓ Maskable icon: public/icons/icon-512x512-maskable.png');

            $generator->generateFavicon($source);
            $this->output->success('✓ Favicon: public/favicon.png');

            $this->output->newline();
        } catch (\Exception $e) {
            $this->output->error('Icon generation failed: ' . $e->getMessage());
            $this->output->newline();

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
