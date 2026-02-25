<?php
/**
 * KQL (Kusto Query Language) query generator
 *
 * Generates KQL management commands and queries that map to the SQL operations
 * phpMyAdmin expects to perform.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use function implode;
use function str_replace;

/**
 * Generates Kusto Query Language statements for common database operations.
 *
 * Kusto management commands start with a dot (e.g. ".show databases").
 * KQL queries do not start with a dot.
 */
final class KqlGenerator
{
    // =========================================================================
    // Database / Cluster operations
    // =========================================================================

    /** List all databases in the cluster */
    public static function showDatabases(): string
    {
        return '.show databases';
    }

    /** Show detailed database info */
    public static function showDatabase(string $database): string
    {
        return '.show database ' . self::quoteIdentifier($database) . ' details';
    }

    // =========================================================================
    // Table operations
    // =========================================================================

    /** List all tables in a database */
    public static function showTables(string $database = ''): string
    {
        if ($database !== '') {
            return '.show database ' . self::quoteIdentifier($database) . ' tables';
        }

        return '.show tables';
    }

    /** Show table schema/columns */
    public static function showTableSchema(string $table): string
    {
        return '.show table ' . self::quoteIdentifier($table) . ' schema as json';
    }

    /** Show table details (row count, extent size, etc.) */
    public static function showTableDetails(string $table): string
    {
        return '.show table ' . self::quoteIdentifier($table) . ' details';
    }

    /** Get column info for a table */
    public static function getColumns(string $table): string
    {
        return '.show table ' . self::quoteIdentifier($table) . ' schema as cslschema';
    }

    /** Count rows in a table */
    public static function countRows(string $table): string
    {
        return self::quoteIdentifier($table) . ' | count';
    }

    /** Take N rows from a table (equivalent to SELECT * LIMIT N) */
    public static function selectAll(string $table, int $limit = 1000, int $offset = 0): string
    {
        $query = self::quoteIdentifier($table);

        if ($offset > 0) {
            // Kusto doesn't have native OFFSET, but we can serialize_rows
            // For proper paging, use a cursor or serialize_rows; here we approximate:
            $query .= ' | serialize _rowNumber = row_number()';
            $query .= ' | where _rowNumber > ' . $offset;
            $query .= ' | project-away _rowNumber';
        }

        $query .= ' | take ' . $limit;

        return $query;
    }

    /** Search/filter table rows */
    public static function searchTable(string $table, string $searchTerm, int $limit = 1000): string
    {
        $escaped = str_replace("'", "''", $searchTerm);

        return self::quoteIdentifier($table)
            . " | where * has '" . $escaped . "'"
            . ' | take ' . $limit;
    }

    // =========================================================================
    // Management operations
    // =========================================================================

    /** Drop a table */
    public static function dropTable(string $table): string
    {
        return '.drop table ' . self::quoteIdentifier($table) . ' ifexists';
    }

    /**
     * Create a table with columns.
     *
     * @param string $table Table name
     * @param array<string, string> $columns Column name => Kusto type
     */
    public static function createTable(string $table, array $columns): string
    {
        $colDefs = [];
        foreach ($columns as $name => $type) {
            $colDefs[] = self::quoteIdentifier($name) . ':' . $type;
        }

        return '.create table ' . self::quoteIdentifier($table)
            . ' (' . implode(', ', $colDefs) . ')';
    }

    /** Rename a table */
    public static function renameTable(string $oldName, string $newName): string
    {
        return '.rename table ' . self::quoteIdentifier($oldName)
            . ' to ' . self::quoteIdentifier($newName);
    }

    /** Add a column to an existing table */
    public static function addColumn(string $table, string $column, string $type): string
    {
        return '.alter-merge table ' . self::quoteIdentifier($table)
            . ' (' . self::quoteIdentifier($column) . ':' . $type . ')';
    }

