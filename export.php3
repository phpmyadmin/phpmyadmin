<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Get the variables sent or posted to this script and a core script
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/zip.lib.php3');

// What type of export are we doing?
if ($what == 'excel') {
    $type = 'csv';
} else {
    $type = $what;
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
require('./libraries/export/' . $type . '.php3');

// Generate error url
if ($export_type == 'server') {
    $err_url = 'server_export.php3?' . PMA_generate_common_url();
} elseif ($export_type == 'database') {
    $err_url = 'db_details_export.php3?' . PMA_generate_common_url($db);
} else {
    $err_url = 'tbl_properties_export.php3?' . PMA_generate_common_url($db, $table);
}

/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit($cfg['ExecTimeLimit']);

// Start with empty buffer
$dump_buffer = '';

// We send fake headers to avoid browser timeout when buffering
$time_start = time();


/**
 * Output handler for all exports, if needed buffering, it stores data into
 * $dump_buffer, otherwise it prints thems out.
 *
 * @param   string  the insert statement
 *
 * @return  bool    Whether output suceeded
 */
function PMA_exportOutputHandler($line)
{
    global $time_start;

    // Kanji encoding convert feature
    if (function_exists('PMA_kanji_str_conv')) {
        $line = PMA_kanji_str_conv($line, $GLOBALS['knjenc'], isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : '');
    }
    // If we have to buffer data, we will perform everything at once at the end
    if ($GLOBALS['buffer_needed']) {
        $GLOBALS['dump_buffer'] .= $line;

        $time_now = time();
        if ($time_start >= $time_now + 30) {
            $time_start = $time_now;
            header('X-pmaPing: Pong');
        } // end if
    } else {
        if ($GLOBALS['asfile']) {
            if ($GLOBALS['save_on_server']) {
                $write_result = @fwrite($GLOBALS['file_handle'], $line);
                if (!$write_result || ($write_result != strlen($line))) {
                    $GLOBALS['message'] = sprintf($GLOBALS['strNoSpace'], $save_filename);
                    return FALSE;
                }
                $time_now = time();
                if ($time_start >= $time_now + 30) {
                    $time_start = $time_now;
                    header('X-pmaPing: Pong');
                } // end if
            } else {
                // We export as file - output normally
                if ($GLOBALS['output_charset_conversion']) {
                    $line = PMA_convert_string($GLOBALS['charset'], $GLOBALS['charset_of_file'], $line);
                }
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars($line);
        }
    }
    return TRUE;
} // end of the 'PMA_exportOutputHandler()' function

// Will we save dump on server?
$save_on_server = isset($cfg['SaveDir']) && !empty($cfg['SaveDir']) && !empty($onserver);

// Ensure compressed formats are associated with the download feature
if (empty($asfile)) {
    if ($save_on_server) {
        $asfile = TRUE;
    } elseif (isset($compression) && ($compression == 'zip' | $compression == 'gzip' | $compression == 'bzip')) {
        $asfile = TRUE;
    } else {
        $asfile = FALSE;
    }
} else {
    $asfile = TRUE;
}

// Defines the default <CR><LF> format
$crlf = PMA_whichCrlf();

// Do we need to convert charset?
$output_charset_conversion = $asfile && 
    $cfg['AllowAnywhereRecoding'] && $allow_recoding
    && isset($charset_of_file) && $charset_of_file != $charset;

// Set whether we will need buffering
$buffer_needed = isset($compression) && ($compression == 'zip' | $compression == 'gzip' | $compression == 'bzip');

// Generate filename and mime type if needed
if ($asfile) {
    $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
    if ($export_type == 'server') {
        if (isset($remember_template)) {
            setcookie('pma_server_filename_template', $filename_template , 0, 
                substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), 
                '', ($pma_uri_parts['scheme'] == 'https'));
        }
        $filename = str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template));
    } elseif ($export_type == 'database') {
        if (isset($remember_template)) {
            setcookie('pma_db_filename_template', $filename_template , 0, 
                substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), 
                '', ($pma_uri_parts['scheme'] == 'https'));
        }
        $filename = str_replace('__DB__', $db, str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template)));
    } else {
        if (isset($remember_template)) {
            setcookie('pma_table_filename_template', $filename_template , 0, 
                substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), 
                '', ($pma_uri_parts['scheme'] == 'https'));
        }
        $filename = str_replace('__TABLE__', $table, str_replace('__DB__', $db, str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template))));
    }

    // convert filename to iso-8859-1, it is safer
    if (!(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
        $filename = PMA_convert_string($charset, 'iso-8859-1', $filename);
    } else {
        $filename = PMA_convert_string($convcharset, 'iso-8859-1', $filename);
    }

    // Generate basic dump extension
    if ($type == 'csv') {
        $filename  .= '.csv';
        $mime_type = 'text/x-csv';
    } else if ($type == 'xml') {
        $filename  .= '.xml';
        $mime_type = 'text/xml';
    } else if ($type == 'latex') {
        $filename  .= '.tex';
        $mime_type = 'application/x-tex';
    } else {
        $filename  .= '.sql';
        // loic1: 'application/octet-stream' is the registered IANA type but
        //        MSIE and Opera seems to prefer 'application/octetstream'
        $mime_type = (PMA_USR_BROWSER_AGENT == 'IE' || PMA_USR_BROWSER_AGENT == 'OPERA')
                   ? 'application/octetstream'
                   : 'application/octet-stream';
    }

    // If dump is going to be compressed, set correct mime_type and add
    // compression to extension
    if (isset($compression) && $compression == 'bzip') {
        $filename  .= '.bz2';
        $mime_type = 'application/x-bzip';
    } else if (isset($compression) && $compression == 'gzip') {
        $filename  .= '.gz';
        $mime_type = 'application/x-gzip';
    } else if (isset($compression) && $compression == 'zip') {
        $filename  .= '.zip';
        $mime_type = 'application/x-zip';
    }
}

