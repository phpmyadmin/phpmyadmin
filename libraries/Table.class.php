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
        if ( $quoted ) {
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
        if ( $quoted ) {
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

        if ( strpos($this->get('TABLE TYPE'), 'VIEW') ) {
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
        if ( isset( $this->settings[$param]) ) {
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

        if ( false === $table_info ) {
            return false;
        }

        $this->settings = $table_info;

        if ( $this->get('TABLE_ROWS') === null ) {
            $this->set('TABLE_ROWS', PMA_Table::countRecords($this->getDbName(),
                $this->getName(), true, true));
        }

        $create_options = explode(' ', $this->get('TABLE_ROWS'));

        // export create options by its name as variables into gloabel namespace
        // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
        foreach ( $create_options as $each_create_option ) {
            $each_create_option = explode('=', $each_create_option);
            if ( isset( $each_create_option[1] ) ) {
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
        if ( false === PMA_DBI_fetch_value('SELECT TABLE_NAME FROM `information_schema`.`VIEWS` WHERE `TABLE_SCHEMA` = \'' . $db . '\' AND `TABLE_NAME` = \'' . $table . '\';')) {
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

}
?>