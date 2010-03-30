<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * @todo make use of PMA_Message and PMA_Error
 * @package phpMyAdmin
 */
class PMA_Table
{

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
     * @param   string  $table_name table name
     * @param   string  $db_name    database name
     */
    function __construct($table_name, $db_name)
    {
        $this->setName($table_name);
        $this->setDbName($db_name);
    }

    /**
     * @see PMA_Table::getName()
     */
    function __toString()
    {
        return $this->getName();
    }

    function getLastError()
    {
        return end($this->errors);
    }

    function getLastMessage()
    {
        return end($this->messages);
    }

    /**
     * sets table name
     *
     * @uses    $this->name to set it
     * @param   string  $table_name new table name
     */
    function setName($table_name)
    {
        $this->name = $table_name;
    }

    /**
     * returns table name
     *
     * @uses    $this->name as return value
     * @param   boolean whether to quote name with backticks ``
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
     * @uses    $this->db_name  to set it
     * @param   string  $db_name
     */
    function setDbName($db_name)
    {
        $this->db_name = $db_name;
    }

    /**
     * returns database name for this table
     *
     * @uses    $this->db_name  as return value
     * @param   boolean whether to quote name with backticks ``
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
     * @param   boolean whether to quote name with backticks ``
     */
    function getFullName($backquoted = false)
    {
        return $this->getDbName($backquoted) . '.' . $this->getName($backquoted);
    }

    static public function isView($db = null, $table = null)
    {
        if (strlen($db) && strlen($table)) {
            return PMA_Table::_isView($db, $table);
        }

        if (isset($this) && strpos($this->get('TABLE TYPE'), 'VIEW')) {
            return true;
        }

        return false;
    }

    /**
     * sets given $value for given $param
     *
     * @uses    $this->settings to add or change value
     * @param   string  param name
     * @param   mixed   param value
     */
    function set($param, $value)
    {
        $this->settings[$param] = $value;
    }

