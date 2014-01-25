<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA_Table class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Handles everything related to tables
 *
 * @todo make use of PMA_Message and PMA_Error
 * @package PhpMyAdmin
 */
class PMA_Table
{
    /**
     * UI preferences properties
     */
    const PROP_SORTED_COLUMN = 'sorted_col';
    const PROP_COLUMN_ORDER = 'col_order';
    const PROP_COLUMN_VISIB = 'col_visib';

    static $cache = array();

    /**
     * @var string  table name
     */
    var $name = '';

    /**
     * @var string  database name
     */
    var $db_name = '';

    /**
     * @var string  engine (innodb, myisam, bdb, ...)
     */
    var $engine = '';

    /**
     * @var string  type (view, base table, system view)
     */
    var $type = '';

    /**
     * @var array   settings
     */
    var $settings = array();

    /**
     * @var array UI preferences
     */
    var $uiprefs;

    /**
     * @var array errors occurred
     */
    var $errors = array();

    /**
     * @var array messages
     */
    var $messages = array();

    /**
     * Constructor
     *
     * @param string $table_name table name
     * @param string $db_name    database name
     */
    function __construct($table_name, $db_name)
    {
        $this->setName($table_name);
        $this->setDbName($db_name);
    }

    /**
     * returns table name
     *
     * @see PMA_Table::getName()
     * @return string  table name
     */
    function __toString()
    {
        return $this->getName();
    }

    /**
     * return the last error
     *
     * @return string the last error
     */
    function getLastError()
    {
        return end($this->errors);
    }

    /**
     * return the last message
     *
     * @return string the last message
     */
    function getLastMessage()
    {
        return end($this->messages);
    }

    /**
     * sets table name
     *
     * @param string $table_name new table name
     *
     * @return void
     */
    function setName($table_name)
    {
        $this->name = $table_name;
    }

    /**
     * returns table name
     *
     * @param boolean $backquoted whether to quote name with backticks ``
     *
     * @return string  table name
     */
    function getName($backquoted = false)
    {
        if ($backquoted) {
            return PMA_Util::backquote($this->name);
        }
        return $this->name;
    }

    /**
     * sets database name for this table
     *
     * @param string $db_name database name
     *
     * @return void
     */
    function setDbName($db_name)
    {
        $this->db_name = $db_name;
    }

    /**
     * returns database name for this table
     *
     * @param boolean $backquoted whether to quote name with backticks ``
     *
     * @return string  database name for this table
     */
    function getDbName($backquoted = false)
    {
        if ($backquoted) {
            return PMA_Util::backquote($this->db_name);
        }
        return $this->db_name;
    }

    /**
     * returns full name for table, including database name
     *
     * @param boolean $backquoted whether to quote name with backticks ``
     *
     * @return string
     */
    function getFullName($backquoted = false)
    {
        return $this->getDbName($backquoted) . '.'
            . $this->getName($backquoted);
    }

    /**
     * returns whether the table is actually a view
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return boolean whether the given is a view
     */
    static public function isView($db = null, $table = null)
    {
        if (empty($db) || empty($table)) {
            return false;
        }

        // use cached data or load information with SHOW command
        if (isset(PMA_Table::$cache[$db][$table])
        ) {
            $type = PMA_Table::sGetStatusInfo($db, $table, 'TABLE_TYPE');
            return $type == 'VIEW';
        }

        // query information_schema
        $result = $GLOBALS['dbi']->fetchResult(
            "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($db) . "'
                AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($table) . "'"
        );
        return $result ? true : false;
    }

    /**
     * Returns whether the table is actually an updatable view
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return boolean whether the given is an updatable view
     */
    static public function isUpdatableView($db = null, $table = null)
    {
        if (empty($db) || empty($table)) {
            return false;
        }

        $result = $GLOBALS['dbi']->fetchResult(
            "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($db) . "'
                AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($table) . "'
                AND IS_UPDATABLE = 'YES'"
        );
        return $result ? true : false;
    }

    /**
     * Returns the analysis of 'SHOW CREATE TABLE' query for the table.
     * In case of a view, the values are taken from the information_schema.
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return array analysis of 'SHOW CREATE TABLE' query for the table
     */
    static public function analyzeStructure($db = null, $table = null)
    {
        if (empty($db) || empty($table)) {
            return false;
        }

        $analyzed_sql = array();
        if (self::isView($db, $table)) {
            // For a view, 'SHOW CREATE TABLE' returns the definition,
            // but the structure of the view. So, we try to mock
            // the result of analyzing 'SHOW CREATE TABLE' query.
            $analyzed_sql[0] = array();
            $analyzed_sql[0]['create_table_fields'] = array();

            $results = $GLOBALS['dbi']->fetchResult(
                "SELECT COLUMN_NAME, DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($db) . "'
                AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($table) . "'"
            );
            foreach ($results as $result) {
                $analyzed_sql[0]['create_table_fields'][$result['COLUMN_NAME']]
                    = array('type' => strtoupper($result['DATA_TYPE']));
            }
        } else {
            $show_create_table = $GLOBALS['dbi']->fetchValue(
                'SHOW CREATE TABLE '
                . PMA_Util::backquote($db)
                . '.' . PMA_Util::backquote($table),
                0,
                1
            );
            $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
        }
        return $analyzed_sql;
    }

    /**
     * sets given $value for given $param
     *
     * @param string $param name
     * @param mixed  $value value
     *
     * @return void
     */
    function set($param, $value)
    {
        $this->settings[$param] = $value;
    }

    /**
     * returns value for given setting/param
     *
     * @param string $param name for value to return
     *
     * @return mixed   value for $param
     */
    function get($param)
    {
        if (isset($this->settings[$param])) {
            return $this->settings[$param];
        }

        return null;
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias),
     * this is a merge table.
     *
     * @param string $db    the database name
     * @param string $table the table name
     *
     * @return boolean  true if it is a merge table
     */
    static public function isMerge($db = null, $table = null)
    {
        $engine = null;
        // if called static, with parameters
        if (! empty($db) && ! empty($table)) {
            $engine = PMA_Table::sGetStatusInfo(
                $db, $table, 'ENGINE', null, true
            );
        }

        // did we get engine?
        if (empty($engine)) {
            return false;
        }

        // any of known merge engines?
        return in_array(strtoupper($engine), array('MERGE', 'MRG_MYISAM'));
    }

