<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\DisplayFeature;
use PhpMyAdmin\ConfigStorage\Features\RelationFeature;
use PhpMyAdmin\ConfigStorage\Features\UiPreferencesFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Utils\ForeignKey;
use PhpMyAdmin\SqlParser\Utils\Table as TableUtils;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Util;
use Stringable;

use function __;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function stripos;
use function strtolower;
use function strtoupper;
use function substr;
use function substr_compare;
use function trim;

/**
 * Handles everything related to tables
 *
 * @todo make use of Message and Error
 */
class Table implements Stringable
{
    /** @var mixed[] UI preferences */
    public array $uiprefs = [];

    /** @var string[] errors occurred */
    public array $errors = [];

    /** @var string[] messages */
    public array $messages = [];

    private Relation $relation;

    /**
     * @param string            $name   table name
     * @param string            $dbName database name
     * @param DatabaseInterface $dbi    database interface for the table
     */
    public function __construct(protected string $name, protected string $dbName, protected DatabaseInterface $dbi)
    {
        $this->relation = new Relation($this->dbi);
    }

    /**
     * returns table name
     *
     * @see Table::getName()
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Table getter
     *
     * @param string            $tableName table name
     * @param string            $dbName    database name
     * @param DatabaseInterface $dbi       database interface for the table
     */
    public static function get(string $tableName, string $dbName, DatabaseInterface $dbi): Table
    {
        return new Table($tableName, $dbName, $dbi);
    }

    /**
     * return the last error
     *
     * @return string the last error
     */
    public function getLastError(): string
    {
        if ($this->errors === []) {
            return '';
        }

        return end($this->errors);
    }

    /**
     * return the last message
     *
     * @return string the last message
     */
    public function getLastMessage(): string
    {
        if ($this->messages === []) {
            return '';
        }

        return end($this->messages);
    }

    /**
     * returns table name
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string  table name
     */
    public function getName(bool $backquoted = false): string
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
    public function getDbName(bool $backquoted = false): string
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
     */
    public function getFullName(bool $backquoted = false): string
    {
        return $this->getDbName($backquoted) . '.'
        . $this->getName($backquoted);
    }

    /**
     * Checks the storage engine used to create table
     *
     * @param string[]|string $engine Checks the table engine against an
     *                             array of engine strings or a single string, should be uppercase
     */
    public function isEngine(array|string $engine): bool
    {
        $engine = (array) $engine;
        $tableStorageEngine = strtoupper($this->getStorageEngine());

        return in_array($tableStorageEngine, $engine, true);
    }

    /**
     * returns whether the table is actually a view
     */
    public function isView(): bool
    {
        if ($this->dbName === '' || $this->name === '') {
            return false;
        }

        // use cached data or load information with SHOW command
        $type = $this->dbi->getCache()->getCachedTableContent($this->dbName, $this->name, 'TABLE_TYPE');
        if ($type === null && Config::getInstance()->selectedServer['DisableIS']) {
            $type = $this->getStatusInfo('TABLE_TYPE');
        }

        if ($type !== null) {
            return $type === 'VIEW' || $type === 'SYSTEM VIEW';
        }

        // information_schema tables are 'SYSTEM VIEW's
        if ($this->dbName === 'information_schema') {
            return true;
        }

        // query information_schema
        return (bool) $this->dbi->fetchValue(
            'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = ' . $this->dbi->quoteString($this->dbName)
            . ' AND TABLE_NAME = ' . $this->dbi->quoteString($this->name),
        );
    }

    /**
     * Returns whether the table is actually an updatable view
     */
    public function isUpdatableView(): bool
    {
        if ($this->dbName === '' || $this->name === '') {
            return false;
        }

        return (bool) $this->dbi->fetchValue(
            'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = ' . $this->dbi->quoteString($this->dbName)
            . ' AND TABLE_NAME = ' . $this->dbi->quoteString($this->name)
            . ' AND IS_UPDATABLE = \'YES\'',
        );
    }

    /**
     * Checks if this is a merge table
     *
     * If the ENGINE of the table is MERGE or MRG_MYISAM (alias),
     * this is a merge table.
     */
    public function isMerge(): bool
    {
        return $this->isEngine(['MERGE', 'MRG_MYISAM']);
    }

    /**
     * Returns full table status info, or specific if $info provided
     * this info is collected from information_schema
     *
     * @param T $info specific information to be fetched
     *
     * @return (T is null ? (string|int|null)[]|null : (string|int|null))
     *
     * @template T of string|null
     */
    public function getStatusInfo(string|null $info = null): array|string|int|null
    {
        $cachedResult = $this->dbi->getCache()->getCachedTableContent($this->dbName, $this->name);

        // sometimes there is only one entry (ExactRows) so
        // we have to get the table's details
        if ($cachedResult === null || count($cachedResult) === 1) {
            $this->dbi->getTablesFull($this->dbName, $this->name);
            $cachedResult = $this->dbi->getCache()->getCachedTableContent($this->dbName, $this->name);
        }

        if ($cachedResult === null) {
            // happens when we enter the table creation dialog
            // or when we really did not get any status info, for example
            // when $table === 'TABLE_NAMES' after the user tried SHOW TABLES
            return null;
        }

        if ($info === null) {
            return $cachedResult;
        }

        return $cachedResult[$info];
    }

    /**
     * Returns the Table storage Engine for current table.
     *
     * @return string                 Return storage engine info if it is set for
     *                                the selected table else return blank.
     */
    public function getStorageEngine(): string
    {
        $tableStorageEngine = $this->getStatusInfo('ENGINE');

        return (string) $tableStorageEngine;
    }

    /**
     * Returns the comments for current table.
     *
     * @return string Return comment info if it is set for the selected table or return blank.
     */
    public function getComment(): string
    {
        return $this->getStatusInfo('TABLE_COMMENT') ?? '';
    }

    /**
     * Returns the collation for current table.
     *
     * @return string Return blank if collation is empty else return the collation info from table info.
     */
    public function getCollation(): string
    {
        return $this->getStatusInfo('TABLE_COLLATION') ?? '';
    }

    /**
     * Returns the info about no of rows for current table.
     *
     * @return int Return no of rows info if it is not null for the selected table or return 0.
     */
    public function getNumRows(): int
    {
        return (int) $this->getStatusInfo('TABLE_ROWS');
    }

