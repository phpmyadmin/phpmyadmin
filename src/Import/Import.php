<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\File;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\ReplaceStatement;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function abs;
use function array_key_last;
use function array_map;
use function count;
use function explode;
use function function_exists;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_numeric;
use function max;
use function mb_chr;
use function mb_ord;
use function mb_stripos;
use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;
use function mb_substr_count;
use function preg_grep;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;
use function time;
use function trim;

/**
 * Library that provides common import functions that are used by import plugins
 */
class Import
{
    private string|null $importRunBuffer = null;
    public static bool $hasError = false;
    public static string $importText = '';
    public static ResultInterface|false $result = false;
    public static string $errorUrl = '';

    public function __construct()
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
    }

    /**
     * Checks whether timeout is getting close
     */
    public function checkTimeout(): bool
    {
        if (ImportSettings::$maximumTime === 0) {
            return false;
        }

        if (ImportSettings::$timeoutPassed) {
            return true;

            /* 5 in next row might be too much */
        }

        if (time() - ImportSettings::$timestamp > ImportSettings::$maximumTime - 5) {
            ImportSettings::$timeoutPassed = true;

            return true;
        }

        return false;
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string   $sql     query to run
     * @param string[] $sqlData SQL parse data storage
     */
    public function executeQuery(string $sql, array &$sqlData): void
    {
        $dbi = DatabaseInterface::getInstance();
        self::$result = $dbi->tryQuery($sql);

        // USE query changes the database, son need to track
        // while running multiple queries
        $isUseQuery = mb_stripos($sql, 'use ') !== false;

        ImportSettings::$message = '# ';
        if (self::$result === false) {
            ImportSettings::$failedQueries[] = ['sql' => $sql, 'error' => $dbi->getError()];

            ImportSettings::$message .= __('Error');

            if (! Config::getInstance()->settings['IgnoreMultiSubmitErrors']) {
                self::$hasError = true;

                return;
            }
        } else {
            $aNumRows = (int) self::$result->numRows();
            $aAffectedRows = (int) @$dbi->affectedRows();
            if ($aNumRows > 0) {
                ImportSettings::$message .= __('Rows') . ': ' . $aNumRows;
            } elseif ($aAffectedRows > 0) {
                $message = Message::getMessageForAffectedRows($aAffectedRows);
                ImportSettings::$message .= $message->getMessage();
            } else {
                ImportSettings::$message .= __('MySQL returned an empty result set (i.e. zero rows).');
            }

            if ($aNumRows > 0 || $isUseQuery) {
                $sqlData[] = $sql;
            }
        }

        if (! ImportSettings::$sqlQueryDisabled) {
            Current::$sqlQuery .= ImportSettings::$message . "\n";
        }

        // If a 'USE <db>' SQL-clause was found and the query
        // succeeded, set our current $db to the new one
        if (self::$result !== false) {
            $dbNameInsideUse = $this->lookForUse($sql);
            if ($dbNameInsideUse !== '') {
                Current::$database = $dbNameInsideUse;
                ResponseRenderer::$reload = true;
            }
        }

        $pattern = '@^[\s]*(DROP|CREATE)[\s]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)@im';
        if (self::$result === false || preg_match($pattern, $sql) !== 1) {
            return;
        }

        ResponseRenderer::$reload = true;
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string   $sql     query to run
     * @param string[] $sqlData SQL parse data storage
     */
    public function runQuery(string $sql, array &$sqlData): void
    {
        ImportSettings::$readMultiply = 1;
        if ($this->importRunBuffer === null) {
            // Do we have something to push into buffer?
            $this->importRunBuffer = $sql !== '' ? $sql . ';' : null;

            return;
        }

        // Should we skip something?
        if (ImportSettings::$skipQueries > 0) {
            ImportSettings::$skipQueries--;
            // Do we have something to push into buffer?
            $this->importRunBuffer = $sql !== '' ? $sql . ';' : null;

            return;
        }

        ImportSettings::$maxSqlLength = max(
            ImportSettings::$maxSqlLength,
            mb_strlen($this->importRunBuffer),
        );
        if (! ImportSettings::$sqlQueryDisabled) {
            Current::$sqlQuery .= $this->importRunBuffer;
        }

        ImportSettings::$executedQueries++;

        if (ImportSettings::$runQuery && ImportSettings::$executedQueries < 50) {
            ImportSettings::$goSql = true;

            if (! ImportSettings::$sqlQueryDisabled) {
                Current::$completeQuery = Current::$sqlQuery;
                Current::$displayQuery = Current::$sqlQuery;
            } else {
                Current::$completeQuery = '';
                Current::$displayQuery = '';
            }

            Current::$sqlQuery = $this->importRunBuffer;
            $sqlData[] = $this->importRunBuffer;
        } elseif (ImportSettings::$runQuery) {
            /* Handle rollback from go_sql */
            if (ImportSettings::$goSql && $sqlData !== []) {
                $queries = $sqlData;
                $sqlData = [];
                ImportSettings::$goSql = false;

                foreach ($queries as $query) {
                    $this->executeQuery($query, $sqlData);
                    if (self::$hasError) {
                        break;
                    }
                }
            }

            if (! self::$hasError) {
                $this->executeQuery($this->importRunBuffer, $sqlData);
            }
        }

        // check length of query unless we decided to pass it to /sql
        // (if $run_query is false, we are just displaying so show
        // the complete query in the textarea)
        if (! ImportSettings::$goSql && ImportSettings::$runQuery && Current::$sqlQuery !== '') {
            if (
                mb_strlen(Current::$sqlQuery) > 50000
                || ImportSettings::$executedQueries > 50
                || ImportSettings::$maxSqlLength > 1000
            ) {
                Current::$sqlQuery = '';
                ImportSettings::$sqlQueryDisabled = true;
            }
        }

        // Do we have something to push into buffer?
        $this->importRunBuffer = $sql !== '' ? $sql . ';' : null;
    }

    /**
     * Looks for the presence of USE to possibly change current db
     */
    public function lookForUse(string $buffer): string
    {
        if (preg_match('@^[\s]*USE[[:space:]]+([\S]+)@i', $buffer, $match) === 1) {
            $db = trim($match[1]);
            $db = trim($db, ';'); // for example, USE abc;

            // $db must not contain the escape characters generated by backquote()
            // ( used in buildSql() as: backquote($db_name), and then called
            // in runQuery() which in turn calls lookForUse() )
            return Util::unQuote($db);
        }

        return '';
    }

    /**
     * Returns next part of imported file/buffer
     *
     * @param int $size size of buffer to read (this is maximal size function will return)
     *
     * @return string|bool part of file/buffer
     */
    public function getNextChunk(File|null $importHandle = null, int $size = 32768): string|bool
    {
        // Add some progression while reading large amount of data
        if (ImportSettings::$readMultiply <= 8) {
            $size *= ImportSettings::$readMultiply;
        } else {
            $size *= 8;
        }

        ImportSettings::$readMultiply++;

        // We can not read too much
        if ($size > ImportSettings::$readLimit) {
            $size = ImportSettings::$readLimit;
        }

        if ($this->checkTimeout()) {
            return false;
        }

        if (ImportSettings::$finished) {
            return true;
        }

        if (ImportSettings::$importFile === 'none') {
            // Well this is not yet supported and tested,
            // but should return content of textarea
            if (mb_strlen(self::$importText) < $size) {
                ImportSettings::$finished = true;

                return self::$importText;
            }

            $r = mb_substr(self::$importText, 0, $size);
            ImportSettings::$offset += $size;
            self::$importText = mb_substr(self::$importText, $size);

            return $r;
        }

        if ($importHandle === null) {
            return false;
        }

        $result = $importHandle->read($size);
        ImportSettings::$finished = $importHandle->eof();
        ImportSettings::$offset += $size;

        if (ImportSettings::$charsetConversion) {
            return Encoding::convertString(ImportSettings::$charsetOfFile, 'utf-8', $result);
        }

        if (ImportSettings::$offset === $size) {
            return $this->skipByteOrderMarksFromContents($result);
        }

        return $result;
    }

    /**
     * Skip possible byte order marks (I do not think we need more
     * charsets, but feel free to add more, you can use wikipedia for
     * reference: <https://en.wikipedia.org/wiki/Byte_Order_Mark>)
     *
     * @todo BOM could be used for charset autodetection
     */
    public function skipByteOrderMarksFromContents(string $contents): string
    {
        // Do not use mb_ functions they are sensible to mb_internal_encoding()

        // UTF-8
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);
        }

        // UTF-16 BE, LE
        if (str_starts_with($contents, "\xFE\xFF") || str_starts_with($contents, "\xFF\xFE")) {
            return substr($contents, 2);
        }

        return $contents;
    }

    /**
     * Returns the "Excel" column name (i.e. 1 = "A", 26 = "Z", 27 = "AA", etc.)
     *
     * This functions uses recursion to build the Excel column name.
     *
     * The column number (1-26) is converted to the responding
     * ASCII character (A-Z) and returned.
     *
     * If the column number is bigger than 26 (= num of letters in alphabet),
     * an extra character needs to be added. To find this extra character,
     * the number is divided by 26 and this value is passed to another instance
     * of the same function (hence recursion). In that new instance the number is
     * evaluated again, and if it is still bigger than 26, it is divided again
     * and passed to another instance of the same function. This continues until
     * the number is smaller than 26. Then the last called function returns
     * the corresponding ASCII character to the function that called it.
     * Each time a called function ends an extra character is added to the column name.
     * When the first function is reached, the last character is added and the complete
     * column name is returned.
     *
     * @param int $num the column number
     *
     * @return string The column's "Excel" name
     */
    public function getColumnAlphaName(int $num): string
    {
        $capitalA = 65; // ASCII value for capital "A"
        $colName = '';

        /** @infection-ignore-all */
        if ($num > 26) {
            $div = (int) ($num / 26);
            $remain = $num % 26;

            // subtract 1 of divided value in case the modulus is 0,
            // this is necessary because A-Z has no 'zero'
            if ($remain === 0) {
                $div--;
            }

            // recursive function call
            $colName = $this->getColumnAlphaName($div);
            // use modulus as new column number
            $num = $remain;
        }

        if ($num === 0) {
            // use 'Z' if column number is 0,
            // this is necessary because A-Z has no 'zero'
            $colName .= mb_chr($capitalA + 26 - 1);
        } else {
            // convert column number to ASCII character
            $colName .= mb_chr($capitalA + $num - 1);
        }

        return $colName;
    }

    /**
     * Returns the column number based on the Excel name.
     * So "A" = 1, "Z" = 26, "AA" = 27, etc.
     *
     * Basically this is a base26 (A-Z) to base10 (0-9) conversion.
     * It iterates through all characters in the column name and
     * calculates the corresponding value, based on character value
     * (A = 1, ..., Z = 26) and position in the string.
     *
     * @param string $name column name(i.e. "A", or "BC", etc.)
     *
     * @return int The column number
     */
    public function getColumnNumberFromName(string $name): int
    {
        if ($name === '') {
            return 0;
        }

        $name = mb_strtoupper($name);
        $numChars = mb_strlen($name);
        $columnNumber = 0;
        for ($i = 0; $i < $numChars; ++$i) {
            // read string from back to front
            $charPos = $numChars - 1 - $i;

            // convert capital character to ASCII value
            // and subtract 64 to get corresponding decimal value
            // ASCII value of "A" is 65, "B" is 66, etc.
            // Decimal equivalent of "A" is 1, "B" is 2, etc.
            $number = mb_ord($name[$charPos]) - 64;

            // base26 to base10 conversion : multiply each number
            // with corresponding value of the position, in this case
            // $i=0 : 1; $i=1 : 26; $i=2 : 676; ...
            $columnNumber += $number * 26 ** $i;
        }

        return (int) $columnNumber;
    }

    /**
     * Obtains the size of the given cell
     *
     * @param DecimalSize|int $lastCumulativeSize Last cumulative column size
     * @param ColumnType|null $lastCumulativeType Last cumulative column type
     * @param ColumnType      $currentCellType    Type of the current cell
     * @param string          $cell               The current cell
     *
     * @return DecimalSize|int Size of the given cell in the type-appropriate format
     *
     * @todo    Handle the error cases more elegantly
     */
    private function detectSize(
        DecimalSize|int $lastCumulativeSize,
        ColumnType|null $lastCumulativeType,
        ColumnType $currentCellType,
        string $cell,
    ): DecimalSize|int {
        $currSize = mb_strlen($cell);

        /**
         * If the cell is NULL, don't treat it as a varchar
         */
        if ($cell === 'NULL') {
            return $lastCumulativeSize;
        }

        if ($currentCellType === ColumnType::Varchar) {
            if ($lastCumulativeType === ColumnType::Varchar) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === ColumnType::Decimal) {
                if ($currSize >= $lastCumulativeSize->precision) {
                    return $currSize;
                }

                return $lastCumulativeSize->precision;
            }

            if ($lastCumulativeType === ColumnType::BigInt || $lastCumulativeType === ColumnType::Int) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === null || $lastCumulativeType === ColumnType::None) {
                /**
                 * This is the first row to be analyzed
                 */
                return $currSize;
            }
        } elseif ($currentCellType === ColumnType::Decimal) {
            if ($lastCumulativeType === ColumnType::Varchar) {
                /* Convert $last_cumulative_size from varchar to decimal format */
                $size = DecimalSize::fromCell($cell);

                if ($size->precision >= $lastCumulativeSize) {
                    return $size->precision;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === ColumnType::Decimal) {
                $size = DecimalSize::fromCell($cell);

                if ($size->precision > $lastCumulativeSize->precision || $size->scale > $lastCumulativeSize->scale) {
                    /* Take the largest of both types */
                    return DecimalSize::fromPrecisionAndScale(
                        max($size->precision, $lastCumulativeSize->precision),
                        max($size->scale, $lastCumulativeSize->scale),
                    );
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === ColumnType::BigInt || $lastCumulativeType === ColumnType::Int) {
                /* Convert $last_cumulative_size from int to decimal format */
                $size = DecimalSize::fromCell($cell);

                if ($size->precision >= $lastCumulativeSize) {
                    return $size;
                }

                return DecimalSize::fromPrecisionAndScale($lastCumulativeSize, $size->scale);
            }

            if ($lastCumulativeType === null || $lastCumulativeType === ColumnType::None) {
                /**
                 * This is the first row to be analyzed
                 */

                /* First row of the column */
                return DecimalSize::fromCell($cell);
            }
        } elseif ($currentCellType === ColumnType::BigInt || $currentCellType === ColumnType::Int) {
            if ($lastCumulativeType === ColumnType::Varchar) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === ColumnType::Decimal) {
                $oldInt = $lastCumulativeSize->precision - $lastCumulativeSize->scale;
                $newInt = mb_strlen($cell);

                if ($oldInt >= $newInt) {
                    /* Use old decimal size */
                    return $lastCumulativeSize;
                }

                return DecimalSize::fromPrecisionAndScale(
                    $newInt + $lastCumulativeSize->scale,
                    $lastCumulativeSize->scale,
                );
            }

            if ($lastCumulativeType === ColumnType::BigInt || $lastCumulativeType === ColumnType::Int) {
                if ($currSize >= $lastCumulativeSize) {
                    return $currSize;
                }

                return $lastCumulativeSize;
            }

            if ($lastCumulativeType === null || $lastCumulativeType === ColumnType::None) {
                /**
                 * This is the first row to be analyzed
                 */
                return $currSize;
            }
        }

        /**
         * An error has DEFINITELY occurred
         */
        /**
         * TODO: Handle this MUCH more elegantly
         */

        return -1;
    }

    public function detectType(ColumnType|null $lastCumulativeType, string|null $cell): ColumnType
    {
        /**
         * If numeric, determine if decimal, int or bigint
         * Else, we call it varchar for simplicity
         */

        if ($cell === 'NULL') {
            return $lastCumulativeType ?? ColumnType::None;
        }

        if (! is_numeric($cell)) {
            return ColumnType::Varchar;
        }

        if (
            $cell === (string) (float) $cell
            && str_contains($cell, '.')
            && mb_substr_count($cell, '.') === 1
        ) {
            return ColumnType::Decimal;
        }

        if (abs((int) $cell) > 2147483647) {
            return ColumnType::BigInt;
        }

        if ($cell !== (string) (int) $cell) {
            return ColumnType::Varchar;
        }

        return ColumnType::Int;
    }

    /**
     * Determines if the column types are int, decimal, or string
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     *
     * @return AnalysedColumn[]
     */
    public function analyzeTable(ImportTable $table): array
    {
        /* Get number of rows in table */
        /* Get number of columns */
        $numberOfColumns = count($table->columns);

        $columns = [];
        for ($i = 0; $i < $numberOfColumns; ++$i) {
            $columns[] = new AnalysedColumn(ColumnType::None, 0);
        }

        /* Analyze each column */
        for ($i = 0; $i < $numberOfColumns; ++$i) {
            /* Analyze the column in each row */
            foreach ($table->rows as $row) {
                $cellValue = $row[$i];
                /* Determine type of the current cell */
                $currType = $this->detectType($columns[$i]->type, $cellValue === null ? null : (string) $cellValue);
                /* Determine size of the current cell */
                $columns[$i]->size = $this->detectSize(
                    $columns[$i]->size,
                    $columns[$i]->type,
                    $currType,
                    (string) $cellValue,
                );

                /**
                 * If a type for this column has already been declared,
                 * only alter it if it was a number and a varchar was found
                 */
                if ($currType === ColumnType::None) {
                    continue;
                }

                if ($currType === ColumnType::Varchar) {
                    $columns[$i]->type = ColumnType::Varchar;
                } elseif ($currType === ColumnType::Decimal) {
                    if ($columns[$i]->type !== ColumnType::Varchar) {
                        $columns[$i]->type = ColumnType::Decimal;
                    }
                } elseif ($currType === ColumnType::BigInt) {
                    if ($columns[$i]->type !== ColumnType::Varchar && $columns[$i]->type !== ColumnType::Decimal) {
                        $columns[$i]->type = ColumnType::BigInt;
                    }
                } elseif ($currType === ColumnType::Int) {
                    if (
                        $columns[$i]->type !== ColumnType::Varchar
                        && $columns[$i]->type !== ColumnType::Decimal
                        && $columns[$i]->type !== ColumnType::BigInt
                    ) {
                        $columns[$i]->type = ColumnType::Int;
                    }
                }
            }
        }

        /* Check to ensure that all types are valid */
        foreach ($columns as $column) {
            if ($column->type !== ColumnType::None) {
                continue;
            }

            $column->type = ColumnType::Varchar;
            $column->size = 10;
        }

        return $columns;
    }

    /**
     * Builds and executes SQL statements to create the database and tables
     * as necessary, as well as insert all the data.
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     *
     * @param ImportTable[]           $tables
     * @param AnalysedColumn[][]|null $analyses      Analyses of the tables
     * @param string[]|null           $additionalSql Additional SQL to be executed
     * @param string[]                $sqlData       List of SQL to be executed
     * @param string                  $insertMode    The insert mode you can use
     * @phpstan-param 'INSERT'|'REPLACE'|'INSERT IGNORE' $insertMode
     */
    public function buildSql(
        string $dbName,
        array $tables,
        array|null $analyses = null,
        array|null $additionalSql = null,
        array &$sqlData = [],
        string $insertMode = 'INSERT',
    ): void {
        /* Needed to quell the beast that is Message */
        ImportSettings::$importNotice = '';

        /* Run the $additional_sql statements supplied by the caller plug-in */
        if ($additionalSql !== null) {
            /* Clean the SQL first */

            /**
             * Only match tables for now, because CREATE IF NOT EXISTS
             * syntax is lacking or nonexisting for views, triggers,
             * functions, and procedures.
             *
             * See: https://bugs.mysql.com/bug.php?id=15287
             *
             * To the best of my knowledge this is still an issue.
             *
             * $pattern = 'CREATE (TABLE|VIEW|TRIGGER|FUNCTION|PROCEDURE)';
             */
            $pattern = '/CREATE [^`]*(TABLE)/';
            $replacement = 'CREATE \\1 IF NOT EXISTS';

            // Change CREATE statements to CREATE IF NOT EXISTS to support inserting into existing structures.
            foreach ($additionalSql as $i => $singleAdditionalSql) {
                $additionalSql[$i] = preg_replace($pattern, $replacement, $singleAdditionalSql);
                /* Execute the resulting statements */
                $this->runQuery($additionalSql[$i], $sqlData);
            }
        }

        if ($analyses !== null) {
            /* TODO: Do more checking here to make sure they really are matched */
            if (count($tables) !== count($analyses)) {
                ResponseRenderer::getInstance()->callExit();
            }

            /* Create SQL code to create the tables */
            foreach ($tables as $i => $table) {
                $lastColumnKey = array_key_last($table->columns);
                $tempSQLStr = 'CREATE TABLE IF NOT EXISTS '
                    . Util::backquote($dbName)
                    . '.' . Util::backquote($table->tableName) . ' (';
                foreach ($table->columns as $j => $column) {
                    $size = $analyses[$i][$j]->size;
                    if ($size === 0) {
                        $size = 10;
                    }

                    $tempSQLStr .= Util::backquote($column) . ' ' . match ($analyses[$i][$j]->type) {
                        ColumnType::None => 'NULL',
                        ColumnType::Varchar => 'varchar',
                        ColumnType::Int => 'int',
                        ColumnType::Decimal => 'decimal',
                        ColumnType::BigInt => 'bigint',
                        ColumnType::Geometry => 'geometry',
                    };
                    if ($analyses[$i][$j]->type !== ColumnType::Geometry) {
                        $tempSQLStr .= '(' . $size . ')';
                    }

                    if ($j === $lastColumnKey) {
                        continue;
                    }

                    $tempSQLStr .= ', ';
                }

                $tempSQLStr .= ')';

                /**
                 * Each SQL statement is executed immediately
                 * after it is formed so that we don't have
                 * to store them in a (possibly large) buffer
                 */
                $this->runQuery($tempSQLStr, $sqlData);
            }
        }

        /**
         * Create the SQL statements to insert all the data
         *
         * Only one insert query is formed for each table
         */
        $dbi = DatabaseInterface::getInstance();
        foreach ($tables as $tableIndex => $table) {
            $numCols = count($table->columns);
            $lastColumnKey = array_key_last($table->columns);

            if ($table->rows === []) {
                break;
            }

            $tempSQLStr = $insertMode . ' INTO ' . Util::backquote($dbName) . '.'
                . Util::backquote($table->tableName) . ' (';

            $tempSQLStr .= implode(', ', array_map(Util::backquote(...), $table->columns));

            $tempSQLStr .= ') VALUES ';

            $lastRowKey = array_key_last($table->rows);
            foreach ($table->rows as $rowIndex => $row) {
                $tempSQLStr .= '(';

                for ($columnIndex = 0; $columnIndex < $numCols; ++$columnIndex) {
                    // If fully formatted SQL, no need to enclose
                    // with apostrophes, add slashes etc.
                    if (
                        $analyses !== null
                        && $analyses[$tableIndex][$columnIndex]->isFullyFormattedSql
                    ) {
                        $tempSQLStr .= (string) $row[$columnIndex];
                    } else {
                        if ($analyses !== null) {
                            $isVarchar = $analyses[$tableIndex][$columnIndex]->type === ColumnType::Varchar;
                        } else {
                            $isVarchar = ! is_numeric($row[$columnIndex]);
                        }

                        /* Don't put quotes around NULL fields */
                        if ((string) $row[$columnIndex] === 'NULL') {
                            $isVarchar = false;
                        }

                        $tempSQLStr .= $isVarchar
                            ? $dbi->quoteString((string) $row[$columnIndex])
                            : (string) $row[$columnIndex];
                    }

                    if ($columnIndex === $lastColumnKey) {
                        continue;
                    }

                    $tempSQLStr .= ', ';
                }

                $tempSQLStr .= ')';

                if ($rowIndex !== $lastRowKey) {
                    $tempSQLStr .= ",\n ";
                }

                /* Delete the row after we are done with it */
                unset($table->rows[$rowIndex]);
            }

            /**
             * Each SQL statement is executed immediately
             * after it is formed so that we don't have
             * to store them in a (possibly large) buffer
             */
            $this->runQuery($tempSQLStr, $sqlData);
        }

        /**
         * A work in progress
         */

        /**
         * Add the viewable structures from $additional_sql
         * to $tables so they are also displayed
         */
        $viewPattern = '@VIEW `[^`]+`\.`([^`]+)@';
        $tablePattern = '@CREATE TABLE IF NOT EXISTS `([^`]+)`@';
        /* Check a third pattern to make sure its not a "USE `db_name`;" statement */

        /** @var string $sql */
        foreach ($additionalSql ?? [] as $sql) {
            $regs = [];
            preg_match($viewPattern, $sql, $regs);

            if ($regs === []) {
                preg_match($tablePattern, $sql, $regs);
            }

            if ($regs === []) {
                continue;
            }

            foreach ($tables as $table) {
                if ($regs[1] === $table->tableName) {
                    continue 2;
                }
            }

            $tables[] = new ImportTable($regs[1]);
        }

        $message = $this->getSuccessMessage($dbName, $tables, $dbi);

        ImportSettings::$importNotice = $message;
    }

    public function handleRollbackRequest(string $sqlQuery): void
    {
        $sqlDelimiter = $_POST['sql_delimiter'];
        $queries = explode($sqlDelimiter, $sqlQuery);
        $dbi = DatabaseInterface::getInstance();
        foreach ($queries as $sqlQuery) {
            if ($sqlQuery === '') {
                continue;
            }

            // Check each query for ROLLBACK support.
            if ($this->checkIfRollbackPossible($sqlQuery)) {
                continue;
            }

            $sqlError = $dbi->getError();
            $error = $sqlError !== '' ? $sqlError : __(
                'Only INSERT, UPDATE, DELETE, REPLACE and SET (without options like GLOBAL) '
                . 'SQL queries containing transactional engine tables can be rolled back.',
            );

            $response = ResponseRenderer::getInstance();
            $response->addJSON('message', Message::rawError($error));
            $response->callExit();
        }

        // If everything fine, START a transaction.
        $dbi->query('START TRANSACTION');
    }

    /**
     * Checks if ROLLBACK is possible for a SQL query or not.
     *
     * @param string $sqlQuery SQL query
     */
    public function checkIfRollbackPossible(string $sqlQuery): bool
    {
        $parser = new Parser($sqlQuery);

        if (empty($parser->statements[0])) {
            return true;
        }

        $statement = $parser->statements[0];

        // Check if query is supported.
        if (
            ! ($statement instanceof InsertStatement
            || $statement instanceof UpdateStatement
            || $statement instanceof DeleteStatement
            || $statement instanceof ReplaceStatement
            || (
                $statement instanceof SetStatement
                && $statement->options !== null
                && $statement->options->isEmpty()
                )
            )
        ) {
            return false;
        }

        // Get table_references from the query.
        $tables = Query::getTables($statement);

        // Check if each table is 'InnoDB'.
        foreach ($tables as $table) {
            if (! $this->isTableTransactional($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a table is 'InnoDB' or not.
     *
     * @param string $table Table details
     */
    public function isTableTransactional(string $table): bool
    {
        $table = explode('.', $table);
        if (count($table) === 2) {
            $db = Util::unQuote($table[0]);
            $table = Util::unQuote($table[1]);
        } else {
            $db = Current::$database;
            $table = Util::unQuote($table[0]);
        }

        // Query to check if table exists.
        $checkTableQuery = 'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' '
            . 'LIMIT 1';

        $dbi = DatabaseInterface::getInstance();
        $result = $dbi->tryQuery($checkTableQuery);

        if (! $result) {
            return false;
        }

        // List of Transactional Engines.
        $transactionalEngines = [
            'INNODB',
            'FALCON',
            'NDB',
            'INFINIDB',
            'TOKUDB',
            'XTRADB',
            'SEQUENCE',
            'ROCKSDB',
        ];

        // Query to check if table is 'Transactional'.
        $checkQuery = 'SELECT `ENGINE` FROM `information_schema`.`tables` '
            . 'WHERE `table_name` = ' . $dbi->quoteString($table) . ' '
            . 'AND `table_schema` = ' . $dbi->quoteString($db) . ' '
            . 'AND UPPER(`engine`) IN ("'
            . implode('", "', $transactionalEngines)
            . '")';

        $result = $dbi->tryQuery($checkQuery);

        return $result && $result->numRows() === 1;
    }

    /** @return string[] */
    public static function getCompressions(): array
    {
        $compressions = [];

        $config = Config::getInstance();
        if ($config->config->GZipDump && function_exists('gzopen')) {
            $compressions[] = 'gzip';
        }

        if ($config->config->BZipDump && function_exists('bzopen')) {
            $compressions[] = 'bzip2';
        }

        if ($config->config->ZipDump && function_exists('zip_open')) {
            $compressions[] = 'zip';
        }

        return $compressions;
    }

    /** @param ImportPlugin[] $importList List of plugin instances. */
    public static function getLocalFiles(array $importList): false|string
    {
        $fileListing = new FileListing();

        $extensions = '';
        foreach ($importList as $importPlugin) {
            if ($extensions !== '') {
                $extensions .= '|';
            }

            $extensions .= $importPlugin->getProperties()->getExtension();
        }

        $matcher = '@\.(' . $extensions . ')(\.(' . $fileListing->supportedDecompressions() . '))?$@';

        $active = ImportSettings::$localImportFile !== '' && ImportSettings::$timeoutPassed
            ? ImportSettings::$localImportFile
            : '';

        return $fileListing->getFileSelectOptions(
            Util::userDir(Config::getInstance()->config->UploadDir),
            $matcher,
            $active,
        );
    }

    public function getNextAvailableTableName(string $databaseName, string $proposedTableName): string
    {
        if ($proposedTableName === '') {
            $proposedTableName = 'TABLE';
        }

        $importFileName = rtrim($proposedTableName);
        $importFileName = (string) preg_replace('/[^\x{0001}-\x{FFFF}]/u', '_', $importFileName);

        if ($databaseName !== '') {
            $existingTables = DatabaseInterface::getInstance()->getTables($databaseName);

            // check to see if {filename} as table exist
            // if no use filename as table name
            if (! in_array($importFileName, $existingTables, true)) {
                return $importFileName;
            }

            // check if {filename}_ as table exist
            $nameArray = preg_grep('/^' . preg_quote($importFileName, '/') . '_/isU', $existingTables);
            if ($nameArray === false) {
                return $importFileName;
            }

            return $importFileName . '_' . (count($nameArray) + 1);
        }

        return $importFileName;
    }

    /**
     * @param string[] $sqlData List of SQL statements to be executed
     *
     * @return string[]
     */
    public function createDatabase(string $dbName, string $charset, string $collation, array $sqlData): array
    {
        $sql = 'CREATE DATABASE IF NOT EXISTS ' . Util::backquote($dbName)
            . ' DEFAULT CHARACTER SET ' . $charset . ' COLLATE ' . $collation;
        $this->runQuery($sql, $sqlData);

        return $sqlData;
    }

    /** @param ImportTable[] $tables */
    private function getHtmlListForAllTables(array $tables, string $dbName, DatabaseInterface $dbi): string
    {
        $message = '<ul>';

        foreach ($tables as $table) {
            $params = ['db' => $dbName, 'table' => $table->tableName];
            $tblUrl = Url::getFromRoute('/sql', $params);
            $tblStructUrl = Url::getFromRoute('/table/structure', $params);
            $tblOpsUrl = Url::getFromRoute('/table/operations', $params);

            $tableObj = new Table($table->tableName, $dbName, $dbi);
            if (! $tableObj->isView()) {
                $message .= sprintf(
                    '<li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">' . __(
                        'Structure',
                    ) . '</a>) (<a href="%s" title="%s">' . __('Options') . '</a>)</li>',
                    $tblUrl,
                    sprintf(
                        __('Go to table: %s'),
                        htmlspecialchars(
                            Util::backquote($table->tableName),
                        ),
                    ),
                    htmlspecialchars($table->tableName),
                    $tblStructUrl,
                    sprintf(
                        __('Structure of %s'),
                        htmlspecialchars(
                            Util::backquote($table->tableName),
                        ),
                    ),
                    $tblOpsUrl,
                    sprintf(
                        __('Edit settings for %s'),
                        htmlspecialchars(
                            Util::backquote($table->tableName),
                        ),
                    ),
                );
            } else {
                $message .= sprintf(
                    '<li><a href="%s" title="%s">%s</a></li>',
                    $tblUrl,
                    sprintf(
                        __('Go to view: %s'),
                        htmlspecialchars(
                            Util::backquote($table->tableName),
                        ),
                    ),
                    htmlspecialchars($table->tableName),
                );
            }
        }

        return $message . '</ul></ul>';
    }

    /** @param ImportTable[] $tables */
    private function getSuccessMessage(string $dbName, array $tables, DatabaseInterface $dbi): string
    {
        $dbUrl = Url::getFromRoute('/database/structure', ['db' => $dbName]);
        $dbOperationsUrl = Url::getFromRoute('/database/operations', ['db' => $dbName]);

        $message = '<br><br>';
        $message .= '<strong>' . __(
            'The following structures have either been created or altered. Here you can:',
        ) . '</strong><br>';
        $message .= '<ul><li>' . __("View a structure's contents by clicking on its name.") . '</li>';
        $message .= '<li>' . __('Change any of its settings by clicking the corresponding "Options" link.') . '</li>';
        $message .= '<li>' . __('Edit structure by following the "Structure" link.')
            . '</li>';
        $message .= sprintf(
            '<br><li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">'
            . __('Options') . '</a>)</li>',
            $dbUrl,
            sprintf(
                __('Go to database: %s'),
                htmlspecialchars(Util::backquote($dbName)),
            ),
            htmlspecialchars($dbName),
            $dbOperationsUrl,
            sprintf(
                __('Edit settings for %s'),
                htmlspecialchars(Util::backquote($dbName)),
            ),
        );

        $message .= $this->getHtmlListForAllTables($tables, $dbName, $dbi);

        return $message;
    }
}
