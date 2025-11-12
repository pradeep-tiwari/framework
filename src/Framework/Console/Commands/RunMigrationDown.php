<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\ICommand;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationDown implements ICommand
{
    public function run(array $arguments = [])
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            fputs(STDOUT, "Running migrations require ./.env which is missing.\n\n");
            exit;
        }

        $driver = Env::get('DB_DRIVER');

        if ('mysql' !== $driver) {
            fputs(STDOUT, "Migrations are supported only for MySQL/MariaDB.\n\n");
            exit;
        }

        $migrator = new Migrator($this->getConnection());
        $steps = $this->getStepsArgument($arguments);
        $confirm = $this->promptConfirmation($steps);

        if ($confirm) {
            // Collect all migration files from all paths (app + modules)
            $allMigrationFiles = $this->getAllMigrationFiles();
            
            if('all' === $steps) {
                $migrations = $migrator->rollbackAll($allMigrationFiles);
            } else {
                $migrations = $migrator->rollback($allMigrationFiles, $steps);
            }

            fputs(STDOUT, "\n");

            if (empty($migrations)) {
                fputs(STDOUT, "✓ No migrations to rollback.\n");
            } else {
                fputs(STDOUT, "Rolled back migrations:\n");
                foreach ($migrations as $migration) {
                    fputs(STDOUT, "✓ {$migration}\n");
                }
                fputs(STDOUT, "\n");
            }
        }
    }

    private function getConnection()
    {
        switch (Env::get('DB_DRIVER')) {
            case 'mysql':
                return new Mysql([
                    'host'      => Env::get('DB_HOST'),
                    'port'      => Env::get('DB_PORT'),
                    'username'  => Env::get('DB_USER'),
                    'password'  => Env::get('DB_PSWD'),
                    'database'  => Env::get('DB_NAME'),
                    'options'   => [],
                ]);
            default:
                fputs(STDOUT, "Invalid database driver found in ./.env\n\n");
                exit;
        }
    }

    private function getStepsArgument(array $arguments)
    {
        $steps = $arguments[0] ?? null;

        if (null === $steps) {
            return null;
        }

        if ('--all' === $steps) {
            return 'all';
        }

        $steps = explode('=', $steps);

        $steps = $steps[1] ?? null;

        return $steps;
    }

    private function promptConfirmation(null|string|int $steps = null): bool
    {
        fputs(STDOUT, "\n");

        if ('all' === $steps) {
            fputs(STDOUT, "Are you sure you want to rollback all the migrations? [y/N]: ");
        } else if (null === $steps || 1 === $steps) {
            fputs(STDOUT, "Are you sure you want to rollback last batch of migrations? [y/N]: ");
        } else {
            fputs(STDOUT, "Are you sure you want to rollback last {$steps} batch of migrations? [y/N]: ");
        }

        return strtolower(trim(fgets(STDIN))) === 'y';
    }

    private function getAllMigrationFiles(): array
    {
        $paths = $this->getMigrationPaths();
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

    private function getMigrationPaths(): array
    {
        $paths = [DIR_ROOT . '/database/migrations'];
        
        // Auto-discover module migrations
        $modulesDir = DIR_ROOT . '/modules';
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
}
