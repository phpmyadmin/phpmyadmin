<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @author Alexander Rutkowski
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets relation settings
 */
require_once './libraries/relation.lib.php';

/**
 * This class tracks changes on databases, tables and views.
 * For more information please see phpMyAdmin/Documentation.html
 *
 * @author Alexander Rutkowski <alexander.rutkowski@googlemail.com>
 * @package phpMyAdmin
 *
 * @todo use stristr instead of strstr
 */
class PMA_Tracker
{
    /**
     * Whether tracking is ready.
     */
    static protected $enabled = false;

    /**
     * Defines the internal PMA table which contains tracking data.
     *
     * @access  protected
     * @var string
     */
    static protected $pma_table;

    /**
     * Defines the usage of DROP TABLE statment in SQL dumps.
     *
     * @access protected
     * @var boolean
     */
    static protected $add_drop_table;

    /**
     * Defines the usage of DROP VIEW statment in SQL dumps.
     *
     * @access protected
     * @var boolean
     */
    static protected $add_drop_view;

    /**
     * Defines the usage of DROP DATABASE statment in SQL dumps.
     *
     * @access protected
     * @var boolean
     */
    static protected $add_drop_database;

    /**
     * Defines auto-creation of tracking versions.
     *
     * @var boolean
     */
    static protected $version_auto_create;

    /**
     * Defines the default set of tracked statements.
     *
     * @var string
     */
    static protected $default_tracking_set;

    /**
     * Initializes settings. See phpMyAdmin/Documentation.html.
     *
     * @static
     *
     */
    static public function init()
    {
        self::$pma_table = PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) .".".
                           PMA_backquote($GLOBALS['cfg']['Server']['tracking']);

        self::$add_drop_table = $GLOBALS['cfg']['Server']['tracking_add_drop_table'];

        self::$add_drop_view = $GLOBALS['cfg']['Server']['tracking_add_drop_view'];

        self::$add_drop_database = $GLOBALS['cfg']['Server']['tracking_add_drop_database'];

        self::$default_tracking_set = $GLOBALS['cfg']['Server']['tracking_default_statements'];

