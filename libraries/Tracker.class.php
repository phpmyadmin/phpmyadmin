<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tracking changes on databases, tables and views
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This class tracks changes on databases, tables and views.
 *
 * @package PhpMyAdmin
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
     * Flags copied from `tracking` column definition in `pma_tracking` table.
     * Used for column type conversion in Drizzle.
     *
     * @var array
     */
    static private $_tracking_set_flags = array(
        'UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE',
        'ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE',
        'RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX',
        'CREATE VIEW','ALTER VIEW','DROP VIEW'
    );


    /**
     * Initializes settings.
     *
     * @static
     *
     * @return void
     */
    static protected function init()
    {
        self::$pma_table = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) .".".
                           PMA_Util::backquote($GLOBALS['cfg']['Server']['tracking']);

        self::$add_drop_table = $GLOBALS['cfg']['Server']['tracking_add_drop_table'];

        self::$add_drop_view = $GLOBALS['cfg']['Server']['tracking_add_drop_view'];

        self::$add_drop_database
            = $GLOBALS['cfg']['Server']['tracking_add_drop_database'];

        self::$default_tracking_set
            = $GLOBALS['cfg']['Server']['tracking_default_statements'];

        self::$version_auto_create
            = $GLOBALS['cfg']['Server']['tracking_version_auto_create'];
    }

    /**
     * Actually enables tracking. This needs to be done after all
     * underlaying code is initialized.
     *
     * @static
     *
     * @return void
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
        /* We need to avoid attempt to track any queries
         * from PMA_getRelationsParam
         */
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
     * Parses the name of a table from a SQL statement substring.
     *
     * @param string $string part of SQL statement
     *
     * @static
     *
     * @return string the name of table
     */
    static protected function getTableName($string)
    {
        if (strstr($string, '.')) {
            $temp = explode('.', $string);
            $tablename = $temp[1];
        } else {
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
     * @param string $dbname    name of database
     * @param string $tablename name of table
     *
     * @static
     *
     * @return boolean true or false
     */
    static public function isTracked($dbname, $tablename)
    {
        if (! self::$enabled) {
            return false;
        }
        /* We need to avoid attempt to track any queries
         * from PMA_getRelationsParam
         */
        self::$enabled = false;
        $cfgRelation = PMA_getRelationsParam();
        /* Restore original state */
        self::$enabled = true;
        if (! $cfgRelation['trackingwork']) {
            return false;
        }

        $sql_query = " SELECT tracking_active FROM " . self::$pma_table .
        " WHERE db_name = '" . PMA_Util::sqlAddSlashes($dbname) . "' " .
        " AND table_name = '" . PMA_Util::sqlAddSlashes($tablename) . "' " .
        " ORDER BY version DESC";

        $row = PMA_DBI_fetch_array(PMA_queryAsControlUser($sql_query));

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
        $user = preg_replace('/\s+/', ' ', $GLOBALS['cfg']['Server']['user']);

        return "# log " . $date . " " . $user . "\n";
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
     * @static
     *
     * @return int result of version insertion
     */
    static public function createVersion($dbname, $tablename, $version,
        $tracking_set = '', $is_view = false
    ) {
        global $sql_backquotes, $export_type;

        if ($tracking_set == '') {
            $tracking_set = self::$default_tracking_set;
        }

        // get Export SQL instance
        include_once "libraries/plugin_interface.lib.php";
        $export_sql_plugin = PMA_getPlugin(
            "export",
            "sql",
            'libraries/plugins/export/',
            array(
                'export_type' => $export_type,
                'single_table' => isset($single_table)
            )
        );

        $sql_backquotes = true;

        $date = date('Y-m-d H:i:s');

        // Get data definition snapshot of table

        $columns = PMA_DBI_get_columns($dbname, $tablename, null, true);
        // int indices to reduce size
        $columns = array_values($columns);
        // remove Privileges to reduce size
        for ($i = 0; $i < count($columns); $i++) {
            unset($columns[$i]['Privileges']);
        }

        $indexes = PMA_DBI_get_table_indexes($dbname, $tablename);

        $snapshot = array('COLUMNS' => $columns, 'INDEXES' => $indexes);
        $snapshot = serialize($snapshot);

        // Get DROP TABLE / DROP VIEW and CREATE TABLE SQL statements
        $sql_backquotes = true;

        $create_sql  = "";

        if (self::$add_drop_table == true && $is_view == false) {
            $create_sql .= self::getLogComment()
                . 'DROP TABLE IF EXISTS ' . PMA_Util::backquote($tablename) . ";\n";

        }

        if (self::$add_drop_view == true && $is_view == true) {
            $create_sql .= self::getLogComment()
                . 'DROP VIEW IF EXISTS ' . PMA_Util::backquote($tablename) . ";\n";
        }

        $create_sql .= self::getLogComment() .
            $export_sql_plugin->getTableDef($dbname, $tablename, "\n", "");

        // Save version

        $sql_query = "/*NOTRACK*/\n" .
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
        '" . PMA_Util::sqlAddSlashes($dbname) . "',
        '" . PMA_Util::sqlAddSlashes($tablename) . "',
        '" . PMA_Util::sqlAddSlashes($version) . "',
        '" . PMA_Util::sqlAddSlashes($date) . "',
        '" . PMA_Util::sqlAddSlashes($date) . "',
        '" . PMA_Util::sqlAddSlashes($snapshot) . "',
        '" . PMA_Util::sqlAddSlashes($create_sql) . "',
        '" . PMA_Util::sqlAddSlashes("\n") . "',
        '" . PMA_Util::sqlAddSlashes(self::_transformTrackingSet($tracking_set)) . "' )";

        $result = PMA_queryAsControlUser($sql_query);

        if ($result) {
            // Deactivate previous version
            self::deactivateTracking($dbname, $tablename, ($version - 1));
        }

        return $result;
    }


    /**
     * Removes all tracking data for a table
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     *
     * @static
     *
     * @return int result of version insertion
     */
    static public function deleteTracking($dbname, $tablename)
    {
        $sql_query = "/*NOTRACK*/\n"
            . "DELETE FROM " . self::$pma_table
            . " WHERE `db_name` = '"
            . PMA_Util::sqlAddSlashes($dbname) . "'"
            . " AND `table_name` = '"
            . PMA_Util::sqlAddSlashes($tablename) . "'";
        $result = PMA_queryAsControlUser($sql_query);

        return $result;
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
     * @static
     *
     * @return int result of version insertion
     */
    static public function createDatabaseVersion($dbname, $version, $query,
        $tracking_set = 'CREATE DATABASE,ALTER DATABASE,DROP DATABASE'
    ) {
        $date = date('Y-m-d H:i:s');

        if ($tracking_set == '') {
            $tracking_set = self::$default_tracking_set;
        }

        include_once './libraries/export/sql.php';

        $create_sql  = "";

        if (self::$add_drop_database == true) {
            $create_sql .= self::getLogComment()
                . 'DROP DATABASE IF EXISTS ' . PMA_Util::backquote($dbname) . ";\n";
        }

        $create_sql .= self::getLogComment() . $query;

        // Save version
        $sql_query = "/*NOTRACK*/\n" .
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
        '" . PMA_Util::sqlAddSlashes($dbname) . "',
        '" . PMA_Util::sqlAddSlashes('') . "',
        '" . PMA_Util::sqlAddSlashes($version) . "',
        '" . PMA_Util::sqlAddSlashes($date) . "',
        '" . PMA_Util::sqlAddSlashes($date) . "',
        '" . PMA_Util::sqlAddSlashes('') . "',
        '" . PMA_Util::sqlAddSlashes($create_sql) . "',
        '" . PMA_Util::sqlAddSlashes("\n") . "',
        '" . PMA_Util::sqlAddSlashes(self::_transformTrackingSet($tracking_set)) . "' )";

        $result = PMA_queryAsControlUser($sql_query);

        return $result;
    }



    /**
     * Changes tracking of a table.
     *
     * @param string  $dbname    name of database
     * @param string  $tablename name of table
     * @param string  $version   version
     * @param integer $new_state the new state of tracking
     *
     * @static
     *
     * @return int result of SQL query
     */
    static private function _changeTracking($dbname, $tablename,
        $version, $new_state
    ) {

        $sql_query = " UPDATE " . self::$pma_table .
        " SET `tracking_active` = '" . $new_state . "' " .
        " WHERE `db_name` = '" . PMA_Util::sqlAddSlashes($dbname) . "' " .
        " AND `table_name` = '" . PMA_Util::sqlAddSlashes($tablename) . "' " .
        " AND `version` = '" . PMA_Util::sqlAddSlashes($version) . "' ";

        $result = PMA_queryAsControlUser($sql_query);

        return $result;
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
     * @static
     *
     * @return bool result of change
     */
    static public function changeTrackingData($dbname, $tablename,
        $version, $type, $new_data
    ) {
        if ($type == 'DDL') {
            $save_to = 'schema_sql';
        } elseif ($type == 'DML') {
            $save_to = 'data_sql';
        } else {
            return false;
        }
        $date  = date('Y-m-d H:i:s');

        $new_data_processed = '';
        if (is_array($new_data)) {
            foreach ($new_data as $data) {
                $new_data_processed .= '# log ' . $date . ' ' . $data['username']
                    . PMA_Util::sqlAddSlashes($data['statement']) . "\n";
            }
        } else {
            $new_data_processed = $new_data;
        }

        $sql_query = " UPDATE " . self::$pma_table .
        " SET `" . $save_to . "` = '" . $new_data_processed . "' " .
        " WHERE `db_name` = '" . PMA_Util::sqlAddSlashes($dbname) . "' " .
        " AND `table_name` = '" . PMA_Util::sqlAddSlashes($tablename) . "' " .
        " AND `version` = '" . PMA_Util::sqlAddSlashes($version) . "' ";

        $result = PMA_queryAsControlUser($sql_query);

        return $result;
    }

    /**
     * Activates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     *
     * @static
     *
     * @return int result of SQL query
     */
    static public function activateTracking($dbname, $tablename, $version)
    {
        return self::_changeTracking($dbname, $tablename, $version, 1);
    }


    /**
     * Deactivates tracking of a table.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version
     *
     * @static
     *
     * @return int result of SQL query
     */
    static public function deactivateTracking($dbname, $tablename, $version)
    {
        return self::_changeTracking($dbname, $tablename, $version, 0);
    }


    /**
     * Gets the newest version of a tracking job
     * (in other words: gets the HEAD version).
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $statement tracked statement
     *
     * @static
     *
     * @return int (-1 if no version exists | >  0 if a version exists)
     */
    static public function getVersion($dbname, $tablename, $statement = null)
    {
        $sql_query = " SELECT MAX(version) FROM " . self::$pma_table .
        " WHERE `db_name` = '" . PMA_Util::sqlAddSlashes($dbname) . "' " .
        " AND `table_name` = '" . PMA_Util::sqlAddSlashes($tablename) . "' ";

        if ($statement != "") {
            $sql_query .= PMA_DRIZZLE
                ? ' AND tracking & ' . self::_transformTrackingSet($statement) . ' <> 0'
                : " AND FIND_IN_SET('" . $statement . "',tracking) > 0" ;
        }
        $row = PMA_DBI_fetch_array(PMA_queryAsControlUser($sql_query));
        return isset($row[0])
            ? $row[0]
            : -1;
    }


    /**
     * Gets the record of a tracking job.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version number
     *
     * @static
     *
     * @return mixed record DDM log, DDL log, structure snapshot, tracked statements.
     */
    static public function getTrackedData($dbname, $tablename, $version)
    {
        if (! isset(self::$pma_table)) {
            self::init();
        }
        $sql_query = " SELECT * FROM " . self::$pma_table .
            " WHERE `db_name` = '" . PMA_Util::sqlAddSlashes($dbname) . "' ";
        if (! empty($tablename)) {
            $sql_query .= " AND `table_name` = '"
                . PMA_Util::sqlAddSlashes($tablename) ."' ";
        }
        $sql_query .= " AND `version` = '" . PMA_Util::sqlAddSlashes($version) ."' ".
                     " ORDER BY `version` DESC LIMIT 1";

        $mixed = PMA_DBI_fetch_assoc(PMA_queryAsControlUser($sql_query));

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
        $data['tracking']        = self::_transformTrackingSet($mixed['tracking']);
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
     * @static
     * @todo: using PMA SQL Parser when possible
     * @todo: support multi-table/view drops
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
        foreach ($tokens as $key => $value) {
            $tokens[$key] = strtoupper($value);
        }

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
        if (in_array('CREATE', $tokens) == true
            && in_array('VIEW', $tokens) == true
            && in_array('AS', $tokens) == true
        ) {
            $result['identifier'] = 'CREATE VIEW';

            $index = array_search('VIEW', $tokens);

            $result['tablename'] = strtolower(
                self::getTableName($tokens[$index + 1])
            );
        }

        // Parse ALTER VIEW statement
        if (in_array('ALTER', $tokens) == true
            && in_array('VIEW', $tokens) == true
            && in_array('AS', $tokens) == true
            && ! isset($result['identifier'])
        ) {
            $result['identifier'] = 'ALTER VIEW';

            $index = array_search('VIEW', $tokens);

            $result['tablename'] = strtolower(
                self::getTableName($tokens[$index + 1])
            );
        }

        // Parse DROP VIEW statement
        if (! isset($result['identifier'])
            && substr($query, 0, 10) == 'DROP VIEW '
        ) {
            $result['identifier'] = 'DROP VIEW';

            $prefix  = explode('DROP VIEW ', $query);
            $str = strstr($prefix[1], 'IF EXISTS');

            if ($str == false ) {
                $str = $prefix[1];
            }
            $result['tablename'] = self::getTableName($str);
        }

        // Parse CREATE DATABASE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 15) == 'CREATE DATABASE'
        ) {
            $result['identifier'] = 'CREATE DATABASE';
            $str = str_replace('CREATE DATABASE', '', $query);
            $str = str_replace('IF NOT EXISTS', '', $str);

            $prefix = explode('DEFAULT ', $str);

            $result['tablename'] = '';
            $GLOBALS['db'] = self::getTableName($prefix[0]);
        }

        // Parse ALTER DATABASE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 14) == 'ALTER DATABASE'
        ) {
            $result['identifier'] = 'ALTER DATABASE';
            $result['tablename'] = '';
        }

        // Parse DROP DATABASE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 13) == 'DROP DATABASE'
        ) {
            $result['identifier'] = 'DROP DATABASE';
            $str = str_replace('DROP DATABASE', '', $query);
            $str = str_replace('IF EXISTS', '', $str);
            $GLOBALS['db'] = self::getTableName($str);
            $result['tablename'] = '';
        }

        // Parse CREATE TABLE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 12) == 'CREATE TABLE'
        ) {
            $result['identifier'] = 'CREATE TABLE';
            $query   = str_replace('IF NOT EXISTS', '', $query);
            $prefix  = explode('CREATE TABLE ', $query);
            $suffix  = explode('(', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse ALTER TABLE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 12) == 'ALTER TABLE '
        ) {
            $result['identifier'] = 'ALTER TABLE';

            $prefix  = explode('ALTER TABLE ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename']  = self::getTableName($suffix[0]);
        }

        // Parse DROP TABLE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 11) == 'DROP TABLE '
        ) {
            $result['identifier'] = 'DROP TABLE';

            $prefix  = explode('DROP TABLE ', $query);
            $str = strstr($prefix[1], 'IF EXISTS');

            if ($str == false ) {
                $str = $prefix[1];
            }
            $result['tablename'] = self::getTableName($str);
        }

        // Parse CREATE INDEX statement
        if (! isset($result['identifier'])
            && (substr($query, 0, 12) == 'CREATE INDEX'
            || substr($query, 0, 19) == 'CREATE UNIQUE INDEX'
            || substr($query, 0, 20) == 'CREATE SPATIAL INDEX')
        ) {
             $result['identifier'] = 'CREATE INDEX';
             $prefix = explode('ON ', $query);
             $suffix = explode('(', $prefix[1]);
             $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse DROP INDEX statement
        if (! isset($result['identifier'])
            && substr($query, 0, 10) == 'DROP INDEX'
        ) {
             $result['identifier'] = 'DROP INDEX';
             $prefix = explode('ON ', $query);
             $result['tablename'] = self::getTableName($prefix[1]);
        }

        // Parse RENAME TABLE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 13) == 'RENAME TABLE '
        ) {
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
        if (! isset($result['identifier'])
            && substr($query, 0, 6) == 'UPDATE'
        ) {
            $result['identifier'] = 'UPDATE';
            $prefix  = explode('UPDATE ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse INSERT INTO statement
        if (! isset($result['identifier'])
            && substr($query, 0, 11) == 'INSERT INTO'
        ) {
            $result['identifier'] = 'INSERT';
            $prefix  = explode('INSERT INTO', $query);
            $suffix  = explode('(', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse DELETE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 6) == 'DELETE'
        ) {
            $result['identifier'] = 'DELETE';
            $prefix  = explode('FROM ', $query);
            $suffix  = explode(' ', $prefix[1]);
            $result['tablename'] = self::getTableName($suffix[0]);
        }

        // Parse TRUNCATE statement
        if (! isset($result['identifier'])
            && substr($query, 0, 8) == 'TRUNCATE'
        ) {
            $result['identifier'] = 'TRUNCATE';
            $prefix  = explode('TRUNCATE', $query);
            $result['tablename'] = self::getTableName($prefix[1]);
        }

        return $result;
    }


    /**
     * Analyzes a given SQL statement and saves tracking data.
     *
     * @param string $query a SQL query
     *
     * @static
     *
     * @return void
     */
    static public function handleQuery($query)
    {
        // If query is marked as untouchable, leave
        if (strstr($query, "/*NOTRACK*/")) {
            return;
        }

        if (! (substr($query, -1) == ';')) {
            $query = $query . ";\n";
        }
        // Get some information about query
        $result = self::parseQuery($query);

        // Get database name
        $dbname = trim(isset($GLOBALS['db']) ? $GLOBALS['db'] : '', '`');
        // $dbname can be empty, for example when coming from Synchronize
        // and this is a query for the remote server
        if (empty($dbname)) {
            return;
        }
        // Remove null bytes (preg_replace() is vulnerable in some
        // PHP versions)
        $dbname = str_replace("\0", "", $dbname);

        // If we found a valid statement
        if (isset($result['identifier'])) {
            $version = self::getVersion(
                $dbname, $result['tablename'], $result['identifier']
            );

            // If version not exists and auto-creation is enabled
            if (self::$version_auto_create == true
                && self::isTracked($dbname, $result['tablename']) == false
                && $version == -1
            ) {
                // Create the version

                switch ($result['identifier']) {
                case 'CREATE TABLE':
                    self::createVersion($dbname, $result['tablename'], '1');
                    break;
                case 'CREATE VIEW':
                    self::createVersion(
                        $dbname, $result['tablename'], '1', '', true
                    );
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
                $sql_query = " /*NOTRACK*/\n"
                    . " UPDATE " . self::$pma_table
                    . " SET " . PMA_Util::backquote($save_to)
                    . " = CONCAT( " . PMA_Util::backquote($save_to) . ",'\n"
                    . PMA_Util::sqlAddSlashes($query) . "') ,"
                    . " `date_updated` = '" . $date . "' ";

                // If table was renamed we have to change
                // the tablename attribute in pma_tracking too
                if ($result['identifier'] == 'RENAME TABLE') {
                    $sql_query .= ', `table_name` = \''
                        . PMA_Util::sqlAddSlashes($result['tablename_after_rename'])
                        . '\' ';
                }

                // Save the tracking information only for
                //     1. the database
                //     2. the table / view
                //     3. the statements
                // we want to track
                $sql_query .=
                " WHERE FIND_IN_SET('" . $result['identifier'] . "',tracking) > 0" .
                " AND `db_name` = '" . PMA_Util::sqlAddSlashes($dbname) . "' " .
                " AND `table_name` = '" . PMA_Util::sqlAddSlashes($result['tablename']) . "' " .
                " AND `version` = '" . PMA_Util::sqlAddSlashes($version) . "' ";

                $result = PMA_queryAsControlUser($sql_query);
            }
        }
    }

    /**
     * Transforms tracking set for Drizzle, which has no SET type
     *
     * Converts int<>string for Drizzle, does nothing for MySQL
     *
     * @param int|string $tracking_set
     *
     * @return int|string
     */
    static private function _transformTrackingSet($tracking_set)
    {
        if (!PMA_DRIZZLE) {
            return $tracking_set;
        }

        // init conversion array (key 3 doesn't exist in calculated array)
        if (isset(self::$_tracking_set_flags[3])) {
            // initialize flags
            $set = self::$_tracking_set_flags;
            $array = array();
            for ($i = 0; $i < count($set); $i++) {
                $flag = 1 << $i;
                $array[$flag] = $set[$i];
                $array[$set[$i]] = $flag;
            }
            self::$_tracking_set_flags = $array;
        }

        if (is_numeric($tracking_set)) {
            // int > string conversion
            $aflags = array();
            // count/2 - conversion table has both int > string
            // and string > int values
            for ($i = 0; $i < count(self::$_tracking_set_flags)/2; $i++) {
                $flag = 1 << $i;
                if ($tracking_set & $flag) {
                    $aflags[] = self::$_tracking_set_flags[$flag];
                }
            }
            $flags = implode(',', $aflags);
        } else {
            // string > int conversion
            $flags = 0;
            foreach (explode(',', $tracking_set) as $strflag) {
                if ($strflag == '') {
                    continue;
                }
                $flags |= self::$_tracking_set_flags[$strflag];
            }
        }

        return $flags;
    }
}
?>
