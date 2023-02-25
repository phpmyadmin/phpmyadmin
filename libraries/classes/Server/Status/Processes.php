<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function count;
use function mb_strtolower;
use function strlen;
use function ucfirst;

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
        if (
            ($orderByField !== '' && $sortOrder !== '')
            || $showExecuting
        ) {
            $urlParams['order_by_field'] = $orderByField;
            $urlParams['sort_order'] = $sortOrder;
            $urlParams['showExecuting'] = $showExecuting;
            $sqlQuery = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ';
        }

        if ($showExecuting) {
            $sqlQuery .= ' WHERE state != "" ';
        }

        if ($orderByField !== '' && $sortOrder !== '') {
            $sqlQuery .= ' ORDER BY ' . Util::backquote($orderByField) . ' ' . $sortOrder;
        }

        $result = $this->dbi->query($sqlQuery);
        $rows = [];
        while ($process = $result->fetchAssoc()) {
            // Array keys need to modify due to the way it has used
            // to display column values
            foreach (array_keys($process) as $key) {
                $newKey = ucfirst(mb_strtolower($key));
                if ($newKey === $key) {
                    continue;
                }

                $process[$newKey] = $process[$key];
                unset($process[$key]);
            }

            $rows[] = [
                'id' => $process['Id'],
                'user' => $process['User'],
                'host' => $process['Host'],
                'db' => ! isset($process['Db']) || strlen($process['Db']) === 0 ? '' : $process['Db'],
                'command' => $process['Command'],
                'time' => $process['Time'],
                'state' => ! empty($process['State']) ? $process['State'] : '---',
                'progress' => ! empty($process['Progress']) ? $process['Progress'] : '---',
                'info' => ! empty($process['Info']) ? Generator::formatSql($process['Info'], ! $showFullSql) : '---',
            ];
        }

        $columns = $this->getSortableColumnsForProcessList($showExecuting, $showFullSql, $orderByField, $sortOrder);

        return [
            'columns' => $columns,
            'rows' => $rows,
            'refresh_params' => $urlParams,
            'is_mariadb' => $this->dbi->isMariaDB(),
        ];
    }

    private function getSortableColumnsForProcessList(
        bool $showExecuting,
        bool $showFullSql,
        string $orderByField,
        string $sortOrder,
    ): array {
        // This array contains display name and real column name of each
        // sortable column in the table
        $sortableColumns = [
            [
                'column_name' => __('ID'),
                'order_by_field' => 'Id',
            ],
            [
                'column_name' => __('User'),
                'order_by_field' => 'User',
            ],
            [
                'column_name' => __('Host'),
                'order_by_field' => 'Host',
            ],
            [
                'column_name' => __('Database'),
                'order_by_field' => 'Db',
            ],
            [
                'column_name' => __('Command'),
                'order_by_field' => 'Command',
            ],
            [
                'column_name' => __('Time'),
                'order_by_field' => 'Time',
            ],
            [
                'column_name' => __('Status'),
                'order_by_field' => 'State',
            ],
        ];

        if ($this->dbi->isMariaDB()) {
            $sortableColumns[] = [
                'column_name' => __('Progress'),
                'order_by_field' => 'Progress',
            ];
        }

        $sortableColumns[] = [
            'column_name' => __('SQL query'),
            'order_by_field' => 'Info',
        ];

        $sortableColCount = count($sortableColumns);

        $columns = [];
        foreach ($sortableColumns as $columnKey => $column) {
            $is_sorted = $orderByField !== ''
                && $sortOrder !== ''
                && ($orderByField == $column['order_by_field']);

            $column['sort_order'] = 'ASC';
            if ($is_sorted && $sortOrder === 'ASC') {
                $column['sort_order'] = 'DESC';
            }

            if ($showExecuting) {
                $column['showExecuting'] = 'on';
            }

            $columns[$columnKey] = [
                'name' => $column['column_name'],
                'params' => $column,
                'is_sorted' => $is_sorted,
                'sort_order' => $column['sort_order'],
                'has_full_query' => false,
                'is_full' => false,
            ];

            if (0 !== --$sortableColCount) {
                continue;
            }

            $columns[$columnKey]['has_full_query'] = true;
            if (! $showFullSql) {
                continue;
            }

            $columns[$columnKey]['is_full'] = true;
        }

        return $columns;
    }
}