        self::$version_auto_create = $GLOBALS['cfg']['Server']['tracking_version_auto_create'];

    }

    /**
     * Actually enables tracking. This needs to be done after all 
     * underlaying code is initialized.
     *
     * @static
     *
     */
    static public function enable()
    {
        self::$enabled = true;
    }

    /**
     * Gets the on/off value of the Tracker module, starts initialization.
     *
     * @static
     *
     * @return boolean (true=on|false=off)
     */
    static public function isActive()
    {
        if (! self::$enabled) {
            return false;
        }
        /* We need to avoid attempt to track any queries from PMA_getRelationsParam */
        self::$enabled = false;
        $cfgRelation = PMA_getRelationsParam();
        /* Restore original state */
        self::$enabled = true;
        if (! $cfgRelation['trackingwork']) {
            return false;
        }
        self::init();

        if (isset(self::$pma_table)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns a simple DROP TABLE statement.
     *
     * @param string $tablename
     * @return string
     */
    static public function getStatementDropTable($tablename)
    {
        return 'DROP TABLE IF EXISTS ' . $tablename;
    }

    /**
     * Returns a simple DROP VIEW statement.
     *
     * @param string $viewname
     * @return string
     */
    static public function getStatementDropView($viewname)
    {
        return 'DROP VIEW IF EXISTS ' . $viewname;
    }

    /**
     * Returns a simple DROP DATABASE statement.
     *
     * @param string $dbname
     * @return string
     */
    static public function getStatementDropDatabase($dbname)
    {
        return 'DROP DATABASE IF EXISTS ' . $dbname;
    }

    /**
     * Parses the name of a table from a SQL statement substring.
     *
     * @static
     *
     * @param  string $string      part of SQL statement
     *
     * @return string the name of table
     */
    static protected function getTableName($string)
    {
        if (strstr($string, '.')) {
            $temp = explode('.', $string);
            $tablename = $temp[1];
        }
        else {
            $tablename = $string;
        }

        $str = explode("\n", $tablename);
        $tablename = $str[0];

        $tablename = str_replace(';', '', $tablename);
        $tablename = str_replace('`', '', $tablename);
        $tablename = trim($tablename);

        return $tablename;
    }


    /**
     * Gets the tracking status of a table, is it active or deactive ?
     *
     * @static
     *
     * @param  string $dbname      name of database
     * @param  string $tablename   name of table
     *
     * @return boolean true or false
     */
    static public function isTracked($dbname, $tablename)
    {
        if (! self::$enabled) {
            return false;
        }
        /* We need to avoid attempt to track any queries from PMA_getRelationsParam */
        self::$enabled = false;
        $cfgRelation = PMA_getRelationsParam();
        /* Restore original state */
        self::$enabled = true;
        if (! $cfgRelation['trackingwork']) {
            return false;
        }

        $sql_query =
        " SELECT tracking_active FROM " . self::$pma_table .
        " WHERE " . PMA_backquote('db_name') . " = '" . PMA_sqlAddslashes($dbname) . "' " .
        " AND " . PMA_backquote('table_name') . " = '" . PMA_sqlAddslashes($tablename) . "' " .
        " ORDER BY version DESC";

        $row = PMA_DBI_fetch_array(PMA_query_as_controluser($sql_query));

        if (isset($row['tracking_active']) && $row['tracking_active'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the comment line for the log.
     *
     * @return string Comment, contains date and username
     */
    static public function getLogComment()
    {
        $date = date('Y-m-d H:i:s');

        return "# log " . $date . " " . $GLOBALS['cfg']['Server']['user'] . "\n";
    }

    /**
     * Creates tracking version of a table / view
     * (in other words: create a job to track future changes on the table).
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $tablename    name of table
     * @param  string $version      version
     * @param  string $tracking_set set of tracking statements
     * @param  string $is_view      if table is a view
     *
     * @return int result of version insertion
     */
    static public function createVersion($dbname, $tablename, $version, $tracking_set = '', $is_view = false)
    {
        global $sql_backquotes;

        if ($tracking_set == '') {
            $tracking_set = self::$default_tracking_set;
        }

        require_once './libraries/export/sql.php';

        $sql_backquotes = true;

        $date = date('Y-m-d H:i:s');

        // Get data definition snapshot of table
        $sql_query = '
        SHOW FULL COLUMNS FROM ' . PMA_backquote($dbname) . '.' . PMA_backquote($tablename);

        $sql_result = PMA_DBI_query($sql_query);

        while ($row = PMA_DBI_fetch_array($sql_result)) {
            $columns[] = $row;
        }

        $sql_query = '
        SHOW INDEX FROM ' . PMA_backquote($dbname) . '.' . PMA_backquote($tablename);

        $sql_result = PMA_DBI_query($sql_query);

        $indexes = array();

        while($row = PMA_DBI_fetch_array($sql_result)) {
            $indexes[] = $row;
        }

        $snapshot = array('COLUMNS' => $columns, 'INDEXES' => $indexes);
        $snapshot = serialize($snapshot);

        // Get DROP TABLE / DROP VIEW and CREATE TABLE SQL statements
        $sql_backquotes = true;

        $create_sql  = "";

        if (self::$add_drop_table == true && $is_view == false) {
            $create_sql .= self::getLogComment() .
                           self::getStatementDropTable(PMA_backquote($tablename)) . ";\n";

        }

        if (self::$add_drop_view == true && $is_view == true) {
            $create_sql .= self::getLogComment() .
                           self::getStatementDropView(PMA_backquote($tablename)) . ";\n";
        }

        $create_sql .= self::getLogComment() .
                       PMA_getTableDef($dbname, $tablename, "\n", "");

        // Save version

        $sql_query =
        "/*NOTRACK*/\n" .
        "INSERT INTO" . self::$pma_table . " (" .
        "db_name, " .
        "table_name, " .
        "version, " .
        "date_created, " .
        "date_updated, " .
        "schema_snapshot, " .
        "schema_sql, " .
        "data_sql, " .
        "tracking " .
        ") " .
        "values (
        '" . PMA_sqlAddslashes($dbname) . "',
        '" . PMA_sqlAddslashes($tablename) . "',
        '" . PMA_sqlAddslashes($version) . "',
        '" . PMA_sqlAddslashes($date) . "',
        '" . PMA_sqlAddslashes($date) . "',
        '" . PMA_sqlAddslashes($snapshot) . "',
        '" . PMA_sqlAddslashes($create_sql) . "',
        '" . PMA_sqlAddslashes("\n") . "',
        '" . PMA_sqlAddslashes($tracking_set) . "' )";

        $result = PMA_query_as_controluser($sql_query);

        if ($result) {
            // Deactivate previous version
            self::deactivateTracking($dbname, $tablename, ($version - 1));
        }

        return $result;
    }


    /**
     * Removes all tracking data for a table 
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $tablename    name of table 
     *
     * @return int result of version insertion
     */
    static public function deleteTracking($dbname, $tablename)
    {
        $sql_query =
        "/*NOTRACK*/\n" .
        "DELETE FROM " . self::$pma_table . " WHERE `db_name` = '" . PMA_sqlAddslashes($dbname) . "' AND `table_name` = '" . PMA_sqlAddslashes($tablename) . "'";
        $result = PMA_query_as_controluser($sql_query);

        return $result;
    }

    /**
     * Creates tracking version of a database
     * (in other words: create a job to track future changes on the database).
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $version      version
     * @param  string $query        query
     * @param  string $tracking_set set of tracking statements
     *
     * @return int result of version insertion
     */
    static public function createDatabaseVersion($dbname, $version, $query, $tracking_set = 'CREATE DATABASE,ALTER DATABASE,DROP DATABASE')
    {
        global $sql_backquotes;

        $date = date('Y-m-d H:i:s');

        if ($tracking_set == '') {
            $tracking_set = self::$default_tracking_set;
        }

        require_once './libraries/export/sql.php';

        $create_sql  = "";

        if (self::$add_drop_database == true) {
            $create_sql .= self::getLogComment() .
                           self::getStatementDropDatabase(PMA_backquote($dbname)) . ";\n";
        }

        $create_sql .= self::getLogComment() . $query;

        // Save version
        $sql_query =
        "/*NOTRACK*/\n" .
        "INSERT INTO" . self::$pma_table . " (" .
        "db_name, " .
        "table_name, " .
        "version, " .
        "date_created, " .
        "date_updated, " .
        "schema_snapshot, " .
        "schema_sql, " .
        "data_sql, " .
        "tracking " .
        ") " .
        "values (
        '" . PMA_sqlAddslashes($dbname) . "',
        '" . PMA_sqlAddslashes('') . "',
        '" . PMA_sqlAddslashes($version) . "',
        '" . PMA_sqlAddslashes($date) . "',
        '" . PMA_sqlAddslashes($date) . "',
        '" . PMA_sqlAddslashes('') . "',
        '" . PMA_sqlAddslashes($create_sql) . "',
        '" . PMA_sqlAddslashes("\n") . "',
        '" . PMA_sqlAddslashes($tracking_set) . "' )";

        $result = PMA_query_as_controluser($sql_query);

        return $result;
    }



    /**
     * Changes tracking of a table.
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $tablename    name of table
     * @param  string $version      version
     * @param  integer $new_state   the new state of tracking 
     *
     * @return int result of SQL query
     */
    static private function changeTracking($dbname, $tablename, $version, $new_state)
    {
        $sql_query =
        " UPDATE " . self::$pma_table .
        " SET `tracking_active` = '" . $new_state . "' " .
        " WHERE `db_name` = '" . PMA_sqlAddslashes($dbname) . "' " .
        " AND `table_name` = '" . PMA_sqlAddslashes($tablename) . "' " .
        " AND `version` = '" . PMA_sqlAddslashes($version) . "' ";

        $result = PMA_query_as_controluser($sql_query);

        return $result;
    }

    /**
     * Activates tracking of a table.
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $tablename    name of table
     * @param  string $version      version
     *
     * @return int result of SQL query
     */
    static public function activateTracking($dbname, $tablename, $version)
    {
        return self::changeTracking($dbname, $tablename, $version, 1); 
    }


    /**
     * Deactivates tracking of a table.
     *
     * @static
     *
     * @param  string $dbname       name of database
     * @param  string $tablename    name of table
     * @param  string $version      version
     *
     * @return int result of SQL query
     */
    static public function deactivateTracking($dbname, $tablename, $version)
    {
        return self::changeTracking($dbname, $tablename, $version, 0); 
    }


    /**
     * Gets the newest version of a tracking job
     * (in other words: gets the HEAD version).
     *
     * @static
     *
     * @param  string $dbname      name of database
     * @param  string $tablename   name of table
     * @param  string $statement   tracked statement
     *
     * @return int (-1 if no version exists | >  0 if a version exists)
     */
    static public function getVersion($dbname, $tablename, $statement = null)
    {
        $sql_query =
        " SELECT MAX(version) FROM " . self::$pma_table .
        " WHERE `db_name` = '" . PMA_sqlAddslashes($dbname) . "' " .
        " AND `table_name` = '" . PMA_sqlAddslashes($tablename) . "' ";

        if ($statement != "") {
            $sql_query .= " AND FIND_IN_SET('" . $statement . "',tracking) > 0" ;
        }
        $row = PMA_DBI_fetch_array(PMA_query_as_controluser($sql_query));
        if (isset($row[0])) {
            $version = $row[0];
        }
        if (! isset($version)) {
            $version = -1;
        }
        return $version;
    }


    /**
     * Gets the record of a tracking job.
     *
     * @static
     *
     * @param  string $dbname      name of database
     * @param  string $tablename   name of table
     * @param  string $version     version number
     *
     * @return mixed record DDM log, DDL log, structure snapshot, tracked statements.
     */
    static public function getTrackedData($dbname, $tablename, $version)
    {
        if (! isset(self::$pma_table)) {
            self::init();
        }
        $sql_query = " SELECT * FROM " . self::$pma_table .
            " WHERE `db_name` = '" . PMA_sqlAddslashes($dbname) . "' ";
        if (! empty($tablename)) {
            $sql_query .= " AND `table_name` = '" . PMA_sqlAddslashes($tablename) ."' ";
        }
        $sql_query .= " AND `version` = '" . PMA_sqlAddslashes($version) ."' ".
                     " ORDER BY `version` DESC ";

        $mixed = PMA_DBI_fetch_array(PMA_query_as_controluser($sql_query));

        // Parse log
        $log_schema_entries = explode('# log ',  $mixed['schema_sql']);
        $log_data_entries   = explode('# log ',  $mixed['data_sql']);

        $ddl_date_from = $date = date('Y-m-d H:i:s');

        $ddlog = array();
        $i = 0;

        // Iterate tracked data definition statements
        // For each log entry we want to get date, username and statement
        foreach ($log_schema_entries as $log_entry) {
            if (trim($log_entry) != '') {
                $date      = substr($log_entry, 0, 19);
                $username  = substr($log_entry, 20, strpos($log_entry, "\n") - 20);
                if ($i == 0) {
                    $ddl_date_from = $date;
                }
                $statement = rtrim(strstr($log_entry, "\n"));

                $ddlog[] = array( 'date' => $date,
                                  'username'=> $username,
                                  'statement' => $statement );
                $i++;
            }
        }

        $date_from = $ddl_date_from;
        $date_to   = $ddl_date_to = $date;

        $dml_date_from = $date_from;

        $dmlog = array();
        $i = 0;

        // Iterate tracked data manipulation statements
        // For each log entry we want to get date, username and statement
        foreach ($log_data_entries as $log_entry) {
            if (trim($log_entry) != '') {
                $date      = substr($log_entry, 0, 19);
                $username  = substr($log_entry, 20, strpos($log_entry, "\n") - 20);
                if ($i == 0) {
                    $dml_date_from = $date;
                }
                $statement = rtrim(strstr($log_entry, "\n"));

                $dmlog[] = array( 'date' => $date,
                                  'username' => $username,
                                  'statement' => $statement );
                $i++;
            }
        }

        $dml_date_to = $date;

        // Define begin and end of date range for both logs
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
     * @static
     * @todo: using PMA SQL Parser when possible
     * @todo: support multi-table/view drops
     *
     * @param string $query
     *
     * @return mixed Array containing identifier, type and tablename.
     *
     */
    static public function parseQuery($query)
    {

        // Usage of PMA_SQP does not work here
        //
        // require_once("libraries/sqlparser.lib.php");
        // $parsed_sql = PMA_SQP_parse($query);
        // $sql_info = PMA_SQP_analyze($parsed_sql);

        $query = str_replace("\n", " ", $query);
        $query = str_replace("\r", " ", $query);

        $query = trim($query);
        $query = trim($query, ' -');

        $tokens = explode(" ", $query);
        $tokens = array_map('strtoupper', $tokens);

        // Parse USE statement, need it for SQL dump imports
        if (substr($query, 0, 4) == 'USE ') {
            $prefix = explode('USE ', $query);
            $GLOBALS['db'] = self::getTableName($prefix[1]);
        }

        /*
         * DDL statements
         */

        $result['type']         = 'DDL';

        // Parse CREATE VIEW statement
        if (in_array('CREATE', $tokens) == true && 
           in_array('VIEW', $tokens) == true && 
           in_array('AS', $tokens) == true) {
            $result['identifier'] = 'CREATE VIEW';

            $index = array_search('VIEW', $tokens);

            $result['tablename'] = strtolower(self::getTableName($tokens[$index + 1]));
        }

        // Parse ALTER VIEW statement
        if (in_array('ALTER', $tokens) == true && 
           in_array('VIEW', $tokens) == true && 
           in_array('AS', $tokens) == true && 
           ! isset($result['identifier'])) {
            $result['identifier'] = 'ALTER VIEW';

            $index = array_search('VIEW', $tokens);

            $result['tablename'] = strtolower(self::getTableName($tokens[$index + 1]));
        }

        // Parse DROP VIEW statement
        if (! isset($result['identifier']) && substr($query, 0, 10) == 'DROP VIEW ') {
            $result['identifier'] = 'DROP VIEW';

            $prefix  = explode('DROP VIEW ', $query);
            $str = strstr($prefix[1], 'IF EXISTS');

            if ($str == FALSE ) {
                $str = $prefix[1];
            }
            $result['tablename'] = self::getTableName($str);
        }

        // Parse CREATE DATABASE statement
        if (! isset($result['identifier']) && substr($query, 0, 15) == 'CREATE DATABASE') {
            $result['identifier'] = 'CREATE DATABASE';
            $str = str_replace('CREATE DATABASE', '', $query);
            $str = str_replace('IF NOT EXISTS', '', $str);

            $prefix = explode('DEFAULT ', $str);

            $result['tablename'] = '';
            $GLOBALS['db'] = self::getTableName($prefix[0]);
        }

        // Parse ALTER DATABASE statement
        if (! isset($result['identifier']) && substr($query, 0, 14) == 'ALTER DATABASE') {
            $result['identifier'] = 'ALTER DATABASE';
            $result['tablename'] = '';
        }

        // Parse DROP DATABASE statement
        if (! isset($result['identifier']) && substr($query, 0, 13) == 'DROP DATABASE') {
            $result['identifier'] = 'DROP DATABASE';
            $str = str_replace('DROP DATABASE', '', $query);
            $str = str_replace('IF EXISTS', '', $str);
            $GLOBALS['db'] = self::getTableName($str);
            $result['tablename'] = '';
        }

        // Parse CREATE TABLE statement
        if (! isset($result['identifier']) && substr($query, 0, 12) == 'CREATE TABLE' ) {
            $result['identifier'] = 'CREATE TABLE';
            $query   = str_replace('IF NOT EXISTS', '', $query);
            $prefix  = explode('CREATE TABLE ', $query);
            $suffix  = explode('(', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse ALTER TABLE statement
        if (! isset($result['identifier']) && substr($query, 0, 12) == 'ALTER TABLE ') {
            $result['identifier'] = 'ALTER TABLE';

            $prefix  = explode('ALTER TABLE ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename']  = self::getTableName($suffix[0]);
        }

        // Parse DROP TABLE statement
        if (! isset($result['identifier']) && substr($query, 0, 11) == 'DROP TABLE ') {
            $result['identifier'] = 'DROP TABLE';

            $prefix  = explode('DROP TABLE ', $query);
            $str = strstr($prefix[1], 'IF EXISTS');

            if ($str == FALSE ) {
                $str = $prefix[1];
            }
            $result['tablename'] = self::getTableName($str);
        }

        // Parse CREATE INDEX statement
        if (! isset($result['identifier']) && 
            (   substr($query, 0, 12) == 'CREATE INDEX' || 
                substr($query, 0, 19) == 'CREATE UNIQUE INDEX' || 
                substr($query, 0, 20) == 'CREATE SPATIAL INDEX'
            )
        ) {
             $result['identifier'] = 'CREATE INDEX';
             $prefix = explode('ON ', $query);
             $suffix = explode('(', $prefix[1]);
             $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse DROP INDEX statement
        if (! isset($result['identifier']) && substr($query, 0, 10) == 'DROP INDEX') {
             $result['identifier'] = 'DROP INDEX';
             $prefix = explode('ON ', $query);
             $result['tablename'] = self::getTableName($prefix[1]);
        }

        // Parse RENAME TABLE statement
        if (! isset($result['identifier']) && substr($query, 0, 13) == 'RENAME TABLE ') {
            $result['identifier'] = 'RENAME TABLE';
            $prefix = explode('RENAME TABLE ', $query);
            $names  = explode(' TO ', $prefix[1]);
            $result['tablename']      = self::getTableName($names[0]);
            $result["tablename_after_rename"]  = self::getTableName($names[1]);
        }

        /*
         * DML statements
         */

        if (! isset($result['identifier'])) {
            $result["type"]       = 'DML';
        }
        // Parse UPDATE statement
        if (! isset($result['identifier']) && substr($query, 0, 6) == 'UPDATE') {
            $result['identifier'] = 'UPDATE';
            $prefix  = explode('UPDATE ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse INSERT INTO statement
        if (! isset($result['identifier']) && substr($query, 0, 11 ) == 'INSERT INTO') {
            $result['identifier'] = 'INSERT';
            $prefix  = explode('INSERT INTO', $query);
            $suffix  = explode('(', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse DELETE statement
        if (! isset($result['identifier']) && substr($query, 0, 6 ) == 'DELETE') {
            $result['identifier'] = 'DELETE';
            $prefix  = explode('FROM ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse TRUNCATE statement
        if (! isset($result['identifier']) && substr($query, 0, 8 ) == 'TRUNCATE') {
            $result['identifier'] = 'TRUNCATE';
            $prefix  = explode('TRUNCATE', $query);
            $result['tablename'] = self::getTableName($prefix[1]);
        }

        return $result;
    }


    /**
     * Analyzes a given SQL statement and saves tracking data.
     *
     *
     * @static
     * @param   string $query a SQL query
     */
    static public function handleQuery($query)
    {
        // If query is marked as untouchable, leave
        if (strstr($query, "/*NOTRACK*/")) {
            return false;
        }

        if (! (substr($query, -1) == ';')) {
            $query = $query . ";\n";
        }
        // Get some information about query
        $result = self::parseQuery($query);

        // Get database name
        $dbname = trim($GLOBALS['db'], '`');
        // $dbname can be empty, for example when coming from Synchronize
        // and this is a query for the remote server
        if (empty($dbname)) {
            return false;
        }

        // If we found a valid statement
        if (isset($result['identifier'])) {
            $version = self::getVersion($dbname, $result['tablename'], $result['identifier']);

            // If version not exists and auto-creation is enabled
            if (self::$version_auto_create == true
                && self::isTracked($dbname, $result['tablename']) == false 
                && $version == -1) {
                // Create the version

                switch ($result['identifier']) {
                case 'CREATE TABLE':
                    self::createVersion($dbname, $result['tablename'], '1');
                    break;
                case 'CREATE VIEW':
                    self::createVersion($dbname, $result['tablename'], '1', '', true);
                    break;
                case 'CREATE DATABASE':
                    self::createDatabaseVersion($dbname, '1', $query);
                    break;
                } // end switch
            }

            // If version exists
            if (self::isTracked($dbname, $result['tablename']) && $version != -1) {
                if ($result['type'] == 'DDL') {
                    $save_to = 'schema_sql';
                } elseif ($result['type'] == 'DML') {
                    $save_to = 'data_sql';
                } else {
                    $save_to = '';
                }
                $date  = date('Y-m-d H:i:s');

                // Cut off `dbname`. from query
                $query = preg_replace('/`' . $dbname . '`\s?\./', '', $query);

                // Add log information
                $query = self::getLogComment() . $query ;

                // Mark it as untouchable
                $sql_query =
                " /*NOTRACK*/\n" .
                " UPDATE " . self::$pma_table .
                " SET " . PMA_backquote($save_to) ." = CONCAT( " . PMA_backquote($save_to) . ",'\n" . PMA_sqlAddslashes($query) . "') ," .
                " `date_updated` = '" . $date . "' ";

                // If table was renamed we have to change the tablename attribute in pma_tracking too
                if ($result['identifier'] == 'RENAME TABLE') {
                    $sql_query .= ', `table_name` = \'' . PMA_sqlAddslashes($result['tablename_after_rename']) . '\' ';
                }

                // Save the tracking information only for
                //     1. the database
                //     2. the table / view
                //     3. the statements
                // we want to track
                $sql_query .=
                " WHERE FIND_IN_SET('" . $result['identifier'] . "',tracking) > 0" .
                " AND `db_name` = '" . PMA_sqlAddslashes($dbname) . "' " .
                " AND `table_name` = '" . PMA_sqlAddslashes($result['tablename']) . "' " .
                " AND `version` = '" . PMA_sqlAddslashes($version) . "' ";

                $result = PMA_query_as_controluser($sql_query);
            }
        }
    }
}
?>