    /**
     * Returns tooltip for the table
     * Format : <table_comment> (<number_of_rows>)
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string tooltip fot the table
     */
    static public function sGetToolTip($db, $table)
    {
        return PMA_Table::sGetStatusInfo($db, $table, 'Comment')
            . ' (' . PMA_Table::countRecords($db, $table)
            . ' ' . __('Rows') . ')';
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param string  $db            database name
     * @param string  $table         table name
     * @param string  $info          specific information to be fetched
     * @param boolean $force_read    read new rather than serving from cache
     * @param boolean $disable_error if true, disables error message
     *
     * @todo DatabaseInterface::getTablesFull needs to be merged
     * somehow into this class or at least better documented
     *
     * @return mixed
     */
    static public function sGetStatusInfo($db, $table, $info = null,
        $force_read = false, $disable_error = false
    ) {
        if (! empty($_SESSION['is_multi_query'])) {
            $disable_error = true;
        }

        if (! isset(PMA_Table::$cache[$db][$table])
            || $force_read
            // sometimes there is only one entry (ExactRows) so
            // we have to get the table's details
            || count(PMA_Table::$cache[$db][$table]) == 1
        ) {
            $GLOBALS['dbi']->getTablesFull($db, $table);
        }

        if (! isset(PMA_Table::$cache[$db][$table])) {
            // happens when we enter the table creation dialog
            // or when we really did not get any status info, for example
            // when $table == 'TABLE_NAMES' after the user tried SHOW TABLES
            return '';
        }

        if (null === $info) {
            return PMA_Table::$cache[$db][$table];
        }

        // array_key_exists allows for null values
        if (!array_key_exists($info, PMA_Table::$cache[$db][$table])) {
            if (! $disable_error) {
                trigger_error(
                    __('unknown table status: ') . $info,
                    E_USER_WARNING
                );
            }
            return false;
        }

        return PMA_Table::$cache[$db][$table][$info];
    }

    /**
     * generates column specification for ALTER or CREATE TABLE syntax
     *
     * @param string      $name           name
     * @param string      $type           type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $index          index
     * @param string      $length         length ('2', '5,2', '', ...)
     * @param string      $attribute      attribute
     * @param string      $collation      collation
     * @param bool|string $null           with 'NULL' or 'NOT NULL'
     * @param string      $default_type   whether default is CURRENT_TIMESTAMP,
     *                                    NULL, NONE, USER_DEFINED
     * @param string      $default_value  default value for USER_DEFINED
     *                                    default type
     * @param string      $extra          'AUTO_INCREMENT'
     * @param string      $comment        field comment
     * @param array       &$field_primary list of fields for PRIMARY KEY
     * @param string      $move_to        new position for column
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the
     * default current_timestamp is checked
     *
     * @return string  field specification
     */
    static function generateFieldSpec($name, $type, $index, $length = '',
        $attribute = '', $collation = '', $null = false,
        $default_type = 'USER_DEFINED', $default_value = '',  $extra = '',
        $comment = '', &$field_primary = null, $move_to = ''
    ) {
        $is_timestamp = strpos(strtoupper($type), 'TIMESTAMP') !== false;

        $query = PMA_Util::backquote($name) . ' ' . $type;

        if ($length != ''
            && ! preg_match(
                '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID)$@i',
                $type
            )
        ) {
            $query .= '(' . $length . ')';
        }

        if ($attribute != '') {
            $query .= ' ' . $attribute;
        }

        $matches = preg_match(
            '@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i',
            $type
        );
        if (! empty($collation) && $collation != 'NULL' && $matches) {
            $query .= PMA_generateCharsetQueryPart($collation);
        }

        if ($null !== false) {
            if ($null == 'NULL') {
                $query .= ' NULL';
            } else {
                $query .= ' NOT NULL';
            }
        }

        switch ($default_type) {
        case 'USER_DEFINED' :
            if ($is_timestamp && $default_value === '0') {
                // a TIMESTAMP does not accept DEFAULT '0'
                // but DEFAULT 0 works
                $query .= ' DEFAULT 0';
            } elseif ($type == 'BIT') {
                $query .= ' DEFAULT b\''
                        . preg_replace('/[^01]/', '0', $default_value)
                        . '\'';
            } elseif ($type == 'BOOLEAN') {
                if (preg_match('/^1|T|TRUE|YES$/i', $default_value)) {
                    $query .= ' DEFAULT TRUE';
                } elseif (preg_match('/^0|F|FALSE|NO$/i', $default_value)) {
                    $query .= ' DEFAULT FALSE';
                } else {
                    // Invalid BOOLEAN value
                    $query .= ' DEFAULT \''
                        . PMA_Util::sqlAddSlashes($default_value) . '\'';
                }
            } else {
                $query .= ' DEFAULT \''
                    . PMA_Util::sqlAddSlashes($default_value) . '\'';
            }
            break;
        case 'NULL' :
            //If user uncheck null checkbox and not change default value null,
            //default value will be ignored.
            if ($null !== false && $null != 'NULL') {
                break;
            }
        case 'CURRENT_TIMESTAMP' :
            $query .= ' DEFAULT ' . $default_type;
            break;
        case 'NONE' :
        default :
            break;
        }

        if (!empty($extra)) {
            $query .= ' ' . $extra;
            // Force an auto_increment field to be part of the primary key
            // even if user did not tick the PK box;
            if ($extra == 'AUTO_INCREMENT') {
                $primary_cnt = count($field_primary);
                if (1 == $primary_cnt) {
                    for ($j = 0; $j < $primary_cnt; $j++) {
                        if ($field_primary[$j] == $index) {
                            break;
                        }
                    }
                    if (isset($field_primary[$j]) && $field_primary[$j] == $index) {
                        $query .= ' PRIMARY KEY';
                        unset($field_primary[$j]);
                    }
                } else {
                    // but the PK could contain other columns so do not append
                    // a PRIMARY KEY clause, just add a member to $field_primary
                    $found_in_pk = false;
                    for ($j = 0; $j < $primary_cnt; $j++) {
                        if ($field_primary[$j] == $index) {
                            $found_in_pk = true;
                            break;
                        }
                    } // end for
                    if (! $found_in_pk) {
                        $field_primary[] = $index;
                    }
                }
            } // end if (auto_increment)
        }
        if (!empty($comment)) {
            $query .= " COMMENT '" . PMA_Util::sqlAddSlashes($comment) . "'";
        }

        // move column
        if ($move_to == '-first') { // dash can't appear as part of column name
            $query .= ' FIRST';
        } elseif ($move_to != '') {
            $query .= ' AFTER ' . PMA_Util::backquote($move_to);
        }
        return $query;
    } // end function

    /**
     * Counts and returns (or displays) the number of records in a table
     *
     * @param string $db          the current database name
     * @param string $table       the current table name
     * @param bool   $force_exact whether to force an exact count
     * @param bool   $is_view     whether the table is a view
     *
     * @return mixed the number of records if "retain" param is true,
     *               otherwise true
     */
    static public function countRecords($db, $table, $force_exact = false,
        $is_view = null
    ) {
        if (isset(PMA_Table::$cache[$db][$table]['ExactRows'])) {
            $row_count = PMA_Table::$cache[$db][$table]['ExactRows'];
        } else {
            $row_count = false;

            if (null === $is_view) {
                $is_view = PMA_Table::isView($db, $table);
            }

            if (! $force_exact) {
                if (! isset(PMA_Table::$cache[$db][$table]['Rows']) && ! $is_view) {
                    $tmp_tables = $GLOBALS['dbi']->getTablesFull($db, $table);
                    if (isset($tmp_tables[$table])) {
                        PMA_Table::$cache[$db][$table] = $tmp_tables[$table];
                    }
                }
                if (isset(PMA_Table::$cache[$db][$table]['Rows'])) {
                    $row_count = PMA_Table::$cache[$db][$table]['Rows'];
                } else {
                    $row_count = false;
                }
            }

            // for a VIEW, $row_count is always false at this point
            if (false === $row_count
                || $row_count < $GLOBALS['cfg']['MaxExactCount']
            ) {
                // Make an exception for views in I_S and D_D schema in
                // Drizzle, as these map to in-memory data and should execute
                // fast enough
                if (! $is_view
                    || (PMA_DRIZZLE && $GLOBALS['dbi']->isSystemSchema($db))
                ) {
                    $row_count = $GLOBALS['dbi']->fetchValue(
                        'SELECT COUNT(*) FROM ' . PMA_Util::backquote($db) . '.'
                        . PMA_Util::backquote($table)
                    );
                } else {
                    // For complex views, even trying to get a partial record
                    // count could bring down a server, so we offer an
                    // alternative: setting MaxExactCountViews to 0 will bypass
                    // completely the record counting for views

                    if ($GLOBALS['cfg']['MaxExactCountViews'] == 0) {
                        $row_count = 0;
                    } else {
                        // Counting all rows of a VIEW could be too long,
                        // so use a LIMIT clause.
                        // Use try_query because it can fail (when a VIEW is
                        // based on a table that no longer exists)
                        $result = $GLOBALS['dbi']->tryQuery(
                            'SELECT 1 FROM ' . PMA_Util::backquote($db) . '.'
                            . PMA_Util::backquote($table) . ' LIMIT '
                            . $GLOBALS['cfg']['MaxExactCountViews'],
                            null,
                            PMA_DatabaseInterface::QUERY_STORE
                        );
                        if (!$GLOBALS['dbi']->getError()) {
                            $row_count = $GLOBALS['dbi']->numRows($result);
                            $GLOBALS['dbi']->freeResult($result);
                        }
                    }
                }
                if ($row_count) {
                    PMA_Table::$cache[$db][$table]['ExactRows'] = $row_count;
                }
            }
        }

        return $row_count;
    } // end of the 'PMA_Table::countRecords()' function

    /**
     * Generates column specification for ALTER syntax
     *
     * @param string      $oldcol         old column name
     * @param string      $newcol         new column name
     * @param string      $type           type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length         length ('2', '5,2', '', ...)
     * @param string      $attribute      attribute
     * @param string      $collation      collation
     * @param bool|string $null           with 'NULL' or 'NOT NULL'
     * @param string      $default_type   whether default is CURRENT_TIMESTAMP,
     *                                    NULL, NONE, USER_DEFINED
     * @param string      $default_value  default value for USER_DEFINED default
     *                                    type
     * @param string      $extra          'AUTO_INCREMENT'
     * @param string      $comment        field comment
     * @param array       &$field_primary list of fields for PRIMARY KEY
     * @param string      $index          index
     * @param string      $move_to        new position for column
     *
     * @see PMA_Table::generateFieldSpec()
     *
     * @return string  field specification
     */
    static public function generateAlter($oldcol, $newcol, $type, $length,
        $attribute, $collation, $null, $default_type, $default_value,
        $extra, $comment, &$field_primary, $index, $move_to
    ) {
        return PMA_Util::backquote($oldcol) . ' '
            . PMA_Table::generateFieldSpec(
                $newcol, $type, $index, $length, $attribute,
                $collation, $null, $default_type, $default_value, $extra,
                $comment, $field_primary, $move_to
            );
    } // end function

    /**
     * Inserts existing entries in a PMA_* table by reading a value from an old
     * entry
     *
     * @param string $work         The array index, which Relation feature to
     *                             check ('relwork', 'commwork', ...)
     * @param string $pma_table    The array index, which PMA-table to update
     *                             ('bookmark', 'relation', ...)
     * @param array  $get_fields   Which fields will be SELECT'ed from the old entry
     * @param array  $where_fields Which fields will be used for the WHERE query
     *                             (array('FIELDNAME' => 'FIELDVALUE'))
     * @param array  $new_fields   Which fields will be used as new VALUES.
     *                             These are the important keys which differ
     *                             from the old entry
     *                             (array('FIELDNAME' => 'NEW FIELDVALUE'))
     *
     * @global relation variable
     *
     * @return int|true
     */
    static public function duplicateInfo($work, $pma_table, $get_fields,
        $where_fields, $new_fields
    ) {
        $last_id = -1;

        if (isset($GLOBALS['cfgRelation']) && $GLOBALS['cfgRelation'][$work]) {
            $select_parts = array();
            $row_fields = array();
            foreach ($get_fields as $get_field) {
                $select_parts[] = PMA_Util::backquote($get_field);
                $row_fields[$get_field] = 'cc';
            }

            $where_parts = array();
            foreach ($where_fields as $_where => $_value) {
                $where_parts[] = PMA_Util::backquote($_where) . ' = \''
                    . PMA_Util::sqlAddSlashes($_value) . '\'';
            }

            $new_parts = array();
            $new_value_parts = array();
            foreach ($new_fields as $_where => $_value) {
                $new_parts[] = PMA_Util::backquote($_where);
                $new_value_parts[] = PMA_Util::sqlAddSlashes($_value);
            }

            $table_copy_query = '
                SELECT ' . implode(', ', $select_parts) . '
                  FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                  . PMA_Util::backquote($GLOBALS['cfgRelation'][$pma_table]) . '
                 WHERE ' . implode(' AND ', $where_parts);

            // must use PMA_DatabaseInterface::QUERY_STORE here, since we execute
            // another query inside the loop
            $table_copy_rs = PMA_queryAsControlUser(
                $table_copy_query, true, PMA_DatabaseInterface::QUERY_STORE
            );

            while ($table_copy_row = @$GLOBALS['dbi']->fetchAssoc($table_copy_rs)) {
                $value_parts = array();
                foreach ($table_copy_row as $_key => $_val) {
                    if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                        $value_parts[] = PMA_Util::sqlAddSlashes($_val);
                    }
                }

                $new_table_query = 'INSERT IGNORE INTO '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.'
                    . PMA_Util::backquote($GLOBALS['cfgRelation'][$pma_table])
                    . ' (' . implode(', ', $select_parts)
                    . ', ' . implode(', ', $new_parts)
                    . ') VALUES (\''
                    . implode('\', \'', $value_parts) . '\', \''
                    . implode('\', \'', $new_value_parts) . '\')';

                PMA_queryAsControlUser($new_table_query);
                $last_id = $GLOBALS['dbi']->insertId();
            } // end while

            $GLOBALS['dbi']->freeResult($table_copy_rs);

            return $last_id;
        }

        return true;
    } // end of 'PMA_Table::duplicateInfo()' function

