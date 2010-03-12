<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @todo    too much die here, or?
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Get the variables sent or posted to this script and a core script
 */
require_once './libraries/common.inc.php';
require_once './libraries/zip.lib.php';
require_once './libraries/plugin_interface.lib.php';

PMA_checkParameters(array('what', 'export_type'));

// Scan plugins
$export_list = PMA_getPlugins('./libraries/export/', array('export_type' => $export_type, 'single_table' => isset($single_table)));

// Backward compatbility
$type = $what;

// Check export type
if (!isset($export_list[$type])) {
    die('Bad type!');
}

/**
 * valid compression methods
 */
$compression_methods = array(
    'zip',
    'gzip',
    'bzip',
);

/**
 * init and variable checking
 */
$compression = false;
$onserver = false;
$save_on_server = false;
$buffer_needed = false;
if (empty($_REQUEST['asfile'])) {
    $asfile = false;
} else {
    $asfile = true;
    if (in_array($_REQUEST['compression'], $compression_methods)) {
        $compression = $_REQUEST['compression'];
        $buffer_needed = true;
    }
    if (!empty($_REQUEST['onserver'])) {
        $onserver = $_REQUEST['onserver'];
        // Will we save dump on server?
        $save_on_server = ! empty($cfg['SaveDir']) && $onserver;
    }
}

// Does export require to be into file?
if (isset($export_list[$type]['force_file']) && ! $asfile) {
    $message = PMA_Message::error('strExportMustBeFile');
    $GLOBALS['js_include'][] = 'functions.js';
    require_once './libraries/header.inc.php';
    if ($export_type == 'server') {
        $active_page = 'server_export.php';
        require './server_export.php';
    } elseif ($export_type == 'database') {
        $active_page = 'db_export.php';
        require './db_export.php';
    } else {
        $active_page = 'tbl_export.php';
        require './tbl_export.php';
    }
    exit();
}

// Generate error url and check for needed variables
if ($export_type == 'server') {
    $err_url = 'server_export.php?' . PMA_generate_common_url();
} elseif ($export_type == 'database' && strlen($db)) {
    $err_url = 'db_export.php?' . PMA_generate_common_url($db);
    // Check if we have something to export
    if (isset($table_select)) {
        $tables = $table_select;
    } else {
        $tables = array();
    }
} elseif ($export_type == 'table' && strlen($db) && strlen($table)) {
    $err_url = 'tbl_export.php?' . PMA_generate_common_url($db, $table);
} else {
    die('Bad parameters!');
}

// Get the functions specific to the export type
require './libraries/export/' . PMA_securePath($type) . '.php';

/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit($cfg['ExecTimeLimit']);
if (!empty($cfg['MemoryLimit'])) {
    @ini_set('memory_limit', $cfg['MemoryLimit']);
}

// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

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
    global $time_start, $dump_buffer, $dump_buffer_len, $save_filename;

    // Kanji encoding convert feature
    if ($GLOBALS['output_kanji_conversion']) {
        $line = PMA_kanji_str_conv($line, $GLOBALS['knjenc'], isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : '');
    }
    // If we have to buffer data, we will perform everything at once at the end
    if ($GLOBALS['buffer_needed']) {

        $dump_buffer .= $line;
        if ($GLOBALS['onfly_compression']) {

            $dump_buffer_len += strlen($line);

            if ($dump_buffer_len > $GLOBALS['memory_limit']) {
                if ($GLOBALS['output_charset_conversion']) {
                    $dump_buffer = PMA_convert_string($GLOBALS['charset'], $GLOBALS['charset_of_file'], $dump_buffer);
                }
                // as bzipped
                if ($GLOBALS['compression'] == 'bzip'  && @function_exists('bzcompress')) {
                    $dump_buffer = bzcompress($dump_buffer);
                }
                // as a gzipped file
                elseif ($GLOBALS['compression'] == 'gzip' && @function_exists('gzencode')) {
                    // without the optional parameter level because it bug
                    $dump_buffer = gzencode($dump_buffer);
                }
                if ($GLOBALS['save_on_server']) {
                    $write_result = @fwrite($GLOBALS['file_handle'], $dump_buffer);
                    if (!$write_result || ($write_result != strlen($dump_buffer))) {
                        $GLOBALS['message'] = PMA_Message::error('strNoSpace');
                        $GLOBALS['message']->addParam($save_filename);
                        return false;
                    }
                } else {
                    echo $dump_buffer;
                }
                $dump_buffer = '';
                $dump_buffer_len = 0;
            }
        } else {
            $time_now = time();
            if ($time_start >= $time_now + 30) {
                $time_start = $time_now;
                header('X-pmaPing: Pong');
            } // end if
        }
    } else {
        if ($GLOBALS['asfile']) {
            if ($GLOBALS['output_charset_conversion']) {
                $line = PMA_convert_string($GLOBALS['charset'], $GLOBALS['charset_of_file'], $line);
            }
            if ($GLOBALS['save_on_server'] && strlen($line) > 0) {
                $write_result = @fwrite($GLOBALS['file_handle'], $line);
                if (!$write_result || ($write_result != strlen($line))) {
                    $GLOBALS['message'] = PMA_Message::error('strNoSpace');
                    $GLOBALS['message']->addParam($save_filename);
                    return false;
                }
                $time_now = time();
                if ($time_start >= $time_now + 30) {
                    $time_start = $time_now;
                    header('X-pmaPing: Pong');
                } // end if
            } else {
                // We export as file - output normally
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars($line);
        }
    }
    return true;
} // end of the 'PMA_exportOutputHandler()' function

