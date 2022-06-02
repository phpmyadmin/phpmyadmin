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
        $GLOBALS['containerBuilder'] = $GLOBALS['containerBuilder'] ?? null;
        $GLOBALS['export_type'] = $GLOBALS['export_type'] ?? null;
        $GLOBALS['filename_template'] = $GLOBALS['filename_template'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['compression'] = $GLOBALS['compression'] ?? null;
        $GLOBALS['crlf'] = $GLOBALS['crlf'] ?? null;
        $GLOBALS['asfile'] = $GLOBALS['asfile'] ?? null;
        $GLOBALS['buffer_needed'] = $GLOBALS['buffer_needed'] ?? null;
        $GLOBALS['save_on_server'] = $GLOBALS['save_on_server'] ?? null;
        $GLOBALS['file_handle'] = $GLOBALS['file_handle'] ?? null;
        $GLOBALS['separate_files'] = $GLOBALS['separate_files'] ?? null;
        $GLOBALS['output_charset_conversion'] = $GLOBALS['output_charset_conversion'] ?? null;
        $GLOBALS['output_kanji_conversion'] = $GLOBALS['output_kanji_conversion'] ?? null;
        $GLOBALS['what'] = $GLOBALS['what'] ?? null;
        $GLOBALS['export_plugin'] = $GLOBALS['export_plugin'] ?? null;
        $GLOBALS['single_table'] = $GLOBALS['single_table'] ?? null;
        $GLOBALS['compression_methods'] = $GLOBALS['compression_methods'] ?? null;
        $GLOBALS['onserver'] = $GLOBALS['onserver'] ?? null;
        $GLOBALS['save_filename'] = $GLOBALS['save_filename'] ?? null;
        $GLOBALS['filename'] = $GLOBALS['filename'] ?? null;
        $GLOBALS['quick_export'] = $GLOBALS['quick_export'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['table_select'] = $GLOBALS['table_select'] ?? null;
        $GLOBALS['aliases'] = $GLOBALS['aliases'] ?? null;
        $GLOBALS['time_start'] = $GLOBALS['time_start'] ?? null;
        $GLOBALS['charset'] = $GLOBALS['charset'] ?? null;
        $GLOBALS['remember_template'] = $GLOBALS['remember_template'] ?? null;
        $GLOBALS['mime_type'] = $GLOBALS['mime_type'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['active_page'] = $GLOBALS['active_page'] ?? null;
        $GLOBALS['do_relation'] = $GLOBALS['do_relation'] ?? null;
        $GLOBALS['do_comments'] = $GLOBALS['do_comments'] ?? null;
        $GLOBALS['do_mime'] = $GLOBALS['do_mime'] ?? null;
        $GLOBALS['do_dates'] = $GLOBALS['do_dates'] ?? null;
        $GLOBALS['whatStrucOrData'] = $GLOBALS['whatStrucOrData'] ?? null;
        $GLOBALS['db_select'] = $GLOBALS['db_select'] ?? null;
        $GLOBALS['table_structure'] = $GLOBALS['table_structure'] ?? null;
        $GLOBALS['table_data'] = $GLOBALS['table_data'] ?? null;
        $GLOBALS['lock_tables'] = $GLOBALS['lock_tables'] ?? null;
        $GLOBALS['allrows'] = $GLOBALS['allrows'] ?? null;
        $GLOBALS['limit_to'] = $GLOBALS['limit_to'] ?? null;
        $GLOBALS['limit_from'] = $GLOBALS['limit_from'] ?? null;

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

        $this->setGlobalsFromRequest($postParams);

        // sanitize this parameter which will be used below in a file inclusion
        $GLOBALS['what'] = Core::securePath($whatParam);

        $this->checkParameters(['what', 'export_type']);

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

                echo $this->export->getHtmlForDisplayedExportHeader(
                    $GLOBALS['export_type'],
                    $GLOBALS['db'],
                    $GLOBALS['table']
                );
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
            echo $this->export->getHtmlForDisplayedExportFooter(
                $GLOBALS['export_type'],
                $GLOBALS['db'],
                $GLOBALS['table']
            );

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

    /**
     * Please keep the parameters in order of their appearance in the form.
     * Some of these parameters are not used.
     *
     * @param mixed[] $postParams
     */
    private function setGlobalsFromRequest(array $postParams): void
    {
        if (isset($postParams['single_table'])) {
            $GLOBALS['single_table'] = $postParams['single_table'];
        }

        if (isset($postParams['export_type'])) {
            $GLOBALS['export_type'] = $postParams['export_type'];
        }

        if (isset($postParams['export_method'])) {
            $GLOBALS['export_method'] = $postParams['export_method'];
        }

        if (isset($postParams['quick_or_custom'])) {
            $GLOBALS['quick_or_custom'] = $postParams['quick_or_custom'];
        }

        if (isset($postParams['db_select'])) {
            $GLOBALS['db_select'] = $postParams['db_select'];
        }

        if (isset($postParams['table_select'])) {
            $GLOBALS['table_select'] = $postParams['table_select'];
        }

        if (isset($postParams['table_structure'])) {
            $GLOBALS['table_structure'] = $postParams['table_structure'];
        }

        if (isset($postParams['table_data'])) {
            $GLOBALS['table_data'] = $postParams['table_data'];
        }

        if (isset($postParams['limit_to'])) {
            $GLOBALS['limit_to'] = $postParams['limit_to'];
        }

        if (isset($postParams['limit_from'])) {
            $GLOBALS['limit_from'] = $postParams['limit_from'];
        }

        if (isset($postParams['allrows'])) {
            $GLOBALS['allrows'] = $postParams['allrows'];
        }

        if (isset($postParams['lock_tables'])) {
            $GLOBALS['lock_tables'] = $postParams['lock_tables'];
        }

        if (isset($postParams['output_format'])) {
            $GLOBALS['output_format'] = $postParams['output_format'];
        }

        if (isset($postParams['filename_template'])) {
            $GLOBALS['filename_template'] = $postParams['filename_template'];
        }

        if (isset($postParams['maxsize'])) {
            $GLOBALS['maxsize'] = $postParams['maxsize'];
        }

        if (isset($postParams['remember_template'])) {
            $GLOBALS['remember_template'] = $postParams['remember_template'];
        }

        if (isset($postParams['charset'])) {
            $GLOBALS['charset'] = $postParams['charset'];
        }

        if (isset($postParams['compression'])) {
            $GLOBALS['compression'] = $postParams['compression'];
        }

        if (isset($postParams['as_separate_files'])) {
            $GLOBALS['as_separate_files'] = $postParams['as_separate_files'];
        }

        if (isset($postParams['knjenc'])) {
            $GLOBALS['knjenc'] = $postParams['knjenc'];
        }

        if (isset($postParams['xkana'])) {
            $GLOBALS['xkana'] = $postParams['xkana'];
        }

        if (isset($postParams['htmlword_structure_or_data'])) {
            $GLOBALS['htmlword_structure_or_data'] = $postParams['htmlword_structure_or_data'];
        }

        if (isset($postParams['htmlword_null'])) {
            $GLOBALS['htmlword_null'] = $postParams['htmlword_null'];
        }

        if (isset($postParams['htmlword_columns'])) {
            $GLOBALS['htmlword_columns'] = $postParams['htmlword_columns'];
        }

        if (isset($postParams['mediawiki_headers'])) {
            $GLOBALS['mediawiki_headers'] = $postParams['mediawiki_headers'];
        }

        if (isset($postParams['mediawiki_structure_or_data'])) {
            $GLOBALS['mediawiki_structure_or_data'] = $postParams['mediawiki_structure_or_data'];
        }

        if (isset($postParams['mediawiki_caption'])) {
            $GLOBALS['mediawiki_caption'] = $postParams['mediawiki_caption'];
        }

        if (isset($postParams['pdf_structure_or_data'])) {
            $GLOBALS['pdf_structure_or_data'] = $postParams['pdf_structure_or_data'];
        }

        if (isset($postParams['odt_structure_or_data'])) {
            $GLOBALS['odt_structure_or_data'] = $postParams['odt_structure_or_data'];
        }

        if (isset($postParams['odt_relation'])) {
            $GLOBALS['odt_relation'] = $postParams['odt_relation'];
        }

        if (isset($postParams['odt_comments'])) {
            $GLOBALS['odt_comments'] = $postParams['odt_comments'];
        }

        if (isset($postParams['odt_mime'])) {
            $GLOBALS['odt_mime'] = $postParams['odt_mime'];
        }

        if (isset($postParams['odt_columns'])) {
            $GLOBALS['odt_columns'] = $postParams['odt_columns'];
        }

        if (isset($postParams['odt_null'])) {
            $GLOBALS['odt_null'] = $postParams['odt_null'];
        }

        if (isset($postParams['codegen_structure_or_data'])) {
            $GLOBALS['codegen_structure_or_data'] = $postParams['codegen_structure_or_data'];
        }

        if (isset($postParams['codegen_format'])) {
            $GLOBALS['codegen_format'] = $postParams['codegen_format'];
        }

        if (isset($postParams['excel_null'])) {
            $GLOBALS['excel_null'] = $postParams['excel_null'];
        }

        if (isset($postParams['excel_removeCRLF'])) {
            $GLOBALS['excel_removeCRLF'] = $postParams['excel_removeCRLF'];
        }

        if (isset($postParams['excel_columns'])) {
            $GLOBALS['excel_columns'] = $postParams['excel_columns'];
        }

        if (isset($postParams['excel_edition'])) {
            $GLOBALS['excel_edition'] = $postParams['excel_edition'];
        }

        if (isset($postParams['excel_structure_or_data'])) {
            $GLOBALS['excel_structure_or_data'] = $postParams['excel_structure_or_data'];
        }

        if (isset($postParams['yaml_structure_or_data'])) {
            $GLOBALS['yaml_structure_or_data'] = $postParams['yaml_structure_or_data'];
        }

        if (isset($postParams['ods_null'])) {
            $GLOBALS['ods_null'] = $postParams['ods_null'];
        }

        if (isset($postParams['ods_structure_or_data'])) {
            $GLOBALS['ods_structure_or_data'] = $postParams['ods_structure_or_data'];
        }

        if (isset($postParams['ods_columns'])) {
            $GLOBALS['ods_columns'] = $postParams['ods_columns'];
        }

        if (isset($postParams['json_structure_or_data'])) {
            $GLOBALS['json_structure_or_data'] = $postParams['json_structure_or_data'];
        }

        if (isset($postParams['json_pretty_print'])) {
            $GLOBALS['json_pretty_print'] = $postParams['json_pretty_print'];
        }

        if (isset($postParams['json_unicode'])) {
            $GLOBALS['json_unicode'] = $postParams['json_unicode'];
        }

        if (isset($postParams['xml_structure_or_data'])) {
            $GLOBALS['xml_structure_or_data'] = $postParams['xml_structure_or_data'];
        }

        if (isset($postParams['xml_export_events'])) {
            $GLOBALS['xml_export_events'] = $postParams['xml_export_events'];
        }

        if (isset($postParams['xml_export_functions'])) {
            $GLOBALS['xml_export_functions'] = $postParams['xml_export_functions'];
        }

        if (isset($postParams['xml_export_procedures'])) {
            $GLOBALS['xml_export_procedures'] = $postParams['xml_export_procedures'];
        }

        if (isset($postParams['xml_export_tables'])) {
            $GLOBALS['xml_export_tables'] = $postParams['xml_export_tables'];
        }

        if (isset($postParams['xml_export_triggers'])) {
            $GLOBALS['xml_export_triggers'] = $postParams['xml_export_triggers'];
        }

        if (isset($postParams['xml_export_views'])) {
            $GLOBALS['xml_export_views'] = $postParams['xml_export_views'];
        }

        if (isset($postParams['xml_export_contents'])) {
            $GLOBALS['xml_export_contents'] = $postParams['xml_export_contents'];
        }

        if (isset($postParams['texytext_structure_or_data'])) {
            $GLOBALS['texytext_structure_or_data'] = $postParams['texytext_structure_or_data'];
        }

        if (isset($postParams['texytext_columns'])) {
            $GLOBALS['texytext_columns'] = $postParams['texytext_columns'];
        }

        if (isset($postParams['texytext_null'])) {
            $GLOBALS['texytext_null'] = $postParams['texytext_null'];
        }

        if (isset($postParams['phparray_structure_or_data'])) {
            $GLOBALS['phparray_structure_or_data'] = $postParams['phparray_structure_or_data'];
        }

        if (isset($postParams['sql_include_comments'])) {
            $GLOBALS['sql_include_comments'] = $postParams['sql_include_comments'];
        }

        if (isset($postParams['sql_header_comment'])) {
            $GLOBALS['sql_header_comment'] = $postParams['sql_header_comment'];
        }

        if (isset($postParams['sql_dates'])) {
            $GLOBALS['sql_dates'] = $postParams['sql_dates'];
        }

        if (isset($postParams['sql_relation'])) {
            $GLOBALS['sql_relation'] = $postParams['sql_relation'];
        }

        if (isset($postParams['sql_mime'])) {
            $GLOBALS['sql_mime'] = $postParams['sql_mime'];
        }

        if (isset($postParams['sql_use_transaction'])) {
            $GLOBALS['sql_use_transaction'] = $postParams['sql_use_transaction'];
        }

        if (isset($postParams['sql_disable_fk'])) {
            $GLOBALS['sql_disable_fk'] = $postParams['sql_disable_fk'];
        }

        if (isset($postParams['sql_compatibility'])) {
            $GLOBALS['sql_compatibility'] = $postParams['sql_compatibility'];
        }

        if (isset($postParams['sql_structure_or_data'])) {
            $GLOBALS['sql_structure_or_data'] = $postParams['sql_structure_or_data'];
        }

        if (isset($postParams['sql_create_database'])) {
            $GLOBALS['sql_create_database'] = $postParams['sql_create_database'];
        }

        if (isset($postParams['sql_drop_table'])) {
            $GLOBALS['sql_drop_table'] = $postParams['sql_drop_table'];
        }

        if (isset($postParams['sql_procedure_function'])) {
            $GLOBALS['sql_procedure_function'] = $postParams['sql_procedure_function'];
        }

        if (isset($postParams['sql_create_table'])) {
            $GLOBALS['sql_create_table'] = $postParams['sql_create_table'];
        }

        if (isset($postParams['sql_create_view'])) {
            $GLOBALS['sql_create_view'] = $postParams['sql_create_view'];
        }

        if (isset($postParams['sql_create_trigger'])) {
            $GLOBALS['sql_create_trigger'] = $postParams['sql_create_trigger'];
        }

        if (isset($postParams['sql_view_current_user'])) {
            $GLOBALS['sql_view_current_user'] = $postParams['sql_view_current_user'];
        }

        if (isset($postParams['sql_simple_view_export'])) {
            $GLOBALS['sql_simple_view_export'] = $postParams['sql_simple_view_export'];
        }

        if (isset($postParams['sql_if_not_exists'])) {
            $GLOBALS['sql_if_not_exists'] = $postParams['sql_if_not_exists'];
        }

        if (isset($postParams['sql_or_replace_view'])) {
            $GLOBALS['sql_or_replace_view'] = $postParams['sql_or_replace_view'];
        }

        if (isset($postParams['sql_auto_increment'])) {
            $GLOBALS['sql_auto_increment'] = $postParams['sql_auto_increment'];
        }

        if (isset($postParams['sql_backquotes'])) {
            $GLOBALS['sql_backquotes'] = $postParams['sql_backquotes'];
        }

        if (isset($postParams['sql_truncate'])) {
            $GLOBALS['sql_truncate'] = $postParams['sql_truncate'];
        }

        if (isset($postParams['sql_delayed'])) {
            $GLOBALS['sql_delayed'] = $postParams['sql_delayed'];
        }

        if (isset($postParams['sql_ignore'])) {
            $GLOBALS['sql_ignore'] = $postParams['sql_ignore'];
        }

        if (isset($postParams['sql_type'])) {
            $GLOBALS['sql_type'] = $postParams['sql_type'];
        }

        if (isset($postParams['sql_insert_syntax'])) {
            $GLOBALS['sql_insert_syntax'] = $postParams['sql_insert_syntax'];
        }

        if (isset($postParams['sql_max_query_size'])) {
            $GLOBALS['sql_max_query_size'] = $postParams['sql_max_query_size'];
        }

        if (isset($postParams['sql_hex_for_binary'])) {
            $GLOBALS['sql_hex_for_binary'] = $postParams['sql_hex_for_binary'];
        }

        if (isset($postParams['sql_utc_time'])) {
            $GLOBALS['sql_utc_time'] = $postParams['sql_utc_time'];
        }

        if (isset($postParams['sql_drop_database'])) {
            $GLOBALS['sql_drop_database'] = $postParams['sql_drop_database'];
        }

        if (isset($postParams['sql_views_as_tables'])) {
            $GLOBALS['sql_views_as_tables'] = $postParams['sql_views_as_tables'];
        }

        if (isset($postParams['sql_metadata'])) {
            $GLOBALS['sql_metadata'] = $postParams['sql_metadata'];
        }

        if (isset($postParams['csv_separator'])) {
            $GLOBALS['csv_separator'] = $postParams['csv_separator'];
        }

        if (isset($postParams['csv_enclosed'])) {
            $GLOBALS['csv_enclosed'] = $postParams['csv_enclosed'];
        }

        if (isset($postParams['csv_escaped'])) {
            $GLOBALS['csv_escaped'] = $postParams['csv_escaped'];
        }

        if (isset($postParams['csv_terminated'])) {
            $GLOBALS['csv_terminated'] = $postParams['csv_terminated'];
        }

        if (isset($postParams['csv_null'])) {
            $GLOBALS['csv_null'] = $postParams['csv_null'];
        }

        if (isset($postParams['csv_removeCRLF'])) {
            $GLOBALS['csv_removeCRLF'] = $postParams['csv_removeCRLF'];
        }

        if (isset($postParams['csv_columns'])) {
            $GLOBALS['csv_columns'] = $postParams['csv_columns'];
        }

        if (isset($postParams['csv_structure_or_data'])) {
            $GLOBALS['csv_structure_or_data'] = $postParams['csv_structure_or_data'];
        }

        if (isset($postParams['latex_caption'])) {
            $GLOBALS['latex_caption'] = $postParams['latex_caption'];
        }

        if (isset($postParams['latex_structure_or_data'])) {
            $GLOBALS['latex_structure_or_data'] = $postParams['latex_structure_or_data'];
        }

        if (isset($postParams['latex_structure_caption'])) {
            $GLOBALS['latex_structure_caption'] = $postParams['latex_structure_caption'];
        }

        if (isset($postParams['latex_structure_continued_caption'])) {
            $GLOBALS['latex_structure_continued_caption'] = $postParams['latex_structure_continued_caption'];
        }

        if (isset($postParams['latex_structure_label'])) {
            $GLOBALS['latex_structure_label'] = $postParams['latex_structure_label'];
        }

        if (isset($postParams['latex_relation'])) {
            $GLOBALS['latex_relation'] = $postParams['latex_relation'];
        }

        if (isset($postParams['latex_comments'])) {
            $GLOBALS['latex_comments'] = $postParams['latex_comments'];
        }

        if (isset($postParams['latex_mime'])) {
            $GLOBALS['latex_mime'] = $postParams['latex_mime'];
        }

        if (isset($postParams['latex_columns'])) {
            $GLOBALS['latex_columns'] = $postParams['latex_columns'];
        }

        if (isset($postParams['latex_data_caption'])) {
            $GLOBALS['latex_data_caption'] = $postParams['latex_data_caption'];
        }

        if (isset($postParams['latex_data_continued_caption'])) {
            $GLOBALS['latex_data_continued_caption'] = $postParams['latex_data_continued_caption'];
        }

        if (isset($postParams['latex_data_label'])) {
            $GLOBALS['latex_data_label'] = $postParams['latex_data_label'];
        }

        if (isset($postParams['latex_null'])) {
            $GLOBALS['latex_null'] = $postParams['latex_null'];
        }

        if (! isset($postParams['aliases'])) {
            return;
        }

        $GLOBALS['aliases'] = $postParams['aliases'];
    }
}
