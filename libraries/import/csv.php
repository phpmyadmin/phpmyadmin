<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV import plugin for phpMyAdmin
 *
 * @todo    add an option for handling NULL values
 * @package PhpMyAdmin-Import
 * @subpackage CSV
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$analyze = false;

if ($plugin_param !== 'table') {
    $analyze = true;
}

if (isset($plugin_list)) {
    $plugin_list['csv'] = array(
        'text' => __('CSV'),
        'extension' => 'csv',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'bool', 'name' => 'replace', 'text' => __('Replace table data with file')),
            array('type' => 'bool', 'name' => 'ignore', 'text' => __('Do not abort on INSERT error')),
            array('type' => 'text', 'name' => 'terminated', 'text' => __('Columns separated with:'), 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'enclosed', 'text' => __('Columns enclosed with:'), 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'escaped', 'text' => __('Columns escaped with:'), 'size' => 2, 'len' => 2),
            array('type' => 'text', 'name' => 'new_line', 'text' => __('Lines terminated with:'), 'size' => 2),
            ),
        'options_text' => __('Options'),
        );

    if ($plugin_param !== 'table') {
        $plugin_list['csv']['options'][]
            = array('type' => 'bool', 'name' => 'col_names', 'text' => __('The first line of the file contains the table column names <i>(if this is unchecked, the first line will become part of the data)</i>'));
    } else {
        $hint = new PMA_Message(__('If the data in each row of the file is not in the same order as in the database, list the corresponding column names here. Column names must be separated by commas and not enclosed in quotations.'));
        $plugin_list['csv']['options'][]
            = array('type' => 'text', 'name' => 'columns', 'text' => __('Column names: ') . PMA_showHint($hint));
    }
    $plugin_list['csv']['options'][] = array('type' => 'end_group');

    /* We do not define function when plugin is just queried for information above */
    return;
}

$replacements = array(
    '\\n'   => "\n",
    '\\t'   => "\t",
    '\\r'   => "\r",
    );
$csv_terminated = strtr($csv_terminated, $replacements);
$csv_enclosed = strtr($csv_enclosed,  $replacements);
$csv_escaped = strtr($csv_escaped, $replacements);
$csv_new_line = strtr($csv_new_line, $replacements);

$param_error = false;
if (strlen($csv_terminated) != 1) {
    $message = PMA_Message::error(__('Invalid parameter for CSV import: %s'));
    $message->addParam(__('Columns terminated by'), false);
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
} elseif (strlen($csv_enclosed) > 1) {
    $message = PMA_Message::error(__('Invalid parameter for CSV import: %s'));
    $message->addParam(__('Columns enclosed by'), false);
    $error = true;
    $param_error = true;
} elseif (strlen($csv_escaped) != 1) {
    $message = PMA_Message::error(__('Invalid parameter for CSV import: %s'));
    $message->addParam(__('Columns escaped by'), false);
    $error = true;
    $param_error = true;
} elseif (strlen($csv_new_line) != 1 && $csv_new_line != 'auto') {
    $message = PMA_Message::error(__('Invalid parameter for CSV import: %s'));
    $message->addParam(__('Lines terminated by'), false);
    $error = true;
    $param_error = true;
}

// If there is an error in the parameters entered, indicate that immediately.
if ($param_error) {
    PMA_mysqlDie($message->getMessage(), '', '', $err_url);
}

$buffer = '';
$required_fields = 0;

if (!$analyze) {
    if (isset($csv_replace)) {
        $sql_template = 'REPLACE';
    } else {
        $sql_template = 'INSERT';
        if (isset($csv_ignore)) {
            $sql_template .= ' IGNORE';
        }
    }
    $sql_template .= ' INTO ' . PMA_backquote($table);

    $tmp_fields = PMA_DBI_get_columns($db, $table);

    if (empty($csv_columns)) {
        $fields = $tmp_fields;
    } else {
        $sql_template .= ' (';
        $fields = array();
        $tmp   = preg_split('/,( ?)/', $csv_columns);
        foreach ($tmp as $key => $val) {
            if (count($fields) > 0) {
                $sql_template .= ', ';
            }
            /* Trim also `, if user already included backquoted fields */
            $val = trim($val, " \t\r\n\0\x0B`");
            $found = false;
            foreach ($tmp_fields as $id => $field) {
                if ($field['Field'] == $val) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $message = PMA_Message::error(__('Invalid column (%s) specified! Ensure that columns names are spelled correctly, separated by commas, and not enclosed in quotes.'));
                $message->addParam($val);
                $error = true;
                break;
            }
            $fields[] = $field;
            $sql_template .= PMA_backquote($val);
        }
        $sql_template .= ') ';
    }

    $required_fields = count($fields);

    $sql_template .= ' VALUES (';
}

// Defaults for parser
$i = 0;
$len = 0;
$line = 1;
$lasti = -1;
$values = array();
$csv_finish = false;

$tempRow = array();
$rows = array();
$col_names = array();
$tables = array();

$col_count = 0;
$max_cols = 0;

