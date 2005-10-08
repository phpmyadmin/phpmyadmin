<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/* SQL import plugin for phpMyAdmin */

if (isset($import_list)) {
    $import_list['sql'] = array(
        'text' => 'strSQL',
        'extension' => 'sql',
        );
} else {
/* We do not define function when plugin is just queried for information above */
    $buffer = '';
    // Defaults for parser
    $sql = '';
    $start_pos = 0;
    $i = 0;
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
            if ((strpos($buffer, ';') === FALSE) && !$finished) continue;
        }
        // Current length of our buffer
        $len = strlen($buffer);
        // Grab some SQL queries out of it
        while($i < $len) {
            // Find first interesting character, several strpos seem to be faster than simple loop in php:
            //while(($i < $len) && (strpos('\'";#-/', $buffer[$i]) === FALSE)) $i++;
            //if ($i == $len) break;
            $oi = $i;
            $p1 = strpos($buffer, '\'', $i);
            if ($p1 === FALSE) $p1 = 2147483647;
            $p2 = strpos($buffer, '"', $i);
            if ($p2 === FALSE) $p2 = 2147483647;
            $p3 = strpos($buffer, ';', $i);
            if ($p3 === FALSE) $p3 = 2147483647;
            $p4 = strpos($buffer, '#', $i);
            if ($p4 === FALSE) $p4 = 2147483647;
            $p5 = strpos($buffer, '--', $i);
            if ($p5 === FALSE) $p5 = 2147483647;
            $p6 = strpos($buffer, '/*', $i);
            if ($p6 === FALSE) $p6 = 2147483647;
            $p7 = strpos($buffer, '`', $i);
            if ($p7 === FALSE) $p7 = 2147483647;
            $i = min ($p1, $p2, $p3, $p4, $p5, $p6, $p7);
            if ($i == 2147483647) {
                $i = $oi;
                if (!$finished) break;
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
                    if ($pos === FALSE) break;
                    // Was not the quote escaped?
                    $j = $pos - 1;
                    while ($buffer[$j] == '\\') $j--;
                    // Even count means it was not escaped
                    $endq = (((($pos - 1) - $j) % 2) == 0);
                    // Skip the string
                    $i = $pos;
                }
                if (!$endq) break;
                $i++;
                continue;
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
                $i = strpos($buffer, $ch == '/' ? '*/' : "\n", $i);
                // didn't we hit end of string?
                if ($i === FALSE) break;
                // Skip *
                if ($ch == '/') $i++;
                // Skip last char
                $i++;
                // Next query part will start here 
                $start_pos = $i;
            }

            // End of SQL
            if ($ch == ';' || ($finished && ($i == $len - 1))) {
                $sql .= substr($buffer, $start_pos, $i - $start_pos + 1);
                PMA_importRunQuery($sql, substr($buffer, 0, $i + 1));
                $buffer = substr($buffer, $i + 1);
                // Reset parser:
                $len = strlen($buffer);
                $sql = '';
                $i = 0;
                $start_pos = 0;
                // Any chance we will get a complete query?
                if ((strpos($buffer, ';') === FALSE) && !$finished) break;
            }
        } // End of parser loop
    } // End of import loop
    // Commit any possible data in buffers
    PMA_importRunQuery();
}
?>