    /** Drop a column from a table */
    public static function dropColumn(string $table, string $column): string
    {
        return '.alter table ' . self::quoteIdentifier($table)
            . ' drop column ' . self::quoteIdentifier($column);
    }

    // =========================================================================
    // Function operations (Kusto stored functions)
    // =========================================================================

    /** List all functions */
    public static function showFunctions(): string
    {
        return '.show functions';
    }

    /** Show a specific function */
    public static function showFunction(string $name): string
    {
        return '.show function ' . self::quoteIdentifier($name);
    }

    /** Drop a function */
    public static function dropFunction(string $name): string
    {
        return '.drop function ' . self::quoteIdentifier($name) . ' ifexists';
    }

    // =========================================================================
    // Ingestion (data insertion)
    // =========================================================================

    /**
     * Inline ingestion of data into a table.
     *
     * @param string $table Table name
     * @param list<list<string>> $rows Rows of data (each row as list of stringified values)
     */
    public static function ingestInline(string $table, array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $escapedValues = [];
            foreach ($row as $value) {
                // Inline ingestion uses CSV-like format
                $escapedValues[] = str_replace(["\n", "\r", ','], ['\\n', '\\r', '\\,'], $value);
            }

            $lines[] = implode(',', $escapedValues);
        }

        return '.ingest inline into table ' . self::quoteIdentifier($table)
            . ' <|' . "\n" . implode("\n", $lines);
    }

    // =========================================================================
    // Cluster operations
    // =========================================================================

    /** Show cluster information */
    public static function showCluster(): string
    {
        return '.show cluster';
    }

    /** Show cluster version info */
    public static function showVersion(): string
    {
        return '.show version';
    }

    /** Show running queries (similar to SHOW PROCESSLIST) */
    public static function showQueries(): string
    {
        return '.show queries';
    }

    /** Show cluster policies */
    public static function showPolicies(string $entity, string $entityName = ''): string
    {
        $cmd = '.show ' . $entity;
        if ($entityName !== '') {
            $cmd .= ' ' . self::quoteIdentifier($entityName);
        }

        return $cmd . ' policy *';
    }

    // =========================================================================
    // Utility
    // =========================================================================

    /**
     * Quote a Kusto identifier.
     *
     * Kusto uses bracket notation ['name'] for identifiers that contain
     * special characters. Simple identifiers don't need quoting.
     */
    public static function quoteIdentifier(string $identifier): string
    {
        // If identifier is already quoted, return as-is
        if (str_starts_with($identifier, "['") && str_ends_with($identifier, "']")) {
            return $identifier;
        }

        // Quote if it contains special characters
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            return $identifier;
        }

        return "['" . str_replace("'", "\\'", $identifier) . "']";
    }

    /**
     * Map a MySQL type name to the closest Kusto type.
     */
    public static function mysqlTypeToKusto(string $mysqlType): string
    {
        $mysqlType = strtolower(trim($mysqlType));

        return match (true) {
            in_array($mysqlType, ['tinyint', 'smallint', 'mediumint', 'int', 'integer'], true) => 'int',
            in_array($mysqlType, ['bigint'], true) => 'long',
            in_array($mysqlType, ['float', 'double', 'decimal', 'numeric', 'real'], true) => 'real',
            in_array($mysqlType, ['bool', 'boolean'], true) => 'bool',
            in_array($mysqlType, ['date', 'datetime', 'timestamp'], true) => 'datetime',
            in_array($mysqlType, ['time'], true) => 'timespan',
            in_array($mysqlType, ['json', 'blob', 'mediumblob', 'longblob'], true) => 'dynamic',
            in_array($mysqlType, ['binary', 'varbinary'], true) => 'dynamic',
            str_starts_with($mysqlType, 'varchar') => 'string',
            str_starts_with($mysqlType, 'char') => 'string',
            str_starts_with($mysqlType, 'text') => 'string',
            default => 'string',
        };
    }
}
