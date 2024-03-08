<?php
/**
 * CSV import plugin for phpMyAdmin
 *
 * @todo       add an option for handling NULL values
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Import\ImportTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\NumberPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function array_pad;
use function array_shift;
use function count;
use function mb_strlen;
use function mb_substr;
use function pathinfo;
use function preg_split;
use function rtrim;
use function str_contains;
use function strlen;
use function strtr;
use function trim;

use const PATHINFO_FILENAME;

/**
 * Handles the import for the CSV format
 */
class ImportCsv extends AbstractImportCsv
{
    /**
     * Whether to analyze tables
     */
    private bool $analyze = false;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'csv';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $this->analyze = false;

        if ($GLOBALS['plugin_param'] !== 'table') {
            $this->analyze = true;
        }

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('CSV');
        $importPluginProperties->setExtension('csv');
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        $generalOptions = $this->getGeneralOptions();

        if ($GLOBALS['plugin_param'] !== 'table') {
            $leaf = new TextPropertyItem(
                'new_tbl_name',
                __(
                    'Name of the new table (optional):',
                ),
            );
            $generalOptions->addProperty($leaf);

            if ($GLOBALS['plugin_param'] === 'server') {
                $leaf = new TextPropertyItem(
                    'new_db_name',
                    __(
                        'Name of the new database (optional):',
                    ),
                );
                $generalOptions->addProperty($leaf);
            }

            $leaf = new NumberPropertyItem(
                'partial_import',
                __(
                    'Import these many number of rows (optional):',
                ),
            );
            $generalOptions->addProperty($leaf);

            $leaf = new BoolPropertyItem(
                'col_names',
                __(
                    'The first line of the file contains the table column names'
                    . ' <i>(if this is unchecked, the first line will become part'
                    . ' of the data)</i>',
                ),
            );
            $generalOptions->addProperty($leaf);
        } else {
            $leaf = new NumberPropertyItem(
                'partial_import',
                __(
                    'Import these many number of rows (optional):',
                ),
            );
            $generalOptions->addProperty($leaf);

            $hint = new Message(
                __(
                    'If the data in each row of the file is not'
                    . ' in the same order as in the database, list the corresponding'
                    . ' column names here. Column names must be separated by commas'
                    . ' and not enclosed in quotations.',
                ),
            );
            $leaf = new TextPropertyItem(
                'columns',
                __('Column names:') . ' ' . Generator::showHint($hint->getMessage()),
            );
            $generalOptions->addProperty($leaf);
        }

