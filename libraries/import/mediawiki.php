<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MediaWiki import plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage MediaWiki
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$analyze = false;
if ($plugin_param !== 'table') {
    $analyze = true;
}

if (isset($plugin_list)) {
    $plugin_list['mediawiki'] = array(
        'text' => __('MediaWiki Table'),
        'extension' => 'txt',
        'mime_type' => 'text/plain',
        'options' => array( ),
        'options_text' => __('Options'),
        );

    $plugin_list['mediawiki']['options'][] = array(
        'type' => 'begin_group',
        'name' => 'general_opts',
        );

    // Preferred performance indicator (speed/memory)
    $plugin_list['mediawiki']['options'][] = array(
        'type' => 'begin_subgroup',
        'subgroup_header' => array(
            'type' => 'message_only',
            'text' => __('Choose your preferred import type:')
        ));
    $plugin_list['mediawiki']['options'][] = array(
        'type' => 'radio',
        'name' => 'performance_indicator',
        'values' => array(
            'memory' => __(
                'Memory saving (will import one table at a time).'
                . ' Recommended for a large number of tables.'
            ),
            'speed' => __(
                'Faster, but memory consuming (will save all tables into'
                . ' memory). Recommended for small amounts of data.'
            )
        ));
    $plugin_list['mediawiki']['options'][] = array(
        'type' => 'end_subgroup'
        );

    $plugin_list['mediawiki']['options'][] = array(
        'type' => 'end_group'
        );

    // We do not define function when plugin is just
    // queried for information above
    return;
}

// Defaults for parser

// The buffer that will be used to store chunks read from the imported file
$buffer = '';

// Used as storage for the last part of the current chunk data
// Will be appended to the first line of the next chunk, if there is one
$last_chunk_line = '';

// Remembers whether the current buffer line is part of a comment
$inside_comment = false;
// Remembers whether the current buffer line is part of a data comment
$inside_data_comment = false;
// Remembers whether the current buffer line is part of a structure comment
$inside_structure_comment = false;

// MediaWiki only accepts "\n" as row terminator
$mediawiki_new_line = "\n";

// Initialize the name of the current table
$cur_table_name = "";

// If the user chooses a faster, but more memory consuming import,
// all table information will be stored in an array.
if ($GLOBALS['mediawiki_performance_indicator'] === "speed") {
    /**
     * Array containing all table info.
     * It has a structure that facilitates its use in PMA_buildSQL():
     *
     * $all_tables[$i][0] - string containing table name
     * $all_tables[$i][1] - array[]   of table headers
     * $all_tables[$i][2] - array[][] of table content rows
     */
    $all_tables = array();

    // Increase the memory limit to at least 128MB
    ini_set('memory_limit', '128M');
}

