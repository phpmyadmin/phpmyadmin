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
if (!defined('TESTSUITE')) {
    /**
     * If we are sending the export file (as opposed to just displaying it
     * as text), we have to bypass the usual PMA_Response mechanism
     */
    if (isset($_POST['output_format']) && $_POST['output_format'] == 'sendit') {
        define('PMA_BYPASS_GET_INSTANCE', 1);
    }
    include_once 'libraries/common.inc.php';
    include_once 'libraries/zip.lib.php';
    include_once 'libraries/plugin_interface.lib.php';
    include_once 'libraries/export.lib.php';

    //check if it's the GET request to check export time out
    if (isset($_GET['check_time_out'])) {
        if (isset($_SESSION['pma_export_error'])) {
            $err = $_SESSION['pma_export_error'];
            unset($_SESSION['pma_export_error']);
            echo "timeout";
        } else {
            echo "success";
        }
        exit;
    }
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
            'table_structure',
            'table_data',
            'limit_to',
            'limit_from',
            'allrows',
            'lock_tables',
            'output_format',
            'filename_template',
            'maxsize',
            'remember_template',
            'charset',
            'compression',
            'as_separate_files',
            'knjenc',
            'xkana',
            'htmlword_structure_or_data',
            'htmlword_null',
            'htmlword_columns',
            'mediawiki_headers',
            'mediawiki_structure_or_data',
            'mediawiki_caption',
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
            'json_pretty_print',
            'xml_structure_or_data',
            'xml_export_events',
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
            'sql_create_table',
            'sql_create_view',
            'sql_create_trigger',
            'sql_if_not_exists',
            'sql_auto_increment',
            'sql_backquotes',
            'sql_truncate',
            'sql_delayed',
            'sql_ignore',
            'sql_type',
            'sql_insert_syntax',
            'sql_max_query_size',
            'sql_hex_for_binary',
            'sql_utc_time',
            'sql_drop_database',
            'sql_views_as_tables',
            'sql_metadata',
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
            'latex_null',
            'aliases'
    );

    foreach ($post_params as $one_post_param) {
        if (isset($_POST[$one_post_param])) {
            $GLOBALS[$one_post_param] = $_POST[$one_post_param];
        }
    }

    $table = $GLOBALS['table'];
    // sanitize this parameter which will be used below in a file inclusion
    $what = PMA_securePath($_POST['what']);

    PMA_Util::checkParameters(array('what', 'export_type'));

    // export class instance, not array of properties, as before
    /* @var $export_plugin ExportPlugin */
    $export_plugin = PMA_getPlugin(
        "export",
        $what,
        'libraries/plugins/export/',
        array(
            'export_type' => $export_type,
            'single_table' => isset($single_table)
        )
    );

    // Backward compatibility
    $type = $what;

    // Check export type
    if (empty($export_plugin)) {
        PMA_fatalError(__('Bad type!'));
    }

    /**
     * valid compression methods
     */
    $compression_methods = array(
        'zip',
        'gzip'
    );

    /**
     * init and variable checking
     */
    $compression = false;
    $onserver = false;
    $save_on_server = false;
    $buffer_needed = false;
    $back_button = '';
    $save_filename = '';
    $file_handle = '';
    $err_url = '';
    $filename = '';
    $separate_files = '';

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
        if (isset($_REQUEST['as_separate_files'])
            && ! empty($_REQUEST['as_separate_files'])
        ) {
            if (isset($_REQUEST['compression'])
                && ! empty($_REQUEST['compression'])
                && $_REQUEST['compression'] == 'zip'
            ) {
                $separate_files = $_REQUEST['as_separate_files'];
            }
        }
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

    // Generate error url and check for needed variables
    /** @var PMA_String $pmaString */
    $pmaString = $GLOBALS['PMA_String'];
    if ($export_type == 'server') {
        $err_url = 'server_export.php' . PMA_URL_getCommon();
    } elseif ($export_type == 'database'
        && /*overload*/mb_strlen($db)
    ) {
        $err_url = 'db_export.php' . PMA_URL_getCommon(array('db' => $db));
        // Check if we have something to export
        if (isset($table_select)) {
            $tables = $table_select;
        } else {
            $tables = array();
        }
    } elseif ($export_type == 'table' && /*overload*/mb_strlen($db)
        && /*overload*/mb_strlen($table)
    ) {
        $err_url = 'tbl_export.php' . PMA_URL_getCommon(
            array(
                'db' => $db, 'table' => $table
            )
        );
    } else {
        PMA_fatalError(__('Bad parameters!'));
    }

    // Merge SQL Query aliases with Export aliases from
    // export page, Export page aliases are given more
    // preference over SQL Query aliases.
    $parser = new SqlParser\Parser($sql_query);
    $aliases = array();
    if ((!empty($parser->statements[0]))
        && ($parser->statements[0] instanceof SqlParser\Statements\SelectStatement)
    ) {
        if (!empty($_REQUEST['aliases'])) {
            $aliases = PMA_mergeAliases(
                SqlParser\Utils\Misc::getAliases($parser->statements[0], $db),
                $_REQUEST['aliases']
            );
            $_SESSION['tmpval']['aliases'] = $_REQUEST['aliases'];
        } else {
            $aliases = SqlParser\Utils\Misc::getAliases($parser->statements[0], $db);
        }
    }

    /**
     * Increase time limit for script execution and initializes some variables
     */
    @set_time_limit($cfg['ExecTimeLimit']);
    if (! empty($cfg['MemoryLimit'])) {
        @ini_set('memory_limit', $cfg['MemoryLimit']);
    }
    register_shutdown_function('PMA_shutdownDuringExport');
    // Start with empty buffer
    $dump_buffer = '';
    $dump_buffer_len = 0;

    // Array of dump_buffers - used in separate file exports
    $dump_buffer_objects = array();

    // We send fake headers to avoid browser timeout when buffering
    $time_start = time();

    // Defines the default <CR><LF> format.
    // For SQL always use \n as MySQL wants this on all platforms.
    if ($what == 'sql') {
        $crlf = "\n";
    } else {
        $crlf = PMA_Util::whichCrlf();
    }

    $output_kanji_conversion = function_exists('PMA_Kanji_strConv')
        && $type != 'xls';

    // Do we need to convert charset?
    $output_charset_conversion = $asfile
        && $GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE
        && isset($charset) && $charset != 'utf-8'
        && $type != 'xls';

    // Use on the fly compression?
    $GLOBALS['onfly_compression'] = $GLOBALS['cfg']['CompressOnFly']
        && $compression == 'gzip';
    if ($GLOBALS['onfly_compression']) {
        $GLOBALS['memory_limit'] = PMA_getMemoryLimitForExport();
    }

    // Generate filename and mime type if needed
    if ($asfile) {
        if (empty($remember_template)) {
            $remember_template = '';
        }
        list($filename, $mime_type) = PMA_getExportFilenameAndMimetype(
            $export_type, $remember_template, $export_plugin, $compression,
            $filename_template
        );
    } else {
        $mime_type = '';
    }

    // Open file on server if needed
    if ($save_on_server) {
        list($save_filename, $message, $file_handle) = PMA_openExportFile(
            $filename, $quick_export
        );

        // problem opening export file on server?
        if (! empty($message)) {
            PMA_showExportPage($db, $table, $export_type);
        }
    } else {
        /**
         * Send headers depending on whether the user chose to download a dump file
         * or not
         */
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
                    $message = PMA_Message::error(
                        __('No tables found in database.')
                    );
                    $active_page = 'db_export.php';
                    include 'db_export.php';
                    exit();
                }
            }
            list($html, $back_button) = PMA_getHtmlForDisplayedExportHeader(
                $export_type, $db, $table
            );
            echo $html;
            unset($html);
        } // end download
    }

    // Fake loop just to allow skip of remain of this code by break, I'd really
    // need exceptions here :-)
    do {

        // Add possibly some comments to export
        if (! $export_plugin->exportHeader()) {
            break;
        }
        // Re - initialize
        $dump_buffer = '';
        $dump_buffer_len = 0;

        // Will we need relation & co. setup?
        $do_relation = isset($GLOBALS[$what . '_relation']);
        $do_comments = isset($GLOBALS[$what . '_include_comments'])
            || isset($GLOBALS[$what . '_comments']) ;
        $do_mime     = isset($GLOBALS[$what . '_mime']);
        if ($do_relation || $do_comments || $do_mime) {
            $cfgRelation = PMA_getRelationsParam();
        }
        if ($do_mime) {
            include_once 'libraries/transformations.lib.php';
        }

        // Include dates in export?
        $do_dates = isset($GLOBALS[$what . '_dates']);

        $whatStrucOrData = $GLOBALS[$what . '_structure_or_data'];

        /**
         * Builds the dump
         */
        if ($export_type == 'server') {
            if (! isset($db_select)) {
                $db_select = '';
            }
            PMA_exportServer(
                $db_select, $whatStrucOrData, $export_plugin, $crlf, $err_url,
                $export_type, $do_relation, $do_comments, $do_mime, $do_dates,
                $aliases, $separate_files
            );
        } elseif ($export_type == 'database') {
            if (!isset($table_structure) || !is_array($table_structure)) {
                $table_structure = array();
            }
            if (!isset($table_data) || !is_array($table_data)) {
                $table_data = array();
            }
            if (!empty($_REQUEST['structure_or_data_forced'])) {
                $table_structure = $tables;
                $table_data = $tables;
            }
            if (isset($lock_tables)) {
                PMA_lockTables($db, $tables, "READ");
                try {
                    PMA_exportDatabase(
                        $db, $tables, $whatStrucOrData, $table_structure,
                        $table_data, $export_plugin, $crlf, $err_url, $export_type,
                        $do_relation, $do_comments, $do_mime, $do_dates, $aliases,
                        $separate_files
                    );
                    PMA_unlockTables();
                } catch (Exception $e) { // TODO use finally when PHP version is 5.5
                    PMA_unlockTables();
                    throw $e;
                }
            } else {
                PMA_exportDatabase(
                    $db, $tables, $whatStrucOrData, $table_structure, $table_data,
                    $export_plugin, $crlf, $err_url, $export_type, $do_relation,
                    $do_comments, $do_mime, $do_dates, $aliases, $separate_files
                );
            }
        } else {
            // We export just one table
            // $allrows comes from the form when "Dump all rows" has been selected
            if (! isset($allrows)) {
                $allrows = '';
            }
            if (! isset($limit_to)) {
                $limit_to = 0;
            }
            if (! isset($limit_from)) {
                $limit_from = 0;
            }
            if (isset($lock_tables)) {
                try {
                    PMA_lockTables($db, array($table), "READ");
                    PMA_exportTable(
                        $db, $table, $whatStrucOrData, $export_plugin, $crlf,
                        $err_url, $export_type, $do_relation, $do_comments,
                        $do_mime, $do_dates, $allrows, $limit_to, $limit_from,
                        $sql_query, $aliases
                    );
                    PMA_unlockTables();
                } catch (Exception $e) { // TODO use finally when PHP version is 5.5
                    PMA_unlockTables();
                    throw $e;
                }
            } else {
                PMA_exportTable(
                    $db, $table, $whatStrucOrData, $export_plugin, $crlf, $err_url,
                    $export_type, $do_relation, $do_comments, $do_mime, $do_dates,
                    $allrows, $limit_to, $limit_from, $sql_query, $aliases
                );
            }
        }
        if (! $export_plugin->exportFooter()) {
            break;
        }

    } while (false);
    // End of fake loop

    if ($save_on_server && ! empty($message)) {
        PMA_showExportPage($db, $table, $export_type);
    }

    /**
     * Send the dump as a file...
     */
    if (empty($asfile)) {
        echo PMA_getHtmlForDisplayedExportFooter($back_button);
        return;
    } // end if

    // Convert the charset if required.
    if ($output_charset_conversion) {
        $dump_buffer = PMA_convertString(
            'utf-8',
            $GLOBALS['charset'],
            $dump_buffer
        );
    }

    // Compression needed?
    if ($compression) {
        if (! empty($separate_files)) {
            $dump_buffer = PMA_compressExport(
                $dump_buffer_objects, $compression, $filename
            );
        } else {
            $dump_buffer = PMA_compressExport($dump_buffer, $compression, $filename);
        }

    }

    /* If we saved on server, we have to close file now */
    if ($save_on_server) {
        $message = PMA_closeExportFile(
            $file_handle, $dump_buffer, $save_filename
        );
        PMA_showExportPage($db, $table, $export_type);
    } else {
        echo $dump_buffer;
    }
}
