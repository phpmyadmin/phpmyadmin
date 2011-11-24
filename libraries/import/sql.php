<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL import plugin for phpMyAdmin
 *
 * @package PhpMyAdmin-Import
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['sql'] = array(
        'text'          => __('SQL'),
        'extension'     => 'sql',
        'options_text'  => __('Options'),
    );
    $compats = PMA_DBI_getCompatibilities();
    if (count($compats) > 0) {
        $values = array();
        foreach ($compats as $val) {
            $values[$val] = $val;
        }
        $plugin_list['sql']['options'] = array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array(
                'type'      => 'select',
                'name'      => 'compatibility',
                'text'      => __('SQL compatibility mode:'),
                'values'    => $values,
                'doc'       => array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                ),
            ),
            array(
                'type' => 'bool',
                'name' => 'no_auto_value_on_zero',
                'text' => __('Do not use <code>AUTO_INCREMENT</code> for zero values'),
                'doc'       => array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                    'sqlmode_no_auto_value_on_zero'
                ),

            ),
            array('type' => 'end_group'),
        );
    }

    /* We do not define function when plugin is just queried for information above */
    return;
}

$buffer = '';
// Defaults for parser
$sql = '';
$start_pos = 0;
$i = 0;
$len= 0;
$big_value = 2147483647;
$delimiter_keyword = 'DELIMITER '; // include the space because it's mandatory
$length_of_delimiter_keyword = strlen($delimiter_keyword);

if (isset($_POST['sql_delimiter'])) {
    $sql_delimiter = $_POST['sql_delimiter'];
} else {
    $sql_delimiter = ';';
}

// Handle compatibility options
$sql_modes = array();
if (isset($_REQUEST['sql_compatibility']) && 'NONE' != $_REQUEST['sql_compatibility']) {
    $sql_modes[] = $_REQUEST['sql_compatibility'];
}
if (isset($_REQUEST['sql_no_auto_value_on_zero'])) {
    $sql_modes[] = 'NO_AUTO_VALUE_ON_ZERO';
}
if (count($sql_modes) > 0) {
    PMA_DBI_try_query('SET SQL_MODE="' . implode(',', $sql_modes) . '"');
}
unset($sql_modes);

/**
 * will be set in PMA_importGetNextChunk()
 *
 * @global boolean $GLOBALS['finished']
 */
$GLOBALS['finished'] = false;