while (! $finished && ! $error && ! $timeout_passed ) {
    $data = PMA_importGetNextChunk();

    if ($data === false) {
        // Subtract data we didn't handle yet and stop processing
        $offset -= strlen($buffer);
        break;
    } elseif ($data === true) {
        // Handle rest of buffer
    } else {
        // Append new data to buffer
        $buffer = $data;
        unset($data);
        // Don't parse string if we're not at the end
        // and don't have a new line inside
        if ( strpos($buffer, $mediawiki_new_line) === false ) {
            continue;
        }
    }

    // Because of reading chunk by chunk, the first line from the buffer
    // contains only a portion of an actual line from the imported file.
    // Therefore, we have to append it to the last line from the previous
    // chunk. If we are at the first chunk, $last_chunk_line should be empty.
    $buffer = $last_chunk_line . $buffer;

    // Process the buffer line by line
    $buffer_lines = explode($mediawiki_new_line, $buffer);

    $full_buffer_lines_count = count($buffer_lines);
    // If the reading is not finalised, the final line of the current chunk
    // will not be complete
    if (! $finished) {
        $full_buffer_lines_count -= 1;
        $last_chunk_line = $buffer_lines[$full_buffer_lines_count];
    }

    for ($line_nr = 0; $line_nr < $full_buffer_lines_count; ++ $line_nr) {
        $cur_buffer_line = trim($buffer_lines[$line_nr]);

        // If the line is empty, go to the next one
        if ( $cur_buffer_line === '' ) {
            continue;
        }

        $first_character = $cur_buffer_line[0];
        $matches = array();

        // Check beginnning of comment
        if (! strcmp(substr($cur_buffer_line, 0, 4), "<!--")) {
            $inside_comment = true;
            continue;
        } elseif ($inside_comment) {
            // Check end of comment
            if (! strcmp(substr($cur_buffer_line, 0, 4), "-->")) {
                // Only data comments are closed. The structure comments will
                // be closed when a data comment begins (in order to skip
                // structure tables)
                if ($inside_data_comment) {
                    $inside_data_comment = false;
                }

                // End comments that are not related to table structure
                if (! $inside_structure_comment) {
                    $inside_comment = false;
                }
            } else {
                // Check table name
                $match_table_name = array();
                if (preg_match(
                    "/^Table data for `(.*)`$/",
                    $cur_buffer_line,
                    $match_table_name
                )
                ) {
                    $cur_table_name = $match_table_name[1];
                    $inside_data_comment = true;

                    // End ignoring structure rows
                    if ($inside_structure_comment) {
                        $inside_structure_comment = false;
                    }
                } elseif (preg_match(
                    "/^Table structure for `(.*)`$/",
                    $cur_buffer_line,
                    $match_table_name
                )
                ) {
                    // The structure comments will be ignored
                    $inside_structure_comment = true;
                }
            }
            continue;
        } elseif (preg_match('/^\{\|(.*)$/', $cur_buffer_line, $matches)) {
            // Check start of table

            // This will store all the column info on all rows from
            // the current table read from the buffer
            $cur_temp_table = array();

            // Will be used as storage for the current row in the buffer
            // Once all its columns are read, it will be added to
            // $cur_temp_table and then it will be emptied
            $cur_temp_line = array();

            // Helps us differentiate the header columns
            // from the normal columns
            $in_table_header = false;
            // End processing because the current line does not
            // contain any column information
        } elseif (substr($cur_buffer_line, 0, 2) === '|-'
              || substr($cur_buffer_line, 0, 2) === '|+'
              || substr($cur_buffer_line, 0, 2) === '|}'
        ) {
            // Check begin row or end table

            // Add current line to the values storage
            if (! empty($cur_temp_line)) {
                // If the current line contains header cells ( marked with '!' ),
                // it will be marked as table header
                if ( $in_table_header ) {
                    // Set the header columns
                    $cur_temp_table_headers = $cur_temp_line;
                } else {
                    // Normal line, add it to the table
                    $cur_temp_table [] = $cur_temp_line;
                }
            }

            // Empty the temporary buffer
            $cur_temp_line = array();

            // No more processing required at the end of the table
            if (substr($cur_buffer_line, 0, 2) === '|}') {
                $current_table = array(
                    $cur_table_name,
                    $cur_temp_table_headers,
                    $cur_temp_table
                );

                // Decide next action based on chosen import method
                switch ( $GLOBALS['mediawiki_performance_indicator'] ) {
                case "memory":
                    // Import the current table data into the database
                    PMA_importDataOneTable($current_table);
                    break;
                case "speed":
                    // Save the current table in memory and import
                    // all tables` data at the end
                    $tbl_nr = count($all_tables);
                    $all_tables[$tbl_nr] = $current_table;
                    break;
                default:
                    $message = PMA_Message::error(
                        __('Invalid performance indicator selected.')
                    );
                    $error = true;
                    break;
                }

                // Reset table name
                $cur_table_name = "";
            }
            // What's after the row tag is now only attributes

        } elseif (($first_character === '|') || ($first_character === '!')) {
            // Check cell elements

            // Header cells
            if ($first_character === '!') {
                // Mark as table header, but treat as normal row
                $cur_buffer_line = str_replace('!!', '||', $cur_buffer_line);
                // Will be used to set $cur_temp_line as table header
                $in_table_header = true;
            } else {
                $in_table_header = false;
            }

            // Loop through each table cell
            $cells = PMA_explodeMarkup('||', $cur_buffer_line);
            foreach ($cells as $cell) {
                // A cell could contain both parameters and data
                $cell_data = explode('|', $cell, 2);

                // A '|' inside an invalid link should not
                // be mistaken as delimiting cell parameters
                if (strpos($cell_data[0], '[[') === true ) {
                    if (count($cell_data) == 1) {
                        $cell = $cell_data[0];
                    } else {
                        $cell = $cell_data[1];
                    }
                }

                // Delete the beginning of the column, if there is one
                $cell = trim($cell);
                $col_start_chars = array( "|", "!");
                foreach ($col_start_chars as $col_start_char) {
                    if (strpos($cell, $col_start_char) === 0) {
                        $cell = trim(substr($cell, 1));
                    }
                }

                // Add the cell to the row
                $cur_temp_line [] = $cell;
            } // foreach $cells
        } else {
            // If it's none of the above, then the current line has a bad format
            $message = PMA_Message::error(
                __('Invalid format of mediawiki input on line: <br />%s.')
            );
            $message->addParam($cur_buffer_line);
            $error = true;
        }
    } // End treating full buffer lines
} // while - finished parsing buffer

