<?php
/* $Id$ */


/**
 * Formats the INSERT statements depending on the target (screen/file) of the
 * sql dump
 *
 * @param   string  the insert statement
 *
 * @global  string  the buffer containing formatted strings
 */
function my_handler($sql_insert)
{
    global $tmp_buffer;

    // Defines the end of line delimiter to use
    $eol_dlm = (isset($GLOBALS['extended_ins'])) ? ',' : ';';
    // Result will be displays on screen
    if (empty($GLOBALS['asfile'])) {
        $tmp_buffer .= htmlspecialchars($sql_insert . $eol_dlm . $GLOBALS['crlf']);
    }
    // Result will be save in a file
    else {
        $tmp_buffer .= $sql_insert . $eol_dlm . $GLOBALS['crlf'];
    }
} // end of the 'my_handler()' function


/**
 * Formats the INSERT statements depending on the target (screen/file) of the
 * cvs export
 *
 * Revisions: 2001-05-07, Lem9: added $add_character
 *            2001-07-12, loic1: $crlf should be used only if there is no EOL
 *                               character defined by the user
 *
 * @param   string  the insert statement
 *
 * @global  string  the character to add at the end of lines
 * @global  string  the buffer containing formatted strings
 */
function my_csvhandler($sql_insert)
{
    global $add_character;
    global $tmp_buffer;

    // Handles the EOL character
    if ($GLOBALS['what'] == 'excel') {
        $add_character = "\r\n";
    } else if (empty($add_character)) {
        $add_character = $GLOBALS['crlf'];
    } else {
        if (get_magic_quotes_gpc()) {
            $add_character = stripslashes($add_character);
        }
        $add_character = str_replace('\\r', "\015", $add_character);
        $add_character = str_replace('\\n', "\012", $add_character);
        $add_character = str_replace('\\t', "\011", $add_character);
    } // end if

    // Result will be displays on screen
    if (empty($GLOBALS['asfile'])) {
        $tmp_buffer .= htmlspecialchars($sql_insert) . $add_character;
    }
    // Result will be save in a file
    else {
        $tmp_buffer .= $sql_insert . $add_character;
    }
} // end of the 'my_csvhandler()' function



/**
 * Get the variables sent or posted to this script and a core script
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit(600);
$dump_buffer = '';
// Defines the default <CR><LF> format
$crlf        = which_crlf();


/**
 * Ensure zipped formats are associated with the download feature
 */
if (empty($asfile)
    && (!empty($gzip) || !empty($bzip))) {
    $asfile = 1;
}


/**
 * Send headers depending on whether the user choosen to download a dump file
 * or not
 */
// No download
if (empty($asfile)) {
    $cfgServer_backup = $cfgServer;
    include('./header.inc.php3');
    $cfgServer = $cfgServer_backup;
    unset($cfgServer_backup);
    echo '<div align="left">' . "\n";
    echo '    <pre>' . "\n";
} // end if

