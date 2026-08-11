<?php

namespace Lightpack\Testing;

use Lightpack\Database\DB;
use Lightpack\Database\Migrations\Migrator;

trait DatabaseTrait
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $db = self::createConnection();

        $migrator = new Migrator($db);
        $migrator->run(getcwd() . '/database/migrations');
    }

    public static function tearDownAfterClass(): void
    {
        try {
            $db = self::createConnection();

            $migrator = new Migrator($db);
            $migrator->rollbackAll(getcwd() . '/database/migrations');
        } catch (\Throwable $e) {
            // Ensure parent cleanup runs even if migration rollback fails
        } finally {
            parent::tearDownAfterClass();
        }
    }

    protected function beginTransaction()
    {
        db()->begin();
    }

    protected function rollbackTransaction()
    {
        db()->rollback();
    }

    /**
     * Assert that a database table contains a row matching the given conditions.
     *
     * @param string $table The table name.
     * @param array $conditions Column-value pairs to match.
     * @return self
     */
    public function assertDatabaseHas(string $table, array $conditions): self
    {
        $query = db()->table($table);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        $record = $query->one();

        $this->assertNotNull(
            $record,
            "Failed asserting that table '{$table}' has a row matching: " . json_encode($conditions)
        );

        return $this;
    }

    /**
     * Assert that a database table contains no row matching the given conditions.
     *
     * @param string $table The table name.
     * @param array $conditions Column-value pairs to match.
     * @return self
     */
    public function assertDatabaseMissing(string $table, array $conditions): self
    {
        $query = db()->table($table);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        $record = $query->one();

        $this->assertNull(
            $record,
            "Failed asserting that table '{$table}' has no row matching: " . json_encode($conditions)
        );

        return $this;
    }

    /**
     * Assert that a database table has the given number of rows.
     *
     * @param string $table The table name.
     * @param int $count Expected row count.
     * @return self
     */
    public function assertDatabaseCount(string $table, int $count): self
    {
        $actual = db()->table($table)->count();

        $this->assertEquals(
            $count,
            $actual,
            "Failed asserting that table '{$table}' has {$count} row(s). Found {$actual}."
        );

        return $this;
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
