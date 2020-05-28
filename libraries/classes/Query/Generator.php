<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;
use function implode;
use function is_array;

/**
 * Handles generating SQL queries
 */
class Generator
{
    /**
     * returns a segment of the SQL WHERE clause regarding table name and type
     *
     * @param array|string $escapedTableOrTables table(s)
     * @param bool         $tblIsGroup           $table is a table group
     * @param string       $tableType            whether table or view
     *
     * @return string a segment of the WHERE clause
     */
    public static function getTableCondition(
        $escapedTableOrTables,
        bool $tblIsGroup,
        ?string $tableType
    ): string {
        // get table information from information_schema
        if ($escapedTableOrTables) {
            if (is_array($escapedTableOrTables)) {
                $sqlWhereTable = 'AND t.`TABLE_NAME` '
                    . Util::getCollateForIS() . ' IN (\''
                    . implode(
                        '\', \'',
                        $escapedTableOrTables
                    )
                    . '\')';
            } elseif ($tblIsGroup === true) {
                $sqlWhereTable = 'AND t.`TABLE_NAME` LIKE \''
                    . Util::escapeMysqlWildcards(
                        $escapedTableOrTables
                    )
                    . '%\'';
            } else {
                $sqlWhereTable = 'AND t.`TABLE_NAME` '
                    . Util::getCollateForIS() . ' = \''
                    . $escapedTableOrTables . '\'';
            }
        } else {
            $sqlWhereTable = '';
        }

        if ($tableType) {
            if ($tableType === 'view') {
                $sqlWhereTable .= " AND t.`TABLE_TYPE` NOT IN ('BASE TABLE', 'SYSTEM VERSIONED')";
            } elseif ($tableType === 'table') {
                $sqlWhereTable .= " AND t.`TABLE_TYPE` IN ('BASE TABLE', 'SYSTEM VERSIONED')";
            }
        }

        return $sqlWhereTable;
    }

    /**
     * returns the beginning of the SQL statement to fetch the list of tables
     *
     * @param string[] $thisDatabases databases to list
     * @param string   $sqlWhereTable additional condition
     *
     * @return string the SQL statement
     */
    public static function getSqlForTablesFull(array $thisDatabases, string $sqlWhereTable): string
    {
        return 'SELECT *,'
            . ' `TABLE_SCHEMA`       AS `Db`,'
            . ' `TABLE_NAME`         AS `Name`,'
            . ' `TABLE_TYPE`         AS `TABLE_TYPE`,'
            . ' `ENGINE`             AS `Engine`,'
            . ' `ENGINE`             AS `Type`,'
            . ' `VERSION`            AS `Version`,'
            . ' `ROW_FORMAT`         AS `Row_format`,'
            . ' `TABLE_ROWS`         AS `Rows`,'
            . ' `AVG_ROW_LENGTH`     AS `Avg_row_length`,'
            . ' `DATA_LENGTH`        AS `Data_length`,'
            . ' `MAX_DATA_LENGTH`    AS `Max_data_length`,'
            . ' `INDEX_LENGTH`       AS `Index_length`,'
            . ' `DATA_FREE`          AS `Data_free`,'
            . ' `AUTO_INCREMENT`     AS `Auto_increment`,'
            . ' `CREATE_TIME`        AS `Create_time`,'
            . ' `UPDATE_TIME`        AS `Update_time`,'
            . ' `CHECK_TIME`         AS `Check_time`,'
            . ' `TABLE_COLLATION`    AS `Collation`,'
            . ' `CHECKSUM`           AS `Checksum`,'
            . ' `CREATE_OPTIONS`     AS `Create_options`,'
            . ' `TABLE_COMMENT`      AS `Comment`'
            . ' FROM `information_schema`.`TABLES` t'
            . ' WHERE `TABLE_SCHEMA` ' . Util::getCollateForIS()
            . ' IN (\'' . implode("', '", $thisDatabases) . '\')'
            . ' ' . $sqlWhereTable;
    }

    /**
     * Returns SQL for fetching information on table indexes (SHOW INDEXES)
     *
     * @param string $database name of database
     * @param string $table    name of the table whose indexes are to be retrieved
     * @param string $where    additional conditions for WHERE
     *
     * @return string SQL for getting indexes
     */
    public static function getTableIndexesSql(
        string $database,
        string $table,
        ?string $where = null
    ): string {
        $sql = 'SHOW INDEXES FROM ' . Util::backquote($database) . '.'
            . Util::backquote($table);
        if ($where) {
            $sql .= ' WHERE (' . $where . ')';
        }

        return $sql;
    }
}