// Defines the default <CR><LF> format. For SQL always use \n as MySQL wants this on all platforms.
if ($what == 'sql') {
    $crlf = "\n";
} else {
    $crlf = PMA_whichCrlf();
}

$output_kanji_conversion = function_exists('PMA_kanji_str_conv') && $type != 'xls';

// Do we need to convert charset?
$output_charset_conversion = $asfile && $cfg['AllowAnywhereRecoding']
    && isset($charset_of_file) && $charset_of_file != $charset
    && $type != 'xls';

// Use on the fly compression?
$onfly_compression = $GLOBALS['cfg']['CompressOnFly'] && ($compression == 'gzip' || $compression == 'bzip');
if ($onfly_compression) {
    $memory_limit = trim(@ini_get('memory_limit'));
    // 2 MB as default
    if (empty($memory_limit)) {
        $memory_limit = 2 * 1024 * 1024;
    }

    if (strtolower(substr($memory_limit, -1)) == 'm') {
        $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024;
    } elseif (strtolower(substr($memory_limit, -1)) == 'k') {
        $memory_limit = (int)substr($memory_limit, 0, -1) * 1024;
    } elseif (strtolower(substr($memory_limit, -1)) == 'g') {
        $memory_limit = (int)substr($memory_limit, 0, -1) * 1024 * 1024 * 1024;
    } else {
        $memory_limit = (int)$memory_limit;
    }

    // Some of memory is needed for other thins and as treshold.
    // Nijel: During export I had allocated (see memory_get_usage function)
    //        approx 1.2MB so this comes from that.
    if ($memory_limit > 1500000) {
        $memory_limit -= 1500000;
    }

    // Some memory is needed for compression, assume 1/3
    $memory_limit /= 8;
}

// Generate filename and mime type if needed
if ($asfile) {
    $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
    if ($export_type == 'server') {
        if (isset($remember_template)) {
            PMA_setCookie('pma_server_filename_template', $filename_template);
        }
        $filename = str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template));
    } elseif ($export_type == 'database') {
        if (isset($remember_template)) {
            PMA_setCookie('pma_db_filename_template', $filename_template);
        }
        $filename = str_replace('__DB__', $db, str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template)));
    } else {
        if (isset($remember_template)) {
            PMA_setCookie('pma_table_filename_template', $filename_template);
        }
        $filename = str_replace('__TABLE__', $table, str_replace('__DB__', $db, str_replace('__SERVER__', $GLOBALS['cfg']['Server']['host'], strftime($filename_template))));
    }

    // convert filename to iso-8859-1, it is safer
    if (!(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] )) {
        $filename = PMA_convert_string($charset, 'iso-8859-1', $filename);
    } else {
        $filename = PMA_convert_string($convcharset, 'iso-8859-1', $filename);
    }

    // Grab basic dump extension and mime type
    $filename  .= '.' . $export_list[$type]['extension'];
    $mime_type  = $export_list[$type]['mime_type'];

    // If dump is going to be compressed, set correct mime_type and add
    // compression to extension
    if ($compression == 'bzip') {
        $filename  .= '.bz2';
        $mime_type = 'application/x-bzip2';
    } elseif ($compression == 'gzip') {
        $filename  .= '.gz';
        $mime_type = 'application/x-gzip';
    } elseif ($compression == 'zip') {
        $filename  .= '.zip';
        $mime_type = 'application/zip';
    }
}