    /**
     * Copies or renames table
     *
     * @param string $source_db    source database
     * @param string $source_table source table
     * @param string $target_db    target database
     * @param string $target_table target table
     * @param string $what         what to be moved or copied (data, dataonly)
     * @param bool   $move         whether to move
     * @param string $mode         mode
     *
     * @return bool true if success, false otherwise
     */
    static public function moveCopy($source_db, $source_table, $target_db,
        $target_table, $what, $move, $mode
    ) {
        global $err_url;

        /* Try moving table directly */
        if ($move && $what == 'data') {
            $tbl = new PMA_Table($source_table, $source_db);
            $result = $tbl->rename($target_table, $target_db);
            if ($result) {
                $GLOBALS['message'] = $tbl->getLastMessage();
                return true;
            }
        }

        // set export settings we need
        $GLOBALS['sql_backquotes'] = 1;
        $GLOBALS['asfile']         = 1;

        // Ensure the target is valid
        if (! $GLOBALS['pma']->databases->exists($source_db, $target_db)) {
            if (! $GLOBALS['pma']->databases->exists($source_db)) {
                $GLOBALS['message'] = PMA_Message::rawError(
                    sprintf(
                        __('Source database `%s` was not found!'),
                        htmlspecialchars($source_db)
                    )
                );
            }
            if (! $GLOBALS['pma']->databases->exists($target_db)) {
                $GLOBALS['message'] = PMA_Message::rawError(
                    sprintf(
                        __('Target database `%s` was not found!'),
                        htmlspecialchars($target_db)
                    )
                );
            }
            return false;
        }

        $source = PMA_Util::backquote($source_db)
            . '.' . PMA_Util::backquote($source_table);
        if (! isset($target_db) || ! strlen($target_db)) {
            $target_db = $source_db;
        }

        // Doing a select_db could avoid some problems with replicated databases,
        // when moving table from replicated one to not replicated one
        $GLOBALS['dbi']->selectDb($target_db);

        $target = PMA_Util::backquote($target_db)
            . '.' . PMA_Util::backquote($target_table);

        // do not create the table if dataonly
        if ($what != 'dataonly') {
            include_once "libraries/plugin_interface.lib.php";
            // get Export SQL instance
            $export_sql_plugin = PMA_getPlugin(
                "export",
                "sql",
                'libraries/plugins/export/',
                array(
                    'export_type' => 'table',
                    'single_table' => false,
                )
            );

            $no_constraints_comments = true;
            $GLOBALS['sql_constraints_query'] = '';
            // set the value of global sql_auto_increment variable
            if (isset($_POST['sql_auto_increment'])) {
                $GLOBALS['sql_auto_increment'] = $_POST['sql_auto_increment'];
            }

            $sql_structure = $export_sql_plugin->getTableDef(
                $source_db, $source_table, "\n", $err_url, false, false
            );
            unset($no_constraints_comments);
            $parsed_sql =  PMA_SQP_parse($sql_structure);
            $analyzed_sql = PMA_SQP_analyze($parsed_sql);
            $i = 0;
            if (empty($analyzed_sql[0]['create_table_fields'])) {
                // this is not a CREATE TABLE, so find the first VIEW
                $target_for_view = PMA_Util::backquote($target_db);
                while (true) {
                    if ($parsed_sql[$i]['type'] == 'alpha_reservedWord'
                        && $parsed_sql[$i]['data'] == 'VIEW'
                    ) {
                        break;
                    }
                    $i++;
                }
            }
            unset($analyzed_sql);
            if (PMA_DRIZZLE) {
                $table_delimiter = 'quote_backtick';
            } else {
                $server_sql_mode = $GLOBALS['dbi']->fetchValue(
                    "SHOW VARIABLES LIKE 'sql_mode'",
                    0,
                    1
                );
                // ANSI_QUOTES might be a subset of sql_mode, for example
                // REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ANSI
                if (false !== strpos($server_sql_mode, 'ANSI_QUOTES')) {
                    $table_delimiter = 'quote_double';
                } else {
                    $table_delimiter = 'quote_backtick';
                }
                unset($server_sql_mode);
            }

            /* Find table name in query and replace it */
            while ($parsed_sql[$i]['type'] != $table_delimiter) {
                $i++;
            }

            /* no need to backquote() */
            if (isset($target_for_view)) {
                // this a view definition; we just found the first db name
                // that follows DEFINER VIEW
                // so change it for the new db name
                $parsed_sql[$i]['data'] = $target_for_view;
                // then we have to find all references to the source db
                // and change them to the target db, ensuring we stay into
                // the $parsed_sql limits
                $last = $parsed_sql['len'] - 1;
                $backquoted_source_db = PMA_Util::backquote($source_db);
                for (++$i; $i <= $last; $i++) {
                    if ($parsed_sql[$i]['type'] == $table_delimiter
                        && $parsed_sql[$i]['data'] == $backquoted_source_db
                        && $parsed_sql[$i - 1]['type'] != 'punct_qualifier'
                    ) {
                        $parsed_sql[$i]['data'] = $target_for_view;
                    }
                }
                unset($last,$backquoted_source_db);
            } else {
                $parsed_sql[$i]['data'] = $target;
            }

            /* Generate query back */
            $sql_structure = PMA_SQP_format($parsed_sql, 'query_only');
            // If table exists, and 'add drop table' is selected: Drop it!
            $drop_query = '';
            if (isset($_REQUEST['drop_if_exists'])
                && $_REQUEST['drop_if_exists'] == 'true'
            ) {
                if (PMA_Table::isView($target_db, $target_table)) {
                    $drop_query = 'DROP VIEW';
                } else {
                    $drop_query = 'DROP TABLE';
                }
                $drop_query .= ' IF EXISTS '
                    . PMA_Util::backquote($target_db) . '.'
                    . PMA_Util::backquote($target_table);
                $GLOBALS['dbi']->query($drop_query);

                $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';

                // If an existing table gets deleted, maintain any
                // entries for the PMA_* tables
                $maintain_relations = true;
            }

            @$GLOBALS['dbi']->query($sql_structure);
            $GLOBALS['sql_query'] .= "\n" . $sql_structure . ';';

            if (($move || isset($GLOBALS['add_constraints']))
                && !empty($GLOBALS['sql_constraints_query'])
            ) {
                $parsed_sql =  PMA_SQP_parse($GLOBALS['sql_constraints_query']);
                $i = 0;

                // find the first $table_delimiter, it must be the source
                // table name
                while ($parsed_sql[$i]['type'] != $table_delimiter) {
                    $i++;
                    // maybe someday we should guard against going over limit
                    //if ($i == $parsed_sql['len']) {
                    //    break;
                    //}
                }

                // replace it by the target table name, no need
                // to backquote()
                $parsed_sql[$i]['data'] = $target;

                // now we must remove all $table_delimiter that follow a
                // CONSTRAINT keyword, because a constraint name must be
                // unique in a db

                $cnt = $parsed_sql['len'] - 1;

                for ($j = $i; $j < $cnt; $j++) {
                    if ($parsed_sql[$j]['type'] == 'alpha_reservedWord'
                        && strtoupper($parsed_sql[$j]['data']) == 'CONSTRAINT'
                    ) {
                        if ($parsed_sql[$j+1]['type'] == $table_delimiter) {
                            $parsed_sql[$j+1]['data'] = '';
                        }
                    }
                }

                // Generate query back
                $GLOBALS['sql_constraints_query'] = PMA_SQP_format(
                    $parsed_sql, 'query_only'
                );
                if ($mode == 'one_table') {
                    $GLOBALS['dbi']->query($GLOBALS['sql_constraints_query']);
                }
                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_constraints_query'];
                if ($mode == 'one_table') {
                    unset($GLOBALS['sql_constraints_query']);
                }
            }
        } else {
            $GLOBALS['sql_query'] = '';
        }

        // Copy the data unless this is a VIEW
        if (($what == 'data' || $what == 'dataonly')
            && ! PMA_Table::isView($target_db, $target_table)
        ) {
            $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
            $GLOBALS['dbi']->query($sql_set_mode);
            $GLOBALS['sql_query'] .= "\n\n" . $sql_set_mode . ';';

            $sql_insert_data = 'INSERT INTO ' . $target
                . ' SELECT * FROM ' . $source;
            $GLOBALS['dbi']->query($sql_insert_data);
            $GLOBALS['sql_query']      .= "\n\n" . $sql_insert_data . ';';
        }

        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            $GLOBALS['dbi']->selectDb($source_db);

            if (PMA_Table::isView($source_db, $source_table)) {
                $sql_drop_query = 'DROP VIEW';
            } else {
                $sql_drop_query = 'DROP TABLE';
            }
            $sql_drop_query .= ' ' . $source;
            $GLOBALS['dbi']->query($sql_drop_query);

            // Renable table in configuration storage
            PMA_REL_renameTable(
                $source_db, $target_db,
                $source_table, $target_table
            );

            $GLOBALS['sql_query']      .= "\n\n" . $sql_drop_query . ';';
            // end if ($move)
        } else {
            // we are copying
            // Create new entries as duplicates from old PMA DBs
            if ($what != 'dataonly' && ! isset($maintain_relations)) {
                if ($GLOBALS['cfgRelation']['commwork']) {
                    // Get all comments and MIME-Types for current table
                    $comments_copy_rs = PMA_queryAsControlUser(
                        'SELECT column_name, comment'
                        . ($GLOBALS['cfgRelation']['mimework']
                        ? ', mimetype, transformation, transformation_options'
                        : '')
                        . ' FROM '
                        . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.'
                        . PMA_Util::backquote($GLOBALS['cfgRelation']['column_info'])
                        . ' WHERE '
                        . ' db_name = \''
                        . PMA_Util::sqlAddSlashes($source_db) . '\''
                        . ' AND '
                        . ' table_name = \''
                        . PMA_Util::sqlAddSlashes($source_table) . '\''
                    );

                    // Write every comment as new copied entry. [MIME]
                    while ($comments_copy_row = $GLOBALS['dbi']->fetchAssoc($comments_copy_rs)) {
                        $new_comment_query = 'REPLACE INTO ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['column_info'])
                                    . ' (db_name, table_name, column_name, comment' . ($GLOBALS['cfgRelation']['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                    . ' VALUES('
                                    . '\'' . PMA_Util::sqlAddSlashes($target_db) . '\','
                                    . '\'' . PMA_Util::sqlAddSlashes($target_table) . '\','
                                    . '\'' . PMA_Util::sqlAddSlashes($comments_copy_row['column_name']) . '\''
                                    . ($GLOBALS['cfgRelation']['mimework'] ? ',\'' . PMA_Util::sqlAddSlashes($comments_copy_row['comment']) . '\','
                                            . '\'' . PMA_Util::sqlAddSlashes($comments_copy_row['mimetype']) . '\','
                                            . '\'' . PMA_Util::sqlAddSlashes($comments_copy_row['transformation']) . '\','
                                            . '\'' . PMA_Util::sqlAddSlashes($comments_copy_row['transformation_options']) . '\'' : '')
                                    . ')';
                        PMA_queryAsControlUser($new_comment_query);
                    } // end while
                    $GLOBALS['dbi']->freeResult($comments_copy_rs);
                    unset($comments_copy_rs);
                }

                // duplicating the bookmarks must not be done here, but
                // just once per db

                $get_fields = array('display_field');
                $where_fields = array(
                    'db_name' => $source_db,
                    'table_name' => $source_table
                );
                $new_fields = array(
                    'db_name' => $target_db,
                    'table_name' => $target_table
                );
                PMA_Table::duplicateInfo(
                    'displaywork',
                    'table_info',
                    $get_fields,
                    $where_fields,
                    $new_fields
                );


                /**
                 * @todo revise this code when we support cross-db relations
                 */
                $get_fields = array(
                    'master_field',
                    'foreign_table',
                    'foreign_field'
                );
                $where_fields = array(
                    'master_db' => $source_db,
                    'master_table' => $source_table
                );
                $new_fields = array(
                    'master_db' => $target_db,
                    'foreign_db' => $target_db,
                    'master_table' => $target_table
                );
                PMA_Table::duplicateInfo(
                    'relwork',
                    'relation',
                    $get_fields,
                    $where_fields,
                    $new_fields
                );


                $get_fields = array(
                    'foreign_field',
                    'master_table',
                    'master_field'
                );
                $where_fields = array(
                    'foreign_db' => $source_db,
                    'foreign_table' => $source_table
                );
                $new_fields = array(
                    'master_db' => $target_db,
                    'foreign_db' => $target_db,
                    'foreign_table' => $target_table
                );
                PMA_Table::duplicateInfo(
                    'relwork',
                    'relation',
                    $get_fields,
                    $where_fields,
                    $new_fields
                );


                $get_fields = array('x', 'y', 'v', 'h');
                $where_fields = array(
                    'db_name' => $source_db,
                    'table_name' => $source_table
                );
                $new_fields = array(
                    'db_name' => $target_db,
                    'table_name' => $target_table
                );
                PMA_Table::duplicateInfo(
                    'designerwork',
                    'designer_coords',
                    $get_fields,
                    $where_fields,
                    $new_fields
                );

                /**
                 * @todo Can't get duplicating PDFs the right way. The
                 * page numbers always get screwed up independently from
                 * duplication because the numbers do not seem to be stored on a
                 * per-database basis. Would the author of pdf support please
                 * have a look at it?
                 *
                $get_fields = array('page_descr');
                $where_fields = array('db_name' => $source_db);
                $new_fields = array('db_name' => $target_db);
                $last_id = PMA_Table::duplicateInfo(
                    'pdfwork',
                    'pdf_pages',
                    $get_fields,
                    $where_fields,
                    $new_fields
                );

                if (isset($last_id) && $last_id >= 0) {
                    $get_fields = array('x', 'y');
                    $where_fields = array(
                        'db_name' => $source_db,
                        'table_name' => $source_table
                    );
                    $new_fields = array(
                        'db_name' => $target_db,
                        'table_name' => $target_table,
                        'pdf_page_number' => $last_id
                    );
                    PMA_Table::duplicateInfo(
                        'pdfwork',
                        'table_coords',
                        $get_fields,
                        $where_fields,
                        $new_fields
                    );
                }
                 */
            }
        }
        return true;
    }

    /**
     * checks if given name is a valid table name,
     * currently if not empty, trailing spaces, '.', '/' and '\'
     *
     * @param string $table_name name to check
     *
     * @todo add check for valid chars in filename on current system/os
     * @see  http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
     *
     * @return boolean whether the string is valid or not
     */
    static function isValidName($table_name)
    {
        if ($table_name !== trim($table_name)) {
            // trailing spaces
            return false;
        }

        if (! strlen($table_name)) {
            // zero length
            return false;
        }

        if (preg_match('/[.\/\\\\]+/i', $table_name)) {
            // illegal char . / \
            return false;
        }

        return true;
    }

    /**
     * renames table
     *
     * @param string $new_name new table name
     * @param string $new_db   new database name
     *
     * @return bool success
     */
    function rename($new_name, $new_db = null)
    {
        if (null !== $new_db && $new_db !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $GLOBALS['pma']->databases->exists($new_db)) {
                $this->errors[] = __('Invalid database:') . ' ' . $new_db;
                return false;
            }
        } else {
            $new_db = $this->getDbName();
        }

        $new_table = new PMA_Table($new_name, $new_db);

        if ($this->getFullName() === $new_table->getFullName()) {
            return true;
        }

        if (! PMA_Table::isValidName($new_name)) {
            $this->errors[] = __('Invalid table name:') . ' '
                . $new_table->getFullName();
            return false;
        }

        // If the table is moved to a different database drop its triggers first
        $triggers = $GLOBALS['dbi']->getTriggers(
            $this->getDbName(), $this->getName(), ''
        );
        $handle_triggers = $this->getDbName() != $new_db && $triggers;
        if ($handle_triggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS '
                    . PMA_Util::backquote($this->getDbName())
                    . '.' . PMA_Util::backquote($trigger['name']) . ';';
                $GLOBALS['dbi']->query($sql);
            }
        }

        /*
         * tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
         */
        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                  TO ' . $new_table->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if (! $GLOBALS['dbi']->query($GLOBALS['sql_query'])) {
            // Restore triggers in the old database
            if ($handle_triggers) {
                $GLOBALS['dbi']->selectDb($this->getDbName());
                foreach ($triggers as $trigger) {
                    $GLOBALS['dbi']->query($trigger['create']);
                }
            }
            $this->errors[] = sprintf(
                __('Error renaming table %1$s to %2$s'),
                $this->getFullName(),
                $new_table->getFullName()
            );
            return false;
        }

