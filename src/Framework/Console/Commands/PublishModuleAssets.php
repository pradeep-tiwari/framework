<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\File\File;

class PublishModuleAssets implements ICommand
{
    public function run(array $arguments = [])
    {
        $moduleName = $arguments[0] ?? null;
        
        if (!$moduleName) {
            fputs(STDERR, "Please provide a module name.\n");
            fputs(STDERR, "Usage: php console module:publish-assets Blog\n\n");
            return;
        }
        
        $modulePath = DIR_ROOT . '/modules/' . $moduleName;
        
        if (!is_dir($modulePath)) {
            fputs(STDERR, "Module not found: {$moduleName}\n\n");
            return;
        }
        
        $assetsPath = $modulePath . '/Assets';
        
        if (!is_dir($assetsPath)) {
            fputs(STDOUT, "No assets directory found in {$moduleName} module.\n\n");
            return;
        }
        
        $publicPath = DIR_ROOT . '/public/modules/' . strtolower($moduleName);
        
        // Create public modules directory if it doesn't exist
        if (!is_dir(DIR_ROOT . '/public/modules')) {
            mkdir(DIR_ROOT . '/public/modules', 0755, true);
        }
        
        // Remove existing published assets
        if (is_dir($publicPath)) {
            $this->removeDirectory($publicPath);
        }
        
        // Copy assets to public directory
        $this->copyDirectory($assetsPath, $publicPath);
        
        fputs(STDOUT, "✓ Assets published for {$moduleName} module\n");
        fputs(STDOUT, "  From: modules/{$moduleName}/Assets\n");
        fputs(STDOUT, "  To:   public/modules/" . strtolower($moduleName) . "\n\n");
    }
    
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $items = scandir($source);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $sourcePath = $source . '/' . $item;
            $destPath = $destination . '/' . $item;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }
    
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $itemPath = $path . '/' . $item;
            
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        
        rmdir($path);
    }
}