    /**
     * returns value for given setting/param
     *
     * @uses    $this->settings to return value
     * @param   string  name for value to return
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
     */
    function loadStructure()
    {
        $table_info = PMA_DBI_get_tables_full($this->getDbName(), $this->getName());

        if (false === $table_info) {
            return false;
        }

        $this->settings = $table_info;

        if ($this->get('TABLE_ROWS') === null) {
            $this->set('TABLE_ROWS', PMA_Table::countRecords($this->getDbName(),
                $this->getName(), true));
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
    }

    /**
     * Checks if this "table" is a view
     *
     * @deprecated
     * @todo see what we could do with the possible existence of $table_is_view
     * @param   string   the database name
     * @param   string   the table name
     *
     * @return  boolean  whether this is a view
     *
     * @access  public
     */
    static protected function _isView($db, $table)
    {
        // maybe we already know if the table is a view
        if (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']) {
            return true;
        }

        // Since phpMyAdmin 3.2 the field TABLE_TYPE is properly filled by PMA_DBI_get_tables_full()
        $type = PMA_Table::sGetStatusInfo($db, $table, 'TABLE_TYPE');
        return $type == 'VIEW';
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias), this is a merge table.
     *
     * @param   string   the database name
     * @param   string   the table name
     * @return  boolean  true if it is a merge table 
     * @access  public
     */
    static public function isMerge($db = null, $table = null)
    {
        // if called static, with parameters
        if (! empty($db) && ! empty($table)) {
            $engine = PMA_Table::sGetStatusInfo($db, $table, 'ENGINE', null, true);
        }
        // if called as an object
        // does not work yet, because $this->settings[] is not filled correctly
        else if (! empty($this)) {
            $engine = $this->get('ENGINE');
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
     *
     * this info is collected from information_schema
     *
     * @todo PMA_DBI_get_tables_full needs to be merged somehow into this class or at least better documented
     * @param string $db
     * @param string $table
     * @param string $info
     * @param boolean $force_read
     * @param boolean if true, disables error message
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

        if (! isset(PMA_Table::$cache[$db][$table][$info])) {
            if (! $disable_error) {
                trigger_error('unknown table status: ' . $info, E_USER_WARNING);
            }
            return false;
        }

        return PMA_Table::$cache[$db][$table][$info];
    }

    /**
     * generates column/field specification for ALTER or CREATE TABLE syntax
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the default
     * current_timestamp is checked
     * @static
     * @param   string  $name       name
     * @param   string  $type       type ('INT', 'VARCHAR', 'BIT', ...)
     * @param   string  $length     length ('2', '5,2', '', ...)
     * @param   string  $attribute
     * @param   string  $collation
     * @param   string  $null       with 'NULL' or 'NOT NULL'
     * @param   string  $default_type   whether default is CURRENT_TIMESTAMP,
     *                                  NULL, NONE, USER_DEFINED
     * @param   boolean $default_value  default value for USER_DEFINED default type
     * @param   string  $extra      'AUTO_INCREMENT'
     * @param   string  $comment    field comment
     * @param   array   &$field_primary list of fields for PRIMARY KEY
     * @param   string  $index
     * @return  string  field specification
     */
    static function generateFieldSpec($name, $type, $length = '', $attribute = '',
        $collation = '', $null = false, $default_type = 'USER_DEFINED',
        $default_value = '', $extra = '', $comment = '',
        &$field_primary, $index, $default_orig = false)
    {

        $is_timestamp = strpos(' ' . strtoupper($type), 'TIMESTAMP') == 1;

        /**
         * @todo include db-name
         */
        $query = PMA_backquote($name) . ' ' . $type;

        if ($length != ''
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i', $type)) {
            $query .= '(' . $length . ')';
        }

        if ($attribute != '') {
            $query .= ' ' . $attribute;
        }

        if (!empty($collation) && $collation != 'NULL'
          && preg_match('@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i', $type)) {
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
                    $query .= ' DEFAULT b\'' . preg_replace('/[^01]/', '0', $default_value) . '\'';
                } else {
                    $query .= ' DEFAULT \'' . PMA_sqlAddslashes($default_value) . '\'';
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
                    for ($j = 0; $j < $primary_cnt && $field_primary[$j] != $index; $j++) {
                        //void
                    }
                    if (isset($field_primary[$j]) && $field_primary[$j] == $index) {
                        $query .= ' PRIMARY KEY';
                        unset($field_primary[$j]);
                    }
                // but the PK could contain other columns so do not append
                // a PRIMARY KEY clause, just add a member to $field_primary
                } else {
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
            $query .= " COMMENT '" . PMA_sqlAddslashes($comment) . "'";
        }
        return $query;
    } // end function

    /**
     * Counts and returns (or displays) the number of records in a table
     *
     * Revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   boolean  whether to force an exact count
     *
     * @return  mixed    the number of records if "retain" param is true,
     *                   otherwise true
     *
     * @access  public
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
                    PMA_Table::$cache[$db][$table] = $tmp_tables[$table];
                }
                $row_count = PMA_Table::$cache[$db][$table]['Rows'];
            }

            // for a VIEW, $row_count is always false at this point
            if (false === $row_count || $row_count < $GLOBALS['cfg']['MaxExactCount']) {
                if (! $is_view) {
                    $row_count = PMA_DBI_fetch_value(
                        'SELECT COUNT(*) FROM ' . PMA_backquote($db) . '.'
                        . PMA_backquote($table));
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
                                null, PMA_DBI_QUERY_STORE);
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
     * @see PMA_Table::generateFieldSpec()
     */
    static public function generateAlter($oldcol, $newcol, $type, $length,
        $attribute, $collation, $null, $default_type, $default_value,
        $extra, $comment = '', &$field_primary, $index, $default_orig)
    {
        return PMA_backquote($oldcol) . ' '
            . PMA_Table::generateFieldSpec($newcol, $type, $length, $attribute,
                $collation, $null, $default_type, $default_value, $extra,
                $comment, $field_primary, $index, $default_orig);
    } // end function

    /**
     * Inserts existing entries in a PMA_* table by reading a value from an old entry
     *
     * @param   string  The array index, which Relation feature to check
     *                  ('relwork', 'commwork', ...)
     * @param   string  The array index, which PMA-table to update
     *                  ('bookmark', 'relation', ...)
     * @param   array   Which fields will be SELECT'ed from the old entry
     * @param   array   Which fields will be used for the WHERE query
     *                  (array('FIELDNAME' => 'FIELDVALUE'))
     * @param   array   Which fields will be used as new VALUES. These are the important
     *                  keys which differ from the old entry.
     *                  (array('FIELDNAME' => 'NEW FIELDVALUE'))

     * @global  string  relation variable
     *
     * @author          Garvin Hicking <me@supergarv.de>
     */
    static public function duplicateInfo($work, $pma_table, $get_fields, $where_fields,
      $new_fields)
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
                    . PMA_sqlAddslashes($_value) . '\'';
            }

            $new_parts = array();
            $new_value_parts = array();
            foreach ($new_fields as $_where => $_value) {
                $new_parts[] = PMA_backquote($_where);
                $new_value_parts[] = PMA_sqlAddslashes($_value);
            }

            $table_copy_query = '
                SELECT ' . implode(', ', $select_parts) . '
                  FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                  . PMA_backquote($GLOBALS['cfgRelation'][$pma_table]) . '
                 WHERE ' . implode(' AND ', $where_parts);

            // must use PMA_DBI_QUERY_STORE here, since we execute another
            // query inside the loop
            $table_copy_rs    = PMA_query_as_controluser($table_copy_query, true,
                PMA_DBI_QUERY_STORE);

            while ($table_copy_row = @PMA_DBI_fetch_assoc($table_copy_rs)) {
                $value_parts = array();
                foreach ($table_copy_row as $_key => $_val) {
                    if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                        $value_parts[] = PMA_sqlAddslashes($_val);
                    }
                }

                $new_table_query = '
                    INSERT IGNORE INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db'])
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
     * @todo use RENAME for move operations
     *        - would work only if the databases are on the same filesystem,
     *          how can we check that? try the operation and
     *          catch an error?
     *        - for views, only if MYSQL > 50013
     *        - still have to handle pmadb synch.
     *
     * @author          Michal Cihar <michal@cihar.com>
     */
    static public function moveCopy($source_db, $source_table, $target_db, $target_table, $what, $move, $mode)
    {
        global $err_url;

        // set export settings we need
        $GLOBALS['sql_backquotes'] = 1;
        $GLOBALS['asfile']         = 1;

        // Ensure the target is valid
        if (! $GLOBALS['pma']->databases->exists($source_db, $target_db)) {
            if (! $GLOBALS['pma']->databases->exists($source_db)) {
                $GLOBALS['message'] = PMA_Message::rawError('source database `'
                    . htmlspecialchars($source_db) . '` not found');
            }
            if (! $GLOBALS['pma']->databases->exists($target_db)) {
                $GLOBALS['message'] = PMA_Message::rawError('target database `'
                    . htmlspecialchars($target_db) . '` not found');
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
            require_once './libraries/export/sql.php';

            $no_constraints_comments = true;
            $GLOBALS['sql_constraints_query'] = '';

            $sql_structure = PMA_getTableDef($source_db, $source_table, "\n", $err_url, false, false);
            unset($no_constraints_comments);
            $parsed_sql =  PMA_SQP_parse($sql_structure);
            $analyzed_sql = PMA_SQP_analyze($parsed_sql);
            $i = 0;
            if (empty($analyzed_sql[0]['create_table_fields'])) {
            // this is not a CREATE TABLE, so find the first VIEW
                $target_for_view = PMA_backquote($target_db);
                while (true) {
                if ($parsed_sql[$i]['type'] == 'alpha_reservedWord' && $parsed_sql[$i]['data'] == 'VIEW') {
                        break;
                    }
                    $i++;
                }
            }
            unset($analyzed_sql);
            $server_sql_mode = PMA_DBI_fetch_value("SHOW VARIABLES LIKE 'sql_mode'", 0, 1);
            // ANSI_QUOTES might be a subset of sql_mode, for example 
            // REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ANSI
            if (false !== strpos($server_sql_mode, 'ANSI_QUOTES')) {
                $table_delimiter = 'quote_double';
            } else {
                $table_delimiter = 'quote_backtick';
            }
            unset($server_sql_mode);

            /* nijel: Find table name in query and replace it */
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
                            if ($parsed_sql[$i]['type'] == $table_delimiter && $parsed_sql[$i]['data'] == $backquoted_source_db) {
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
              && $GLOBALS['drop_if_exists'] == 'true') {
                if (PMA_Table::_isView($target_db,$target_table)) {
                    $drop_query = 'DROP VIEW';
                } else {
                    $drop_query = 'DROP TABLE';
                }
                $drop_query .= ' IF EXISTS '
                    . PMA_backquote($target_db) . '.'
                    . PMA_backquote($target_table);
                PMA_DBI_query($drop_query);

                $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';

                // garvin: If an existing table gets deleted, maintain any
                // entries for the PMA_* tables
                $maintain_relations = true;
            }

            @PMA_DBI_query($sql_structure);
            $GLOBALS['sql_query'] .= "\n" . $sql_structure . ';';

            if (($move || isset($GLOBALS['add_constraints']))
              && !empty($GLOBALS['sql_constraints_query'])) {
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
                      && strtoupper($parsed_sql[$j]['data']) == 'CONSTRAINT') {
                        if ($parsed_sql[$j+1]['type'] == $table_delimiter) {
                            $parsed_sql[$j+1]['data'] = '';
                        }
                    }
                }

                // Generate query back
                $GLOBALS['sql_constraints_query'] = PMA_SQP_formatHtml($parsed_sql,
                    'query_only');
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
        if (($what == 'data' || $what == 'dataonly') && ! PMA_Table::_isView($target_db,$target_table)) {
            $sql_insert_data =
                'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
            PMA_DBI_query($sql_insert_data);
            $GLOBALS['sql_query']      .= "\n\n" . $sql_insert_data . ';';
        }

        require_once './libraries/relation.lib.php';
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            PMA_DBI_select_db($source_db);

            if (PMA_Table::_isView($source_db,$source_table)) {
                $sql_drop_query = 'DROP VIEW';
            } else {
                $sql_drop_query = 'DROP TABLE';
            }
            $sql_drop_query .= ' ' . $source;
            PMA_DBI_query($sql_drop_query);

            // garvin: Move old entries from PMA-DBs to new table
            if ($GLOBALS['cfgRelation']['commwork']) {
                $remove_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info'])
                              . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\', '
                              . '        db_name    = \'' . PMA_sqlAddslashes($target_db) . '\''
                              . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                              . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($remove_query);
                unset($remove_query);
            }

            // garvin: updating bookmarks is not possible since only a single table is moved,
            // and not the whole DB.

            if ($GLOBALS['cfgRelation']['displaywork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_info'])
                                . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\', '
                                . '         table_name = \'' . PMA_sqlAddslashes($target_table) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            if ($GLOBALS['cfgRelation']['relwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
                                . ' SET     foreign_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         foreign_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND foreign_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);

                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
                                . ' SET     master_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         master_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            /**
             * @todo garvin: Can't get moving PDFs the right way. The page numbers
             * always get screwed up independently from duplication because the
             * numbers do not seem to be stored on a per-database basis. Would
             * the author of pdf support please have a look at it?
             */

            if ($GLOBALS['cfgRelation']['pdfwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
                /*
                $pdf_query = 'SELECT pdf_page_number '
                           . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['table_coords'])
                           . ' WHERE db_name  = \'' . PMA_sqlAddslashes($target_db) . '\''
                           . ' AND table_name = \'' . PMA_sqlAddslashes($target_table) . '\'';
                $pdf_rs = PMA_query_as_controluser($pdf_query);

                while ($pdf_copy_row = PMA_DBI_fetch_assoc($pdf_rs)) {
                    $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['pdf_pages'])
                                    . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                    . ' AND page_nr = \'' . PMA_sqlAddslashes($pdf_copy_row['pdf_page_number']) . '\'';
                    $tb_rs    = PMA_query_as_controluser($table_query);
                    unset($table_query);
                    unset($tb_rs);
                }
                */
            }

            if ($GLOBALS['cfgRelation']['designerwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                PMA_query_as_controluser($table_query);
                unset($table_query);
            }

            $GLOBALS['sql_query']      .= "\n\n" . $sql_drop_query . ';';
        // end if ($move)
        } else {
            // we are copying
            // garvin: Create new entries as duplicates from old PMA DBs
            if ($what != 'dataonly' && !isset($maintain_relations)) {
                if ($GLOBALS['cfgRelation']['commwork']) {
                    // Get all comments and MIME-Types for current table
                    $comments_copy_query = 'SELECT
                                                column_name, ' . PMA_backquote('comment') . ($GLOBALS['cfgRelation']['mimework'] ? ', mimetype, transformation, transformation_options' : '') . '
                                            FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info']) . '
                                            WHERE
                                                db_name = \'' . PMA_sqlAddslashes($source_db) . '\' AND
                                                table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                    $comments_copy_rs    = PMA_query_as_controluser($comments_copy_query);

                    // Write every comment as new copied entry. [MIME]
                    while ($comments_copy_row = PMA_DBI_fetch_assoc($comments_copy_rs)) {
                        $new_comment_query = 'REPLACE INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['column_info'])
                                    . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ($GLOBALS['cfgRelation']['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                    . ' VALUES('
                                    . '\'' . PMA_sqlAddslashes($target_db) . '\','
                                    . '\'' . PMA_sqlAddslashes($target_table) . '\','
                                    . '\'' . PMA_sqlAddslashes($comments_copy_row['column_name']) . '\''
                                    . ($GLOBALS['cfgRelation']['mimework'] ? ',\'' . PMA_sqlAddslashes($comments_copy_row['comment']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['mimetype']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation_options']) . '\'' : '')
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
                 * @todo garvin: Can't get duplicating PDFs the right way. The
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
     * @todo    add check for valid chars in filename on current system/os
     * @see     http://dev.mysql.com/doc/refman/5.0/en/legal-names.html
     * @param   string  $table_name name to check
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
     * @param   string  new table name
     * @param   string  new database name
     * @return  boolean success
     */
    function rename($new_name, $new_db = null)
    {
        if (null !== $new_db && $new_db !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $GLOBALS['pma']->databases->exists($new_db)) {
                $this->errors[] = $GLOBALS['strInvalidDatabase'] . ': ' . $new_db;
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
            $this->errors[] = $GLOBALS['strInvalidTableName'] . ': ' . $new_table->getFullName();
            return false;
        }

        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                      TO ' . $new_table->getFullName(true) . ';';
        if (! PMA_DBI_query($GLOBALS['sql_query'])) {
            $this->errors[] = sprintf($GLOBALS['strErrorRenamingTable'], $this->getFullName(), $new_table->getFullName());
            return false;
        }

        $old_name = $this->getName();
        $old_db = $this->getDbName();
        $this->setName($new_name);
        $this->setDbName($new_db);

        /**
         * @todo move into extra function PMA_Relation::renameTable($new_name, $old_name, $new_db, $old_db)
         */
        // garvin: Move old entries from comments to new table
        require_once './libraries/relation.lib.php';
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
        if ($GLOBALS['cfgRelation']['commwork']) {
            $remove_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['column_info']) . '
                   SET `db_name`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($remove_query);
            unset($remove_query);
        }

        if ($GLOBALS['cfgRelation']['displaywork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['table_info']) . '
                   SET `db_name`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['relwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['relation']) . '
                   SET `foreign_db`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `foreign_table` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `foreign_db`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `foreign_table` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);

            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['relation']) . '
                   SET `master_db`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `master_table` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `master_db`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `master_table` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['pdfwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['table_coords']) . '
                   SET `db_name`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        if ($GLOBALS['cfgRelation']['designerwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']) . '
                   SET `db_name`    = \'' . PMA_sqlAddslashes($new_db) . '\',
                       `table_name` = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE `db_name`    = \'' . PMA_sqlAddslashes($old_db) . '\'
                   AND `table_name` = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_controluser($table_query);
            unset($table_query);
        }

        $this->messages[] = sprintf($GLOBALS['strRenameTableOK'],
            htmlspecialchars($old_name), htmlspecialchars($new_name));
        return true;
    }

    /**
     * Get all unique columns
     *
     * returns an array with all columns with unqiue content, in fact these are
     * all columns being single indexed in PRIMARY or UNIQUE
     *
     * f.e.
     *  - PRIMARY(id) // id
     *  - UNIQUE(name) // name
     *  - PRIMARY(fk_id1, fk_id2) // NONE
     *  - UNIQUE(x,y) // NONE
     *
     *
     * @param   boolean whether to quote name with backticks ``
     * @return array
     */
    public function getUniqueColumns($backquoted = true)
    {
        $sql = 'SHOW INDEX FROM ' . $this->getFullName(true) . ' WHERE Non_unique = 0';
        $uniques = PMA_DBI_fetch_result($sql, array('Key_name', null), 'Column_name');

        $return = array();
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }
            $return[] = $this->getFullName($backquoted) . '.' . ($backquoted ? PMA_backquote($index[0]) : $index[0]);
        }

        return $return;
    }

    /**
     * Get all indexed columns
     *
     * returns an array with all columns make use of an index, in fact only
     * first columns in an index
     *
     * f.e. index(col1, col2) would only return col1
     * @param   boolean whether to quote name with backticks ``
     * @return array
     */
    public function getIndexedColumns($backquoted = true)
    {
        $sql = 'SHOW INDEX FROM ' . $this->getFullName(true) . ' WHERE Seq_in_index = 1';
        $indexed = PMA_DBI_fetch_result($sql, 'Column_name', 'Column_name');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = $this->getFullName($backquoted) . '.' . ($backquoted ? PMA_backquote($column) : $column);
        }

        return $return;
    }
}
?>
