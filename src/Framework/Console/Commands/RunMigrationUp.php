<?php

namespace Lightpack\Console\Commands;

use Lightpack\Config\Env;
use Lightpack\Console\ICommand;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Migrations\Migrator;

class RunMigrationUp implements ICommand
{
    public function run(array $arguments = [])
    {
        if (!file_exists(DIR_ROOT . '/.env')) {
            fputs(STDOUT, "Running migrations require ./.env which is missing.\n\n");
            exit;
        }

        if ('mysql' !== Env::get('DB_DRIVER')) {
            fputs(STDOUT, "Migrations are supported only for MySQL/MariaDB.\n\n");
            exit;
        }

        $confirm = $this->promptConfirmation();

        if(false === $confirm) {
            fputs(STDOUT, "\n✓ Migration cancelled.\n");
            exit;
        }

        $migrator = new Migrator($this->getConnection());
        $paths = $this->getMigrationPaths();
        $allMigrations = [];
        
        fputs(STDOUT, "\n");

        foreach ($paths as $name => $path) {
            if (!is_dir($path)) {
                continue;
            }

            fputs(STDOUT, "Running migrations: {$name}\n");
            $migrations = $migrator->run($path);
            
            if (empty($migrations)) {
                fputs(STDOUT, "  ✓ Already up-to-date\n");
            } else {
                foreach ($migrations as $migration) {
                    fputs(STDOUT, "  ✓ {$migration}\n");
                    $allMigrations[] = $migration;
                }
            }
            
            fputs(STDOUT, "\n");
        }

        if (empty($allMigrations)) {
            fputs(STDOUT, "✓ All migrations already up-to-date.\n\n");
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

    private function promptConfirmation(): bool
    {
        if ('production' === strtolower(get_env('APP_ENV'))) {
            fputs(STDOUT, "\n[Production] Are you sure you want to migrate? [y/N]: ");
            return strtolower(trim(fgets(STDIN))) === 'y';
        } 

        return true;
    }

    private function getMigrationPaths(): array
    {
        $paths = [
            'app' => DIR_ROOT . '/database/migrations',
        ];
        
        // Auto-discover module migrations
        $modulesDir = DIR_ROOT . '/modules';
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
}
