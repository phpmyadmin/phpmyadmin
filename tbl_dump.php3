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
function PMA_myHandler($sql_insert)
{
    global $tmp_buffer;

    // Kanji encoding convert feature appended by Y.Kawada (2001/2/21)
    if (function_exists('PMA_kanji_str_conv')) {
        $sql_insert = PMA_kanji_str_conv($sql_insert, $GLOBALS['knjenc'], isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : '');
    }
    // Defines the end of line delimiter to use
    $eol_dlm = (isset($GLOBALS['extended_ins']) && ($GLOBALS['current_row'] < $GLOBALS['rows_cnt']))
             ? ','
             : ';';
    // Result has to be displayed on screen
    if (empty($GLOBALS['asfile'])) {
        echo htmlspecialchars($sql_insert . $eol_dlm . $GLOBALS['crlf']);
    }
    // Result has to be saved in a text file
    else if (!isset($GLOBALS['zip']) && !isset($GLOBALS['bzip']) && !isset($GLOBALS['gzip'])) {
        echo $sql_insert . $eol_dlm . $GLOBALS['crlf'];
    }
    // Result will be saved in a *zipped file
    else {
        $tmp_buffer .= $sql_insert . $eol_dlm . $GLOBALS['crlf'];
    }
} // end of the 'PMA_myHandler()' function


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
function PMA_myCsvHandler($sql_insert)
{
    global $add_character;
    global $tmp_buffer;

    // Kanji encoding convert feature appended by Y.Kawada (2001/2/21)
    if (function_exists('PMA_kanji_str_conv')) {
        $sql_insert = PMA_kanji_str_conv($sql_insert, $GLOBALS['knjenc'], isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : '');
    }
    // Result has to be displayed on screen
    if (empty($GLOBALS['asfile'])) {
        echo htmlspecialchars($sql_insert) . $add_character;
    }
    // Result has to be saved in a text file
    else if (!isset($GLOBALS['zip']) && !isset($GLOBALS['bzip']) && !isset($GLOBALS['gzip'])) {
        echo $sql_insert . $add_character;
    }
    // Result will be saved in a *zipped file
    else {
        $tmp_buffer .= $sql_insert . $add_character;
    }
} // end of the 'PMA_myCsvHandler()' function



/**
 * Get the variables sent or posted to this script and a core script
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/build_dump.lib.php3');
require('./libraries/zip.lib.php3');


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php3'
         . '?lang=' . $lang
         . '&amp;server=' . $server
         . '&amp;db=' . urlencode($db)
         . (isset($table) ? '&amp;table=' . urlencode($table) : '');


/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit($cfg['ExecTimeLimit']);
$dump_buffer = '';
// Defines the default <CR><LF> format
$crlf        = PMA_whichCrlf();


/**
 * Ensure zipped formats are associated with the download feature
 */
if (empty($asfile)
    && (!empty($zip) || !empty($gzip) || !empty($bzip))) {
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
    echo '<div align="' . $cell_align_left . '">' . "\n";
    echo '    <pre>' . "\n";
} // end if

