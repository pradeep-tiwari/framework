<?php

namespace Lightpack\Database\Migrations;

/**
 * Resolves migration paths and files for both app and modules.
 * Shared service used by migration commands and tests.
 */
class MigrationPathResolver
{
    private string $rootPath;

    public function __construct(?string $rootPath = null)
    {
        $this->rootPath = $rootPath ?? getcwd();
    }

    /**
     * Get all migration paths (app + modules).
     * Returns associative array with names as keys for display purposes.
     * 
     * @return array<string, string> ['app' => '/path/to/migrations', 'modulename' => '/path/to/module/migrations']
     */
    public function getPathsWithNames(): array
    {
        $paths = [
            'app' => $this->rootPath . '/database/migrations',
        ];
        
        // Auto-discover module migrations
        $modulesDir = $this->rootPath . '/modules';
        if (is_dir($modulesDir)) {
            $modules = glob($modulesDir . '/*', GLOB_ONLYDIR);
            
            foreach ($modules as $moduleDir) {
                $moduleName = basename($moduleDir);
                $migrationPath = $moduleDir . '/Database/Migrations';
                
                if (is_dir($migrationPath)) {
                    $paths[strtolower($moduleName)] = $migrationPath;
                }
            }
        }
        
        return $paths;
    }

    /**
     * Get all migration paths (app + modules).
     * Returns indexed array of paths.
     * 
     * @return array<int, string> ['/path/to/migrations', '/path/to/module/migrations']
     */
    public function getPaths(): array
    {
        $paths = [$this->rootPath . '/database/migrations'];
        
        // Auto-discover module migrations
        $modulesDir = $this->rootPath . '/modules';
        if (is_dir($modulesDir)) {
            $modules = glob($modulesDir . '/*', GLOB_ONLYDIR);
            
            foreach ($modules as $moduleDir) {
                $migrationPath = $moduleDir . '/Database/Migrations';
                
                if (is_dir($migrationPath)) {
                    $paths[] = $migrationPath;
                }
            }
        }
        
        return $paths;
    }

    /**
     * Collect all migration files from all paths (app + modules).
     * Returns array indexed by filename for easy lookup.
     * 
     * @return array<string, \SplFileInfo> ['001_create_users.php' => SplFileInfo, ...]
     */
    public function getAllMigrationFiles(): array
    {
        $paths = $this->getPaths();
        $allFiles = [];
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.php');
                
                foreach ($files as $file) {
                    $filename = basename($file);
                    $allFiles[$filename] = new \SplFileInfo($file);
                }
            }
        }
        
        return $allFiles;
    }
}
