<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Args;
use Lightpack\Pwa\IconGenerator;

/**
 * GeneratePwaIcons - Generate PWA icons from source image
 * 
 * Command: php console pwa:generate-icons <source-path>
 * Or: php console pwa:generate-icons --source=<path>
 */
class GeneratePwaIcons implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $args = new Args($arguments);
        
        // Get source path from first positional argument or --source option
        $sourcePath = $args->first() ?? $args->get('source');
        
        if (!$sourcePath) {
            fputs(STDERR, "Error: Source image path is required\n");
            fputs(STDERR, "Usage: php console pwa:generate-icons <source-path>\n");
            fputs(STDERR, "   Or: php console pwa:generate-icons --source=<path>\n\n");
            return 1;
        }

        if (!file_exists($sourcePath)) {
            fputs(STDERR, "Error: Source image not found: {$sourcePath}\n\n");
            return 1;
        }

        fputs(STDOUT, "Generating PWA icons...\n\n");

        try {
            $generator = new IconGenerator(DIR_ROOT . '/public');
            
            // Generate standard icons
            $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
            $icons = $generator->generate($sourcePath, $sizes);
            
            foreach ($sizes as $size) {
                fputs(STDOUT, "✓ Generated icon-{$size}x{$size}.png\n");
            }
            
            // Generate maskable icon
            $generator->generateMaskable($sourcePath);
            fputs(STDOUT, "✓ Generated maskable icon (512x512)\n");
            
            // Generate favicon
            $generator->generateFavicon($sourcePath);
            fputs(STDOUT, "✓ Generated favicon.ico\n\n");
            
            fputs(STDOUT, "✓ All icons generated successfully!\n\n");
            
        } catch (\Exception $e) {
            fputs(STDERR, "Error: Failed to generate icons: " . $e->getMessage() . "\n\n");
            return 1;
        }

        return 0;
    }
}