// Open file on server if needed
if ($save_on_server) {
    $save_filename = $cfg['SaveDir'] . ereg_replace('[/\\]','_',$filename);
    unset($message);
    if (file_exists($save_filename) && empty($onserverover)) {
        $message = sprintf($strFileAlreadyExists, $save_filename);
    } else {
        if (is_file($save_filename) && !is_writable($save_filename)) {
            $message = sprintf($strNoPermission, $save_filename);
        } else {
            if (!$file_handle = @fopen($save_filename, 'w')) {
                $message = sprintf($strNoPermission, $save_filename);
            }
        }
    }
    if (isset($message)) {
        $js_to_run = 'functions.js';
        include('./header.inc.php3');
        if ($export_type == 'server') {
            $active_page = 'server_export.php3';
            include('./server_export.php3');
        } elseif ($export_type == 'database') {
            $active_page = 'db_details_export.php3';
            include('./db_details_export.php3');
        } else {
            $active_page = 'tbl_properties_export.php3';
            include('./tbl_properties_export.php3');
        }
        exit();
    }
}

/**
 * Send headers depending on whether the user chose to download a dump file
 * or not
 */
if (!$save_on_server) {
    if ($asfile ) {
        // Download
        header('Content-Type: ' . $mime_type);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        // lem9 & loic1: IE need specific headers
        if (PMA_USR_BROWSER_AGENT == 'IE') {
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
        }
    } else {
        // HTML
        $backup_cfgServer = $cfg['Server'];
        include('./header.inc.php3');
        $cfg['Server'] = $backup_cfgServer;
        unset($backup_cfgServer);
        echo '<div align="' . $cell_align_left . '">' . "\n";
        echo '    <pre>' . "\n";
    } // end download
}

// Check if we have something to export
if ($export_type == 'database') {
    $tables     = PMA_mysql_list_tables($db);
    $num_tables = ($tables) ? @mysql_numrows($tables) : 0;
    if ($num_tables == 0) {
        $message = $strNoTablesFound;
        $js_to_run = 'functions.js';
        include('./header.inc.php3');
        if ($export_type == 'server') {
            $active_page = 'server_export.php3';
            include('./server_export.php3');
        } elseif ($export_type == 'database') {
            $active_page = 'db_details_export.php3';
            include('./db_details_export.php3');
        } else {
            $active_page = 'tbl_properties_export.php3';
            include('./tbl_properties_export.php3');
        }
        exit();
    }
}

// Add possibly some comments to export
PMA_exportHeader();

// Will we need relation & co. setup?
$do_relation = isset($GLOBALS[$what . '_relation']);
$do_comments = isset($GLOBALS[$what . '_comments']);
$do_mime     = isset($GLOBALS[$what . '_mime']);
if ($do_relation || $do_comments || $do_mime) {
    require('./libraries/relation.lib.php3');
    $cfgRelation = PMA_getRelationsParam();
}
if ($do_mime) {
    require('./libraries/transformations.lib.php3');
}

/**
 * Builds the dump
 */
