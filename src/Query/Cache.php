<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

/**
 * Handles caching results
 */
class Cache
{
    /** @var (string|int|null)[][][] Table data cache */
    private array $tableCache = [];

    /**
     * Caches table data so Table does not require to issue
     * SHOW TABLE STATUS again
     *
     * @param (string|int|null)[][] $tables information for tables of some databases
     */
    public function cacheTableData(string $database, array $tables): void
    {
        // Note: This function must not use array_merge because numerical indices must be preserved.
        // When an entry already exists for the database in cache, we merge the incoming data with existing data.
        // The union operator appends elements from right to left unless they exists on the left already.
        // Doing the union with incoming data on the left ensures that when we reread table status from DB,
        // we overwrite whatever was in cache with the new data.

        if (isset($this->tableCache[$database])) {
            $this->tableCache[$database] = $tables + $this->tableCache[$database];
        } else {
            $this->tableCache[$database] = $tables;
        }
    }

    /**
     * Set an item in the cache
     */
    public function cacheTableValue(string $db, string $table, string $key, string|int|null $value): void
    {
        $this->tableCache[$db][$table][$key] = $value;
    }

    /**
     * Get a cached value from table cache.
     *
     * @param T $key
     *
     * @return (T is null ? (string|int|null)[] : (string|int|null))|null
     *
     * @template T of string|null
     */
    public function getCachedTableContent(string $db, string $table, string|null $key = null): array|string|int|null
    {
        if ($key === null) {
            return $this->tableCache[$db][$table] ?? null;
        }

        return $this->tableCache[$db][$table][$key] ?? null;
    }

    public function clearTableCache(): void
    {
        $this->tableCache = [];
    }
}
