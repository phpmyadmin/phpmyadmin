<?php
/**
 * Tracking changes on databases, tables and views
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\RenameStatement;
use PhpMyAdmin\SqlParser\Statements\TruncateStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;

use function array_values;
use function count;
use function explode;
use function is_array;
use function mb_strpos;
use function mb_strstr;
use function mb_substr;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function serialize;
use function str_replace;
use function strtotime;
use function substr;
use function trim;

/**
 * This class tracks changes on databases, tables and views.
 *
 * @todo use stristr instead of strstr
 */
class Tracker
{
    /**
     * Whether tracking is ready.
     *
     * @var bool
     */
    protected static $enabled = false;

    /**
     * Cache to avoid quering tracking status multiple times.
     *
     * @var array
     */
    protected static $trackingCache = [];

    /**
     * Actually enables tracking. This needs to be done after all
     * underlaying code is initialized.
     *
     * @return void
     *
     * @static
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * Gets the on/off value of the Tracker module, starts initialization.
     *
     * @return bool (true=on|false=off)
     *
     * @static
     */
    public static function isActive()
    {
        global $dbi;

        if (! self::$enabled) {
            return false;
        }

        /* We need to avoid attempt to track any queries
         * from Relation::getRelationsParam
         */
        self::$enabled = false;
        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();
        /* Restore original state */
        self::$enabled = true;
        if (! $cfgRelation['trackingwork']) {
            return false;
        }

        $table = self::getTrackingTable();

        return $table !== null;
    }

    /**
     * Parses the name of a table from a SQL statement substring.
     *
     * @param string $string part of SQL statement
     *
     * @return string the name of table
     *
     * @static
     */
    protected static function getTableName($string)
    {
        if (mb_strstr($string, '.')) {
            $temp = explode('.', $string);
            $tableName = $temp[1];
        } else {
            $tableName = $string;
        }

        $str = explode("\n", $tableName);
        $tableName = $str[0];

        $tableName = str_replace([';', '`'], '', $tableName);
        $tableName = trim($tableName);

        return $tableName;
    }

    /**
     * Gets the tracking status of a table, is it active or disabled ?
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     *
     * @return bool true or false
     *
     * @static
     */
    public static function isTracked($dbName, $tableName)
    {
        global $dbi;

        if (! self::$enabled) {
            return false;
        }

        if (isset(self::$trackingCache[$dbName][$tableName])) {
            return self::$trackingCache[$dbName][$tableName];
        }

        /* We need to avoid attempt to track any queries
         * from Relation::getRelationsParam
         */
        self::$enabled = false;
        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();
        /* Restore original state */
        self::$enabled = true;
        if (! $cfgRelation['trackingwork']) {
            return false;
        }

        $sqlQuery = ' SELECT tracking_active FROM ' . self::getTrackingTable() .
        " WHERE db_name = '" . $dbi->escapeString($dbName) . "' " .
        " AND table_name = '" . $dbi->escapeString($tableName) . "' " .
        ' ORDER BY version DESC LIMIT 1';

        $result = $dbi->fetchValue($sqlQuery, 0, 0, DatabaseInterface::CONNECT_CONTROL) == 1;

        self::$trackingCache[$dbName][$tableName] = $result;

        return $result;
    }

    /**
     * Returns the comment line for the log.
     *
     * @return string Comment, contains date and username
     */
    public static function getLogComment()
    {
        $date = Util::date('Y-m-d H:i:s');
        $user = preg_replace('/\s+/', ' ', $GLOBALS['cfg']['Server']['user']);

        return '# log ' . $date . ' ' . $user . "\n";
    }