// Download
else {
    // Defines filename and extension, and also mime types
    if (!isset($table)) {
        $filename = $db;
    } else {
        $filename = $table;
    }
    if (isset($bzip) && $bzip == 'bzip') {
        $ext       = 'bz2';
        $mime_type = 'application/x-bzip';
    } else if (isset($gzip) && $gzip == 'gzip') {
        $ext       = 'gz';
        $mime_type = 'application/x-gzip';
    } else if (isset($zip) && $zip == 'zip') {
        $ext       = 'zip';
        $mime_type = 'application/x-zip';
    } else if ($what == 'csv' || $what == 'excel') {
        $ext       = 'csv';
        $mime_type = 'text/x-csv';
    } else {
        $ext       = 'sql';
        // loic1: 'application/octet-stream' is the registered IANA type but
        //        MSIE and Opera seems to prefer 'application/octetstream'
        $mime_type = (PMA_USR_BROWSER_AGENT == 'IE' || PMA_USR_BROWSER_AGENT == 'OPERA')
                   ? 'application/octetstream'
                   : 'application/octet-stream';
    }

    // Send headers
    header('Content-Type: ' . $mime_type);
    // lem9 & loic1: IE need specific headers
    if (PMA_USR_BROWSER_AGENT == 'IE') {
        header('Content-Disposition: inline; filename="' . $filename . '.' . $ext . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
        header('Expires: 0');
        header('Pragma: no-cache');
    }
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
                           .  '# version ' . PMA_VERSION . $crlf
                           .  '# http://phpwizard.net/phpMyAdmin/' . $crlf
                           .  '# http://www.phpmyadmin.net/ (download page)' . $crlf
                           .  '#' . $crlf
                           .  '# ' . $strHost . ': ' . $cfgServer['host'];
        if (!empty($cfgServer['port'])) {
            $dump_buffer   .= ':' . $cfgServer['port'];
        }
        $formatted_db_name = (isset($use_backquotes))
                           ? PMA_backquote($db)
                           : '\'' . $db . '\'';
        $dump_buffer       .= $crlf
                           .  '# ' . $strGenTime . ': ' . PMA_localisedDate() . $crlf
                           .  '# ' . $strServerVersion . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . substr(PMA_MYSQL_INT_VERSION, 3) . $crlf
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
                                      ? PMA_backquote($table)
                                      : '\'' . $table . '\'';
                // If only datas, no need to displays table name
                if ($what != 'dataonly') {
                    $dump_buffer .= '# --------------------------------------------------------' . $crlf
                                 .  $crlf . '#' . $crlf
                                 .  '# ' . $strTableStructure . ' ' . $formatted_table_name . $crlf
                                 .  '#' . $crlf . $crlf
                                 .  PMA_getTableDef($db, $table, $crlf, $err_url) . ';' . $crlf;
                }
                if (function_exists('PMA_kanji_str_conv')) { // Y.Kawada
                    $dump_buffer = PMA_kanji_str_conv($dump_buffer, $knjenc, isset($xkana) ? $xkana : '');
                }
                // At least data
                if (($what == 'data') || ($what == 'dataonly')) {
                    $tcmt = $crlf . '#' . $crlf
                                 .  '# ' . $strDumpingData . ' ' . $formatted_table_name . $crlf
                                 .  '#' . $crlf .$crlf;
                    if (function_exists('PMA_kanji_str_conv')) { // Y.Kawada
                        $dump_buffer .= PMA_kanji_str_conv($tcmt, $knjenc, isset($xkana) ? $xkana : '');
                    } else {
                        $dump_buffer .= $tcmt;
                    }
                    $tmp_buffer  = '';
                    if (!isset($limit_from) || !isset($limit_to)) {
                        $limit_from = $limit_to = 0;
                    }
                    // loic1: display data if they aren't bufferized
                    if (!isset($zip) && !isset($bzip) && !isset($gzip)) {
                        echo $dump_buffer;
                        $dump_buffer = '';
                    }
                    PMA_getTableContent($db, $table, $limit_from, $limit_to, 'PMA_myHandler', $err_url);

                    $dump_buffer .= $tmp_buffer;
                } // end if
                $i++;
            } // end if-else
        } // end while

        // staybyte: don't remove, it makes easier to select & copy from
        // browser
        $dump_buffer .= $crlf;
    } // end 'no csv' case

    // 'csv' case
    else {
        // Handles the EOL character
        if ($GLOBALS['what'] == 'excel') {
            $add_character = "\015\012";
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

        $tmp_buffer = '';
        PMA_getTableCsv($db, $table, $limit_from, $limit_to, $separator, $enclosed, $escaped, 'PMA_myCsvHandler', $err_url);
        $dump_buffer .= $tmp_buffer;
    } // end 'csv case
} // end building the dump


/**
 * "Displays" the dump...
 */
// 1. as a gzipped file
if (isset($zip) && $zip == 'zip') {
    if (PMA_PHP_INT_VERSION >= 40000 && @function_exists('gzcompress')) {
        if ($what == 'csv' || $what == 'excel') {
            $extbis = '.csv';
        } else {
            $extbis = '.sql';
        }
        $zipfile = new zipfile();
        $zipfile -> addFile($dump_buffer, $filename . $extbis);
        echo $zipfile -> file();
    }
}
// 2. as a bzipped file
else if (isset($bzip) && $bzip == 'bzip') {
    if (PMA_PHP_INT_VERSION >= 40004 && @function_exists('bzcompress')) {
        echo bzcompress($dump_buffer);
    }
}
// 3. as a gzipped file
else if (isset($gzip) && $gzip == 'gzip') {
    if (PMA_PHP_INT_VERSION >= 40004 && @function_exists('gzencode')) {
        // without the optional parameter level because it bug
        echo gzencode($dump_buffer);
    }
}
// 4. on screen or as a text file
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
