<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function count;
use function function_exists;
use function in_array;
use function ini_set;
use function is_array;
use function register_shutdown_function;
use function time;

final class ExportController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Export $export,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['compression'] ??= null;
        $GLOBALS['asfile'] ??= null;
        $GLOBALS['buffer_needed'] ??= null;
        $GLOBALS['save_on_server'] ??= null;
        $GLOBALS['file_handle'] ??= null;
        $GLOBALS['output_charset_conversion'] ??= null;
        $GLOBALS['output_kanji_conversion'] ??= null;
        $GLOBALS['single_table'] ??= null;
        $GLOBALS['save_filename'] ??= null;
        $GLOBALS['table_select'] ??= null;
        $GLOBALS['time_start'] ??= null;
        $GLOBALS['charset'] ??= null;
        $GLOBALS['table_data'] ??= null;

        /** @var array<string, string> $postParams */
        $postParams = $request->getParsedBody();

        $quickOrCustom = $request->getParsedBodyParamAsStringOrNull('quick_or_custom');
        $outputFormat = $request->getParsedBodyParamAsStringOrNull('output_format');
        $compressionParam = $request->getParsedBodyParamAsString('compression', '');
        $asSeparateFiles = $request->getParsedBodyParamAsStringOrNull('as_separate_files');
        $quickExportOnServer = $request->getParsedBodyParamAsStringOrNull('quick_export_onserver');
        $onServerParam = $request->getParsedBodyParamAsStringOrNull('onserver');
        /** @var array|null $aliasesParam */
        $aliasesParam = $request->getParsedBodyParam('aliases');
        $structureOrDataForced = (bool) $request->getParsedBodyParamAsStringOrNull('structure_or_data_forced');
        $rememberTemplate = $request->getParsedBodyParamAsString('remember_template', '');
        $dbSelect = $request->getParsedBodyParam('db_select');
        $tableStructure = $request->getParsedBodyParam('table_structure');
        $lockTables = $request->hasBodyParam('lock_tables');

        $this->response->addScriptFiles(['export_output.js']);

        $this->setGlobalsFromRequest($postParams);

        // sanitize this parameter which will be used below in a file inclusion
        $what = Core::securePath($request->getParsedBodyParamAsString('what', ''));
        if ($what === '') {
            return $this->response->missingParameterError('what');
        }

        $exportType = ExportType::from($request->getParsedBodyParamAsString('export_type'));

        // export class instance, not array of properties, as before
        $exportPlugin = Plugins::getPlugin('export', $what, $exportType, isset($GLOBALS['single_table']));

        // Check export type
        if (! $exportPlugin instanceof ExportPlugin) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error(__('Bad type!'))->getDisplay());

            return $this->response->response();
        }

        $config = Config::getInstance();
        $exportPlugin->setExportOptions($request, $config->settings['Export']);

        /**
         * valid compression methods
         */
        $compressionMethods = [];
        if ($config->settings['ZipDump'] && function_exists('gzcompress')) {
            $compressionMethods[] = 'zip';
        }

        if ($config->settings['GZipDump'] && function_exists('gzencode')) {
            $compressionMethods[] = 'gzip';
        }

        /**
         * init and variable checking
         */
        $GLOBALS['compression'] = '';
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['save_filename'] = '';
        $GLOBALS['file_handle'] = '';
        $filename = '';
        $separateFiles = '';

        // Is it a quick or custom export?
        $isQuickExport = $quickOrCustom === 'quick';

        if ($outputFormat === 'astext') {
            $GLOBALS['asfile'] = false;
        } else {
            $GLOBALS['asfile'] = true;
            if ($asSeparateFiles && $compressionParam === 'zip') {
                $separateFiles = $asSeparateFiles;
            }

            if (in_array($compressionParam, $compressionMethods, true)) {
                $GLOBALS['compression'] = $compressionParam;
                $GLOBALS['buffer_needed'] = true;
            }

            if (($isQuickExport && $quickExportOnServer) || (! $isQuickExport && $onServerParam)) {
                // Will we save dump on server?
                $GLOBALS['save_on_server'] = ! empty($config->settings['SaveDir']);
            }
        }

        $tableNames = [];
        // Generate error url and check for needed variables
        if ($exportType === ExportType::Database) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            // Check if we have something to export
            $tableNames = $GLOBALS['table_select'] ?? [];
            Assert::isArray($tableNames);
            Assert::allString($tableNames);
        } elseif ($exportType === ExportType::Table) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            if (Current::$table === '') {
                return $this->response->missingParameterError('table');
            }
        }

        // Merge SQL Query aliases with Export aliases from
        // export page, Export page aliases are given more
        // preference over SQL Query aliases.
        $parser = new Parser(Current::$sqlQuery);
        $aliases = [];
        if (! empty($parser->statements[0]) && $parser->statements[0] instanceof SelectStatement) {
            $aliases = $parser->statements[0]->getAliases(Current::$database);
        }

        if ($aliasesParam !== null && $aliasesParam !== []) {
            $aliases = $this->export->mergeAliases($aliases, $aliasesParam);
            $_SESSION['tmpval']['aliases'] = $aliasesParam;
        }

        /**
         * Increase time limit for script execution and initializes some variables
         */
        Util::setTimeLimit();
        if (! empty($config->settings['MemoryLimit'])) {
            ini_set('memory_limit', $config->settings['MemoryLimit']);
        }

        register_shutdown_function([$this->export, 'shutdown']);
        // Start with empty buffer
        $this->export->dumpBuffer = '';
        $this->export->dumpBufferLength = 0;

        // Array of dump buffers - used in separate file exports
        $this->export->dumpBufferObjects = [];

        // We send fake headers to avoid browser timeout when buffering
        $GLOBALS['time_start'] = time();

        $GLOBALS['output_kanji_conversion'] = Encoding::canConvertKanji();

        // Do we need to convert charset?
        $GLOBALS['output_charset_conversion'] = $GLOBALS['asfile']
            && Encoding::isSupported()
            && isset($GLOBALS['charset']) && $GLOBALS['charset'] !== 'utf-8';

        // Use on the fly compression?
        $GLOBALS['onfly_compression'] = $config->settings['CompressOnFly']
            && $GLOBALS['compression'] === 'gzip';
        if ($GLOBALS['onfly_compression']) {
            $GLOBALS['memory_limit'] = $this->export->getMemoryLimit();
        }

        // Generate filename and mime type if needed
        $mimeType = '';
        if ($GLOBALS['asfile']) {
            $filenameTemplate = $request->getParsedBodyParamAsString('filename_template');

            if ((bool) $rememberTemplate) {
                $this->export->rememberFilename($config, $exportType, $filenameTemplate);
            }

            $filename = $this->export->getFinalFilename(
                $exportPlugin,
                $GLOBALS['compression'],
                Sanitize::sanitizeFilename(Util::expandUserString($filenameTemplate), true),
            );

            $mimeType = $this->export->getMimeType($exportPlugin, $GLOBALS['compression']);
        }

        // For raw query export, filename will be export.extension
        if ($exportType === ExportType::Raw) {
            $filename = $this->export->getFinalFilename($exportPlugin, $GLOBALS['compression'], 'export');
        }

        // Open file on server if needed
        if ($GLOBALS['save_on_server']) {
            [$GLOBALS['save_filename'], $message, $GLOBALS['file_handle']] = $this->export->openFile(
                $filename,
                $isQuickExport,
            );

            // problem opening export file on server?
            if ($message !== null) {
                $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
                $this->response->redirect($location);

                return $this->response->response();
            }
        } elseif ($GLOBALS['asfile']) {
            /**
             * Send headers depending on whether the user chose to download a dump file
             * or not
             */
            // Download
            // (avoid rewriting data containing HTML with anchors and forms;
            // this was reported to happen under Plesk)
            ini_set('url_rewriter.tags', '');
            $filename = Sanitize::sanitizeFilename($filename);
            Core::downloadHeader($filename, $mimeType);
        } else {
            // HTML
            if ($exportType === ExportType::Database) {
                $GLOBALS['num_tables'] = count($tableNames);
                if ($GLOBALS['num_tables'] === 0) {
                    $GLOBALS['message'] = Message::error(
                        __('No tables found in database.'),
                    );
                    /** @var DatabaseExportController $controller */
                    $controller = ContainerBuilder::getContainer()->get(DatabaseExportController::class);

                    return $controller($request);
                }
            }

            echo $this->export->getHtmlForDisplayedExportHeader($exportType, Current::$database, Current::$table);
        }

        try {
            // Re - initialize
            $this->export->dumpBuffer = '';
            $this->export->dumpBufferLength = 0;

            // TODO: This is a temporary hack to avoid GLOBALS. Replace this with something better.
            if ($exportPlugin instanceof ExportXml) {
                $exportPlugin->setTables($tableNames);
            }

            // Add possibly some comments to export
            if (! $exportPlugin->exportHeader()) {
                throw new ExportException('Failure during header export.');
            }

            /**
             * Builds the dump
             */
            if ($exportType === ExportType::Server) {
                if ($dbSelect === null) {
                    $dbSelect = '';
                }

                $this->export->exportServer($dbSelect, $exportPlugin, $aliases, $separateFiles);
            } elseif ($exportType === ExportType::Database) {
                if (! is_array($tableStructure)) {
                    $tableStructure = [];
                }

                if (! isset($GLOBALS['table_data']) || ! is_array($GLOBALS['table_data'])) {
                    $GLOBALS['table_data'] = [];
                }

                if ($structureOrDataForced) {
                    $tableStructure = $tableNames;
                    $GLOBALS['table_data'] = $tableNames;
                }

                if ($lockTables) {
                    $this->export->lockTables(DatabaseName::from(Current::$database), $tableNames, 'READ');
                    try {
                        $this->export->exportDatabase(
                            DatabaseName::from(Current::$database),
                            $tableNames,
                            $tableStructure,
                            $GLOBALS['table_data'],
                            $exportPlugin,
                            $aliases,
                            $separateFiles,
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportDatabase(
                        DatabaseName::from(Current::$database),
                        $tableNames,
                        $tableStructure,
                        $GLOBALS['table_data'],
                        $exportPlugin,
                        $aliases,
                        $separateFiles,
                    );
                }
            } elseif ($exportType === ExportType::Raw) {
                Export::exportRaw($exportPlugin, Current::$database, Current::$sqlQuery);
            } else {
                // We export just one table

                $allrows = $request->getParsedBodyParamAsString('allrows', '');
                $limitTo = $request->getParsedBodyParamAsString('limit_to', '0');
                $limitFrom = $request->getParsedBodyParamAsString('limit_from', '0');

                if ($lockTables) {
                    try {
                        $this->export->lockTables(DatabaseName::from(Current::$database), [Current::$table], 'READ');
                        $this->export->exportTable(
                            Current::$database,
                            Current::$table,
                            $exportPlugin,
                            $allrows,
                            $limitTo,
                            $limitFrom,
                            Current::$sqlQuery,
                            $aliases,
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportTable(
                        Current::$database,
                        Current::$table,
                        $exportPlugin,
                        $allrows,
                        $limitTo,
                        $limitFrom,
                        Current::$sqlQuery,
                        $aliases,
                    );
                }
            }

            if (! $exportPlugin->exportFooter()) {
                throw new ExportException('Failure during footer export.');
            }
        } catch (ExportException) {
            // Ignore
        }

        if ($GLOBALS['save_on_server'] && $GLOBALS['message'] instanceof Message) {
            $location = $this->export->getPageLocationAndSaveMessage($exportType, $GLOBALS['message']);
            $this->response->redirect($location);

            return $this->response->response();
        }

        /**
         * Send the dump as a file...
         */
        if (empty($GLOBALS['asfile'])) {
            echo $this->export->getHtmlForDisplayedExportFooter($exportType, Current::$database, Current::$table);

            return $this->response->response();
        }

        // Convert the charset if required.
        if ($GLOBALS['output_charset_conversion']) {
            $this->export->dumpBuffer = Encoding::convertString(
                'utf-8',
                $GLOBALS['charset'],
                $this->export->dumpBuffer,
            );
        }

        // Compression needed?
        if ($GLOBALS['compression']) {
            if ($separateFiles !== '') {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBufferObjects,
                    $GLOBALS['compression'],
                    $filename,
                );
            } else {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBuffer,
                    $GLOBALS['compression'],
                    $filename,
                );
            }
        }

        /* If we saved on server, we have to close file now */
        if ($GLOBALS['save_on_server']) {
            $message = $this->export->closeFile(
                $GLOBALS['file_handle'],
                $this->export->dumpBuffer,
                $GLOBALS['save_filename'],
            );
            $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
            $this->response->redirect($location);

            return $this->response->response();
        }

        return $this->responseFactory->createResponse()->write($this->export->dumpBuffer);
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

        if (isset($postParams['table_select'])) {
            $GLOBALS['table_select'] = $postParams['table_select'];
        }

        if (isset($postParams['table_data'])) {
            $GLOBALS['table_data'] = $postParams['table_data'];
        }

        if (isset($postParams['maxsize'])) {
            $GLOBALS['maxsize'] = $postParams['maxsize'];
        }

        if (isset($postParams['charset'])) {
            $GLOBALS['charset'] = $postParams['charset'];
        }

        if (isset($postParams['compression'])) {
            $GLOBALS['compression'] = $postParams['compression'];
        }

        if (isset($postParams['knjenc'])) {
            $GLOBALS['knjenc'] = $postParams['knjenc'];
        }

        if (isset($postParams['xkana'])) {
            $GLOBALS['xkana'] = $postParams['xkana'];
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

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if (isset($postParams['csv_columns'])) {
            $GLOBALS['csv_columns'] = $postParams['csv_columns'];
        }
    }
}
