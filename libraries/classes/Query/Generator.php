<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * Handles generating SQL queries
 */
class Generator
{
    /**
     * returns a segment of the SQL WHERE clause regarding table name
     *
     * @param bool $tblIsGroup $table is a table group
     *
     * @return string a segment of the WHERE clause
     */
    public static function getTableNameCondition(
        string $escapedTabletable,
        bool $tblIsGroup,
    ): string {
        $sqlWhereTable = 'AND t.`TABLE_NAME` ';
        if ($tblIsGroup) {
            $sqlWhereTable .= 'LIKE ' . $escapedTabletable . '%';
        } else {
            $sqlWhereTable .= Util::getCollateForIS() . ' = ' . $escapedTabletable;
        }

        return $sqlWhereTable;
    }

    /**
     * returns a segment of the SQL WHERE clause regarding table name
     *
     * @param string[] $escapedTables tables
     *
     * @return string a segment of the WHERE clause
     */
    public static function getTableNameConditionForMultiple(array $escapedTables): string
    {
        return 'AND t.`TABLE_NAME` ' . Util::getCollateForIS() . ' IN (' . implode(', ', $escapedTables) . ')';
    }

    /**
     * returns a segment of the SQL WHERE clause regarding table type
     *
     * @param string|null $tableType whether table or view
     *
     * @return string a segment of the WHERE clause
     */
    public static function getTableTypeCondition(
        string|null $tableType,
    ): string {
        $sqlWhereTable = '';

        if ($tableType === 'view') {
            $sqlWhereTable .= " AND t.`TABLE_TYPE` NOT IN ('BASE TABLE', 'SYSTEM VERSIONED')";
        } elseif ($tableType === 'table') {
            $sqlWhereTable .= " AND t.`TABLE_TYPE` IN ('BASE TABLE', 'SYSTEM VERSIONED')";
        }

        return $sqlWhereTable;
    }