while (!($finished && $i >= $len) && !$error && !$timeout_passed) {
    $data = PMA_importGetNextChunk();
    if ($data === false) {
        // subtract data we didn't handle yet and stop processing
        $offset -= strlen($buffer);
        break;
    } elseif ($data === true) {
        // Handle rest of buffer
    } else {
        // Append new data to buffer
        $buffer .= $data;
        unset($data);
        // Do not parse string when we're not at the end and don't have new line inside
        if (($csv_new_line == 'auto' && strpos($buffer, "\r") === false && strpos($buffer, "\n") === false)
            || ($csv_new_line != 'auto' && strpos($buffer, $csv_new_line) === false)
        ) {
            continue;
        }
    }

    // Current length of our buffer
    $len = strlen($buffer);
    // Currently parsed char
    $ch = $buffer[$i];
    while ($i < $len) {
        // Deadlock protection
        if ($lasti == $i && $lastlen == $len) {
            $message = PMA_Message::error(__('Invalid format of CSV input on line %d.'));
            $message->addParam($line);
            $error = true;
            break;
        }
        $lasti = $i;
        $lastlen = $len;

        // This can happen with auto EOL and \r at the end of buffer
        if (!$csv_finish) {
            // Grab empty field
            if ($ch == $csv_terminated) {
                if ($i == $len - 1) {
                    break;
                }
                $values[] = '';
                $i++;
                $ch = $buffer[$i];
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
                $ch = $buffer[$i];
            } else {
                $need_end = false;
            }
            $fail = false;
            $value = '';
            while (($need_end && ($ch != $csv_enclosed || $csv_enclosed == $csv_escaped))
             || (!$need_end && !($ch == $csv_terminated
               || $ch == $csv_new_line || ($csv_new_line == 'auto'
                && ($ch == "\r" || $ch == "\n"))))) {
                if ($ch == $csv_escaped) {
                    if ($i == $len - 1) {
                        $fail = true;
                        break;
                    }
                    $i++;
                    $ch = $buffer[$i];
                    if ($csv_enclosed == $csv_escaped && ($ch == $csv_terminated
                     || $ch == $csv_new_line || ($csv_new_line == 'auto'
                      && ($ch == "\r" || $ch == "\n")))) {
                        break;
                    }
                }
                $value .= $ch;
                if ($i == $len - 1) {
                    if (!$finished) {
                        $fail = true;
                    }
                    break;
                }
                $i++;
                $ch = $buffer[$i];
            }

            // unquoted NULL string
            if (false === $need_end && $value === 'NULL') {
                $value = null;
            }

            if ($fail) {
                $i = $fallbacki;
                $ch = $buffer[$i];
                break;
            }
            // Need to strip trailing enclosing char?
            if ($need_end && $ch == $csv_enclosed) {
                if ($finished && $i == $len - 1) {
                    $ch = null;
                } elseif ($i == $len - 1) {
                    $i = $fallbacki;
                    $ch = $buffer[$i];
                    break;
                } else {
                    $i++;
                    $ch = $buffer[$i];
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
                    $ch = $buffer[$i];
                    break;
                }
                $i++;
                $ch = $buffer[$i];
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
                if ($i >= ($len - 2) && !$finished) {
                    break; // We need more data to decide new line
                }
                if ($buffer[$i + 1] == "\n") {
                    $i++;
                }
            }
            // We didn't parse value till the end of line, so there was empty one
            if (!$csv_finish) {
                $values[] = '';
            }

            if ($analyze) {
                foreach ($values as $ley => $val) {
                    $tempRow[] = $val;
                    ++$col_count;
                }

                if ($col_count > $max_cols) {
                    $max_cols = $col_count;
                }
                $col_count = 0;

                $rows[] = $tempRow;
                $tempRow = array();
            } else {
                // Do we have correct count of values?
                if (count($values) != $required_fields) {

                    // Hack for excel
                    if ($values[count($values) - 1] == ';') {
                        unset($values[count($values) - 1]);
                    } else {
                        $message = PMA_Message::error(__('Invalid column count in CSV input on line %d.'));
                        $message->addParam($line);
                        $error = true;
                        break;
                    }
                }

                $first = true;
                $sql = $sql_template;
                foreach ($values as $key => $val) {
                    if (!$first) {
                        $sql .= ', ';
                    }
                    if ($val === null) {
                        $sql .= 'NULL';
                    } else {
                        $sql .= '\'' . PMA_sqlAddSlashes($val) . '\'';
                    }

                    $first = false;
                }
                $sql .= ')';

                /**
                 * @todo maybe we could add original line to verbose SQL in comment
                 */
                PMA_importRunQuery($sql, $sql);
            }

            $line++;
            $csv_finish = false;
            $values = array();
            $buffer = substr($buffer, $i + 1);
            $len = strlen($buffer);
            $i = 0;
            $lasti = -1;
            $ch = $buffer[0];
        }
    } // End of parser loop
} // End of import loop

if ($analyze) {
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
    }

    if ((isset($col_names) && count($col_names) != $max_cols)
        || ! isset($col_names)
    ) {
        // Fill out column names
        for ($i = 0; $i < $max_cols; ++$i) {
            $col_names[] = 'COL '.($i+1);
        }
    }

    if (strlen($db)) {
        $result = PMA_DBI_fetch_result('SHOW TABLES');
        $tbl_name = 'TABLE '.(count($result) + 1);
    } else {
        $tbl_name = 'TBL_NAME';
    }

    $tables[] = array($tbl_name, $col_names, $rows);

    /* Obtain the best-fit MySQL types for each column */
    $analyses = array();
    $analyses[] = PMA_analyzeTable($tables[0]);

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

    /* Set database name to the currently selected one, if applicable */
    if (strlen($db)) {
        $db_name = $db;
        $options = array('create_db' => false);
    } else {
        $db_name = 'CSV_DB';
        $options = null;
    }

    /* Non-applicable parameters */
    $create = null;

    /* Created and execute necessary SQL statements from data */
    PMA_buildSQL($db_name, $tables, $analyses, $create, $options);

    unset($tables);
    unset($analyses);
}

// Commit any possible data in buffers
PMA_importRunQuery();

if (count($values) != 0 && !$error) {
    $message = PMA_Message::error(__('Invalid format of CSV input on line %d.'));
    $message->addParam($line);
    $error = true;
}
?>
