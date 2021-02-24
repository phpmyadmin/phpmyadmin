<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Utils\Table as TableUtils;
use const E_USER_WARNING;
use function array_key_exists;
use function array_map;
use function count;
use function end;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function substr_compare;
use function trigger_error;
use function trim;

/**
 * Handles everything related to tables
 *
 * @todo make use of Message and Error
 */
class Table
{
    /**
     * UI preferences properties
     */
    public const PROP_SORTED_COLUMN = 'sorted_col';
    public const PROP_COLUMN_ORDER = 'col_order';
    public const PROP_COLUMN_VISIB = 'col_visib';

    /** @var string  engine (innodb, myisam, bdb, ...) */
    public $engine = '';

    /** @var string  type (view, base table, system view) */
    public $type = '';

    /** @var array UI preferences */
    public $uiprefs;

    /** @var array errors occurred */
    public $errors = [];

    /** @var array messages */
    public $messages = [];

    /** @var string  table name */
    protected $name = '';

    /** @var string  database name */
    protected $dbName = '';

    /** @var DatabaseInterface */
    protected $dbi;

    /** @var Relation */
    private $relation;

    /**
     * @param string                 $table_name table name
     * @param string                 $db_name    database name
     * @param DatabaseInterface|null $dbi        database interface for the table
     */
    public function __construct($table_name, $db_name, ?DatabaseInterface $dbi = null)
    {
        if (empty($dbi)) {
            $dbi = $GLOBALS['dbi'];
        }
        $this->dbi = $dbi;
        $this->name = $table_name;
        $this->dbName = $db_name;
        $this->relation = new Relation($this->dbi);
    }

    /**
     * returns table name
     *
     * @see Table::getName()
     *
     * @return string  table name
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Table getter
     *
     * @param string                 $table_name table name
     * @param string                 $db_name    database name
     * @param DatabaseInterface|null $dbi        database interface for the table
     *
     * @return Table
     */
    public static function get($table_name, $db_name, ?DatabaseInterface $dbi = null)
    {
        return new Table($table_name, $db_name, $dbi);
    }

    /**
     * return the last error
     *
     * @return string the last error
     */
    public function getLastError()
    {
        return end($this->errors);
    }

    /**
     * return the last message
     *
     * @return string the last message
     */
    public function getLastMessage()
    {
        return end($this->messages);
    }

    /**
     * returns table name
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string  table name
     */
    public function getName($backquoted = false)
    {
        if ($backquoted) {
            return Util::backquote($this->name);
        }

        return $this->name;
    }

    /**
     * returns database name for this table
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string  database name for this table
     */
    public function getDbName($backquoted = false)
    {
        if ($backquoted) {
            return Util::backquote($this->dbName);
        }

        return $this->dbName;
    }

    /**
     * returns full name for table, including database name
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string
     */
    public function getFullName($backquoted = false)
    {
        return $this->getDbName($backquoted) . '.'
        . $this->getName($backquoted);
    }

    /**
     * Checks the storage engine used to create table
     *
     * @param array|string $engine Checks the table engine against an
     *                             array of engine strings or a single string, should be uppercase
     *
     * @return bool True, if $engine matches the storage engine for the table,
     * False otherwise.
     */
    public function isEngine($engine)
    {
        $tbl_storage_engine = $this->getStorageEngine();

        if (is_array($engine)) {
            foreach ($engine as $e) {
                if ($e == $tbl_storage_engine) {
                    return true;
                }
            }

            return false;
        }

        return $tbl_storage_engine == $engine;
    }

    /**
     * returns whether the table is actually a view
     *
     * @return bool whether the given is a view
     */
    public function isView()
    {
        $db = $this->dbName;
        $table = $this->name;
        if (empty($db) || empty($table)) {
            return false;
        }

        // use cached data or load information with SHOW command
        if ($this->dbi->getCache()->getCachedTableContent([$db, $table]) != null
            || $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $type = $this->getStatusInfo('TABLE_TYPE');

            return $type === 'VIEW' || $type === 'SYSTEM VIEW';
        }

        // information_schema tables are 'SYSTEM VIEW's
        if ($db === 'information_schema') {
            return true;
        }

        // query information_schema
        $result = $this->dbi->fetchResult(
            'SELECT TABLE_NAME'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'' . $this->dbi->escapeString((string) $db) . '\''
            . ' AND TABLE_NAME = \'' . $this->dbi->escapeString((string) $table) . '\''
        );

        return (bool) $result;
    }