        $old_name = $this->getName();
        $old_db = $this->getDbName();
        $this->setName($new_name);
        $this->setDbName($new_db);

        // Renable table in configuration storage
        PMA_REL_renameTable(
            $old_db, $new_db,
            $old_name, $new_name
        );

        $this->messages[] = sprintf(
            __('Table %1$s has been renamed to %2$s.'),
            htmlspecialchars($old_name),
            htmlspecialchars($new_name)
        );
        return true;
    }

    /**
     * Get all unique columns
     *
     * returns an array with all columns with unqiue content, in fact these are
     * all columns being single indexed in PRIMARY or UNIQUE
     *
     * e.g.
     *  - PRIMARY(id) // id
     *  - UNIQUE(name) // name
     *  - PRIMARY(fk_id1, fk_id2) // NONE
     *  - UNIQUE(x,y) // NONE
     *
     * @param bool $backquoted whether to quote name with backticks ``
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getUniqueColumns($backquoted = true, $fullName = true)
    {
        $sql = $GLOBALS['dbi']->getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Non_unique = 0'
        );
        $uniques = $GLOBALS['dbi']->fetchResult(
            $sql,
            array('Key_name', null),
            'Column_name'
        );

        $return = array();
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }
            $return[] = ($fullName ? $this->getFullName($backquoted) . '.' : '')
                . ($backquoted ? PMA_Util::backquote($index[0]) : $index[0]);
        }

        return $return;
    }

    /**
     * Get all indexed columns
     *
     * returns an array with all columns make use of an index, in fact only
     * first columns in an index
     *
     * e.g. index(col1, col2) would only return col1
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return array
     */
    public function getIndexedColumns($backquoted = true)
    {
        $sql = $GLOBALS['dbi']->getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Seq_in_index = 1'
        );
        $indexed = $GLOBALS['dbi']->fetchResult($sql, 'Column_name', 'Column_name');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = $this->getFullName($backquoted) . '.'
                . ($backquoted ? PMA_Util::backquote($column) : $column);
        }

        return $return;
    }

    /**
     * Get all columns
     *
     * returns an array with all columns
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return array
     */
    public function getColumns($backquoted = true)
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $indexed = $GLOBALS['dbi']->fetchResult($sql, 'Field', 'Field');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = $this->getFullName($backquoted) . '.'
                . ($backquoted ? PMA_Util::backquote($column) : $column);
        }

        return $return;
    }

    /**
     * Return UI preferences for this table from phpMyAdmin database.
     *
     * @return array
     */
    protected function getUiPrefsFromDb()
    {
        $pma_table = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) ."."
            . PMA_Util::backquote($GLOBALS['cfg']['Server']['table_uiprefs']);

        // Read from phpMyAdmin database
        $sql_query = " SELECT `prefs` FROM " . $pma_table
            . " WHERE `username` = '" . $GLOBALS['cfg']['Server']['user'] . "'"
            . " AND `db_name` = '" . PMA_Util::sqlAddSlashes($this->db_name) . "'"
            . " AND `table_name` = '" . PMA_Util::sqlAddSlashes($this->name) . "'";

        $row = $GLOBALS['dbi']->fetchArray(PMA_queryAsControlUser($sql_query));
        if (isset($row[0])) {
            return json_decode($row[0], true);
        } else {
            return array();
        }
    }

    /**
     * Save this table's UI preferences into phpMyAdmin database.
     *
     * @return true|PMA_Message
     */
    protected function saveUiPrefsToDb()
    {
        $pma_table = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) . "."
            . PMA_Util::backquote($GLOBALS['cfg']['Server']['table_uiprefs']);

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query = " REPLACE INTO " . $pma_table
            . " VALUES ('" . $username . "', '" . PMA_Util::sqlAddSlashes($this->db_name)
            . "', '" . PMA_Util::sqlAddSlashes($this->name) . "', '"
            . PMA_Util::sqlAddSlashes(json_encode($this->uiprefs)) . "', NULL)";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, $GLOBALS['controllink']);

        if (!$success) {
            $message = PMA_Message::error(
                __('Could not save table UI preferences')
            );
            $message->addMessage('<br /><br />');
            $message->addMessage(
                PMA_Message::rawError(
                    $GLOBALS['dbi']->getError($GLOBALS['controllink'])
                )
            );
            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured
        // maximum rows
        $sql_query = 'SELECT COUNT(*) FROM ' . $pma_table;
        $rows_count = $GLOBALS['dbi']->fetchValue($sql_query);
        $max_rows = $GLOBALS['cfg']['Server']['MaxTableUiprefs'];
        if ($rows_count > $max_rows) {
            $num_rows_to_delete = $rows_count - $max_rows;
            $sql_query
                = ' DELETE FROM ' . $pma_table .
                ' ORDER BY last_update ASC' .
                ' LIMIT ' . $num_rows_to_delete;
            $success = $GLOBALS['dbi']->tryQuery(
                $sql_query, $GLOBALS['controllink']
            );

            if (!$success) {
                $message = PMA_Message::error(
                    sprintf(
                        __('Failed to cleanup table UI preferences (see $cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'),
                        PMA_Util::showDocu('config', 'cfg_Servers_MaxTableUiprefs')
                    )
                );
                $message->addMessage('<br /><br />');
                $message->addMessage(
                    PMA_Message::rawError(
                        $GLOBALS['dbi']->getError($GLOBALS['controllink'])
                    )
                );
                print_r($message);
                return $message;
            }
        }

        return true;
    }

    /**
     * Loads the UI preferences for this table.
     * If pmadb and table_uiprefs is set, it will load the UI preferences from
     * phpMyAdmin database.
     *
     * @return void
     */
    protected function loadUiPrefs()
    {
        $server_id = $GLOBALS['server'];
        // set session variable if it's still undefined
        if (! isset($_SESSION['tmpval']['table_uiprefs'][$server_id][$this->db_name][$this->name])) {
            // check whether we can get from pmadb
            $_SESSION['tmpval']['table_uiprefs'][$server_id][$this->db_name][$this->name]
                = (strlen($GLOBALS['cfg']['Server']['pmadb'])
                    && strlen($GLOBALS['cfg']['Server']['table_uiprefs']))
                    ?  $this->getUiPrefsFromDb()
                    : array();
        }
        $this->uiprefs =& $_SESSION['tmpval']['table_uiprefs'][$server_id]
            [$this->db_name][$this->name];
    }

    /**
     * Get a property from UI preferences.
     * Return false if the property is not found.
     * Available property:
     * - PROP_SORTED_COLUMN
     * - PROP_COLUMN_ORDER
     * - PROP_COLUMN_VISIB
     *
     * @param string $property property
     *
     * @return mixed
     */
    public function getUiProp($property)
    {
        if (! isset($this->uiprefs)) {
            $this->loadUiPrefs();
        }
        // do checking based on property
        if ($property == self::PROP_SORTED_COLUMN) {
            if (isset($this->uiprefs[$property])) {
                // check if the column name exists in this table
                $tmp = explode(' ', $this->uiprefs[$property]);
                $colname = $tmp[0];               
                //remove backquoting from colname 
                $colname = str_replace('`', '', $colname);
                //get the available column name without backquoting
                $avail_columns = $this->getColumns(false);
                foreach ($avail_columns as $each_col) {
                    // check if $each_col ends with $colname
                    if (substr_compare(
                        $each_col,
                        $colname,
                        strlen($each_col) - strlen($colname)
                    ) === 0) {
                        return $this->uiprefs[$property];
                    }
                }
                // remove the property, since it is not exist anymore in database
                $this->removeUiProp(self::PROP_SORTED_COLUMN);
                return false;
            } else {
                return false;
            }
        } elseif ($property == self::PROP_COLUMN_ORDER
            || $property == self::PROP_COLUMN_VISIB
        ) {
            if (! PMA_Table::isView($this->db_name, $this->name)
                && isset($this->uiprefs[$property])
            ) {
                // check if the table has not been modified
                if (self::sGetStatusInfo(
                    $this->db_name,
                    $this->name, 'Create_time'
                ) == $this->uiprefs['CREATE_TIME']) {
                    return $this->uiprefs[$property];
                } else {
                    // remove the property, since the table has been modified
                    $this->removeUiProp(self::PROP_COLUMN_ORDER);
                    return false;
                }
            } else {
                return false;
            }
        }
        // default behaviour for other property:
        return isset($this->uiprefs[$property]) ? $this->uiprefs[$property] : false;
    }

    /**
     * Set a property from UI preferences.
     * If pmadb and table_uiprefs is set, it will save the UI preferences to
     * phpMyAdmin database.
     * Available property:
     * - PROP_SORTED_COLUMN
     * - PROP_COLUMN_ORDER
     * - PROP_COLUMN_VISIB
     *
     * @param string $property          Property
     * @param mixed  $value             Value for the property
     * @param string $table_create_time Needed for PROP_COLUMN_ORDER
     *                                  and PROP_COLUMN_VISIB
     *
     * @return boolean|PMA_Message
     */
    public function setUiProp($property, $value, $table_create_time = null)
    {
        if (! isset($this->uiprefs)) {
            $this->loadUiPrefs();
        }
        // we want to save the create time if the property is PROP_COLUMN_ORDER
        if (! PMA_Table::isView($this->db_name, $this->name)
            && ($property == self::PROP_COLUMN_ORDER
            || $property == self::PROP_COLUMN_VISIB)
        ) {
            $curr_create_time = self::sGetStatusInfo(
                $this->db_name,
                $this->name,
                'CREATE_TIME'
            );
            if (isset($table_create_time)
                && $table_create_time == $curr_create_time
            ) {
                $this->uiprefs['CREATE_TIME'] = $curr_create_time;
            } else {
                // there is no $table_create_time, or
                // supplied $table_create_time is older than current create time,
                // so don't save
                return PMA_Message::error(
                    sprintf(
                        __('Cannot save UI property "%s". The changes made will not be persistent after you refresh this page. Please check if the table structure has been changed.'),
                        $property
                    )
                );
            }
        }
        // save the value
        $this->uiprefs[$property] = $value;
        // check if pmadb is set
        if (strlen($GLOBALS['cfg']['Server']['pmadb'])
            && strlen($GLOBALS['cfg']['Server']['table_uiprefs'])
        ) {
            return $this->saveUiprefsToDb();
        }
        return true;
    }

    /**
     * Remove a property from UI preferences.
     *
     * @param string $property the property
     *
     * @return true|PMA_Message
     */
    public function removeUiProp($property)
    {
        if (! isset($this->uiprefs)) {
            $this->loadUiPrefs();
        }
        if (isset($this->uiprefs[$property])) {
            unset($this->uiprefs[$property]);
            // check if pmadb is set
            if (strlen($GLOBALS['cfg']['Server']['pmadb'])
                && strlen($GLOBALS['cfg']['Server']['table_uiprefs'])
            ) {
                return $this->saveUiprefsToDb();
            }
        }
        return true;
    }

    /**
     * Get all column names which are MySQL reserved words
     *
     * @return array
     * @access public
     */
    public function getReservedColumnNames()
    {
        $columns = $this->getColumns(false);
        $return = array();
        foreach ($columns as $column) {
            $temp = explode('.', $column);
            $column_name = $temp[2];
            if (PMA_SQP_isKeyWord($column_name)) {
                $return[] = $column_name;
            }
        }
        return $return;
    }
}
?>