    /**
     * returns the beginning of the SQL statement to fetch the list of tables
     *
     * @param string $thisDatabases databases to list
     * @param string $sqlWhereTable additional condition
     *
     * @return string the SQL statement
     */
    public static function getSqlForTablesFull(string $thisDatabases, string $sqlWhereTable): string
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
            . ' IN (' . $thisDatabases . ')'
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
        string $where = '',
    ): string {
        $sql = 'SHOW INDEXES FROM ' . Util::backquote($database) . '.' . Util::backquote($table);
        if ($where !== '') {
            $sql .= ' WHERE (' . $where . ')';
        }

        return $sql;
    }

    /**
     * Returns SQL query for fetching columns for a table
     *
     * @param string      $database      name of database
     * @param string      $table         name of table to retrieve columns from
     * @param string|null $escapedColumn name of column, null to show all columns
     * @param bool        $full          whether to return full info or only column names
     */
    public static function getColumnsSql(
        string $database,
        string $table,
        string|null $escapedColumn = null,
        bool $full = false,
    ): string {
        return 'SHOW ' . ($full ? 'FULL' : '') . ' COLUMNS FROM '
            . Util::backquote($database) . '.' . Util::backquote($table)
            . ($escapedColumn !== null ? " LIKE '" . $escapedColumn . "'" : '');
    }

    public static function getInformationSchemaRoutinesRequest(
        string $escapedDb,
        string|null $routineType,
        string|null $escapedRoutineName,
    ): string {
        $query = 'SELECT'
            . ' `ROUTINE_SCHEMA` AS `Db`,'
            . ' `SPECIFIC_NAME` AS `Name`,'
            . ' `ROUTINE_TYPE` AS `Type`,'
            . ' `DEFINER` AS `Definer`,'
            . ' `LAST_ALTERED` AS `Modified`,'
            . ' `CREATED` AS `Created`,'
            . ' `SECURITY_TYPE` AS `Security_type`,'
            . ' `ROUTINE_COMMENT` AS `Comment`,'
            . ' `CHARACTER_SET_CLIENT` AS `character_set_client`,'
            . ' `COLLATION_CONNECTION` AS `collation_connection`,'
            . ' `DATABASE_COLLATION` AS `Database Collation`,'
            . ' `DTD_IDENTIFIER`'
            . ' FROM `information_schema`.`ROUTINES`'
            . ' WHERE `ROUTINE_SCHEMA` ' . Util::getCollateForIS()
            . " = '" . $escapedDb . "'";
        if ($routineType !== null) {
            $query .= " AND `ROUTINE_TYPE` = '" . $routineType . "'";
        }

        if ($escapedRoutineName !== null) {
            $query .= ' AND `SPECIFIC_NAME`' . " = '" . $escapedRoutineName . "'";
        }

        return $query;
    }

    public static function getInformationSchemaEventsRequest(string $escapedDb, string|null $escapedEventName): string
    {
        $query = 'SELECT'
            . ' `EVENT_SCHEMA` AS `Db`,'
            . ' `EVENT_NAME` AS `Name`,'
            . ' `DEFINER` AS `Definer`,'
            . ' `TIME_ZONE` AS `Time zone`,'
            . ' `EVENT_TYPE` AS `Type`,'
            . ' `EXECUTE_AT` AS `Execute at`,'
            . ' `INTERVAL_VALUE` AS `Interval value`,'
            . ' `INTERVAL_FIELD` AS `Interval field`,'
            . ' `STARTS` AS `Starts`,'
            . ' `ENDS` AS `Ends`,'
            . ' `STATUS` AS `Status`,'
            . ' `ORIGINATOR` AS `Originator`,'
            . ' `CHARACTER_SET_CLIENT` AS `character_set_client`,'
            . ' `COLLATION_CONNECTION` AS `collation_connection`, '
            . '`DATABASE_COLLATION` AS `Database Collation`'
            . ' FROM `information_schema`.`EVENTS`'
            . ' WHERE `EVENT_SCHEMA` ' . Util::getCollateForIS()
            . ' = ' . $escapedDb;
        if ($escapedEventName !== null) {
            $query .= ' AND `EVENT_NAME` = ' . $escapedEventName;
        }

        return $query;
    }

    public static function getInformationSchemaTriggersRequest(string $escapedDb, string|null $escapedTable): string
    {
        $query = 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION'
            . ', EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT'
            . ', EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
            . ' FROM information_schema.TRIGGERS'
            . ' WHERE EVENT_OBJECT_SCHEMA ' . Util::getCollateForIS() . '='
            . ' \'' . $escapedDb . '\'';

        if ($escapedTable !== null) {
            $query .= ' AND EVENT_OBJECT_TABLE ' . Util::getCollateForIS() . " = '" . $escapedTable . "';";
        }

        return $query;
    }

    public static function getInformationSchemaDataForCreateRequest(string $user, string $host): string
    {
        return 'SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` '
            . "WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND "
            . "'''" . $user . "''@''" . $host . "''' LIKE `GRANTEE` LIMIT 1";
    }

    public static function getInformationSchemaDataForGranteeRequest(string $user, string $host): string
    {
        return 'SELECT 1 FROM ('
            . 'SELECT `GRANTEE`, `IS_GRANTABLE` FROM '
            . '`INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION '
            . 'SELECT `GRANTEE`, `IS_GRANTABLE` FROM '
            . '`INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION '
            . 'SELECT `GRANTEE`, `IS_GRANTABLE` FROM '
            . '`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION '
            . 'SELECT `GRANTEE`, `IS_GRANTABLE` FROM '
            . '`INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t '
            . "WHERE `IS_GRANTABLE` = 'YES' AND "
            . "'''" . $user . "''@''" . $host . "''' LIKE `GRANTEE` LIMIT 1";
    }

    public static function getInformationSchemaForeignKeyConstraintsRequest(
        string $escapedDatabase,
        string $tablesListForQueryCsv,
    ): string {
        return 'SELECT'
            . ' TABLE_NAME,'
            . ' COLUMN_NAME,'
            . ' REFERENCED_TABLE_NAME,'
            . ' REFERENCED_COLUMN_NAME'
            . ' FROM information_schema.key_column_usage'
            . ' WHERE referenced_table_name IS NOT NULL'
            . ' AND TABLE_SCHEMA = ' . $escapedDatabase
            . ' AND TABLE_NAME IN (' . $tablesListForQueryCsv . ')'
            . ' AND REFERENCED_TABLE_NAME IN (' . $tablesListForQueryCsv . ');';
    }

    public static function getInformationSchemaDatabasesFullRequest(
        bool $forceStats,
        string $sqlWhereSchema,
        string $sortBy,
        string $sortOrder,
        string $limit,
    ): string {
        $sql = 'SELECT *, CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME FROM (';
        $sql .= 'SELECT BINARY s.SCHEMA_NAME AS BIN_NAME, s.DEFAULT_COLLATION_NAME';
        if ($forceStats) {
            $sql .= ','
                . ' COUNT(t.TABLE_SCHEMA)  AS SCHEMA_TABLES,'
                . ' SUM(t.TABLE_ROWS)      AS SCHEMA_TABLE_ROWS,'
                . ' SUM(t.DATA_LENGTH)     AS SCHEMA_DATA_LENGTH,'
                . ' SUM(t.MAX_DATA_LENGTH) AS SCHEMA_MAX_DATA_LENGTH,'
                . ' SUM(t.INDEX_LENGTH)    AS SCHEMA_INDEX_LENGTH,'
                . ' SUM(t.DATA_LENGTH + t.INDEX_LENGTH) AS SCHEMA_LENGTH,'
                . ' SUM(IF(t.ENGINE <> \'InnoDB\', t.DATA_FREE, 0)) AS SCHEMA_DATA_FREE';
        }

        $sql .= ' FROM `information_schema`.SCHEMATA s ';
        if ($forceStats) {
            $sql .= ' LEFT JOIN `information_schema`.TABLES t ON BINARY t.TABLE_SCHEMA = BINARY s.SCHEMA_NAME';
        }

        $sql .= $sqlWhereSchema
                . ' GROUP BY BINARY s.SCHEMA_NAME, s.DEFAULT_COLLATION_NAME'
                . ' ORDER BY ';
        if ($sortBy === 'SCHEMA_NAME' || $sortBy === 'DEFAULT_COLLATION_NAME') {
            $sql .= 'BINARY ';
        }

        $sql .= Util::backquote($sortBy)
            . ' ' . $sortOrder
            . $limit;
        $sql .= ') a';

        return $sql;
    }

    public static function getInformationSchemaColumnsFullRequest(
        string|null $escapedDatabase,
        string|null $escapedTable,
        string|null $escapedColumn,
    ): string {
        $sqlWheres = [];

        // get columns information from information_schema
        if ($escapedDatabase !== null) {
            $sqlWheres[] = '`TABLE_SCHEMA` = ' . $escapedDatabase . ' ';
        }

        if ($escapedTable !== null) {
            $sqlWheres[] = '`TABLE_NAME` = ' . $escapedTable . ' ';
        }

        if ($escapedColumn !== null) {
            $sqlWheres[] = '`COLUMN_NAME` = ' . $escapedColumn . ' ';
        }

        // for PMA bc:
        // `[SCHEMA_FIELD_NAME]` AS `[SHOW_FULL_COLUMNS_FIELD_NAME]`
        $sql = 'SELECT *,'
                    . ' `COLUMN_NAME`       AS `Field`,'
                    . ' `COLUMN_TYPE`       AS `Type`,'
                    . ' `COLLATION_NAME`    AS `Collation`,'
                    . ' `IS_NULLABLE`       AS `Null`,'
                    . ' `COLUMN_KEY`        AS `Key`,'
                    . ' `COLUMN_DEFAULT`    AS `Default`,'
                    . ' `EXTRA`             AS `Extra`,'
                    . ' `PRIVILEGES`        AS `Privileges`,'
                    . ' `COLUMN_COMMENT`    AS `Comment`'
               . ' FROM `information_schema`.`COLUMNS`';

        if ($sqlWheres !== []) {
            $sql .= "\n" . ' WHERE ' . implode(' AND ', $sqlWheres);
        }

        return $sql;
    }

    /** @return string[] */
    public static function getInformationSchemaColumns(
        string|null $database,
        string|null $table,
        string|null $column,
    ): array {
        $arrayKeys = [];

        if ($database === null) {
            $arrayKeys[] = 'TABLE_SCHEMA';
        }

        if ($table === null) {
            $arrayKeys[] = 'TABLE_NAME';
        }

        if ($column === null) {
            $arrayKeys[] = 'COLUMN_NAME';
        }

        return $arrayKeys;
    }

    /**
     * Function to get sql query for renaming the index using SQL RENAME INDEX Syntax
     */
    public static function getSqlQueryForIndexRename(
        string $dbName,
        string $tableName,
        string $oldIndexName,
        string $newIndexName,
    ): string {
        return sprintf(
            'ALTER TABLE %s.%s RENAME INDEX %s TO %s;',
            Util::backquote($dbName),
            Util::backquote($tableName),
            Util::backquote($oldIndexName),
            Util::backquote($newIndexName),
        );
    }

    /**
     * Function to get sql query to re-order the table
     */
    public static function getQueryForReorderingTable(
        string $table,
        string $orderField,
        string|null $order,
    ): string {
        return 'ALTER TABLE '
            . Util::backquote($table)
            . ' ORDER BY '
            . Util::backquote($orderField)
            . ($order === 'desc' ? ' DESC;' : ' ASC;');
    }

    /**
     * Function to get sql query to partition the table
     *
     * @param string[] $partitionNames
     */
    public static function getQueryForPartitioningTable(
        string $table,
        string $partitionOperation,
        array $partitionNames,
    ): string {
        $sqlQuery = 'ALTER TABLE '
            . Util::backquote($table) . ' '
            . $partitionOperation
            . ' PARTITION ';

        if ($partitionOperation === 'COALESCE') {
            return $sqlQuery . count($partitionNames);
        }

        return $sqlQuery . implode(', ', $partitionNames) . ';';
    }

    /** @param string[] $selectedColumns */
    public static function getAddIndexSql(string $indexType, string $table, array $selectedColumns): string
    {
        $columnsSql = implode(', ', array_map(Util::backquote(...), $selectedColumns));

        return 'ALTER TABLE ' . Util::backquote($table) . ' ADD ' . $indexType . '(' . $columnsSql . ');';
    }
}