while (!($GLOBALS['finished'] && $i >= $len) && !$error && !$timeout_passed) {
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
        // free memory
        unset($data);
        // Do not parse string when we're not at the end and don't have ; inside
        if ((strpos($buffer, $sql_delimiter, $i) === false) && !$GLOBALS['finished']) {
            continue;
        }
    }
    // Current length of our buffer
    $len = strlen($buffer);

    // Grab some SQL queries out of it
    while ($i < $len) {
        $found_delimiter = false;
        // Find first interesting character
        $old_i = $i;
        // this is about 7 times faster that looking for each sequence i
        // one by one with strpos()
        if (preg_match('/(\'|"|#|-- |\/\*|`|(?i)(?<![A-Z0-9_])' . $delimiter_keyword . ')/', $buffer, $matches, PREG_OFFSET_CAPTURE, $i)) {
            // in $matches, index 0 contains the match for the complete
            // expression but we don't use it
            $first_position = $matches[1][1];
        } else {
            $first_position = $big_value;
        }
        /**
         * @todo we should not look for a delimiter that might be
         *       inside quotes (or even double-quotes)
         */
        // the cost of doing this one with preg_match() would be too high
        $first_sql_delimiter = strpos($buffer, $sql_delimiter, $i);
        if ($first_sql_delimiter === false) {
            $first_sql_delimiter = $big_value;
        } else {
            $found_delimiter = true;
        }

        // set $i to the position of the first quote, comment.start or delimiter found
        $i = min($first_position, $first_sql_delimiter);

        if ($i == $big_value) {
            // none of the above was found in the string

            $i = $old_i;
            if (!$GLOBALS['finished']) {
                break;
            }
            // at the end there might be some whitespace...
            if (trim($buffer) == '') {
                $buffer = '';
                $len = 0;
                break;
            }
            // We hit end of query, go there!
            $i = strlen($buffer) - 1;
        }

        // Grab current character
        $ch = $buffer[$i];

        // Quotes
        if (strpos('\'"`', $ch) !== false) {
            $quote = $ch;
            $endq = false;
            while (!$endq) {
                // Find next quote
                $pos = strpos($buffer, $quote, $i + 1);
                /*
                 * Behave same as MySQL and accept end of query as end of backtick.
                 * I know this is sick, but MySQL behaves like this:
                 *
                 * SELECT * FROM `table
                 *
                 * is treated like
                 *
                 * SELECT * FROM `table`
                 */
                if ($pos === false && $quote == '`' && $found_delimiter) {
                    $pos = $first_sql_delimiter - 1;
                // No quote? Too short string
                } elseif ($pos === false) {
                    // We hit end of string => unclosed quote, but we handle it as end of query
                    if ($GLOBALS['finished']) {
                        $endq = true;
                        $i = $len - 1;
                    }
                    $found_delimiter = false;
                    break;
                }
                // Was not the quote escaped?
                $j = $pos - 1;
                while ($buffer[$j] == '\\') $j--;
                // Even count means it was not escaped
                $endq = (((($pos - 1) - $j) % 2) == 0);
                // Skip the string
                $i = $pos;

                if ($first_sql_delimiter < $pos) {
                    $found_delimiter = false;
                }
            }
            if (!$endq) {
                break;
            }
            $i++;
            // Aren't we at the end?
            if ($GLOBALS['finished'] && $i == $len) {
                $i--;
            } else {
                continue;
            }
        }

        // Not enough data to decide
        if ((($i == ($len - 1) && ($ch == '-' || $ch == '/'))
          || ($i == ($len - 2) && (($ch == '-' && $buffer[$i + 1] == '-')
            || ($ch == '/' && $buffer[$i + 1] == '*')))) && !$GLOBALS['finished']) {
            break;
        }

        // Comments
        if ($ch == '#'
         || ($i < ($len - 1) && $ch == '-' && $buffer[$i + 1] == '-'
          && (($i < ($len - 2) && $buffer[$i + 2] <= ' ')
           || ($i == ($len - 1)  && $GLOBALS['finished'])))
         || ($i < ($len - 1) && $ch == '/' && $buffer[$i + 1] == '*')
                ) {
            // Copy current string to SQL
            if ($start_pos != $i) {
                $sql .= substr($buffer, $start_pos, $i - $start_pos);
            }
            // Skip the rest
            $start_of_comment = $i;
            // do not use PHP_EOL here instead of "\n", because the export
            // file might have been produced on a different system
            $i = strpos($buffer, $ch == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === false) {
                if ($GLOBALS['finished']) {
                    $i = $len - 1;
                } else {
                    break;
                }
            }
            // Skip *
            if ($ch == '/') {
                $i++;
            }
            // Skip last char
            $i++;
            // We need to send the comment part in case we are defining
            // a procedure or function and comments in it are valuable
            $sql .= substr($buffer, $start_of_comment, $i - $start_of_comment);
            // Next query part will start here
            $start_pos = $i;
            // Aren't we at the end?
            if ($i == $len) {
                $i--;
            } else {
                continue;
            }
        }
        // Change delimiter, if redefined, and skip it (don't send to server!)
        if (strtoupper(substr($buffer, $i, $length_of_delimiter_keyword)) == $delimiter_keyword
         && ($i + $length_of_delimiter_keyword < $len)) {
             // look for EOL on the character immediately after 'DELIMITER '
             // (see previous comment about PHP_EOL)
           $new_line_pos = strpos($buffer, "\n", $i + $length_of_delimiter_keyword);
           // it might happen that there is no EOL
           if (false === $new_line_pos) {
               $new_line_pos = $len;
           }
           $sql_delimiter = substr($buffer, $i + $length_of_delimiter_keyword, $new_line_pos - $i - $length_of_delimiter_keyword);
           $i = $new_line_pos + 1;
           // Next query part will start here
           $start_pos = $i;
           continue;
        }

        // End of SQL
        if ($found_delimiter || ($GLOBALS['finished'] && ($i == $len - 1))) {
            $tmp_sql = $sql;
            if ($start_pos < $len) {
                $length_to_grab = $i - $start_pos;

                if (! $found_delimiter) {
                    $length_to_grab++;
                }
                $tmp_sql .= substr($buffer, $start_pos, $length_to_grab);
                unset($length_to_grab);
            }
            // Do not try to execute empty SQL
            if (! preg_match('/^([\s]*;)*$/', trim($tmp_sql))) {
                $sql = $tmp_sql;
                PMA_importRunQuery($sql, substr($buffer, 0, $i + strlen($sql_delimiter)));
                $buffer = substr($buffer, $i + strlen($sql_delimiter));
                // Reset parser:
                $len = strlen($buffer);
                $sql = '';
                $i = 0;
                $start_pos = 0;
                // Any chance we will get a complete query?
                //if ((strpos($buffer, ';') === false) && !$GLOBALS['finished']) {
                if ((strpos($buffer, $sql_delimiter) === false) && !$GLOBALS['finished']) {
                    break;
                }
            } else {
                $i++;
                $start_pos = $i;
            }
        }
    } // End of parser loop
} // End of import loop
// Commit any possible data in buffers
PMA_importRunQuery('', substr($buffer, 0, $len));
PMA_importRunQuery();
?>