// Open file on server if needed
if ($save_on_server) {
    $save_filename = PMA_userDir($cfg['SaveDir']) . preg_replace('@[/\\\\]@', '_', $filename);
    unset($message);
    if (file_exists($save_filename) && empty($onserverover)) {
        $message = PMA_Message::error('strFileAlreadyExists');
        $message->addParam($save_filename);
    } else {
        if (is_file($save_filename) && !is_writable($save_filename)) {
            $message = PMA_Message::error('strNoPermission');
            $message->addParam($save_filename);
        } else {
            if (!$file_handle = @fopen($save_filename, 'w')) {
                $message = PMA_Message::error('strNoPermission');
                $message->addParam($save_filename);
            }
        }
    }
    if (isset($message)) {
        $GLOBALS['js_include'][] = 'functions.js';
        require_once './libraries/header.inc.php';
        if ($export_type == 'server') {
            $active_page = 'server_export.php';
            require './server_export.php';
        } elseif ($export_type == 'database') {
            $active_page = 'db_export.php';
            require './db_export.php';
        } else {
            $active_page = 'tbl_export.php';
            require './tbl_export.php';
        }
        exit();
    }
}

/**
 * Send headers depending on whether the user chose to download a dump file
 * or not
 */
if (!$save_on_server) {
    if ($asfile) {
        // Download
        // (avoid rewriting data containing HTML with anchors and forms;
        // this was reported to happen under Plesk)
        @ini_set('url_rewriter.tags','');

        header('Content-Type: ' . $mime_type);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        // lem9: Tested behavior of
        //       IE 5.50.4807.2300
        //       IE 6.0.2800.1106 (small glitch, asks twice when I click Open)
        //       IE 6.0.2900.2180
        //       Firefox 1.0.6
        // in http and https
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        if (PMA_USR_BROWSER_AGENT == 'IE') {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header('Pragma: no-cache');
            // test case: exporting a database into a .gz file with Safari
            // would produce files not having the current time
            // (added this header for Safari but should not harm other browsers)
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        }
    } else {
        // HTML
        if ($export_type == 'database') {
            $num_tables = count($tables);
            if ($num_tables == 0) {
                $message = PMA_Message::error('strNoTablesFound');
                $GLOBALS['js_include'][] = 'functions.js';
                require_once './libraries/header.inc.php';
                $active_page = 'db_export.php';
                require './db_export.php';
                exit();
            }
        }
        $backup_cfgServer = $cfg['Server'];
        require_once './libraries/header.inc.php';
        $cfg['Server'] = $backup_cfgServer;
        unset($backup_cfgServer);
        echo "\n" . '<div align="' . $cell_align_left . '">' . "\n";
        //echo '    <pre>' . "\n";
        echo '    <form name="nofunction">' . "\n"
           // remove auto-select for now: there is no way to select
           // only a part of the text; anyway, it should obey
           // $cfg['TextareaAutoSelect']
           //. '        <textarea name="sqldump" cols="50" rows="30" onclick="this.select();" id="textSQLDUMP" wrap="OFF">' . "\n";
           . '        <textarea name="sqldump" cols="50" rows="30" id="textSQLDUMP" wrap="OFF">' . "\n";
    } // end download
}

