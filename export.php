<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main export handling code
 *
 * @package PhpMyAdmin
 */

/**
 * Get the variables sent or posted to this script and a core script
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/zip.lib.php';
require_once 'libraries/plugin_interface.lib.php';

/**
 * Sets globals from $_POST
 *
 * - Please keep the parameters in order of their appearance in the form
 * - Some of these parameters are not used, as the code below directly
 *   verifies from the superglobal $_POST or $_REQUEST
 */
$post_params = array(
        'db',
        'table',
        'single_table',
        'export_type',
        'export_method',
        'quick_or_custom',
        'db_select',
        'table_select',
        'limit_to',
        'limit_from',
        'allrows',
        'output_format',
        'filename_template',
        'remember_template',
        'charset_of_file',
        'compression',
        'what',
        'htmlword_structure_or_data',
        'htmlword_null',
        'htmlword_columns',
        'mediawiki_structure_or_data',
        'mediawiki_caption',
        'pdf_report_title',
        'pdf_structure_or_data',
        'odt_structure_or_data',
        'odt_relation',
        'odt_comments',
        'odt_mime',
        'odt_columns',
        'odt_null',
        'codegen_structure_or_data',
        'codegen_format',
        'excel_null',
        'excel_removeCRLF',
        'excel_columns',
        'excel_edition',
        'excel_structure_or_data',
        'yaml_structure_or_data',
        'ods_null',
        'ods_structure_or_data',
        'ods_columns',
        'json_structure_or_data',
        'xml_structure_or_data',
        'xml_export_functions',
        'xml_export_procedures',
        'xml_export_tables',
        'xml_export_triggers',
        'xml_export_views',
        'xml_export_contents',
        'texytext_structure_or_data',
        'texytext_columns',
        'texytext_null',
        'phparray_structure_or_data',
        'sql_include_comments',
        'sql_header_comment',
        'sql_dates',
        'sql_relation',
        'sql_mime',
        'sql_use_transaction',
        'sql_disable_fk',
        'sql_compatibility',
        'sql_structure_or_data',
        'sql_create_database',
        'sql_drop_table',
        'sql_procedure_function',
        'sql_create_table_statements',
        'sql_if_not_exists',
        'sql_auto_increment',
        'sql_backquotes',
        'sql_truncate',
        'sql_delayed',
        'sql_ignore',
        'sql_type',
        'sql_insert_syntax',
        'sql_max_query_size',
        'sql_hex_for_blob',
        'sql_utc_time',
        'csv_separator',
        'csv_enclosed',
        'csv_escaped',
        'csv_terminated',
        'csv_null',
        'csv_removeCRLF',
        'csv_columns',
        'csv_structure_or_data',
        // csv_replace should have been here but we use it directly from $_POST
        'latex_caption',
        'latex_structure_or_data',
        'latex_structure_caption',
        'latex_structure_continued_caption',
        'latex_structure_label',
        'latex_relation',
        'latex_comments',
        'latex_mime',
        'latex_columns',
        'latex_data_caption',
        'latex_data_continued_caption',
        'latex_data_label',
        'latex_null'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

// sanitize this parameter which will be used below in a file inclusion
$what = PMA_securePath($what);

PMA_Util::checkParameters(array('what', 'export_type'));

// export class instance, not array of properties, as before
$export_plugin = PMA_getPlugin(
    "export",
    $what,
    'libraries/plugins/export/',
    array(
        'export_type' => $export_type,
        'single_table' => isset($single_table)
    )
);

// Backward compatbility
$type = $what;

// Check export type
if (! isset($export_plugin)) {
    PMA_fatalError(__('Bad type!'));
}

/**
 * valid compression methods
 */
$compression_methods = array(
    'zip',
    'gzip',
    'bzip2',
);

/**
 * init and variable checking
 */
$compression = false;
$onserver = false;
$save_on_server = false;
$buffer_needed = false;

// Is it a quick or custom export?
if ($_REQUEST['quick_or_custom'] == 'quick') {
    $quick_export = true;
} else {
    $quick_export = false;
}

if ($_REQUEST['output_format'] == 'astext') {
    $asfile = false;
} else {
    $asfile = true;
    if (in_array($_REQUEST['compression'], $compression_methods)) {
        $compression = $_REQUEST['compression'];
        $buffer_needed = true;
    }
    if (($quick_export && ! empty($_REQUEST['quick_export_onserver']))
        || (! $quick_export && ! empty($_REQUEST['onserver']))
    ) {
        if ($quick_export) {
            $onserver = $_REQUEST['quick_export_onserver'];
        } else {
            $onserver = $_REQUEST['onserver'];
        }
        // Will we save dump on server?
        $save_on_server = ! empty($cfg['SaveDir']) && $onserver;
    }
}

// Does export require to be into file?
if ($export_plugin->getProperties()->getForceFile() != null && ! $asfile) {
    $message = PMA_Message::error(
        __('Selected export type has to be saved in file!')
    );
    if ($export_type == 'server') {
        $active_page = 'server_export.php';
        include 'server_export.php';
    } elseif ($export_type == 'database') {
        $active_page = 'db_export.php';
        include 'db_export.php';
    } else {
        $active_page = 'tbl_export.php';
        include 'tbl_export.php';
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
    PMA_fatalError(__('Bad parameters!'));
}

/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit($cfg['ExecTimeLimit']);
if (! empty($cfg['MemoryLimit'])) {
    @ini_set('memory_limit', $cfg['MemoryLimit']);
}

// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We send fake headers to avoid browser timeout when buffering
$time_start = time();


/**
 * Detect ob_gzhandler
 *
 * @return bool
 */
function PMA_isGzHandlerEnabled()
{
    return in_array('ob_gzhandler', ob_list_handlers());
}

/**
 * Detect whether gzencode is needed; it might not be needed if
 * the server is already compressing by itself 
 *
 * @return bool Whether gzencode is needed 
 */
function PMA_gzencodeNeeded()
{
    if (@function_exists('gzencode')
        && ! @ini_get('zlib.output_compression')
        // Here, we detect Apache's mod_deflate so we bet that
        // this module is active for this instance of phpMyAdmin
        // and therefore, will gzip encode the content
        && ! (function_exists('apache_get_modules')
            && in_array('mod_deflate', apache_get_modules()))
        && ! PMA_isGzHandlerEnabled()
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Output handler for all exports, if needed buffering, it stores data into
 * $dump_buffer, otherwise it prints thems out.
 *
 * @param string $line the insert statement
 *
 * @return bool Whether output succeeded
 */
function PMA_exportOutputHandler($line)
{
    global $time_start, $dump_buffer, $dump_buffer_len, $save_filename;

    // Kanji encoding convert feature
    if ($GLOBALS['output_kanji_conversion']) {
        $line = PMA_kanji_str_conv(
            $line,
            $GLOBALS['knjenc'],
            isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : ''
        );
    }
    // If we have to buffer data, we will perform everything at once at the end
    if ($GLOBALS['buffer_needed']) {

        $dump_buffer .= $line;
        if ($GLOBALS['onfly_compression']) {

            $dump_buffer_len += strlen($line);

            if ($dump_buffer_len > $GLOBALS['memory_limit']) {
                if ($GLOBALS['output_charset_conversion']) {
                    $dump_buffer = PMA_convert_string(
                        'utf-8',
                        $GLOBALS['charset_of_file'],
                        $dump_buffer
                    );
                }
                // as bzipped
                if ($GLOBALS['compression'] == 'bzip2'
                    && @function_exists('bzcompress')
                ) {
                    $dump_buffer = bzcompress($dump_buffer);
                } elseif ($GLOBALS['compression'] == 'gzip'
                     && PMA_gzencodeNeeded() 
                ) {
                    // as a gzipped file
                    // without the optional parameter level because it bugs
                    $dump_buffer = gzencode($dump_buffer);
                }
                if ($GLOBALS['save_on_server']) {
                    $write_result = @fwrite($GLOBALS['file_handle'], $dump_buffer);
                    if (! $write_result || ($write_result != strlen($dump_buffer))) {
                        $GLOBALS['message'] = PMA_Message::error(
                            __('Insufficient space to save the file %s.')
                        );
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
                $line = PMA_convert_string(
                    'utf-8',
                    $GLOBALS['charset_of_file'],
                    $line
                );
            }
            if ($GLOBALS['save_on_server'] && strlen($line) > 0) {
                $write_result = @fwrite($GLOBALS['file_handle'], $line);
                if (! $write_result || ($write_result != strlen($line))) {
                    $GLOBALS['message'] = PMA_Message::error(
                        __('Insufficient space to save the file %s.')
                    );
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

// Defines the default <CR><LF> format.
// For SQL always use \n as MySQL wants this on all platforms.
if ($what == 'sql') {
    $crlf = "\n";
} else {
    $crlf = PMA_Util::whichCrlf();
}

$output_kanji_conversion = function_exists('PMA_kanji_str_conv') && $type != 'xls';

// Do we need to convert charset?
$output_charset_conversion = $asfile
    && $GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE
    && isset($charset_of_file) && $charset_of_file != 'utf-8'
    && $type != 'xls';

// Use on the fly compression?
$onfly_compression = $GLOBALS['cfg']['CompressOnFly']
    && ($compression == 'gzip' || $compression == 'bzip2');
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
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_server_filename_template',
                'Export/file_template_server',
                $filename_template
            );
        }
    } elseif ($export_type == 'database') {
        if (isset($remember_template)) {
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_db_filename_template',
                'Export/file_template_database',
                $filename_template
            );
        }
    } else {
        if (isset($remember_template)) {
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_table_filename_template',
                'Export/file_template_table',
                $filename_template
            );
        }
    }
    $filename = PMA_Util::expandUserString($filename_template);
    // remove dots in filename (coming from either the template or already
    // part of the filename) to avoid a remote code execution vulnerability
    $filename = PMA_sanitizeFilename($filename, $replaceDots = true);

    // Grab basic dump extension and mime type
    // Check if the user already added extension;
    // get the substring where the extension would be if it was included
    $extension_start_pos = strlen($filename) - strlen(
        $export_plugin->getProperties()->getExtension()
    ) - 1;
    $user_extension = substr($filename, $extension_start_pos, strlen($filename));
    $required_extension = "." . $export_plugin->getProperties()->getExtension();
    if (strtolower($user_extension) != $required_extension) {
        $filename  .= $required_extension;
    }
    $mime_type  = $export_plugin->getProperties()->getMimeType();

    // If dump is going to be compressed, set correct mime_type and add
    // compression to extension
    if ($compression == 'bzip2') {
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
    $save_filename = PMA_Util::userDir($cfg['SaveDir'])
        . preg_replace('@[/\\\\]@', '_', $filename);
    unset($message);
    if (file_exists($save_filename)
        && ((! $quick_export && empty($onserverover))
        || ($quick_export
        && $_REQUEST['quick_export_onserverover'] != 'saveitover'))
    ) {
        $message = PMA_Message::error(
            __('File %s already exists on server, change filename or check overwrite option.')
        );
        $message->addParam($save_filename);
    } else {
        if (is_file($save_filename) && ! is_writable($save_filename)) {
            $message = PMA_Message::error(
                __('The web server does not have permission to save the file %s.')
            );
            $message->addParam($save_filename);
        } else {
            if (! $file_handle = @fopen($save_filename, 'w')) {
                $message = PMA_Message::error(
                    __('The web server does not have permission to save the file %s.')
                );
                $message->addParam($save_filename);
            }
        }
    }
    if (isset($message)) {
        if ($export_type == 'server') {
            $active_page = 'server_export.php';
            include 'server_export.php';
        } elseif ($export_type == 'database') {
            $active_page = 'db_export.php';
            include 'db_export.php';
        } else {
            $active_page = 'tbl_export.php';
            include 'tbl_export.php';
        }
        exit();
    }
}

/**
 * Send headers depending on whether the user chose to download a dump file
 * or not
 */
if (! $save_on_server) {
    if ($asfile) {
        // Download
        // (avoid rewriting data containing HTML with anchors and forms;
        // this was reported to happen under Plesk)
        @ini_set('url_rewriter.tags', '');
        $filename = PMA_sanitizeFilename($filename);

        PMA_downloadHeader($filename, $mime_type);
    } else {
        // HTML
        if ($export_type == 'database') {
            $num_tables = count($tables);
            if ($num_tables == 0) {
                $message = PMA_Message::error(__('No tables found in database.'));
                $active_page = 'db_export.php';
                include 'db_export.php';
                exit();
            }
        }
        $backup_cfgServer = $cfg['Server'];
        $cfg['Server'] = $backup_cfgServer;
        unset($backup_cfgServer);
        echo "\n" . '<div style="text-align: ' . $cell_align_left . '">' . "\n";
        //echo '    <pre>' . "\n";

        /**
         * Displays a back button with all the $_REQUEST data in the URL
         * (store in a variable to also display after the textarea)
         */
        $back_button = '<p>[ <a href="';
        if ($export_type == 'server') {
            $back_button .= 'server_export.php?' . PMA_generate_common_url();
        } elseif ($export_type == 'database') {
            $back_button .= 'db_export.php?' . PMA_generate_common_url($db);
        } else {
            $back_button .= 'tbl_export.php?' . PMA_generate_common_url($db, $table);
        }

        // Convert the multiple select elements from an array to a string
        if ($export_type == 'server' && isset($_REQUEST['db_select'])) {
            $_REQUEST['db_select'] = implode(",", $_REQUEST['db_select']);
        } elseif ($export_type == 'database' && isset($_REQUEST['table_select'])) {
            $_REQUEST['table_select'] = implode(",", $_REQUEST['table_select']);
        }

        foreach ($_REQUEST as $name => $value) {
            $back_button .= '&amp;' . urlencode($name) . '=' . urlencode($value);
        }
        $back_button .= '&amp;repopulate=1">Back</a> ]</p>';

        echo $back_button;
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
    if (! $export_plugin->exportHeader($db)) {
        break;
    }

    // Will we need relation & co. setup?
    $do_relation = isset($GLOBALS[$what . '_relation']);
    $do_comments = isset($GLOBALS[$what . '_include_comments']);
    $do_mime     = isset($GLOBALS[$what . '_mime']);
    if ($do_relation || $do_comments || $do_mime) {
        $cfgRelation = PMA_getRelationsParam();
    }
    if ($do_mime) {
        include_once 'libraries/transformations.lib.php';
    }

    // Include dates in export?
    $do_dates = isset($GLOBALS[$what . '_dates']);

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
            if (isset($tmp_select)
                && strpos(' ' . $tmp_select, '|' . $current_db . '|')
            ) {
                if (! $export_plugin->exportDBHeader($current_db)) {
                    break 2;
                }
                if (! $export_plugin->exportDBCreate($current_db)) {
                    break 2;
                }
                if (method_exists($export_plugin, 'exportRoutines')
                    && strpos($GLOBALS['sql_structure_or_data'], 'structure') !== false
                    && isset($GLOBALS['sql_procedure_function'])
                ) {
                    $export_plugin->exportRoutines($current_db);
                }

                $tables = PMA_DBI_get_tables($current_db);
                $views = array();
                foreach ($tables as $table) {
                    // if this is a view, collect it for later;
                    // views must be exported after the tables
                    $is_view = PMA_Table::isView($current_db, $table);
                    if ($is_view) {
                        $views[] = $table;
                    }
                    if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                        || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
                    ) {
                        // for a view, export a stand-in definition of the table
                        // to resolve view dependencies
                        if (! $export_plugin->exportStructure(
                            $current_db, $table, $crlf, $err_url,
                            $is_view ? 'stand_in' : 'create_table', $export_type,
                            $do_relation, $do_comments, $do_mime, $do_dates
                        )) {
                            break 3;
                        }
                    }
                    // if this is a view or a merge table, don't export data
                    if (($GLOBALS[$what . '_structure_or_data'] == 'data'
                        || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data')
                        && ! ($is_view || PMA_Table::isMerge($current_db, $table))
                    ) {
                        $local_query  = 'SELECT * FROM ' . PMA_Util::backquote($current_db)
                            . '.' . PMA_Util::backquote($table);
                        if (! $export_plugin->exportData($current_db, $table, $crlf, $err_url, $local_query)) {
                            break 3;
                        }
                    }
                    // now export the triggers (needs to be done after the data
                    // because triggers can modify already imported tables)
                    if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                        || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
                    ) {
                        if (! $export_plugin->exportStructure(
                            $current_db, $table, $crlf, $err_url,
                            'triggers', $export_type,
                            $do_relation, $do_comments, $do_mime, $do_dates
                        )) {
                            break 2;
                        }
                    }
                }
                foreach ($views as $view) {
                    // no data export for a view
                    if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                        || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
                    ) {
                        if (! $export_plugin->exportStructure(
                            $current_db, $view, $crlf, $err_url,
                            'create_view', $export_type,
                            $do_relation, $do_comments, $do_mime, $do_dates
                        )) {
                            break 3;
                        }
                    }
                }
                if (! $export_plugin->exportDBFooter($current_db)) {
                    break 2;
                }
            }
        }
    } elseif ($export_type == 'database') {
        if (! $export_plugin->exportDBHeader($db)) {
            break;
        }
        if (! $export_plugin->exportDBCreate($db)) {
            break;
        }

        if (method_exists($export_plugin, 'exportRoutines')
            && strpos($GLOBALS['sql_structure_or_data'], 'structure') !== false
            && isset($GLOBALS['sql_procedure_function'])
        ) {
            $export_plugin->exportRoutines($db);
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
            if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
            ) {
                // for a view, export a stand-in definition of the table
                // to resolve view dependencies
                if (! $export_plugin->exportStructure(
                    $db, $table, $crlf, $err_url,
                    $is_view ? 'stand_in' : 'create_table', $export_type,
                    $do_relation, $do_comments, $do_mime, $do_dates
                )) {
                    break 2;
                }
            }
            // if this is a view or a merge table, don't export data
            if (($GLOBALS[$what . '_structure_or_data'] == 'data'
                || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data')
                && ! ($is_view || PMA_Table::isMerge($db, $table))
            ) {
                $local_query  = 'SELECT * FROM ' . PMA_Util::backquote($db)
                    . '.' . PMA_Util::backquote($table);
                if (! $export_plugin->exportData($db, $table, $crlf, $err_url, $local_query)) {
                    break 2;
                }
            }
            // now export the triggers (needs to be done after the data because
            // triggers can modify already imported tables)
            if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
            ) {
                if (! $export_plugin->exportStructure(
                    $db, $table, $crlf, $err_url,
                    'triggers', $export_type,
                    $do_relation, $do_comments, $do_mime, $do_dates
                )) {
                    break 2;
                }
            }
        }
        foreach ($views as $view) {
            // no data export for a view
            if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
                || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
            ) {
                if (! $export_plugin->exportStructure(
                    $db, $view, $crlf, $err_url,
                    'create_view', $export_type,
                    $do_relation, $do_comments, $do_mime, $do_dates
                )) {
                    break 2;
                }
            }
        }

        if (! $export_plugin->exportDBFooter($db)) {
            break;
        }
    } else {
        if (! $export_plugin->exportDBHeader($db)) {
            break;
        }
        // We export just one table
        // $allrows comes from the form when "Dump all rows" has been selected
        if (isset($allrows) && $allrows == '0' && $limit_to > 0 && $limit_from >= 0) {
            $add_query  = ' LIMIT '
                        . (($limit_from > 0) ? $limit_from . ', ' : '')
                        . $limit_to;
        } else {
            $add_query  = '';
        }

        $is_view = PMA_Table::isView($db, $table);
        if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
            || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
        ) {
            if (! $export_plugin->exportStructure(
                $db, $table, $crlf, $err_url,
                $is_view ? 'create_view' : 'create_table', $export_type,
                $do_relation, $do_comments, $do_mime, $do_dates
            )) {
                break;
            }
        }
        // If this is an export of a single view, we have to export data;
        // for example, a PDF report
        // if it is a merge table, no data is exported
        if (($GLOBALS[$what . '_structure_or_data'] == 'data'
            || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data')
            && ! PMA_Table::isMerge($db, $table)
        ) {
            if (! empty($sql_query)) {
                // only preg_replace if needed
                if (! empty($add_query)) {
                    // remove trailing semicolon before adding a LIMIT
                    $sql_query = preg_replace('%;\s*$%', '', $sql_query);
                }
                $local_query = $sql_query . $add_query;
                PMA_DBI_select_db($db);
            } else {
                $local_query  = 'SELECT * FROM ' . PMA_Util::backquote($db)
                    . '.' . PMA_Util::backquote($table) . $add_query;
            }
            if (! $export_plugin->exportData($db, $table, $crlf, $err_url, $local_query)) {
                break;
            }
        }
        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if ($GLOBALS[$what . '_structure_or_data'] == 'structure'
            || $GLOBALS[$what . '_structure_or_data'] == 'structure_and_data'
        ) {
            if (! $export_plugin->exportStructure(
                $db, $table, $crlf, $err_url,
                'triggers', $export_type,
                $do_relation, $do_comments, $do_mime, $do_dates
            )) {
                break 2;
            }
        }
        if (! $export_plugin->exportDBFooter($db)) {
            break;
        }
    }
    if (! $export_plugin->exportFooter()) {
        break;
    }

} while (false);
// End of fake loop

if ($save_on_server && isset($message)) {
    if ($export_type == 'server') {
        $active_page = 'server_export.php';
        include 'server_export.php';
    } elseif ($export_type == 'database') {
        $active_page = 'db_export.php';
        include 'db_export.php';
    } else {
        $active_page = 'tbl_export.php';
        include 'tbl_export.php';
    }
    exit();
}

/**
 * Send the dump as a file...
 */
if (! empty($asfile)) {
    // Convert the charset if required.
    if ($output_charset_conversion) {
        $dump_buffer = PMA_convert_string(
            'utf-8',
            $GLOBALS['charset_of_file'],
            $dump_buffer
        );
    }

    // Do the compression
    // 1. as a zipped file
    if ($compression == 'zip') {
        if (@function_exists('gzcompress')) {
            $zipfile = new ZipFile();
            $zipfile->addFile($dump_buffer, substr($filename, 0, -4));
            $dump_buffer = $zipfile->file();
        }
    } elseif ($compression == 'bzip2') {
        // 2. as a bzipped file
        if (@function_exists('bzcompress')) {
            $dump_buffer = bzcompress($dump_buffer);
        }
    } elseif ($compression == 'gzip' && PMA_gzencodeNeeded()) {
        // 3. as a gzipped file
        // without the optional parameter level because it bugs
            $dump_buffer = gzencode($dump_buffer);
    }

    /* If we saved on server, we have to close file now */
    if ($save_on_server) {
        $write_result = @fwrite($file_handle, $dump_buffer);
        fclose($file_handle);
        if (strlen($dump_buffer) > 0
            && (! $write_result || ($write_result != strlen($dump_buffer)))
        ) {
            $message = new PMA_Message(
                __('Insufficient space to save the file %s.'),
                PMA_Message::ERROR,
                $save_filename
            );
        } else {
            $message = new PMA_Message(
                __('Dump has been saved to file %s.'),
                PMA_Message::SUCCESS,
                $save_filename
            );
        }

        if ($export_type == 'server') {
            $active_page = 'server_export.php';
            include_once 'server_export.php';
        } elseif ($export_type == 'database') {
            $active_page = 'db_export.php';
            include_once 'db_export.php';
        } else {
            $active_page = 'tbl_export.php';
            include_once 'tbl_export.php';
        }
        exit();
    } else {
        PMA_Response::getInstance()->disable();
        echo $dump_buffer;
    }
} else {
    /**
     * Displays the dump...
     *
     * Close the html tags and add the footers if dump is displayed on screen
     */
    echo '</textarea>' . "\n"
       . '    </form>' . "\n";
    echo $back_button;

    echo "\n";
    echo '</div>' . "\n";
    echo "\n";
?>
<script type="text/javascript">
//<![CDATA[
    var $body = $("body");
    $("#textSQLDUMP")
        .width($body.width() - 50)
        .height($body.height() - 100);
//]]>
</script>
<?php
} // end if
?>
