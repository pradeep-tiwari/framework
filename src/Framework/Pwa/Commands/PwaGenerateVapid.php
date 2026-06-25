<?php

namespace Lightpack\Pwa\Commands;

use Lightpack\Console\Command;
use Lightpack\Pwa\WebPush\VapidHelper;

class PwaGenerateVapid extends Command
{
    public function run()
    {
        $this->output->newline();

        try {
            $keys = VapidHelper::generateKeys();

            $this->output->success('✓ VAPID keys generated successfully');
            $this->output->newline();
            $this->output->line('Add these to your .env file:');
            $this->output->newline();
            $this->output->line('PWA_VAPID_SUBJECT=mailto:admin@example.com');
            $this->output->line('PWA_VAPID_PUBLIC_KEY=' . $keys['public_key']);
            $this->output->line('PWA_VAPID_PRIVATE_KEY="' . $keys['private_key'] . '"');
            $this->output->newline();
        } catch (\Exception $e) {
            $this->output->error('Failed to generate VAPID keys: ' . $e->getMessage());
            $this->output->newline();

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