// Fake loop just to allow skip of remain of this code by break, I'd really
// need exceptions here :-)
do {

// Add possibly some comments to export
if (!PMA_exportHeader()) {
    break;
}

// Will we need relation & co. setup?
$do_relation = isset($GLOBALS[$what . '_relation']);
$do_comments = isset($GLOBALS[$what . '_comments']);
$do_mime     = isset($GLOBALS[$what . '_mime']);
if ($do_relation || $do_comments || $do_mime) {
    require_once './libraries/relation.lib.php';
    $cfgRelation = PMA_getRelationsParam();
}
if ($do_mime) {
    require_once './libraries/transformations.lib.php';
}

// Include dates in export?
$do_dates   = isset($GLOBALS[$what . '_dates']);

/**
 * Builds the dump
 */
// Gets the number of tables if a dump of a database has been required
if ($export_type == 'server') {
    if (isset($db_select)) {
        $tmp_select = implode($db_select, '|');
        $tmp_select = '|' . $tmp_select . '|';
    }
    // Walk over databases
    foreach ($GLOBALS['pma']->databases as $current_db) {
        if ((isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $current_db . '|'))
            || !isset($tmp_select)) {
            if (!PMA_exportDBHeader($current_db)) {
                break 2;
            }
            if (!PMA_exportDBCreate($current_db)) {
                break 2;
            }
            $tables = PMA_DBI_get_tables($current_db);
            $views = array();
            foreach ($tables as $table) {
                // if this is a view, collect it for later; views must be exported
                // after the tables
                $is_view = PMA_Table::isView($current_db, $table);
                if ($is_view) {
                    $views[] = $table;
                }
                if (isset($GLOBALS[$what . '_structure'])) {
                    // for a view, export a stand-in definition of the table
                    // to resolve view dependencies
                    if (!PMA_exportStructure($current_db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, $is_view ? 'stand_in' : 'create_table', $export_type)) {
                        break 3;
                    }
                }
                // if this is a view or a merge table, don't export data
                if (isset($GLOBALS[$what . '_data']) && !($is_view || PMA_Table::isMerge($current_db, $table))) {
                    $local_query  = 'SELECT * FROM ' . PMA_backquote($current_db) . '.' . PMA_backquote($table);
                    if (!PMA_exportData($current_db, $table, $crlf, $err_url, $local_query)) {
                        break 3;
                    }
                }
                // now export the triggers (needs to be done after the data because
                // triggers can modify already imported tables)
                if (isset($GLOBALS[$what . '_structure'])) {
                    if (!PMA_exportStructure($current_db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, 'triggers', $export_type)) {
                        break 2;
                    }
                }
            }
            foreach($views as $view) {
                // no data export for a view
                if (isset($GLOBALS[$what . '_structure'])) {
                    if (!PMA_exportStructure($current_db, $view, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, 'create_view', $export_type)) {
                        break 3;
                    }
                }
            }
            if (!PMA_exportDBFooter($current_db)) {
                break 2;
            }
        }
    }
} elseif ($export_type == 'database') {
    if (!PMA_exportDBHeader($db)) {
        break;
    }
    $i = 0;
    $views = array();
    // $tables contains the choices from the user (via $table_select)
    foreach ($tables as $table) {
        // if this is a view, collect it for later; views must be exported after
        // the tables
        $is_view = PMA_Table::isView($db, $table);
        if ($is_view) {
            $views[] = $table;
        }
        if (isset($GLOBALS[$what . '_structure'])) {
            // for a view, export a stand-in definition of the table
            // to resolve view dependencies
            if (!PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, $is_view ? 'stand_in' : 'create_table', $export_type)) {
                break 2;
            }
        }
        // if this is a view or a merge table, don't export data
        if (isset($GLOBALS[$what . '_data']) && !($is_view || PMA_Table::isMerge($db, $table))) {
            $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
            if (!PMA_exportData($db, $table, $crlf, $err_url, $local_query)) {
                break 2;
            }
        }
        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if (isset($GLOBALS[$what . '_structure'])) {
            if (!PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, 'triggers', $export_type)) {
                break 2;
            }
        }
    }
    foreach ($views as $view) {
        // no data export for a view
        if (isset($GLOBALS[$what . '_structure'])) {
            if (!PMA_exportStructure($db, $view, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, 'create_view', $export_type)) {
                break 2;
            }
        }
    }

    if (!PMA_exportDBFooter($db)) {
        break;
    }
} else {
    if (!PMA_exportDBHeader($db)) {
        break;
    }
    // We export just one table
    // $allrows comes from the form when "Dump all rows" has been selected
    if ($allrows == '0' && $limit_to > 0 && $limit_from >= 0) {
        $add_query  = ' LIMIT '
                    . (($limit_from > 0) ? $limit_from . ', ' : '')
                    . $limit_to;
    } else {
        $add_query  = '';
    }

    $is_view = PMA_Table::isView($db, $table);
    if (isset($GLOBALS[$what . '_structure'])) {
        if (!PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, $is_view ? 'create_view' : 'create_table', $export_type)) {
            break;
        }
    }
    // If this is an export of a single view, we have to export data;
    // for example, a PDF report
    // if it is a merge table, no data is exported
    if (isset($GLOBALS[$what . '_data']) && ! PMA_Table::isMerge($db, $table)) {
        if (!empty($sql_query)) {
            // only preg_replace if needed
            if (!empty($add_query)) {
                // remove trailing semicolon before adding a LIMIT
                $sql_query = preg_replace('%;\s*$%', '', $sql_query);
            }
            $local_query = $sql_query . $add_query;
            PMA_DBI_select_db($db);
        } else {
            $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        }
        if (!PMA_exportData($db, $table, $crlf, $err_url, $local_query)) {
            break;
        }
    }
    // now export the triggers (needs to be done after the data because
    // triggers can modify already imported tables)
    if (isset($GLOBALS[$what . '_structure'])) {
        if (!PMA_exportStructure($db, $table, $crlf, $err_url, $do_relation, $do_comments, $do_mime, $do_dates, 'triggers', $export_type)) {
            break 2;
        }
    }
    if (!PMA_exportDBFooter($db)) {
        break;
    }
}
if (!PMA_exportFooter()) {
    break;
}

} while (false);
// End of fake loop

