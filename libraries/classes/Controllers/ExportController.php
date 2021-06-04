<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Misc;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use const PHP_EOL;
use function count;
use function function_exists;
use function in_array;
use function ini_set;
use function is_array;
use function ob_end_clean;
use function ob_get_length;
use function ob_get_level;
use function register_shutdown_function;
use function strlen;
use function time;

final class ExportController extends AbstractController
{
    /** @var Export */
    private $export;

    /** @var Relation */
    private $relation;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template, Export $export, Relation $relation)
    {
        parent::__construct($response, $template);
        $this->export = $export;
        $this->relation = $relation;
    }

    public function index(): void
    {
        global $containerBuilder, $db, $export_type, $filename_template, $sql_query, $err_url, $message;
        global $compression, $crlf, $asfile, $buffer_needed, $save_on_server, $file_handle, $separate_files;
        global $output_charset_conversion, $output_kanji_conversion, $table, $what, $export_plugin, $single_table;
        global $compression_methods, $onserver, $back_button, $refreshButton, $save_filename, $filename;
        global $quick_export, $cfg, $tables, $table_select, $aliases, $dump_buffer, $dump_buffer_len;
        global $time_start, $charset, $remember_template, $mime_type, $num_tables, $dump_buffer_objects;
        global $active_page, $do_relation, $do_comments, $do_mime, $do_dates, $whatStrucOrData, $db_select;
        global $table_structure, $table_data, $lock_tables, $allrows, $limit_to, $limit_from;

        $this->addScriptFiles(['export_output.js']);

        /**
         * Sets globals from $_POST
         *
         * - Please keep the parameters in order of their appearance in the form
         * - Some of these parameters are not used, as the code below directly
         *   verifies from the superglobal $_POST or $_REQUEST
         * TODO: this should be removed to avoid passing user input to GLOBALS
         * without checking
         */
        $post_params = [
            'db',
            'table',
            'what',
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
            'json_unicode',
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
            'sql_view_current_user',
            'sql_simple_view_export',
            'sql_if_not_exists',
            'sql_or_replace_view',
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
            'aliases',
        ];

        foreach ($post_params as $one_post_param) {
            if (! isset($_POST[$one_post_param])) {
                continue;
            }

            $GLOBALS[$one_post_param] = $_POST[$one_post_param];
        }

        Util::checkParameters(['what', 'export_type']);

        // sanitize this parameter which will be used below in a file inclusion
        $what = Core::securePath($_POST['what']);

        // export class instance, not array of properties, as before
        /** @var ExportPlugin $export_plugin */
        $export_plugin = Plugins::getPlugin(
            'export',
            $what,
            'libraries/classes/Plugins/Export/',
            [
                'export_type' => $export_type,
                'single_table' => isset($single_table),
            ]
        );

        // Check export type
        if (empty($export_plugin)) {
            Core::fatalError(__('Bad type!'));
        }

        /**
         * valid compression methods
         */
        $compression_methods = [];
        if ($GLOBALS['cfg']['ZipDump'] && function_exists('gzcompress')) {
            $compression_methods[] = 'zip';
        }
        if ($GLOBALS['cfg']['GZipDump'] && function_exists('gzencode')) {
            $compression_methods[] = 'gzip';
        }

        /**
         * init and variable checking
         */
        $compression = '';
        $onserver = false;
        $save_on_server = false;
        $buffer_needed = false;
        $back_button = '';
        $refreshButton = '';
        $save_filename = '';
        $file_handle = '';
        $err_url = '';
        $filename = '';
        $separate_files = '';

        // Is it a quick or custom export?
        if (isset($_POST['quick_or_custom'])
            && $_POST['quick_or_custom'] === 'quick'
        ) {
            $quick_export = true;
        } else {
            $quick_export = false;
        }

        if ($_POST['output_format'] === 'astext') {
            $asfile = false;
        } else {
            $asfile = true;
            $selectedCompression = $_POST['compression'] ?? '';
            if (isset($_POST['as_separate_files'])
                && ! empty($_POST['as_separate_files'])
            ) {
                if (! empty($selectedCompression)
                    && $selectedCompression === 'zip'
                ) {
                    $separate_files = $_POST['as_separate_files'];
                }
            }
            if (in_array($selectedCompression, $compression_methods)) {
                $compression = $selectedCompression;
                $buffer_needed = true;
            }
            if (($quick_export && ! empty($_POST['quick_export_onserver']))
                || (! $quick_export && ! empty($_POST['onserver']))
            ) {
                if ($quick_export) {
                    $onserver = $_POST['quick_export_onserver'];
                } else {
                    $onserver = $_POST['onserver'];
                }
                // Will we save dump on server?
                $save_on_server = ! empty($cfg['SaveDir']) && $onserver;
            }
        }

        /**
         * If we are sending the export file (as opposed to just displaying it
         * as text), we have to bypass the usual PhpMyAdmin\Response mechanism
         */
        if (isset($_POST['output_format']) && $_POST['output_format'] === 'sendit' && ! $save_on_server) {
            $this->response->disable();
            //Disable all active buffers (see: ob_get_status(true) at this point)
            do {
                if (ob_get_length() > 0 || ob_get_level() > 0) {
                    $hasBuffer = ob_end_clean();
                } else {
                    $hasBuffer = false;
                }
            } while ($hasBuffer);
        }

        $tables = [];
        // Generate error url and check for needed variables
        if ($export_type === 'server') {
            $err_url = Url::getFromRoute('/server/export');
        } elseif ($export_type === 'database' && strlen($db) > 0) {
            $err_url = Url::getFromRoute('/database/export', ['db' => $db]);
            // Check if we have something to export
            if (isset($table_select)) {
                $tables = $table_select;
            } else {
                $tables = [];
            }
        } elseif ($export_type === 'table' && strlen($db) > 0 && strlen($table) > 0) {
            $err_url = Url::getFromRoute('/table/export', [
                'db' => $db,
                'table' => $table,
            ]);
        } elseif ($export_type === 'raw') {
            $err_url = Url::getFromRoute('/server/export', ['sql_query' => $sql_query]);
        } else {
            Core::fatalError(__('Bad parameters!'));
        }

        // Merge SQL Query aliases with Export aliases from
        // export page, Export page aliases are given more
        // preference over SQL Query aliases.
        $parser = new Parser($sql_query);
        $aliases = [];
        if (! empty($parser->statements[0])
            && ($parser->statements[0] instanceof SelectStatement)
        ) {
            $aliases = Misc::getAliases($parser->statements[0], $db);
        }
        if (! empty($_POST['aliases'])) {
            $aliases = $this->export->mergeAliases($aliases, $_POST['aliases']);
            $_SESSION['tmpval']['aliases'] = $_POST['aliases'];
        }

        /**
         * Increase time limit for script execution and initializes some variables
         */
        Util::setTimeLimit();
        if (! empty($cfg['MemoryLimit'])) {
            ini_set('memory_limit', $cfg['MemoryLimit']);
        }
        register_shutdown_function([$this->export, 'shutdown']);
        // Start with empty buffer
        $dump_buffer = '';
        $dump_buffer_len = 0;

        // Array of dump_buffers - used in separate file exports
        $dump_buffer_objects = [];

        // We send fake headers to avoid browser timeout when buffering
        $time_start = time();

        // Defines the default <CR><LF> format.
        // For SQL always use \n as MySQL wants this on all platforms.
        if ($what === 'sql') {
            $crlf = "\n";
        } else {
            $crlf = PHP_EOL;
        }

        $output_kanji_conversion = Encoding::canConvertKanji();

        // Do we need to convert charset?
        $output_charset_conversion = $asfile
            && Encoding::isSupported()
            && isset($charset) && $charset !== 'utf-8';

        // Use on the fly compression?
        $GLOBALS['onfly_compression'] = $GLOBALS['cfg']['CompressOnFly']
            && $compression === 'gzip';
        if ($GLOBALS['onfly_compression']) {
            $GLOBALS['memory_limit'] = $this->export->getMemoryLimit();
        }

        // Generate filename and mime type if needed
        if ($asfile) {
            if (empty($remember_template)) {
                $remember_template = '';
            }
            [$filename, $mime_type] = $this->export->getFilenameAndMimetype(
                $export_type,
                $remember_template,
                $export_plugin,
                $compression,
                $filename_template
            );
        } else {
            $mime_type = '';
        }

        // For raw query export, filename will be export.extension
        if ($export_type === 'raw') {
            [$filename] = $this->export->getFinalFilenameAndMimetypeForFilename(
                $export_plugin,
                $compression,
                'export'
            );
        }

        // Open file on server if needed
        if ($save_on_server) {
            [$save_filename, $message, $file_handle] = $this->export->openFile(
                $filename,
                $quick_export
            );

            // problem opening export file on server?
            if (! empty($message)) {
                $this->export->showPage($export_type);

                return;
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
                ini_set('url_rewriter.tags', '');
                $filename = Sanitize::sanitizeFilename($filename);

                Core::downloadHeader($filename, $mime_type);
            } else {
                // HTML
                if ($export_type === 'database') {
                    $num_tables = count($tables);
                    if ($num_tables === 0) {
                        $message = Message::error(
                            __('No tables found in database.')
                        );
                        $active_page = Url::getFromRoute('/database/export');
                        /** @var DatabaseExportController $controller */
                        $controller = $containerBuilder->get(DatabaseExportController::class);
                        $controller->index();
                        exit;
                    }
                }
                [$html, $back_button, $refreshButton] = $this->export->getHtmlForDisplayedExportHeader(
                    $export_type,
                    $db,
                    $table
                );
                echo $html;
                unset($html);
            }
        }

        try {
            // Re - initialize
            $dump_buffer = '';
            $dump_buffer_len = 0;

            // Add possibly some comments to export
            if (! $export_plugin->exportHeader()) {
                throw new ExportException('Failure during header export.');
            }

            // Will we need relation & co. setup?
            $do_relation = isset($GLOBALS[$what . '_relation']);
            $do_comments = isset($GLOBALS[$what . '_include_comments'])
                || isset($GLOBALS[$what . '_comments']);
            $do_mime     = isset($GLOBALS[$what . '_mime']);
            if ($do_relation || $do_comments || $do_mime) {
                $this->relation->getRelationsParam();
            }

            // Include dates in export?
            $do_dates = isset($GLOBALS[$what . '_dates']);

            $whatStrucOrData = $GLOBALS[$what . '_structure_or_data'];

            if ($export_type === 'raw') {
                $whatStrucOrData = 'raw';
            }

            /**
             * Builds the dump
             */
            if ($export_type === 'server') {
                if (! isset($db_select)) {
                    $db_select = '';
                }
                $this->export->exportServer(
                    $db_select,
                    $whatStrucOrData,
                    $export_plugin,
                    $crlf,
                    $err_url,
                    $export_type,
                    $do_relation,
                    $do_comments,
                    $do_mime,
                    $do_dates,
                    $aliases,
                    $separate_files
                );
            } elseif ($export_type === 'database') {
                if (! isset($table_structure) || ! is_array($table_structure)) {
                    $table_structure = [];
                }
                if (! isset($table_data) || ! is_array($table_data)) {
                    $table_data = [];
                }
                if (! empty($_POST['structure_or_data_forced'])) {
                    $table_structure = $tables;
                    $table_data = $tables;
                }
                if (isset($lock_tables)) {
                    $this->export->lockTables($db, $tables, 'READ');
                    try {
                        $this->export->exportDatabase(
                            $db,
                            $tables,
                            $whatStrucOrData,
                            $table_structure,
                            $table_data,
                            $export_plugin,
                            $crlf,
                            $err_url,
                            $export_type,
                            $do_relation,
                            $do_comments,
                            $do_mime,
                            $do_dates,
                            $aliases,
                            $separate_files
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportDatabase(
                        $db,
                        $tables,
                        $whatStrucOrData,
                        $table_structure,
                        $table_data,
                        $export_plugin,
                        $crlf,
                        $err_url,
                        $export_type,
                        $do_relation,
                        $do_comments,
                        $do_mime,
                        $do_dates,
                        $aliases,
                        $separate_files
                    );
                }
            } elseif ($export_type === 'raw') {
                Export::exportRaw(
                    $whatStrucOrData,
                    $export_plugin,
                    $crlf,
                    $err_url,
                    $sql_query,
                    $export_type
                );
            } else {
                // We export just one table
                // $allrows comes from the form when "Dump all rows" has been selected
                if (! isset($allrows)) {
                    $allrows = '';
                }
                if (! isset($limit_to)) {
                    $limit_to = '0';
                }
                if (! isset($limit_from)) {
                    $limit_from = '0';
                }
                if (isset($lock_tables)) {
                    try {
                        $this->export->lockTables($db, [$table], 'READ');
                        $this->export->exportTable(
                            $db,
                            $table,
                            $whatStrucOrData,
                            $export_plugin,
                            $crlf,
                            $err_url,
                            $export_type,
                            $do_relation,
                            $do_comments,
                            $do_mime,
                            $do_dates,
                            $allrows,
                            $limit_to,
                            $limit_from,
                            $sql_query,
                            $aliases
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportTable(
                        $db,
                        $table,
                        $whatStrucOrData,
                        $export_plugin,
                        $crlf,
                        $err_url,
                        $export_type,
                        $do_relation,
                        $do_comments,
                        $do_mime,
                        $do_dates,
                        $allrows,
                        $limit_to,
                        $limit_from,
                        $sql_query,
                        $aliases
                    );
                }
            }
            if (! $export_plugin->exportFooter()) {
                throw new ExportException('Failure during footer export.');
            }
        } catch (ExportException $e) {
            null; // Avoid phpcs error...
        }

        if ($save_on_server && ! empty($message)) {
            $this->export->showPage($export_type);

            return;
        }

        /**
         * Send the dump as a file...
         */
        if (empty($asfile)) {
            echo $this->export->getHtmlForDisplayedExportFooter($back_button, $refreshButton);

            return;
        }

        // Convert the charset if required.
        if ($output_charset_conversion) {
            $dump_buffer = Encoding::convertString(
                'utf-8',
                $GLOBALS['charset'],
                $dump_buffer
            );
        }

        // Compression needed?
        if ($compression) {
            if (! empty($separate_files)) {
                $dump_buffer = $this->export->compress(
                    $dump_buffer_objects,
                    $compression,
                    $filename
                );
            } else {
                $dump_buffer = $this->export->compress($dump_buffer, $compression, $filename);
            }
        }

        /* If we saved on server, we have to close file now */
        if ($save_on_server) {
            $message = $this->export->closeFile(
                $file_handle,
                $dump_buffer,
                $save_filename
            );
            $this->export->showPage($export_type);

            return;
        }

        echo $dump_buffer;
    }

    public function checkTimeOut(): void
    {
        $this->response->setAjax(true);

        if (isset($_SESSION['pma_export_error'])) {
            unset($_SESSION['pma_export_error']);
            $this->response->addJSON('message', 'timeout');

            return;
        }

        $this->response->addJSON('message', 'success');
    }
}