        $leaf = new BoolPropertyItem(
            'ignore',
            __('Do not abort on INSERT error'),
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @return string[]
     *
     * @infection-ignore-all
     */
    public function doImport(File|null $importHandle = null): array
    {
        $GLOBALS['error'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['csv_terminated'] ??= null;
        $GLOBALS['csv_enclosed'] ??= null;
        $GLOBALS['csv_escaped'] ??= null;
        $GLOBALS['csv_new_line'] ??= null;
        $GLOBALS['csv_columns'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        // $csv_replace and $csv_ignore should have been here,
        // but we use directly from $_POST

        $replacements = ['\\n' => "\n", '\\t' => "\t", '\\r' => "\r"];
        $GLOBALS['csv_terminated'] = strtr($GLOBALS['csv_terminated'], $replacements);
        $GLOBALS['csv_enclosed'] = strtr($GLOBALS['csv_enclosed'], $replacements);
        $GLOBALS['csv_escaped'] = strtr($GLOBALS['csv_escaped'], $replacements);
        $GLOBALS['csv_new_line'] = strtr($GLOBALS['csv_new_line'], $replacements);

        [$GLOBALS['error'], $GLOBALS['message']] = $this->buildErrorsForParams(
            $GLOBALS['csv_terminated'],
            $GLOBALS['csv_enclosed'],
            $GLOBALS['csv_escaped'],
            $GLOBALS['csv_new_line'],
            (string) $GLOBALS['errorUrl'],
        );

        [$sqlTemplate, $fields] = $this->getSqlTemplateAndRequiredFields(
            Current::$database,
            Current::$table,
            $GLOBALS['csv_columns'],
        );

        $requiredFields = count($fields);

        $sqlStatements = [];

        // Defaults for parser
        $i = 0;
        $len = 0;
        $lastlen = null;
        $line = 1;
        $lasti = -1;
        $values = [];
        $csvFinish = false;
        $maxLines = 0; // defaults to 0 (get all the lines)

        /**
         * If we get a negative value, probably someone changed min value
         * attribute in DOM or there is an integer overflow, whatever be
         * the case, get all the lines.
         */
        if (isset($_REQUEST['csv_partial_import']) && $_REQUEST['csv_partial_import'] > 0) {
            $maxLines = $_REQUEST['csv_partial_import'];
        }

        $maxLinesConstraint = $maxLines + 1;
        // if the first row has to be counted as column names, include one more row in the max lines
        if (isset($_REQUEST['csv_col_names'])) {
            $maxLinesConstraint++;
        }

        $tempRow = [];
        $rows = [];
        $colNames = [];

        $buffer = '';
        $colCount = 0;
        $maxCols = 0;
        $csvTerminatedLen = mb_strlen($GLOBALS['csv_terminated']);
        $dbi = DatabaseInterface::getInstance();
        while (! (ImportSettings::$finished && $i >= $len) && ! $GLOBALS['error'] && ! ImportSettings::$timeoutPassed) {
            $data = $this->import->getNextChunk($importHandle);
            if ($data === false) {
                // subtract data we didn't handle yet and stop processing
                ImportSettings::$offset -= strlen($buffer);
                break;
            }

            if ($data !== true) {
                // Append new data to buffer
                $buffer .= $data;
                unset($data);

                // Force a trailing new line at EOF to prevent parsing problems
                if (ImportSettings::$finished && $buffer) {
                    $finalch = mb_substr($buffer, -1);
                    if ($GLOBALS['csv_new_line'] === 'auto' && $finalch != "\r" && $finalch != "\n") {
                        $buffer .= "\n";
                    } elseif ($GLOBALS['csv_new_line'] !== 'auto' && $finalch != $GLOBALS['csv_new_line']) {
                        $buffer .= $GLOBALS['csv_new_line'];
                    }
                }

                // Do not parse string when we're not at the end
                // and don't have new line inside
                if (
                    ($GLOBALS['csv_new_line'] === 'auto'
                    && ! str_contains($buffer, "\r")
                    && ! str_contains($buffer, "\n"))
                    || ($GLOBALS['csv_new_line'] !== 'auto'
                    && ! str_contains($buffer, $GLOBALS['csv_new_line']))
                ) {
                    continue;
                }
            }

            // Current length of our buffer
            $len = mb_strlen($buffer);
            // Currently parsed char

            $ch = mb_substr($buffer, $i, 1);
            if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                $i += $csvTerminatedLen - 1;
            }

            while ($i < $len) {
                // Deadlock protection
                if ($lasti == $i && $lastlen == $len) {
                    $GLOBALS['message'] = Message::error(
                        __('Invalid format of CSV input on line %d.'),
                    );
                    $GLOBALS['message']->addParam($line);
                    $GLOBALS['error'] = true;
                    break;
                }

                $lasti = $i;
                $lastlen = $len;

                // This can happen with auto EOL and \r at the end of buffer
                if (! $csvFinish) {
                    // Grab empty field
                    if ($ch == $GLOBALS['csv_terminated']) {
                        if ($i === $len - 1) {
                            break;
                        }

                        $values[] = '';
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                            $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                            $i += $csvTerminatedLen - 1;
                        }

                        continue;
                    }

                    // Grab one field
                    $fallbacki = $i;
                    if ($ch == $GLOBALS['csv_enclosed']) {
                        if ($i === $len - 1) {
                            break;
                        }

                        $needEnd = true;
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                            $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                            $i += $csvTerminatedLen - 1;
                        }
                    } else {
                        $needEnd = false;
                    }

                    $fail = false;
                    $value = '';
                    while (
                        ($needEnd
                            && ($ch != $GLOBALS['csv_enclosed']
                                || $GLOBALS['csv_enclosed'] == $GLOBALS['csv_escaped']))
                        || (! $needEnd
                            && ! ($ch == $GLOBALS['csv_terminated']
                                || $ch == $GLOBALS['csv_new_line']
                                || ($GLOBALS['csv_new_line'] === 'auto'
                                    && ($ch == "\r" || $ch == "\n"))))
                    ) {
                        if ($ch == $GLOBALS['csv_escaped']) {
                            if ($i === $len - 1) {
                                $fail = true;
                                break;
                            }

                            $i++;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                                $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                                $i += $csvTerminatedLen - 1;
                            }

                            if (
                                $GLOBALS['csv_enclosed'] == $GLOBALS['csv_escaped']
                                && ($ch == $GLOBALS['csv_terminated']
                                || $ch == $GLOBALS['csv_new_line']
                                || ($GLOBALS['csv_new_line'] === 'auto'
                                && ($ch == "\r" || $ch == "\n")))
                            ) {
                                break;
                            }
                        }

                        $value .= $ch;
                        if ($i === $len - 1) {
                            if (! ImportSettings::$finished) {
                                $fail = true;
                            }

                            break;
                        }

                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csvTerminatedLen <= 1 || $ch != $GLOBALS['csv_terminated'][0]) {
                            continue;
                        }

                        $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                        $i += $csvTerminatedLen - 1;
                    }

                    // unquoted NULL string
                    if ($needEnd === false && $value === 'NULL') {
                        $value = null;
                    }

                    if ($fail) {
                        $i = $fallbacki;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                            $i += $csvTerminatedLen - 1;
                        }

                        break;
                    }

                    // Need to strip trailing enclosing char?
                    if ($needEnd && $ch == $GLOBALS['csv_enclosed']) {
                        if (ImportSettings::$finished && $i === $len - 1) {
                            $ch = null;
                        } elseif ($i === $len - 1) {
                            $i = $fallbacki;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                                $i += $csvTerminatedLen - 1;
                            }

                            break;
                        } else {
                            $i++;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                                $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                                $i += $csvTerminatedLen - 1;
                            }
                        }
                    }

                    // Are we at the end?
                    if (
                        $ch == $GLOBALS['csv_new_line']
                        || ($GLOBALS['csv_new_line'] === 'auto' && ($ch == "\r" || $ch == "\n"))
                        || (ImportSettings::$finished && $i === $len - 1)
                    ) {
                        $csvFinish = true;
                    }

                    // Go to next char
                    if ($ch == $GLOBALS['csv_terminated']) {
                        if ($i === $len - 1) {
                            $i = $fallbacki;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                                $i += $csvTerminatedLen - 1;
                            }

                            break;
                        }

                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csvTerminatedLen > 1 && $ch == $GLOBALS['csv_terminated'][0]) {
                            $ch = $this->readCsvTerminatedString($buffer, $ch, $i, $csvTerminatedLen);
                            $i += $csvTerminatedLen - 1;
                        }
                    }

                    // If everything went okay, store value
                    $values[] = $value;
                }

                // End of line
                if (
                    ! $csvFinish
                    && $ch != $GLOBALS['csv_new_line']
                    && ($GLOBALS['csv_new_line'] !== 'auto' || ($ch != "\r" && $ch != "\n"))
                ) {
                    continue;
                }

                if ($GLOBALS['csv_new_line'] === 'auto' && $ch == "\r") { // Handle "\r\n"
                    if ($i >= ($len - 2) && ! ImportSettings::$finished) {
                        break; // We need more data to decide new line
                    }

                    if (mb_substr($buffer, $i + 1, 1) == "\n") {
                        $i++;
                    }
                }

                // We didn't parse value till the end of line, so there was
                // empty one
                if (! $csvFinish) {
                    $values[] = '';
                }

                if ($this->analyze) {
                    foreach ($values as $val) {
                        $tempRow[] = $val;
                        ++$colCount;
                    }

                    if ($colCount > $maxCols) {
                        $maxCols = $colCount;
                    }

                    $colCount = 0;

                    $rows[] = $tempRow;
                    $tempRow = [];
                } else {
                    // Do we have correct count of values?
                    if (count($values) !== $requiredFields) {
                        // Hack for excel
                        if ($values[count($values) - 1] !== ';') {
                            $GLOBALS['message'] = Message::error(
                                __(
                                    'Invalid column count in CSV input on line %d.',
                                ),
                            );
                            $GLOBALS['message']->addParam($line);
                            $GLOBALS['error'] = true;
                            break;
                        }

                        unset($values[count($values) - 1]);
                    }

                    $first = true;
                    $sql = $sqlTemplate;
                    foreach ($values as $val) {
                        if (! $first) {
                            $sql .= ', ';
                        }

                        if ($val === null) {
                            $sql .= 'NULL';
                        } else {
                            $sql .= $dbi->quoteString($val);
                        }

                        $first = false;
                    }

                    $sql .= ')';
                    if (isset($_POST['csv_replace'])) {
                        $sql .= ' ON DUPLICATE KEY UPDATE ';
                        foreach ($fields as $field) {
                            $fieldName = Util::backquote($field);
                            $sql .= $fieldName . ' = VALUES(' . $fieldName
                                . '), ';
                        }

                        $sql = rtrim($sql, ', ');
                    }

                    /**
                     * @todo maybe we could add original line to verbose
                     * SQL in comment
                     */
                    $this->import->runQuery($sql, $sqlStatements);
                }

                $line++;
                $csvFinish = false;
                $values = [];
                $buffer = mb_substr($buffer, $i + 1);
                $len = mb_strlen($buffer);
                $i = 0;
                $lasti = -1;
                $ch = mb_substr($buffer, 0, 1);
                if ($maxLines > 0 && $line == $maxLinesConstraint) {
                    ImportSettings::$finished = true;
                    break;
                }
            }

            if ($maxLines > 0 && $line == $maxLinesConstraint) {
                ImportSettings::$finished = true;
                break;
            }
        }

        if ($this->analyze) {
            /* Fill out all rows */
            foreach ($rows as $i => $row) {
                $rows[$i] = array_pad($row, $maxCols, 'NULL');
            }

            /* Remove the first row if it contains the column names */
            if (isset($_REQUEST['csv_col_names'])) {
                $colNames = array_shift($rows);
            }

            $colNames = $this->getColumnNames($colNames, $maxCols);

            $tblName = $this->getTableNameFromImport(Current::$database);

            $table = new ImportTable($tblName, $colNames, $rows);

            /* Obtain the best-fit MySQL types for each column */
            $analysis = $this->import->analyzeTable($table);

            /**
             * Set database name to the currently selected one, if applicable,
             * Otherwise, check if user provided the database name in the request,
             * if not, set the default name
             */
            if (isset($_REQUEST['csv_new_db_name']) && (string) $_REQUEST['csv_new_db_name'] !== '') {
                $newDb = $_REQUEST['csv_new_db_name'];
                Assert::string($newDb);
            } else {
                $result = $dbi->fetchResult('SHOW DATABASES');

                $newDb = 'CSV_DB ' . (count($result) + 1);
            }

            $dbName = Current::$database !== '' ? Current::$database : $newDb;
            $createDb = Current::$database === '';

            if ($createDb) {
                $sqlStatements = $this->import->createDatabase($dbName, 'utf8', 'utf8_general_ci', $sqlStatements);
            }

            $this->import->buildSql($dbName, [$table], [$analysis], sqlData: $sqlStatements);
        }

        // Commit any possible data in buffers
        $this->import->runQuery('', $sqlStatements);

        if (count($values) == 0 || $GLOBALS['error'] !== false) {
            return $sqlStatements;
        }

        $GLOBALS['message'] = Message::error(
            __('Invalid format of CSV input on line %d.'),
        );
        $GLOBALS['message']->addParam($line);
        $GLOBALS['error'] = true;

        return $sqlStatements;
    }

    /** @return mixed[] */
    private function buildErrorsForParams(
        string $csvTerminated,
        string $csvEnclosed,
        string $csvEscaped,
        string $csvNewLine,
        string $errUrl,
    ): array {
        $GLOBALS['error'] ??= null;
        $GLOBALS['message'] ??= null;

        $paramError = false;
        if ($csvTerminated === '') {
            $GLOBALS['message'] = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            $GLOBALS['message']->addParam(__('Columns terminated with'));
            $GLOBALS['error'] = true;
            $paramError = true;
            // The default dialog of MS Excel when generating a CSV produces a
            // semi-colon-separated file with no chance of specifying the
            // enclosing character. Thus, users who want to import this file
            // tend to remove the enclosing character on the Import dialog.
            // I could not find a test case where having no enclosing characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (mb_strlen($csvEnclosed) > 1) {
            $GLOBALS['message'] = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            $GLOBALS['message']->addParam(__('Columns enclosed with'));
            $GLOBALS['error'] = true;
            $paramError = true;
            // I could not find a test case where having no escaping characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (mb_strlen($csvEscaped) > 1) {
            $GLOBALS['message'] = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            $GLOBALS['message']->addParam(__('Columns escaped with'));
            $GLOBALS['error'] = true;
            $paramError = true;
        } elseif (mb_strlen($csvNewLine) != 1 && $csvNewLine !== 'auto') {
            $GLOBALS['message'] = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            $GLOBALS['message']->addParam(__('Lines terminated with'));
            $GLOBALS['error'] = true;
            $paramError = true;
        }

        // If there is an error in the parameters entered,
        // indicate that immediately.
        if ($paramError) {
            Generator::mysqlDie(
                $GLOBALS['message']->getMessage(),
                '',
                false,
                $errUrl,
            );
        }

        return [$GLOBALS['error'], $GLOBALS['message']];
    }

    private function getTableNameFromImport(string $databaseName): string
    {
        // get new table name, if user didn't provide one, set the default name
        if (isset($_REQUEST['csv_new_tbl_name']) && (string) $_REQUEST['csv_new_tbl_name'] !== '') {
            return $_REQUEST['csv_new_tbl_name'];
        }

        return $this->import->getNextAvailableTableName(
            $databaseName,
            pathinfo(ImportSettings::$importFileName, PATHINFO_FILENAME),
        );
    }

    /**
     * @param list<string> $columnNames
     *
     * @return list<string>
     */
    private function getColumnNames(array $columnNames, int $maxCols): array
    {
        if (isset($_REQUEST['csv_col_names'])) {
            // MySQL column names can't end with a space character.
            foreach ($columnNames as $key => $colName) {
                $columnNames[$key] = rtrim($colName);
            }
        }

        if (count($columnNames) !== $maxCols) {
            // Fill out column names
            /** @infection-ignore-all */
            for ($i = 0; $i < $maxCols; ++$i) {
                $columnNames[] = 'COL ' . ($i + 1);
            }
        }

        return $columnNames;
    }

    /** @return array{string, string[]} */
    private function getSqlTemplateAndRequiredFields(
        string|null $db,
        string|null $table,
        string|null $csvColumns,
    ): array {
        $GLOBALS['error'] ??= null;
        $GLOBALS['message'] ??= null;

        $sqlTemplate = '';
        $fields = [];
        if (! $this->analyze && $db !== null && $table !== null) {
            $sqlTemplate = 'INSERT';
            if (isset($_POST['csv_ignore'])) {
                $sqlTemplate .= ' IGNORE';
            }

            $sqlTemplate .= ' INTO ' . Util::backquote($table);

            $tmpFields = DatabaseInterface::getInstance()->getColumnNames($db, $table);

            if ($csvColumns === null || $csvColumns === '') {
                $fields = $tmpFields;
            } else {
                $sqlTemplate .= ' (';
                $tmp = preg_split('/,( ?)/', $csvColumns);
                if ($tmp === false) {
                    $tmp = [];
                }

                foreach ($tmp as $val) {
                    if ($fields !== []) {
                        $sqlTemplate .= ', ';
                    }

                    /* Trim also `, if user already included backquoted fields */
                    $val = trim($val, " \t\r\n\0\x0B`");
                    $found = false;
                    foreach ($tmpFields as $field) {
                        if ($field === $val) {
                            $found = true;
                            break;
                        }
                    }

                    if (! $found) {
                        $GLOBALS['message'] = Message::error(
                            __(
                                'Invalid column (%s) specified! Ensure that columns'
                                . ' names are spelled correctly, separated by commas'
                                . ', and not enclosed in quotes.',
                            ),
                        );
                        $GLOBALS['message']->addParam($val);
                        $GLOBALS['error'] = true;
                        break;
                    }

                    if (isset($field)) {
                        $fields[] = $field;
                    }

                    $sqlTemplate .= Util::backquote($val);
                }

                $sqlTemplate .= ') ';
            }

            $sqlTemplate .= ' VALUES (';
        }

        return [$sqlTemplate, $fields];
    }

    /**
     * Read the expected column_separated_with String of length
     * $csv_terminated_len from the $buffer
     * into variable $ch and return the read string $ch
     *
     * @param string $buffer           The original string buffer read from csv file
     * @param string $ch               Partially read "column Separated with" string, also used to return after
     *                                 reading length equal $csv_terminated_len
     * @param int    $i                Current read counter of buffer string
     * @param int    $csvTerminatedLen The length of "column separated with" String
     */
    public function readCsvTerminatedString(string $buffer, string $ch, int $i, int $csvTerminatedLen): string
    {
        for ($j = 0; $j < $csvTerminatedLen - 1; $j++) {
            $i++;
            $ch .= mb_substr($buffer, $i, 1);
        }

        return $ch;
    }
}
