<?php
/**
 * SQL to KQL translator
 *
 * Intercepts common SQL patterns emitted by phpMyAdmin's core and
 * translates them into equivalent Kusto Query Language.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use function array_map;
use function count;
use function implode;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function str_ireplace;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * Translates SQL statements commonly issued by phpMyAdmin into their
 * Kusto Query Language (KQL) or management-command equivalents.
 *
 * This is a best-effort translator — not a full SQL parser. It covers the
 * read-heavy operations that the phpMyAdmin UI triggers, such as:
 *  - SHOW DATABASES / SHOW TABLES
 *  - SELECT * FROM table
 *  - INFORMATION_SCHEMA queries
 *  - SHOW CREATE TABLE
 *  - SHOW VARIABLES / SHOW STATUS
 */
final class SqlToKqlTranslator
{
    /**
     * Attempt to translate a SQL query to KQL.
     *
     * Returns the translated KQL string, or null if the query cannot
     * be translated (caller should return an empty result or error).
     */
    public static function translate(string $sql): string|null
    {
        $sql = trim($sql);

        // Remove trailing semicolons
        $sql = rtrim($sql, ';');

        $upper = strtoupper(trim($sql));

        // =====================================================================
        // SHOW commands
        // =====================================================================

        if ($upper === 'SHOW DATABASES' || $upper === 'SHOW SCHEMAS') {
            return KqlGenerator::showDatabases();
        }

        if (preg_match('/^SHOW\s+TABLES(\s+FROM\s+`?(\w+)`?)?/i', $sql, $m)) {
            return KqlGenerator::showTables($m[2] ?? '');
        }

        if (preg_match('/^SHOW\s+FULL\s+TABLES(\s+FROM\s+`?(\w+)`?)?/i', $sql, $m)) {
            return KqlGenerator::showTables($m[2] ?? '');
        }

        if (preg_match('/^SHOW\s+CREATE\s+TABLE\s+`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::showTableSchema($m[1]);
        }

        if (preg_match('/^SHOW\s+COLUMNS\s+FROM\s+`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::getColumns($m[1]);
        }

        if (preg_match('/^SHOW\s+FULL\s+COLUMNS\s+FROM\s+`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::getColumns($m[1]);
        }

        if (preg_match('/^DESCRIBE\s+`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::getColumns($m[1]);
        }

        if (str_starts_with($upper, 'SHOW VARIABLES') || str_starts_with($upper, 'SHOW SESSION VARIABLES')) {
            // Return Kusto version info instead
            return KqlGenerator::showVersion();
        }

        if (str_starts_with($upper, 'SHOW GLOBAL STATUS') || str_starts_with($upper, 'SHOW STATUS')) {
            return KqlGenerator::showCluster();
        }

        if (str_starts_with($upper, 'SHOW PROCESSLIST') || str_starts_with($upper, 'SHOW FULL PROCESSLIST')) {
            return KqlGenerator::showQueries();
        }

        if (str_starts_with($upper, 'SHOW FUNCTION STATUS') || str_starts_with($upper, 'SHOW PROCEDURE STATUS')) {
            return KqlGenerator::showFunctions();
        }

        // =====================================================================
        // SELECT from INFORMATION_SCHEMA
        // =====================================================================

        if (preg_match('/INFORMATION_SCHEMA\s*\.\s*SCHEMATA/i', $sql)) {
            return KqlGenerator::showDatabases();
        }

        if (preg_match('/INFORMATION_SCHEMA\s*\.\s*TABLES/i', $sql)) {
            // Try to extract the database filter
            if (preg_match("/TABLE_SCHEMA\s*=\s*'([^']+)'/i", $sql, $m)) {
                return KqlGenerator::showTables($m[1]);
            }

            return KqlGenerator::showTables();
        }

        if (preg_match('/INFORMATION_SCHEMA\s*\.\s*COLUMNS/i', $sql)) {
            if (preg_match("/TABLE_NAME\s*=\s*'([^']+)'/i", $sql, $m)) {
                return KqlGenerator::getColumns($m[1]);
            }

            return null; // Can't determine which table
        }

        if (preg_match('/INFORMATION_SCHEMA\s*\.\s*ROUTINES/i', $sql)) {
            return KqlGenerator::showFunctions();
        }

        // =====================================================================
        // SELECT queries
        // =====================================================================

        if (preg_match('/^SELECT\s+COUNT\s*\(\s*\*\s*\)\s+FROM\s+`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::countRows($m[1]);
        }

        if (preg_match('/^SELECT\s+\*\s+FROM\s+`?(\w+)`?/i', $sql, $m)) {
            $table = $m[1];
            $limit = 1000;
            $offset = 0;

            if (preg_match('/LIMIT\s+(\d+)/i', $sql, $lm)) {
                $limit = (int) $lm[1];
            }

            if (preg_match('/OFFSET\s+(\d+)/i', $sql, $om)) {
                $offset = (int) $om[1];
            } elseif (preg_match('/LIMIT\s+(\d+)\s*,\s*(\d+)/i', $sql, $lmo)) {
                $offset = (int) $lmo[1];
                $limit = (int) $lmo[2];
            }

            $kql = KqlGenerator::selectAll($table, $limit, $offset);

            // Handle ORDER BY for SELECT * queries
            if (preg_match('/ORDER\s+BY\s+`?(\w+)`?\s*(ASC|DESC)?/i', $sql, $om)) {
                $dir = strtolower($om[2] ?? 'asc');
                // Insert sort before the final | take
                $kql = preg_replace(
                    '/\|\s*take\s+/',
                    '| sort by ' . KqlGenerator::quoteIdentifier($om[1]) . ' ' . $dir . ' | take ',
                    $kql,
                );
            }

            return $kql;
        }

        // Generic SELECT with specific columns
        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+`?(\w+)`?(\s+.*)?$/is', $sql, $m)) {
            $columns = trim($m[1]);
            $table = $m[2];
            $rest = trim($m[3] ?? '');

            $kql = KqlGenerator::quoteIdentifier($table);

            // Handle WHERE clause
            if (preg_match('/WHERE\s+(.+?)(\s+ORDER\s+BY|\s+LIMIT|\s+GROUP\s+BY|$)/is', $rest, $wm)) {
                $whereClause = self::translateWhereClause(trim($wm[1]));
                if ($whereClause !== '') {
                    $kql .= ' | where ' . $whereClause;
                }
            }

            // Handle ORDER BY
            if (preg_match('/ORDER\s+BY\s+`?(\w+)`?\s*(ASC|DESC)?/i', $rest, $om)) {
                $dir = strtolower($om[2] ?? 'asc');
                $kql .= ' | sort by ' . KqlGenerator::quoteIdentifier($om[1]) . ' ' . $dir;
            }

            // Handle LIMIT
            $limit = 1000;
            if (preg_match('/LIMIT\s+(\d+)/i', $rest, $lm)) {
                $limit = (int) $lm[1];
            }

            $kql .= ' | take ' . $limit;

            // Project specific columns (unless SELECT *)
            if ($columns !== '*') {
                $colList = self::parseColumnList($columns);
                if ($colList !== []) {
                    $kql .= ' | project ' . implode(', ', array_map(
                        static fn (string $c): string => KqlGenerator::quoteIdentifier(trim($c, '` ')),
                        $colList,
                    ));
                }
            }

            return $kql;
        }

        // =====================================================================
        // DDL commands
        // =====================================================================

        if (preg_match('/^DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?(\w+)`?/i', $sql, $m)) {
            return KqlGenerator::dropTable($m[1]);
        }

        // =====================================================================
        // Kusto passthrough — if the query already starts with '.', it's KQL
        // =====================================================================

        if (str_starts_with($sql, '.')) {
            return $sql;
        }

        // =====================================================================
        // Pass-through for queries that look like KQL (table name | operator)
        // =====================================================================

        if (preg_match('/^\w+\s*\|/i', $sql)) {
            return $sql;
        }

        // =====================================================================
        // MySQL-specific queries we can safely ignore / return empty for
        // =====================================================================

        if (
            str_starts_with($upper, 'SET ')
            || str_starts_with($upper, 'SHOW GRANTS')
            || str_starts_with($upper, 'SHOW ENGINES')
            || str_starts_with($upper, 'SHOW PLUGINS')
            || str_starts_with($upper, 'SHOW COLLATION')
            || str_starts_with($upper, 'SHOW CHARSET')
            || str_starts_with($upper, 'SHOW INDEX')
            || str_starts_with($upper, 'SHOW MASTER')
            || str_starts_with($upper, 'SHOW SLAVE')
            || str_starts_with($upper, 'SHOW REPLICA')
            || str_starts_with($upper, 'SHOW WARNINGS')
            || str_starts_with($upper, 'SHOW ERRORS')
            || str_starts_with($upper, 'FLUSH')
            || str_starts_with($upper, 'RESET')
            || $upper === 'SELECT 1'
        ) {
            return null; // Signal to return empty result
        }

        // Cannot translate — return null
        return null;
    }

    /**
     * Translate a SQL WHERE clause to KQL where operators.
     */
    private static function translateWhereClause(string $where): string
    {
        // Replace SQL operators with KQL equivalents
        $kql = $where;

        // Replace backtick-quoted identifiers
        $kql = preg_replace('/`(\w+)`/', '$1', $kql);

        // Replace SQL string comparison with KQL
        // = remains =
        // != remains !=
        // <> becomes !=
        $kql = str_ireplace('<>', '!=', $kql);

        // LIKE 'pattern' → has "pattern" or contains "pattern"
        $kql = preg_replace_callback(
            "/(\w+)\s+LIKE\s+'([^']+)'/i",
            static function (array $m): string {
                $field = $m[1];
                $pattern = $m[2];
                // If pattern starts and ends with %, use contains
                if (str_starts_with($pattern, '%') && str_ends_with($pattern, '%')) {
                    return $field . " contains '" . trim($pattern, '%') . "'";
                }

                // If starts with %, use endswith
                if (str_starts_with($pattern, '%')) {
                    return $field . " endswith '" . ltrim($pattern, '%') . "'";
                }

                // If ends with %, use startswith
                if (str_ends_with($pattern, '%')) {
                    return $field . " startswith '" . rtrim($pattern, '%') . "'";
                }

                // Exact match
                return $field . " == '" . $pattern . "'";
            },
            $kql,
        );

        // IS NULL → isnull()
        $kql = preg_replace('/(\w+)\s+IS\s+NULL/i', 'isnull($1)', $kql);

        // IS NOT NULL → isnotnull()
        $kql = preg_replace('/(\w+)\s+IS\s+NOT\s+NULL/i', 'isnotnull($1)', $kql);

        // IN (values) — keep as-is, Kusto supports `in` operator
        // AND / OR — keep as-is, Kusto supports them

        return $kql;
    }

    /**
     * Parse a SQL column list into individual column names.
     *
     * @return list<string>
     */
    private static function parseColumnList(string $columns): array
    {
        // Simple comma split — doesn't handle functions with commas
        $parts = explode(',', $columns);
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                // Handle aliases like `col AS alias`
                if (preg_match('/^(.+?)\s+AS\s+`?(\w+)`?$/i', $part, $m)) {
                    $result[] = $m[1]; // Use original name for project
                } else {
                    $result[] = $part;
                }
            }
        }

        return $result;
    }
}
