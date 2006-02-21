<?php


class PMA_Table {

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

    /**
     * sets table anme
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
     * @param   boolean wether to quote name with backticks ``
     * @return  string  table name
     */
    function getName($quoted = false)
    {
        if ($quoted) {
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
     * @param   boolean wether to quote name with backticks ``
     * @return  string  database name for this table
     */
    function getDbName($quoted = false)
    {
        if ($quoted) {
            return PMA_backquote($this->db_name);
        }
        return $this->db_name;
    }

    /**
     * returns full name for table, including database name
     *
     * @param   boolean wether to quote name with backticks ``
     */
    function getFullName($quoted = false)
    {
        return $this->getDbName($quoted) . '.' . $this->getName($quoted);
    }

    function isView($db = null, $table = null)
    {
        if (null !== $db && null !== $table) {
            return PMA_Table::_isView($db, $table);
        }

        if (strpos($this->get('TABLE TYPE'), 'VIEW')) {
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
                $this->getName(), true, true));
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
     * old PHP 4style constructor
     *
     * @see     PMA_Table::__construct()
     */
    function PMA_Table()
    {
        $this->__construct();
    }

    /**
     * Checks if this "table" is a view
     *
     * @deprecated
     * @param   string   the database name
     * @param   string   the table name
     *
     * @return  boolean  whether this is a view
     *
     * @access  public
     */
    function _isView($db, $table) {
        // maybe we already know if the table is a view
        // TODO: see what we could do with the possible existence
        // of $table_is_view
        if (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']) {
            return true;
        }
        // old MySQL version: no view
        if (PMA_MYSQL_INT_VERSION < 50000) {
            return false;
        }
        if (false === PMA_DBI_fetch_value('SELECT TABLE_NAME FROM `information_schema`.`VIEWS` WHERE `TABLE_SCHEMA` = \'' . $db . '\' AND `TABLE_NAME` = \'' . $table . '\';')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @TODO    add documentation
     *
     * @static
     */
    function generateFieldSpec($name, $type, $length, $attribute,
        $collation, $null, $default, $default_current_timestamp, $extra,
        $comment='', &$field_primary, $index, $default_orig = false)
    {

        // $default_current_timestamp has priority over $default
        // TODO: on the interface, some js to clear the default value
        // when the default current_timestamp is checked

        $query = PMA_backquote($name) . ' ' . $type;

        if ($length != ''
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i', $type)) {
            $query .= '(' . $length . ')';
        }

        if ($attribute != '') {
            $query .= ' ' . $attribute;
        }

        if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($collation)
          && $collation != 'NULL'
          && preg_match('@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i', $type)) {
            $query .= PMA_generateCharsetQueryPart($collation);
        }

        if (!($null === false)) {
            if (!empty($null)) {
                $query .= ' NOT NULL';
            } else {
                $query .= ' NULL';
            }
        }

        if ($default_current_timestamp && strpos(' ' . strtoupper($type), 'TIMESTAMP') == 1) {
            $query .= ' DEFAULT CURRENT_TIMESTAMP';
            // 0 is empty in PHP
            // auto_increment field cannot have a default value
        } elseif ($extra !== 'AUTO_INCREMENT' && (!empty($default) || $default == '0' || $default != $default_orig)) {
            if (strtoupper($default) == 'NULL') {
                $query .= ' DEFAULT NULL';
            } else {
                $query .= ' DEFAULT \'' . PMA_sqlAddslashes($default) . '\'';
            }
        }

        if (!empty($extra)) {
            $query .= ' ' . $extra;
            // An auto_increment field must be use as a primary key
            if ($extra == 'AUTO_INCREMENT' && isset($field_primary)) {
                $primary_cnt = count($field_primary);
                for ($j = 0; $j < $primary_cnt && $field_primary[$j] != $index; $j++) {
                    // void
                } // end for
                if (isset($field_primary[$j]) && $field_primary[$j] == $index) {
                    $query .= ' PRIMARY KEY';
                    unset($field_primary[$j]);
                } // end if
            } // end if (auto_increment)
        }
        if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($comment)) {
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
     * @param   boolean  whether to retain or to displays the result
     * @param   boolean  whether to force an exact count
     *
     * @return  mixed    the number of records if retain is required, true else
     *
     * @access  public
     */
    function countRecords($db, $table, $ret = false, $force_exact = false)
    {
        global $err_url, $cfg;
        if (!$force_exact) {
            $result       = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . PMA_sqlAddslashes($table, true) . '\';');
            $showtable    = PMA_DBI_fetch_assoc($result);
            $num     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
            if ($num < $cfg['MaxExactCount']) {
                unset($num);
            }
            PMA_DBI_free_result($result);
        }

        $tbl_is_view = PMA_Table::isView($db, $table);

        if (!isset($num)) {
            if (! $tbl_is_view) {
                $num = PMA_DBI_fetch_value('SELECT COUNT(*) AS num FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table));
                // necessary?
                if (! $num) {
                    $num = 0;
                }
            // since counting all rows of a view could be too long
            } else {
                $result = PMA_DBI_query('SELECT 1 FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . ' LIMIT ' . $cfg['MaxExactCount'], null, PMA_DBI_QUERY_STORE);
                $num = PMA_DBI_num_rows($result);
            }
        }
        if ($ret) {
            return $num;
        } else {
            // Note: as of PMA 2.8.0, we no longer seem to be using
            // PMA_Table::countRecords() in display mode.
            echo number_format($num, 0, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator']);
            if ($tbl_is_view) {
                echo '&nbsp;' . sprintf($GLOBALS['strViewMaxExactCount'], $cfg['MaxExactCount'], '[a@./Documentation.html#cfg_MaxExactCount@_blank]', '[/a]');
            }
            return true;
        }
    } // end of the 'PMA_Table::countRecords()' function

    /**
     * @TODO    add documentation
     */
    function generateAlter($oldcol, $newcol, $type, $length,
        $attribute, $collation, $null, $default, $default_current_timestamp,
        $extra, $comment='', $default_orig)
    {
        $empty_a = array();
        return PMA_backquote($oldcol) . ' '
            . PMA_generateFieldSpec($newcol, $type, $length, $attribute,
                $collation, $null, $default, $default_current_timestamp, $extra,
                $comment, $empty_a, -1, $default_orig);
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
    function duplicateInfo($work, $pma_table, $get_fields, $where_fields, $new_fields) {
        global $cfgRelation;

        $last_id = -1;

        if ($cfgRelation[$work]) {
            $select_parts = array();
            $row_fields = array();
            foreach ($get_fields AS $nr => $get_field) {
                $select_parts[] = PMA_backquote($get_field);
                $row_fields[$get_field] = 'cc';
            }

            $where_parts = array();
            foreach ($where_fields AS $_where => $_value) {
                $where_parts[] = PMA_backquote($_where) . ' = \'' . PMA_sqlAddslashes($_value) . '\'';
            }

            $new_parts = array();
            $new_value_parts = array();
            foreach ($new_fields AS $_where => $_value) {
                $new_parts[] = PMA_backquote($_where);
                $new_value_parts[] = PMA_sqlAddslashes($_value);
            }

            $table_copy_query = 'SELECT ' . implode(', ', $select_parts)
                              . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation[$pma_table])
                              . ' WHERE ' . implode(' AND ', $where_parts);

            // must use PMA_DBI_QUERY_STORE here, since we execute another
            // query inside the loop
            $table_copy_rs    = PMA_query_as_cu($table_copy_query, true, PMA_DBI_QUERY_STORE);

            while ($table_copy_row = @PMA_DBI_fetch_assoc($table_copy_rs)) {
                $value_parts = array();
                foreach ($table_copy_row AS $_key => $_val) {
                    if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                        $value_parts[] = PMA_sqlAddslashes($_val);
                    }
                }

                $new_table_query = 'INSERT IGNORE INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation[$pma_table])
                                . ' (' . implode(', ', $select_parts) . ', ' . implode(', ', $new_parts) . ')'
                                . ' VALUES '
                                . ' (\'' . implode('\', \'', $value_parts) . '\', \'' . implode('\', \'', $new_value_parts) . '\')';

                $new_table_rs    = PMA_query_as_cu($new_table_query);
                $last_id = PMA_DBI_insert_id();
            } // end while

            return $last_id;
        }

        return true;
    } // end of 'PMA_Table::duplicateInfo()' function


    /**
     * Copies or renames table
     * FIXME: use RENAME
     *
     * @author          Michal Čihař <michal@cihar.com>
     */
    function moveCopy($source_db, $source_table, $target_db, $target_table, $what, $move) {
        global $cfgRelation, $dblist, $err_url, $sql_query;

        // set export settings we need
        $GLOBALS['use_backquotes'] = 1;
        $GLOBALS['asfile']         = 1;

        // Ensure the target is valid
        if (count($dblist) > 0 &&
          (! in_array($source_db, $dblist) || ! in_array($target_db, $dblist))) {
            exit();
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
            require_once('./libraries/export/sql.php');

            $no_constraints_comments = true;
            $sql_structure = PMA_getTableDef($source_db, $source_table, "\n", $err_url);
            unset($no_constraints_comments);

            $parsed_sql =  PMA_SQP_parse($sql_structure);

            /* nijel: Find table name in query and replace it */
            $i = 0;
            while ($parsed_sql[$i]['type'] != 'quote_backtick') $i++;

            /* no need to PMA_backquote() */
            $parsed_sql[$i]['data'] = $target;

            /* Generate query back */
            $sql_structure = PMA_SQP_formatHtml($parsed_sql, 'query_only');
            // If table exists, and 'add drop table' is selected: Drop it!
            $drop_query = '';
            if (isset($GLOBALS['drop_if_exists']) && $GLOBALS['drop_if_exists'] == 'true') {
                $drop_query = 'DROP TABLE IF EXISTS ' . PMA_backquote($target_db) . '.' . PMA_backquote($target_table);
                $result        = PMA_DBI_query($drop_query);

                if (isset($sql_query)) {
                    $sql_query .= "\n" . $drop_query . ';';
                } else {
                    $sql_query = $drop_query . ';';
                }

                // garvin: If an existing table gets deleted, maintain any entries
                // for the PMA_* tables
                $maintain_relations = true;
            }

            $result        = @PMA_DBI_query($sql_structure);
            if (isset($sql_query)) {
                $sql_query .= "\n" . $sql_structure . ';';
            } else {
                $sql_query = $sql_structure . ';';
            }

            if (($move || isset($GLOBALS['constraints'])) && isset($GLOBALS['sql_constraints'])) {
                $parsed_sql =  PMA_SQP_parse($GLOBALS['sql_constraints']);
                $i = 0;

                // find the first quote_backtick, it must be the source table name
                while ($parsed_sql[$i]['type'] != 'quote_backtick') {
                    $i++;
                }

                // replace it by the target table name, no need to PMA_backquote()
                $parsed_sql[$i]['data'] = $target;

                // now we must remove all quote_backtick that follow a CONSTRAINT
                // keyword, because a constraint name must be unique in a db

                $cnt = $parsed_sql['len'] - 1;

                for ($j = $i; $j < $cnt; $j++) {
                    if ($parsed_sql[$j]['type'] == 'alpha_reservedWord'
                      && strtoupper($parsed_sql[$j]['data']) == 'CONSTRAINT') {
                        if ($parsed_sql[$j+1]['type'] == 'quote_backtick') {
                            $parsed_sql[$j+1]['data'] = '';
                        }
                    }
                }


                // Generate query back
                $GLOBALS['sql_constraints'] = PMA_SQP_formatHtml($parsed_sql, 'query_only');
                $result          = PMA_DBI_query($GLOBALS['sql_constraints']);
                if (isset($sql_query)) {
                    $sql_query .= "\n" . $GLOBALS['sql_constraints'];
                } else {
                    $sql_query = $GLOBALS['sql_constraints'];
                }

                unset($GLOBALS['sql_constraints']);
            }

        } else {
            $sql_query='';
        }

        // Copy the data
        //if ($result != false && ($what == 'data' || $what == 'dataonly')) {
        if ($what == 'data' || $what == 'dataonly') {
            $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
            PMA_DBI_query($sql_insert_data);
            $sql_query      .= "\n\n" . $sql_insert_data . ';';
        }

        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            PMA_DBI_select_db($source_db);

            $sql_drop_table = 'DROP TABLE ' . $source;
            PMA_DBI_query($sql_drop_table);

            // garvin: Move old entries from PMA-DBs to new table
            if ($cfgRelation['commwork']) {
                $remove_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                              . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\', '
                              . '        db_name    = \'' . PMA_sqlAddslashes($target_db) . '\''
                              . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                              . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($remove_query);
            }

            // garvin: updating bookmarks is not possible since only a single table is moved,
            // and not the whole DB.
            // if ($cfgRelation['bookmarkwork']) {
            //     $remove_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['bookmark'])
            //                   . ' SET     dbase = \'' . PMA_sqlAddslashes($target_db) . '\''
            //                   . ' WHERE dbase  = \'' . PMA_sqlAddslashes($source_db) . '\'';
            //     $rmv_rs    = PMA_query_as_cu($remove_query);
            //     unset($rmv_query);
            // }

            if ($cfgRelation['displaywork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                                . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\', '
                                . '         table_name = \'' . PMA_sqlAddslashes($target_table) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
            }

            if ($cfgRelation['relwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                                . ' SET     foreign_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         foreign_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND foreign_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);

                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                                . ' SET     master_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         master_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
            }

            // garvin: [TODO] Can't get moving PDFs the right way. The page numbers always
            // get screwed up independently from duplication because the numbers do not
            // seem to be stored on a per-database basis. Would the author of pdf support
            // please have a look at it?

            if ($cfgRelation['pdfwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\','
                                . '         db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
                /*
                $pdf_query = 'SELECT pdf_page_number '
                           . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                           . ' WHERE db_name  = \'' . PMA_sqlAddslashes($target_db) . '\''
                           . ' AND table_name = \'' . PMA_sqlAddslashes($target_table) . '\'';
                $pdf_rs = PMA_query_as_cu($pdf_query);

                while ($pdf_copy_row = PMA_DBI_fetch_assoc($pdf_rs)) {
                    $table_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                                    . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                                    . ' AND page_nr = \'' . PMA_sqlAddslashes($pdf_copy_row['pdf_page_number']) . '\'';
                    $tb_rs    = PMA_query_as_cu($table_query);
                    unset($table_query);
                    unset($tb_rs);
                }
                */
            }

            $sql_query      .= "\n\n" . $sql_drop_table . ';';
        } else {
            // garvin: Create new entries as duplicates from old PMA DBs
            if ($what != 'dataonly' && !isset($maintain_relations)) {
                if ($cfgRelation['commwork']) {
                    // Get all comments and MIME-Types for current table
                    $comments_copy_query = 'SELECT
                                                column_name, ' . PMA_backquote('comment') . ($cfgRelation['mimework'] ? ', mimetype, transformation, transformation_options' : '') . '
                                            FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                                            WHERE
                                                db_name = \'' . PMA_sqlAddslashes($source_db) . '\' AND
                                                table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                    $comments_copy_rs    = PMA_query_as_cu($comments_copy_query);

                    // Write every comment as new copied entry. [MIME]
                    while ($comments_copy_row = PMA_DBI_fetch_assoc($comments_copy_rs)) {
                        $new_comment_query = 'REPLACE INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                                    . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ($cfgRelation['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                    . ' VALUES('
                                    . '\'' . PMA_sqlAddslashes($target_db) . '\','
                                    . '\'' . PMA_sqlAddslashes($target_table) . '\','
                                    . '\'' . PMA_sqlAddslashes($comments_copy_row['column_name']) . '\''
                                    . ($cfgRelation['mimework'] ? ',\'' . PMA_sqlAddslashes($comments_copy_row['comment']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['mimetype']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation_options']) . '\'' : '')
                                    . ')';
                        $new_comment_rs    = PMA_query_as_cu($new_comment_query);
                    } // end while
                }

                // duplicating the bookmarks must not be done here, but
                // just once per db

                $get_fields = array('display_field');
                $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
                $new_fields = array('db_name' => $target_db, 'table_name' => $target_table);
                PMA_Table::duplicateInfo('displaywork', 'table_info', $get_fields, $where_fields, $new_fields);

                $get_fields = array('master_field', 'foreign_db', 'foreign_table', 'foreign_field');
                $where_fields = array('master_db' => $source_db, 'master_table' => $source_table);
                $new_fields = array('master_db' => $target_db, 'master_table' => $target_table);
                PMA_Table::duplicateInfo('relwork', 'relation', $get_fields, $where_fields, $new_fields);

                $get_fields = array('foreign_field', 'master_db', 'master_table', 'master_field');
                $where_fields = array('foreign_db' => $source_db, 'foreign_table' => $source_table);
                $new_fields = array('foreign_db' => $target_db, 'foreign_table' => $target_table);
                PMA_Table::duplicateInfo('relwork', 'relation', $get_fields, $where_fields, $new_fields);

                // garvin: [TODO] Can't get duplicating PDFs the right way. The page numbers always
                // get screwed up independently from duplication because the numbers do not
                // seem to be stored on a per-database basis. Would the author of pdf support
                // please have a look at it?
                /*
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

    }

    /**
     * renames table
     *
     * @param   string  old tbale name
     * @param   string  new table name
     * @return  boolean success
     */
    function rename($old_name, $new_name)
    {
        // Ensure the target is valid
        if (count($GLOBALS['dblist']) > 0
          && ! in_array($GLOBALS['db'], $GLOBALS['dblist'])) {
            return false;
        }

        PMA_DBI_select_db($GLOBALS['db']);

        $sql_query = '
            ALTER TABLE ' . PMA_backquote($old_name) . '
            RENAME ' . PMA_backquote($new_name) . ';';
        if (! PMA_DBI_query($sql_query)) {
            return false;
        }

        // garvin: Move old entries from comments to new table
        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['commwork']) {
            $remove_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                   SET table_name = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE db_name  = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\'
                   AND table_name = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_cu($remove_query);
            unset($remove_query);
        }

        if ($cfgRelation['displaywork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info']) . '
                   SET table_name = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE db_name  = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\'
                   AND table_name = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_cu($table_query);
            unset($table_query);
        }

        if ($cfgRelation['relwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation']) . '
                SET     foreign_table = \'' . PMA_sqlAddslashes($new_name) . '\'
                WHERE foreign_db  = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\'
                AND foreign_table = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_cu($table_query);

            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation']) . '
                   SET     master_table = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE master_db  = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\'
                   AND master_table = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_cu($table_query);
            unset($table_query);
        }

        if ($cfgRelation['pdfwork']) {
            $table_query = '
                UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . '
                   SET table_name = \'' . PMA_sqlAddslashes($new_name) . '\'
                 WHERE db_name  = \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\'
                   AND table_name = \'' . PMA_sqlAddslashes($old_name) . '\'';
            PMA_query_as_cu($table_query);
            unset($table_query);
        }

        return true;
    }
}
?>