    /**
     * Returns the Row format for current table.
     *
     * @return string Return table row format info if it is set for the selected table or return blank.
     */
    public function getRowFormat(): string
    {
        $tableRowFormat = $this->getStatusInfo('ROW_FORMAT');

        return is_string($tableRowFormat) ? $tableRowFormat : '';
    }

    /**
     * Returns the auto increment option for current table.
     *
     * @return string Return auto increment info if it is set for the selected table or return blank.
     */
    public function getAutoIncrement(): string
    {
        $tableAutoIncrement = $this->getStatusInfo('AUTO_INCREMENT');

        return $tableAutoIncrement ?? '';
    }

    /**
     * Returns the array for CREATE statement for current table.
     *
     * @return array<string, string> Return options array info if it is set for the selected table or return blank.
     */
    public function getCreateOptions(): array
    {
        $tableOptions = $this->getStatusInfo('CREATE_OPTIONS');
        $createOptionsTmp = is_string($tableOptions) && $tableOptions !== '' ? explode(' ', $tableOptions) : [];
        $createOptions = [];
        // export create options by its name as variables into global namespace
        // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
        // unset($pack_keys);
        foreach ($createOptionsTmp as $eachCreateOption) {
            $eachCreateOption = explode('=', $eachCreateOption);
            if (! isset($eachCreateOption[1])) {
                continue;
            }

            // ensure there is no ambiguity for PHP 5 and 7
            $createOptions[$eachCreateOption[0]] = $eachCreateOption[1];
        }

        // we need explicit DEFAULT value here (different from '0')
        $hasPackKeys = isset($createOptions['pack_keys']) && $createOptions['pack_keys'] !== '';
        $createOptions['pack_keys'] = $hasPackKeys ? $createOptions['pack_keys'] : 'DEFAULT';

        return $createOptions;
    }

    /**
     * generates column specification for ALTER or CREATE TABLE syntax
     *
     * @param string              $name             name
     * @param string              $type             type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string              $length           length ('2', '5,2', '', ...)
     * @param string              $attribute        attribute
     * @param string              $collation        collation
     * @param bool|string         $null             with 'NULL' or 'NOT NULL'
     * @param string              $defaultType      whether default is CURRENT_TIMESTAMP,
     *                                               NULL, NONE, USER_DEFINED, UUID
     * @param string              $defaultValue     default value for USER_DEFINED
     *                                               default type
     * @param string              $extra            'AUTO_INCREMENT'
     * @param string              $comment          field comment
     * @param string              $virtuality       virtuality of the column
     * @param string              $expression       expression for the virtual column
     * @param string              $moveTo           new position for column
     * @param (string|int)[]|null $columnsWithIndex Fields having PRIMARY or UNIQUE KEY indexes
     * @param string|null         $oldColumnName    Old column name
     *
     * @return string  field specification
     *
     * @todo    move into class PMA_Column
     * @todo on the interface, some js to clear the default value when the
     * default current_timestamp is checked
     */
    public static function generateFieldSpec(
        string $name,
        string $type,
        string $length = '',
        string $attribute = '',
        string $collation = '',
        bool|string $null = false,
        string $defaultType = 'USER_DEFINED',
        string $defaultValue = '',
        string $extra = '',
        string $comment = '',
        string $virtuality = '',
        string $expression = '',
        string $moveTo = '',
        array|null $columnsWithIndex = null,
        string|null $oldColumnName = null,
    ): string {
        $isTimestamp = mb_stripos($type, 'TIMESTAMP') !== false;

        $query = Util::backquote($name) . ' ' . $type;

        // allow the possibility of a length for TIME, DATETIME and TIMESTAMP
        // (will work on MySQL >= 5.6.4)
        //
        // MySQL permits a non-standard syntax for FLOAT and DOUBLE,
        // see https://dev.mysql.com/doc/refman/5.5/en/floating-point-types.html
        $pattern = '@^(DATE|TINYBLOB|TINYTEXT|BLOB|TEXT|'
            . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN|UUID|JSON)$@i';
        $dbi = DatabaseInterface::getInstance();
        if (
            $length !== ''
            && preg_match($pattern, $type) !== 1
            && Compatibility::isIntegersSupportLength($type, $length, $dbi)
        ) {
            // Note: The variable $length here can contain several other things
            // besides length - ENUM/SET value or length of DECIMAL (eg. 12,3)
            // so we can't just convert it to integer
            $query .= '(' . $length . ')';
        }

        if ($attribute !== '') {
            $query .= ' ' . $attribute;

            if ($isTimestamp && stripos($attribute, 'TIMESTAMP') !== false && $length !== '') {
                $query .= '(' . $length . ')';
            }
        }

        // if column is virtual, check if server type is Mysql as only Mysql server
        // supports extra column properties
        $isVirtualColMysql = $virtuality && Compatibility::isMySqlOrPerconaDb();
        // if column is virtual, check if server type is MariaDB as MariaDB server
        // supports no extra virtual column properties except CHARACTER SET for text column types
        $isVirtualColMariaDB = $virtuality && Compatibility::isMariaDb();

        $matches = preg_match('@^(TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT|VARCHAR|CHAR|ENUM|SET)$@i', $type) === 1;
        if ($collation !== '' && $collation !== 'NULL' && $matches) {
            $query .= Util::getCharsetQueryPart(
                $isVirtualColMariaDB ? (string) preg_replace('~_.+~s', '', $collation) : $collation,
                true,
            );
        }

        if ($virtuality !== '') {
            $query .= ' AS (' . $expression . ') ' . $virtuality;
        }