if ($save_on_server && isset($message)) {
    $GLOBALS['js_include'][] = 'functions.js';
    require_once './libraries/header.inc.php';
    if ($export_type == 'server') {
        $active_page = 'server_export.php';
        require './server_export.php';
    } elseif ($export_type == 'database') {
        $active_page = 'db_export.php';
        require './db_export.php';
    } else {
        $active_page = 'tbl_export.php';
        require './tbl_export.php';
    }
    exit();
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
    // 1. as a zipped file
    if ($compression == 'zip') {
        if (@function_exists('gzcompress')) {
            $zipfile = new zipfile();
            $zipfile -> addFile($dump_buffer, substr($filename, 0, -4));
            $dump_buffer = $zipfile -> file();
        }
    }
    // 2. as a bzipped file
    elseif ($compression == 'bzip') {
        if (@function_exists('bzcompress')) {
            $dump_buffer = bzcompress($dump_buffer);
        }
    }
    // 3. as a gzipped file
    elseif ($compression == 'gzip') {
        if (@function_exists('gzencode') && !@ini_get('zlib.output_compression')) {
            // without the optional parameter level because it bug
            $dump_buffer = gzencode($dump_buffer);
        }
    }

    /* If ve saved on server, we have to close file now */
    if ($save_on_server) {
        $write_result = @fwrite($file_handle, $dump_buffer);
        fclose($file_handle);
        if (strlen($dump_buffer) !=0 && (!$write_result || ($write_result != strlen($dump_buffer)))) {
            $message = new PMA_Message('strNoSpace', PMA_Message::ERROR, $save_filename);
        } else {
            $message = new PMA_Message('strDumpSaved', PMA_Message::SUCCESS, $save_filename);
        }

        $GLOBALS['js_include'][] = 'functions.js';
        require_once './libraries/header.inc.php';
        if ($export_type == 'server') {
            $active_page = 'server_export.php';
            require_once './server_export.php';
        } elseif ($export_type == 'database') {
            $active_page = 'db_export.php';
            require_once './db_export.php';
        } else {
            $active_page = 'tbl_export.php';
            require_once './tbl_export.php';
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
    //echo '    </pre>' . "\n";
    echo '</textarea>' . "\n"
       . '    </form>' . "\n";
    echo '</div>' . "\n";
    echo "\n";
?>
<script type="text/javascript">
//<![CDATA[
    var bodyWidth=null; var bodyHeight=null;
    if (document.getElementById('textSQLDUMP')) {
        bodyWidth  = self.innerWidth;
        bodyHeight = self.innerHeight;
        if (!bodyWidth && !bodyHeight) {
            if (document.compatMode && document.compatMode == "BackCompat") {
                bodyWidth  = document.body.clientWidth;
                bodyHeight = document.body.clientHeight;
            } else if (document.compatMode && document.compatMode == "CSS1Compat") {
                bodyWidth  = document.documentElement.clientWidth;
                bodyHeight = document.documentElement.clientHeight;
            }
        }
        document.getElementById('textSQLDUMP').style.width=(bodyWidth-50) + 'px';
        document.getElementById('textSQLDUMP').style.height=(bodyHeight-100) + 'px';
    }
//]]>
</script>
<?php
    require_once './libraries/footer.inc.php';
} // end if
?>
