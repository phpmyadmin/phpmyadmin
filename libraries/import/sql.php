<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/* SQL import plugin for phpMyAdmin */

if (isset($plugin_list)) {
    $plugin_list['sql'] = array(
        'text' => 'strSQL',
        'extension' => 'sql',
        'options_text' => 'strSQLImportOptions',
        );
    $compats = PMA_DBI_getCompatibilities();
    if (count($compats) > 0) {
        $values = array();
        foreach($compats as $val) {
            $values[$val] = $val;
        }
        $plugin_list['sql']['options'] = array(
            array('type' => 'select', 'name' => 'compatibility', 'text' => 'strSQLCompatibility', 'values' => $values, 'doc' => array('manual_MySQL_Database_Administration', 'Server_SQL_mode'))
            );
    }
} else {
/* We do not define function when plugin is just queried for information above */
    $buffer = '';
    // Defaults for parser
    $sql = '';
    $start_pos = 0;
    $i = 0;
    // Handle compatibility option
    if (isset($_REQUEST['sql_compatibility'])) {
        PMA_DBI_try_query('SET SQL_MODE="' . $_REQUEST['sql_compatibility'] . '"');
    }
    while (!($finished && $i >= $len) && !$error && !$timeout_passed) {
        $data = PMA_importGetNextChunk();
        if ($data === FALSE) {
            // subtract data we didn't handle yet and stop processing
            $offset -= strlen($buffer);
            break;
        } elseif ($data === TRUE) {
            // Handle rest of buffer
        } else {
            // Append new data to buffer
            $buffer .= $data;
            // Do not parse string when we're not at the end and don't have ; inside
            if ((strpos($buffer, ';') === FALSE) && !$finished) {
                continue;
            }
        }
        // Current length of our buffer
        $len = strlen($buffer);
        // Grab some SQL queries out of it
        while ($i < $len) {
            // Find first interesting character, several strpos seem to be faster than simple loop in php:
            //while (($i < $len) && (strpos('\'";#-/', $buffer[$i]) === FALSE)) $i++;
            //if ($i == $len) break;
            $oi = $i;
            $p1 = strpos($buffer, '\'', $i);
            if ($p1 === FALSE) {
                $p1 = 2147483647;
            }
            $p2 = strpos($buffer, '"', $i);
            if ($p2 === FALSE) {
                $p2 = 2147483647;
            }
            $p3 = strpos($buffer, ';', $i);
            if ($p3 === FALSE) {
                $p3 = 2147483647;
            }
            $p4 = strpos($buffer, '#', $i);
            if ($p4 === FALSE) {
                $p4 = 2147483647;
            }
            $p5 = strpos($buffer, '--', $i);
            if ($p5 === FALSE || $p5 >= ($len - 2) || $buffer[$p5 + 2] > ' ') {
                $p5 = 2147483647;
            }
            $p6 = strpos($buffer, '/*', $i);
            if ($p6 === FALSE) {
                $p6 = 2147483647;
            }
            $p7 = strpos($buffer, '`', $i);
            if ($p7 === FALSE) {
                $p7 = 2147483647;
            }
            $i = min ($p1, $p2, $p3, $p4, $p5, $p6, $p7);
            if ($i == 2147483647) {
                $i = $oi;
                if (!$finished) {
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
            if (!(strpos('\'"`', $ch) === FALSE)) {
                $quote = $ch;
                $endq = FALSE;
                while (!$endq) {
                    // Find next quote
                    $pos = strpos($buffer, $quote, $i + 1);
                    // No quote? Too short string
                    if ($pos === FALSE) {
                        // We hit end of string => unclosed quote, but we handle it as end of query
                        if ($finished) {
                            $endq = TRUE;
                            $i = $len - 1;
                        }
                        break;
                    }
                    // Was not the quote escaped?
                    $j = $pos - 1;
                    while ($buffer[$j] == '\\') $j--;
                    // Even count means it was not escaped
                    $endq = (((($pos - 1) - $j) % 2) == 0);
                    // Skip the string
                    $i = $pos;
                }
                if (!$endq) {
                    break;
                }
                $i++;
                // Aren't we at the end?
                if ($finished && $i == $len) {
                    $i--;
                } else {
                    continue;
                }
            }

            // Not enough data to decide
            if ((($i == ($len - 1) && ($ch == '-' || $ch == '/'))
                || ($i == ($len - 2) && (($ch == '-' && $buffer[$i + 1] == '-') || ($ch == '/' && $buffer[$i + 1] == '*')))
                ) && !$finished) {
                break;
            }

            // Comments
            if ($ch == '#'
                    || ($i < ($len - 1) && $ch == '-' && $buffer[$i + 1] == '-' && (($i < ($len - 2) && $buffer[$i + 2] <= ' ') || ($i == ($len - 1) && $finished)))
                    || ($i < ($len - 1) && $ch == '/' && $buffer[$i + 1] == '*')
                    ) {
                // Copy current string to SQL
                if ($start_pos != $i) {
                    $sql .= substr($buffer, $start_pos, $i - $start_pos);
                }
                // Skip the rest
                $j = $i;
                $i = strpos($buffer, $ch == '/' ? '*/' : "\n", $i);
                // didn't we hit end of string?
                if ($i === FALSE) {
                    if ($finished) {
                        $i = $len - 1;
                    } else {
                        break;
                    }
                }
                // Skip *
                if ($ch == '/') {
                    // Check for MySQL conditional comments and include them as-is
                    if ($buffer[$j + 2] == '!') {
                        $comment = substr($buffer, $j + 3, $i - $j - 3);
                        if (preg_match('/^[0-9]{5}/', $comment, $version)) {
                            if ($version[0] <= PMA_MYSQL_INT_VERSION) {
                                $sql .= substr($comment, 5);
                            }
                        } else {
                            $sql .= $comment;
                        }
                    }
                    $i++;
                }
                // Skip last char
                $i++;
                // Next query part will start here
                $start_pos = $i;
                // Aren't we at the end?
                if ($i == $len) {
                    $i--;
                } else {
                    continue;
                }
            }

            // End of SQL
            if ($ch == ';' || ($finished && ($i == $len - 1))) {
                $tmp_sql = $sql;
                if ($start_pos < $len) {
                    $tmp_sql .= substr($buffer, $start_pos, $i - $start_pos + 1);
                }
                // Do not try to execute empty SQL
                if (!preg_match('/^([\s]*;)*$/', trim($tmp_sql))) {
                    $sql = $tmp_sql;
                    PMA_importRunQuery($sql, substr($buffer, 0, $i + 1));
                    $buffer = substr($buffer, $i + 1);
                    // Reset parser:
                    $len = strlen($buffer);
                    $sql = '';
                    $i = 0;
                    $start_pos = 0;
                    // Any chance we will get a complete query?
                    if ((strpos($buffer, ';') === FALSE) && !$finished) {
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
}
?>
