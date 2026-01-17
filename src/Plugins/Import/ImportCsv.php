<?php
/**
 * CSV import plugin for phpMyAdmin
 *
 * @todo       add an option for handling NULL values
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Import\ImportTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\NumberPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function array_map;
use function array_pad;
use function array_shift;
use function count;
use function implode;
use function in_array;
use function max;
use function min;
use function pathinfo;
use function preg_split;
use function rtrim;
use function str_contains;
use function strlen;
use function strtr;
use function substr;
use function trim;

use const PATHINFO_FILENAME;

/**
 * Handles the import for the CSV format
 */
class ImportCsv extends AbstractImportCsv
{
    private bool $intoExistingTable = true;

    private bool $replace = false;
    private bool $ignore = false;
    private string $terminated = '';
    private string $enclosed = '';
    private string $escaped = '';
    private string $newLine = '';
    private string $columns = '';
    private int $maxLines = 0;
    private bool $csvHasColumnNames = false;
    private string $newDatabaseName = '';
    private string $newTableName = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'csv';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $this->intoExistingTable = ImportSettings::$importType === 'table';

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('CSV');
        $importPluginProperties->setExtension('csv');
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        $generalOptions = $this->getGeneralOptions();

        if (ImportSettings::$importType !== 'table') {
            $leaf = new TextPropertyItem(
                'new_tbl_name',
                __(
                    'Name of the new table (optional):',
                ),
            );
            $generalOptions->addProperty($leaf);

            if (ImportSettings::$importType === 'server') {
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

    public function setImportOptions(ServerRequest $request): void
    {
        $this->replace = $request->getParsedBodyParam('csv_replace') !== null;
        $this->ignore = $request->getParsedBodyParam('csv_ignore') !== null;
        $this->terminated = $request->getParsedBodyParamAsString('csv_terminated', '');
        $this->enclosed = $request->getParsedBodyParamAsString('csv_enclosed', '');
        $this->escaped = $request->getParsedBodyParamAsString('csv_escaped', '');
        $this->newLine = $request->getParsedBodyParamAsString('csv_new_line', '');
        $this->columns = $request->getParsedBodyParamAsString('csv_columns', '');
        $this->maxLines = min(0, (int) $request->getParsedBodyParamAsStringOrNull('csv_partial_import'));
        $this->csvHasColumnNames = $request->getParsedBodyParam('csv_col_names') !== null;
        $this->newDatabaseName = $request->getParsedBodyParamAsString('csv_new_db_name', '');
        $this->newTableName = $request->getParsedBodyParamAsString('csv_new_tbl_name', '');
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
        $replacements = ['\\n' => "\n", '\\t' => "\t", '\\r' => "\r"];
        $this->terminated = strtr($this->terminated, $replacements);
        $this->enclosed = strtr($this->enclosed, $replacements);
        $this->escaped = strtr($this->escaped, $replacements);
        $this->newLine = strtr($this->newLine, $replacements);

        [Import::$hasError, Current::$message] = $this->buildErrorsForParams(
            $this->terminated,
            $this->enclosed,
            $this->escaped,
            $this->newLine,
            Import::$errorUrl,
        );

        $fields = $this->getSqlRequiredFields(Current::$database, Current::$table);
        $sqlTemplate = $this->getSqlTemplate(Current::$database, Current::$table, $fields);

        $sqlStatements = [];

        // Defaults for parser
        $i = 0;
        $len = 0;
        $lastlen = 0;
        $line = 1;
        $lasti = -1;
        $values = [];
        $csvFinish = false;

        $maxLinesConstraint = $this->maxLines + 1;
        // if the first row has to be counted as column names, include one more row in the max lines
        if ($this->csvHasColumnNames) {
            $maxLinesConstraint++;
        }

        $rows = [];
        $buffer = '';
        $maxCols = 0;
        $csvTerminatedLen = strlen($this->terminated);
        $dbi = DatabaseInterface::getInstance();
        while (! (ImportSettings::$finished && $i >= $len) && ! Import::$hasError && ! ImportSettings::$timeoutPassed) {
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
                    $finalch = substr($buffer, -1);
                    if ($this->newLine === 'auto' && $finalch !== "\r" && $finalch !== "\n") {
                        $buffer .= "\n";
                    } elseif ($this->newLine !== 'auto' && $finalch !== $this->newLine) {
                        $buffer .= $this->newLine;
                    }
                }

                // Do not parse string when we're not at the end
                // and don't have new line inside
                if (
                    ($this->newLine === 'auto'
                    && ! str_contains($buffer, "\r")
                    && ! str_contains($buffer, "\n"))
                    || ($this->newLine !== 'auto'
                    && ! str_contains($buffer, $this->newLine))
                ) {
                    continue;
                }
            }

            // Current length of our buffer
            $len = strlen($buffer);
            // Currently parsed char

            $ch = $buffer[$i];
            if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                $i += $csvTerminatedLen - 1;
            }

            while ($i < $len) {
                // Deadlock protection
                if ($lasti === $i && $lastlen === $len) {
                    Current::$message = Message::error(
                        __('Invalid format of CSV input on line %d.'),
                    );
                    Current::$message->addParam($line);
                    Import::$hasError = true;
                    break;
                }

                $lasti = $i;
                $lastlen = $len;

                // This can happen with auto EOL and \r at the end of buffer
                if (! $csvFinish) {
                    // Grab empty field
                    if ($ch === $this->terminated) {
                        if ($i === $len - 1) {
                            break;
                        }

                        $values[] = '';
                        $i++;
                        $ch = $buffer[$i];
                        if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                            $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                            $i += $csvTerminatedLen - 1;
                        }

                        continue;
                    }

                    // Grab one field
                    $fallbacki = $i;
                    if ($ch === $this->enclosed) {
                        if ($i === $len - 1) {
                            break;
                        }

                        $needEnd = true;
                        $i++;
                        $ch = $buffer[$i];
                        if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                            $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                            $i += $csvTerminatedLen - 1;
                        }
                    } else {
                        $needEnd = false;
                    }

                    $fail = false;
                    $value = '';
                    while (
                        ($needEnd
                            && ($ch != $this->enclosed
                                || $this->enclosed === $this->escaped))
                        || (! $needEnd
                            && ! ($ch === $this->terminated
                                || $ch === $this->newLine
                                || ($this->newLine === 'auto'
                                    && ($ch === "\r" || $ch === "\n"))))
                    ) {
                        if ($ch === $this->escaped) {
                            if ($i === $len - 1) {
                                $fail = true;
                                break;
                            }

                            $i++;
                            $ch = $buffer[$i];
                            if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                                $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                                $i += $csvTerminatedLen - 1;
                            }

                            if (
                                $this->enclosed === $this->escaped
                                && ($ch === $this->terminated
                                || $ch === $this->newLine
                                || ($this->newLine === 'auto'
                                && ($ch === "\r" || $ch === "\n")))
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
                        $ch = $buffer[$i];
                        if ($csvTerminatedLen <= 1 || $ch != $this->terminated[0]) {
                            continue;
                        }

                        $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                        $i += $csvTerminatedLen - 1;
                    }

                    // unquoted NULL string
                    if ($needEnd === false && $value === 'NULL') {
                        $value = null;
                    }

                    if ($fail) {
                        $i = $fallbacki;
                        $ch = $buffer[$i];
                        if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                            $i += $csvTerminatedLen - 1;
                        }

                        break;
                    }

                    // Need to strip trailing enclosing char?
                    if ($needEnd && $ch === $this->enclosed) {
                        if (ImportSettings::$finished && $i === $len - 1) {
                            $ch = null;
                        } elseif ($i === $len - 1) {
                            $i = $fallbacki;
                            $ch = $buffer[$i];
                            if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                                $i += $csvTerminatedLen - 1;
                            }

                            break;
                        } else {
                            $i++;
                            $ch = $buffer[$i];
                            if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                                $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                                $i += $csvTerminatedLen - 1;
                            }
                        }
                    }

                    // Are we at the end?
                    if (
                        $ch === $this->newLine
                        || ($this->newLine === 'auto' && ($ch === "\r" || $ch === "\n"))
                        || (ImportSettings::$finished && $i === $len - 1)
                    ) {
                        $csvFinish = true;
                    }

                    // Go to next char
                    if ($ch === $this->terminated) {
                        if ($i === $len - 1) {
                            $i = $fallbacki;
                            $ch = $buffer[$i];
                            if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                                $i += $csvTerminatedLen - 1;
                            }

                            break;
                        }

                        $i++;
                        $ch = $buffer[$i];
                        if ($csvTerminatedLen > 1 && $ch === $this->terminated[0]) {
                            $ch .= substr($buffer, $i + 1, $csvTerminatedLen - 1);
                            $i += $csvTerminatedLen - 1;
                        }
                    }

                    // If everything went okay, store value
                    $values[] = $value;
                }

                // End of line
                if (
                    ! $csvFinish
                    && $ch != $this->newLine
                    && ($this->newLine !== 'auto' || ($ch !== "\r" && $ch !== "\n"))
                ) {
                    continue;
                }

                if ($this->newLine === 'auto' && $ch === "\r") { // Handle "\r\n"
                    if ($i >= ($len - 2) && ! ImportSettings::$finished) {
                        break; // We need more data to decide new line
                    }

                    if (substr($buffer, $i + 1, 1) === "\n") {
                        $i++;
                    }
                }

                // We didn't parse value till the end of line, so there was
                // empty one
                if (! $csvFinish) {
                    $values[] = '';
                }

                if (! $this->intoExistingTable) {
                    $maxCols = max($maxCols, count($values));
                    $rows[] = $values;
                } else {
                    // Do we have correct count of values?
                    if (count($values) !== count($fields)) {
                        // Hack for excel
                        if ($values[count($values) - 1] !== ';') {
                            Current::$message = Message::error(
                                __(
                                    'Invalid column count in CSV input on line %d.',
                                ),
                            );
                            Current::$message->addParam($line);
                            Import::$hasError = true;
                            break;
                        }

                        unset($values[count($values) - 1]);
                    }

                    $sqlStatements = $this->addRowToDatabase($values, $dbi, $sqlTemplate, $fields, $sqlStatements);
                }

                $line++;
                $csvFinish = false;
                $values = [];
                $buffer = substr($buffer, $i + 1);
                $len = strlen($buffer);
                $i = 0;
                $lasti = -1;
                $ch = substr($buffer, 0, 1);
                if ($this->maxLines > 0 && $line === $maxLinesConstraint) {
                    ImportSettings::$finished = true;
                    break;
                }
            }

            if ($this->maxLines > 0 && $line === $maxLinesConstraint) {
                ImportSettings::$finished = true;
                break;
            }
        }

        if (! $this->intoExistingTable) {
            $this->buildSqlStructures($rows, $maxCols, $dbi, $sqlStatements);
        }

        // Commit any possible data in buffers
        $this->import->runQuery('', $sqlStatements);

        if ($values === [] || Import::$hasError) {
            return $sqlStatements;
        }

        Current::$message = Message::error(
            __('Invalid format of CSV input on line %d.'),
        );
        Current::$message->addParam($line);
        Import::$hasError = true;

        return $sqlStatements;
    }

    /**
     * @param (string|null)[] $values
     * @param string[]        $fields
     * @param string[]        $sqlStatements
     *
     * @return string[]
     */
    private function addRowToDatabase(
        array $values,
        DatabaseInterface $dbi,
        string $sqlTemplate,
        array $fields,
        array $sqlStatements,
    ): array {
        $quotedValues = [];
        foreach ($values as $val) {
            $quotedValues[] = $val === null ? 'NULL' : $dbi->quoteString($val);
        }

        $sql = $sqlTemplate . implode(', ', $quotedValues) . ')';

        if ($this->replace) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            foreach ($fields as $field) {
                $fieldName = Util::backquote($field);
                $sql .= $fieldName . ' = VALUES(' . $fieldName . '), ';
            }

            $sql = rtrim($sql, ', ');
        }

        /**
         * @todo maybe we could add original line to verbose
         * SQL in comment
         */
        $this->import->runQuery($sql, $sqlStatements);

        return $sqlStatements;
    }

    /**
     * @param list<list<string|null>> $rows
     * @param string[]                $sqlStatements
     */
    private function buildSqlStructures(
        array $rows,
        int $maxCols,
        DatabaseInterface $dbi,
        array &$sqlStatements,
    ): void {
        /* Fill out all rows */
        foreach ($rows as $i => $row) {
            $rows[$i] = array_pad($row, $maxCols, 'NULL');
        }

        $colNames = [];
        /* Remove the first row if it contains the column names */
        if ($this->csvHasColumnNames) {
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
        if ($this->newDatabaseName !== '') {
            $newDb = $this->newDatabaseName;
        } else {
            $result = $dbi->fetchSingleColumn('SHOW DATABASES');

            $newDb = 'CSV_DB ' . (count($result) + 1);
        }

        $dbName = Current::$database !== '' ? Current::$database : $newDb;
        $createDb = Current::$database === '';

        if ($createDb) {
            $sqlStatements = $this->import->createDatabase($dbName, 'utf8', 'utf8_general_ci', $sqlStatements);
        }

        $this->import->buildSql(
            $dbName,
            [$table],
            [$analysis],
            sqlData: $sqlStatements,
            insertMode: $this->ignore ? 'INSERT IGNORE' : 'INSERT',
        );
    }

    /** @return array{bool, Message|null} */
    private function buildErrorsForParams(
        string $csvTerminated,
        string $csvEnclosed,
        string $csvEscaped,
        string $csvNewLine,
        string $errUrl,
    ): array {
        $paramError = false;
        if ($csvTerminated === '') {
            Current::$message = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            Current::$message->addParam(__('Columns terminated with'));
            Import::$hasError = true;
            $paramError = true;
            // The default dialog of MS Excel when generating a CSV produces a
            // semi-colon-separated file with no chance of specifying the
            // enclosing character. Thus, users who want to import this file
            // tend to remove the enclosing character on the Import dialog.
            // I could not find a test case where having no enclosing characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (strlen($csvEnclosed) > 1) {
            Current::$message = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            Current::$message->addParam(__('Columns enclosed with'));
            Import::$hasError = true;
            $paramError = true;
            // I could not find a test case where having no escaping characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (strlen($csvEscaped) > 1) {
            Current::$message = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            Current::$message->addParam(__('Columns escaped with'));
            Import::$hasError = true;
            $paramError = true;
        } elseif (strlen($csvNewLine) !== 1 && $csvNewLine !== 'auto') {
            Current::$message = Message::error(
                __('Invalid parameter for CSV import: %s'),
            );
            Current::$message->addParam(__('Lines terminated with'));
            Import::$hasError = true;
            $paramError = true;
        }

        // If there is an error in the parameters entered,
        // indicate that immediately.
        if ($paramError) {
            Generator::mysqlDie(
                Current::$message->getMessage(),
                '',
                false,
                $errUrl,
            );
        }

        return [Import::$hasError, Current::$message];
    }

    private function getTableNameFromImport(string $databaseName): string
    {
        // get new table name, if user didn't provide one, set the default name
        if ($this->newTableName !== '') {
            return $this->newTableName;
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
        if ($this->csvHasColumnNames) {
            // MySQL column names can't end with a space character.
            $columnNames = array_map(rtrim(...), $columnNames);
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

    /** @return string[] */
    private function getSqlRequiredFields(
        string|null $db,
        string|null $table,
    ): array {
        if (! $this->intoExistingTable || $db === null || $table === null) {
            return [];
        }

        $tmpFields = DatabaseInterface::getInstance()->getColumnNames($db, $table);

        if ($this->columns === '') {
            return $tmpFields;
        }

        $fields = [];
        $tmp = preg_split('/,( ?)/', $this->columns);
        if ($tmp === false) {
            $tmp = [];
        }

        foreach ($tmp as $val) {
            /* Trim also `, if user already included backquoted fields */
            $val = trim($val, " \t\r\n\0\x0B`");

            if (! in_array($val, $tmpFields, true)) {
                Current::$message = Message::error(
                    __(
                        'Invalid column (%s) specified! Ensure that columns'
                        . ' names are spelled correctly, separated by commas'
                        . ', and not enclosed in quotes.',
                    ),
                );
                Current::$message->addParam($val);
                Import::$hasError = true;
                break;
            }

            $fields[] = $val;
        }

        return $fields;
    }

    /** @param string[] $fields */
    private function getSqlTemplate(
        string|null $db,
        string|null $table,
        array $fields,
    ): string {
        if (! $this->intoExistingTable || $db === null || $table === null) {
            return '';
        }

        $sqlTemplate = 'INSERT';
        if ($this->ignore) {
            $sqlTemplate .= ' IGNORE';
        }

        $sqlTemplate .= ' INTO ' . Util::backquote($table);

        if ($this->columns !== '') {
            $sqlTemplate .= ' (';

            $sqlTemplate .= implode(
                ', ',
                array_map(Util::backquote(...), $fields),
            );

            $sqlTemplate .= ') ';
        }

        $sqlTemplate .= ' VALUES (';

        return $sqlTemplate;
    }
}