        if ($virtuality === '' || $isVirtualColMysql) {
            if ($null !== false) {
                if ($null === 'YES') {
                    $query .= ' NULL';
                } else {
                    $query .= ' NOT NULL';
                }
            }

            if ($virtuality === '') {
                switch ($defaultType) {
                    case 'USER_DEFINED':
                        if ($isTimestamp && $defaultValue === '0') {
                            // a TIMESTAMP does not accept DEFAULT '0'
                            // but DEFAULT 0 works
                            $query .= ' DEFAULT 0';
                        } elseif (
                            $isTimestamp
                            && preg_match('/^\'\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d(\.\d{1,6})?\'$/', $defaultValue) === 1
                        ) {
                            $query .= ' DEFAULT ' . $defaultValue;
                        } elseif ($type === 'BIT') {
                            $query .= ' DEFAULT b\''
                            . preg_replace('/[^01]/', '0', $defaultValue)
                            . '\'';
                        } elseif ($type === 'BOOLEAN') {
                            if (preg_match('/^1|T|TRUE|YES$/i', $defaultValue) === 1) {
                                $query .= ' DEFAULT TRUE';
                            } elseif (preg_match('/^0|F|FALSE|NO$/i', $defaultValue) === 1) {
                                $query .= ' DEFAULT FALSE';
                            } else {
                                // Invalid BOOLEAN value
                                $query .= ' DEFAULT ' . $dbi->quoteString($defaultValue);
                            }
                        } elseif ($type === 'BINARY' || $type === 'VARBINARY') {
                            $query .= ' DEFAULT 0x' . $defaultValue;
                        } else {
                            $query .= ' DEFAULT ' . $dbi->quoteString($defaultValue);
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
                        $query .= ' DEFAULT ' . $defaultType;

                        if (
                            $length !== ''
                            && $isTimestamp
                            && $defaultType !== 'NULL' // Not to be added in case of NULL
                        ) {
                            $query .= '(' . $length . ')';
                        }

                        break;
                    case 'UUID':
                    case 'uuid()':
                        $query .= ' DEFAULT uuid()';

                        break;
                    case 'NONE':
                    default:
                        break;
                }
            }

            if ($extra !== '') {
                if ($virtuality !== '') {
                    $extra = trim((string) preg_replace('~^\s*AUTO_INCREMENT\s*~is', ' ', $extra));
                }

                $query .= ' ' . $extra;
            }
        }

        if ($comment !== '') {
            $query .= ' COMMENT ' . $dbi->quoteString($comment);
        }

        // move column
        if ($moveTo === '-first') { // dash can't appear as part of column name
            $query .= ' FIRST';
        } elseif ($moveTo !== '') {
            $query .= ' AFTER ' . Util::backquote($moveTo);
        }

        if ($virtuality === '' && $extra !== '') {
            if ($oldColumnName === null) {
                if (is_array($columnsWithIndex) && ! in_array($name, $columnsWithIndex)) {
                    $query .= ', ADD PRIMARY KEY (' . Util::backquote($name) . ')';
                }
            } elseif (is_array($columnsWithIndex) && ! in_array($oldColumnName, $columnsWithIndex)) {
                $query .= ', ADD PRIMARY KEY (' . Util::backquote($name) . ')';
            }
        }

        return $query;
    }

    /**
     * Checks if the number of records in a table is at least equal to
     * $min_records
     *
     * @param int $minRecords Number of records to check for in a table
     */
    public function checkIfMinRecordsExist(int $minRecords = 0): bool
    {
        $checkQuery = 'SELECT ';

        $uniqueFields = $this->getUniqueColumns(true, false);
        if ($uniqueFields !== []) {
            $fieldsToSelect = implode(', ', $uniqueFields);
        } else {
            $indexedCols = $this->getIndexedColumns(true, false);
            if ($indexedCols !== []) {
                $fieldsToSelect = implode(', ', $indexedCols);
            } else {
                $fieldsToSelect = '*';
            }
        }

        $checkQuery .= $fieldsToSelect
            . ' FROM ' . $this->getFullName(true)
            . ' LIMIT ' . $minRecords;

        $res = $this->dbi->tryQuery($checkQuery);

        if ($res !== false) {
            $numRecords = $res->numRows();
            if ($numRecords >= $minRecords) {
                return true;
            }
        }

        return false;
    }

    /**
     * Counts the number of records in a table
     *
     * @param bool $forceExact whether to force an exact count
     */
    public function countRecords(bool $forceExact = false): int
    {
        $isView = $this->isView();
        $cache = $this->dbi->getCache();

        $exactRowsCached = $cache->getCachedTableContent($this->dbName, $this->name, 'ExactRows');
        if ($exactRowsCached !== null) {
            return (int) $exactRowsCached;
        }

        $rowCount = null;

        if (! $forceExact) {
            if ($cache->getCachedTableContent($this->dbName, $this->name, 'Rows') === null && ! $isView) {
                $this->dbi->getTablesFull($this->dbName, $this->name);
            }

            $rowCount = $cache->getCachedTableContent($this->dbName, $this->name, 'Rows');
        }

        // for a VIEW, $row_count is always false at this point
        $config = Config::getInstance();
        if ($rowCount !== null && $rowCount >= $config->settings['MaxExactCount']) {
            return (int) $rowCount;
        }

        if (! $isView) {
            $rowCount = $this->dbi->fetchValue(
                'SELECT COUNT(*) FROM ' . Util::backquote($this->dbName) . '.' . Util::backquote($this->name),
            );
        } elseif ($config->settings['MaxExactCountViews'] === 0) {
            // For complex views, even trying to get a partial record
            // count could bring down a server, so we offer an
            // alternative: setting MaxExactCountViews to 0 will bypass
            // completely the record counting for views
            return -1;
        } else {
            // Counting all rows of a VIEW could be too long,
            // so use a LIMIT clause.
            // Use try_query because it can fail (when a VIEW is
            // based on a table that no longer exists)
            $result = $this->dbi->tryQuery(
                'SELECT 1 FROM ' . Util::backquote($this->dbName) . '.'
                . Util::backquote($this->name) . ' LIMIT '
                . $config->settings['MaxExactCountViews'],
            );
            if ($result) {
                $rowCount = $result->numRows();
                if ((int) $rowCount === $config->settings['MaxExactCountViews']) {
                    // If we reached the limit, we can't be sure how many rows there are
                    return -1;
                }
            }
        }

        if (is_numeric($rowCount)) {
            $cache->cacheTableValue($this->dbName, $this->name, 'ExactRows', (int) $rowCount);

            return (int) $rowCount;
        }

        return -1;
    }