    /**
     * Returns whether the table is actually an updatable view
     *
     * @return bool whether the given is an updatable view
     */
    public function isUpdatableView()
    {
        if (empty($this->dbName) || empty($this->name)) {
            return false;
        }

        $result = $this->dbi->fetchResult(
            'SELECT TABLE_NAME'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'' . $this->dbi->escapeString($this->dbName) . '\''
            . ' AND TABLE_NAME = \'' . $this->dbi->escapeString($this->name) . '\''
            . ' AND IS_UPDATABLE = \'YES\''
        );

        return (bool) $result;
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias),
     * this is a merge table.
     *
     * @return bool true if it is a merge table
     */
    public function isMerge()
    {
        return $this->isEngine(['MERGE', 'MRG_MYISAM']);
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param string $info          specific information to be fetched
     * @param bool   $force_read    read new rather than serving from cache
     * @param bool   $disable_error if true, disables error message
     *
     * @return mixed
     *
     * @todo DatabaseInterface::getTablesFull needs to be merged
     * somehow into this class or at least better documented
     */
    public function getStatusInfo(
        $info = null,
        $force_read = false,
        $disable_error = false
    ) {
        $db = $this->dbName;
        $table = $this->name;

        if (! empty($_SESSION['is_multi_query'])) {
            $disable_error = true;
        }

        $cachedResult = $this->dbi->getCache()->getCachedTableContent([$db, $table]);

        // sometimes there is only one entry (ExactRows) so
        // we have to get the table's details
        if ($cachedResult === null
            || $force_read
            || count($cachedResult) === 1
        ) {
            $this->dbi->getTablesFull($db, $table);
            $cachedResult = $this->dbi->getCache()->getCachedTableContent([$db, $table]);
        }

        if ($cachedResult === null) {
            // happens when we enter the table creation dialog
            // or when we really did not get any status info, for example
            // when $table === 'TABLE_NAMES' after the user tried SHOW TABLES
            return '';
        }

        if ($info === null) {
            return $cachedResult;
        }

        // array_key_exists allows for null values
        if (! array_key_exists(
            $info,
            $cachedResult
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

        return $this->dbi->getCache()->getCachedTableContent([$db, $table, $info]);
    }

    /**
     * Returns the Table storage Engine for current table.
     *
     * @return string                 Return storage engine info if it is set for
     *                                the selected table else return blank.
     */
    public function getStorageEngine(): string
    {
        $table_storage_engine = $this->getStatusInfo('ENGINE', false, true);
        if ($table_storage_engine === false) {
            return '';
        }

        return strtoupper((string) $table_storage_engine);
    }

    /**
     * Returns the comments for current table.
     *
     * @return string Return comment info if it is set for the selected table or return blank.
     */
    public function getComment()
    {
        $table_comment = $this->getStatusInfo('TABLE_COMMENT', false, true);
        if ($table_comment === false) {
            return '';
        }

        return $table_comment;
    }

    /**
     * Returns the collation for current table.
     *
     * @return string Return blank if collation is empty else return the collation info from table info.
     */
    public function getCollation()
    {
        $table_collation = $this->getStatusInfo('TABLE_COLLATION', false, true);
        if ($table_collation === false) {
            return '';
        }

        return $table_collation;
    }

    /**
     * Returns the info about no of rows for current table.
     *
     * @return int Return no of rows info if it is not null for the selected table or return 0.
     */
    public function getNumRows()
    {
        $table_num_row_info = $this->getStatusInfo('TABLE_ROWS', false, true);
        if ($table_num_row_info === false) {
            $table_num_row_info = $this->dbi->getTable($this->dbName, $GLOBALS['showtable']['Name'])
            ->countRecords(true);
        }

        return $table_num_row_info ?: 0;
    }

    /**
     * Returns the Row format for current table.
     *
     * @return string Return table row format info if it is set for the selected table or return blank.
     */
    public function getRowFormat()
    {
        $table_row_format = $this->getStatusInfo('ROW_FORMAT', false, true);
        if ($table_row_format === false) {
            return '';
        }

        return $table_row_format;
    }

    /**
     * Returns the auto increment option for current table.
     *
     * @return int Return auto increment info if it is set for the selected table or return blank.
     */
    public function getAutoIncrement()
    {
        $table_auto_increment = $this->getStatusInfo('AUTO_INCREMENT', false, true);

        return $table_auto_increment ?? '';
    }

    /**
     * Returns the array for CREATE statement for current table.
     *
     * @return array Return options array info if it is set for the selected table or return blank.
     */
    public function getCreateOptions()
    {
        $table_options = $this->getStatusInfo('CREATE_OPTIONS', false, true);
        $create_options_tmp = empty($table_options) ? [] : explode(' ', $table_options);
        $create_options = [];
        // export create options by its name as variables into global namespace
        // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
        // unset($pack_keys);
        foreach ($create_options_tmp as $each_create_option) {
            $each_create_option = explode('=', $each_create_option);
            if (! isset($each_create_option[1])) {
                continue;
            }

            // ensure there is no ambiguity for PHP 5 and 7
            $create_options[$each_create_option[0]] = $each_create_option[1];
        }
        // we need explicit DEFAULT value here (different from '0')
        $hasPackKeys = isset($create_options['pack_keys']) && strlen($create_options['pack_keys']) > 0;
        $create_options['pack_keys'] = $hasPackKeys ? $create_options['pack_keys'] : 'DEFAULT';

        return $create_options;
    }

    /**
     * generates column specification for ALTER or CREATE TABLE syntax
     *
     * @param string      $name               name
     * @param string      $type               type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length             length ('2', '5,2', '', ...)
     * @param string      $attribute          attribute
     * @param string      $collation          collation
     * @param bool|string $null               with 'NULL' or 'NOT NULL'
     * @param string      $default_type       whether default is CURRENT_TIMESTAMP,
     *                                        NULL, NONE, USER_DEFINED
     * @param string      $default_value      default value for USER_DEFINED
     *                                        default type
     * @param string      $extra              'AUTO_INCREMENT'
     * @param string      $comment            field comment
     * @param string      $virtuality         virtuality of the column
     * @param string      $expression         expression for the virtual column
     * @param string      $move_to            new position for column
     * @param array       $columns_with_index Fields having PRIMARY or UNIQUE KEY indexes
     * @param string      $oldColumnName      Old column name
     *
     * @return string  field specification
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the
     * default current_timestamp is checked
     */
    public static function generateFieldSpec(
        $name,
        $type,
        $length = '',
        $attribute = '',
        $collation = '',
        $null = false,
        $default_type = 'USER_DEFINED',
        $default_value = '',
        $extra = '',
        $comment = '',
        $virtuality = '',
        $expression = '',
        $move_to = '',
        $columns_with_index = null,
        $oldColumnName = null
    ) {
        global $dbi;

        $is_timestamp = mb_stripos($type, 'TIMESTAMP') !== false;

        $query = Util::backquote($name) . ' ' . $type;

        // allow the possibility of a length for TIME, DATETIME and TIMESTAMP
        // (will work on MySQL >= 5.6.4)
        //
        // MySQL permits a non-standard syntax for FLOAT and DOUBLE,
        // see https://dev.mysql.com/doc/refman/5.5/en/floating-point-types.html
        $pattern = '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|'
            . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID|JSON)$@i';
        if (strlen($length) !== 0 && ! preg_match($pattern, $type)) {
            // Note: The variable $length here can contain several other things
            // besides length - ENUM/SET value or length of DECIMAL (eg. 12,3)
            // so we can't just convert it to integer
            $query .= '(' . $length . ')';
        }
        if ($attribute != '') {
            $query .= ' ' . $attribute;

            if ($is_timestamp
                && stripos($attribute, 'TIMESTAMP') !== false
                && strlen($length) !== 0
                && $length !== 0
            ) {
                $query .= '(' . $length . ')';
            }
        }

        // if column is virtual, check if server type is Mysql as only Mysql server
        // supports extra column properties
        $isVirtualColMysql = $virtuality && in_array(Util::getServerType(), ['MySQL', 'Percona Server']);
        // if column is virtual, check if server type is MariaDB as MariaDB server
        // supports no extra virtual column properties except CHARACTER SET for text column types
        $isVirtualColMariaDB = $virtuality && Util::getServerType() === 'MariaDB';

        $matches = preg_match(
            '@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i',
            $type
        );
        if (! empty($collation) && $collation !== 'NULL' && $matches) {
            $query .= Util::getCharsetQueryPart(
                $isVirtualColMariaDB ? (string) preg_replace('~_.+~s', '', $collation) : $collation,
                true
            );
        }

        if ($virtuality) {
            $query .= ' AS (' . $expression . ') ' . $virtuality;
        }

        if (! $virtuality || $isVirtualColMysql) {
            if ($null !== false) {
                if ($null === 'YES') {
                    $query .= ' NULL';
                } else {
                    $query .= ' NOT NULL';
                }
            }

            if (! $virtuality) {
                switch ($default_type) {
                    case 'USER_DEFINED':
                        if ($is_timestamp && $default_value === '0') {
                            // a TIMESTAMP does not accept DEFAULT '0'
                            // but DEFAULT 0 works
                            $query .= ' DEFAULT 0';
                        } elseif ($is_timestamp
                            && preg_match(
                                '/^\'\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d(\.\d{1,6})?\'$/',
                                (string) $default_value
                            )
                        ) {
                            $query .= ' DEFAULT ' . (string) $default_value;
                        } elseif ($type === 'BIT') {
                            $query .= ' DEFAULT b\''
                            . preg_replace('/[^01]/', '0', (string) $default_value)
                            . '\'';
                        } elseif ($type === 'BOOLEAN') {
                            if (preg_match('/^1|T|TRUE|YES$/i', (string) $default_value)) {
                                $query .= ' DEFAULT TRUE';
                            } elseif (preg_match('/^0|F|FALSE|NO$/i', $default_value)) {
                                $query .= ' DEFAULT FALSE';
                            } else {
                                // Invalid BOOLEAN value
                                $query .= ' DEFAULT \''
                                . $dbi->escapeString($default_value) . '\'';
                            }
                        } elseif ($type === 'BINARY' || $type === 'VARBINARY') {
                            $query .= ' DEFAULT 0x' . $default_value;
                        } else {
                            $query .= ' DEFAULT \''
                            . $dbi->escapeString((string) $default_value) . '\'';
                        }
                        break;
                /** @noinspection PhpMissingBreakStatementInspection */
                    case 'NULL':
                        // If user uncheck null checkbox and not change default value null,
                        // default value will be ignored.
                        if ($null !== false && $null !== 'YES') {
                            break;
                        }
                        // else fall-through intended, no break here
                    case 'CURRENT_TIMESTAMP':
                    case 'current_timestamp()':
                        $query .= ' DEFAULT ' . $default_type;

                        if (strlen($length) !== 0
                        && $length !== 0
                        && $is_timestamp
                        && $default_type !== 'NULL' // Not to be added in case of NULL
                        ) {
                            $query .= '(' . $length . ')';
                        }
                        break;
                    case 'NONE':
                    default:
                        break;
                }
            }

            if (! empty($extra)) {
                if ($virtuality) {
                    $extra = trim((string) preg_replace('~^\s*AUTO_INCREMENT\s*~is', ' ', $extra));
                }

                $query .= ' ' . $extra;
            }
        }

        if (! empty($comment)) {
            $query .= " COMMENT '" . $dbi->escapeString($comment) . "'";
        }

        // move column
        if ($move_to === '-first') { // dash can't appear as part of column name
            $query .= ' FIRST';
        } elseif ($move_to != '') {
            $query .= ' AFTER ' . Util::backquote($move_to);
        }
        if (! $virtuality && ! empty($extra)) {
            if ($oldColumnName === null) {
                if (is_array($columns_with_index) && ! in_array($name, $columns_with_index)) {
                    $query .= ', add PRIMARY KEY (' . Util::backquote($name) . ')';
                }
            } else {
                if (is_array($columns_with_index) && ! in_array($oldColumnName, $columns_with_index)) {
                    $query .= ', add PRIMARY KEY (' . Util::backquote($name) . ')';
                }
            }
        }

        return $query;
    }

    /**
     * Checks if the number of records in a table is at least equal to
     * $min_records
     *
     * @param int $min_records Number of records to check for in a table
     *
     * @return bool True, if at least $min_records exist, False otherwise.
     */
    public function checkIfMinRecordsExist($min_records = 0)
    {
        $check_query = 'SELECT ';
        $fieldsToSelect = '';

        $uniqueFields = $this->getUniqueColumns(true, false);
        if (count($uniqueFields) > 0) {
            $fieldsToSelect = implode(', ', $uniqueFields);
        } else {
            $indexedCols = $this->getIndexedColumns(true, false);
            if (count($indexedCols) > 0) {
                $fieldsToSelect = implode(', ', $indexedCols);
            } else {
                $fieldsToSelect = '*';
            }
        }

        $check_query .= $fieldsToSelect
            . ' FROM ' . $this->getFullName(true)
            . ' LIMIT ' . $min_records;

        $res = $this->dbi->tryQuery(
            $check_query
        );

        if ($res !== false) {
            $num_records = $this->dbi->numRows($res);
            if ($num_records >= $min_records) {
                return true;
            }
        }

        return false;
    }

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
        $db = $this->dbName;
        $table = $this->name;

        if ($this->dbi->getCache()->getCachedTableContent([$db, $table, 'ExactRows']) != null) {
            return $this->dbi->getCache()->getCachedTableContent(
                [
                    $db,
                    $table,
                    'ExactRows',
                ]
            );
        }
        $row_count = false;

        if (! $force_exact) {
            if (($this->dbi->getCache()->getCachedTableContent([$db, $table, 'Rows']) == null)
                && ! $is_view
            ) {
                $tmp_tables = $this->dbi->getTablesFull($db, $table);
                if (isset($tmp_tables[$table])) {
                    $this->dbi->getCache()->cacheTableContent(
                        [
                            $db,
                            $table,
                        ],
                        $tmp_tables[$table]
                    );
                }
            }
            if ($this->dbi->getCache()->getCachedTableContent([$db, $table, 'Rows']) != null) {
                $row_count = $this->dbi->getCache()->getCachedTableContent(
                    [
                        $db,
                        $table,
                        'Rows',
                    ]
                );
            } else {
                $row_count = false;
            }
        }
        // for a VIEW, $row_count is always false at this point
        if ($row_count !== false
            && $row_count >= $GLOBALS['cfg']['MaxExactCount']
        ) {
            return $row_count;
        }

        if (! $is_view) {
            $row_count = $this->dbi->fetchValue(
                'SELECT COUNT(*) FROM ' . Util::backquote($db) . '.'
                . Util::backquote($table)
            );
        } else {
            // For complex views, even trying to get a partial record
            // count could bring down a server, so we offer an
            // alternative: setting MaxExactCountViews to 0 will bypass
            // completely the record counting for views

            if ($GLOBALS['cfg']['MaxExactCountViews'] == 0) {
                $row_count = false;
            } else {
                // Counting all rows of a VIEW could be too long,
                // so use a LIMIT clause.
                // Use try_query because it can fail (when a VIEW is
                // based on a table that no longer exists)
                $result = $this->dbi->tryQuery(
                    'SELECT 1 FROM ' . Util::backquote($db) . '.'
                    . Util::backquote($table) . ' LIMIT '
                    . $GLOBALS['cfg']['MaxExactCountViews'],
                    DatabaseInterface::CONNECT_USER,
                    DatabaseInterface::QUERY_STORE
                );
                if (! $this->dbi->getError()) {
                    $row_count = $this->dbi->numRows($result);
                    $this->dbi->freeResult($result);
                }
            }
        }
        if ($row_count) {
            $this->dbi->getCache()->cacheTableContent([$db, $table, 'ExactRows'], $row_count);
        }

        return $row_count;
    }

    /**
     * Generates column specification for ALTER syntax
     *
     * @see Table::generateFieldSpec()
     *
     * @param string      $oldcol             old column name
     * @param string      $newcol             new column name
     * @param string      $type               type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string      $length             length ('2', '5,2', '', ...)
     * @param string      $attribute          attribute
     * @param string      $collation          collation
     * @param bool|string $null               with 'NULL' or 'NOT NULL'
     * @param string      $default_type       whether default is CURRENT_TIMESTAMP,
     *                                        NULL, NONE, USER_DEFINED
     * @param string      $default_value      default value for USER_DEFINED default
     *                                        type
     * @param string      $extra              'AUTO_INCREMENT'
     * @param string      $comment            field comment
     * @param string      $virtuality         virtuality of the column
     * @param string      $expression         expression for the virtual column
     * @param string      $move_to            new position for column
     * @param array       $columns_with_index Fields having PRIMARY or UNIQUE KEY indexes
     *
     * @return string  field specification
     */
    public static function generateAlter(
        $oldcol,
        $newcol,
        $type,
        $length,
        $attribute,
        $collation,
        $null,
        $default_type,
        $default_value,
        $extra,
        $comment,
        $virtuality,
        $expression,
        $move_to,
        $columns_with_index = null
    ) {
        return Util::backquote($oldcol) . ' '
        . self::generateFieldSpec(
            $newcol,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $default_type,
            $default_value,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $move_to,
            $columns_with_index,
            $oldcol
        );
    }

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
     * @return int|bool
     */
    public static function duplicateInfo(
        $work,
        $pma_table,
        array $get_fields,
        array $where_fields,
        array $new_fields
    ) {
        global $dbi;

        $relation = new Relation($dbi);
        $last_id = -1;

        if (! isset($GLOBALS['cfgRelation']) || ! $GLOBALS['cfgRelation'][$work]) {
            return true;
        }

        $select_parts = [];
        $row_fields = [];
        foreach ($get_fields as $get_field) {
            $select_parts[] = Util::backquote($get_field);
            $row_fields[$get_field] = 'cc';
        }

        $where_parts = [];
        foreach ($where_fields as $_where => $_value) {
            $where_parts[] = Util::backquote($_where) . ' = \''
                . $dbi->escapeString((string) $_value) . '\'';
        }

        $new_parts = [];
        $new_value_parts = [];
        foreach ($new_fields as $_where => $_value) {
            $new_parts[] = Util::backquote($_where);
            $new_value_parts[] = $dbi->escapeString((string) $_value);
        }

        $table_copy_query = '
            SELECT ' . implode(', ', $select_parts) . '
              FROM ' . Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
              . Util::backquote($GLOBALS['cfgRelation'][$pma_table]) . '
             WHERE ' . implode(' AND ', $where_parts);

        // must use DatabaseInterface::QUERY_STORE here, since we execute
        // another query inside the loop
        $table_copy_rs = $relation->queryAsControlUser(
            $table_copy_query,
            true,
            DatabaseInterface::QUERY_STORE
        );

        while ($table_copy_row = @$dbi->fetchAssoc($table_copy_rs)) {
            $value_parts = [];
            foreach ($table_copy_row as $_key => $_val) {
                if (! isset($row_fields[$_key]) || $row_fields[$_key] != 'cc') {
                    continue;
                }

                $value_parts[] = $dbi->escapeString($_val);
            }

            $new_table_query = 'INSERT IGNORE INTO '
                . Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . Util::backquote($GLOBALS['cfgRelation'][$pma_table])
                . ' (' . implode(', ', $select_parts) . ', '
                . implode(', ', $new_parts) . ') VALUES (\''
                . implode('\', \'', $value_parts) . '\', \''
                . implode('\', \'', $new_value_parts) . '\')';

            $relation->queryAsControlUser($new_table_query);
            $last_id = $dbi->insertId();
        }

        $dbi->freeResult($table_copy_rs);

        return $last_id;
    }

    /**
     * Copies or renames table
     *
     * @param string      $source_db    source database
     * @param string      $source_table source table
     * @param string|null $target_db    target database
     * @param string      $target_table target table
     * @param string      $what         what to be moved or copied (data, dataonly)
     * @param bool        $move         whether to move
     * @param string      $mode         mode
     *
     * @return bool true if success, false otherwise
     */
    public static function moveCopy(
        $source_db,
        $source_table,
        ?string $target_db,
        $target_table,
        $what,
        $move,
        $mode
    ) {
        global $err_url, $dbi;

        $relation = new Relation($dbi);

        // Try moving the tables directly, using native `RENAME` statement.
        if ($move && $what === 'data') {
            $tbl = new Table($source_table, $source_db);
            if ($tbl->rename($target_table, $target_db)) {
                $GLOBALS['message'] = $tbl->getLastMessage();

                return true;
            }
        }

        // Setting required export settings.
        $GLOBALS['sql_backquotes'] = 1;
        $GLOBALS['asfile']         = 1;

        // Ensuring the target database is valid.
        if (! $GLOBALS['dblist']->databases->exists($source_db, $target_db)) {
            if (! $GLOBALS['dblist']->databases->exists($source_db)) {
                $GLOBALS['message'] = Message::rawError(
                    sprintf(
                        __('Source database `%s` was not found!'),
                        htmlspecialchars($source_db)
                    )
                );
            }
            if (! $GLOBALS['dblist']->databases->exists($target_db)) {
                $GLOBALS['message'] = Message::rawError(
                    sprintf(
                        __('Target database `%s` was not found!'),
                        htmlspecialchars((string) $target_db)
                    )
                );
            }

            return false;
        }

        /**
         * The full name of source table, quoted.
         *
         * @var string $source
         */
        $source = Util::backquote($source_db)
            . '.' . Util::backquote($source_table);

        // If the target database is not specified, the operation is taking
        // place in the same database.
        if (! isset($target_db) || strlen($target_db) === 0) {
            $target_db = $source_db;
        }

        // Selecting the database could avoid some problems with replicated
        // databases, when moving table from replicated one to not replicated one.
        $dbi->selectDb($target_db);

        /**
         * The full name of target table, quoted.
         *
         * @var string $target
         */
        $target = Util::backquote($target_db)
            . '.' . Util::backquote($target_table);

        // No table is created when this is a data-only operation.
        if ($what !== 'dataonly') {
            /**
             * Instance used for exporting the current structure of the table.
             *
             * @var ExportSql $export_sql_plugin
             */
            $export_sql_plugin = Plugins::getPlugin(
                'export',
                'sql',
                'libraries/classes/Plugins/Export/',
                [
                    'export_type' => 'table',
                    'single_table' => false,
                ]
            );

            $no_constraints_comments = true;
            $GLOBALS['sql_constraints_query'] = '';
            // set the value of global sql_auto_increment variable
            if (isset($_POST['sql_auto_increment'])) {
                $GLOBALS['sql_auto_increment'] = $_POST['sql_auto_increment'];
            }

            /**
             * The old structure of the table..
             *
             * @var string $sql_structure
             */
            $sql_structure = $export_sql_plugin->getTableDef(
                $source_db,
                $source_table,
                "\n",
                $err_url,
                false,
                false
            );

            unset($no_constraints_comments);

            // -----------------------------------------------------------------
            // Phase 0: Preparing structures used.

            /**
             * The destination where the table is moved or copied to.
             *
             * @var Expression
             */
            $destination = new Expression(
                $target_db,
                $target_table,
                ''
            );

            // Find server's SQL mode so the builder can generate correct
            // queries.
            // One of the options that alters the behaviour is `ANSI_QUOTES`.
            Context::setMode(
                $dbi->fetchValue('SELECT @@sql_mode')
            );

            // -----------------------------------------------------------------
            // Phase 1: Dropping existent element of the same name (if exists
            // and required).

            if (isset($_POST['drop_if_exists'])
                && $_POST['drop_if_exists'] === 'true'
            ) {

                /**
                 * Drop statement used for building the query.
                 *
                 * @var DropStatement $statement
                 */
                $statement = new DropStatement();

                $tbl = new Table($target_db, $target_table);

                $statement->options = new OptionsArray(
                    [
                        $tbl->isView() ? 'VIEW' : 'TABLE',
                        'IF EXISTS',
                    ]
                );

                $statement->fields = [$destination];

                // Building the query.
                $drop_query = $statement->build() . ';';

                // Executing it.
                $dbi->query($drop_query);
                $GLOBALS['sql_query'] .= "\n" . $drop_query;

                // If an existing table gets deleted, maintain any entries for
                // the PMA_* tables.
                $maintain_relations = true;
            }

            // -----------------------------------------------------------------
            // Phase 2: Generating the new query of this structure.

            /**
             * The parser responsible for parsing the old queries.
             *
             * @var Parser $parser
             */
            $parser = new Parser($sql_structure);

            if (! empty($parser->statements[0])) {

                /**
                 * The CREATE statement of this structure.
                 *
                 * @var CreateStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the destination.
                $statement->name = $destination;

                // Building back the query.
                $sql_structure = $statement->build() . ';';

                // This is to avoid some issues when renaming databases with views
                // See: https://github.com/phpmyadmin/phpmyadmin/issues/16422
                if ($move) {
                    $dbi->selectDb($target_db);
                }

                // Executing it
                $dbi->query($sql_structure);
                $GLOBALS['sql_query'] .= "\n" . $sql_structure;
            }

            // -----------------------------------------------------------------
            // Phase 3: Adding constraints.
            // All constraint names are removed because they must be unique.

            if (($move || isset($GLOBALS['add_constraints']))
                && ! empty($GLOBALS['sql_constraints_query'])
            ) {
                $parser = new Parser($GLOBALS['sql_constraints_query']);

                /**
                 * The ALTER statement that generates the constraints.
                 *
                 * @var AlterStatement $statement
                 */
                $statement = $parser->statements[0];

                // Changing the altered table to the destination.
                $statement->table = $destination;

                // Removing the name of the constraints.
                foreach ($statement->altered as $idx => $altered) {
                    // All constraint names are removed because they must be unique.
                    if (! $altered->options->has('CONSTRAINT')) {
                        continue;
                    }

                    $altered->field = null;
                }

                // Building back the query.
                $GLOBALS['sql_constraints_query'] = $statement->build() . ';';

                // Executing it.
                if ($mode === 'one_table') {
                    $dbi->query($GLOBALS['sql_constraints_query']);
                }
                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_constraints_query'];
                if ($mode === 'one_table') {
                    unset($GLOBALS['sql_constraints_query']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 4: Adding indexes.
            // View phase 3.

            if (! empty($GLOBALS['sql_indexes'])) {
                $parser = new Parser($GLOBALS['sql_indexes']);

                $GLOBALS['sql_indexes'] = '';
                /**
                 * The ALTER statement that generates the indexes.
                 *
                 * @var AlterStatement $statement
                 */
                foreach ($parser->statements as $statement) {
                    // Changing the altered table to the destination.
                    $statement->table = $destination;

                    // Removing the name of the constraints.
                    foreach ($statement->altered as $idx => $altered) {
                        // All constraint names are removed because they must be unique.
                        if (! $altered->options->has('CONSTRAINT')) {
                            continue;
                        }

                        $altered->field = null;
                    }

                    // Building back the query.
                    $sql_index = $statement->build() . ';';

                    // Executing it.
                    if ($mode === 'one_table' || $mode === 'db_copy') {
                        $dbi->query($sql_index);
                    }

                    $GLOBALS['sql_indexes'] .= $sql_index;
                }

                $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_indexes'];
                if ($mode === 'one_table' || $mode === 'db_copy') {
                    unset($GLOBALS['sql_indexes']);
                }
            }

            // -----------------------------------------------------------------
            // Phase 5: Adding AUTO_INCREMENT.

            if (! empty($GLOBALS['sql_auto_increments'])) {
                if ($mode === 'one_table' || $mode === 'db_copy') {
                    $parser =  new Parser($GLOBALS['sql_auto_increments']);

                    /**
                     * The ALTER statement that alters the AUTO_INCREMENT value.
                     *
                     * @var AlterStatement $statement
                     */
                    $statement = $parser->statements[0];

                    // Changing the altered table to the destination.
                    $statement->table = $destination;

                    // Building back the query.
                    $GLOBALS['sql_auto_increments'] = $statement->build() . ';';

                    // Executing it.
                    $dbi->query($GLOBALS['sql_auto_increments']);
                    $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_auto_increments'];
                    unset($GLOBALS['sql_auto_increments']);
                }
            }
        } else {
            $GLOBALS['sql_query'] = '';
        }

        $_table = new Table($target_table, $target_db);
        // Copy the data unless this is a VIEW
        if (($what === 'data' || $what === 'dataonly')
            && ! $_table->isView()
        ) {
            $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
            $dbi->query($sql_set_mode);
            $GLOBALS['sql_query'] .= "\n\n" . $sql_set_mode . ';';

            $_old_table = new Table($source_table, $source_db);
            $nonGeneratedCols = $_old_table->getNonGeneratedColumns(true);
            if (count($nonGeneratedCols) > 0) {
                $sql_insert_data = 'INSERT INTO ' . $target . '('
                    . implode(', ', $nonGeneratedCols)
                    . ') SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . $source;

                $dbi->query($sql_insert_data);
                $GLOBALS['sql_query'] .= "\n\n" . $sql_insert_data . ';';
            }
        }

        $relation->getRelationsParam();

        // Drops old table if the user has requested to move it
        if ($move) {
            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            $dbi->selectDb($source_db);

            $_source_table = new Table($source_table, $source_db);
            if ($_source_table->isView()) {
                $sql_drop_query = 'DROP VIEW';
            } else {
                $sql_drop_query = 'DROP TABLE';
            }
            $sql_drop_query .= ' ' . $source;
            $dbi->query($sql_drop_query);

            // Rename table in configuration storage
            $relation->renameTable(
                $source_db,
                $target_db,
                $source_table,
                $target_table
            );

            $GLOBALS['sql_query'] .= "\n\n" . $sql_drop_query . ';';

            return true;
        }

        // we are copying
        // Create new entries as duplicates from old PMA DBs
        if ($what === 'dataonly' || isset($maintain_relations)) {
            return true;
        }

        if ($GLOBALS['cfgRelation']['commwork']) {
            // Get all comments and MIME-Types for current table
            $comments_copy_rs = $relation->queryAsControlUser(
                'SELECT column_name, comment'
                . ($GLOBALS['cfgRelation']['mimework']
                ? ', mimetype, transformation, transformation_options'
                : '')
                . ' FROM '
                . Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.'
                . Util::backquote($GLOBALS['cfgRelation']['column_info'])
                . ' WHERE '
                . ' db_name = \''
                . $dbi->escapeString($source_db) . '\''
                . ' AND '
                . ' table_name = \''
                . $dbi->escapeString((string) $source_table) . '\''
            );

            // Write every comment as new copied entry. [MIME]
            while ($comments_copy_row = $dbi->fetchAssoc($comments_copy_rs)) {
                $new_comment_query = 'REPLACE INTO '
                    . Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . Util::backquote(
                        $GLOBALS['cfgRelation']['column_info']
                    )
                    . ' (db_name, table_name, column_name, comment'
                    . ($GLOBALS['cfgRelation']['mimework']
                        ? ', mimetype, transformation, transformation_options'
                        : '')
                    . ') VALUES(\'' . $dbi->escapeString($target_db)
                    . '\',\'' . $dbi->escapeString($target_table) . '\',\''
                    . $dbi->escapeString($comments_copy_row['column_name'])
                    . '\',\''
                    . $dbi->escapeString($comments_copy_row['comment'])
                    . '\''
                    . ($GLOBALS['cfgRelation']['mimework']
                        ? ',\'' . $dbi->escapeString(
                            $comments_copy_row['mimetype']
                        )
                        . '\',\'' . $dbi->escapeString(
                            $comments_copy_row['transformation']
                        )
                        . '\',\'' . $dbi->escapeString(
                            $comments_copy_row['transformation_options']
                        )
                        . '\''
                        : '')
                    . ')';
                $relation->queryAsControlUser($new_comment_query);
            }
            $dbi->freeResult($comments_copy_rs);
            unset($comments_copy_rs);
        }

        // duplicating the bookmarks must not be done here, but
        // just once per db

        $get_fields = ['display_field'];
        $where_fields = [
            'db_name' => $source_db,
            'table_name' => $source_table,
        ];
        $new_fields = [
            'db_name' => $target_db,
            'table_name' => $target_table,
        ];
        self::duplicateInfo(
            'displaywork',
            'table_info',
            $get_fields,
            $where_fields,
            $new_fields
        );

        /**
         * @todo revise this code when we support cross-db relations
         */
        $get_fields = [
            'master_field',
            'foreign_table',
            'foreign_field',
        ];
        $where_fields = [
            'master_db' => $source_db,
            'master_table' => $source_table,
        ];
        $new_fields = [
            'master_db' => $target_db,
            'foreign_db' => $target_db,
            'master_table' => $target_table,
        ];
        self::duplicateInfo(
            'relwork',
            'relation',
            $get_fields,
            $where_fields,
            $new_fields
        );

        $get_fields = [
            'foreign_field',
            'master_table',
            'master_field',
        ];
        $where_fields = [
            'foreign_db' => $source_db,
            'foreign_table' => $source_table,
        ];
        $new_fields = [
            'master_db' => $target_db,
            'foreign_db' => $target_db,
            'foreign_table' => $target_table,
        ];
        self::duplicateInfo(
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
        $last_id = self::duplicateInfo(
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
            self::duplicateInfo(
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
     * @see  https://dev.mysql.com/doc/refman/5.0/en/legal-names.html
     *
     * @param string $table_name    name to check
     * @param bool   $is_backquoted whether this name is used inside backquotes or not
     *
     * @return bool whether the string is valid or not
     *
     * @todo add check for valid chars in filename on current system/os
     */
    public static function isValidName($table_name, $is_backquoted = false)
    {
        if ($table_name !== rtrim((string) $table_name)) {
            // trailing spaces not allowed even in backquotes
            return false;
        }

        if (strlen($table_name) === 0) {
            // zero length
            return false;
        }

        if (! $is_backquoted && $table_name !== trim($table_name)) {
            // spaces at the start or in between only allowed inside backquotes
            return false;
        }

        if (! $is_backquoted && preg_match('/^[a-zA-Z0-9_$]+$/', $table_name)) {
            // only allow the above regex in unquoted identifiers
            // see : https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
            return true;
        }

        // If backquoted, all characters should be allowed (except w/ trailing spaces).
        return $is_backquoted;
    }

    /**
     * renames table
     *
     * @param string $new_name new table name
     * @param string $new_db   new database name
     *
     * @return bool success
     */
    public function rename($new_name, $new_db = null)
    {
        if ($this->dbi->getLowerCaseNames() === '1') {
            $new_name = strtolower($new_name);
        }

        if ($new_db !== null && $new_db !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $GLOBALS['dblist']->databases->exists($new_db)) {
                $this->errors[] = __('Invalid database:') . ' ' . $new_db;

                return false;
            }
        } else {
            $new_db = $this->getDbName();
        }

        $new_table = new Table($new_name, $new_db);

        if ($this->getFullName() === $new_table->getFullName()) {
            return true;
        }

        // Allow whitespaces (not trailing) in $new_name,
        // since we are using $backquoted in getting the fullName of table
        // below to be used in the query
        if (! self::isValidName($new_name, true)) {
            $this->errors[] = __('Invalid table name:') . ' '
                . $new_table->getFullName();

            return false;
        }

        // If the table is moved to a different database drop its triggers first
        $triggers = $this->dbi->getTriggers(
            $this->getDbName(),
            $this->getName(),
            ''
        );
        $handle_triggers = $this->getDbName() != $new_db && $triggers;
        if ($handle_triggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS '
                    . Util::backquote($this->getDbName())
                    . '.' . Util::backquote($trigger['name']) . ';';
                $this->dbi->query($sql);
            }
        }

        /*
         * tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
         */
        $GLOBALS['sql_query'] = '
            RENAME TABLE ' . $this->getFullName(true) . '
                  TO ' . $new_table->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if (! $this->dbi->query($GLOBALS['sql_query'])) {
            // Restore triggers in the old database
            if ($handle_triggers) {
                $this->dbi->selectDb($this->getDbName());
                foreach ($triggers as $trigger) {
                    $this->dbi->query($trigger['create']);
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
        $this->name = $new_name;
        $this->dbName = $new_db;

        // Rename table in configuration storage
        $this->relation->renameTable(
            $old_db,
            $new_db,
            $old_name,
            $new_name
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
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Non_unique = 0'
        );
        $uniques = $this->dbi->fetchResult(
            $sql,
            [
                'Key_name',
                null,
            ],
            'Column_name'
        );

        $return = [];
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
                $possible_column .= Util::backquote($index[0]);
            } else {
                $possible_column .= $index[0];
            }
            // a column might have a primary and an unique index on it
            if (in_array($possible_column, $return)) {
                continue;
            }

            $return[] = $possible_column;
        }

        return $return;
    }

    /**
     * Formats lists of columns
     *
     * returns an array with all columns that make use of an index
     *
     * e.g. index(col1, col2) would return col1, col2
     *
     * @param array $indexed    column data
     * @param bool  $backquoted whether to quote name with backticks ``
     * @param bool  $fullName   whether to include full name of the table as a prefix
     *
     * @return array
     */
    private function formatColumns(array $indexed, $backquoted, $fullName)
    {
        $return = [];
        foreach ($indexed as $column) {
            $return[] = ($fullName ? $this->getFullName($backquoted) . '.' : '')
                . ($backquoted ? Util::backquote($column) : $column);
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
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            ''
        );
        $indexed = $this->dbi->fetchResult($sql, 'Column_name', 'Column_name');

        return $this->formatColumns($indexed, $backquoted, $fullName);
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
        $indexed = $this->dbi->fetchResult($sql, 'Field', 'Field');

        return $this->formatColumns($indexed, $backquoted, $fullName);
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
            Util::backquote($this->dbName),
            Util::backquote($this->name)
        );
        $move_columns_sql_result = $this->dbi->tryQuery($move_columns_sql_query);
        if ($move_columns_sql_result !== false) {
            return $this->dbi->getFieldsMeta($move_columns_sql_result);
        }

        // unsure how to reproduce but it was seen on the reporting server
        return [];
    }

    /**
     * Get non-generated columns in table
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return array
     */
    public function getNonGeneratedColumns($backquoted = true)
    {
        $columns_meta_query = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $ret = [];

        $columns_meta_query_result = $this->dbi->fetchResult(
            $columns_meta_query
        );

        if ($columns_meta_query_result
            && $columns_meta_query_result !== false
        ) {
            foreach ($columns_meta_query_result as $column) {
                $value = $column['Field'];
                if ($backquoted === true) {
                    $value = Util::backquote($value);
                }

                // If contains GENERATED or VIRTUAL and does not contain DEFAULT_GENERATED
                if ((
                    strpos($column['Extra'], 'GENERATED') !== false
                    || strpos($column['Extra'], 'VIRTUAL') !== false
                    ) && strpos($column['Extra'], 'DEFAULT_GENERATED') === false
                ) {
                    continue;
                }

                $ret[] = $value;
            }
        }

        return $ret;
    }

    /**
     * Return UI preferences for this table from phpMyAdmin database.
     *
     * @return array
     */
    protected function getUiPrefsFromDb()
    {
        $cfgRelation = $this->relation->getRelationsParam();
        $pma_table = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['table_uiprefs']);

        // Read from phpMyAdmin database
        $sql_query = ' SELECT `prefs` FROM ' . $pma_table
            . " WHERE `username` = '" . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `db_name` = '" . $this->dbi->escapeString($this->dbName) . "'"
            . " AND `table_name` = '" . $this->dbi->escapeString($this->name) . "'";

        $row = $this->dbi->fetchArray($this->relation->queryAsControlUser($sql_query));
        if (isset($row[0])) {
            return json_decode($row[0], true);
        }

        return [];
    }

    /**
     * Save this table's UI preferences into phpMyAdmin database.
     *
     * @return true|Message
     */
    protected function saveUiPrefsToDb()
    {
        $cfgRelation = $this->relation->getRelationsParam();
        $pma_table = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['table_uiprefs']);

        $secureDbName = $this->dbi->escapeString($this->dbName);

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query = ' REPLACE INTO ' . $pma_table
            . " (username, db_name, table_name, prefs) VALUES ('"
            . $this->dbi->escapeString($username) . "', '" . $secureDbName
            . "', '" . $this->dbi->escapeString($this->name) . "', '"
            . $this->dbi->escapeString(json_encode($this->uiprefs)) . "')";

        $success = $this->dbi->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $message = Message::error(
                __('Could not save table UI preferences!')
            );
            $message->addMessage(
                Message::rawError(
                    (string) $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)
                ),
                '<br><br>'
            );

            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured
        // maximum rows
        $sql_query = 'SELECT COUNT(*) FROM ' . $pma_table;
        $rows_count = $this->dbi->fetchValue($sql_query);
        $max_rows = $GLOBALS['cfg']['Server']['MaxTableUiprefs'];
        if ($rows_count > $max_rows) {
            $num_rows_to_delete = $rows_count - $max_rows;
            $sql_query
                = ' DELETE FROM ' . $pma_table .
                ' ORDER BY last_update ASC' .
                ' LIMIT ' . $num_rows_to_delete;
            $success = $this->dbi->tryQuery(
                $sql_query,
                DatabaseInterface::CONNECT_CONTROL
            );

            if (! $success) {
                $message = Message::error(
                    sprintf(
                        __(
                            'Failed to cleanup table UI preferences (see ' .
                            '$cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'
                        ),
                        MySQLDocumentation::showDocumentation('config', 'cfg_Servers_MaxTableUiprefs')
                    )
                );
                $message->addMessage(
                    Message::rawError(
                        (string) $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL)
                    ),
                    '<br><br>'
                );

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
        $cfgRelation = $this->relation->getRelationsParam();
        $server_id = $GLOBALS['server'];

        // set session variable if it's still undefined
        if (! isset($_SESSION['tmpval']['table_uiprefs'][$server_id][$this->dbName][$this->name])) {
            // check whether we can get from pmadb
            $uiPrefs = $cfgRelation['uiprefswork'] ? $this->getUiPrefsFromDb() : [];
            $_SESSION['tmpval']['table_uiprefs'][$server_id][$this->dbName][$this->name] = $uiPrefs;
        }
        $this->uiprefs =& $_SESSION['tmpval']['table_uiprefs'][$server_id][$this->dbName][$this->name];
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
            if (! isset($this->uiprefs[$property])) {
                return false;
            }

            if (! isset($_POST['discard_remembered_sort'])) {
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
                        mb_strlen($each_col) - mb_strlen($colname)
                    ) === 0
                    ) {
                        return $this->uiprefs[$property];
                    }
                }
            }
            // remove the property, since it no longer exists in database
            $this->removeUiProp($property);

            return false;
        }

