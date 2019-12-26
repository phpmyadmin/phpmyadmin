<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV import plugin for phpMyAdmin
 *
 * @todo       add an option for handling NULL values
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\NumberPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Util;

/**
 * Handles the import for the CSV format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage CSV
 */
class ImportCsv extends AbstractImportCsv
{
    /**
     * Whether to analyze tables
     *
     * @var bool
     */
    private $_analyze;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->_setAnalyze(false);

        if ($GLOBALS['plugin_param'] !== 'table') {
            $this->_setAnalyze(true);
        }

        $generalOptions = parent::setProperties();
        $this->properties->setText('CSV');
        $this->properties->setExtension('csv');

        if ($GLOBALS['plugin_param'] !== 'table') {
            $leaf = new TextPropertyItem(
                "new_tbl_name",
                __(
                    'Name of the new table (optional):'
                )
            );
            $generalOptions->addProperty($leaf);

            if ($GLOBALS['plugin_param'] === 'server') {
                $leaf = new TextPropertyItem(
                    "new_db_name",
                    __(
                        'Name of the new database (optional):'
                    )
                );
                $generalOptions->addProperty($leaf);
            }

            $leaf = new NumberPropertyItem(
                "partial_import",
                __(
                    'Import these many number of rows (optional):'
                )
            );
            $generalOptions->addProperty($leaf);

            $leaf = new BoolPropertyItem(
                "col_names",
                __(
                    'The first line of the file contains the table column names'
                    . ' <i>(if this is unchecked, the first line will become part'
                    . ' of the data)</i>'
                )
            );
            $generalOptions->addProperty($leaf);
        } else {
            $leaf = new NumberPropertyItem(
                "partial_import",
                __(
                    'Import these many number of rows (optional):'
                )
            );
            $generalOptions->addProperty($leaf);

            $hint = new Message(
                __(
                    'If the data in each row of the file is not'
                    . ' in the same order as in the database, list the corresponding'
                    . ' column names here. Column names must be separated by commas'
                    . ' and not enclosed in quotations.'
                )
            );
            $leaf = new TextPropertyItem(
                "columns",
                __('Column names:') . ' ' . Util::showHint($hint)
            );
            $generalOptions->addProperty($leaf);
        }

