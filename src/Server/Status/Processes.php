<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Util;

use function __;
use function array_change_key_case;
use function array_fill;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function number_format;
use function preg_match;
use function preg_quote;
use function str_replace;
use function substr_count;

use const CASE_LOWER;

final class Processes
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /** @return array<string, array|string|bool> */
    public function getList(bool $showExecuting, bool $showFullSql, string $orderByField, string $sortOrder): array
    {
        $urlParams = [];

        $urlParams['full'] = $showFullSql ? '' : 1;

        $sqlQuery = $showFullSql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
        $useIS = $showExecuting || ($orderByField !== '' && $sortOrder !== '');
        if ($useIS) {
            $urlParams['order_by_field'] = $orderByField;
            $urlParams['sort_order'] = $sortOrder;
            $urlParams['showExecuting'] = $showExecuting;
            $sqlQuery = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST`';
        }

        if ($showExecuting) {
            $sqlQuery .= " WHERE `COMMAND` <> 'Sleep' AND `STATE` <> 'Waiting for next activation'";
        }

        if ($orderByField !== '' && $sortOrder !== '') {
            $sqlQuery .= ' ORDER BY ' . Util::backquote($orderByField) . ' ' . $sortOrder;
        }

        $result = $this->dbi->query($sqlQuery);
        $rows = [];
        while ($process = $result->fetchAssoc()) {
            // Array keys need to modify due to the way it has used
            // to display column values
            $process = array_change_key_case($process, CASE_LOWER);

            $progress = ! empty($process['progress']) ? $process['progress'] : '---';
            if ($useIS && ! empty($process['progress'])) {
                $stage = array_key_exists('stage', $process) ? (int) $process['stage'] : null;
                $maxStage = array_key_exists('max_stage', $process) ? (int) $process['max_stage'] : null;
                if ($stage !== null && $maxStage !== null && $maxStage > 1) {
                    $progress = number_format(($stage - 1) / $maxStage * 100 + ((float) $progress) / $maxStage, 3);
                }
            }

            $rows[] = [
                'id' => $process['id'],
                'user' => (string) $process['user'],
                'host' => $process['host'],
                'host_without_port' => self::stripPort((string) $process['host']),
                'db' => $process['db'] ?? '',
                'command' => $process['command'],
                'time' => $process['time'],
                'state' => ! empty($process['state']) ? $process['state'] : '---',
                'progress' => $progress,
                'info' => ! empty($process['info']) ? Generator::formatSql($process['info'], ! $showFullSql) : '---',
            ];
        }

        $this->resolveGrantHostnames($rows);

        $columns = $this->getSortableColumnsForProcessList($showExecuting, $showFullSql, $orderByField, $sortOrder);

        return [
            'columns' => $columns,
            'rows' => $rows,
            'refresh_params' => $urlParams,
            'is_mariadb' => $this->dbi->isMariaDB(),
        ];
    }

    /**
     * Strips the port from a `SHOW PROCESSLIST`/`INFORMATION_SCHEMA.PROCESSLIST`
     * `Host` value (e.g. `10.0.0.5:41414`, `[::1]:41414`, `localhost`).
     *
     * `mysql.user`/`mysql.global_priv`'s `Host` column never includes a port
     * (it is matched by exact string against a plain hostname/IP/`%`
     * wildcard), so passing the raw connection host straight through to a
     * user-lookup link never matches. IPv6 addresses are only stripped when
     * bracketed with an explicit port (`[::1]:3306`) — a bare IPv6 address
     * has multiple colons of its own and no port to strip, so it is left
     * untouched to avoid truncating it.
     */
    private static function stripPort(string $host): string
    {
        if (preg_match('/^\[(?P<host>[0-9a-fA-F:]+)]:\d+$/', $host, $matches) === 1) {
            return $matches['host'];
        }

        if (substr_count($host, ':') === 1 && preg_match('/^(?P<host>.+):\d+$/', $host, $matches) === 1) {
            return $matches['host'];
        }

        return $host;
    }

    /**
     * Replaces each row's `host_without_port` with the actual `Host` pattern
     * stored in `mysql.user`/`mysql.global_priv`, when one can be found.
     *
     * `SHOW PROCESSLIST` only ever reports the connecting client's real
     * host/IP — never the (possibly wildcarded, e.g. `%`, `192.168.%`) grant
     * entry that authorized the connection. Since the grant `Host` column
     * uses the same `%`/`_` wildcard syntax as SQL `LIKE`, the actual
     * pattern can be recovered by fetching every `Host` registered for the
     * row's `User` and testing which one matches the connecting host —
     * preferring an exact (non-wildcard) match when one exists, since that
     * is the least ambiguous choice.
     *
     * Reading `mysql.user` requires a privilege the current user might not
     * have; failing to read it (or finding no match) leaves
     * `host_without_port` as the plain connecting host, same as before this
     * resolution existed — an imprecise but harmless fallback.
     *
     * @param list<array{user: string, host_without_port: string}&array<string, mixed>> $rows
     */
    private function resolveGrantHostnames(array &$rows): void
    {
        $usernames = array_values(array_unique(array_map(
            static fn (array $row): string => $row['user'],
            $rows,
        )));

        if ($usernames === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($usernames), '?'));
        $result = $this->dbi->executeQuery(
            'SELECT `User`, `Host` FROM `mysql`.`user` WHERE `User` IN (' . $placeholders . ')',
            $usernames,
        );

        if ($result === null) {
            return;
        }

        $hostsByUser = [];
        foreach ($result as $grant) {
            $hostsByUser[(string) $grant['User']][] = (string) $grant['Host'];
        }

        foreach ($rows as &$row) {
            $candidates = $hostsByUser[$row['user']] ?? [];
            $match = self::findMatchingHost($row['host_without_port'], $candidates);
            if ($match === null) {
                continue;
            }

            $row['host_without_port'] = $match;
        }
    }

    /**
     * Finds, among the given grant `Host` patterns, the one that the actual
     * connecting host satisfies — an exact literal match wins over a
     * wildcard pattern, since it is the least ambiguous choice.
     *
     * @param string[] $patterns
     */
    private static function findMatchingHost(string $host, array $patterns): string|null
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $host) {
                return $pattern;
            }
        }

        foreach ($patterns as $pattern) {
            if (self::hostMatchesPattern($host, $pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Tests a connecting host against a `mysql.user`-style `Host` pattern,
     * which uses the same `%`/`_` wildcard syntax as SQL `LIKE` — `%` for
     * any run of characters, `_` for exactly one. Netmask notation
     * (`192.168.1.0/255.255.255.0`) is a separate, non-`LIKE` syntax MySQL
     * also accepts for `Host`; it is not handled here and such a pattern
     * simply will not match, falling back to the plain connecting host.
     */
    private static function hostMatchesPattern(string $host, string $pattern): bool
    {
        $regex = str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/'));

        return preg_match('/^' . $regex . '$/i', $host) === 1;
    }

    /** @return mixed[] */
    private function getSortableColumnsForProcessList(
        bool $showExecuting,
        bool $showFullSql,
        string $orderByField,
        string $sortOrder,
    ): array {
        // This array contains display name and real column name of each
        // sortable column in the table
        $sortableColumns = [
            ['column_name' => __('ID'), 'order_by_field' => 'Id'],
            ['column_name' => __('User'), 'order_by_field' => 'User'],
            ['column_name' => __('Host'), 'order_by_field' => 'Host'],
            ['column_name' => __('Database'), 'order_by_field' => 'Db'],
            ['column_name' => __('Command'), 'order_by_field' => 'Command'],
            ['column_name' => __('Time'), 'order_by_field' => 'Time'],
            ['column_name' => __('Status'), 'order_by_field' => 'State'],
        ];

        if ($this->dbi->isMariaDB()) {
            $sortableColumns[] = ['column_name' => __('Progress'), 'order_by_field' => 'Progress'];
        }

        $sortableColumns[] = ['column_name' => __('SQL query'), 'order_by_field' => 'Info'];

        $sortableColCount = count($sortableColumns);

        $columns = [];
        foreach ($sortableColumns as $columnKey => $column) {
            $isSorted = $orderByField !== ''
                && $sortOrder !== ''
                && $orderByField === $column['order_by_field'];

            $column['sort_order'] = 'ASC';
            if ($isSorted && $sortOrder === 'ASC') {
                $column['sort_order'] = 'DESC';
            }

            if ($showExecuting) {
                $column['showExecuting'] = 'on';
            }

            $columns[$columnKey] = [
                'name' => $column['column_name'],
                'params' => $column,
                'is_sorted' => $isSorted,
                'sort_order' => $column['sort_order'],
                'has_full_query' => false,
                'is_full' => false,
            ];

            if (0 !== --$sortableColCount) {
                continue;
            }

            $columns[$columnKey]['has_full_query'] = true;

            $columns[$columnKey]['is_full'] = $showFullSql;
        }

        return $columns;
    }
}
