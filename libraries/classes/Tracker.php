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

        $pma_table = self::getTrackingTable();

        return $pma_table !== null;
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
            $tablename = $temp[1];
        } else {
            $tablename = $string;
        }

        $str = explode("\n", $tablename);
        $tablename = $str[0];

        $tablename = str_replace([';', '`'], '', $tablename);
        $tablename = trim($tablename);

        return $tablename;
    }

    /**
     * Gets the tracking status of a table, is it active or disabled ?
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     *
     * @return bool true or false
     *
     * @static
     */
    public static function isTracked($dbname, $tablename)
    {
        global $dbi;

        if (! self::$enabled) {
            return false;
        }

        if (isset(self::$trackingCache[$dbname][$tablename])) {
            return self::$trackingCache[$dbname][$tablename];
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

        $sql_query = ' SELECT tracking_active FROM ' . self::getTrackingTable() .
        " WHERE db_name = '" . $dbi->escapeString($dbname) . "' " .
        " AND table_name = '" . $dbi->escapeString($tablename) . "' " .
        ' ORDER BY version DESC LIMIT 1';

        $result = $dbi->fetchValue($sql_query, 0, 0, DatabaseInterface::CONNECT_CONTROL) == 1;

        self::$trackingCache[$dbname][$tablename] = $result;

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
     * @param string $dbname       name of database
     * @param string $tablename    name of table
     * @param string $version      version
     * @param string $tracking_set set of tracking statements
     * @param bool   $is_view      if table is a view
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function createVersion(
        $dbname,
        $tablename,
        $version,
        $tracking_set = '',
        bool $is_view = false
    ) {
        global $sql_backquotes, $export_type, $dbi;

        $relation = new Relation($dbi);

        if ($tracking_set == '') {
            $tracking_set
                = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        /**
         * get Export SQL instance
         *
         * @var ExportSql $export_sql_plugin
         */
        $export_sql_plugin = Plugins::getPlugin(
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

        $columns = $dbi->getColumns($dbname, $tablename, null, true);
        // int indices to reduce size
        $columns = array_values($columns);
        // remove Privileges to reduce size
        for ($i = 0, $nb = count($columns); $i < $nb; $i++) {
            unset($columns[$i]['Privileges']);
        }

        $indexes = $dbi->getTableIndexes($dbname, $tablename);

        $snapshot = [
            'COLUMNS' => $columns,
            'INDEXES' => $indexes,
        ];
        $snapshot = serialize($snapshot);

        // Get DROP TABLE / DROP VIEW and CREATE TABLE SQL statements
        $sql_backquotes = true;

        $create_sql  = '';

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_table'] == true
            && $is_view === false
        ) {
            $create_sql .= self::getLogComment()
                . 'DROP TABLE IF EXISTS ' . Util::backquote($tablename) . ";\n";
        }

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_view'] == true
            && $is_view === true
        ) {
            $create_sql .= self::getLogComment()
                . 'DROP VIEW IF EXISTS ' . Util::backquote($tablename) . ";\n";
        }

        $create_sql .= self::getLogComment() .
            $export_sql_plugin->getTableDef($dbname, $tablename, "\n", '');

        // Save version

        $sql_query = "/*NOTRACK*/\n" .
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
        '" . $dbi->escapeString($dbname) . "',
        '" . $dbi->escapeString($tablename) . "',
        '" . $dbi->escapeString($version) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($snapshot) . "',
        '" . $dbi->escapeString($create_sql) . "',
        '" . $dbi->escapeString("\n") . "',
        '" . $dbi->escapeString($tracking_set)
        . "' )";

        $result = $relation->queryAsControlUser($sql_query);

        if ($result) {
            // Deactivate previous version
            self::deactivateTracking($dbname, $tablename, (int) $version - 1);
        }

        return $result;
    }

    /**
     * Removes all tracking data for a table or a version of a table
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function deleteTracking($dbname, $tablename, $version = '')
    {
        global $dbi;

        $relation = new Relation($dbi);

        $sql_query = "/*NOTRACK*/\n"
            . 'DELETE FROM ' . self::getTrackingTable()
            . " WHERE `db_name` = '"
            . $dbi->escapeString($dbname) . "'"
            . " AND `table_name` = '"
            . $dbi->escapeString($tablename) . "'";
        if ($version) {
            $sql_query .= " AND `version` = '"
                . $dbi->escapeString($version) . "'";
        }

        return $relation->queryAsControlUser($sql_query);
    }

    /**
     * Creates tracking version of a database
     * (in other words: create a job to track future changes on the database).
     *
     * @param string $dbname       name of database
     * @param string $version      version
     * @param string $query        query
     * @param string $tracking_set set of tracking statements
     *
     * @return int result of version insertion
     *
     * @static
     */
    public static function createDatabaseVersion(
        $dbname,
        $version,
        $query,
        $tracking_set = 'CREATE DATABASE,ALTER DATABASE,DROP DATABASE'
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        $date = Util::date('Y-m-d H:i:s');

        if ($tracking_set == '') {
            $tracking_set
                = $GLOBALS['cfg']['Server']['tracking_default_statements'];
        }

        $create_sql  = '';

        if ($GLOBALS['cfg']['Server']['tracking_add_drop_database'] == true) {
            $create_sql .= self::getLogComment()
                . 'DROP DATABASE IF EXISTS ' . Util::backquote($dbname) . ";\n";
        }

        $create_sql .= self::getLogComment() . $query;

        // Save version
        $sql_query = "/*NOTRACK*/\n" .
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
        '" . $dbi->escapeString($dbname) . "',
        '" . $dbi->escapeString('') . "',
        '" . $dbi->escapeString($version) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString($date) . "',
        '" . $dbi->escapeString('') . "',
        '" . $dbi->escapeString($create_sql) . "',
        '" . $dbi->escapeString("\n") . "',
        '" . $dbi->escapeString($tracking_set)
        . "' )";

        return $relation->queryAsControlUser($sql_query);
    }

    /**
     * Changes tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     * @param int    $new_state the new state of tracking
     *
     * @return int result of SQL query
     *
     * @static
     */
    private static function changeTracking(
        $dbname,
        $tablename,
        $version,
        $new_state
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        $sql_query = ' UPDATE ' . self::getTrackingTable() .
        " SET `tracking_active` = '" . $new_state . "' " .
        " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tablename) . "' " .
        " AND `version` = '" . $dbi->escapeString((string) $version) . "' ";

        return $relation->queryAsControlUser($sql_query);
    }

    /**
     * Changes tracking data of a table.
     *
     * @param string       $dbname    name of database
     * @param string       $tablename name of table
     * @param string       $version   version
     * @param string       $type      type of data(DDL || DML)
     * @param string|array $new_data  the new tracking data
     *
     * @return bool result of change
     *
     * @static
     */
    public static function changeTrackingData(
        $dbname,
        $tablename,
        $version,
        $type,
        $new_data
    ) {
        global $dbi;

        $relation = new Relation($dbi);

        if ($type === 'DDL') {
            $save_to = 'schema_sql';
        } elseif ($type === 'DML') {
            $save_to = 'data_sql';
        } else {
            return false;
        }
        $date  = Util::date('Y-m-d H:i:s');

        $new_data_processed = '';
        if (is_array($new_data)) {
            foreach ($new_data as $data) {
                $new_data_processed .= '# log ' . $date . ' ' . $data['username']
                    . $dbi->escapeString($data['statement']) . "\n";
            }
        } else {
            $new_data_processed = $new_data;
        }

        $sql_query = ' UPDATE ' . self::getTrackingTable() .
        ' SET `' . $save_to . "` = '" . $new_data_processed . "' " .
        " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tablename) . "' " .
        " AND `version` = '" . $dbi->escapeString($version) . "' ";

        $result = $relation->queryAsControlUser($sql_query);

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
        global $dbi;

        $relation = new Relation($dbi);

        $sql_query = ' SELECT MAX(version) FROM ' . self::getTrackingTable() .
        " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' " .
        " AND `table_name` = '" . $dbi->escapeString($tablename) . "' ";

        if ($statement != '') {
            $sql_query .= " AND FIND_IN_SET('"
                . $statement . "',tracking) > 0";
        }
        $row = $dbi->fetchArray($relation->queryAsControlUser($sql_query));

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

        $sql_query = ' SELECT * FROM ' . self::getTrackingTable() .
            " WHERE `db_name` = '" . $dbi->escapeString($dbname) . "' ";
        if (! empty($tablename)) {
            $sql_query .= " AND `table_name` = '"
                . $dbi->escapeString($tablename) . "' ";
        }
        $sql_query .= " AND `version` = '" . $dbi->escapeString($version)
            . "' ORDER BY `version` DESC LIMIT 1";

        $mixed = $dbi->fetchAssoc($relation->queryAsControlUser($sql_query));

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
        $log_schema_entries = explode('# log ', (string) $mixed['schema_sql']);
        $log_data_entries   = explode('# log ', (string) $mixed['data_sql']);

        $ddl_date_from = $date = Util::date('Y-m-d H:i:s');

        $ddlog = [];
        $first_iteration = true;

        // Iterate tracked data definition statements
        // For each log entry we want to get date, username and statement
        foreach ($log_schema_entries as $log_entry) {
            if (trim($log_entry) == '') {
                continue;
            }

            $date      = mb_substr($log_entry, 0, 19);
            $username  = mb_substr(
                $log_entry,
                20,
                mb_strpos($log_entry, "\n") - 20
            );
            if ($first_iteration) {
                $ddl_date_from = $date;
                $first_iteration = false;
            }
            $statement = rtrim((string) mb_strstr($log_entry, "\n"));

            $ddlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $date_from = $ddl_date_from;
        $ddl_date_to = $date;

        $dml_date_from = $date_from;

        $dmlog = [];
        $first_iteration = true;

        // Iterate tracked data manipulation statements
        // For each log entry we want to get date, username and statement
        foreach ($log_data_entries as $log_entry) {
            if (trim($log_entry) == '') {
                continue;
            }

            $date      = mb_substr($log_entry, 0, 19);
            $username  = mb_substr(
                $log_entry,
                20,
                mb_strpos($log_entry, "\n") - 20
            );
            if ($first_iteration) {
                $dml_date_from = $date;
                $first_iteration = false;
            }
            $statement = rtrim((string) mb_strstr($log_entry, "\n"));

            $dmlog[] = [
                'date' => $date,
                'username' => $username,
                'statement' => $statement,
            ];
        }

        $dml_date_to = $date;

        // Define begin and end of date range for both logs
        $data = [];
        if (strtotime($ddl_date_from) <= strtotime($dml_date_from)) {
            $data['date_from'] = $ddl_date_from;
        } else {
            $data['date_from'] = $dml_date_from;
        }
        if (strtotime($ddl_date_to) >= strtotime($dml_date_to)) {
            $data['date_to'] = $ddl_date_to;
        } else {
            $data['date_to'] = $dml_date_to;
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
                } elseif ($options[6] === 'INDEX'
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
        if ($GLOBALS['cfg']['Server']['tracking_version_auto_create'] == true
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
            $save_to = 'schema_sql';
        } elseif ($result['type'] === 'DML') {
            $save_to = 'data_sql';
        } else {
            $save_to = '';
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
        $sql_query = " /*NOTRACK*/\n"
            . ' UPDATE ' . self::getTrackingTable()
            . ' SET ' . Util::backquote($save_to)
            . ' = CONCAT( ' . Util::backquote($save_to) . ",'\n"
            . $dbi->escapeString($query) . "') ,"
            . " `date_updated` = '" . $date . "' ";

        // If table was renamed we have to change
        // the tablename attribute in pma_tracking too
        if ($result['identifier'] === 'RENAME TABLE') {
            $sql_query .= ', `table_name` = \''
                . $dbi->escapeString($result['tablename_after_rename'])
                . '\' ';
        }

        // Save the tracking information only for
        //     1. the database
        //     2. the table / view
        //     3. the statements
        // we want to track
        $sql_query .=
        " WHERE FIND_IN_SET('" . $result['identifier'] . "',tracking) > 0" .
        " AND `db_name` = '" . $dbi->escapeString($dbname ?? '') . "' " .
        " AND `table_name` = '"
        . $dbi->escapeString($result['tablename']) . "' " .
        " AND `version` = '" . $dbi->escapeString($version ?? '') . "' ";

        $relation->queryAsControlUser($sql_query);
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