        $leaf = new BoolPropertyItem(
            "ignore",
            __('Do not abort on INSERT error')
        );
        $generalOptions->addProperty($leaf);
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     *
     * @return void
     */
    public function doImport(array &$sql_data = [])
    {
        global $db, $table, $csv_terminated, $csv_enclosed, $csv_escaped,
               $csv_new_line, $csv_columns, $err_url, $import_file_name;
        // $csv_replace and $csv_ignore should have been here,
        // but we use directly from $_POST
        global $error, $timeout_passed, $finished, $message;

        $import_file_name = basename($import_file_name, ".csv");
        $import_file_name = mb_strtolower($import_file_name);
        $import_file_name = preg_replace("/[^a-zA-Z0-9_]/", "_", $import_file_name);
        
        $replacements = [
            '\\n' => "\n",
            '\\t' => "\t",
            '\\r' => "\r",
        ];
        $csv_terminated = strtr($csv_terminated, $replacements);
        $csv_enclosed = strtr($csv_enclosed, $replacements);
        $csv_escaped = strtr($csv_escaped, $replacements);
        $csv_new_line = strtr($csv_new_line, $replacements);

        $param_error = false;
        if (strlen($csv_terminated) === 0) {
            $message = Message::error(
                __('Invalid parameter for CSV import: %s')
            );
            $message->addParam(__('Columns terminated with'));
            $error = true;
            $param_error = true;
            // The default dialog of MS Excel when generating a CSV produces a
            // semi-colon-separated file with no chance of specifying the
            // enclosing character. Thus, users who want to import this file
            // tend to remove the enclosing character on the Import dialog.
            // I could not find a test case where having no enclosing characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (mb_strlen($csv_enclosed) > 1) {
            $message = Message::error(
                __('Invalid parameter for CSV import: %s')
            );
            $message->addParam(__('Columns enclosed with'));
            $error = true;
            $param_error = true;
            // I could not find a test case where having no escaping characters
            // confuses this script.
            // But the parser won't work correctly with strings so we allow just
            // one character.
        } elseif (mb_strlen($csv_escaped) > 1) {
            $message = Message::error(
                __('Invalid parameter for CSV import: %s')
            );
            $message->addParam(__('Columns escaped with'));
            $error = true;
            $param_error = true;
        } elseif (mb_strlen($csv_new_line) != 1
            && $csv_new_line != 'auto'
        ) {
            $message = Message::error(
                __('Invalid parameter for CSV import: %s')
            );
            $message->addParam(__('Lines terminated with'));
            $error = true;
            $param_error = true;
        }

        // If there is an error in the parameters entered,
        // indicate that immediately.
        if ($param_error) {
            Util::mysqlDie(
                $message->getMessage(),
                '',
                false,
                $err_url
            );
        }

        $buffer = '';
        $required_fields = 0;
        $sql_template = '';
        $fields = [];
        if (! $this->_getAnalyze()) {
            $sql_template = 'INSERT';
            if (isset($_POST['csv_ignore'])) {
                $sql_template .= ' IGNORE';
            }
            $sql_template .= ' INTO ' . Util::backquote($table);

            $tmp_fields = $GLOBALS['dbi']->getColumns($db, $table);

            if (empty($csv_columns)) {
                $fields = $tmp_fields;
            } else {
                $sql_template .= ' (';
                $fields = [];
                $tmp = preg_split('/,( ?)/', $csv_columns);
                foreach ($tmp as $key => $val) {
                    if (count($fields) > 0) {
                        $sql_template .= ', ';
                    }
                    /* Trim also `, if user already included backquoted fields */
                    $val = trim($val, " \t\r\n\0\x0B`");
                    $found = false;
                    foreach ($tmp_fields as $field) {
                        if ($field['Field'] == $val) {
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        $message = Message::error(
                            __(
                                'Invalid column (%s) specified! Ensure that columns'
                                . ' names are spelled correctly, separated by commas'
                                . ', and not enclosed in quotes.'
                            )
                        );
                        $message->addParam($val);
                        $error = true;
                        break;
                    }
                    if (isset($field)) {
                        $fields[] = $field;
                    }
                    $sql_template .= Util::backquote($val);
                }
                $sql_template .= ') ';
            }

            $required_fields = count($fields);

            $sql_template .= ' VALUES (';
        }

        // Defaults for parser
        $i = 0;
        $len = 0;
        $lastlen = null;
        $line = 1;
        $lasti = -1;
        $values = [];
        $csv_finish = false;
        $max_lines = 0; // defaults to 0 (get all the lines)

        // If we get a negative value, probably someone changed min value attribute in DOM or there is an integer overflow, whatever be the case, get all the lines
        if (isset($_REQUEST['csv_partial_import']) && $_REQUEST['csv_partial_import'] > 0) {
            $max_lines = $_REQUEST['csv_partial_import'];
        }
        $max_lines_constraint = $max_lines+1;
        // if the first row has to be counted as column names, include one more row in the max lines
        if (isset($_REQUEST['csv_col_names'])) {
            $max_lines_constraint++;
        }

        $tempRow = [];
        $rows = [];
        $col_names = [];
        $tables = [];

        $col_count = 0;
        $max_cols = 0;
        $csv_terminated_len = mb_strlen($csv_terminated);
        while (! ($finished && $i >= $len) && ! $error && ! $timeout_passed) {
            $data = $this->import->getNextChunk();
            if ($data === false) {
                // subtract data we didn't handle yet and stop processing
                $GLOBALS['offset'] -= strlen($buffer);
                break;
            } elseif ($data !== true) {
                // Append new data to buffer
                $buffer .= $data;
                unset($data);

                // Force a trailing new line at EOF to prevent parsing problems
                if ($finished && $buffer) {
                    $finalch = mb_substr($buffer, -1);
                    if ($csv_new_line == 'auto'
                        && $finalch != "\r"
                        && $finalch != "\n"
                    ) {
                        $buffer .= "\n";
                    } elseif ($csv_new_line != 'auto'
                        && $finalch != $csv_new_line
                    ) {
                        $buffer .= $csv_new_line;
                    }
                }

                // Do not parse string when we're not at the end
                // and don't have new line inside
                if (($csv_new_line == 'auto'
                    && mb_strpos($buffer, "\r") === false
                    && mb_strpos($buffer, "\n") === false)
                    || ($csv_new_line != 'auto'
                    && mb_strpos($buffer, $csv_new_line) === false)
                ) {
                    continue;
                }
            }

            // Current length of our buffer
            $len = mb_strlen($buffer);
            // Currently parsed char

            $ch = mb_substr($buffer, $i, 1);
            if ($csv_terminated_len > 1 && $ch == $csv_terminated[0]) {
                $ch = $this->readCsvTerminatedString(
                    $buffer,
                    $ch,
                    $i,
                    $csv_terminated_len
                );
                $i += $csv_terminated_len - 1;
            }
            while ($i < $len) {
                // Deadlock protection
                if ($lasti == $i && $lastlen == $len) {
                    $message = Message::error(
                        __('Invalid format of CSV input on line %d.')
                    );
                    $message->addParam($line);
                    $error = true;
                    break;
                }
                $lasti = $i;
                $lastlen = $len;

                // This can happen with auto EOL and \r at the end of buffer
                if (! $csv_finish) {
                    // Grab empty field
                    if ($ch == $csv_terminated) {
                        if ($i == $len - 1) {
                            break;
                        }
                        $values[] = '';
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csv_terminated_len > 1 && $ch == $csv_terminated[0]) {
                            $ch = $this->readCsvTerminatedString(
                                $buffer,
                                $ch,
                                $i,
                                $csv_terminated_len
                            );
                            $i += $csv_terminated_len - 1;
                        }
                        continue;
                    }

                    // Grab one field
                    $fallbacki = $i;
                    if ($ch == $csv_enclosed) {
                        if ($i == $len - 1) {
                            break;
                        }
                        $need_end = true;
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csv_terminated_len > 1 && $ch == $csv_terminated[0]) {
                            $ch = $this->readCsvTerminatedString(
                                $buffer,
                                $ch,
                                $i,
                                $csv_terminated_len
                            );
                            $i += $csv_terminated_len - 1;
                        }
                    } else {
                        $need_end = false;
                    }
                    $fail = false;
                    $value = '';
                    while (($need_end
                            && ($ch != $csv_enclosed
                                || $csv_enclosed == $csv_escaped))
                        || (! $need_end
                            && ! ($ch == $csv_terminated
                                || $ch == $csv_new_line
                                || ($csv_new_line == 'auto'
                                    && ($ch == "\r" || $ch == "\n"))))
                    ) {
                        if ($ch == $csv_escaped) {
                            if ($i == $len - 1) {
                                $fail = true;
                                break;
                            }
                            $i++;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csv_terminated_len > 1
                                && $ch == $csv_terminated[0]
                            ) {
                                $ch = $this->readCsvTerminatedString(
                                    $buffer,
                                    $ch,
                                    $i,
                                    $csv_terminated_len
                                );
                                $i += $csv_terminated_len - 1;
                            }
                            if ($csv_enclosed == $csv_escaped
                                && ($ch == $csv_terminated
                                || $ch == $csv_new_line
                                || ($csv_new_line == 'auto'
                                && ($ch == "\r" || $ch == "\n")))
                            ) {
                                break;
                            }
                        }
                        $value .= $ch;
                        if ($i == $len - 1) {
                            if (! $finished) {
                                $fail = true;
                            }
                            break;
                        }
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csv_terminated_len > 1 && $ch == $csv_terminated[0]) {
                            $ch = $this->readCsvTerminatedString(
                                $buffer,
                                $ch,
                                $i,
                                $csv_terminated_len
                            );
                            $i += $csv_terminated_len - 1;
                        }
                    }

                    // unquoted NULL string
                    if (false === $need_end && $value === 'NULL') {
                        $value = null;
                    }

                    if ($fail) {
                        $i = $fallbacki;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csv_terminated_len > 1 && $ch == $csv_terminated[0]) {
                            $i += $csv_terminated_len - 1;
                        }
                        break;
                    }
                    // Need to strip trailing enclosing char?
                    if ($need_end && $ch == $csv_enclosed) {
                        if ($finished && $i == $len - 1) {
                            $ch = null;
                        } elseif ($i == $len - 1) {
                            $i = $fallbacki;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csv_terminated_len > 1
                                && $ch == $csv_terminated[0]
                            ) {
                                $i += $csv_terminated_len - 1;
                            }
                            break;
                        } else {
                            $i++;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csv_terminated_len > 1
                                && $ch == $csv_terminated[0]
                            ) {
                                $ch = $this->readCsvTerminatedString(
                                    $buffer,
                                    $ch,
                                    $i,
                                    $csv_terminated_len
                                );
                                $i += $csv_terminated_len - 1;
                            }
                        }
                    }
                    // Are we at the end?
                    if ($ch == $csv_new_line
                        || ($csv_new_line == 'auto' && ($ch == "\r" || $ch == "\n"))
                        || ($finished && $i == $len - 1)
                    ) {
                        $csv_finish = true;
                    }
                    // Go to next char
                    if ($ch == $csv_terminated) {
                        if ($i == $len - 1) {
                            $i = $fallbacki;
                            $ch = mb_substr($buffer, $i, 1);
                            if ($csv_terminated_len > 1
                                && $ch == $csv_terminated[0]
                            ) {
                                $i += $csv_terminated_len - 1;
                            }
                            break;
                        }
                        $i++;
                        $ch = mb_substr($buffer, $i, 1);
                        if ($csv_terminated_len > 1
                            && $ch == $csv_terminated[0]
                        ) {
                            $ch = $this->readCsvTerminatedString(
                                $buffer,
                                $ch,
                                $i,
                                $csv_terminated_len
                            );
                            $i += $csv_terminated_len - 1;
                        }
                    }
                    // If everything went okay, store value
                    $values[] = $value;
                }

                // End of line
                if ($csv_finish
                    || $ch == $csv_new_line
                    || ($csv_new_line == 'auto' && ($ch == "\r" || $ch == "\n"))
                ) {
                    if ($csv_new_line == 'auto' && $ch == "\r") { // Handle "\r\n"
                        if ($i >= ($len - 2) && ! $finished) {
                            break; // We need more data to decide new line
                        }
                        if (mb_substr($buffer, $i + 1, 1) == "\n") {
                            $i++;
                        }
                    }
                    // We didn't parse value till the end of line, so there was
                    // empty one
                    if (! $csv_finish) {
                        $values[] = '';
                    }

                    if ($this->_getAnalyze()) {
                        foreach ($values as $val) {
                            $tempRow[] = $val;
                            ++$col_count;
                        }

                        if ($col_count > $max_cols) {
                            $max_cols = $col_count;
                        }
                        $col_count = 0;

                        $rows[] = $tempRow;
                        $tempRow = [];
                    } else {
                        // Do we have correct count of values?
                        if (count($values) != $required_fields) {
                            // Hack for excel
                            if ($values[count($values) - 1] == ';') {
                                unset($values[count($values) - 1]);
                            } else {
                                $message = Message::error(
                                    __(
                                        'Invalid column count in CSV input'
                                        . ' on line %d.'
                                    )
                                );
                                $message->addParam($line);
                                $error = true;
                                break;
                            }
                        }

                        $first = true;
                        $sql = $sql_template;
                        foreach ($values as $key => $val) {
                            if (! $first) {
                                $sql .= ', ';
                            }
                            if ($val === null) {
                                $sql .= 'NULL';
                            } else {
                                $sql .= '\''
                                    . $GLOBALS['dbi']->escapeString($val)
                                    . '\'';
                            }

                            $first = false;
                        }
                        $sql .= ')';
                        if (isset($_POST['csv_replace'])) {
                            $sql .= " ON DUPLICATE KEY UPDATE ";
                            foreach ($fields as $field) {
                                $fieldName = Util::backquote(
                                    $field['Field']
                                );
                                $sql .= $fieldName . " = VALUES(" . $fieldName
                                    . "), ";
                            }
                            $sql = rtrim($sql, ', ');
                        }

                        /**
                         * @todo maybe we could add original line to verbose
                         * SQL in comment
                         */
                        $this->import->runQuery($sql, $sql, $sql_data);
                    }

                    $line++;
                    $csv_finish = false;
                    $values = [];
                    $buffer = mb_substr($buffer, $i + 1);
                    $len = mb_strlen($buffer);
                    $i = 0;
                    $lasti = -1;
                    $ch = mb_substr($buffer, 0, 1);
                    if ($max_lines > 0 && $line == $max_lines_constraint) {
                        $finished = 1;
                        break;
                    }
                }
            } // End of parser loop
            if ($max_lines > 0 && $line == $max_lines_constraint) {
                $finished = 1;
                break;
            }
        } // End of import loop

        if ($this->_getAnalyze()) {
            /* Fill out all rows */
            $num_rows = count($rows);
            for ($i = 0; $i < $num_rows; ++$i) {
                for ($j = count($rows[$i]); $j < $max_cols; ++$j) {
                    $rows[$i][] = 'NULL';
                }
            }

            if (isset($_REQUEST['csv_col_names'])) {
                $col_names = array_splice($rows, 0, 1);
                $col_names = $col_names[0];
                // MySQL column names can't end with a space character.
                foreach ($col_names as $key => $col_name) {
                    $col_names[$key] = rtrim($col_name);
                }
            }

            if ((isset($col_names) && count($col_names) != $max_cols)
                || ! isset($col_names)
            ) {
                // Fill out column names
                for ($i = 0; $i < $max_cols; ++$i) {
                    $col_names[] = 'COL ' . ($i + 1);
                }
            }

            // get new table name, if user didn't provide one, set the default name
            if (isset($_REQUEST['csv_new_tbl_name'])
                && strlen($_REQUEST['csv_new_tbl_name']) > 0
            ) {
                $tbl_name = $_REQUEST['csv_new_tbl_name'];
            } elseif (mb_strlen((string) $db)) {
                $result = $GLOBALS['dbi']->fetchResult('SHOW TABLES');
                
                // logic to get table name from filename
                // if no table then use filename as table name
                if (count($result) === 0) {
                    $tbl_name = $import_file_name;
                } else {
                    // check to see if {filename} as table exist
                    $name_array = preg_grep("/{$import_file_name}/isU", $result);
                    // if no use filename as table name
                    if (count($name_array) === 0) {
                        $tbl_name = $import_file_name;
                    } else {
                        // check if {filename}_ as table exist
                        $name_array = preg_grep("/{$import_file_name}_/isU", $result);
                        $tbl_name = $import_file_name . "_" . (count($name_array) + 1);
                    }
                }
            } else {
                $tbl_name = $import_file_name;
            }

            $tables[] = [
                $tbl_name,
                $col_names,
                $rows,
            ];

            /* Obtain the best-fit MySQL types for each column */
            $analyses = [];
            $analyses[] = $this->import->analyzeTable($tables[0]);

            /**
             * string $db_name (no backquotes)
             *
             * array $table = array(table_name, array() column_names, array()() rows)
             * array $tables = array of "$table"s
             *
             * array $analysis = array(array() column_types, array() column_sizes)
             * array $analyses = array of "$analysis"s
             *
             * array $create = array of SQL strings
             *
             * array $options = an associative array of options
             */

            /* Set database name to the currently selected one, if applicable,
             * Otherwise, check if user provided the database name in the request,
             * if not, set the default name
             */
            if (isset($_REQUEST['csv_new_db_name'])
                && strlen($_REQUEST['csv_new_db_name']) > 0
            ) {
                $newDb = $_REQUEST['csv_new_db_name'];
            } else {
                $result = $GLOBALS['dbi']->fetchResult('SHOW DATABASES');
                if (! is_array($result)) {
                    $result = [];
                }
                $newDb = 'CSV_DB ' . (count($result) + 1);
            }
            list($db_name, $options) = $this->getDbnameAndOptions($db, $newDb);

            /* Non-applicable parameters */
            $create = null;

            /* Created and execute necessary SQL statements from data */
            $this->import->buildSql($db_name, $tables, $analyses, $create, $options, $sql_data);

            unset($tables);
            unset($analyses);
        }

        // Commit any possible data in buffers
        $this->import->runQuery('', '', $sql_data);

        if (count($values) != 0 && ! $error) {
            $message = Message::error(
                __('Invalid format of CSV input on line %d.')
            );
            $message->addParam($line);
            $error = true;
        }
    }

    /**
     * Read the expected column_separated_with String of length
     * $csv_terminated_len from the $buffer
     * into variable $ch and return the read string $ch
     *
     * @param string $buffer             The original string buffer read from
     *                                   csv file
     * @param string $ch                 Partially read "column Separated with"
     *                                   string, also used to return after
     *                                   reading length equal $csv_terminated_len
     * @param int    $i                  Current read counter of buffer string
     * @param int    $csv_terminated_len The length of "column separated with"
     *                                   String
     *
     * @return string
     */
    public function readCsvTerminatedString($buffer, $ch, $i, $csv_terminated_len)
    {
        for ($j = 0; $j < $csv_terminated_len - 1; $j++) {
            $i++;
            $ch .= mb_substr($buffer, $i, 1);
        }

        return $ch;
    }
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns true if the table should be analyzed, false otherwise
     *
     * @return bool
     */
    private function _getAnalyze()
    {
        return $this->_analyze;
    }

    /**
     * Sets to true if the table should be analyzed, false otherwise
     *
     * @param bool $analyze status
     *
     * @return void
     */
    private function _setAnalyze($analyze)
    {
        $this->_analyze = $analyze;
    }
}
