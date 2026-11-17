<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Throwable;

class DbSchema
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    public static function hasColumn(string $connection, string $table, string $column): bool
    {
        $key = strtolower($connection . '.' . $table . '.' . $column);
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            self::$columnCache[$key] = Schema::connection($connection)->hasColumn($table, $column);
        } catch (Throwable $e) {
            self::$columnCache[$key] = false;
        }

        return self::$columnCache[$key];
    }

    public static function scctcustHasNoWa(): bool
    {
        return self::hasColumn('DATA_MYSQL', 'scctcust', 'NO_WA');
    }
}
