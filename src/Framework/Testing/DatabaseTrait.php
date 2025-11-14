<?php

namespace Lightpack\Testing;

use Lightpack\Database\DB;
use Lightpack\Database\Migrations\Migrator;
use Lightpack\Database\Migrations\MigrationPathResolver;

trait DatabaseTrait
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $db = self::createConnection();
        $migrator = new Migrator($db);
        $resolver = new MigrationPathResolver();
        
        // Run migrations for app and all modules
        $paths = $resolver->getPaths();
        
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $migrator->run($path);
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        $db = self::createConnection();
        $migrator = new Migrator($db);
        $resolver = new MigrationPathResolver();
        
        // Rollback all migrations from app and modules
        $allMigrationFiles = $resolver->getAllMigrationFiles();
        $migrator->rollbackAll($allMigrationFiles);

        parent::tearDownAfterClass();
    }

    protected function beginTransaction()
    {
        db()->begin();
    }

    protected function rollbackTransaction()
    {
        db()->rollback();
    }

    private static function createConnection(): DB
    {
        return new DB(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                get_env('DB_HOST'),
                get_env('DB_PORT'),
                get_env('DB_NAME')
            ),
            get_env('DB_USER'),
            get_env('DB_PSWD')
        );
    }
}
