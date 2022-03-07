<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Misc;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
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

use const PHP_EOL;

final class ExportController extends AbstractController
{
    /** @var Export */
    private $export;

    public function __construct(ResponseRenderer $response, Template $template, Export $export)
    {
        parent::__construct($response, $template);
        $this->export = $export;
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var array<string, string> $postParams */
        $postParams = $request->getParsedBody();

        /** @var string $whatParam */
        $whatParam = $request->getParsedBodyParam('what', '');
        /** @var string|null $quickOrCustom */
        $quickOrCustom = $request->getParsedBodyParam('quick_or_custom');
        /** @var string|null $outputFormat */
        $outputFormat = $request->getParsedBodyParam('output_format');
        /** @var string $compressionParam */
        $compressionParam = $request->getParsedBodyParam('compression', '');
        /** @var string|null $asSeparateFiles */
        $asSeparateFiles = $request->getParsedBodyParam('as_separate_files');
        /** @var string|null $quickExportOnServer */
        $quickExportOnServer = $request->getParsedBodyParam('quick_export_onserver');
        /** @var string|null $onServerParam */
        $onServerParam = $request->getParsedBodyParam('onserver');
        /** @var array|null $aliasesParam */
        $aliasesParam = $request->getParsedBodyParam('aliases');
        /** @var string|null $structureOrDataForced */
        $structureOrDataForced = $request->getParsedBodyParam('structure_or_data_forced');

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
        $allowedPostParams = [
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

        foreach ($allowedPostParams as $param) {
            if (! isset($postParams[$param])) {
                continue;
            }

            $GLOBALS[$param] = $postParams[$param];
        }

        Util::checkParameters(['what', 'export_type']);

        // sanitize this parameter which will be used below in a file inclusion
        $GLOBALS['what'] = Core::securePath($whatParam);

        // export class instance, not array of properties, as before
        $GLOBALS['export_plugin'] = Plugins::getPlugin('export', $GLOBALS['what'], [
            'export_type' => (string) $GLOBALS['export_type'],
            'single_table' => isset($GLOBALS['single_table']),
        ]);

        // Check export type
        if (empty($GLOBALS['export_plugin'])) {
            Core::fatalError(__('Bad type!'));
        }

        /**
         * valid compression methods
         */
        $GLOBALS['compression_methods'] = [];
        if ($GLOBALS['cfg']['ZipDump'] && function_exists('gzcompress')) {
            $GLOBALS['compression_methods'][] = 'zip';
        }

        if ($GLOBALS['cfg']['GZipDump'] && function_exists('gzencode')) {
            $GLOBALS['compression_methods'][] = 'gzip';
        }

        /**
         * init and variable checking
         */
        $GLOBALS['compression'] = '';
        $GLOBALS['onserver'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['back_button'] = '';
        $GLOBALS['refreshButton'] = '';
        $GLOBALS['save_filename'] = '';
        $GLOBALS['file_handle'] = '';
        $GLOBALS['errorUrl'] = '';
        $GLOBALS['filename'] = '';
        $GLOBALS['separate_files'] = '';

        // Is it a quick or custom export?
        if ($quickOrCustom === 'quick') {
            $GLOBALS['quick_export'] = true;
        } else {
            $GLOBALS['quick_export'] = false;
        }

        if ($outputFormat === 'astext') {
            $GLOBALS['asfile'] = false;
        } else {
            $GLOBALS['asfile'] = true;
            if ($asSeparateFiles && $compressionParam === 'zip') {
                $GLOBALS['separate_files'] = $asSeparateFiles;
            }

            if (in_array($compressionParam, $GLOBALS['compression_methods'])) {
                $GLOBALS['compression'] = $compressionParam;
                $GLOBALS['buffer_needed'] = true;
            }

            if (($GLOBALS['quick_export'] && $quickExportOnServer) || (! $GLOBALS['quick_export'] && $onServerParam)) {
                if ($GLOBALS['quick_export']) {
                    $GLOBALS['onserver'] = $quickExportOnServer;
                } else {
                    $GLOBALS['onserver'] = $onServerParam;
                }

                // Will we save dump on server?
                $GLOBALS['save_on_server'] = ! empty($GLOBALS['cfg']['SaveDir']);
            }
        }

        /**
         * If we are sending the export file (as opposed to just displaying it
         * as text), we have to bypass the usual PhpMyAdmin\Response mechanism
         */
        if ($outputFormat === 'sendit' && ! $GLOBALS['save_on_server']) {
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

        $GLOBALS['tables'] = [];
        // Generate error url and check for needed variables
        if ($GLOBALS['export_type'] === 'server') {
            $GLOBALS['errorUrl'] = Url::getFromRoute('/server/export');
        } elseif ($GLOBALS['export_type'] === 'database' && strlen($GLOBALS['db']) > 0) {
            $GLOBALS['errorUrl'] = Url::getFromRoute('/database/export', ['db' => $GLOBALS['db']]);
            // Check if we have something to export
            $GLOBALS['tables'] = $GLOBALS['table_select'] ?? [];
        } elseif ($GLOBALS['export_type'] === 'table' && strlen($GLOBALS['db']) > 0 && strlen($GLOBALS['table']) > 0) {
            $GLOBALS['errorUrl'] = Url::getFromRoute('/table/export', [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
            ]);
        } elseif ($GLOBALS['export_type'] === 'raw') {
            $GLOBALS['errorUrl'] = Url::getFromRoute('/server/export', ['sql_query' => $GLOBALS['sql_query']]);
        } else {
            Core::fatalError(__('Bad parameters!'));
        }

        // Merge SQL Query aliases with Export aliases from
        // export page, Export page aliases are given more
        // preference over SQL Query aliases.
        $parser = new Parser($GLOBALS['sql_query']);
        $GLOBALS['aliases'] = [];
        if (! empty($parser->statements[0]) && ($parser->statements[0] instanceof SelectStatement)) {
            $GLOBALS['aliases'] = Misc::getAliases($parser->statements[0], $GLOBALS['db']);
        }

        if (! empty($aliasesParam)) {
            $GLOBALS['aliases'] = $this->export->mergeAliases($GLOBALS['aliases'], $aliasesParam);
            $_SESSION['tmpval']['aliases'] = $aliasesParam;
        }

        /**
         * Increase time limit for script execution and initializes some variables
         */
        Util::setTimeLimit();
        if (! empty($GLOBALS['cfg']['MemoryLimit'])) {
            ini_set('memory_limit', $GLOBALS['cfg']['MemoryLimit']);
        }

        register_shutdown_function([$this->export, 'shutdown']);
        // Start with empty buffer
        $this->export->dumpBuffer = '';
        $this->export->dumpBufferLength = 0;

        // Array of dump buffers - used in separate file exports
        $this->export->dumpBufferObjects = [];

        // We send fake headers to avoid browser timeout when buffering
        $GLOBALS['time_start'] = time();

        // Defines the default <CR><LF> format.
        // For SQL always use \n as MySQL wants this on all platforms.
        if ($GLOBALS['what'] === 'sql') {
            $GLOBALS['crlf'] = "\n";
        } else {
            $GLOBALS['crlf'] = PHP_EOL;
        }

        $GLOBALS['output_kanji_conversion'] = Encoding::canConvertKanji();

        // Do we need to convert charset?
        $GLOBALS['output_charset_conversion'] = $GLOBALS['asfile']
            && Encoding::isSupported()
            && isset($GLOBALS['charset']) && $GLOBALS['charset'] !== 'utf-8';

        // Use on the fly compression?
        $GLOBALS['onfly_compression'] = $GLOBALS['cfg']['CompressOnFly']
            && $GLOBALS['compression'] === 'gzip';
        if ($GLOBALS['onfly_compression']) {
            $GLOBALS['memory_limit'] = $this->export->getMemoryLimit();
        }

        // Generate filename and mime type if needed
        if ($GLOBALS['asfile']) {
            if (empty($GLOBALS['remember_template'])) {
                $GLOBALS['remember_template'] = '';
            }

            [$GLOBALS['filename'], $GLOBALS['mime_type']] = $this->export->getFilenameAndMimetype(
                $GLOBALS['export_type'],
                $GLOBALS['remember_template'],
                $GLOBALS['export_plugin'],
                $GLOBALS['compression'],
                $GLOBALS['filename_template']
            );
        } else {
            $GLOBALS['mime_type'] = '';
        }

        // For raw query export, filename will be export.extension
        if ($GLOBALS['export_type'] === 'raw') {
            [$GLOBALS['filename']] = $this->export->getFinalFilenameAndMimetypeForFilename(
                $GLOBALS['export_plugin'],
                $GLOBALS['compression'],
                'export'
            );
        }

        // Open file on server if needed
        if ($GLOBALS['save_on_server']) {
            [
                $GLOBALS['save_filename'],
                $GLOBALS['message'],
                $GLOBALS['file_handle'],
            ] = $this->export->openFile($GLOBALS['filename'], $GLOBALS['quick_export']);

            // problem opening export file on server?
            if (! empty($GLOBALS['message'])) {
                $this->export->showPage($GLOBALS['export_type']);

                return;
            }
        } else {
            /**
             * Send headers depending on whether the user chose to download a dump file
             * or not
             */
            if ($GLOBALS['asfile']) {
                // Download
                // (avoid rewriting data containing HTML with anchors and forms;
                // this was reported to happen under Plesk)
                ini_set('url_rewriter.tags', '');
                $GLOBALS['filename'] = Sanitize::sanitizeFilename($GLOBALS['filename']);

                Core::downloadHeader($GLOBALS['filename'], $GLOBALS['mime_type']);
            } else {
                // HTML
                if ($GLOBALS['export_type'] === 'database') {
                    $GLOBALS['num_tables'] = count($GLOBALS['tables']);
                    if ($GLOBALS['num_tables'] === 0) {
                        $GLOBALS['message'] = Message::error(
                            __('No tables found in database.')
                        );
                        $GLOBALS['active_page'] = Url::getFromRoute('/database/export');
                        /** @var DatabaseExportController $controller */
                        $controller = $GLOBALS['containerBuilder']->get(DatabaseExportController::class);
                        $controller();
                        exit;
                    }
                }

                [
                    $html,
                    $GLOBALS['back_button'],
                    $GLOBALS['refreshButton'],
                ] = $this->export->getHtmlForDisplayedExportHeader(
                    $GLOBALS['export_type'],
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
                echo $html;
                unset($html);
            }
        }

        try {
            // Re - initialize
            $this->export->dumpBuffer = '';
            $this->export->dumpBufferLength = 0;

            // Add possibly some comments to export
            if (! $GLOBALS['export_plugin']->exportHeader()) {
                throw new ExportException('Failure during header export.');
            }

            // Will we need relation & co. setup?
            $GLOBALS['do_relation'] = isset($GLOBALS[$GLOBALS['what'] . '_relation']);
            $GLOBALS['do_comments'] = isset($GLOBALS[$GLOBALS['what'] . '_include_comments'])
                || isset($GLOBALS[$GLOBALS['what'] . '_comments']);
            $GLOBALS['do_mime'] = isset($GLOBALS[$GLOBALS['what'] . '_mime']);

            // Include dates in export?
            $GLOBALS['do_dates'] = isset($GLOBALS[$GLOBALS['what'] . '_dates']);

            $GLOBALS['whatStrucOrData'] = $GLOBALS[$GLOBALS['what'] . '_structure_or_data'];

            if ($GLOBALS['export_type'] === 'raw') {
                $GLOBALS['whatStrucOrData'] = 'raw';
            }

            /**
             * Builds the dump
             */
            if ($GLOBALS['export_type'] === 'server') {
                if (! isset($GLOBALS['db_select'])) {
                    $GLOBALS['db_select'] = '';
                }

                $this->export->exportServer(
                    $GLOBALS['db_select'],
                    $GLOBALS['whatStrucOrData'],
                    $GLOBALS['export_plugin'],
                    $GLOBALS['crlf'],
                    $GLOBALS['errorUrl'],
                    $GLOBALS['export_type'],
                    $GLOBALS['do_relation'],
                    $GLOBALS['do_comments'],
                    $GLOBALS['do_mime'],
                    $GLOBALS['do_dates'],
                    $GLOBALS['aliases'],
                    $GLOBALS['separate_files']
                );
            } elseif ($GLOBALS['export_type'] === 'database') {
                if (! isset($GLOBALS['table_structure']) || ! is_array($GLOBALS['table_structure'])) {
                    $GLOBALS['table_structure'] = [];
                }

                if (! isset($GLOBALS['table_data']) || ! is_array($GLOBALS['table_data'])) {
                    $GLOBALS['table_data'] = [];
                }

                if ($structureOrDataForced) {
                    $GLOBALS['table_structure'] = $GLOBALS['tables'];
                    $GLOBALS['table_data'] = $GLOBALS['tables'];
                }

                if (isset($GLOBALS['lock_tables'])) {
                    $this->export->lockTables($GLOBALS['db'], $GLOBALS['tables'], 'READ');
                    try {
                        $this->export->exportDatabase(
                            $GLOBALS['db'],
                            $GLOBALS['tables'],
                            $GLOBALS['whatStrucOrData'],
                            $GLOBALS['table_structure'],
                            $GLOBALS['table_data'],
                            $GLOBALS['export_plugin'],
                            $GLOBALS['crlf'],
                            $GLOBALS['errorUrl'],
                            $GLOBALS['export_type'],
                            $GLOBALS['do_relation'],
                            $GLOBALS['do_comments'],
                            $GLOBALS['do_mime'],
                            $GLOBALS['do_dates'],
                            $GLOBALS['aliases'],
                            $GLOBALS['separate_files']
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportDatabase(
                        $GLOBALS['db'],
                        $GLOBALS['tables'],
                        $GLOBALS['whatStrucOrData'],
                        $GLOBALS['table_structure'],
                        $GLOBALS['table_data'],
                        $GLOBALS['export_plugin'],
                        $GLOBALS['crlf'],
                        $GLOBALS['errorUrl'],
                        $GLOBALS['export_type'],
                        $GLOBALS['do_relation'],
                        $GLOBALS['do_comments'],
                        $GLOBALS['do_mime'],
                        $GLOBALS['do_dates'],
                        $GLOBALS['aliases'],
                        $GLOBALS['separate_files']
                    );
                }
            } elseif ($GLOBALS['export_type'] === 'raw') {
                Export::exportRaw(
                    $GLOBALS['whatStrucOrData'],
                    $GLOBALS['export_plugin'],
                    $GLOBALS['crlf'],
                    $GLOBALS['errorUrl'],
                    $GLOBALS['sql_query'],
                    $GLOBALS['export_type']
                );
            } else {
                // We export just one table
                // $allrows comes from the form when "Dump all rows" has been selected
                if (! isset($GLOBALS['allrows'])) {
                    $GLOBALS['allrows'] = '';
                }

                if (! isset($GLOBALS['limit_to'])) {
                    $GLOBALS['limit_to'] = '0';
                }

                if (! isset($GLOBALS['limit_from'])) {
                    $GLOBALS['limit_from'] = '0';
                }

                if (isset($GLOBALS['lock_tables'])) {
                    try {
                        $this->export->lockTables($GLOBALS['db'], [$GLOBALS['table']], 'READ');
                        $this->export->exportTable(
                            $GLOBALS['db'],
                            $GLOBALS['table'],
                            $GLOBALS['whatStrucOrData'],
                            $GLOBALS['export_plugin'],
                            $GLOBALS['crlf'],
                            $GLOBALS['errorUrl'],
                            $GLOBALS['export_type'],
                            $GLOBALS['do_relation'],
                            $GLOBALS['do_comments'],
                            $GLOBALS['do_mime'],
                            $GLOBALS['do_dates'],
                            $GLOBALS['allrows'],
                            $GLOBALS['limit_to'],
                            $GLOBALS['limit_from'],
                            $GLOBALS['sql_query'],
                            $GLOBALS['aliases']
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportTable(
                        $GLOBALS['db'],
                        $GLOBALS['table'],
                        $GLOBALS['whatStrucOrData'],
                        $GLOBALS['export_plugin'],
                        $GLOBALS['crlf'],
                        $GLOBALS['errorUrl'],
                        $GLOBALS['export_type'],
                        $GLOBALS['do_relation'],
                        $GLOBALS['do_comments'],
                        $GLOBALS['do_mime'],
                        $GLOBALS['do_dates'],
                        $GLOBALS['allrows'],
                        $GLOBALS['limit_to'],
                        $GLOBALS['limit_from'],
                        $GLOBALS['sql_query'],
                        $GLOBALS['aliases']
                    );
                }
            }

            if (! $GLOBALS['export_plugin']->exportFooter()) {
                throw new ExportException('Failure during footer export.');
            }
        } catch (ExportException $e) {
            // Ignore
        }

        if ($GLOBALS['save_on_server'] && ! empty($GLOBALS['message'])) {
            $this->export->showPage($GLOBALS['export_type']);

            return;
        }

        /**
         * Send the dump as a file...
         */
        if (empty($GLOBALS['asfile'])) {
            echo $this->export->getHtmlForDisplayedExportFooter($GLOBALS['back_button'], $GLOBALS['refreshButton']);

            return;
        }

        // Convert the charset if required.
        if ($GLOBALS['output_charset_conversion']) {
            $this->export->dumpBuffer = Encoding::convertString(
                'utf-8',
                $GLOBALS['charset'],
                $this->export->dumpBuffer
            );
        }

        // Compression needed?
        if ($GLOBALS['compression']) {
            if (! empty($GLOBALS['separate_files'])) {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBufferObjects,
                    $GLOBALS['compression'],
                    $GLOBALS['filename']
                );
            } else {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBuffer,
                    $GLOBALS['compression'],
                    $GLOBALS['filename']
                );
            }
        }

        /* If we saved on server, we have to close file now */
        if ($GLOBALS['save_on_server']) {
            $GLOBALS['message'] = $this->export->closeFile(
                $GLOBALS['file_handle'],
                $this->export->dumpBuffer,
                $GLOBALS['save_filename']
            );
            $this->export->showPage($GLOBALS['export_type']);

            return;
        }

        echo $this->export->dumpBuffer;
    }
}