    /**
     * Creates tracking version of a table / view
     * (in other words: create a job to track future changes on the table).
     *
     * @param string $dbName      name of database
     * @param string $tableName   name of table
     * @param string $version     version
     * @param string $trackingSet set of tracking statements
     * @param bool   $isView      if table is a view
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function createVersion(
        $dbName,
        $tableName,
        $version,
        $trackingSet = '',
        bool $isView = false
    ) {
        global $sql_backquotes, $export_type, $dbi;

        $relation = new Relation($dbi);

        if ($trackingSet == '') {
            $trackingSet = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        /**
         * get Export SQL instance
         *
         * @var ExportSql $exportSqlPlugin
         */
        $exportSqlPlugin = Plugins::getPlugin(
            'export',
            'sql',
            'libraries/classes/Plugins/Export/',
            [
                'export_type' => $export_type,
                'single_table' => false,
            ]
        );

        $sql_backquotes = true;

        $date = Util::date('Y-m-d H:i:s');

        // Get data definition snapshot of table

        $columns = $dbi->getColumns($dbName, $tableName, null, true);
        // int indices to reduce size
        $columns = array_values($columns);
        // remove Privileges to reduce size
        for ($i = 0, $nb = count($columns); $i < $nb; $i++) {
            unset($columns[$i]['Privileges']);
        }

        $indexes = $dbi->getTableIndexes($dbName, $tableName);

        $snapshot = [
            'COLUMNS' => $columns,
            'INDEXES' => $indexes,
        ];
        $snapshot = serialize($snapshot);

        // Get DROP TABLE / DROP VIEW and CREATE TABLE SQL statements
        $sql_backquotes = true;

        $createSql  = '';

        if (
            $GLOBALS['cfg']['Server']['tracking_add_drop_table'] == true
            && $isView === false
        ) {
            $createSql .= self::getLogComment()
                . 'DROP TABLE IF EXISTS ' . Util::backquote($tableName) . ";\n";
        }

        if (
            $GLOBALS['cfg']['Server']['tracking_add_drop_view'] == true
            && $isView === true
        ) {
            $createSql .= self::getLogComment()
                . 'DROP VIEW IF EXISTS ' . Util::backquote($tableName) . ";\n";
        }

        $createSql .= self::getLogComment() .
            $exportSqlPlugin->getTableDef($dbName, $tableName, "\n", '');

        // Save version

        $sqlQuery = "/*NOTRACK*/\n" .
        'INSERT INTO ' . self::getTrackingTable() . ' (' .
        'db_name, ' .
        'table_name, ' .
        'version, ' .
        'date_created, ' .
        'date_updated, ' .
        'schema_snapshot, ' .
        'schema_sql, ' .
        'data_sql, ' .
        'tracking ' .
        ') ' .
        "values (
        '" . $dbi->escapeString($dbName) . "',
        '" . $dbi->escapeString($tableName) . "',
        '" . $dbi->escapeString($version) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($snapshot) . "',
        '" . $dbi->escapeString($createSql) . "',
        '" . $dbi->escapeString("\n") . "',
        '" . $dbi->escapeString($trackingSet)
        . "' )";

        $result = $relation->queryAsControlUser($sqlQuery);

        if ($result) {
            // Deactivate previous version
            self::deactivateTracking($dbName, $tableName, (int) $version - 1);
        }

