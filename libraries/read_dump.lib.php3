<?php
/* $Id$ */


/**
 * Set of functions used to get commands from imported file
 */



if (!defined('__LIB_READ_DUMP__')){
    define('__LIB_READ_DUMP__', 1);

    /**
     * Splits up large sql files into individual queries
     *
     * Last revision: 22 August 2001 - loic1
     *
     * @param   string   the sql commands
     * @param   string   the end of command line delimiter 
     *
     * @return  array    the splitted sql commands
     *
     * @access	public
     */
    function split_sql_file($sql, $delimiter)
    {
        $sql               = trim($sql);
        $char              = '';
        $last_char         = '';
        $ret               = array();
        $string_start      = '';
        $in_string         = FALSE;
        $in_comment        = FALSE;
        $escaped_backslash = FALSE;

        for ($i = 0; $i < strlen($sql); ++$i) {
            $char = $sql[$i];

            // if delimiter found, add the parsed part to the returned array
            if ($char == $delimiter && !$in_string && !$in_comment) {
                $ret[]     = substr($sql, 0, $i);
                $sql       = substr($sql, $i + 1);
                $i         = 0;
                $last_char = '';
            }
            // if in comment, add the parsed part to the returned array and
            // remove the comment (till the first end of line)
            else if ($in_comment) {
                $ret[]      = substr($sql, 0, $i);
                $pos        = strpos($sql, "\n");
                $sql        = substr($sql, $pos + 1);
                $i          = 0;
                $last_char  = '';
                $in_comment = FALSE;
            }

            if ($in_string) {
                // We are in a string, first check for escaped backslashes
                if ($char == '\\') {
                    if ($last_char != '\\') {
                        $escaped_backslash = FALSE;
                    } else {
                        $escaped_backslash = !$escaped_backslash;
                    }
                }
                // then check for not escaped end of strings except for
                // backquotes than cannot be escaped
                if (($char == $string_start)
                    && ($char == '`' || !(($last_char == '\\') && !$escaped_backslash))) {
                    $in_string    = FALSE;
                    $string_start = '';
                }
            } else {
                // we are not in a string, check for start of strings
                if (($char == '"') || ($char == '\'') || ($char == '`')) {
                    $in_string    = TRUE;
                    $string_start = $char;
                }
                // not start of a string, check for start of a "eol comment"
                else if ($char == '#') {
                    $in_comment   = TRUE;
                } 
            }
            $last_char = $char;
        } // end for

        // add any rest to the returned array
        if (!empty($sql)) {
            $ret[] = $sql;
        }
        return $ret;
    } // end of the 'split_sql_file()' function


    /**
     * Removes # type remarks from large sql files
     *
     * Version 3 20th May 2001 - Last Modified By Pete Kelly
     *
     * @param   string   the sql commands
     *
     * @return  string   the cleaned sql commands
     *
     * @access	public
     */
    function remove_remarks($sql)
    {
        $i = 0;

        while ($i < strlen($sql)) {
            // Patch from Chee Wai
            // (otherwise, if $i == 0 and $sql[$i] == "#", the original order
            // in the second part of the AND bit will fail with illegal index)
            if ($sql[$i] == '#' && ($i == 0 || $sql[$i-1] == "\n")) {
                $j = 1;
                while ($sql[$i+$j] != "\n") {
                    $j++;
                    if ($j+$i >= strlen($sql)) {
                        break;
                    }
                } // end while
                $sql = substr($sql, 0, $i) . substr($sql, $i+$j);
            } // end if
            $i++;
        } // end while

        return $sql;
    } // end of the 'remove_remarks()' function

} // $__LIB_READ_DUMP__
?>