// If the user selected a faster import, all the tables are now
// stored in the memory
if ($GLOBALS['mediawiki_performance_indicator'] === "speed") {
    PMA_importDataAllTables($all_tables);
}

/**
 * Imports data from an array containing all the tables. Should only be
 * called after all processing of $all_tables has been finished, because
 * it modifies its contents
 *
 * @param array &$all_tables containing all tables info:
 *        <code>
 *            $all_tables[$i][0] - string containing table name
 *            $all_tables[$i][1] - array[]   of table headers
 *            $all_tables[$i][2] - array[][] of table content rows
 *        </code>
 *
 * @global bool $analyze whether to scan for column types
 *
 * @return void
 */
function PMA_importDataAllTables (&$all_tables)
{
    global $all_tables, $analyze;
    $analyses = array();

    if ($analyze) {
        for ($cur_tbl = 0; $cur_tbl < count($all_tables); ++ $cur_tbl) {
            // Set the table name
            PMA_setTableName($all_tables[$cur_tbl][0]);

            // Set generic names for table headers if they don't exist
            PMA_setTableHeaders(
                $all_tables[$cur_tbl][1],
                $all_tables[$cur_tbl][2][0]
            );

            // Obtain the best-fit MySQL types for each column
            $analyses [] = PMA_analyzeTable($all_tables[$cur_tbl]);
        } // end for

        PMA_executeImportTables($all_tables, $analyses);
    }

    // Commit any possible data in buffers
    PMA_importRunQuery();
}

/**
 * Imports data from a single table
 *
 * @param array $table containing all table info:
 *        <code>
 *            $all_tables[0] - string containing table name
 *            $all_tables[1] - array[]   of table headers
 *            $all_tables[2] - array[][] of table content rows
 *        </code>
 *
 * @global bool  $analyze whether to scan for column types
 *
 * @return void
 */
function PMA_importDataOneTable ($table)
{
    global $analyze;
    if ($analyze) {
        // Set the table name
        PMA_setTableName($table[0]);

        // Set generic names for table headers if they don't exist
        PMA_setTableHeaders($table[1], $table[2][0]);

        // Create the tables array to be used in PMA_buildSQL()
        $tables = array();
        $tables [] = array($table[0], $table[1], $table[2]);

        // Obtain the best-fit MySQL types for each column
        $analyses = array();
        $analyses [] = PMA_analyzeTable($tables[0]);

        PMA_executeImportTables($tables, $analyses);
    }

    // Commit any possible data in buffers
    PMA_importRunQuery();
}

/**
 * Sets the table name
 *
 * @param string &$table_name reference to the name of the table
 *
 * @return void
 */