// Download
else {
    // Defines filename and extension
    if (!isset($table)) {
        $filename = $db;
    } else {
        $filename = $table;
    }
    if (isset($bzip) && $bzip == 'bzip') {
        $ext = 'bz2';
    } else if (isset($gzip) && $gzip == 'gzip') {
        $ext = 'gz';
    } else if ($what == 'csv' || $what == 'excel') {
        $ext = 'csv';
    } else {
        $ext = 'sql';
    }

    // Send headers
    header('Content-Type: application/octetstream');
    header('Content-Disposition: filename="' . $filename . '.' . $ext . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
} // end download


/**
 * Builds the dump
 */
// Gets the number of tables if a dump of a database has been required
if (!isset($table)) {
    $tables     = mysql_list_tables($db);
    $num_tables = @mysql_numrows($tables);
} else {
    $num_tables = 1;
    $single     = TRUE;
}

// No table -> error message
if ($num_tables == 0) {
    echo '# ' . $strNoTablesFound;
}
// At least on table -> do the work
else {
    // No csv format -> add some comments at the top
    if ($what != 'csv' &&  $what != 'excel') {
        $dump_buffer       .= '# phpMyAdmin MySQL-Dump' . $crlf
                           .  '# version ' . PHPMYADMIN_VERSION . $crlf
                           .  '# http://phpwizard.net/phpMyAdmin/' . $crlf
                           .  '# http://phpmyadmin.sourceforge.net/ (download page)' . $crlf
                           .  '#' . $crlf
                           .  '# ' . $strHost . ': ' . $cfgServer['host'];
        if (!empty($cfgServer['port'])) {
            $dump_buffer   .= ':' . $cfgServer['port'];
        }
        $formatted_db_name = (isset($use_backquotes))
                           ? backquote($db)
                           : '\'' . $db . '\'';
        $dump_buffer       .= $crlf
                           .  '# ' . $strGenTime . ': ' . date('F j, Y, g:i a') . $crlf
                           .  '# ' . $strServerVersion . ': ' . substr(MYSQL_INT_VERSION, 0, 1) . '.' . substr(MYSQL_INT_VERSION, 1, 2) . '.' . substr(MYSQL_INT_VERSION, 3) . $crlf
                           .  '# ' . $strPHPVersion . ': ' . phpversion() . $crlf
                           .  '# ' . $strDatabase . ': ' . $formatted_db_name . $crlf;

        $i = 0;
        if (isset($table_select)) {
            $tmp_select = implode($table_select, '|');
            $tmp_select = '|' . $tmp_select . '|';
        }
        while ($i < $num_tables) {
            if (!isset($single)) {
                $table = mysql_tablename($tables, $i);
            }
            if (isset($tmp_select) && is_int(strpos($tmp_select, '|' . $table . '|')) == FALSE) {
                $i++;
            } else {
                $formatted_table_name = (isset($use_backquotes))
                                      ? backquote($table)
                                      : '\'' . $table . '\'';
                // If only datas, no need to displays table name
                if ($what != 'dataonly') {
                    $dump_buffer.= '# --------------------------------------------------------' . $crlf
                                .  $crlf . '#' . $crlf
                                .  '# ' . $strTableStructure . ' ' . $formatted_table_name . $crlf
                                .  '#' . $crlf . $crlf
                                .  get_table_def($db, $table, $crlf) . ';' . $crlf;
                }
                // At least data
                if (($what == 'data') || ($what == 'dataonly')) {
                    $dump_buffer .= $crlf . '#' . $crlf
                                 .  '# ' . $strDumpingData . ' ' . $formatted_table_name . $crlf
                                 .  '#' . $crlf .$crlf;
                    $tmp_buffer  = '';
                    if (!isset($limit_from) || !isset($limit_to)) {
                        $limit_from = $limit_to = 0;
                    }
                    get_table_content($db, $table, $limit_from, $limit_to, 'my_handler');
                    $dump_buffer .= $tmp_buffer;
                } // end if
                $i++;
            } // end if-else
        } // end while

        // Don't remove, it makes easier to select & copy frombrowser - staybyte
        $dump_buffer .= $crlf;
    } // end 'no csv' case

    // 'csv' case
    else {
        $tmp_buffer = '';
        get_table_csv($db, $table, $limit_from, $limit_to, $separator, $enclosed, 'my_csvhandler');
        $dump_buffer .= $tmp_buffer;
    } // end 'csv case
} // end building the dump


/**
 * "Displays" the dump...
 */
// 1. as a bzipped file
if (isset($bzip) && $bzip == 'bzip') {
    if (@function_exists('bzcompress')) {
        echo bzcompress($dump_buffer);
    } 
}
// 2. as a gzipped file
else if (isset($gzip) && $gzip == 'gzip') {
    if ($gzip == 'gzip' && @function_exists('gzencode')) {
        // without the optional parameter level because it bug
        echo gzencode($dump_buffer);
    }
}
// 3. on screen
else {
    echo $dump_buffer;
}


/**
 * Close the html tags and add the footers in dump is displayed on screen
 */
if (empty($asfile)) {
    echo '    </pre>' . "\n";
    echo '</div>' . "\n";
    echo "\n";
    include('./footer.inc.php3');
} // end if
?>