    /**
     * Generates column specification for ALTER syntax
     *
     * @see Table::generateFieldSpec()
     *
     * @param string              $oldcol           old column name
     * @param string              $newcol           new column name
     * @param string              $type             type ('INT', 'VARCHAR', 'BIT', ...)
     * @param string              $length           length ('2', '5,2', '', ...)
     * @param string              $attribute        attribute
     * @param string              $collation        collation
     * @param bool|string         $null             with 'NULL' or 'NOT NULL'
     * @param string              $defaultType      whether default is CURRENT_TIMESTAMP,
     *                                               NULL, NONE, USER_DEFINED
     * @param string              $defaultValue     default value for USER_DEFINED default
     *                                               type
     * @param string              $extra            'AUTO_INCREMENT'
     * @param string              $comment          field comment
     * @param string              $virtuality       virtuality of the column
     * @param string              $expression       expression for the virtual column
     * @param string              $moveTo           new position for column
     * @param (string|int)[]|null $columnsWithIndex Fields having PRIMARY or UNIQUE KEY indexes
     *
     * @return string  field specification
     */
    public static function generateAlter(
        string $oldcol,
        string $newcol,
        string $type,
        string $length,
        string $attribute,
        string $collation,
        bool|string $null,
        string $defaultType,
        string $defaultValue,
        string $extra,
        string $comment,
        string $virtuality,
        string $expression,
        string $moveTo,
        array|null $columnsWithIndex = null,
    ): string {
        return Util::backquote($oldcol) . ' '
        . self::generateFieldSpec(
            $newcol,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            $columnsWithIndex,
            $oldcol,
        );
    }

    /**
     * checks if given name is a valid table name,
     * currently if not empty, trailing spaces, '.', '/' and '\'
     *
     * @see  https://dev.mysql.com/doc/refman/5.0/en/legal-names.html
     *
     * @param string $tableName    name to check
     * @param bool   $isBackquoted whether this name is used inside backquotes or not
     *
     * @todo add check for valid chars in filename on current system/os
     */
    public static function isValidName(string $tableName, bool $isBackquoted = false): bool
    {
        if ($tableName !== rtrim($tableName)) {
            // trailing spaces not allowed even in backquotes
            return false;
        }

        if ($tableName === '') {
            // zero length
            return false;
        }

        if (! $isBackquoted && $tableName !== trim($tableName)) {
            // spaces at the start or in between only allowed inside backquotes
            return false;
        }

        if (! $isBackquoted && preg_match('/^[a-zA-Z0-9_$]+$/', $tableName) === 1) {
            // only allow the above regex in unquoted identifiers
            // see : https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
            return true;
        }

        // If backquoted, all characters should be allowed (except w/ trailing spaces).
        return $isBackquoted;
    }