        if ($property == self::PROP_COLUMN_ORDER
            || $property == self::PROP_COLUMN_VISIB
        ) {
            if ($this->isView() || ! isset($this->uiprefs[$property])) {
                return false;
            }

            // check if the table has not been modified
            if ($this->getStatusInfo('Create_time') == $this->uiprefs['CREATE_TIME']
            ) {
                return array_map('intval', $this->uiprefs[$property]);
            }

            // remove the property, since the table has been modified
            $this->removeUiProp($property);

            return false;
        }

        // default behaviour for other property:
        return $this->uiprefs[$property] ?? false;
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
     * @return bool|Message
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
            if (! isset($table_create_time)
                || $table_create_time != $curr_create_time
            ) {
                // there is no $table_create_time, or
                // supplied $table_create_time is older than current create time,
                // so don't save
                return Message::error(
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

            $this->uiprefs['CREATE_TIME'] = $curr_create_time;
        }
        // save the value
        $this->uiprefs[$property] = $value;

        // check if pmadb is set
        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['uiprefswork']) {
            return $this->saveUiPrefsToDb();
        }

        return true;
    }

    /**
     * Remove a property from UI preferences.
     *
     * @param string $property the property
     *
     * @return true|Message
     */
    public function removeUiProp($property)
    {
        if (! isset($this->uiprefs)) {
            $this->loadUiPrefs();
        }
        if (isset($this->uiprefs[$property])) {
            unset($this->uiprefs[$property]);

            // check if pmadb is set
            $cfgRelation = $this->relation->getRelationsParam();
            if ($cfgRelation['uiprefswork']) {
                return $this->saveUiPrefsToDb();
            }
        }

        return true;
    }

    /**
     * Get all column names which are MySQL reserved words
     *
     * @return array
     *
     * @access public
     */
    public function getReservedColumnNames()
    {
        $columns = $this->getColumns(false);
        $return = [];
        foreach ($columns as $column) {
            $temp = explode('.', $column);
            $column_name = $temp[2];
            if (! Context::isKeyword($column_name, true)) {
                continue;
            }

            $return[] = $column_name;
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
        $columns = [];
        foreach ($this->dbi->getColumnsFull(
            $this->dbName,
            $this->name
        ) as $row) {
            if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
                $tmp[2] = mb_substr(
                    (string) preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]),
                    1
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
     * @return Index
     */
    public function getIndex($index)
    {
        return Index::singleton($this->dbName, $this->name, $index);
    }

    /**
     * Function to get the sql query for index creation or edit
     *
     * @param Index $index current index
     * @param bool  $error whether error occurred or not
     *
     * @return string
     */
    public function getSqlQueryForIndexCreateOrEdit($index, &$error)
    {
        // $sql_query is the one displayed in the query box
        $sql_query = sprintf(
            'ALTER TABLE %s.%s',
            Util::backquote($this->dbName),
            Util::backquote($this->name)
        );

        // Drops the old index
        if (! empty($_POST['old_index'])) {
            if ($_POST['old_index'] === 'PRIMARY') {
                $sql_query .= ' DROP PRIMARY KEY,';
            } else {
                $sql_query .= sprintf(
                    ' DROP INDEX %s,',
                    Util::backquote($_POST['old_index'])
                );
            }
        }

        // Builds the new one
        switch ($index->getChoice()) {
            case 'PRIMARY':
                if ($index->getName() == '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!')
                    );
                }
                $sql_query .= ' ADD PRIMARY KEY';
                break;
            case 'FULLTEXT':
            case 'UNIQUE':
            case 'INDEX':
            case 'SPATIAL':
                if ($index->getName() === 'PRIMARY') {
                    $error = Message::error(
                        __('Can\'t rename index to PRIMARY!')
                    );
                }
                $sql_query .= sprintf(
                    ' ADD %s ',
                    $index->getChoice()
                );
                if ($index->getName()) {
                    $sql_query .= Util::backquote($index->getName());
                }
                break;
        }

        $index_fields = [];
        foreach ($index->getColumns() as $key => $column) {
            $index_fields[$key] = Util::backquote($column->getName());
            if (! $column->getSubPart()) {
                continue;
            }

            $index_fields[$key] .= '(' . $column->getSubPart() . ')';
        }

        if (empty($index_fields)) {
            $error = Message::error(__('No index parts defined!'));
        } else {
            $sql_query .= ' (' . implode(', ', $index_fields) . ')';
        }

        $keyBlockSizes = $index->getKeyBlockSize();
        if (! empty($keyBlockSizes)) {
            $sql_query .= sprintf(
                ' KEY_BLOCK_SIZE = %s',
                $this->dbi->escapeString($keyBlockSizes)
            );
        }

        // specifying index type is allowed only for primary, unique and index only
        // TokuDB is using Fractal Tree, Using Type is not useless
        // Ref: https://mariadb.com/kb/en/mariadb/storage-engine-index-types/
        $type = $index->getType();
        if ($index->getChoice() !== 'SPATIAL'
            && $index->getChoice() !== 'FULLTEXT'
            && in_array($type, Index::getIndexTypes())
            && ! $this->isEngine(['TOKUDB'])
        ) {
            $sql_query .= ' USING ' . $type;
        }

        $parser = $index->getParser();
        if ($index->getChoice() === 'FULLTEXT' && ! empty($parser)) {
            $sql_query .= ' WITH PARSER ' . $this->dbi->escapeString($parser);
        }

        $comment = $index->getComment();
        if (! empty($comment)) {
            $sql_query .= sprintf(
                " COMMENT '%s'",
                $this->dbi->escapeString($comment)
            );
        }

        $sql_query .= ';';

        return $sql_query;
    }

    /**
     * Function to handle update for display field
     *
     * @param string $display_field display field
     * @param array  $cfgRelation   configuration relation
     *
     * @return bool True on update succeed or False on failure
     */
    public function updateDisplayField($display_field, array $cfgRelation)
    {
        $upd_query = false;
        if ($display_field == '') {
            $upd_query = 'DELETE FROM '
                . Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \''
                . $this->dbi->escapeString($this->dbName) . '\''
                . ' AND table_name = \''
                . $this->dbi->escapeString($this->name) . '\'';
        } else {
            $upd_query = 'REPLACE INTO '
                . Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . Util::backquote($cfgRelation['table_info'])
                . '(db_name, table_name, display_field) VALUES('
                . '\'' . $this->dbi->escapeString($this->dbName) . '\','
                . '\'' . $this->dbi->escapeString($this->name) . '\','
                . '\'' . $this->dbi->escapeString($display_field) . '\')';
        }

        if ($upd_query) {
            $this->dbi->query(
                $upd_query,
                DatabaseInterface::CONNECT_CONTROL,
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
     * @return bool
     */
    public function updateInternalRelations(
        array $multi_edit_columns_name,
        array $destination_db,
        array $destination_table,
        array $destination_column,
        array $cfgRelation,
        $existrel
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
                        . Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.' . Util::backquote($cfgRelation['relation'])
                        . '(master_db, master_table, master_field, foreign_db,'
                        . ' foreign_table, foreign_field)'
                        . ' values('
                        . '\'' . $this->dbi->escapeString($this->dbName) . '\', '
                        . '\'' . $this->dbi->escapeString($this->name) . '\', '
                        . '\'' . $this->dbi->escapeString($master_field) . '\', '
                        . '\'' . $this->dbi->escapeString($foreign_db) . '\', '
                        . '\'' . $this->dbi->escapeString($foreign_table) . '\','
                        . '\'' . $this->dbi->escapeString($foreign_field) . '\')';
                } elseif ($existrel[$master_field]['foreign_db'] != $foreign_db
                    || $existrel[$master_field]['foreign_table'] != $foreign_table
                    || $existrel[$master_field]['foreign_field'] != $foreign_field
                ) {
                    $upd_query  = 'UPDATE '
                        . Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.' . Util::backquote($cfgRelation['relation'])
                        . ' SET foreign_db       = \''
                        . $this->dbi->escapeString($foreign_db) . '\', '
                        . ' foreign_table    = \''
                        . $this->dbi->escapeString($foreign_table) . '\', '
                        . ' foreign_field    = \''
                        . $this->dbi->escapeString($foreign_field) . '\' '
                        . ' WHERE master_db  = \''
                        . $this->dbi->escapeString($this->dbName) . '\''
                        . ' AND master_table = \''
                        . $this->dbi->escapeString($this->name) . '\''
                        . ' AND master_field = \''
                        . $this->dbi->escapeString($master_field) . '\'';
                }
            } elseif (isset($existrel[$master_field])) {
                $upd_query = 'DELETE FROM '
                    . Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . Util::backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \''
                    . $this->dbi->escapeString($this->dbName) . '\''
                    . ' AND master_table = \''
                    . $this->dbi->escapeString($this->name) . '\''
                    . ' AND master_field = \''
                    . $this->dbi->escapeString($master_field) . '\'';
            }

            if (! isset($upd_query)) {
                continue;
            }

            $this->dbi->query(
                $upd_query,
                DatabaseInterface::CONNECT_CONTROL,
                0,
                false
            );
            $updated = true;
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
    public function updateForeignKeys(
        array $destination_foreign_db,
        array $multi_edit_columns_name,
        array $destination_foreign_table,
        array $destination_foreign_column,
        array $options_array,
        $table,
        array $existrel_foreign
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

                if (! empty($one_field) || ! empty($foreign_field[$key])) {
                    continue;
                }

                unset($master_field[$key], $foreign_field[$key]);
            }

            if (! empty($foreign_db)
                && ! empty($foreign_table)
                && ! $empty_fields
            ) {
                if (isset($existrel_foreign[$master_field_md5])) {
                    $constraint_name
                        = $existrel_foreign[$master_field_md5]['constraint'];
                    $on_delete = ! empty(
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
                        || $_POST['constraint_name'][$master_field_md5] != $constraint_name
                        || ($_POST['on_delete'][$master_field_md5] != $on_delete)
                        || ($_POST['on_update'][$master_field_md5] != $on_update)
                    ) {
                        // another foreign key is already defined for this field
                        // or an option has been changed for ON DELETE or ON UPDATE
                        $drop = true;
                        $create = true;
                    }
                } else {
                    // no key defined for this field(s)
                    $create = true;
                }
            } elseif (isset($existrel_foreign[$master_field_md5])) {
                $drop = true;
            }

            $tmp_error_drop = false;
            if ($drop) {
                $drop_query = 'ALTER TABLE ' . Util::backquote($table)
                    . ' DROP FOREIGN KEY '
                    . Util::backquote(
                        $existrel_foreign[$master_field_md5]['constraint']
                    )
                    . ';';

                if (! isset($_POST['preview_sql'])) {
                    $display_query .= $drop_query . "\n";
                    $this->dbi->tryQuery($drop_query);
                    $tmp_error_drop = $this->dbi->getError();

                    if (! empty($tmp_error_drop)) {
                        $seen_error = true;
                        $html_output .= Generator::mysqlDie(
                            $tmp_error_drop,
                            $drop_query,
                            false,
                            '',
                            false
                        );
                        continue;
                    }
                } else {
                    $preview_sql_data .= $drop_query . "\n";
                }
            }
            $tmp_error_create = false;
            if (! $create) {
                continue;
            }

            $create_query = $this->getSQLToCreateForeignKey(
                $table,
                $master_field,
                $foreign_db,
                $foreign_table,
                $foreign_field,
                $_POST['constraint_name'][$master_field_md5],
                $options_array[$_POST['on_delete'][$master_field_md5]],
                $options_array[$_POST['on_update'][$master_field_md5]]
            );

            if (! isset($_POST['preview_sql'])) {
                $display_query .= $create_query . "\n";
                $this->dbi->tryQuery($create_query);
                $tmp_error_create = (string) $this->dbi->getError();
                if (! empty($tmp_error_create)) {
                    $seen_error = true;

                    if (substr($tmp_error_create, 1, 4) == '1005') {
                        $message = Message::error(
                            __(
                                'Error creating foreign key on %1$s (check data ' .
                                'types)'
                            )
                        );
                        $message->addParam(implode(', ', $master_field));
                        $html_output .= $message->getDisplay();
                    } else {
                        $html_output .= Generator::mysqlDie(
                            $tmp_error_create,
                            $create_query,
                            false,
                            '',
                            false
                        );
                    }
                    $html_output .= MySQLDocumentation::show(
                        'InnoDB_foreign_key_constraints'
                    ) . "\n";
                }
            } else {
                $preview_sql_data .= $create_query . "\n";
            }

            // this is an alteration and the old constraint has been dropped
            // without creation of a new one
            if (! $drop || ! empty($tmp_error_drop)
                || empty($tmp_error_create)
            ) {
                continue;
            }

            // a rollback may be better here
            $sql_query_recreate = '# Restoring the dropped constraint...' . "\n";
            $sql_query_recreate .= $this->getSQLToCreateForeignKey(
                $table,
                $master_field,
                $existrel_foreign[$master_field_md5]['ref_db_name'],
                $existrel_foreign[$master_field_md5]['ref_table_name'],
                $existrel_foreign[$master_field_md5]['ref_index_list'],
                $existrel_foreign[$master_field_md5]['constraint'],
                $options_array[$existrel_foreign[$master_field_md5]['on_delete'] ?? ''] ?? null,
                $options_array[$existrel_foreign[$master_field_md5]['on_update'] ?? ''] ?? null
            );
            if (! isset($_POST['preview_sql'])) {
                $display_query .= $sql_query_recreate . "\n";
                $this->dbi->tryQuery($sql_query_recreate);
            } else {
                $preview_sql_data .= $sql_query_recreate;
            }
        }

        return [
            $html_output,
            $preview_sql_data,
            $display_query,
            $seen_error,
        ];
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
    private function getSQLToCreateForeignKey(
        $table,
        array $field,
        $foreignDb,
        $foreignTable,
        array $foreignField,
        $name = null,
        $onDelete = null,
        $onUpdate = null
    ) {
        $sql_query  = 'ALTER TABLE ' . Util::backquote($table) . ' ADD ';
        // if user entered a constraint name
        if (! empty($name)) {
            $sql_query .= ' CONSTRAINT ' . Util::backquote($name);
        }

        foreach ($field as $key => $one_field) {
            $field[$key] = Util::backquote($one_field);
        }
        foreach ($foreignField as $key => $one_field) {
            $foreignField[$key] = Util::backquote($one_field);
        }
        $sql_query .= ' FOREIGN KEY (' . implode(', ', $field) . ') REFERENCES '
            . ($this->dbName != $foreignDb
                ? Util::backquote($foreignDb) . '.' : '')
            . Util::backquote($foreignTable)
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
     * @return array|bool associative array of column name and their expressions
     * or false on failure
     */
    public function getColumnGenerationExpression($column = null)
    {
        $serverType = Util::getServerType();
        if ($serverType === 'MySQL'
            && $this->dbi->getVersion() > 50705
            && ! $GLOBALS['cfg']['Server']['DisableIS']
        ) {
            $sql
                = "SELECT
                `COLUMN_NAME` AS `Field`,
                `GENERATION_EXPRESSION` AS `Expression`
                FROM
                `information_schema`.`COLUMNS`
                WHERE
                `TABLE_SCHEMA` = '" . $this->dbi->escapeString($this->dbName) . "'
                AND `TABLE_NAME` = '" . $this->dbi->escapeString($this->name) . "'";
            if ($column != null) {
                $sql .= " AND  `COLUMN_NAME` = '" . $this->dbi->escapeString($column)
                    . "'";
            }

            return $this->dbi->fetchResult($sql, 'Field', 'Expression');
        }

        $createTable = $this->showCreate();
        if (! $createTable) {
            return false;
        }

        $parser = new Parser($createTable);
        /**
         * @var CreateStatement $stmt
        */
        $stmt = $parser->statements[0];
        $fields = TableUtils::getFields($stmt);
        if ($column != null) {
            $expression = isset($fields[$column]['expr']) ?
                substr($fields[$column]['expr'], 1, -1) : '';

            return [$column => $expression];
        }

        $ret = [];
        foreach ($fields as $field => $options) {
            if (! isset($options['expr'])) {
                continue;
            }

            $ret[$field] = substr($options['expr'], 1, -1);
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
        return $this->dbi->fetchValue(
            'SHOW CREATE TABLE ' . Util::backquote($this->dbName) . '.'
            . Util::backquote($this->name),
            0,
            1
        );
    }

    /**
     * Returns the real row count for a table
     */
    public function getRealRowCountTable(): ?int
    {
        // SQL query to get row count for a table.
        $result = $this->dbi->fetchSingleRow(
            sprintf(
                'SELECT COUNT(*) AS %s FROM %s.%s',
                Util::backquote('row_count'),
                Util::backquote($this->dbName),
                Util::backquote($this->name)
            )
        );

        if (! is_array($result)) {
            return null;
        }

        return (int) $result['row_count'];
    }

    /**
     * Get columns with indexes
     *
     * @param int $types types bitmask
     *
     * @return array an array of columns
     */
    public function getColumnsWithIndex($types)
    {
        $columns_with_index = [];
        foreach (Index::getFromTableByChoice(
            $this->name,
            $this->dbName,
            $types
        ) as $index) {
            $columns = $index->getColumns();
            foreach ($columns as $column_name => $dummy) {
                $columns_with_index[] = $column_name;
            }
        }

        return $columns_with_index;
    }
}