function PMA_setTableName(&$table_name)
{
    if (empty($table_name) && strlen($db)) {
        $result = PMA_DBI_fetch_result('SHOW TABLES');
        // todo check if the name below already exists
        $table_name = 'TABLE '.(count($result) + 1);
    }
}

/**
 * Set generic names for table headers, if they don't exist
 *
 * @param array &$table_headers reference to the array containing the headers
 *                              of a table
 * @param array $table_row      array containing the first content row
 *
 * @return void
 */
function PMA_setTableHeaders(&$table_headers, $table_row)
{
    if ( empty($table_headers) ) {
        // The first table row should contain the number of columns
        // If they are not set, generic names will be given (COL 1, COL 2, etc)
        $num_cols = count($table_row);
        for ($i = 0; $i < $num_cols; ++ $i) {
            $table_headers [$i] = 'COL '. ($i + 1);
        }
    }
}

/**
 * Sets the database name and additional options and calls PMA_buildSQL()
 * Used in PMA_importDataAllTables() and PMA_importDataOneTable()
 *
 * @param array &$tables   structure:
 *              array(
 *                  array(table_name, array() column_names, array()() rows)
 *              )
 * @param array &$analyses structure:
 *              $analyses = array(
 *                  array(array() column_types, array() column_sizes)
 *              )
 *
 * @global string $db name of the database to import in
 *
 * @return void
 */
function PMA_executeImportTables(&$tables, &$analyses)
{
    global $db;

    // $db_name : The currently selected database name, if applicable
    //            No backquotes
    // $options : An associative array of options
    if (strlen($db)) {
        $db_name = $db;
        $options = array('create_db' => false);
    } else {
        $db_name = 'mediawiki_DB';
        $options = null;
    }

    // Array of SQL strings
    // Non-applicable parameters
    $create = null;

    // Create and execute necessary SQL statements from data
    PMA_buildSQL($db_name, $tables, $analyses, $create, $options);

    unset($tables);
    unset($analyses);
}

/**
 * Perform an operation equivalent to
 *
 * <code>
 *     preg_replace("!$start_delim(.*?)$end_delim!", $replace, $subject);
 * </code>
 *
 * except that it's worst-case O(N) instead of O(N^2)
 *
 * This implementation is fast but memory-hungry, so that it is better
 * used on small chunks of text.
 *
 * @param string $start_delim start delimiter
 * @param string $end_delim   end delimiter
 * @param string $separator   separator
 * @param string $replace     the string to be replaced with
 * @param string $subject     the text to be replaced
 *
 * @return string
 */
function PMA_delimiterReplace($start_delim, $end_delim,
    $separator, $replace, $subject
) {
    $segments = explode($start_delim, $subject);
    $output = array_shift($segments);

    foreach ($segments as $s) {
        $end_delim_pos = strpos($s, $end_delim);
        if ($end_delim_pos === false) {
            $output .= $start_delim . $s;
        } else {
            $replacement = substr($s, 0, $end_delim_pos);
            $output .= $start_delim
                . str_replace($separator, $replace, $replacement)
                . substr($s, $end_delim_pos + strlen($end_delim) -1);
        }
    }

    return $output;
}

/**
 * More or less "markup-safe" explode()
 * Ignores any instances of the separator inside <...>
 * Used in parsing buffer lines containing data cells
 *
 * @param string $separator separator
 * @param string $text      text to be split
 *
 * @return array
 */
function PMA_explodeMarkup($separator, $text)
{
    $placeholder = "\x00";

    // Remove placeholder instances
    $text = str_replace($placeholder, '', $text);

    // Replace instances of the separator inside HTML-like
    // tags with the placeholder
    $cleaned = PMA_delimiterReplace('<', '>', $separator, $placeholder, $text);

    // Explode, then put the replaced separators back in
    $items = explode($separator, $cleaned);
    foreach ($items as $i => $str) {
        $items[$i] = str_replace($placeholder, $separator, $str);
    }

    return $items;
}
?>