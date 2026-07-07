<?php

namespace Lightpack\Meter\Drivers;

use Lightpack\Database\DB;
use Lightpack\Meter\MeterDriverInterface;

class DatabaseDriver implements MeterDriverInterface
{
    public function __construct(
        protected DB $db,
        protected string $table = 'meters',
    ) {
    }

    public function increment(string $key, int $amount = 1): void
    {
        $table = $this->db->quoteIdentifier($this->table);

        $sql = "INSERT INTO {$table} (`key`, `value`) VALUES (:key, :amount)
                ON DUPLICATE KEY UPDATE `value` = `value` + VALUES(`value`)";

        $this->db->query($sql, [
            'key' => $key,
            'amount' => $amount,
        ]);
    }

    public function value(string $key): int
    {
        $table = $this->db->quoteIdentifier($this->table);
        $stmt = $this->db->query(
            "SELECT `value` FROM {$table} WHERE `key` = :key",
            ['key' => $key]
        );

        $row = $stmt->fetch();

        return $row ? (int) $row['value'] : 0;
    }

    public function reset(string $key): void
    {
        $table = $this->db->quoteIdentifier($this->table);

        $this->db->query(
            "DELETE FROM {$table} WHERE `key` = :key",
            ['key' => $key]
        );
    }
}
