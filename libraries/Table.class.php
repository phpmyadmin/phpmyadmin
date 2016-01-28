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

    /**
     * @var string  engine (innodb, myisam, bdb, ...)
     */
    var $engine = '';

    /**
     * @var string  type (view, base table, system view)
     */
    var $type = '';

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
     * @var string  table name
     */
    protected $_name = '';

    /**
     * @var string  database name
     */
    protected $_db_name = '';

    /**
     * @var PMA_DatabaseInterface
     */
    protected $_dbi;

    /**
     * Constructor
     *
     * @param string                $table_name table name
     * @param string                $db_name    database name
     * @param PMA_DatabaseInterface $dbi        database interface for the table
     */
    function __construct($table_name, $db_name, PMA_DatabaseInterface $dbi = null)
    {
        if (empty($dbi)) {
            $dbi = $GLOBALS['dbi'];
        }
        $this->_dbi = $dbi;
        $this->_name = $table_name;
        $this->_db_name = $db_name;
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
     * returns table name
     *
     * @param boolean $backquoted whether to quote name with backticks ``
     *
     * @return string  table name
     */
    function getName($backquoted = false)
    {
        if ($backquoted) {
            return PMA_Util::backquote($this->_name);
        }
        return $this->_name;
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
            return PMA_Util::backquote($this->_db_name);
        }
        return $this->_db_name;
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
     * @return boolean whether the given is a view
     */
    public function isView()
    {
        $db = $this->_db_name;
        $table = $this->_name;
        if (empty($db) || empty($table)) {
            return false;
        }

        // use cached data or load information with SHOW command
        if ($this->_dbi->getCachedTableContent(array($db, $table)) != null
            || $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $type = $this->getStatusInfo('TABLE_TYPE');
            return $type == 'VIEW' || $type == 'SYSTEM VIEW';
        }

        // information_schema tables are 'SYSTEM VIEW's
        if ($db == 'information_schema') {
            return true;
        }

        // query information_schema
        $result = $this->_dbi->fetchResult(
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
     * @return boolean whether the given is an updatable view
     */
    public function isUpdatableView()
    {
        if (empty($this->_db_name) || empty($this->_name)) {
            return false;
        }

        $result = $this->_dbi->fetchResult(
            "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($this->_db_name) . "'
                AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($this->_name) . "'
                AND IS_UPDATABLE = 'YES'"
        );
        return $result ? true : false;
    }

    /**
     * Returns the analysis of 'SHOW CREATE TABLE' query for the table.
     * In case of a view, the values are taken from the information_schema.
     *
     * @return array analysis of 'SHOW CREATE TABLE' query for the table
     */
    public function analyzeStructure()
    {
        if (empty($this->_db_name) || empty($this->_name)) {
            return false;
        }

        $analyzed_sql = array();
        if ($this->isView()) {
            // For a view, 'SHOW CREATE TABLE' returns the definition,
            // but the structure of the view. So, we try to mock
            // the result of analyzing 'SHOW CREATE TABLE' query.
            $analyzed_sql[0] = array();
            $analyzed_sql[0]['create_table_fields'] = array();

            $results = $this->_dbi->fetchResult(
                "SELECT COLUMN_NAME, DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '" . PMA_Util::sqlAddSlashes($this->_db_name)
                . " AND TABLE_NAME = '" . PMA_Util::sqlAddSlashes($this->_name) . "'"
            );

            foreach ($results as $result) {
                $analyzed_sql[0]['create_table_fields'][$result['COLUMN_NAME']]
                    = array(
                        'type' => /*overload*/mb_strtoupper($result['DATA_TYPE'])
                    );
            }
        } else {
            $show_create_table = $this->_dbi->fetchValue(
                'SHOW CREATE TABLE '
                . PMA_Util::backquote($this->_db_name)
                . '.' . PMA_Util::backquote($this->_name),
                0,
                1
            );
            $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
        }
        return $analyzed_sql;
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias),
     * this is a merge table.
     *
     *
     * @return boolean  true if it is a merge table
     */
    public function isMerge()
    {
        $engine = null;
        // if called static, with parameters
        if (! empty($this->_db_name) && ! empty($this->_name)) {
            $engine = $this->getStatusInfo('ENGINE', null, true);
        }

        // did we get engine?
        if (empty($engine)) {
            return false;
        }

        // any of known merge engines?
        return in_array(
            /*overload*/mb_strtoupper($engine),
            array('MERGE', 'MRG_MYISAM')
        );
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param string  $info          specific information to be fetched
     * @param boolean $force_read    read new rather than serving from cache
     * @param boolean $disable_error if true, disables error message
     *
     * @todo DatabaseInterface::getTablesFull needs to be merged
     * somehow into this class or at least better documented
     *
     * @return mixed
     */
    public function getStatusInfo(
        $info = null,
        $force_read = false,
        $disable_error = false
    ) {
        $db = $this->_db_name;
        $table = $this->_name;

        if (! empty($_SESSION['is_multi_query'])) {
            $disable_error = true;
        }

        // sometimes there is only one entry (ExactRows) so
        // we have to get the table's details
        if ($this->_dbi->getCachedTableContent(array($db, $table)) == null
            || $force_read
            || count($this->_dbi->getCachedTableContent(array($db, $table))) == 1
        ) {
            $this->_dbi->getTablesFull($db, $table);
        }

        if ($this->_dbi->getCachedTableContent(array($db, $table)) == null) {
            // happens when we enter the table creation dialog
            // or when we really did not get any status info, for example
            // when $table == 'TABLE_NAMES' after the user tried SHOW TABLES
            return '';
        }

        if (null === $info) {
            return $this->_dbi->getCachedTableContent(array($db, $table));
        }

        // array_key_exists allows for null values
        if (!array_key_exists(
            $info, $this->_dbi->getCachedTableContent(array($db, $table))
        )
        ) {
            if (! $disable_error) {
                trigger_error(
                    __('Unknown table status:') . ' ' . $info,
                    E_USER_WARNING
                );
            }
            return false;
        }

        return $this->_dbi->getCachedTableContent(array($db, $table, $info));
    }

    /**
     * generates column specification for ALTER or CREATE TABLE syntax
     *
     * @param string      $name          name
     * @param string      $type          type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length        length ('2', '5,2', '', ...)
     * @param string      $attribute     attribute
     * @param string      $collation     collation
     * @param bool|string $null          with 'NULL' or 'NOT NULL'
     * @param string      $default_type  whether default is CURRENT_TIMESTAMP,
     *                                   NULL, NONE, USER_DEFINED
     * @param string      $default_value default value for USER_DEFINED
     *                                   default type
     * @param string      $extra         'AUTO_INCREMENT'
     * @param string      $comment       field comment
     * @param string      $virtuality    virtuality of the column
     * @param string      $expression    expression for the virtual column
     * @param string      $move_to       new position for column
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the
     * default current_timestamp is checked
     *
     * @return string  field specification
     */
    static function generateFieldSpec($name, $type, $length = '',
        $attribute = '', $collation = '', $null = false,
        $default_type = 'USER_DEFINED', $default_value = '',  $extra = '',
        $comment = '', $virtuality = '', $expression = '', $move_to = ''
    ) {
        $is_timestamp = /*overload*/mb_strpos(
            /*overload*/mb_strtoupper($type),
            'TIMESTAMP'
        ) !== false;

        $query = PMA_Util::backquote($name) . ' ' . $type;

        // allow the possibility of a length for TIME, DATETIME and TIMESTAMP
        // (will work on MySQL >= 5.6.4)
        //
        // MySQL permits a non-standard syntax for FLOAT and DOUBLE,
        // see http://dev.mysql.com/doc/refman/5.5/en/floating-point-types.html
        //
        $pattern = '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|'
            . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID)$@i';
        if ($length != '' && ! preg_match($pattern, $type)) {
            $query .= '(' . $length . ')';
        }

        if ($virtuality) {
            $query .= ' AS (' . $expression . ') ' . $virtuality;
        } else {
            if ($attribute != '') {
                $query .= ' ' . $attribute;
            }

            $matches = preg_match(
                '@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i',
                $type
            );
            if (! empty($collation) && $collation != 'NULL' && $matches) {
                $query .= PMA_generateCharsetQueryPart($collation, true);
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
                } elseif ($type == 'BINARY' || $type == 'VARBINARY') {
                    $query .= ' DEFAULT 0x' . $default_value;
                } else {
                    $query .= ' DEFAULT \''
                        . PMA_Util::sqlAddSlashes($default_value) . '\'';
                }
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'NULL' :
                // If user uncheck null checkbox and not change default value null,
                // default value will be ignored.
                if ($null !== false && $null !== 'NULL') {
                    break;
                }
                // else fall-through intended, no break here
            case 'CURRENT_TIMESTAMP' :
                $query .= ' DEFAULT ' . $default_type;
                break;
            case 'NONE' :
            default :
                break;
            }

            if (!empty($extra)) {
                $query .= ' ' . $extra;
            }
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
     * @param bool $force_exact whether to force an exact count
     *
     * @return mixed the number of records if "retain" param is true,
     *               otherwise true
     */
    public function countRecords($force_exact = false)
    {
        $is_view = $this->isView();
        $db = $this->_db_name;
        $table = $this->_name;

        if ($this->_dbi->getCachedTableContent(array($db, $table, 'ExactRows')) != null) {
            $row_count = $this->_dbi->getCachedTableContent(
                array($db, $table, 'ExactRows')
            );
            return $row_count;
        }
        $row_count = false;

        if (! $force_exact) {
            if (($this->_dbi->getCachedTableContent(array($db, $table, 'Rows')) == null)
                && !$is_view
            ) {
                $tmp_tables = $this->_dbi->getTablesFull($db, $table);
                if (isset($tmp_tables[$table])) {
                    $this->_dbi->cacheTableContent(
                        array($db, $table),
                        $tmp_tables[$table]
                    );
                }
            }
            if ($this->_dbi->getCachedTableContent(array($db, $table, 'Rows')) != null) {
                $row_count = $this->_dbi->getCachedTableContent(
                    array($db, $table, 'Rows')
                );
            } else {
                $row_count = false;
            }
        }
        // for a VIEW, $row_count is always false at this point
        if (false !== $row_count
            && $row_count >= $GLOBALS['cfg']['MaxExactCount']
        ) {
            return $row_count;
        }

        // Make an exception for views in I_S and D_D schema in
        // Drizzle, as these map to in-memory data and should execute
        // fast enough
        if (! $is_view
            || (PMA_DRIZZLE && $this->_dbi->isSystemSchema($db))
        ) {
            $row_count = $this->_dbi->fetchValue(
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
                $result = $this->_dbi->tryQuery(
                    'SELECT 1 FROM ' . PMA_Util::backquote($db) . '.'
                    . PMA_Util::backquote($table) . ' LIMIT '
                    . $GLOBALS['cfg']['MaxExactCountViews'],
                    null,
                    PMA_DatabaseInterface::QUERY_STORE
                );
                if (!$this->_dbi->getError()) {
                    $row_count = $this->_dbi->numRows($result);
                    $this->_dbi->freeResult($result);
                }
            }
        }
        if ($row_count) {
            $this->_dbi->cacheTableContent(array($db, $table, 'ExactRows'), $row_count);
        }

        return $row_count;
    } // end of the 'PMA_Table::countRecords()' function

    /**
     * Generates column specification for ALTER syntax
     *
     * @param string      $oldcol        old column name
     * @param string      $newcol        new column name
     * @param string      $type          type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length        length ('2', '5,2', '', ...)
     * @param string      $attribute     attribute
     * @param string      $collation     collation
     * @param bool|string $null          with 'NULL' or 'NOT NULL'
     * @param string      $default_type  whether default is CURRENT_TIMESTAMP,
     *                                   NULL, NONE, USER_DEFINED
     * @param string      $default_value default value for USER_DEFINED default
     *                                   type
     * @param string      $extra         'AUTO_INCREMENT'
     * @param string      $comment       field comment
     * @param string      $virtuality    virtuality of the column
     * @param string      $expression    expression for the virtual column
     * @param string      $move_to       new position for column
     *
     * @see PMA_Table::generateFieldSpec()
     *
     * @return string  field specification
     */
    static public function generateAlter($oldcol, $newcol, $type, $length,
        $attribute, $collation, $null, $default_type, $default_value,
        $extra, $comment, $virtuality, $expression, $move_to
    ) {
        return PMA_Util::backquote($oldcol) . ' '
        . PMA_Table::generateFieldSpec(
            $newcol, $type, $length, $attribute,
            $collation, $null, $default_type, $default_value, $extra,
            $comment, $virtuality, $expression, $move_to
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

        if (!isset($GLOBALS['cfgRelation']) || !$GLOBALS['cfgRelation'][$work]) {
            return true;
        }

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
                . '.' . PMA_Util::backquote($GLOBALS['cfgRelation'][$pma_table])
                . ' (' . implode(', ', $select_parts) . ', '
                . implode(', ', $new_parts) . ') VALUES (\''
                . implode('\', \'', $value_parts) . '\', \''
                . implode('\', \'', $new_value_parts) . '\')';

            PMA_queryAsControlUser($new_table_query);
            $last_id = $GLOBALS['dbi']->insertId();
        } // end while

        $GLOBALS['dbi']->freeResult($table_copy_rs);

        return $last_id;
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

        // Try moving the tables directly, using native `RENAME` statement.
        if ($move && $what == 'data') {
            $tbl = new PMA_Table($source_table, $source_db);
            if ($tbl->rename($target_table, $target_db)) {
                $GLOBALS['message'] = $tbl->getLastMessage();
                return true;
            }
        }

        // Setting required export settings.
        $GLOBALS['sql_backquotes'] = 1;
        $GLOBALS['asfile']         = 1;

        // Ensuring the target database is valid.
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

        /**
         * The full name of source table, quoted.
         * @var string $source
         */
        $source = PMA_Util::backquote($source_db)
            . '.' . PMA_Util::backquote($source_table);

        // If the target database is not specified, the operation is taking
        // place in the same database.
        if (! isset($target_db) || ! /*overload*/mb_strlen($target_db)) {
            $target_db = $source_db;
        }

        // Selecting the database could avoid some problems with replicated
        // databases, when moving table from replicated one to not replicated one.
        $GLOBALS['dbi']->selectDb($target_db);

        /**
         * The full name of target table, quoted.
         * @var string $target
         */
        $target = PMA_Util::backquote($target_db)
            . '.' . PMA_Util::backquote($target_table);

        // No table is created when this is a data-only operation.
        if ($what != 'dataonly') {

            include_once "libraries/plugin_interface.lib.php";

            /**
             * Instance used for exporting the current structure of the table.
             *
             * @var ExportSql
             */
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

            /**
             * The old structure of the table..
             * @var string $sql_structure
             */
            $sql_structure = $export_sql_plugin->getTableDef(
                $source_db, $source_table, "\n", $err_url, false, false
            );

            unset($no_constraints_comments);

            // -----------------------------------------------------------------
            // Phase 0: Preparing structures used.

            /**
             * The destination where the table is moved or copied to.
             * @var SqlParser\Components\Expression
             */
            $destination = new SqlParser\Components\Expression(
                $target_db, $target_table, ''
            );

            // Find server's SQL mode so the builder can generate correct
            // queries.
            // One of the options that alters the behaviour is `ANSI_QUOTES`.
            // This is not availabile for Drizzle.
            if (!PMA_DRIZZLE) {
                SqlParser\Context::setMode(
                    $GLOBALS['dbi']->fetchValue("SELECT @@sql_mode")
                );
            }

            // -----------------------------------------------------------------
            // Phase 1: Dropping existent element of the same name (if exists
            // and required).

            if (isset($_REQUEST['drop_if_exists'])
                && $_REQUEST['drop_if_exists'] == 'true'
            ) {

                /**
                 * Drop statement used for building the query.
                 * @var SqlParser\Statements\DropStatement $statement
                 */
                $statement = new SqlParser\Statements\DropStatement();

                $tbl = new PMA_Table($target_db, $target_table);

                $statement->options = new SqlParser\Components\OptionsArray(
                    array(
                        $tbl->isView() ? 'VIEW' : 'TABLE',
                        'IF EXISTS',
                    )
                );

                $statement->fields = array($destination);

                // Building the query.
                $drop_query = $statement->build() . ';';

                // Executing it.
                $GLOBALS['dbi']->query($drop_query);
                $GLOBALS['sql_query'] .= "\n" . $drop_query;

                // If an existing table gets deleted, maintain any entries for
                // the PMA_* tables.
                $maintain_relations = true;
            }

            // -----------------------------------------------------------------
            // Phase 2: Generating the new query of this structure.

            /**
             * The parser responsible for parsing the old queries.
             * @var SqlParser\Parser $parser
             */
            $parser = new SqlParser\Parser($sql_structure);

            if (!empty($parser->statements[0])) {

                /**
                 * The CREATE statement of this structure.
                 * @var SqlParser\Statements\CreateStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the destination.
                $statement->name = $destination;

                // Building back the query.
                $sql_structure = $statement->build() . ';';

                // Executing it.
                $GLOBALS['dbi']->query($sql_structure);
                $GLOBALS['sql_query'] .= "\n" . $sql_structure;
            }

            // -----------------------------------------------------------------
            // Phase 3: Adding constraints.
            // All constraint names are removed because they must be unique.

            if (($move || isset($GLOBALS['add_constraints']))
                && !empty($GLOBALS['sql_constraints_query'])
            ) {

                $parser = new SqlParser\Parser($GLOBALS['sql_constraints_query']);

                /**
                 * The ALTER statement that generates the constraints.
                 * @var SqlParser\Statements\AlterStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the altered table to the destination.
                $statement->table = $destination;

                // Removing the name of the constraints.
                foreach ($statement->altered as $idx => $altered) {
                    // All constraint names are removed because they must be unique.
                    if ($altered->options->has('CONSTRAINT')) {
                        $altered->field = null;
                    }
                }

                // Building back the query.
                $GLOBALS['sql_constraints_query'] = $statement->build() . ';';

                // Executing it.
                if ($mode == 'one_table') {
                    $GLOBALS['dbi']->query($GLOBALS['sql_constraints_query']);
                }
                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_constraints_query'];
                if ($mode == 'one_table') {
                    unset($GLOBALS['sql_constraints_query']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 4: Adding indexes.
            // View phase 3.

            if (!empty($GLOBALS['sql_indexes'])) {

                $parser = new SqlParser\Parser($GLOBALS['sql_indexes']);

                /**
                 * The ALTER statement that generates the indexes.
                 * @var SqlParser\Statements\AlterStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the altered table to the destination.
                $statement->table = $destination;

                // Removing the name of the constraints.
                foreach ($statement->altered as $idx => $altered) {
                    // All constraint names are removed because they must be unique.
                    if ($altered->options->has('CONSTRAINT')) {
                        $altered->field = null;
                    }
                }

                // Building back the query.
                $GLOBALS['sql_indexes'] = $statement->build() . ';';

                // Executing it.
                if ($mode == 'one_table' || $mode == 'db_copy') {
                    $GLOBALS['dbi']->query($GLOBALS['sql_indexes']);
                }
                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_indexes'];
                if ($mode == 'one_table' || $mode == 'db_copy') {
                    unset($GLOBALS['sql_indexes']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 5: Adding AUTO_INCREMENT.

            if (! empty($GLOBALS['sql_auto_increments'])) {
                if ($mode == 'one_table' || $mode == 'db_copy') {

                    $parser =  new SqlParser\Parser($GLOBALS['sql_auto_increments']);

                    /**
                     * The ALTER statement that alters the AUTO_INCREMENT value.
                     * @var SqlParser\Statements\AlterStatement $statement
                     */
                    $statement = $parser->statements[0];

                    // Changing the altered table to the destination.
                    $statement->table = $destination;

                    // Building back the query.
                    $GLOBALS['sql_auto_increments'] = $statement->build() . ';';

                    // Executing it.
                    $GLOBALS['dbi']->query($GLOBALS['sql_auto_increments']);
                    $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_auto_increments'];
                    unset($GLOBALS['sql_auto_increments']);
                }
            }
        } else {
            $GLOBALS['sql_query'] = '';
        }

        $_table = new PMA_Table($target_table, $target_db);
        // Copy the data unless this is a VIEW
        if (($what == 'data' || $what == 'dataonly')
            && ! $_table->isView()
        ) {
            if (! PMA_DRIZZLE) {
                $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
                $GLOBALS['dbi']->query($sql_set_mode);
                $GLOBALS['sql_query'] .= "\n\n" . $sql_set_mode . ';';
            }

            $sql_insert_data = 'INSERT INTO ' . $target
                . ' SELECT * FROM ' . $source;
            $GLOBALS['dbi']->query($sql_insert_data);
            $GLOBALS['sql_query'] .= "\n\n" . $sql_insert_data . ';';
        }

        PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            $GLOBALS['dbi']->selectDb($source_db);

            $_source_table = new PMA_Table($source_table, $source_db);
            if ($_source_table->isView()) {
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

            $GLOBALS['sql_query'] .= "\n\n" . $sql_drop_query . ';';
            // end if ($move)
            return true;
        }

        // we are copying
        // Create new entries as duplicates from old PMA DBs
        if ($what == 'dataonly' || isset($maintain_relations)) {
            return true;
        }

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
            while ($comments_copy_row
                = $GLOBALS['dbi']->fetchAssoc($comments_copy_rs)) {
                $new_comment_query = 'REPLACE INTO '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote(
                        $GLOBALS['cfgRelation']['column_info']
                    )
                    . ' (db_name, table_name, column_name, comment'
                    . ($GLOBALS['cfgRelation']['mimework']
                        ? ', mimetype, transformation, transformation_options'
                        : '')
                    . ') ' . ' VALUES(' . '\'' . PMA_Util::sqlAddSlashes($target_db)
                    . '\',\'' . PMA_Util::sqlAddSlashes($target_table) . '\',\''
                    . PMA_Util::sqlAddSlashes($comments_copy_row['column_name'])
                    . '\''
                    . ($GLOBALS['cfgRelation']['mimework']
                        ? ',\'' . PMA_Util::sqlAddSlashes(
                            $comments_copy_row['comment']
                        )
                        . '\',' . '\'' . PMA_Util::sqlAddSlashes(
                            $comments_copy_row['mimetype']
                        )
                        . '\',' . '\'' . PMA_Util::sqlAddSlashes(
                            $comments_copy_row['transformation']
                        )
                        . '\',' . '\'' . PMA_Util::sqlAddSlashes(
                            $comments_copy_row['transformation_options']
                        )
                        . '\''
                        : '')
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

        if (! /*overload*/mb_strlen($table_name)) {
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
        $lowerCaseTableNames = PMA_Util::cacheGet(
            'lower_case_table_names',
            function () {
                return $GLOBALS['dbi']->fetchValue(
                    "SELECT @@lower_case_table_names"
                );
            }
        );
        if ($lowerCaseTableNames) {
            $new_name = strtolower($new_name);
        }

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
        $triggers = $this->_dbi->getTriggers(
            $this->getDbName(), $this->getName(), ''
        );
        $handle_triggers = $this->getDbName() != $new_db && $triggers;
        if ($handle_triggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS '
                    . PMA_Util::backquote($this->getDbName())
                    . '.' . PMA_Util::backquote($trigger['name']) . ';';
                $this->_dbi->query($sql);
            }
        }

        /*
         * tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
         */
        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                  TO ' . $new_table->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if (! $this->_dbi->query($GLOBALS['sql_query'])) {
            // Restore triggers in the old database
            if ($handle_triggers) {
                $this->_dbi->selectDb($this->getDbName());
                foreach ($triggers as $trigger) {
                    $this->_dbi->query($trigger['create']);
                }
            }
            $this->errors[] = sprintf(
                __('Failed to rename table %1$s to %2$s!'),
                $this->getFullName(),
                $new_table->getFullName()
            );
            return false;
        }

        $old_name = $this->getName();
        $old_db = $this->getDbName();
        $this->_name = $new_name;
        $this->_db_name = $new_db;

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
     * returns an array with all columns with unique content, in fact these are
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
        $sql = $this->_dbi->getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Non_unique = 0'
        );
        $uniques = $this->_dbi->fetchResult(
            $sql,
            array('Key_name', null),
            'Column_name'
        );

        $return = array();
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }
            if ($fullName) {
                $possible_column = $this->getFullName($backquoted) . '.';
            } else {
                $possible_column = '';
            }
            if ($backquoted) {
                $possible_column .= PMA_Util::backquote($index[0]);
            } else {
                $possible_column .= $index[0];
            }
            // a column might have a primary and an unique index on it
            if (! in_array($possible_column, $return)) {
                $return[] = $possible_column;
            }
        }

        return $return;
    }

    /**
     * Get all indexed columns
     *
     * returns an array with all columns that make use of an index
     *
     * e.g. index(col1, col2) would return col1, col2
     *
     * @param bool $backquoted whether to quote name with backticks ``
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getIndexedColumns($backquoted = true, $fullName = true)
    {
        $sql = $this->_dbi->getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            ''
        );
        $indexed = $this->_dbi->fetchResult($sql, 'Column_name', 'Column_name');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = ($fullName ? $this->getFullName($backquoted) . '.' : '')
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
     * @param bool $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    public function getColumns($backquoted = true, $fullName = true)
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $indexed = $this->_dbi->fetchResult($sql, 'Field', 'Field');

        $return = array();
        foreach ($indexed as $column) {
            $return[] = ($fullName ? $this->getFullName($backquoted) . '.' : '')
                . ($backquoted ? PMA_Util::backquote($column) : $column);
        }

        return $return;
    }

    /**
     * Get meta info for fields in table
     *
     * @return mixed
     */
    public function getColumnsMeta()
    {
        $move_columns_sql_query = sprintf(
            'SELECT * FROM %s.%s LIMIT 1',
            PMA_Util::backquote($this->_db_name),
            PMA_Util::backquote($this->_name)
        );
        $move_columns_sql_result = $this->_dbi->tryQuery($move_columns_sql_query);
        if ($move_columns_sql_result !== false) {
            return $this->_dbi->getFieldsMeta($move_columns_sql_result);
        } else {
            // unsure how to reproduce but it was seen on the reporting server
            return array();
        }
    }

    /**
     * Return UI preferences for this table from phpMyAdmin database.
     *
     * @return array
     */
    protected function getUiPrefsFromDb()
    {
        $cfgRelation = PMA_getRelationsParam();
        $pma_table = PMA_Util::backquote($cfgRelation['db']) . "."
            . PMA_Util::backquote($cfgRelation['table_uiprefs']);

        // Read from phpMyAdmin database
        $sql_query = " SELECT `prefs` FROM " . $pma_table
            . " WHERE `username` = '" . $GLOBALS['cfg']['Server']['user'] . "'"
            . " AND `db_name` = '" . PMA_Util::sqlAddSlashes($this->_db_name) . "'"
            . " AND `table_name` = '" . PMA_Util::sqlAddSlashes($this->_name) . "'";

        $row = $this->_dbi->fetchArray(PMA_queryAsControlUser($sql_query));
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
        $cfgRelation = PMA_getRelationsParam();
        $pma_table = PMA_Util::backquote($cfgRelation['db']) . "."
            . PMA_Util::backquote($cfgRelation['table_uiprefs']);

        $secureDbName = PMA_Util::sqlAddSlashes($this->_db_name);

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query = " REPLACE INTO " . $pma_table
            . " (username, db_name, table_name, prefs) VALUES ('"
            . $username . "', '" . $secureDbName
            . "', '" . PMA_Util::sqlAddSlashes($this->_name) . "', '"
            . PMA_Util::sqlAddSlashes(json_encode($this->uiprefs)) . "')";

        $success = $this->_dbi->tryQuery($sql_query, $GLOBALS['controllink']);

        if (!$success) {
            $message = PMA_Message::error(
                __('Could not save table UI preferences!')
            );
            $message->addMessage('<br /><br />');
            $message->addMessage(
                PMA_Message::rawError(
                    $this->_dbi->getError($GLOBALS['controllink'])
                )
            );
            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured
        // maximum rows
        $sql_query = 'SELECT COUNT(*) FROM ' . $pma_table;
        $rows_count = $this->_dbi->fetchValue($sql_query);
        $max_rows = $GLOBALS['cfg']['Server']['MaxTableUiprefs'];
        if ($rows_count > $max_rows) {
            $num_rows_to_delete = $rows_count - $max_rows;
            $sql_query
                = ' DELETE FROM ' . $pma_table .
                ' ORDER BY last_update ASC' .
                ' LIMIT ' . $num_rows_to_delete;
            $success = $this->_dbi->tryQuery(
                $sql_query, $GLOBALS['controllink']
            );

            if (!$success) {
                $message = PMA_Message::error(
                    sprintf(
                        __(
                            'Failed to cleanup table UI preferences (see ' .
                            '$cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'
                        ),
                        PMA_Util::showDocu('config', 'cfg_Servers_MaxTableUiprefs')
                    )
                );
                $message->addMessage('<br /><br />');
                $message->addMessage(
                    PMA_Message::rawError(
                        $this->_dbi->getError($GLOBALS['controllink'])
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
        $cfgRelation = PMA_getRelationsParam();
        $server_id = $GLOBALS['server'];

        // set session variable if it's still undefined
        if (!isset($_SESSION['tmpval']['table_uiprefs'][$server_id][$this->_db_name][$this->_name])) {
            // check whether we can get from pmadb
            $_SESSION['tmpval']['table_uiprefs'][$server_id][$this->_db_name]
            [$this->_name] = $cfgRelation['uiprefswork']
                ?  $this->getUiPrefsFromDb()
                : array();
        }
        $this->uiprefs =& $_SESSION['tmpval']['table_uiprefs'][$server_id]
        [$this->_db_name][$this->_name];
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
            if (!isset($this->uiprefs[$property])) {
                return false;
            }

            if (!isset($_REQUEST['discard_remembered_sort'])) {
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
                        /*overload*/mb_strlen($each_col)
                        - /*overload*/mb_strlen($colname)
                    ) === 0
                    ) {
                        return $this->uiprefs[$property];
                    }
                }
            }
            // remove the property, since it no longer exists in database
            $this->removeUiProp(self::PROP_SORTED_COLUMN);
            return false;
        }

        if ($property == self::PROP_COLUMN_ORDER
            || $property == self::PROP_COLUMN_VISIB
        ) {
            if ($this->isView() || !isset($this->uiprefs[$property])) {
                return false;
            }

            // check if the table has not been modified
            if ($this->getStatusInfo('Create_time') == $this->uiprefs['CREATE_TIME']
            ) {
                return $this->uiprefs[$property];
            }

            // remove the property, since the table has been modified
            $this->removeUiProp(self::PROP_COLUMN_ORDER);
            return false;
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
        if (! $this->isView()
            && ($property == self::PROP_COLUMN_ORDER
            || $property == self::PROP_COLUMN_VISIB)
        ) {
            $curr_create_time = $this->getStatusInfo('CREATE_TIME');
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
                        __(
                            'Cannot save UI property "%s". The changes made will ' .
                            'not be persistent after you refresh this page. ' .
                            'Please check if the table structure has been changed.'
                        ),
                        $property
                    )
                );
            }
        }
        // save the value
        $this->uiprefs[$property] = $value;

        // check if pmadb is set
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['uiprefswork']) {
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
            $cfgRelation = PMA_getRelationsParam();
            if ($cfgRelation['uiprefswork']) {
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
            if (SqlParser\Context::isKeyword($column_name, true)) {
                $return[] = $column_name;
            }
        }
        return $return;
    }

    /**
     * Function to get the name and type of the columns of a table
     *
     * @return array
     */
    public function getNameAndTypeOfTheColumns()
    {
        $columns = array();
        foreach ($this->_dbi->getColumnsFull(
            $this->_db_name, $this->_name
        ) as $row) {
            if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
                $tmp[2] = /*overload*/
                    mb_substr(
                        preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1
                    );
                $columns[$row['Field']] = $tmp[1] . '('
                    . str_replace(',', ', ', $tmp[2]) . ')';
            } else {
                $columns[$row['Field']] = $row['Type'];
            }
        }
        return $columns;
    }

    /**
     * Get index with index name
     *
     * @param string $index Index name
     *
     * @return PMA_Index
     */
    public function getIndex($index)
    {
        return PMA_Index::singleton($this->_db_name, $this->_name, $index);
    }

    /**
     * Function to get the sql query for index creation or edit
     *
     * @param PMA_Index $index  current index
     * @param bool      &$error whether error occurred or not
     *
     * @return string
     */
    public function getSqlQueryForIndexCreateOrEdit($index, &$error)
    {
        // $sql_query is the one displayed in the query box
        $sql_query = sprintf(
            'ALTER TABLE %s.%s',
            PMA_Util::backquote($this->_db_name),
            PMA_Util::backquote($this->_name)
        );

        // Drops the old index
        if (! empty($_REQUEST['old_index'])) {
            if ($_REQUEST['old_index'] == 'PRIMARY') {
                $sql_query .= ' DROP PRIMARY KEY,';
            } else {
                $sql_query .= sprintf(
                    ' DROP INDEX %s,',
                    PMA_Util::backquote($_REQUEST['old_index'])
                );
            }
        } // end if

        // Builds the new one
        switch ($index->getChoice()) {
        case 'PRIMARY':
            if ($index->getName() == '') {
                $index->setName('PRIMARY');
            } elseif ($index->getName() != 'PRIMARY') {
                $error = PMA_Message::error(
                    __('The name of the primary key must be "PRIMARY"!')
                );
            }
            $sql_query .= ' ADD PRIMARY KEY';
            break;
        case 'FULLTEXT':
        case 'UNIQUE':
        case 'INDEX':
        case 'SPATIAL':
            if ($index->getName() == 'PRIMARY') {
                $error = PMA_Message::error(
                    __('Can\'t rename index to PRIMARY!')
                );
            }
            $sql_query .= sprintf(
                ' ADD %s ',
                $index->getChoice()
            );
            if ($index->getName()) {
                $sql_query .= PMA_Util::backquote($index->getName());
            }
            break;
        } // end switch

        $index_fields = array();
        foreach ($index->getColumns() as $key => $column) {
            $index_fields[$key] = PMA_Util::backquote($column->getName());
            if ($column->getSubPart()) {
                $index_fields[$key] .= '(' . $column->getSubPart() . ')';
            }
        } // end while

        if (empty($index_fields)) {
            $error = PMA_Message::error(__('No index parts defined!'));
        } else {
            $sql_query .= ' (' . implode(', ', $index_fields) . ')';
        }

        $keyBlockSizes = $index->getKeyBlockSize();
        if (! empty($keyBlockSizes)) {
            $sql_query .= sprintf(
                ' KEY_BLOCK_SIZE = ',
                PMA_Util::sqlAddSlashes($keyBlockSizes)
            );
        }

        // specifying index type is allowed only for primary, unique and index only
        $type = $index->getType();
        if ($index->getChoice() != 'SPATIAL'
            && $index->getChoice() != 'FULLTEXT'
            && in_array($type, PMA_Index::getIndexTypes())
        ) {
            $sql_query .= ' USING ' . $type;
        }

        $parser = $index->getParser();
        if ($index->getChoice() == 'FULLTEXT' && ! empty($parser)) {
            $sql_query .= ' WITH PARSER ' . PMA_Util::sqlAddSlashes($parser);
        }

        $comment = $index->getComment();
        if (! empty($comment)) {
            $sql_query .= sprintf(
                " COMMENT '%s'",
                PMA_Util::sqlAddSlashes($comment)
            );
        }

        $sql_query .= ';';

        return $sql_query;
    }

    /**
     * Function to handle update for display field
     *
     * @param string $disp          current display field
     * @param string $display_field display field
     * @param array  $cfgRelation   configuration relation
     *
     * @return boolean True on update succeed or False on failure
     */
    public function updateDisplayField($disp, $display_field, $cfgRelation)
    {
        $upd_query = false;
        if ($disp) {
            if ($display_field == '') {
                $upd_query = 'DELETE FROM '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \''
                    . PMA_Util::sqlAddSlashes($this->_db_name) . '\''
                    . ' AND table_name = \''
                    . PMA_Util::sqlAddSlashes($this->_name) . '\'';
            } elseif ($disp != $display_field) {
                $upd_query = 'UPDATE '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                    . ' SET display_field = \''
                    . PMA_Util::sqlAddSlashes($display_field) . '\''
                    . ' WHERE db_name  = \''
                    . PMA_Util::sqlAddSlashes($this->_db_name) . '\''
                    . ' AND table_name = \''
                    . PMA_Util::sqlAddSlashes($this->_name) . '\'';
            }
        } elseif ($display_field != '') {
            $upd_query = 'INSERT INTO '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                . '(db_name, table_name, display_field) VALUES('
                . '\'' . PMA_Util::sqlAddSlashes($this->_db_name) . '\','
                . '\'' . PMA_Util::sqlAddSlashes($this->_name) . '\','
                . '\'' . PMA_Util::sqlAddSlashes($display_field) . '\')';
        }

        if ($upd_query) {
            $this->_dbi->query(
                $upd_query,
                $GLOBALS['controllink'],
                0,
                false
            );
            return true;
        }
        return false;
    }

    /**
     * Function to get update query for updating internal relations
     *
     * @param array      $multi_edit_columns_name multi edit column names
     * @param array      $destination_db          destination tables
     * @param array      $destination_table       destination tables
     * @param array      $destination_column      destination columns
     * @param array      $cfgRelation             configuration relation
     * @param array|null $existrel                db, table, column
     *
     * @return boolean
     */
    public function updateInternalRelations($multi_edit_columns_name,
        $destination_db, $destination_table, $destination_column,
        $cfgRelation, $existrel
    ) {
        $updated = false;
        foreach ($destination_db as $master_field_md5 => $foreign_db) {
            $upd_query = null;
            // Map the fieldname's md5 back to its real name
            $master_field = $multi_edit_columns_name[$master_field_md5];
            $foreign_table = $destination_table[$master_field_md5];
            $foreign_field = $destination_column[$master_field_md5];
            if (! empty($foreign_db)
                && ! empty($foreign_table)
                && ! empty($foreign_field)
            ) {
                if (! isset($existrel[$master_field])) {
                    $upd_query  = 'INSERT INTO '
                        . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.' . PMA_Util::backquote($cfgRelation['relation'])
                        . '(master_db, master_table, master_field, foreign_db,'
                        . ' foreign_table, foreign_field)'
                        . ' values('
                        . '\'' . PMA_Util::sqlAddSlashes($this->_db_name) . '\', '
                        . '\'' . PMA_Util::sqlAddSlashes($this->_name) . '\', '
                        . '\'' . PMA_Util::sqlAddSlashes($master_field) . '\', '
                        . '\'' . PMA_Util::sqlAddSlashes($foreign_db) . '\', '
                        . '\'' . PMA_Util::sqlAddSlashes($foreign_table) . '\','
                        . '\'' . PMA_Util::sqlAddSlashes($foreign_field) . '\')';

                } elseif ($existrel[$master_field]['foreign_db'] != $foreign_db
                    || $existrel[$master_field]['foreign_table'] != $foreign_table
                    || $existrel[$master_field]['foreign_field'] != $foreign_field
                ) {
                    $upd_query  = 'UPDATE '
                        . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.' . PMA_Util::backquote($cfgRelation['relation'])
                        . ' SET foreign_db       = \''
                        . PMA_Util::sqlAddSlashes($foreign_db) . '\', '
                        . ' foreign_table    = \''
                        . PMA_Util::sqlAddSlashes($foreign_table) . '\', '
                        . ' foreign_field    = \''
                        . PMA_Util::sqlAddSlashes($foreign_field) . '\' '
                        . ' WHERE master_db  = \''
                        . PMA_Util::sqlAddSlashes($this->_db_name) . '\''
                        . ' AND master_table = \''
                        . PMA_Util::sqlAddSlashes($this->_name) . '\''
                        . ' AND master_field = \''
                        . PMA_Util::sqlAddSlashes($master_field) . '\'';
                } // end if... else....
            } elseif (isset($existrel[$master_field])) {
                $upd_query = 'DELETE FROM '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \''
                    . PMA_Util::sqlAddSlashes($this->_db_name) . '\''
                    . ' AND master_table = \''
                    . PMA_Util::sqlAddSlashes($this->_name) . '\''
                    . ' AND master_field = \''
                    . PMA_Util::sqlAddSlashes($master_field) . '\'';
            } // end if... else....

            if (isset($upd_query)) {
                $this->_dbi->query(
                    $upd_query,
                    $GLOBALS['controllink'],
                    0,
                    false
                );
                $updated = true;
            }
        }
        return $updated;
    }

    /**
     * Function to handle foreign key updates
     *
     * @param array  $destination_foreign_db     destination foreign database
     * @param array  $multi_edit_columns_name    multi edit column names
     * @param array  $destination_foreign_table  destination foreign table
     * @param array  $destination_foreign_column destination foreign column
     * @param array  $options_array              options array
     * @param string $table                      current table
     * @param array  $existrel_foreign           db, table, column
     *
     * @return array
     */
    public function updateForeignKeys($destination_foreign_db,
        $multi_edit_columns_name, $destination_foreign_table,
        $destination_foreign_column, $options_array, $table, $existrel_foreign
    ) {
        $html_output = '';
        $preview_sql_data = '';
        $display_query = '';
        $seen_error = false;

        foreach ($destination_foreign_db as $master_field_md5 => $foreign_db) {
            $create = false;
            $drop = false;

            // Map the fieldname's md5 back to its real name
            $master_field = $multi_edit_columns_name[$master_field_md5];

            $foreign_table = $destination_foreign_table[$master_field_md5];
            $foreign_field = $destination_foreign_column[$master_field_md5];

            if (isset($existrel_foreign[$master_field_md5]['ref_db_name'])) {
                $ref_db_name = $existrel_foreign[$master_field_md5]['ref_db_name'];
            } else {
                $ref_db_name = $GLOBALS['db'];
            }

            $empty_fields = false;
            foreach ($master_field as $key => $one_field) {
                if ((! empty($one_field) && empty($foreign_field[$key]))
                    || (empty($one_field) && ! empty($foreign_field[$key]))
                ) {
                    $empty_fields = true;
                }

                if (empty($one_field) && empty($foreign_field[$key])) {
                    unset($master_field[$key]);
                    unset($foreign_field[$key]);
                }
            }

            if (! empty($foreign_db)
                && ! empty($foreign_table)
                && ! $empty_fields
            ) {
                if (isset($existrel_foreign[$master_field_md5])) {
                    $constraint_name
                        = $existrel_foreign[$master_field_md5]['constraint'];
                    $on_delete = !empty(
                        $existrel_foreign[$master_field_md5]['on_delete']
                    )
                        ? $existrel_foreign[$master_field_md5]['on_delete']
                        : 'RESTRICT';
                    $on_update = ! empty(
                        $existrel_foreign[$master_field_md5]['on_update']
                    )
                        ? $existrel_foreign[$master_field_md5]['on_update']
                        : 'RESTRICT';

                    if ($ref_db_name != $foreign_db
                        || $existrel_foreign[$master_field_md5]['ref_table_name'] != $foreign_table
                        || $existrel_foreign[$master_field_md5]['ref_index_list'] != $foreign_field
                        || $existrel_foreign[$master_field_md5]['index_list'] != $master_field
                        || $_REQUEST['constraint_name'][$master_field_md5] != $constraint_name
                        || ($_REQUEST['on_delete'][$master_field_md5] != $on_delete)
                        || ($_REQUEST['on_update'][$master_field_md5] != $on_update)
                    ) {
                        // another foreign key is already defined for this field
                        // or an option has been changed for ON DELETE or ON UPDATE
                        $drop = true;
                        $create = true;
                    } // end if... else....
                } else {
                    // no key defined for this field(s)
                    $create = true;
                }
            } elseif (isset($existrel_foreign[$master_field_md5])) {
                $drop = true;
            } // end if... else....

            $tmp_error_drop = false;
            if ($drop) {
                $drop_query = 'ALTER TABLE ' . PMA_Util::backquote($table)
                    . ' DROP FOREIGN KEY ' . PMA_Util::backquote($existrel_foreign[$master_field_md5]['constraint']) . ';';

                if (! isset($_REQUEST['preview_sql'])) {
                    $display_query .= $drop_query . "\n";
                    $this->_dbi->tryQuery($drop_query);
                    $tmp_error_drop = $this->_dbi->getError();

                    if (! empty($tmp_error_drop)) {
                        $seen_error = true;
                        $html_output .= PMA_Util::mysqlDie(
                            $tmp_error_drop, $drop_query, false, '', false
                        );
                        continue;
                    }
                } else {
                    $preview_sql_data .= $drop_query . "\n";
                }
            }
            $tmp_error_create = false;
            if (!$create) {
                continue;
            }

            $create_query = $this->_getSQLToCreateForeignKey(
                $table, $master_field, $foreign_db, $foreign_table, $foreign_field,
                $_REQUEST['constraint_name'][$master_field_md5],
                $options_array[$_REQUEST['on_delete'][$master_field_md5]],
                $options_array[$_REQUEST['on_update'][$master_field_md5]]
            );

            if (! isset($_REQUEST['preview_sql'])) {
                $display_query .= $create_query . "\n";
                $this->_dbi->tryQuery($create_query);
                $tmp_error_create = $this->_dbi->getError();
                if (! empty($tmp_error_create)) {
                    $seen_error = true;

                    if (substr($tmp_error_create, 1, 4) == '1005') {
                        $message = PMA_Message::error(
                            __(
                                'Error creating foreign key on %1$s (check data ' .
                                'types)'
                            )
                        );
                        $message->addParam(implode(', ', $master_field));
                        $html_output .= $message->getDisplay();
                    } else {
                        $html_output .= PMA_Util::mysqlDie(
                            $tmp_error_create, $create_query, false, '', false
                        );
                    }
                    $html_output .= PMA_Util::showMySQLDocu(
                        'InnoDB_foreign_key_constraints'
                    ) . "\n";
                }
            } else {
                $preview_sql_data .= $create_query . "\n";
            }

            // this is an alteration and the old constraint has been dropped
            // without creation of a new one
            if ($drop && $create && empty($tmp_error_drop)
                && ! empty($tmp_error_create)
            ) {
                // a rollback may be better here
                $sql_query_recreate = '# Restoring the dropped constraint...' . "\n";
                $sql_query_recreate .= $this->_getSQLToCreateForeignKey(
                    $table,
                    $master_field,
                    $existrel_foreign[$master_field_md5]['ref_db_name'],
                    $existrel_foreign[$master_field_md5]['ref_table_name'],
                    $existrel_foreign[$master_field_md5]['ref_index_list'],
                    $existrel_foreign[$master_field_md5]['constraint'],
                    $options_array[$existrel_foreign[$master_field_md5]['on_delete']],
                    $options_array[$existrel_foreign[$master_field_md5]['on_update']]
                );
                if (! isset($_REQUEST['preview_sql'])) {
                    $display_query .= $sql_query_recreate . "\n";
                    $this->_dbi->tryQuery($sql_query_recreate);
                } else {
                    $preview_sql_data .= $sql_query_recreate;
                }
            }
        } // end foreach

        return array(
            $html_output,
            $preview_sql_data,
            $display_query,
            $seen_error
        );
    }

    /**
     * Returns the SQL query for foreign key constraint creation
     *
     * @param string $table        table name
     * @param array  $field        field names
     * @param string $foreignDb    foreign database name
     * @param string $foreignTable foreign table name
     * @param array  $foreignField foreign field names
     * @param string $name         name of the constraint
     * @param string $onDelete     on delete action
     * @param string $onUpdate     on update action
     *
     * @return string SQL query for foreign key constraint creation
     */
    private function _getSQLToCreateForeignKey(
        $table,
        $field,
        $foreignDb,
        $foreignTable,
        $foreignField,
        $name = null,
        $onDelete = null,
        $onUpdate = null
    ) {
        $sql_query  = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD ';
        // if user entered a constraint name
        if (! empty($name)) {
            $sql_query .= ' CONSTRAINT ' . PMA_Util::backquote($name);
        }

        foreach ($field as $key => $one_field) {
            $field[$key] = PMA_Util::backquote($one_field);
        }
        foreach ($foreignField as $key => $one_field) {
            $foreignField[$key] = PMA_Util::backquote($one_field);
        }
        $sql_query .= ' FOREIGN KEY (' . implode(', ', $field) . ') REFERENCES '
            . ($this->_db_name != $foreignDb
                ? PMA_Util::backquote($foreignDb) . '.' : '')
            . PMA_Util::backquote($foreignTable)
            . '(' . implode(', ', $foreignField) . ')';

        if (! empty($onDelete)) {
            $sql_query .= ' ON DELETE ' . $onDelete;
        }
        if (! empty($onUpdate)) {
            $sql_query .= ' ON UPDATE ' . $onUpdate;
        }
        $sql_query .= ';';

        return $sql_query;
    }

    /**
     * Returns the generation expression for virtual columns
     *
     * @param string $column name of the column
     *
     * @return array|boolean associative array of column name and their expressions
     *                       or false on failure
     */
    public function getColumnGenerationExpression($column = null)
    {
        $serverType = PMA_Util::getServerType();
        if ($serverType == 'MySQL'
            && PMA_MYSQL_INT_VERSION > 50705
            && ! $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $sql
                = "SELECT
                `COLUMN_NAME` AS `Field`,
                `GENERATION_EXPRESSION` AS `Expression`
                FROM
                `information_schema`.`COLUMNS`
                WHERE
                `TABLE_SCHEMA` = '" . PMA_Util::sqlAddSlashes($this->_db_name) . "'
                AND `TABLE_NAME` = '" . PMA_Util::sqlAddSlashes($this->_name) . "'";
            if ($column != null) {
                $sql .= " AND  `COLUMN_NAME` = '" . PMA_Util::sqlAddSlashes($column)
                    . "'";
            }
            $columns = $this->_dbi->fetchResult($sql, 'Field', 'Expression');
            return $columns;
        }

        $createTable = $this->showCreate();
        if (!$createTable) {
            return false;
        }

        $parser = new SqlParser\Parser($createTable);
        /**
         * @var SqlParser\Statements\CreateStatement $stmt
        */
        $stmt = $parser->statements[0];
        $fields = SqlParser\Utils\Table::getFields($stmt);
        if ($column != null) {
            $expression = isset($fields[$column]['expr']) ?
                substr($fields[$column]['expr'], 1, -1) : '';
            return array($column => $expression);
        }

        $ret = array();
        foreach ($fields as $field => $options) {
            if (isset($options['expr'])) {
                $ret[$field] = substr($options['expr'], 1, -1);
            }
        }
        return $ret;
    }

    /**
     * Returns the CREATE statement for this table
     *
     * @return mixed
     */
    public function showCreate()
    {
        return $this->_dbi->fetchValue(
            'SHOW CREATE TABLE ' . PMA_Util::backquote($this->_db_name) . '.'
            . PMA_Util::backquote($this->_name),
            0, 1
        );
    }

    /**
     * Returns the real row count for a table
     *
     * @return number
     */
    function getRealRowCountTable()
    {
        // SQL query to get row count for a table.
        $result = $this->_dbi->fetchSingleRow(
            sprintf(
                'SELECT COUNT(*) AS %s FROM %s.%s',
                PMA_Util::backquote('row_count'),
                PMA_Util::backquote($this->_db_name),
                PMA_Util::backquote($this->_name)
            )
        );
        return $result['row_count'];
    }

    /**
     * Get columns with indexes
     *
     * @param int $types types bitmask
     *
     * @return array an array of columns
     */
    function getColumnsWithIndex($types)
    {
        $columns_with_index = array();
        foreach (
            PMA_Index::getFromTableByChoice(
                $this->_name,
                $this->_db_name,
                $types
            ) as $index
        ) {
            $columns = $index->getColumns();
            foreach ($columns as $column_name => $dummy) {
                $columns_with_index[] = $column_name;
            }
        }
        return $columns_with_index;
    }
}
