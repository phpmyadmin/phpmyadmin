<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
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
     * @var array errors occured
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
     * @return  string  table name
     */
    function __toString()
    {
        return $this->getName();
    }

    /**
     * return the last error
     *
     * @return the last error
     */
    function getLastError()
    {
        return end($this->errors);
    }

    /**
     * return the last message
     *
     * @return the last message
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
     * @return nothing
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
     * @return  string  table name
     */
    function getName($backquoted = false)
    {
        if ($backquoted) {
            return PMA_backquote($this->name);
        }
        return $this->name;
    }

    /**
     * sets database name for this table
     *
     * @param string $db_name database name
     *
     * @return nothing
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
     * @return  string  database name for this table
     */
    function getDbName($backquoted = false)
    {
        if ($backquoted) {
            return PMA_backquote($this->db_name);
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
        return $this->getDbName($backquoted) . '.' . $this->getName($backquoted);
    }

    /**
     * returns whether the table is actually a view
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return whether the given is a view
     */
    static public function isView($db = null, $table = null)
    {
        if (empty($db) || empty($table)) {
            return false;
        }

        // use cached data or load information with SHOW command
        if (isset(PMA_Table::$cache[$db][$table]) || $GLOBALS['cfg']['Server']['DisableIS']) {
            $type = PMA_Table::sGetStatusInfo($db, $table, 'TABLE_TYPE');
            return $type == 'VIEW';
        }

        // query information_schema
        $result = PMA_DBI_fetch_result(
            "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '" . PMA_sqlAddSlashes($db) . "'
                AND TABLE_NAME = '" . PMA_sqlAddSlashes($table) . "'");
        return $result ? true : false;
    }

    /**
     * sets given $value for given $param
     *
     * @param string $param name
     * @param mixed  $value value
     *
     * @return nothing
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
     * @return  mixed   value for $param
     */
    function get($param)
    {
        if (isset($this->settings[$param])) {
            return $this->settings[$param];
        }

        return null;
    }

    /**
     * loads structure data
     * (this function is work in progress? not yet used)
     *
     * @return boolean
     */
    function loadStructure()
    {
        $table_info = PMA_DBI_get_tables_full($this->getDbName(), $this->getName());

        if (false === $table_info) {
            return false;
        }

        $this->settings = $table_info;

        if ($this->get('TABLE_ROWS') === null) {
            $this->set(
                'TABLE_ROWS',
                PMA_Table::countRecords($this->getDbName(), $this->getName(), true)
            );
        }

        $create_options = explode(' ', $this->get('TABLE_ROWS'));

        // export create options by its name as variables into gloabel namespace
        // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
        foreach ($create_options as $each_create_option) {
            $each_create_option = explode('=', $each_create_option);
            if (isset($each_create_option[1])) {
                $this->set($$each_create_option[0], $each_create_option[1]);
            }
        }
        return true;
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
     * @return  boolean  true if it is a merge table
     */
    static public function isMerge($db = null, $table = null)
    {
        $engine = null;
        // if called static, with parameters
        if (! empty($db) && ! empty($table)) {
            $engine = PMA_Table::sGetStatusInfo($db, $table, 'ENGINE', null, true);
        }

        return (! empty($engine) && ((strtoupper($engine) == 'MERGE') || (strtoupper($engine) == 'MRG_MYISAM')));
    }

    static public function sGetToolTip($db, $table)
    {
        return PMA_Table::sGetStatusInfo($db, $table, 'Comment')
            . ' (' . PMA_Table::countRecords($db, $table) . ')';
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param string  $db            database name
     * @param string  $table         table name
     * @param string  $info
     * @param boolean $force_read    read new rather than serving from cache
     * @param boolean $disable_error if true, disables error message
     *
     * @todo PMA_DBI_get_tables_full needs to be merged somehow into this class
     * or at least better documented
     *
     * @return mixed
     */
    static public function sGetStatusInfo($db, $table, $info = null, $force_read = false, $disable_error = false)
    {
        if (! isset(PMA_Table::$cache[$db][$table]) || $force_read) {
            PMA_DBI_get_tables_full($db, $table);
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
                trigger_error(__('unknown table status: ') . $info, E_USER_WARNING);
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
     * @param string      $length         length ('2', '5,2', '', ...)
     * @param string      $attribute      attribute
     * @param string      $collation      collation
     * @param bool|string $null           with 'NULL' or 'NOT NULL'
     * @param string      $default_type   whether default is CURRENT_TIMESTAMP,
     *                                    NULL, NONE, USER_DEFINED
     * @param string      $default_value  default value for USER_DEFINED default type
     * @param string      $extra          'AUTO_INCREMENT'
     * @param string      $comment        field comment
     * @param array       &$field_primary list of fields for PRIMARY KEY
     * @param string      $index
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the default
     * current_timestamp is checked
     *
     * @return  string  field specification
     */
    static function generateFieldSpec($name, $type, $length = '', $attribute = '',
        $collation = '', $null = false, $default_type = 'USER_DEFINED',
        $default_value = '', $extra = '', $comment = '',
        &$field_primary, $index)
    {

        $is_timestamp = strpos(strtoupper($type), 'TIMESTAMP') !== false;

        $query = PMA_backquote($name) . ' ' . $type;

        if ($length != ''
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID)$@i', $type)) {
            $query .= '(' . $length . ')';
        }

        if ($attribute != '') {
            $query .= ' ' . $attribute;
        }

        if (! empty($collation) && $collation != 'NULL'
            && preg_match('@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i', $type)
        ) {
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
                    $query .= ' DEFAULT \'' . PMA_sqlAddSlashes($default_value) . '\'';
                }
            } else {
                $query .= ' DEFAULT \'' . PMA_sqlAddSlashes($default_value) . '\'';
            }
            break;
        case 'NULL' :
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
            $query .= " COMMENT '" . PMA_sqlAddSlashes($comment) . "'";
        }
        return $query;
    } // end function

    /**
     * Counts and returns (or displays) the number of records in a table
     *
     * Revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param string $db          the current database name
     * @param string $table       the current table name
     * @param bool   $force_exact whether to force an exact count
     * @param bool   $is_view     whether the table is a view
     *
     * @return mixed the number of records if "retain" param is true,
     *               otherwise true
     */
    static public function countRecords($db, $table, $force_exact = false, $is_view = null)
    {
        if (isset(PMA_Table::$cache[$db][$table]['ExactRows'])) {
            $row_count = PMA_Table::$cache[$db][$table]['ExactRows'];
        } else {
            $row_count = false;

            if (null === $is_view) {
                $is_view = PMA_Table::isView($db, $table);
            }

            if (! $force_exact) {
                if (! isset(PMA_Table::$cache[$db][$table]['Rows']) && ! $is_view) {
                    $tmp_tables = PMA_DBI_get_tables_full($db, $table);
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
            if (false === $row_count || $row_count < $GLOBALS['cfg']['MaxExactCount']) {
                // Make an exception for views in I_S and D_D schema in Drizzle, as these map to
                // in-memory data and should execute fast enough
                if (! $is_view || (PMA_DRIZZLE && PMA_is_system_schema($db))) {
                    $row_count = PMA_DBI_fetch_value(
                        'SELECT COUNT(*) FROM ' . PMA_backquote($db) . '.'
                        . PMA_backquote($table)
                    );
                } else {
                    // For complex views, even trying to get a partial record
                    // count could bring down a server, so we offer an
                    // alternative: setting MaxExactCountViews to 0 will bypass
                    // completely the record counting for views

                    if ($GLOBALS['cfg']['MaxExactCountViews'] == 0) {
                        $row_count = 0;
                    } else {
                        // Counting all rows of a VIEW could be too long, so use
                        // a LIMIT clause.
                        // Use try_query because it can fail (when a VIEW is
                        // based on a table that no longer exists)
                        $result = PMA_DBI_try_query(
                            'SELECT 1 FROM ' . PMA_backquote($db) . '.'
                            . PMA_backquote($table) . ' LIMIT '
                            . $GLOBALS['cfg']['MaxExactCountViews'],
                            null,
                            PMA_DBI_QUERY_STORE
                        );
                        if (!PMA_DBI_getError()) {
                            $row_count = PMA_DBI_num_rows($result);
                            PMA_DBI_free_result($result);
                        }
                    }
                }
                PMA_Table::$cache[$db][$table]['ExactRows'] = $row_count;
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
     * @param string      $default_value  default value for USER_DEFINED default type
     * @param string      $extra          'AUTO_INCREMENT'
     * @param string      $comment        field comment
     * @param array       &$field_primary list of fields for PRIMARY KEY
     * @param string      $index
     * @param mixed       $default_orig
     *
     * @see PMA_Table::generateFieldSpec()
     *
     * @return  string  field specification
     */
    static public function generateAlter($oldcol, $newcol, $type, $length,
        $attribute, $collation, $null, $default_type, $default_value,
        $extra, $comment = '', &$field_primary, $index, $default_orig)
    {
        return PMA_backquote($oldcol) . ' '
            . PMA_Table::generateFieldSpec(
                $newcol, $type, $length, $attribute,
                $collation, $null, $default_type, $default_value, $extra,
                $comment, $field_primary, $index, $default_orig
            );
    } // end function

    /**
     * Inserts existing entries in a PMA_* table by reading a value from an old entry
     *
     * @param string $work         The array index, which Relation feature to check
     *                             ('relwork', 'commwork', ...)
     * @param string $pma_table    The array index, which PMA-table to update
     *                             ('bookmark', 'relation', ...)
     * @param array  $get_fields   Which fields will be SELECT'ed from the old entry
     * @param array  $where_fields Which fields will be used for the WHERE query
     *                             (array('FIELDNAME' => 'FIELDVALUE'))
     * @param array  $new_fields   Which fields will be used as new VALUES. These are
     *                             the important keys which differ from the old entry
     *                             (array('FIELDNAME' => 'NEW FIELDVALUE'))
     *
     * @global relation variable
     *
     * @return int|true
     */
    static public function duplicateInfo($work, $pma_table, $get_fields, $where_fields, $new_fields)
    {
        $last_id = -1;

        if (isset($GLOBALS['cfgRelation']) && $GLOBALS['cfgRelation'][$work]) {
            $select_parts = array();
            $row_fields = array();
            foreach ($get_fields as $get_field) {
                $select_parts[] = PMA_backquote($get_field);
                $row_fields[$get_field] = 'cc';
            }

            $where_parts = array();
            foreach ($where_fields as $_where => $_value) {
                $where_parts[] = PMA_backquote($_where) . ' = \''
                    . PMA_sqlAddSlashes($_value) . '\'';
            }

            $new_parts = array();
            $new_value_parts = array();
            foreach ($new_fields as $_where => $_value) {
                $new_parts[] = PMA_backquote($_where);
                $new_value_parts[] = PMA_sqlAddSlashes($_value);
            }

            $table_copy_query = '
                SELECT ' . implode(', ', $select_parts) . '
                  FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                  . PMA_backquote($GLOBALS['cfgRelation'][$pma_table]) . '
                 WHERE ' . implode(' AND ', $where_parts);

            // must use PMA_DBI_QUERY_STORE here, since we execute another
            // query inside the loop
            $table_copy_rs = PMA_query_as_controluser(
                $table_copy_query, true, PMA_DBI_QUERY_STORE
            );

            while ($table_copy_row = @PMA_DBI_fetch_assoc($table_copy_rs)) {
                $value_parts = array();
                foreach ($table_copy_row as $_key => $_val) {
                    if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                        $value_parts[] = PMA_sqlAddSlashes($_val);
                    }
                }

                $new_table_query = 'INSERT IGNORE INTO '
                    . PMA_backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_backquote($GLOBALS['cfgRelation'][$pma_table]) . '
                    (' . implode(', ', $select_parts) . ',
                     ' . implode(', ', $new_parts) . ')
                    VALUES
                    (\'' . implode('\', \'', $value_parts) . '\',
                     \'' . implode('\', \'', $new_value_parts) . '\')';

                PMA_query_as_controluser($new_table_query);
                $last_id = PMA_DBI_insert_id();
            } // end while

            PMA_DBI_free_result($table_copy_rs);

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
    static public function moveCopy($source_db, $source_table, $target_db, $target_table, $what, $move, $mode)
    {
        global $err_url;

        /* Try moving table directly */
        if ($move && $what == 'data') {
            $tbl = new PMA_Table($source_table, $source_db);
            $result = $tbl->rename(
                $target_table, $target_db,
                PMA_Table::isView($source_db, $source_table)
            );
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
                    'source database `' . htmlspecialchars($source_db) . '` not found'
                );
            }
            if (! $GLOBALS['pma']->databases->exists($target_db)) {
                $GLOBALS['message'] = PMA_Message::rawError(
                    'target database `' . htmlspecialchars($target_db) . '` not found'
                );
            }
            return false;
        }

        $source = PMA_backquote($source_db) . '.' . PMA_backquote($source_table);
        if (! isset($target_db) || ! strlen($target_db)) {
            $target_db = $source_db;
        }

        // Doing a select_db could avoid some problems with replicated databases,
        // when moving table from replicated one to not replicated one
        PMA_DBI_select_db($target_db);

        $target = PMA_backquote($target_db) . '.' . PMA_backquote($target_table);

        // do not create the table if dataonly
        if ($what != 'dataonly') {
            include_once './libraries/export/sql.php';

            $no_constraints_comments = true;
            $GLOBALS['sql_constraints_query'] = '';

            $sql_structure = PMA_getTableDef(
                $source_db, $source_table, "\n", $err_url, false, false
            );
            unset($no_constraints_comments);
            $parsed_sql =  PMA_SQP_parse($sql_structure);
            $analyzed_sql = PMA_SQP_analyze($parsed_sql);
            $i = 0;
            if (empty($analyzed_sql[0]['create_table_fields'])) {
                // this is not a CREATE TABLE, so find the first VIEW
                $target_for_view = PMA_backquote($target_db);
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
                $server_sql_mode = PMA_DBI_fetch_value("SHOW VARIABLES LIKE 'sql_mode'", 0, 1);
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

            /* no need to PMA_backquote() */
            if (isset($target_for_view)) {
                // this a view definition; we just found the first db name
                // that follows DEFINER VIEW
                // so change it for the new db name
                        $parsed_sql[$i]['data'] = $target_for_view;
                // then we have to find all references to the source db
                // and change them to the target db, ensuring we stay into
                // the $parsed_sql limits
                $last = $parsed_sql['len'] - 1;
                $backquoted_source_db = PMA_backquote($source_db);
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
            $sql_structure = PMA_SQP_formatHtml($parsed_sql, 'query_only');
            // If table exists, and 'add drop table' is selected: Drop it!
            $drop_query = '';
            if (isset($GLOBALS['drop_if_exists'])
                && $GLOBALS['drop_if_exists'] == 'true'
            ) {
                if (PMA_Table::isView($target_db, $target_table)) {
                    $drop_query = 'DROP VIEW';
                } else {
                    $drop_query = 'DROP TABLE';
                }
                $drop_query .= ' IF EXISTS '
                    . PMA_backquote($target_db) . '.'
                    . PMA_backquote($target_table);
                PMA_DBI_query($drop_query);

                $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';

                // If an existing table gets deleted, maintain any
                // entries for the PMA_* tables
                $maintain_relations = true;
            }

            @PMA_DBI_query($sql_structure);
            $GLOBALS['sql_query'] .= "\n" . $sql_structure . ';';

            if (($move || isset($GLOBALS['add_constraints']))
                && !empty($GLOBALS['sql_constraints_query'])
            ) {
                $parsed_sql =  PMA_SQP_parse($GLOBALS['sql_constraints_query']);
                $i = 0;

                // find the first $table_delimiter, it must be the source table name
                while ($parsed_sql[$i]['type'] != $table_delimiter) {
                    $i++;
                    // maybe someday we should guard against going over limit
                    //if ($i == $parsed_sql['len']) {
                    //    break;
                    //}
                }

                // replace it by the target table name, no need to PMA_backquote()
                $parsed_sql[$i]['data'] = $target;

                // now we must remove all $table_delimiter that follow a CONSTRAINT
                // keyword, because a constraint name must be unique in a db

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
                $GLOBALS['sql_constraints_query'] = PMA_SQP_formatHtml(
                    $parsed_sql, 'query_only'
                );
                if ($mode == 'one_table') {
                    PMA_DBI_query($GLOBALS['sql_constraints_query']);
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
            PMA_DBI_query($sql_set_mode);
            $GLOBALS['sql_query'] .= "\n\n" . $sql_set_mode . ';';

            $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
            PMA_DBI_query($sql_insert_data);
            $GLOBALS['sql_query']      .= "\n\n" . $sql_insert_data . ';';
        }

        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            PMA_DBI_select_db($source_db);

            if (PMA_Table::isView($source_db, $source_table)) {
                $sql_drop_query = 'DROP VIEW';
            } else {
                $sql_drop_query = 'DROP TABLE';
            }
            $sql_drop_query .= ' ' . $source;
            PMA_DBI_query($sql_drop_query);

            // Move old entries from PMA-DBs to new table
            if ($GLOBALS['cfgRelation']['commwork']) {
                $remove_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info'])
                              . ' SET     table_name = \'' . PMA_sqlAddSlashes($target_table) . '\', '
                              . '        db_name    = \'' . PMA_sqlAddSlashes($target_db) . '\''
                              . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                              . ' AND table_name = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($remove_query);
                unset($remove_query);
            }

            // updating bookmarks is not possible since only a single table is moved,
            // and not the whole DB.

            if ($GLOBALS['cfgRelation']['displaywork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_info'])
                                . ' SET     db_name = \'' . PMA_sqlAddSlashes($target_db) . '\', '
                                . '         table_name = \'' . PMA_sqlAddSlashes($target_table) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            if ($GLOBALS['cfgRelation']['relwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
                                . ' SET     foreign_table = \'' . PMA_sqlAddSlashes($target_table) . '\','
                                . '         foreign_db = \'' . PMA_sqlAddSlashes($target_db) . '\''
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                . ' AND foreign_table = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);

                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
                                . ' SET     master_table = \'' . PMA_sqlAddSlashes($target_table) . '\','
                                . '         master_db = \'' . PMA_sqlAddSlashes($target_db) . '\''
                                . ' WHERE master_db  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            /**
             * @todo Can't get moving PDFs the right way. The page numbers
             * always get screwed up independently from duplication because the
             * numbers do not seem to be stored on a per-database basis. Would
             * the author of pdf support please have a look at it?
             */

            if ($GLOBALS['cfgRelation']['pdfwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddSlashes($target_table) . '\','
                                . '         db_name = \'' . PMA_sqlAddSlashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
                /*
                $pdf_query = 'SELECT pdf_page_number '
                           . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_coords'])
                           . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($target_db) . '\''
                           . ' AND table_name = \'' . PMA_sqlAddSlashes($target_table) . '\'';
                $pdf_rs = PMA_query_as_controluser($pdf_query);

                while ($pdf_copy_row = PMA_DBI_fetch_assoc($pdf_rs)) {
                    $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['pdf_pages'])
                                    . ' SET     db_name = \'' . PMA_sqlAddSlashes($target_db) . '\''
                                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                    . ' AND page_nr = \'' . PMA_sqlAddSlashes($pdf_copy_row['pdf_page_number']) . '\'';
                    $tb_rs    = PMA_query_as_controluser($table_query);
                    unset($table_query);
                    unset($tb_rs);
                }
                */
            }

            if ($GLOBALS['cfgRelation']['designerwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddSlashes($target_table) . '\','
                                . '         db_name = \'' . PMA_sqlAddSlashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            $GLOBALS['sql_query']      .= "\n\n" . $sql_drop_query . ';';
            // end if ($move)
        } else {
            // we are copying
            // Create new entries as duplicates from old PMA DBs
            if ($what != 'dataonly' && ! isset($maintain_relations)) {
                if ($GLOBALS['cfgRelation']['commwork']) {
                    // Get all comments and MIME-Types for current table
                    $comments_copy_query = 'SELECT
                                                column_name, comment' . ($GLOBALS['cfgRelation']['mimework'] ? ', mimetype, transformation, transformation_options' : '') . '
                                            FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info']) . '
                                            WHERE
                                                db_name = \'' . PMA_sqlAddSlashes($source_db) . '\' AND
                                                table_name = \'' . PMA_sqlAddSlashes($source_table) . '\'';
                    $comments_copy_rs    = PMA_query_as_controluser($comments_copy_query);

                    // Write every comment as new copied entry. [MIME]
                    while ($comments_copy_row = PMA_DBI_fetch_assoc($comments_copy_rs)) {
                        $new_comment_query = 'REPLACE INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info'])
                                    . ' (db_name, table_name, column_name, comment' . ($GLOBALS['cfgRelation']['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                    . ' VALUES('
                                    . '\'' . PMA_sqlAddSlashes($target_db) . '\','
                                    . '\'' . PMA_sqlAddSlashes($target_table) . '\','
                                    . '\'' . PMA_sqlAddSlashes($comments_copy_row['column_name']) . '\''
                                    . ($GLOBALS['cfgRelation']['mimework'] ? ',\'' . PMA_sqlAddSlashes($comments_copy_row['comment']) . '\','
                                            . '\'' . PMA_sqlAddSlashes($comments_copy_row['mimetype']) . '\','
                                            . '\'' . PMA_sqlAddSlashes($comments_copy_row['transformation']) . '\','
                                            . '\'' . PMA_sqlAddSlashes($comments_copy_row['transformation_options']) . '\'' : '')
                                    . ')';
                        PMA_query_as_controluser($new_comment_query);
                    } // end while
                    PMA_DBI_free_result($comments_copy_rs);
                    unset($comments_copy_rs);
                }

                // duplicating the bookmarks must not be done here, but
                // just once per db

                $get_fields = array('display_field');
                $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
                $new_fields = array('db_name' => $target_db, 'table_name' => $target_table);
                PMA_Table::duplicateInfo('displaywork', 'table_info', $get_fields, $where_fields, $new_fields);


                /**
                 * @todo revise this code when we support cross-db relations
                 */
                $get_fields = array('master_field', 'foreign_table', 'foreign_field');
                $where_fields = array('master_db' => $source_db, 'master_table' => $source_table);
                $new_fields = array('master_db' => $target_db, 'foreign_db' => $target_db, 'master_table' => $target_table);
                PMA_Table::duplicateInfo('relwork', 'relation', $get_fields, $where_fields, $new_fields);


                $get_fields = array('foreign_field', 'master_table', 'master_field');
                $where_fields = array('foreign_db' => $source_db, 'foreign_table' => $source_table);
                $new_fields = array('master_db' => $target_db, 'foreign_db' => $target_db, 'foreign_table' => $target_table);
                PMA_Table::duplicateInfo('relwork', 'relation', $get_fields, $where_fields, $new_fields);


                $get_fields = array('x', 'y', 'v', 'h');
                $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
                $new_fields = array('db_name' => $target_db, 'table_name' => $target_table);
                PMA_Table::duplicateInfo('designerwork', 'designer_coords', $get_fields, $where_fields, $new_fields);

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
                $last_id = PMA_Table::duplicateInfo('pdfwork', 'pdf_pages', $get_fields, $where_fields, $new_fields);

                if (isset($last_id) && $last_id >= 0) {
                    $get_fields = array('x', 'y');
                    $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
                    $new_fields = array('db_name' => $target_db, 'table_name' => $target_table, 'pdf_page_number' => $last_id);
                    PMA_Table::duplicateInfo('pdfwork', 'table_coords', $get_fields, $where_fields, $new_fields);
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
     * @return  boolean whether the string is valid or not
     */
    function isValidName($table_name)
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
     * @param bool   $is_view  is this for a VIEW rename?
     * @todo    remove the $is_view parameter (also in callers)
     *
     * @return bool success
     */
    function rename($new_name, $new_db = null, $is_view = false)
    {
        if (null !== $new_db && $new_db !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $GLOBALS['pma']->databases->exists($new_db)) {
                $this->errors[] = __('Invalid database') . ': ' . $new_db;
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
            $this->errors[] = __('Invalid table name') . ': ' . $new_table->getFullName();
            return false;
        }

        // If the table is moved to a different database drop its triggers first
        $triggers = PMA_DBI_get_triggers($this->getDbName(), $this->getName(), '');
        $handle_triggers = $this->getDbName() != $new_db && $triggers;
        if ($handle_triggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS ' . PMA_backquote($this->getDbName()) . '.'
                    . PMA_backquote($trigger['name']) . ';';
                PMA_DBI_query($sql);
            }
        }

        /*
         * tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
         */
        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                  TO ' . $new_table->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if (! PMA_DBI_query($GLOBALS['sql_query'])) {
            // Restore triggers in the old database
            if ($handle_triggers) {
                PMA_DBI_select_db($this->getDbName());
                foreach ($triggers as $trigger) {
                    PMA_DBI_query($trigger['create']);
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

        /**
         * @todo move into extra function PMA_Relation::renameTable($new_name, $old_name, $new_db, $old_db)
         */
        // Move old entries from comments to new table
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
        if ($GLOBALS['cfgRelation']['commwork']) {
            $remove_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['column_info']) . '
                   SET `db_name`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($remove_query);
            unset($remove_query);
        }

        if ($GLOBALS['cfgRelation']['displaywork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['table_info']) . '
                   SET `db_name`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['relwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['relation']) . '
                   SET `foreign_db`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `foreign_table` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `foreign_db`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `foreign_table` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);

            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['relation']) . '
                   SET `master_db`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `master_table` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `master_db`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `master_table` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['pdfwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['table_coords']) . '
                   SET `db_name`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['designerwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']) . '
                   SET `db_name`    = \'' . PMA_sqlAddSlashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddSlashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddSlashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddSlashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        $this->messages[] = sprintf(
            __('Table %s has been renamed to %s'),
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
     *
     * @return array
     */
    public function getUniqueColumns($backquoted = true)
    {
        $sql = PMA_DBI_get_table_indexes_sql($this->getDbName(), $this->getName(), 'Non_unique = 0');
        $uniques = PMA_DBI_fetch_result($sql, array('Key_name', null), 'Column_name');

        $return = array();
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }
            $return[] = $this->getFullName($backquoted) . '.'
                . ($backquoted ? PMA_backquote($index[0]) : $index[0]);
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
        $sql = PMA_DBI_get_table_indexes_sql($this->getDbName(), $this->getName(), 'Seq_in_index = 1');
        $indexed = PMA_DBI_fetch_result($sql, 'Column_name', 'Column_name');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = $this->getFullName($backquoted) . '.'
                . ($backquoted ? PMA_backquote($column) : $column);
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
        $indexed = PMA_DBI_fetch_result($sql, 'Field', 'Field');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = $this->getFullName($backquoted) . '.'
                . ($backquoted ? PMA_backquote($column) : $column);
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
        $pma_table = PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) .".".
                     PMA_backquote($GLOBALS['cfg']['Server']['table_uiprefs']);

        // Read from phpMyAdmin database
        $sql_query = " SELECT `prefs` FROM " . $pma_table
            . " WHERE `username` = '" . $GLOBALS['cfg']['Server']['user'] . "'"
            . " AND `db_name` = '" . PMA_sqlAddSlashes($this->db_name) . "'"
            . " AND `table_name` = '" . PMA_sqlAddSlashes($this->name) . "'";

        $row = PMA_DBI_fetch_array(PMA_query_as_controluser($sql_query));
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
        $pma_table = PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) . "."
            . PMA_backquote($GLOBALS['cfg']['Server']['table_uiprefs']);

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query = " REPLACE INTO " . $pma_table
            . " VALUES ('" . $username . "', '" . PMA_sqlAddSlashes($this->db_name)
            . "', '" . PMA_sqlAddSlashes($this->name) . "', '"
            . PMA_sqlAddSlashes(json_encode($this->uiprefs)) . "', NULL)";

        $success = PMA_DBI_try_query($sql_query, $GLOBALS['controllink']);

        if (!$success) {
            $message = PMA_Message::error(__('Could not save table UI preferences'));
            $message->addMessage('<br /><br />');
            $message->addMessage(
                PMA_Message::rawError(PMA_DBI_getError($GLOBALS['controllink']))
            );
            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured maximum rows
        $sql_query = 'SELECT COUNT(*) FROM ' . $pma_table;
        $rows_count = PMA_DBI_fetch_value($sql_query);
        $max_rows = $GLOBALS['cfg']['Server']['MaxTableUiprefs'];
        if ($rows_count > $max_rows) {
            $num_rows_to_delete = $rows_count - $max_rows;
            $sql_query
                = ' DELETE FROM ' . $pma_table .
                ' ORDER BY last_update ASC' .
                ' LIMIT ' . $num_rows_to_delete;
            $success = PMA_DBI_try_query($sql_query, $GLOBALS['controllink']);

            if (!$success) {
                $message = PMA_Message::error(
                    sprintf(
                        __('Failed to cleanup table UI preferences (see $cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'),
                        PMA_showDocu('cfg_Servers_MaxTableUiprefs')
                    )
                );
                $message->addMessage('<br /><br />');
                $message->addMessage(PMA_Message::rawError(PMA_DBI_getError($GLOBALS['controllink'])));
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
     * @return nothing
     */
    protected function loadUiPrefs()
    {
        $server_id = $GLOBALS['server'];
        // set session variable if it's still undefined
        if (! isset($_SESSION['tmp_user_values']['table_uiprefs'][$server_id][$this->db_name][$this->name])) {
            $_SESSION['tmp_user_values']['table_uiprefs'][$server_id][$this->db_name][$this->name] =
                // check whether we can get from pmadb
                (strlen($GLOBALS['cfg']['Server']['pmadb'])
                && strlen($GLOBALS['cfg']['Server']['table_uiprefs']))
                    ?  $this->getUiPrefsFromDb()
                    : array();
        }
        $this->uiprefs =& $_SESSION['tmp_user_values']['table_uiprefs'][$server_id][$this->db_name][$this->name];
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
                // check if the column name is exist in this table
                $tmp = explode(' ', $this->uiprefs[$property]);
                $colname = $tmp[0];
                $avail_columns = $this->getColumns();
                foreach ($avail_columns as $each_col) {
                    // check if $each_col ends with $colname
                    if (substr_compare($each_col, $colname, strlen($each_col) - strlen($colname)) === 0) {
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
            if (! PMA_Table::isView($this->db_name, $this->name) && isset($this->uiprefs[$property])) {
                // check if the table has not been modified
                if (self::sGetStatusInfo($this->db_name, $this->name, 'Create_time') == $this->uiprefs['CREATE_TIME']) {
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
     * @param string $table_create_time Needed for PROP_COLUMN_ORDER and PROP_COLUMN_VISIB
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
            && ($property == self::PROP_COLUMN_ORDER || $property == self::PROP_COLUMN_VISIB)
        ) {
            $curr_create_time = self::sGetStatusInfo($this->db_name, $this->name, 'CREATE_TIME');
            if (isset($table_create_time)
                && $table_create_time == $curr_create_time
            ) {
                $this->uiprefs['CREATE_TIME'] = $curr_create_time;
            } else {
                // there is no $table_create_time, or
                // supplied $table_create_time is older than current create time,
                // so don't save
                return PMA_Message::error(sprintf(
                    __('Cannot save UI property "%s". The changes made will not be persistent after you refresh this page. Please check if the table structure has been changed.'), $property));
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
}
?>