        return $result;
    }

    /**
     * Removes all tracking data for a table or a version of a table
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     * @param string $version   version
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function deleteTracking($dbName, $tableName, $version = '')
    {
        global $dbi;

        $relation = new Relation($dbi);

        $sqlQuery = "/*NOTRACK*/\n"
            . 'DELETE FROM ' . self::getTrackingTable()
            . " WHERE `db_name` = '"
            . $dbi->escapeString($dbName) . "'"
            . " AND `table_name` = '"
            . $dbi->escapeString($tableName) . "'";
        if ($version) {
            $sqlQuery .= " AND `version` = '" . $dbi->escapeString($version) . "'";
        }

        return $relation->queryAsControlUser($sqlQuery);
    }

    /**
     * Creates tracking version of a database
     * (in other words: create a job to track future changes on the database).
     *
     * @param string $dbName      name of database
     * @param string $version     version
     * @param string $query       query
     * @param string $trackingSet set of tracking statements
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function createDatabaseVersion(
        $dbName,
        $version,
        $query,
        $trackingSet = 'CREATE DATABASE,ALTER DATABASE,DROP DATABASE'
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        $date = Util::date('Y-m-d H:i:s');

        if ($trackingSet == '') {
            $trackingSet = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        $createSql  = '';

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_database'] == true) {
            $createSql .= self::getLogComment() . 'DROP DATABASE IF EXISTS ' . Util::backquote($dbName) . ";\n";
        }

        $createSql .= self::getLogComment() . $query;

        // Save version
        $sqlQuery = "/*NOTRACK*/\n" .
        'INSERT INTO ' . self::getTrackingTable() . ' (' .
        'db_name, ' .
        'table_name, ' .
        'version, ' .
        'date_created, ' .
        'date_updated, ' .
        'schema_snapshot, ' .
        'schema_sql, ' .
        'data_sql, ' .
        'tracking ' .
        ') ' .
        "values (
        '" . $dbi->escapeString($dbName) . "',
        '" . $dbi->escapeString('') . "',
        '" . $dbi->escapeString($version) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString('') . "',
        '" . $dbi->escapeString($createSql) . "',
        '" . $dbi->escapeString("\n") . "',
        '" . $dbi->escapeString($trackingSet)
        . "' )";

        return $relation->queryAsControlUser($sqlQuery);
    }

    /**
     * Changes tracking of a table.
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     * @param string $version   version
     * @param int    $newState  the new state of tracking
     *
     * @return int result of SQL query
     *
     * @static
     */
    private static function changeTracking(
        $dbName,
        $tableName,
        $version,
        $newState
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        $sqlQuery = ' UPDATE ' . self::getTrackingTable() .
        " SET `tracking_active` = '" . $newState . "' " .
        " WHERE `db_name` = '" . $dbi->escapeString($dbName) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tableName) . "' " .
        " AND `version` = '" . $dbi->escapeString((string) $version) . "' ";

        return $relation->queryAsControlUser($sqlQuery);
    }

    /**
     * Changes tracking data of a table.
     *
     * @param string       $dbName    name of database
     * @param string       $tableName name of table
     * @param string       $version   version
     * @param string       $type      type of data(DDL || DML)
     * @param string|array $newData   the new tracking data
     *
     * @return bool result of change
     *
     * @static
     */
    public static function changeTrackingData(
        $dbName,
        $tableName,
        $version,
        $type,
        $newData
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        if ($type === 'DDL') {
            $saveTo = 'schema_sql';
        } elseif ($type === 'DML') {
            $saveTo = 'data_sql';
        } else {
            return false;
        }

        $date  = Util::date('Y-m-d H:i:s');

        $newDataProcessed = '';
        if (is_array($newData)) {
            foreach ($newData as $data) {
                $newDataProcessed .= '# log ' . $date . ' ' . $data['username']
                    . $dbi->escapeString($data['statement']) . "\n";
            }
        } else {
            $newDataProcessed = $newData;
        }

        $sqlQuery = ' UPDATE ' . self::getTrackingTable() .
        ' SET `' . $saveTo . "` = '" . $newDataProcessed . "' " .
        " WHERE `db_name` = '" . $dbi->escapeString($dbName) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tableName) . "' " .
        " AND `version` = '" . $dbi->escapeString($version) . "' ";

        $result = $relation->queryAsControlUser($sqlQuery);

        return (bool) $result;
    }

    /**
     * Activates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     *
     * @return int result of SQL query
     *
     * @static
     */
    public static function activateTracking($dbname, $tablename, $version)
    {
        return self::changeTracking($dbname, $tablename, $version, 1);
    }

    /**
     * Deactivates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     *
     * @return int result of SQL query
     *
     * @static
     */
    public static function deactivateTracking($dbname, $tablename, $version)
    {
        return self::changeTracking($dbname, $tablename, $version, 0);
    }

    /**
     * Gets the newest version of a tracking job
     * (in other words: gets the HEAD version).
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $statement tracked statement
     *
     * @return int (-1 if no version exists | >  0 if a version exists)
     *
     * @static
     */
    public static function getVersion($dbname, $tablename, $statement = null)
    {
        /** @var DatabaseInterface $dbi */
        global $dbi;

        $relation = new Relation($dbi);

        $sqlQuery = ' SELECT MAX(version) FROM ' . self::getTrackingTable() .
        " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tablename) . "' ";

        if ($statement != '') {
            $sqlQuery .= " AND FIND_IN_SET('" . $statement . "',tracking) > 0";
        }

        $result = $relation->queryAsControlUser($sqlQuery, false);

        if ($result === false) {
            return -1;
        }

        $row = $dbi->fetchArray($result);

        return $row[0] ?? -1;
    }

    /**
     * Gets the record of a tracking job.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version number
     *
     * @return mixed record DDM log, DDL log, structure snapshot, tracked
     *         statements.
     *
     * @static
     */
    public static function getTrackedData($dbname, $tablename, $version)
    {
        global $dbi;

        $relation = new Relation($dbi);

        $sqlQuery = ' SELECT * FROM ' . self::getTrackingTable() .
            " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' ";
        if (! empty($tablename)) {
            $sqlQuery .= " AND `table_name` = '"
                . $dbi->escapeString($tablename) . "' ";
        }

        $sqlQuery .= " AND `version` = '" . $dbi->escapeString($version)
            . "' ORDER BY `version` DESC LIMIT 1";

        $mixed = $dbi->fetchAssoc($relation->queryAsControlUser($sqlQuery));

        // PHP 7.4 fix for accessing array offset on null
        if (! is_array($mixed)) {
            $mixed = [
                'schema_sql' => null,
                'data_sql' => null,
                'tracking' => null,
                'schema_snapshot' => null,
            ];
        }

        // Parse log
        $logSchemaEntries = explode('# log ', (string) $mixed['schema_sql']);
        $logDataEntries   = explode('# log ', (string) $mixed['data_sql']);

        $ddlDateFrom = $date = Util::date('Y-m-d H:i:s');

        $ddlog = [];
        $firstIteration = true;

        // Iterate tracked data definition statements
        // For each log entry we want to get date, username and statement
        foreach ($logSchemaEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date      = mb_substr($logEntry, 0, 19);
            $username  = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20
            );
            if ($firstIteration) {
                $ddlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $ddlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $dateFrom = $ddlDateFrom;
        $ddlDateTo = $date;

        $dmlDateFrom = $dateFrom;

        $dmlog = [];
        $firstIteration = true;

        // Iterate tracked data manipulation statements
        // For each log entry we want to get date, username and statement
        foreach ($logDataEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date      = mb_substr($logEntry, 0, 19);
            $username  = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20
            );
            if ($firstIteration) {
                $dmlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $dmlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $dmlDateTo = $date;

        // Define begin and end of date range for both logs
        $data = [];
        if (strtotime($ddlDateFrom) <= strtotime($dmlDateFrom)) {
            $data['date_from'] = $ddlDateFrom;
        } else {
            $data['date_from'] = $dmlDateFrom;
        }

        if (strtotime($ddlDateTo) >= strtotime($dmlDateTo)) {
            $data['date_to'] = $ddlDateTo;
        } else {
            $data['date_to'] = $dmlDateTo;
        }

        $data['ddlog']           = $ddlog;
        $data['dmlog']           = $dmlog;
        $data['tracking']        = $mixed['tracking'];
        $data['schema_snapshot'] = $mixed['schema_snapshot'];

        return $data;
    }

    /**
     * Parses a query. Gets
     *  - statement identifier (UPDATE, ALTER TABLE, ...)
     *  - type of statement, is it part of DDL or DML ?
     *  - tablename
     *
     * @param string $query query
     *
     * @return array containing identifier, type and tablename.
     *
     * @static
     * @todo: using PMA SQL Parser when possible
     * @todo: support multi-table/view drops
     */
    public static function parseQuery($query): array
    {
        // Usage of PMA_SQP does not work here
        //
        // require_once("libraries/sqlparser.lib.php");
        // $parsed_sql = PMA_SQP_parse($query);
        // $sql_info = PMA_SQP_analyze($parsed_sql);

        $parser = new Parser($query);

        $tokens = $parser->list->tokens;

        // Parse USE statement, need it for SQL dump imports
        if ($tokens[0]->value === 'USE') {
            $GLOBALS['db'] = $tokens[2]->value;
        }

        $result = [];

        if (! empty($parser->statements)) {
            $statement = $parser->statements[0];
            $options   = isset($statement->options) ? $statement->options->options : null;

            /*
             * DDL statements
             */
            $result['type'] = 'DDL';

            // Parse CREATE statement
            if ($statement instanceof CreateStatement) {
                if (empty($options) || ! isset($options[6])) {
                    return $result;
                }

                if ($options[6] === 'VIEW' || $options[6] === 'TABLE') {
                    $result['identifier'] = 'CREATE ' . $options[6];
                    $result['tablename']  = $statement->name->table;
                } elseif ($options[6] === 'DATABASE') {
                    $result['identifier'] = 'CREATE DATABASE';
                    $result['tablename']  = '';

                    // In case of CREATE DATABASE, database field of the CreateStatement is the name of the database
                    $GLOBALS['db']        = $statement->name->database;
                } elseif (
                    $options[6] === 'INDEX'
                          || $options[6] === 'UNIQUE INDEX'
                          || $options[6] === 'FULLTEXT INDEX'
                          || $options[6] === 'SPATIAL INDEX'
                ) {
                    $result['identifier'] = 'CREATE INDEX';

                    // In case of CREATE INDEX, we have to get the table name from body of the statement
                    $result['tablename']  = $statement->body[3]->value === '.' ? $statement->body[4]->value
                                                                              : $statement->body[2]->value;
                }
            } elseif ($statement instanceof AlterStatement) { // Parse ALTER statement
                if (empty($options) || ! isset($options[3])) {
                    return $result;
                }

                if ($options[3] === 'VIEW' || $options[3] === 'TABLE') {
                    $result['identifier']   = 'ALTER ' . $options[3];
                    $result['tablename']    = $statement->table->table;
                } elseif ($options[3] === 'DATABASE') {
                    $result['identifier']   = 'ALTER DATABASE';
                    $result['tablename']    = '';

                    $GLOBALS['db']          = $statement->table->table;
                }
            } elseif ($statement instanceof DropStatement) { // Parse DROP statement
                if (empty($options) || ! isset($options[1])) {
                    return $result;
                }

                if ($options[1] === 'VIEW' || $options[1] === 'TABLE') {
                    $result['identifier'] = 'DROP ' . $options[1];
                    $result['tablename']  = $statement->fields[0]->table;
                } elseif ($options[1] === 'DATABASE') {
                    $result['identifier'] = 'DROP DATABASE';
                    $result['tablename']  = '';

                    $GLOBALS['db']        = $statement->fields[0]->table;
                } elseif ($options[1] === 'INDEX') {
                    $result['identifier']   = 'DROP INDEX';
                    $result['tablename']    = $statement->table->table;
                }
            } elseif ($statement instanceof RenameStatement) { // Parse RENAME statement
                $result['identifier']               = 'RENAME TABLE';
                $result['tablename']                = $statement->renames[0]->old->table;
                $result['tablename_after_rename']   = $statement->renames[0]->new->table;
            }

            if (isset($result['identifier'])) {
                return $result;
            }

            /*
             * DML statements
             */
            $result['type'] = 'DML';

            // Parse UPDATE statement
            if ($statement instanceof UpdateStatement) {
                $result['identifier']   = 'UPDATE';
                $result['tablename']    = $statement->tables[0]->table;
            }

            // Parse INSERT INTO statement
            if ($statement instanceof InsertStatement) {
                $result['identifier']   = 'INSERT';
                $result['tablename']    = $statement->into->dest->table;
            }

            // Parse DELETE statement
            if ($statement instanceof DeleteStatement) {
                $result['identifier']   = 'DELETE';
                $result['tablename']    = $statement->from[0]->table;
            }

            // Parse TRUNCATE statement
            if ($statement instanceof TruncateStatement) {
                $result['identifier']   = 'TRUNCATE';
                $result['tablename']    = $statement->table->table;
            }
        }

        return $result;
    }

    /**
     * Analyzes a given SQL statement and saves tracking data.
     *
     * @param string $query a SQL query
     *
     * @return void
     *
     * @static
     */
    public static function handleQuery($query)
    {
        global $dbi;

        $relation = new Relation($dbi);

        // If query is marked as untouchable, leave
        if (mb_strstr($query, '/*NOTRACK*/')) {
            return;
        }

        if (! (substr($query, -1) === ';')) {
            $query .= ";\n";
        }

        // Get some information about query
        $result = self::parseQuery($query);

        // Get database name
        $dbname = trim($GLOBALS['db'] ?? '', '`');
        // $dbname can be empty, for example when coming from Synchronize
        // and this is a query for the remote server
        if (empty($dbname)) {
            return;
        }

        // If we found a valid statement
        if (! isset($result['identifier'])) {
            return;
        }

        $version = self::getVersion(
            $dbname,
            $result['tablename'],
            $result['identifier']
        );

        // If version not exists and auto-creation is enabled
        if (
            $GLOBALS['cfg']['Server']['tracking_version_auto_create'] == true
            && $version == -1
        ) {
            // Create the version

            switch ($result['identifier']) {
                case 'CREATE TABLE':
                    self::createVersion($dbname, $result['tablename'], '1');
                    break;
                case 'CREATE VIEW':
                    self::createVersion(
                        $dbname,
                        $result['tablename'],
                        '1',
                        '',
                        true
                    );
                    break;
                case 'CREATE DATABASE':
                    self::createDatabaseVersion($dbname, '1', $query);
                    break;
            }
        }

        // If version exists
        if ($version == -1) {
            return;
        }

        if (! self::isTracked($dbname, $result['tablename'])) {
            return;
        }

        if ($result['type'] === 'DDL') {
            $saveTo = 'schema_sql';
        } elseif ($result['type'] === 'DML') {
            $saveTo = 'data_sql';
        } else {
            $saveTo = '';
        }

        $date  = Util::date('Y-m-d H:i:s');

        // Cut off `dbname`. from query
        $query = preg_replace(
            '/`' . preg_quote($dbname, '/') . '`\s?\./',
            '',
            $query
        );

        // Add log information
        $query = self::getLogComment() . $query;

        // Mark it as untouchable
        $sqlQuery = " /*NOTRACK*/\n"
            . ' UPDATE ' . self::getTrackingTable()
            . ' SET ' . Util::backquote($saveTo)
            . ' = CONCAT( ' . Util::backquote($saveTo) . ",'\n"
            . $dbi->escapeString($query) . "') ,"
            . " `date_updated` = '" . $date . "' ";

        // If table was renamed we have to change
        // the tablename attribute in pma_tracking too
        if ($result['identifier'] === 'RENAME TABLE') {
            $sqlQuery .= ', `table_name` = \''
                . $dbi->escapeString($result['tablename_after_rename'])
                . '\' ';
        }

        // Save the tracking information only for
        //     1. the database
        //     2. the table / view
        //     3. the statements
        // we want to track
        $sqlQuery .=
        " WHERE FIND_IN_SET('" . $result['identifier'] . "',tracking) > 0" .
        " AND `db_name` = '" . $dbi->escapeString($dbname ?? '') . "' " .
        " AND `table_name` = '"
        . $dbi->escapeString($result['tablename']) . "' " .
        " AND `version` = '" . $dbi->escapeString($version ?? '') . "' ";

        $relation->queryAsControlUser($sqlQuery);
    }

    /**
     * Returns the tracking table
     *
     * @return string tracking table
     */
    private static function getTrackingTable()
    {
        global $dbi;

        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();

        return Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['tracking']);
    }
}