// Gets the number of tables if a dump of a database has been required
if ($export_type == 'server') {
    /**
     * Gets the databases list - if it has not been built yet
     */
    if ($server > 0 && empty($dblist)) {
        PMA_availableDatabases();
    }
    
    if (isset($db_select)) {
        $tmp_select = implode($db_select, '|');
        $tmp_select = '|' . $tmp_select . '|';
    }
    // Walk over databases
    reset($dblist);
    while (list(, $current_db) = each($dblist)) {
        if ((isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $current_db . '|'))
            || !isset($tmp_select)) {
            PMA_exportDBHeader($current_db);
            PMA_exportDBCreate($current_db);
            $tables     = PMA_mysql_list_tables($current_db);
            $num_tables = ($tables) ? @mysql_numrows($tables) : 0;
            $i = 0;
            while ($i < $num_tables) {
                $table = PMA_mysql_tablename($tables, $i);
                $local_query  = 'SELECT * FROM ' . PMA_backquote($current_db) . '.' . PMA_backquote($table);
                if (isset($GLOBALS[$what . '_structure'])) PMA_exportStructure($current_db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime);
                if (isset($GLOBALS[$what . '_data'])) PMA_exportData($current_db, $table, $crlf, $err_url, $local_query);
                $i++;
            }
            PMA_exportDBFooter($current_db);
        }
    }
} elseif ($export_type == 'database') {
    PMA_exportDBHeader($db);
    if (isset($table_select)) {
        $tmp_select = implode($table_select, '|');
        $tmp_select = '|' . $tmp_select . '|';
    }
    $i = 0;
    while ($i < $num_tables) {
        $table = PMA_mysql_tablename($tables, $i);
        $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
        if ((isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $table . '|'))
            || !isset($tmp_select)) {

            if (isset($GLOBALS[$what . '_structure'])) PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime);
            if (isset($GLOBALS[$what . '_data'])) PMA_exportData($db, $table, $crlf, $err_url, $local_query);
        }
        $i++;
    }
    PMA_exportDBFooter($db);
} else {
    PMA_exportDBHeader($db);
    // We export just one table

    if ($limit_to > 0 && $limit_from >= 0) {
        $add_query  = ' LIMIT '
                    . (($limit_from > 0) ? $limit_from . ', ' : '')
                    . $limit_to;
    } else {
        $add_query  = '';
    }

    if (!empty($sql_query)) {
        $local_query = $sql_query . $add_query;
        PMA_mysql_select_db($db);
    } else {
        $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
    }

    if (isset($GLOBALS[$what . '_structure'])) PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime);
    if (isset($GLOBALS[$what . '_data'])) PMA_exportData($db, $table, $crlf, $err_url, $local_query);
    PMA_exportDBFooter($db);
}

/**
 * Send the dump as a file...
 */
if (!empty($asfile)) {
    // Convert the charset if required.
    if ($output_charset_conversion) {
        $dump_buffer = PMA_convert_string($GLOBALS['charset'], $GLOBALS['charset_of_file'], $dump_buffer);
    }

    // Do the compression
    // 1. as a gzipped file
    if (isset($compression) && $compression == 'zip') {
        if (PMA_PHP_INT_VERSION >= 40000 && @function_exists('gzcompress')) {
            if ($type == 'csv' ) {
                $extbis = '.csv';
            } else if ($type == 'xml') {
                $extbis = '.xml';
            } else {
                $extbis = '.sql';
            }
            $zipfile = new zipfile();
            $zipfile -> addFile($dump_buffer, $filename . $extbis);
            $dump_buffer = $zipfile -> file();
        }
    }
    // 2. as a bzipped file
    else if (isset($compression) && $compression == 'bzip') {
        if (PMA_PHP_INT_VERSION >= 40004 && @function_exists('bzcompress')) {
            $dump_buffer = bzcompress($dump_buffer);
            // nijel: eval in next line is because otherwise === causes syntax error on php3
            if (eval('return($dump_buffer === -8);')) {
                include('./header.inc.php3');
                echo sprintf($strBzError, '<a href="http://bugs.php.net/bug.php?id=17300" target="_blank">17300</a>');
                include('./footer.inc.php3');
                exit;
            }
        }
    }
    // 3. as a gzipped file
    else if (isset($compression) && $compression == 'gzip') {
        if (PMA_PHP_INT_VERSION >= 40004 && @function_exists('gzencode')) {
            // without the optional parameter level because it bug
            $dump_buffer = gzencode($dump_buffer);
        }
    }

    /* If ve saved on server, we have to close file now */
    if ($save_on_server) {
        $write_result = @fwrite($file_handle, $dump_buffer);
        fclose($file_handle);
        if (strlen($dump_buffer) !=0 && (!$write_result || ($write_result != strlen($dump_buffer)))) {
            $message = sprintf($strNoSpace, $save_filename);
        } else {
            $message = sprintf($strDumpSaved, $save_filename);
        }

        $js_to_run = 'functions.js';
        include('./header.inc.php3');
        if ($export_type == 'server') {
            $active_page = 'server_export.php3';
            include('./server_export.php3');
        } elseif ($export_type == 'database') {
            $active_page = 'db_details_export.php3';
            include('./db_details_export.php3');
        } else {
            $active_page = 'tbl_properties_export.php3';
            include('./tbl_properties_export.php3');
        }
        exit();
    } else {
        echo $dump_buffer;
    }
}
/**
 * Displays the dump...
 */
else {
    /**
     * Close the html tags and add the footers in dump is displayed on screen
     */
    echo '    </pre>' . "\n";
    echo '</div>' . "\n";
    echo "\n";
    include('./footer.inc.php3');
} // end if
?>
