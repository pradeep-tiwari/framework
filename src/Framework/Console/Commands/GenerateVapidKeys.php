<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Pwa\WebPush\VapidHelper;

/**
 * GenerateVapidKeys - Generate VAPID keys for Web Push
 * 
 * Command: php console pwa:generate-vapid
 */
class GenerateVapidKeys implements CommandInterface
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "Generating VAPID keys...\n\n");

        try {
            $keys = VapidHelper::generateKeys();
            
            fputs(STDOUT, "✓ VAPID keys generated successfully!\n\n");
            
            $envContent = VapidHelper::formatForEnv($keys);
            
            fputs(STDOUT, "Add these to your .env file:\n\n");
            fputs(STDOUT, $envContent . "\n\n");
            fputs(STDOUT, "Note: Keep your private key secret!\n\n");
            
        } catch (\Exception $e) {
            fputs(STDERR, "Error: Failed to generate VAPID keys: " . $e->getMessage() . "\n\n");
            return 1;
        }

        return 0;
    }
}