    /**
     * renames table
     *
     * @param string      $newName new table name
     * @param string|null $newDb   new database name
     */
    public function rename(string $newName, string|null $newDb = null): bool
    {
        if ($this->dbi->getLowerCaseNames() === 1) {
            $newName = strtolower($newName);
        }

        if ($newDb !== null && $newDb !== $this->getDbName()) {
            // Ensure the target is valid
            if (! $this->dbi->getDatabaseList()->exists($newDb)) {
                $this->errors[] = __('Invalid database:') . ' ' . $newDb;

                return false;
            }
        } else {
            $newDb = $this->getDbName();
        }

        $newTable = new Table($newName, $newDb, $this->dbi);

        if ($this->getFullName() === $newTable->getFullName()) {
            return true;
        }

        // Allow whitespaces (not trailing) in $new_name,
        // since we are using $backquoted in getting the fullName of table
        // below to be used in the query
        if (! self::isValidName($newName, true)) {
            $this->errors[] = __('Invalid table name:') . ' '
                . $newTable->getFullName();

            return false;
        }

        // If the table is moved to a different database drop its triggers first
        $triggers = Triggers::getDetails($this->dbi, $this->getDbName(), $this->getName());
        $handleTriggers = $this->getDbName() !== $newDb && $triggers !== [];
        if ($handleTriggers) {
            foreach ($triggers as $trigger) {
                $sql = 'DROP TRIGGER IF EXISTS '
                    . Util::backquote($this->getDbName())
                    . '.' . Util::backquote($trigger->name->getName()) . ';';
                $this->dbi->query($sql);
            }
        }

        // tested also for a view, in MySQL 5.0.92, 5.1.55 and 5.5.13
        Current::$sqlQuery = 'RENAME TABLE ' . $this->getFullName(true) . ' TO '
            . $newTable->getFullName(true) . ';';
        // I don't think a specific error message for views is necessary
        if ($this->dbi->tryQuery(Current::$sqlQuery) === false) {
            $this->errors[] = $this->dbi->getError();

            // Restore triggers in the old database
            if ($handleTriggers) {
                $this->dbi->selectDb($this->getDbName());
                foreach ($triggers as $trigger) {
                    $this->dbi->query($trigger->getCreateSql(''));
                }
            }

            return false;
        }

        $oldName = $this->getName();
        $oldDb = $this->getDbName();
        $this->name = $newName;
        $this->dbName = $newDb;

        // Rename table in configuration storage
        $this->relation->renameTable($oldDb, $newDb, $oldName, $newName);

        $this->messages[] = sprintf(
            __('Table %1$s has been renamed to %2$s.'),
            $oldName,
            $newName,
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
     * @return string[]
     */
    public function getUniqueColumns(bool $backquoted = true, bool $fullName = true): array
    {
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
            'Non_unique = 0',
        );
        $uniques = $this->dbi->fetchResultMultidimensional(
            $sql,
            ['Key_name', null],
            'Column_name',
        );

        $return = [];
        foreach ($uniques as $index) {
            if (count($index) > 1) {
                continue;
            }

            $possibleColumn = $fullName ? $this->getFullName($backquoted) . '.' : '';

            if ($backquoted) {
                $possibleColumn .= Util::backquote($index[0]);
            } else {
                $possibleColumn .= $index[0];
            }

            // a column might have a primary and an unique index on it
            if (in_array($possibleColumn, $return, true)) {
                continue;
            }

            $return[] = $possibleColumn;
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
     * @param string[] $columnNames
     * @param bool     $backquoted  whether to quote name with backticks ``
     * @param bool     $fullName    whether to include full name of the table as a prefix
     *
     * @return string[]
     */
    private function formatColumns(array $columnNames, bool $backquoted, bool $fullName): array
    {
        $return = [];
        foreach ($columnNames as $column) {
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
     * @return string[]
     */
    public function getIndexedColumns(bool $backquoted = true, bool $fullName = true): array
    {
        $sql = QueryGenerator::getTableIndexesSql(
            $this->getDbName(),
            $this->getName(),
        );
        /** @var string[] $indexed */
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
     * @return string[]
     */
    public function getColumns(bool $backquoted = true, bool $fullName = true): array
    {
        $columnNames = $this->dbi->getColumnNames($this->dbName, $this->name);

        return $this->formatColumns($columnNames, $backquoted, $fullName);
    }

    /**
     * Get meta info for fields in table
     *
     * @return FieldMetadata[]
     */
    public function getColumnsMeta(): array
    {
        $moveColumnsSqlQuery = sprintf(
            'SELECT * FROM %s.%s LIMIT 1',
            Util::backquote($this->dbName),
            Util::backquote($this->name),
        );
        $moveColumnsSqlResult = $this->dbi->tryQuery($moveColumnsSqlQuery);
        if ($moveColumnsSqlResult !== false) {
            return $this->dbi->getFieldsMeta($moveColumnsSqlResult);
        }

        // unsure how to reproduce but it was seen on the reporting server
        return [];
    }

    /**
     * Get non-generated columns in table
     *
     * @param bool $backquoted whether to quote name with backticks ``
     *
     * @return string[]
     */
    public function getNonGeneratedColumns(bool $backquoted = true): array
    {
        $columnsMetaQuery = 'SHOW COLUMNS FROM ' . $this->getFullName(true);
        $ret = [];

        $columnsMetaQueryResult = $this->dbi->fetchResultSimple($columnsMetaQuery);

        foreach ($columnsMetaQueryResult as $column) {
            /** @var string $value */
            $value = $column['Field'];
            if ($backquoted) {
                $value = Util::backquote($value);
            }

            // If contains GENERATED or VIRTUAL and does not contain DEFAULT_GENERATED
            if (
                (
                str_contains($column['Extra'], 'GENERATED')
                || str_contains($column['Extra'], 'VIRTUAL')
                ) && ! str_contains($column['Extra'], 'DEFAULT_GENERATED')
            ) {
                continue;
            }

            $ret[] = $value;
        }

        return $ret;
    }

    /**
     * Return UI preferences for this table from phpMyAdmin database.
     *
     * @return mixed[]
     */
    protected function getUiPrefsFromDb(UiPreferencesFeature|null $uiPreferencesFeature): array
    {
        if ($uiPreferencesFeature === null) {
            return [];
        }

        // Read from phpMyAdmin database
        $sqlQuery = sprintf(
            'SELECT `prefs` FROM %s.%s WHERE `username` = %s AND `db_name` = %s AND `table_name` = %s',
            Util::backquote($uiPreferencesFeature->database),
            Util::backquote($uiPreferencesFeature->tableUiPrefs),
            $this->dbi->quoteString(Config::getInstance()->selectedServer['user'], ConnectionType::ControlUser),
            $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser),
            $this->dbi->quoteString($this->name, ConnectionType::ControlUser),
        );

        $value = $this->dbi->queryAsControlUser($sqlQuery)->fetchValue();
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return [];
    }

    /**
     * Save this table's UI preferences into phpMyAdmin database.
     */
    protected function saveUiPrefsToDb(UiPreferencesFeature $uiPreferencesFeature): true|Message
    {
        $table = Util::backquote($uiPreferencesFeature->database) . '.'
            . Util::backquote($uiPreferencesFeature->tableUiPrefs);

        $config = Config::getInstance();
        $username = $config->selectedServer['user'];
        $sqlQuery = ' REPLACE INTO ' . $table
            . ' (username, db_name, table_name, prefs) VALUES ('
            . $this->dbi->quoteString($username, ConnectionType::ControlUser) . ', '
            . $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser) . ', '
            . $this->dbi->quoteString($this->name, ConnectionType::ControlUser) . ', '
            . $this->dbi->quoteString((string) json_encode($this->uiprefs), ConnectionType::ControlUser) . ')';

        $success = $this->dbi->tryQuery($sqlQuery, ConnectionType::ControlUser);

        if (! $success) {
            $message = Message::error(
                __('Could not save table UI preferences!'),
            );
            $message->addMessage(
                Message::rawError($this->dbi->getError(ConnectionType::ControlUser)),
                '<br><br>',
            );

            return $message;
        }

        // Remove some old rows in table_uiprefs if it exceeds the configured
        // maximum rows
        $sqlQuery = 'SELECT COUNT(*) FROM ' . $table;
        $rowsCount = (int) $this->dbi->fetchValue($sqlQuery);
        $maxRows = $config->selectedServer['MaxTableUiprefs'];
        if ($rowsCount > $maxRows) {
            $numRowsToDelete = $rowsCount - $maxRows;
            $sqlQuery = ' DELETE FROM ' . $table . ' ORDER BY last_update ASC LIMIT ' . $numRowsToDelete;
            $success = $this->dbi->tryQuery($sqlQuery, ConnectionType::ControlUser);

            if (! $success) {
                $message = Message::error(sprintf(
                    __('Failed to cleanup table UI preferences (see $cfg[\'Servers\'][$i][\'MaxTableUiprefs\'] %s)'),
                    MySQLDocumentation::showDocumentation('config', 'cfg_Servers_MaxTableUiprefs'),
                ));
                $message->addMessage(
                    Message::rawError($this->dbi->getError(ConnectionType::ControlUser)),
                    '<br><br>',
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
     */
    protected function loadUiPrefs(): void
    {
        $serverId = Current::$server;

        // set session variable if it's still undefined
        if (! isset($_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name])) {
            // check whether we can get from pmadb
            $uiPrefs = $this->getUiPrefsFromDb($this->relation->getRelationParameters()->uiPreferencesFeature);
            $_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name] = $uiPrefs;
        }

        $this->uiprefs =& $_SESSION['tmpval']['table_uiprefs'][$serverId][$this->dbName][$this->name];
    }

    /**
     * Get a property from UI preferences.
     * Return false if the property is not found.
     */
    public function getUiProp(UiProperty $property): mixed
    {
        if ($this->uiprefs === []) {
            $this->loadUiPrefs();
        }

        // do checking based on property
        if ($property === UiProperty::SortedColumn) {
            if (! isset($this->uiprefs[$property->value])) {
                return false;
            }

            if (! isset($_POST['discard_remembered_sort'])) {
                // check if the column name exists in this table
                $tmp = explode(' ', $this->uiprefs[$property->value]);
                $colname = $tmp[0];
                //remove backquoting from colname
                $colname = str_replace('`', '', $colname);
                //get the available column name without backquoting
                $availColumns = $this->getColumns(false);

                foreach ($availColumns as $eachCol) {
                    // check if $each_col ends with $colname
                    if (substr_compare($eachCol, $colname, mb_strlen($eachCol) - mb_strlen($colname)) === 0) {
                        return $this->uiprefs[$property->value];
                    }
                }
            }

            // remove the property, since it no longer exists in database
            $this->removeUiProp($property);

            return false;
        }

        if ($this->isView() || ! isset($this->uiprefs[$property->value])) {
            return false;
        }

        // check if the table has not been modified
        if ($this->getStatusInfo('Create_time') === $this->uiprefs['CREATE_TIME']) {
            return array_map(intval(...), $this->uiprefs[$property->value]);
        }

        // remove the property, since the table has been modified
        $this->removeUiProp($property);

        return false;
    }

    /**
     * Set a property from UI preferences.
     * If pmadb and table_uiprefs is set, it will save the UI preferences to
     * phpMyAdmin database.
     *
     * @param int[]|string $value           Value for the property
     * @param string|null  $tableCreateTime Needed for PROP_COLUMN_ORDER and PROP_COLUMN_VISIB
     */
    public function setUiProp(
        UiProperty $property,
        array|string $value,
        string|null $tableCreateTime = null,
    ): bool|Message {
        if ($this->uiprefs === []) {
            $this->loadUiPrefs();
        }

        // we want to save the create time if the property is PROP_COLUMN_ORDER
        if (
            ! $this->isView()
            && ($property === UiProperty::ColumnOrder || $property === UiProperty::ColumnVisibility)
        ) {
            $currCreateTime = $this->getStatusInfo('CREATE_TIME');
            if ($tableCreateTime === null || $tableCreateTime !== $currCreateTime) {
                // there is no $table_create_time, or
                // supplied $table_create_time is older than current create time,
                // so don't save
                return Message::error(
                    sprintf(
                        __(
                            'Cannot save UI property "%s". The changes made will ' .
                            'not be persistent after you refresh this page. ' .
                            'Please check if the table structure has been changed.',
                        ),
                        $property->value,
                    ),
                );
            }

            $this->uiprefs['CREATE_TIME'] = $currCreateTime;
        }

        // save the value
        $this->uiprefs[$property->value] = $value;

        // check if pmadb is set
        $uiPreferencesFeature = $this->relation->getRelationParameters()->uiPreferencesFeature;
        if ($uiPreferencesFeature !== null) {
            return $this->saveUiPrefsToDb($uiPreferencesFeature);
        }

        return true;
    }

    /**
     * Remove a property from UI preferences.
     */
    public function removeUiProp(UiProperty $property): true|Message
    {
        if ($this->uiprefs === []) {
            $this->loadUiPrefs();
        }

        if (isset($this->uiprefs[$property->value])) {
            unset($this->uiprefs[$property->value]);

            // check if pmadb is set
            $uiPreferencesFeature = $this->relation->getRelationParameters()->uiPreferencesFeature;
            if ($uiPreferencesFeature !== null) {
                return $this->saveUiPrefsToDb($uiPreferencesFeature);
            }
        }

        return true;
    }

    /**
     * Function to get the name and type of the columns of a table
     *
     * @return array<string, string>
     */
    public function getNameAndTypeOfTheColumns(): array
    {
        $columns = [];
        foreach ($this->dbi->getColumns($this->dbName, $this->name) as $row) {
            if (preg_match('@^(set|enum)\((.+)\)$@i', $row->type, $tmp) === 1) {
                $tmp[2] = mb_substr(
                    (string) preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]),
                    1,
                );
                $columns[$row->field] = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
            } else {
                $columns[$row->field] = $row->type;
            }
        }

        return $columns;
    }

    /**
     * Get index with index name
     *
     * @param string $index Index name
     */
    public function getIndex(string $index): Index
    {
        return Index::singleton($this->dbi, $this->dbName, $this->name, $index);
    }

    /**
     * Function to handle update for display field
     *
     * @param string $displayField display field
     */
    public function updateDisplayField(string $displayField, DisplayFeature $displayFeature): void
    {
        if ($displayField === '') {
            $updQuery = 'DELETE FROM '
                . Util::backquote($displayFeature->database)
                . '.' . Util::backquote($displayFeature->tableInfo)
                . ' WHERE db_name  = ' . $this->dbi->quoteString($this->dbName)
                . ' AND table_name = ' . $this->dbi->quoteString($this->name);
        } else {
            $updQuery = 'REPLACE INTO '
                . Util::backquote($displayFeature->database)
                . '.' . Util::backquote($displayFeature->tableInfo)
                . '(db_name, table_name, display_field) VALUES('
                . $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($this->name, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($displayField, ConnectionType::ControlUser) . ')';
        }

        $this->dbi->queryAsControlUser($updQuery);
    }

    /**
     * Function to get update query for updating internal relations
     *
     * @param string[]        $multiEditColumnsName multi edit column names
     * @param string[]        $destinationDb        destination tables
     * @param (string|null)[] $destinationTable     destination tables
     * @param (string|null)[] $destinationColumn    destination columns
     * @param mixed[]|null    $existrel             db, table, column
     */
    public function updateInternalRelations(
        array $multiEditColumnsName,
        array $destinationDb,
        array $destinationTable,
        array $destinationColumn,
        RelationFeature $relationFeature,
        array|null $existrel,
    ): bool {
        $updated = false;
        foreach ($destinationDb as $masterFieldMd5 => $foreignDb) {
            $updQuery = null;
            // Map the fieldname's md5 back to its real name
            $masterField = $multiEditColumnsName[$masterFieldMd5];
            $foreignTable = $destinationTable[$masterFieldMd5];
            $foreignField = $destinationColumn[$masterFieldMd5];
            if (
                $foreignDb !== ''
                && $foreignTable !== '' && $foreignTable !== null
                && $foreignField !== '' && $foreignField !== null
            ) {
                if (! isset($existrel[$masterField])) {
                    $updQuery = 'INSERT INTO '
                        . Util::backquote($relationFeature->database)
                        . '.' . Util::backquote($relationFeature->relation)
                        . '(master_db, master_table, master_field, foreign_db,'
                        . ' foreign_table, foreign_field)'
                        . ' values('
                        . $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser) . ', '
                        . $this->dbi->quoteString($this->name, ConnectionType::ControlUser) . ', '
                        . $this->dbi->quoteString($masterField, ConnectionType::ControlUser) . ', '
                        . $this->dbi->quoteString($foreignDb, ConnectionType::ControlUser) . ', '
                        . $this->dbi->quoteString($foreignTable, ConnectionType::ControlUser) . ','
                        . $this->dbi->quoteString($foreignField, ConnectionType::ControlUser) . ')';
                } elseif (
                    $existrel[$masterField]['foreign_db'] !== $foreignDb
                    || $existrel[$masterField]['foreign_table'] !== $foreignTable
                    || $existrel[$masterField]['foreign_field'] !== $foreignField
                ) {
                    $updQuery = 'UPDATE '
                        . Util::backquote($relationFeature->database)
                        . '.' . Util::backquote($relationFeature->relation)
                        . ' SET foreign_db       = '
                        . $this->dbi->quoteString($foreignDb, ConnectionType::ControlUser) . ', '
                        . ' foreign_table    = '
                        . $this->dbi->quoteString($foreignTable, ConnectionType::ControlUser) . ', '
                        . ' foreign_field    = '
                        . $this->dbi->quoteString($foreignField, ConnectionType::ControlUser) . ' '
                        . ' WHERE master_db  = '
                        . $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser)
                        . ' AND master_table = '
                        . $this->dbi->quoteString($this->name, ConnectionType::ControlUser)
                        . ' AND master_field = '
                        . $this->dbi->quoteString($masterField, ConnectionType::ControlUser);
                }
            } elseif (isset($existrel[$masterField])) {
                $updQuery = 'DELETE FROM '
                    . Util::backquote($relationFeature->database)
                    . '.' . Util::backquote($relationFeature->relation)
                    . ' WHERE master_db  = '
                    . $this->dbi->quoteString($this->dbName, ConnectionType::ControlUser)
                    . ' AND master_table = '
                    . $this->dbi->quoteString($this->name, ConnectionType::ControlUser)
                    . ' AND master_field = '
                    . $this->dbi->quoteString($masterField, ConnectionType::ControlUser);
            }

            if ($updQuery === null) {
                continue;
            }

            $this->dbi->queryAsControlUser($updQuery);
            $updated = true;
        }

        return $updated;
    }

    /**
     * Function to handle foreign key updates
     *
     * @param string[]     $destinationForeignDb     destination foreign database
     * @param string[][]   $multiEditColumnsName     multi edit column names
     * @param string[]     $destinationForeignTable  destination foreign table
     * @param string[][]   $destinationForeignColumn destination foreign column
     * @param string[]     $optionsArray             options array
     * @param string       $table                    current table
     * @param ForeignKey[] $existrelForeign          db, table, column
     *
     * @return array{string, string, string, bool}
     */
    public function updateForeignKeys(
        array $destinationForeignDb,
        array $multiEditColumnsName,
        array $destinationForeignTable,
        array $destinationForeignColumn,
        array $optionsArray,
        string $table,
        array $existrelForeign,
    ): array {
        $htmlOutput = '';
        $previewSqlData = '';
        $displayQuery = '';
        $seenError = false;

        foreach ($destinationForeignDb as $masterFieldMd5 => $foreignDb) {
            $create = false;
            $drop = false;

            // Map the fieldname's md5 back to its real name
            $masterField = $multiEditColumnsName[$masterFieldMd5];

            $foreignTable = $destinationForeignTable[$masterFieldMd5];
            $foreignField = $destinationForeignColumn[$masterFieldMd5];

            $refDbName = $existrelForeign[$masterFieldMd5]->refDbName ?? Current::$database;

            $emptyFields = false;
            foreach ($masterField as $key => $oneField) {
                if (
                    ($oneField !== '' && (! isset($foreignField[$key]) || $foreignField[$key] === ''))
                    || ($oneField === '' && (isset($foreignField[$key]) && $foreignField[$key] !== ''))
                ) {
                    $emptyFields = true;
                }

                if ($oneField !== '' || (isset($foreignField[$key]) && $foreignField[$key] !== '')) {
                    continue;
                }

                unset($masterField[$key], $foreignField[$key]);
            }

            if ($foreignDb !== '' && $foreignTable !== '' && ! $emptyFields) {
                if (isset($existrelForeign[$masterFieldMd5])) {
                    $constraintName = $existrelForeign[$masterFieldMd5]->constraint;
                    $onDelete = ! empty($existrelForeign[$masterFieldMd5]->onDelete)
                        ? $existrelForeign[$masterFieldMd5]->onDelete
                        : 'RESTRICT';
                    $onUpdate = ! empty($existrelForeign[$masterFieldMd5]->onUpdate)
                        ? $existrelForeign[$masterFieldMd5]->onUpdate
                        : 'RESTRICT';

                    if (
                        $refDbName != $foreignDb
                        || $existrelForeign[$masterFieldMd5]->refTableName != $foreignTable
                        || $existrelForeign[$masterFieldMd5]->refIndexList !== $foreignField
                        || $existrelForeign[$masterFieldMd5]->indexList !== $masterField
                        || $_POST['constraint_name'][$masterFieldMd5] != $constraintName
                        || ($_POST['on_delete'][$masterFieldMd5] !== $onDelete)
                        || ($_POST['on_update'][$masterFieldMd5] !== $onUpdate)
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
            } elseif (isset($existrelForeign[$masterFieldMd5])) {
                $drop = true;
            }

            if ($drop) {
                $dropQuery = 'ALTER TABLE ' . Util::backquote($table)
                    . ' DROP FOREIGN KEY '
                    . Util::backquote($existrelForeign[$masterFieldMd5]->constraint)
                    . ';';

                if (! isset($_POST['preview_sql'])) {
                    $displayQuery .= $dropQuery . "\n";
                    $this->dbi->tryQuery($dropQuery);
                    $tmpErrorDrop = $this->dbi->getError();

                    if ($tmpErrorDrop !== '') {
                        $seenError = true;
                        $htmlOutput .= Generator::mysqlDie($tmpErrorDrop, $dropQuery, false, '', false);
                        continue;
                    }
                } else {
                    $previewSqlData .= $dropQuery . "\n";
                }
            }

            $tmpErrorCreate = '';
            if (! $create) {
                continue;
            }

            $createQuery = $this->getSQLToCreateForeignKey(
                $table,
                $masterField,
                $foreignDb,
                $foreignTable,
                $foreignField,
                $_POST['constraint_name'][$masterFieldMd5],
                $optionsArray[$_POST['on_delete'][$masterFieldMd5]],
                $optionsArray[$_POST['on_update'][$masterFieldMd5]],
            );

            if (! isset($_POST['preview_sql'])) {
                $displayQuery .= $createQuery . "\n";
                $this->dbi->tryQuery($createQuery);
                $tmpErrorCreate = $this->dbi->getError();
                if ($tmpErrorCreate !== '') {
                    $seenError = true;

                    if (substr($tmpErrorCreate, 1, 4) === '1005') {
                        $message = Message::error(
                            __(
                                'Error creating foreign key on %1$s (check data types)',
                            ),
                        );
                        $message->addParam(implode(', ', $masterField));
                        $htmlOutput .= $message->getDisplay();
                    } else {
                        $htmlOutput .= Generator::mysqlDie($tmpErrorCreate, $createQuery, false, '', false);
                    }

                    $htmlOutput .= MySQLDocumentation::show('create-table-foreign-keys') . "\n";
                }
            } else {
                $previewSqlData .= $createQuery . "\n";
            }

            // this is an alteration and the old constraint has been dropped
            // without creation of a new one
            if (! $drop || $tmpErrorCreate === '') {
                continue;
            }

            // a rollback may be better here
            $sqlQueryRecreate = '# Restoring the dropped constraint...' . "\n";
            $sqlQueryRecreate .= $this->getSQLToCreateForeignKey(
                $table,
                $existrelForeign[$masterFieldMd5]->indexList,
                $existrelForeign[$masterFieldMd5]->refDbName ?? Current::$database,
                $existrelForeign[$masterFieldMd5]->refTableName,
                $existrelForeign[$masterFieldMd5]->refIndexList,
                $existrelForeign[$masterFieldMd5]->constraint,
                $optionsArray[$existrelForeign[$masterFieldMd5]->onDelete ?? ''] ?? null,
                $optionsArray[$existrelForeign[$masterFieldMd5]->onUpdate ?? ''] ?? null,
            );
            if (! isset($_POST['preview_sql'])) {
                $displayQuery .= $sqlQueryRecreate . "\n";
                $this->dbi->tryQuery($sqlQueryRecreate);
            } else {
                $previewSqlData .= $sqlQueryRecreate;
            }
        }

        return [$htmlOutput, $previewSqlData, $displayQuery, $seenError];
    }

    /**
     * Returns the SQL query for foreign key constraint creation
     *
     * @param string      $table        table name
     * @param string[]    $field        field names
     * @param string      $foreignDb    foreign database name
     * @param string      $foreignTable foreign table name
     * @param string[]    $foreignField foreign field names
     * @param string|null $name         name of the constraint
     * @param string|null $onDelete     on delete action
     * @param string|null $onUpdate     on update action
     *
     * @return string SQL query for foreign key constraint creation
     */
    private function getSQLToCreateForeignKey(
        string $table,
        array $field,
        string $foreignDb,
        string $foreignTable,
        array $foreignField,
        string|null $name = null,
        string|null $onDelete = null,
        string|null $onUpdate = null,
    ): string {
        $sqlQuery = 'ALTER TABLE ' . Util::backquote($table) . ' ADD ';
        // if user entered a constraint name
        if ($name !== null && $name !== '') {
            $sqlQuery .= ' CONSTRAINT ' . Util::backquote($name);
        }

        foreach ($field as $key => $oneField) {
            $field[$key] = Util::backquote($oneField);
        }

        foreach ($foreignField as $key => $oneField) {
            $foreignField[$key] = Util::backquote($oneField);
        }

        $sqlQuery .= ' FOREIGN KEY (' . implode(', ', $field) . ') REFERENCES '
            . ($this->dbName !== $foreignDb
                ? Util::backquote($foreignDb) . '.' : '')
            . Util::backquote($foreignTable)
            . '(' . implode(', ', $foreignField) . ')';

        if ($onDelete !== null && $onDelete !== '') {
            $sqlQuery .= ' ON DELETE ' . $onDelete;
        }

        if ($onUpdate !== null && $onUpdate !== '') {
            $sqlQuery .= ' ON UPDATE ' . $onUpdate;
        }

        $sqlQuery .= ';';

        return $sqlQuery;
    }

    /**
     * Returns the generation expression for virtual columns
     *
     * @param string|null $column name of the column
     *
     * @return mixed[]|bool associative array of column name and their expressions
     * or false on failure
     */
    public function getColumnGenerationExpression(string|null $column = null): array|bool
    {
        if (
            Compatibility::isMySqlOrPerconaDb()
            && $this->dbi->getVersion() > 50705
            && ! Config::getInstance()->selectedServer['DisableIS']
        ) {
            $sql = 'SELECT
                `COLUMN_NAME` AS `Field`,
                `GENERATION_EXPRESSION` AS `Expression`
                FROM
                `information_schema`.`COLUMNS`
                WHERE
                `TABLE_SCHEMA` = ' . $this->dbi->quoteString($this->dbName) . '
                AND `TABLE_NAME` = ' . $this->dbi->quoteString($this->name);
            if ($column !== null) {
                $sql .= ' AND  `COLUMN_NAME` = ' . $this->dbi->quoteString($column);
            }

            return $this->dbi->fetchResult($sql, 'Field', 'Expression');
        }

        $createTable = $this->showCreate();
        if ($createTable === '') {
            return false;
        }

        $parser = new Parser($createTable);
        $stmt = $parser->statements[0];
        $fields = [];
        if ($stmt instanceof CreateStatement) {
            $fields = TableUtils::getFields($stmt);
        }

        if ($column !== null && $column !== '') {
            $expression = isset($fields[$column]['expr']) ? substr($fields[$column]['expr'], 1, -1) : '';

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
     */
    public function showCreate(): string
    {
        return (string) $this->dbi->fetchValue(
            'SHOW CREATE TABLE ' . Util::backquote($this->dbName) . '.'
            . Util::backquote($this->name),
            1,
        );
    }

    /**
     * Returns the real row count for a table
     */
    public function getRealRowCountTable(): int
    {
        return (int) $this->dbi->fetchValue(
            'SELECT COUNT(*) FROM ' . Util::backquote($this->dbName) . '.'
            . Util::backquote($this->name),
        );
    }

    /**
     * Get columns with indexes
     *
     * @param int $types types bitmask
     *
     * @return (string|int)[] an array of columns
     */
    public function getColumnsWithIndex(int $types): array
    {
        $columnsWithIndex = [];
        foreach (
            Index::getFromTableByChoice($this->name, $this->dbName, $types) as $index
        ) {
            $columns = $index->getColumns();
            $columnsWithIndex = array_merge($columnsWithIndex, array_keys($columns));
        }

        return $columnsWithIndex;
    }
}
